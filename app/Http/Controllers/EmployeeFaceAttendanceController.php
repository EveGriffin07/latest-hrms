<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Attendance;
use App\Models\AttendanceScanSession;
use App\Models\Employee;
use App\Models\FaceAuditLog;
use App\Services\AuditLogService;
use App\Services\FaceService;

class EmployeeFaceAttendanceController extends Controller
{
    public function __construct(private FaceService $faceService)
    {
    }

    /**
     * Preconditions: Employee logged in; must have face_enrolled and active template.
     * If not enrolled: block scanning and prompt "Enroll face first."
     */
    public function show()
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can verify attendance.');

        $faceData = $employee->faceData;
        $empTable = $employee->getTable();
        $faceEnrolledOk = ! Schema::hasColumn($empTable, 'face_enrolled')
            ? true
            : ($employee->face_enrolled ?? false);
        $hasEnrollment = $faceData
            && $faceEnrolledOk
            && (($faceData->status ?? 'ACTIVE') === 'ACTIVE');
        $today = now()->toDateString();
        $todayRecord = $employee->attendance()->where('date', $today)->first();
        $mode = $this->detectMode($todayRecord);
        $cooldownMinutes = config('services.face_api.cooldown_minutes', 2);
        $lastSuccessScan = $employee->attendanceScanSessions()
            ->where('status', 'SUCCESS')
            ->latest('scanned_at')
            ->first();

        return view('employee.attendance_face', compact(
            'employee',
            'hasEnrollment',
            'todayRecord',
            'mode',
            'cooldownMinutes',
            'lastSuccessScan'
        ));
    }

    /**
     * Auto-detect mode: no clock_in today → CHECK_IN; clock_in and no clock_out → CHECK_OUT; both → null (block).
     */
    private function detectMode(?Attendance $todayRecord): ?string
    {
        if (! $todayRecord) {
            return 'CHECK_IN';
        }
        if ($todayRecord->clock_in_time && ! $todayRecord->clock_out_time) {
            return 'CHECK_OUT';
        }
        return null; // already checked out today
    }

    /**
     * Attendance scan: create session, validate frames, match, apply anti-fraud rules, write attendance atomically.
     */
    public function check(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can verify attendance.');

        $faceData = $employee->faceData;
        $empTable = $employee->getTable();
        $faceEnrolledOk = ! Schema::hasColumn($empTable, 'face_enrolled')
            ? true
            : ($employee->face_enrolled ?? false);
        if (! $faceData || ! $faceEnrolledOk || ($faceData->status ?? 'ACTIVE') !== 'ACTIVE') {
            return back()->withErrors(['face' => 'Enroll face first.']);
        }

        $validated = $request->validate([
            'frames' => ['required', 'array', 'min:1', 'max:3'],
            'frames.*' => ['file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $frames = $request->file('frames');
        $deviceId = $request->header('X-Device-Id') ?? $request->input('device_id') ?? 'web';
        $today = now()->toDateString();
        $todayRecord = $employee->attendance()->where('date', $today)->first();
        $mode = $this->detectMode($todayRecord);

        // Anti-fraud: duplicate punch
        if ($mode === null) {
            AuditLogService::log(
                AuditLogService::CATEGORY_ATTENDANCE,
                'attendance_failed',
                AuditLogService::STATUS_FAILED,
                'Attendance FAILED (reason: DUPLICATE_BLOCK)',
                ['reason' => 'Already checked out today'],
                $employee->employee_id,
                AuditLogService::SEVERITY_WARN
            );
            return back()->withErrors(['face' => 'You have already checked out today.']);
        }

        // Anti-fraud: cooldown
        $cooldownMinutes = config('services.face_api.cooldown_minutes', 2);
        $lastSuccess = $employee->attendanceScanSessions()
            ->where('status', 'SUCCESS')
            ->where('scanned_at', '>=', now()->subMinutes($cooldownMinutes))
            ->exists();
        if ($lastSuccess) {
            $session = AttendanceScanSession::create([
                'session_id' => (string) Str::uuid(),
                'employee_id' => $employee->employee_id,
                'scanned_at' => now(),
                'device_id' => $deviceId,
                'mode' => $mode,
                'status' => 'FAILED',
                'failure_reason' => 'POLICY_BLOCK',
            ]);
            AuditLogService::log(
                AuditLogService::CATEGORY_ATTENDANCE,
                'attendance_failed',
                AuditLogService::STATUS_FAILED,
                'Attendance FAILED (reason: cooldown)',
                ['session_id' => $session->session_id, 'failure_reason' => 'POLICY_BLOCK'],
                $employee->employee_id,
                AuditLogService::SEVERITY_WARN
            );
            Log::warning('face_attendance_cooldown', [
                'employee_id' => $employee->employee_id,
                'session_id' => $session->session_id,
            ]);
            return back()->withErrors(['face' => 'Please wait ' . $cooldownMinutes . ' minutes between scans. Try again.']);
        }

        // Start attendance scan session
        $session = AttendanceScanSession::create([
            'session_id' => (string) Str::uuid(),
            'employee_id' => $employee->employee_id,
            'scanned_at' => now(),
            'device_id' => $deviceId,
            'mode' => $mode,
            'status' => 'IN_PROGRESS',
        ]);

        AuditLogService::log(
            AuditLogService::CATEGORY_ATTENDANCE,
            'face_scan_started',
            AuditLogService::STATUS_SUCCESS,
            'Face scan started (' . $mode . ')',
            ['session_id' => $session->session_id, 'mode' => $mode, 'device_id' => $deviceId],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO
        );

        $result = $this->faceService->attend(
            $employee->employee_id,
            $frames,
            $employee->faceData->embedding
        );

        if (! $result['ok']) {
            $reason = $result['failure_reason'] ?? 'LOW_QUALITY';
            $session->update([
                'status' => 'FAILED',
                'failure_reason' => $reason,
            ]);
            FaceAuditLog::log('attendance_scan', $employee->employee_id, false, Auth::id(), null, $reason, null, null, ['session_id' => $session->session_id, 'mode' => $mode]);
            AuditLogService::log(
                AuditLogService::CATEGORY_ATTENDANCE,
                'attendance_failed',
                AuditLogService::STATUS_FAILED,
                'Attendance FAILED (reason: ' . $reason . ')',
                ['session_id' => $session->session_id, 'mode' => $mode, 'failure_reason' => $reason],
                $employee->employee_id,
                AuditLogService::SEVERITY_WARN
            );
            Log::warning('face_attendance_failed', ['employee_id' => $employee->employee_id, 'session_id' => $session->session_id, 'reason' => $reason]);
            return back()->withErrors(['face' => $result['message'] ?? 'Verification failed. Try again.']);
        }

        // Business rule: attendance is only accepted if similarity score >= threshold
        $score = $result['score'] ?? null;
        $attendScoreThreshold = 0.70;
        if (! ($result['matched'] ?? false) || ($score !== null && (float) $score < $attendScoreThreshold)) {
            $reason = $result['failure_reason'] ?? 'BELOW_THRESHOLD';
            if (($result['matched'] ?? false) && $score !== null && (float) $score < $attendScoreThreshold) {
                $reason = 'BELOW_REQUIRED_SCORE';
            }
            $session->update([
                'status' => 'FAILED',
                'failure_reason' => $reason,
                'confidence_score' => $score,
            ]);
            FaceAuditLog::log('attendance_scan', $employee->employee_id, false, Auth::id(), null, $reason, $score, null, ['session_id' => $session->session_id, 'mode' => $mode]);
            AuditLogService::log(
                AuditLogService::CATEGORY_ATTENDANCE,
                'attendance_failed',
                AuditLogService::STATUS_FAILED,
                'Attendance FAILED (reason: ' . $reason . ', sim: ' . ($score !== null ? round($score, 2) : 'N/A') . ')',
                ['session_id' => $session->session_id, 'mode' => $mode, 'similarity_score' => $score, 'failure_reason' => $reason],
                $employee->employee_id,
                AuditLogService::SEVERITY_WARN
            );
            $msg = $result['message'] ?? 'Face did not match. Try again.';
            if ($reason === 'BELOW_REQUIRED_SCORE') {
                $msg = 'Score too low (need ≥ ' . number_format($attendScoreThreshold, 2) . '). Try again.';
            }
            return back()->withErrors(['face' => $msg]);
        }

        $score = $result['score'] ?? null;

        // Re-check duplicate punch inside transaction (atomic)
        $written = DB::transaction(function () use ($employee, $today, $mode, $deviceId, $score, $session) {
            $current = $employee->attendance()->where('date', $today)->lockForUpdate()->first();
            $allowedMode = $this->detectMode($current);
            if ($allowedMode !== $mode) {
                return false; // e.g. another tab checked out meanwhile
            }
            if ($mode === 'CHECK_IN') {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->employee_id,
                        'date' => $today,
                    ],
                    [
                        'clock_in_time' => now()->format('H:i:s'),
                        'clock_out_time' => null,
                        'at_status' => 'present',
                        'verified_method' => 'face',
                        'verify_score' => $score,
                        'device_id' => $deviceId,
                    ]
                );
            } else {
                $att = $employee->attendance()->where('date', $today)->first();
                if (! $att || ! $att->clock_in_time) {
                    return false;
                }
                $att->update([
                    'clock_out_time' => now()->format('H:i:s'),
                    'device_id' => $deviceId,
                    'verified_method' => 'face',
                    'verify_score' => $score,
                ]);
            }
            $session->update([
                'status' => 'SUCCESS',
                'failure_reason' => null,
                'confidence_score' => $score,
            ]);
            return true;
        });

        if (! $written) {
            return back()->withErrors(['face' => 'Attendance state changed. Please try again.']);
        }

        $timeStr = now()->format('H:i');
        $message = $mode === 'CHECK_IN'
            ? "Check-in recorded at {$timeStr}"
            : "Check-out recorded at {$timeStr}";

        FaceAuditLog::log('attendance_scan', $employee->employee_id, true, Auth::id(), null, null, $score, null, ['session_id' => $session->session_id, 'mode' => $mode]);
        AuditLogService::log(
            AuditLogService::CATEGORY_ATTENDANCE,
            $mode === 'CHECK_IN' ? 'check_in_success' : 'check_out_success',
            AuditLogService::STATUS_SUCCESS,
            'Attendance ' . $mode . ' SUCCESS (sim: ' . ($score !== null ? round($score, 2) : 'N/A') . ')',
            ['session_id' => $session->session_id, 'mode' => $mode, 'similarity_score' => $score, 'time' => $timeStr],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO
        );
        Log::info('face_attendance_success', ['employee_id' => $employee->employee_id, 'session_id' => $session->session_id, 'mode' => $mode, 'score' => $score]);

        return back()->with('success', $message);
    }
}
