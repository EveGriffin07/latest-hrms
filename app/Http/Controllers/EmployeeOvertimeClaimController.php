<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimeClaim;
use App\Models\PayrollPeriod;
use App\Services\OtClaimApproverResolver;
use App\Services\OtClaimAudit;
use App\Services\OtClaimNotifier;
use App\Services\OvertimeDayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmployeeOvertimeClaimController extends Controller
{
    public function index()
    {
        $employee = $this->currentEmployee();
        $claims = OvertimeClaim::where('employee_id', $employee->employee_id)
            ->with(['period', 'employee'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
        return view('employee.overtime_claims', compact('employee', 'claims'));
    }

    public function create()
    {
        $employee = $this->currentEmployee();
        $areas = Area::orderBy('name')->get(['id', 'name']);
        $supervisor = $this->resolveSupervisorForClaim($employee, null);
        $supervisorName = $supervisor ? $supervisor->name : null;
        $departmentName = $employee->department ? $employee->department->department_name : null;
        $hasSupervisor = (bool) $supervisor;
        return view('employee.overtime_claim_form', [
            'areas' => $areas,
            'supervisorName' => $supervisorName,
            'departmentName' => $departmentName,
            'hasSupervisor' => $hasSupervisor,
        ]);
    }

    /** AJAX: get day type, suggested rate and official clock-out for a given date. */
    public function dayInfo(Request $request)
    {
        $employee = $this->currentEmployee();
        $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'ot_mode' => ['nullable', 'in:NORMAL,HOLIDAY_REST'],
        ]);
        $date = Carbon::parse($request->input('date'))->startOfDay();
        $otMode = $request->input('ot_mode', 'NORMAL');

        $dayType = OvertimeDayService::getDayType($employee->employee_id, $date);
        $dayTypeLabel = OvertimeDayService::labelForDayType($dayType);
        $rate = OvertimeDayService::getRateForDayType($dayType);
        $officialClockOutMinutes = OvertimeDayService::getOfficialClockOutMinutes($employee->employee_id, $date);

        // Attendance clock-out (reference only)
        $attendance = Attendance::where('employee_id', $employee->employee_id)
            ->whereDate('date', $date->toDateString())
            ->first();
        $attendanceClockOutMinutes = null;
        $attendanceClockOutLabel = null;
        if ($attendance && $attendance->clock_out_time) {
            $att = Carbon::parse($attendance->clock_out_time);
            $attendanceClockOutMinutes = $att->hour * 60 + $att->minute;
            $attendanceClockOutLabel = $att->format('H:i');
        }

        $validationMessage = 'OK';
        if ($otMode === 'NORMAL' && $dayType !== OvertimeDayService::TYPE_NORMAL) {
            $validationMessage = 'Selected mode is Normal OT but this date is ' . strtolower($dayTypeLabel) . '.';
        } elseif ($otMode === 'HOLIDAY_REST' && $dayType === OvertimeDayService::TYPE_NORMAL) {
            $validationMessage = 'Selected mode is Public Holiday / Rest Day OT but this date is a normal working day.';
        }

        return response()->json([
            'day_type' => $dayType,
            'day_type_label' => $dayTypeLabel,
            'rate_value' => $rate,
            'rate_label' => $rate . 'x',
            'official_clock_out_minutes' => $officialClockOutMinutes,
            'attendance_clock_out_minutes' => $attendanceClockOutMinutes,
            'attendance_clock_out_label' => $attendanceClockOutLabel,
            'validation_message' => $validationMessage,
        ]);
    }

    /** AJAX: check if employee already has a claim for the given date. Returns existing claim summary or empty. */
    public function checkDuplicate(Request $request)
    {
        $employee = $this->currentEmployee();
        $request->validate(['date' => ['required', 'date', 'before_or_equal:today']]);
        $date = $request->input('date');
        $existing = OvertimeClaim::where('employee_id', $employee->employee_id)
            ->whereDate('date', $date)
            ->whereNotIn('status', [OvertimeClaim::STATUS_CANCELLED, OvertimeClaim::STATUS_SUPERVISOR_REJECTED, OvertimeClaim::STATUS_ADMIN_REJECTED])
            ->first();
        if (!$existing) {
            return response()->json(['has_duplicate' => false]);
        }
        return response()->json([
            'has_duplicate' => true,
            'claim' => [
                'id' => $existing->id,
                'date' => $existing->date->format('Y-m-d'),
                'status' => $existing->status,
                'hours' => (float) $existing->hours,
                'submitted_at' => $existing->submitted_at ? $existing->submitted_at->format('M j, g:i A') : null,
                'edit_url' => route('employee.ot_claims.edit', $existing),
                'view_url' => route('employee.ot_claims.index'),
                'is_editable' => $existing->isEditableByEmployee(),
            ],
        ]);
    }

    private function resolveSupervisorForClaim(Employee $employee, ?int $routingAreaId): ?\App\Models\User
    {
        $userId = OtClaimApproverResolver::resolve($employee, $routingAreaId);
        return $userId ? \App\Models\User::find($userId) : null;
    }

    public function store(Request $request)
    {
        $employee = $this->currentEmployee();
        $submitNow = $request->boolean('submit_now');
        $user = Auth::user();
        $role = strtolower(trim((string) ($user?->role ?? '')));
        $isSupervisorRequester = $role === 'supervisor';
        if ($submitNow && !$isSupervisorRequester && !OtClaimApproverResolver::resolve($employee, $request->input('route_to_area_id') ? (int) $request->input('route_to_area_id') : null)) {
            throw ValidationException::withMessages(['date' => 'No supervisor assigned to your department. Contact HR.']);
        }

        $rules = [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'rate_type' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'ot_mode' => ['required', 'in:NORMAL,HOLIDAY_REST'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'supporting_info' => ['nullable', 'string', 'max:1000'],
            'submit_now' => ['nullable', 'boolean'],
            'location_type' => ['required', 'in:INSIDE,OUTSIDE,CLIENT_SITE,REMOTE_WFH,OTHER'],
            'location_other' => ['nullable', 'string', 'max:255'],
            'proof_image' => ['nullable', 'image', 'max:5120'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
            'missing_proof_reason' => ['nullable', 'string', 'max:1000'],
            'route_to_area_id' => ['nullable', 'exists:areas,id'],
        ];
        $validated = $request->validate($rules);

        $validated['hours'] = $this->computeClaimHours(
            $employee->employee_id,
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            (int) ($validated['break_minutes'] ?? 0),
            $validated['ot_mode']
        );
        if ($validated['hours'] < 0.25) {
            throw ValidationException::withMessages(['end_time' => 'Total hours after break deduction must be at least 0.25.']);
        }
        if ($validated['hours'] > 8) {
            throw ValidationException::withMessages(['end_time' => 'Maximum claim per day is 8 hours.']);
        }

        $this->validateLocationAndProof($request, $validated, $employee->employee_id, null);
        $this->validateNoDuplicate($employee->employee_id, $validated['date'], null);

        $period = PayrollPeriod::whereDate('start_date', '<=', $validated['date'])
            ->whereDate('end_date', '>=', $validated['date'])
            ->orderByDesc('start_date')
            ->first();

        $proofPath = $this->storeProofImage($request);
        $attachmentPath = $this->storeAttachment($request);
        $noProofFlag = false;

        $status = $submitNow
            ? ($isSupervisorRequester ? OvertimeClaim::STATUS_ADMIN_PENDING : OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)
            : OvertimeClaim::STATUS_DRAFT;
        $routingAreaId = isset($validated['route_to_area_id']) ? (int) $validated['route_to_area_id'] : null;
        $areaId = $routingAreaId ?? $employee->user->area_id ?? null;
        $approverId = $status === OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR
            ? OtClaimApproverResolver::resolve($employee, $routingAreaId)
            : ($status === OvertimeClaim::STATUS_ADMIN_PENDING ? Auth::id() : null);
        $this->syncUserDeptId($employee);

        $claim = DB::transaction(function () use ($employee, $validated, $period, $status, $proofPath, $attachmentPath, $noProofFlag, $areaId, $approverId) {
            $claim = OvertimeClaim::create([
                'employee_id' => $employee->employee_id,
                'user_id' => $employee->user_id,
                'area_id' => $areaId,
                'period_id' => $period?->period_id,
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'break_minutes' => (int) ($validated['break_minutes'] ?? 0),
                'hours' => $validated['hours'],
                'rate_type' => $validated['rate_type'] ?? 1.0,
                'reason' => $validated['reason'],
                'supporting_info' => $validated['supporting_info'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => $status,
                'submitted_at' => in_array($status, [OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR, OvertimeClaim::STATUS_ADMIN_PENDING], true) ? now() : null,
                'supervisor_id' => $approverId,
                'location_type' => $validated['location_type'],
                'location_other' => $validated['location_other'] ?? null,
                'proof_image_path' => $proofPath,
                'missing_proof_reason' => $validated['missing_proof_reason'] ?? null,
                'no_proof_flag' => $noProofFlag,
            ]);
            if (in_array($status, [OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR, OvertimeClaim::STATUS_ADMIN_PENDING], true)) {
                OtClaimAudit::log(OtClaimAudit::ACTION_SUBMITTED, $claim, null, $claim->status, [], 'OT claim submitted');
                OtClaimNotifier::onSubmitted($claim->load('employee.user'));
            }
            return $claim;
        });

        $message = $status === OvertimeClaim::STATUS_ADMIN_PENDING
            ? 'OT claim submitted and sent to admin.'
            : ($status === OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR
                ? 'OT claim submitted and sent to your supervisor.'
                : 'OT claim saved as draft.');
        return redirect()->route('employee.ot_claims.index')->with('success', $message);
    }

    /** Compute hours from start_time, end_time, break_minutes. Round to nearest 0.5. Overnight: end < start = next day. */
    private function computeHoursFromTimes(string $start, string $end, int $breakMinutes): float
    {
        $s = Carbon::createFromFormat('H:i', $start);
        $e = Carbon::createFromFormat('H:i', $end);
        if ($e <= $s) {
            $e->addDay();
        }
        $totalMin = max(0, $s->diffInMinutes($e) - $breakMinutes);
        $hours = $totalMin / 60;
        return round($hours * 2) / 2; // nearest 0.5
    }

    /**
     * Compute claim hours using day type rules.
     * - Normal OT: only time after official clock-out is counted.
     * - Public Holiday / Rest Day OT: full selected time minus break.
     */
    private function computeClaimHours(int $employeeId, string $date, string $start, string $end, int $breakMinutes, string $otMode): float
    {
        $baseDate = Carbon::parse($date)->startOfDay();
        $s = Carbon::createFromFormat('H:i', $start);
        $e = Carbon::createFromFormat('H:i', $end);
        if ($e <= $s) {
            $e->addDay();
        }

        $startMinutes = $s->hour * 60 + $s->minute;
        $endMinutes = $startMinutes + $s->diffInMinutes($e);

        $effectiveStartMinutes = $startMinutes;
        if ($otMode === 'NORMAL') {
            $officialClockOutMinutes = OvertimeDayService::getOfficialClockOutMinutes($employeeId, $baseDate);
            if ($officialClockOutMinutes !== null) {
                $effectiveStartMinutes = max($effectiveStartMinutes, $officialClockOutMinutes);
            }
        }

        $totalMinutes = max(0, $endMinutes - $effectiveStartMinutes - $breakMinutes);
        $hours = $totalMinutes / 60;
        return round($hours * 2) / 2;
    }

    private function storeAttachment(Request $request, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile('attachment')) {
            return $existingPath;
        }
        $file = $request->file('attachment');
        $path = $file->store('ot_claims_attachments', 'public');
        return $path ?: $existingPath;
    }

    public function edit(OvertimeClaim $claim)
    {
        $employee = $this->currentEmployee();
        if ($claim->employee_id !== $employee->employee_id || !$claim->isEditableByEmployee()) {
            abort(403, 'You can only edit your own claim when it is draft or returned.');
        }
        $areas = Area::orderBy('name')->get(['id', 'name']);
        $supervisor = $this->resolveSupervisorForClaim($employee, $claim->area_id);
        $supervisorName = $supervisor ? $supervisor->name : null;
        $departmentName = $employee->department ? $employee->department->department_name : null;
        $hasSupervisor = (bool) $supervisor;
        return view('employee.overtime_claim_form', [
            'claim' => $claim,
            'areas' => $areas,
            'supervisorName' => $supervisorName,
            'departmentName' => $departmentName,
            'hasSupervisor' => $hasSupervisor,
        ]);
    }

    public function update(Request $request, OvertimeClaim $claim)
    {
        $employee = $this->currentEmployee();
        if ($claim->employee_id !== $employee->employee_id || !$claim->isEditableByEmployee()) {
            abort(403, 'You can only edit your own claim when it is draft or returned.');
        }
        $submitNow = $request->boolean('submit_now');
        $user = Auth::user();
        $role = strtolower(trim((string) ($user?->role ?? '')));
        $isSupervisorRequester = $role === 'supervisor';
        if ($submitNow && !$isSupervisorRequester && !OtClaimApproverResolver::resolve($employee, $request->input('route_to_area_id') ? (int) $request->input('route_to_area_id') : null)) {
            throw ValidationException::withMessages(['date' => 'No supervisor assigned to your department. Contact HR.']);
        }
        $rules = [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'rate_type' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'ot_mode' => ['required', 'in:NORMAL,HOLIDAY_REST'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'supporting_info' => ['nullable', 'string', 'max:1000'],
            'submit_now' => ['nullable', 'boolean'],
            'location_type' => ['required', 'in:INSIDE,OUTSIDE,CLIENT_SITE,REMOTE_WFH,OTHER'],
            'location_other' => ['nullable', 'string', 'max:255'],
            'proof_image' => ['nullable', 'image', 'max:5120'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
            'missing_proof_reason' => ['nullable', 'string', 'max:1000'],
            'route_to_area_id' => ['nullable', 'exists:areas,id'],
        ];
        $validated = $request->validate($rules);

        $validated['hours'] = $this->computeClaimHours(
            $employee->employee_id,
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            (int) ($validated['break_minutes'] ?? 0),
            $validated['ot_mode']
        );
        if ($validated['hours'] < 0.25) {
            throw ValidationException::withMessages(['end_time' => 'Total hours after break deduction must be at least 0.25.']);
        }
        if ($validated['hours'] > 8) {
            throw ValidationException::withMessages(['end_time' => 'Maximum claim per day is 8 hours.']);
        }

        $this->validateLocationAndProof($request, $validated, $employee->employee_id, $claim);
        $this->validateNoDuplicate($employee->employee_id, $validated['date'], $claim->id);

        $period = PayrollPeriod::whereDate('start_date', '<=', $validated['date'])
            ->whereDate('end_date', '>=', $validated['date'])
            ->orderByDesc('start_date')
            ->first();

        $proofPath = $this->storeProofImage($request, $claim->proof_image_path);
        $attachmentPath = $this->storeAttachment($request, $claim->attachment_path);
        $noProofFlag = false;

        $newStatus = $submitNow
            ? ($isSupervisorRequester ? OvertimeClaim::STATUS_ADMIN_PENDING : OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)
            : $claim->status;
        $beforeStatus = $claim->status;
        $routingAreaId = isset($validated['route_to_area_id']) ? (int) $validated['route_to_area_id'] : null;
        $areaId = $routingAreaId ?? $employee->user->area_id ?? $claim->area_id;
        $approverId = $submitNow
            ? ($newStatus === OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR
                ? OtClaimApproverResolver::resolve($employee, $routingAreaId)
                : Auth::id())
            : $claim->supervisor_id;
        $this->syncUserDeptId($employee);

        DB::transaction(function () use ($claim, $validated, $period, $newStatus, $beforeStatus, $submitNow, $proofPath, $attachmentPath, $noProofFlag, $areaId, $approverId) {
            $claim->update([
                'user_id' => $claim->employee->user_id,
                'area_id' => $areaId,
                'period_id' => $period?->period_id,
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'break_minutes' => (int) ($validated['break_minutes'] ?? 0),
                'hours' => $validated['hours'],
                'rate_type' => $validated['rate_type'] ?? 1.0,
                'reason' => $validated['reason'],
                'supporting_info' => $validated['supporting_info'] ?? null,
                'attachment_path' => $attachmentPath ?? $claim->attachment_path,
                'status' => $newStatus,
                'submitted_at' => $submitNow ? now() : $claim->submitted_at,
                'supervisor_remark' => $newStatus === OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR ? null : $claim->supervisor_remark,
                'supervisor_id' => $approverId,
                'location_type' => $validated['location_type'],
                'location_other' => $validated['location_other'] ?? null,
                'proof_image_path' => $proofPath ?? $claim->proof_image_path,
                'missing_proof_reason' => $validated['missing_proof_reason'] ?? null,
                'no_proof_flag' => $noProofFlag,
            ]);
            if ($submitNow) {
                OtClaimAudit::log($beforeStatus === OvertimeClaim::STATUS_SUPERVISOR_RETURNED ? OtClaimAudit::ACTION_RESUBMITTED : OtClaimAudit::ACTION_SUBMITTED, $claim, $beforeStatus, $newStatus);
                OtClaimNotifier::onSubmitted($claim->load('employee.user'));
            }
        });

        $message = $submitNow
            ? ($newStatus === OvertimeClaim::STATUS_ADMIN_PENDING ? 'OT claim submitted and sent to admin.' : 'OT claim resubmitted to your supervisor.')
            : 'OT claim updated.';
        return redirect()->route('employee.ot_claims.index')->with('success', $message);
    }

    public function cancel(OvertimeClaim $claim)
    {
        $employee = $this->currentEmployee();
        if ($claim->employee_id !== $employee->employee_id || !$claim->isCancellableByEmployee()) {
            abort(403, 'You can only cancel a claim that is pending supervisor and not yet acted on.');
        }
        $before = $claim->status;
        $claim->update([
            'status' => OvertimeClaim::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        OtClaimAudit::log(OtClaimAudit::ACTION_CANCELLED, $claim, $before, OvertimeClaim::STATUS_CANCELLED);
        return redirect()->route('employee.ot_claims.index')->with('success', 'OT claim cancelled.');
    }

    private function currentEmployee(): Employee
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->user_id)->first();
        abort_unless($employee, 403, 'Employee profile not found.');
        return $employee;
    }

    private function validateNoDuplicate(int $employeeId, string $date, ?int $excludeClaimId): void
    {
        $q = OvertimeClaim::where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->whereNotIn('status', [OvertimeClaim::STATUS_CANCELLED, OvertimeClaim::STATUS_SUPERVISOR_REJECTED, OvertimeClaim::STATUS_ADMIN_REJECTED]);
        if ($excludeClaimId) {
            $q->where('id', '!=', $excludeClaimId);
        }
        if ($q->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['date' => 'You already have an OT claim for this date.']);
        }
    }

    private function validateNoOverlap(int $employeeId, string $date, float $hours, ?int $excludeClaimId): void
    {
        // Optional: check overlap with another approved claim (same date, overlapping hours). Simplified: duplicate date check above is enough.
    }

    /** Validate location and proof; attendance clock-out is informational only (no hard block). */
    private function validateLocationAndProof(Request $request, array $validated, int $employeeId, ?OvertimeClaim $claim): void
    {
        $locationType = $validated['location_type'] ?? OvertimeClaim::LOCATION_INSIDE;

        if (in_array($locationType, [OvertimeClaim::LOCATION_INSIDE, OvertimeClaim::LOCATION_REMOTE_WFH], true)) {
            return;
        }

        // OUTSIDE / CLIENT_SITE / OTHER: no extra validation now; main Reason field is required already.
    }

    /** Store proof image; return path or null. Keeps existing path if no new file. */
    private function storeProofImage(Request $request, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile('proof_image')) {
            return $existingPath;
        }
        $file = $request->file('proof_image');
        $path = $file->store('ot_claims_proof', 'public');
        return $path ?: $existingPath;
    }

    /** Ensure user.dept_id is set from employee.department_id for department-based routing. */
    private function syncUserDeptId(Employee $employee): void
    {
        $user = $employee->user;
        if (!$user) {
            return;
        }
        $deptId = $employee->department_id ?? null;
        if ($user->dept_id != $deptId) {
            $user->update(['dept_id' => $deptId]);
        }
    }
}