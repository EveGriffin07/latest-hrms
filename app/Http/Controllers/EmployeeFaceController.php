<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Employee;
use App\Models\EmployeeFace;
use App\Models\EmployeeFaceTemplate;
use App\Models\FaceAuditLog;
use App\Models\FaceEnrollmentSession;
use App\Services\AuditLogService;
use App\Services\FaceService;

class EmployeeFaceController extends Controller
{
    public function __construct(private FaceService $faceService)
    {
    }

    /**
     * Show face enrollment form: upload a single photo. Photo is stored and sent to Face API
     * to extract embedding; attendance then compares camera scan with this embedding.
     * Admin users are redirected to the admin enroll page so they are not blocked by role middleware.
     */
    public function enrollForm()
    {
        $user = Auth::user();
        if ($this->isAdminRole($user)) {
            return redirect()->route('admin.face.enroll.self');
        }

        $employee = $user->employee;
        abort_if(! $employee, 403, 'Only employees can enroll face data.');

        $faceData = $employee->faceData;

        return view('employee.face_enroll', compact('employee', 'faceData'));
    }

    /**
     * Enroll from a single uploaded photo: store image in storage, call Face API to get embedding,
     * save embedding + photo_path. System uses this to compare with camera at attendance.
     * Used by both employee and admin (admin.face.enroll.self.post) routes.
     */
    public function enroll(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;
        abort_if(! $employee, 403, 'Only employees can enroll face data. If you are an admin, ensure your account is linked to an employee record.');
        abort_if(($employee->employee_status ?? 'inactive') !== 'active', 403, 'Only active employees can enroll face data.');

        if ($employee->faceData && $employee->faceTemplates()->where('is_active', true)->exists()) {
            return back()->withErrors(['face' => 'Face already enrolled. Contact HR to reset if you need to re-upload.']);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $photo = $request->file('photo');
        $logContext = ['employee_id' => $employee->employee_id];
        $deviceId = $request->header('X-Device-Id') ?? $request->input('device_id') ?? 'web';

        $session = FaceEnrollmentSession::create([
            'session_id' => (string) Str::uuid(),
            'employee_id' => $employee->employee_id,
            'device_id' => $deviceId,
            'status' => 'IN_PROGRESS',
            'frame_count' => 1,
            'started_at' => now(),
        ]);

        $result = $this->faceService->enroll($employee->employee_id, [$photo], null, $deviceId);

        if (! $result['ok']) {
            $session->update([
                'status' => 'FAILED',
                'failure_reason' => $result['message'] ?? 'Enrollment failed',
                'completed_at' => now(),
            ]);
            FaceAuditLog::log('enrollment_attempt', $employee->employee_id, false, Auth::id(), null, null, null, $result['message'] ?? 'Enrollment failed', ['session_id' => $session->session_id]);
            AuditLogService::log(
                AuditLogService::CATEGORY_FACE,
                'face_enrollment_failed',
                AuditLogService::STATUS_FAILED,
                'Face enrollment FAILED: ' . ($result['message'] ?? 'unknown'),
                ['session_id' => $session->session_id],
                $employee->employee_id,
                AuditLogService::SEVERITY_WARN
            );
            Log::warning('face_enroll_failed', $logContext + ['session_id' => $session->session_id, 'message' => $result['message'] ?? '']);
            return back()->withErrors(['face' => $result['message'] ?? 'Enrollment failed. Please use a clear face photo (one person, good lighting).'])->withInput();
        }

        $templateEmbedding = $result['template'] ?? $result['embedding'] ?? null;
        if (! $templateEmbedding) {
            $session->update(['status' => 'FAILED', 'failure_reason' => 'No embedding from API', 'completed_at' => now()]);
            FaceAuditLog::log('enrollment_attempt', $employee->employee_id, false, Auth::id(), null, 'TEMPLATE_MISSING', null, 'Template missing', ['session_id' => $session->session_id]);
            return back()->withErrors(['face' => 'Enrollment failed. Try again.']);
        }

        $photoPath = null;
        try {
            $photoPath = $photo->store('employee_faces', 'public');
        } catch (\Throwable $e) {
            Log::warning('face_enroll_photo_store_failed', $logContext + ['error' => $e->getMessage()]);
        }

        $faceTable = (new EmployeeFace)->getTable();
        $faceAttrs = [
            'embedding' => $templateEmbedding,
            'model_name' => config('services.face_api.model', 'buffalo_l'),
            'model_version' => $result['model_version'] ?? null,
            'enrollment_quality_score' => $result['enrollment_quality_score'] ?? null,
        ];
        if (Schema::hasColumn($faceTable, 'status')) {
            $faceAttrs['status'] = 'ACTIVE';
        }
        if (Schema::hasColumn($faceTable, 'enrolled_at')) {
            $faceAttrs['enrolled_at'] = now();
        }
        if (Schema::hasColumn($faceTable, 'photo_path')) {
            $faceAttrs['photo_path'] = $photoPath;
        }
        EmployeeFace::updateOrCreate(['employee_id' => $employee->employee_id], $faceAttrs);

        $empTable = $employee->getTable();
        $empUpdate = [];
        if (Schema::hasColumn($empTable, 'face_enrolled')) {
            $empUpdate['face_enrolled'] = true;
        }
        if (Schema::hasColumn($empTable, 'face_enrolled_at')) {
            $empUpdate['face_enrolled_at'] = now();
        }
        if (! empty($empUpdate)) {
            $employee->update($empUpdate);
        }

        EmployeeFaceTemplate::where('employee_id', $employee->employee_id)->update(['is_active' => false]);
        EmployeeFaceTemplate::create([
            'employee_id' => $employee->employee_id,
            'embedding' => $templateEmbedding,
            'image_path' => $photoPath,
            'is_active' => true,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $session->update([
            'status' => 'SUCCESS',
            'frame_count' => 1,
            'liveness_passed' => true,
            'quality_score' => $result['enrollment_quality_score'] ?? null,
            'completed_at' => now(),
            'failure_reason' => null,
        ]);

        FaceAuditLog::log('enrollment_attempt', $employee->employee_id, true, Auth::id(), null, null, $result['enrollment_quality_score'] ?? null, null, [
            'session_id' => $session->session_id,
            'frame_count' => 1,
            'photo_path' => $photoPath,
        ]);
        AuditLogService::log(
            AuditLogService::CATEGORY_FACE,
            'face_enrollment_success',
            AuditLogService::STATUS_SUCCESS,
            'Face enrollment SUCCESS (photo upload)',
            ['session_id' => $session->session_id],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO
        );
        Log::info('face_enroll_success', $logContext + ['session_id' => $session->session_id]);

        return redirect()->route($this->enrollSuccessRedirectRoute())->with('success', 'Photo saved. You can now use face attendance (camera scan) to check in/out.');
    }

    /**
     * Start enrollment session (5-movement flow). Returns session_id, current_step=1, movement_completed=0.
     */
    public function startEnrollSession(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can enroll.');
        abort_if(($employee->employee_status ?? 'inactive') !== 'active', 403, 'Only active employees can enroll.');
        if ($employee->faceData || $employee->faceTemplates()->where('is_active', true)->exists()) {
            return response()->json(['ok' => false, 'error' => 'Face already enrolled. Contact HR to reset.'], 403);
        }

        $deviceId = $request->header('X-Device-Id') ?? $request->input('device_id') ?? 'web';
        $attrs = [
            'session_id' => (string) Str::uuid(),
            'employee_id' => $employee->employee_id,
            'device_id' => $deviceId,
            'status' => 'IN_PROGRESS',
            'current_step' => 1,
            'movement_completed' => 0,
            'frame_count' => 0,
            'started_at' => now(),
        ];
        if (Schema::hasColumn((new FaceEnrollmentSession)->getTable(), 'movement_state')) {
            $attrs['movement_state'] = 'CENTER_REQUIRED';
        }
        try {
            $session = FaceEnrollmentSession::create($attrs);
        } catch (\Throwable $e) {
            Log::error('face_enroll_start_failed', ['employee_id' => $employee->employee_id, 'error' => $e->getMessage()]);
            return response()->json([
                'ok' => false,
                'error' => 'Could not create session. If you just updated the app, run: php artisan migrate',
            ], 500);
        }

        AuditLogService::log(
            AuditLogService::CATEGORY_FACE,
            'face_enrollment_started',
            AuditLogService::STATUS_SUCCESS,
            'Employee started face enrollment',
            ['session_id' => $session->session_id, 'device_id' => $deviceId],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO
        );

        return response()->json([
            'ok' => true,
            'session_id' => $session->session_id,
            'movement_state' => $session->movement_state ?? 'CENTER_REQUIRED',
            'current_step' => 1,
            'movement_completed' => 0,
        ]);
    }

    /**
     * Validate one frame for current movement step. Returns accepted, reason, next_step, embedding.
     */
    public function validateStep(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can enroll.');
        $request->validate([
            'frame' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'current_step' => ['required', 'integer', 'min:1', 'max:5'],
            'session_id' => ['nullable', 'string'],
        ]);

        $result = $this->faceService->validateStep(
            $employee->employee_id,
            $request->file('frame'),
            (int) $request->input('current_step'),
            $request->input('session_id')
        );

        return response()->json($result);
    }

    /**
     * Process one frame (state-machine flow). Proxies to Face API POST /enroll/frame.
     * Returns movement_state, valid_frames, message, session_completed.
     */
    public function processFrame(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can enroll.');
        $request->validate([
            'frame' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'session_id' => ['required', 'string'],
            'guide_x_min' => ['nullable', 'numeric'],
            'guide_y_min' => ['nullable', 'numeric'],
            'guide_x_max' => ['nullable', 'numeric'],
            'guide_y_max' => ['nullable', 'numeric'],
        ]);

        $guideBox = null;
        if ($request->filled(['guide_x_min', 'guide_y_min', 'guide_x_max', 'guide_y_max'])) {
            $guideBox = [
                (float) $request->input('guide_x_min'),
                (float) $request->input('guide_y_min'),
                (float) $request->input('guide_x_max'),
                (float) $request->input('guide_y_max'),
            ];
        }

        $result = $this->faceService->processFrame(
            $employee->employee_id,
            $request->file('frame'),
            $request->input('session_id'),
            $guideBox
        );

        if (! ($result['ok'] ?? true)) {
            return response()->json([
                'ok' => false,
                'reason' => $result['reason'] ?? 'Validation failed',
                'movement_state' => $result['movement_state'] ?? 'CENTER_REQUIRED',
                'valid_frames' => (int) ($result['valid_frames'] ?? 0),
                'message' => $result['message'] ?? 'Please look straight at the camera.',
                'session_completed' => false,
            ], 400);
        }

        $session = FaceEnrollmentSession::where('session_id', $request->input('session_id'))
            ->where('employee_id', $employee->employee_id)
            ->first();
        if ($session) {
            $update = ['frame_count' => (int) ($result['valid_frames'] ?? $session->frame_count)];
            if (Schema::hasColumn($session->getTable(), 'movement_state')) {
                $update['movement_state'] = $result['movement_state'] ?? $session->movement_state;
            }
            $session->update($update);
        }

        return response()->json([
            'ok' => true,
            'accepted' => (bool) ($result['accepted'] ?? false),
            'reason' => $result['reason'] ?? null,
            'failure_code' => $result['failure_code'] ?? null,
            'guidance' => $result['guidance'] ?? null,
            'movement_state' => $result['movement_state'] ?? 'CENTER_REQUIRED',
            'valid_frames' => (int) ($result['valid_frames'] ?? 0),
            'message' => $result['message'] ?? $result['guidance'] ?? 'Please look straight at the camera.',
            'session_completed' => (bool) ($result['session_completed'] ?? false),
            'timeout' => (bool) ($result['timeout'] ?? false),
        ]);
    }

    /**
     * Finalize enrollment (state-machine flow). Proxies to Face API POST /enroll/finalize,
     * then saves template and marks session SUCCESS or FAILED.
     */
    public function finalizeEnroll(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can enroll.');
        abort_if(($employee->employee_status ?? 'inactive') !== 'active', 403, 'Only active employees can enroll.');
        if ($employee->faceData || $employee->faceTemplates()->where('is_active', true)->exists()) {
            return response()->json(['ok' => false, 'error' => 'Face already enrolled.'], 403);
        }

        $request->validate(['session_id' => ['required', 'string']]);
        $sessionId = $request->input('session_id');

        $session = FaceEnrollmentSession::where('session_id', $sessionId)
            ->where('employee_id', $employee->employee_id)
            ->where('status', 'IN_PROGRESS')
            ->first();
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found or already completed.'], 400);
        }

        $result = $this->faceService->finalizeEnrollment($employee->employee_id, $sessionId);

        if (! ($result['ok'] ?? false)) {
            $session->update([
                'status' => 'FAILED',
                'failure_reason' => $result['failure_reason'] ?? $result['message'] ?? 'Finalize failed',
                'completed_at' => now(),
            ]);
            FaceAuditLog::log('enrollment_attempt', $employee->employee_id, false, Auth::id(), null, $result['failure_reason'] ?? null, null, $result['message'] ?? 'Finalize failed', ['session_id' => $sessionId]);
            AuditLogService::log(
                AuditLogService::CATEGORY_FACE,
                'face_enrollment_failed',
                AuditLogService::STATUS_FAILED,
                'Face enrollment FAILED (reason: ' . ($result['failure_reason'] ?? 'unknown') . ')',
                ['session_id' => $sessionId, 'reason' => $result['failure_reason'] ?? null],
                $employee->employee_id,
                AuditLogService::SEVERITY_WARN
            );
            return response()->json([
                'ok' => false,
                'error' => $result['message'] ?? 'Enrollment failed.',
                'failure_reason' => $result['failure_reason'] ?? null,
            ], 400);
        }

        $templateEmbedding = $result['template'] ?? null;
        if (! $templateEmbedding) {
            $session->update(['status' => 'FAILED', 'failure_reason' => 'No template', 'completed_at' => now()]);
            return response()->json(['ok' => false, 'error' => 'Template missing.', 'failure_reason' => 'LOW_QUALITY'], 400);
        }

        $faceTable = (new EmployeeFace)->getTable();
        $faceAttrs = [
            'embedding' => $templateEmbedding,
            'model_name' => config('services.face_api.model', 'buffalo_l'),
            'model_version' => $result['model_version'] ?? null,
            'enrollment_quality_score' => $result['enrollment_quality_score'] ?? null,
        ];
        if (Schema::hasColumn($faceTable, 'status')) {
            $faceAttrs['status'] = 'ACTIVE';
        }
        if (Schema::hasColumn($faceTable, 'enrolled_at')) {
            $faceAttrs['enrolled_at'] = now();
        }
        EmployeeFace::updateOrCreate(['employee_id' => $employee->employee_id], $faceAttrs);
        $empTable = $employee->getTable();
        $empUpdate = [];
        if (Schema::hasColumn($empTable, 'face_enrolled')) {
            $empUpdate['face_enrolled'] = true;
        }
        if (Schema::hasColumn($empTable, 'face_enrolled_at')) {
            $empUpdate['face_enrolled_at'] = now();
        }
        if (! empty($empUpdate)) {
            $employee->update($empUpdate);
        }
        EmployeeFaceTemplate::create([
            'employee_id' => $employee->employee_id,
            'embedding' => $templateEmbedding,
            'image_path' => null,
            'is_active' => true,
            'approved_by' => null,
            'approved_at' => null,
        ]);
        $sessionAttrs = [
            'status' => 'SUCCESS',
            'frame_count' => (int) ($result['valid_frames'] ?? 0),
            'liveness_passed' => true,
            'quality_score' => $result['enrollment_quality_score'] ?? null,
            'completed_at' => now(),
            'failure_reason' => null,
        ];
        if (Schema::hasColumn($session->getTable(), 'movement_state')) {
            $sessionAttrs['movement_state'] = 'COMPLETED';
        }
        $session->update($sessionAttrs);

        FaceAuditLog::log('enrollment_attempt', $employee->employee_id, true, Auth::id(), null, null, $result['enrollment_quality_score'] ?? null, null, [
            'session_id' => $session->session_id,
            'frame_count' => $result['valid_frames'] ?? 0,
            'flow' => 'state_machine',
        ]);
        $qualityScore = $result['enrollment_quality_score'] ?? null;
        AuditLogService::log(
            AuditLogService::CATEGORY_FACE,
            'face_enrollment_success',
            AuditLogService::STATUS_SUCCESS,
            'Face enrollment SUCCESS (quality score: ' . ($qualityScore !== null ? round($qualityScore, 2) : 'N/A') . ')',
            ['session_id' => $session->session_id, 'quality_score' => $qualityScore, 'valid_frames' => $result['valid_frames'] ?? 0],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO
        );

        return response()->json(['ok' => true, 'message' => 'Enrollment successful.']);
    }

    /**
     * Complete enrollment with 5 embeddings (from 5 movement steps). Saves template and marks session SUCCESS.
     */
    public function completeEnroll(Request $request)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can enroll.');
        abort_if(($employee->employee_status ?? 'inactive') !== 'active', 403, 'Only active employees can enroll.');
        if ($employee->faceData || $employee->faceTemplates()->where('is_active', true)->exists()) {
            return response()->json(['ok' => false, 'error' => 'Face already enrolled.'], 403);
        }

        $validated = $request->validate([
            'session_id' => ['required', 'string'],
            'embeddings' => ['required', 'array', 'size:5'],
            'embeddings.*' => ['array'],
        ]);

        $session = FaceEnrollmentSession::where('session_id', $request->input('session_id'))
            ->where('employee_id', $employee->employee_id)
            ->where('status', 'IN_PROGRESS')
            ->first();
        if (! $session) {
            return response()->json(['ok' => false, 'error' => 'Session not found or already completed.'], 400);
        }

        $embeddings = $request->input('embeddings');
        $result = $this->faceService->enrollComplete($employee->employee_id, $session->session_id, $embeddings);

        if (! $result['ok']) {
            $session->update([
                'status' => 'FAILED',
                'failure_reason' => $result['message'] ?? 'Complete failed',
                'completed_at' => now(),
            ]);
            AuditLogService::log(
                AuditLogService::CATEGORY_FACE,
                'face_enrollment_failed',
                AuditLogService::STATUS_FAILED,
                'Face enrollment FAILED (reason: ' . ($result['message'] ?? 'unknown') . ')',
                ['session_id' => $session->session_id],
                $employee->employee_id,
                AuditLogService::SEVERITY_WARN
            );
            return response()->json(['ok' => false, 'error' => $result['message'] ?? 'Enrollment failed.'], 400);
        }

        $templateEmbedding = $result['template'] ?? null;
        if (! $templateEmbedding) {
            $session->update(['status' => 'FAILED', 'failure_reason' => 'No template', 'completed_at' => now()]);
            return response()->json(['ok' => false, 'error' => 'Template missing.'], 400);
        }

        $faceTable = (new EmployeeFace)->getTable();
        $faceAttrs = [
            'embedding' => $templateEmbedding,
            'model_name' => config('services.face_api.model', 'buffalo_l'),
            'model_version' => $result['model_version'] ?? null,
            'enrollment_quality_score' => $result['enrollment_quality_score'] ?? null,
        ];
        if (Schema::hasColumn($faceTable, 'status')) {
            $faceAttrs['status'] = 'ACTIVE';
        }
        if (Schema::hasColumn($faceTable, 'enrolled_at')) {
            $faceAttrs['enrolled_at'] = now();
        }
        EmployeeFace::updateOrCreate(['employee_id' => $employee->employee_id], $faceAttrs);
        $empTable = $employee->getTable();
        $empUpdate = [];
        if (Schema::hasColumn($empTable, 'face_enrolled')) {
            $empUpdate['face_enrolled'] = true;
        }
        if (Schema::hasColumn($empTable, 'face_enrolled_at')) {
            $empUpdate['face_enrolled_at'] = now();
        }
        if (! empty($empUpdate)) {
            $employee->update($empUpdate);
        }
        foreach ($embeddings as $emb) {
            EmployeeFaceTemplate::create([
                'employee_id' => $employee->employee_id,
                'embedding' => $emb,
                'image_path' => null,
                'is_active' => true,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        }
        $session->update([
            'status' => 'SUCCESS',
            'current_step' => 6,
            'movement_completed' => 5,
            'frame_count' => 5,
            'liveness_passed' => true,
            'quality_score' => $result['enrollment_quality_score'] ?? null,
            'completed_at' => now(),
            'failure_reason' => null,
        ]);

        FaceAuditLog::log('enrollment_attempt', $employee->employee_id, true, Auth::id(), null, null, $result['enrollment_quality_score'] ?? null, null, [
            'session_id' => $session->session_id,
            'frame_count' => 5,
            'flow' => 'movement_steps',
        ]);
        AuditLogService::log(
            AuditLogService::CATEGORY_FACE,
            'face_enrollment_success',
            AuditLogService::STATUS_SUCCESS,
            'Face enrollment SUCCESS (quality score: ' . (isset($result['enrollment_quality_score']) ? round($result['enrollment_quality_score'], 2) : 'N/A') . ')',
            ['session_id' => $session->session_id, 'frame_count' => 5],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO
        );

        return response()->json(['ok' => true, 'message' => 'Enrollment successful.']);
    }

    public function destroy(EmployeeFaceTemplate $template)
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403, 'Only employees can manage their face data.');
        abort_if($template->employee_id !== $employee->employee_id, 403, 'Not your template.');

        if ($template->image_path && Storage::disk('public')->exists($template->image_path)) {
            Storage::disk('public')->delete($template->image_path);
        }
        if ($template->image_path && Storage::disk('local')->exists($template->image_path)) {
            Storage::disk('local')->delete($template->image_path);
        }

        $template->delete();

        return redirect()->route($this->enrollSuccessRedirectRoute())->with('success', 'Template removed.');
    }

    /**
     * Redirect target after enroll/template-destroy: admin users go to admin page to avoid 403.
     */
    private function enrollSuccessRedirectRoute(): string
    {
        if ($this->isAdminRole(Auth::user())) {
            return 'admin.face.enroll.self';
        }
        return 'employee.face.enroll';
    }

    private function isAdminRole(?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }
        $role = strtolower(trim((string) ($user->role ?? '')));
        return in_array($role, ['admin', 'administrator', 'hr', 'manager'], true);
    }
}
