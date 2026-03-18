<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\PasswordResetController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;

use App\Http\Controllers\JobPostController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicantJobController;

use App\Http\Controllers\TrainingController;
use App\Http\Controllers\FaceRecognitionController;

// main (modules)
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\EmployeeTrainingController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\EmployeeOnboardingController;
use App\Http\Controllers\EmployeeLeaveController;
use App\Http\Controllers\EmployeeAssistantController;

// helvin (modules)
use App\Http\Controllers\AdminEmployeeController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminPenaltyController;
use App\Http\Controllers\AdminPenaltyRemovalController;
use App\Http\Controllers\AdminOvertimeController;
use App\Http\Controllers\AdminSalaryController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\AdminLeaveController;
use App\Http\Controllers\AdminLeaveBalanceController;
use App\Http\Controllers\AdminLeaveTypeController;
use App\Http\Controllers\EmployeeFaceAttendanceController;
use App\Http\Controllers\EmployeePayrollController;
use App\Http\Controllers\EmployeeOvertimeClaimController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\SupervisorOvertimeController;
use App\Http\Controllers\SupervisorOvertimeRecordController;
use App\Http\Controllers\EmployeePenaltyController;
use App\Http\Controllers\AdminOvertimeClaimController;
use App\Http\Controllers\AdminAreaController;
use App\Http\Controllers\AdminDepartmentController;
use App\Http\Controllers\EmployeeProfileController;
use App\Http\Controllers\SupervisorProfileController;
use App\Http\Controllers\SupervisorLeaveController;
use App\Http\Controllers\SupervisorPenaltyController;

use App\Http\Controllers\AssistantController;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Email Verification
Route::get('/email/verify', function () {
    return view('auth.verify-email'); 
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('applicant.jobs'); 
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Forgot Password
Route::get('/forgot-password', function () { 
    return view('auth.forgot'); 
})->middleware('guest')->name('password.request');

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware('guest')
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->middleware('guest')
    ->name('password.update');

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'role:admin,administrator,hr,manager'])->group(function () {

    // Dashboard & Assistant
    Route::post('/assistant/chat', [AssistantController::class, 'chat'])->name('admin.assistant.chat');
    Route::get('/assistant', fn() => view('admin.assistant'))->name('admin.assistant');
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    // Announcements
    Route::get('/dashboard/announcement/view', [AnnouncementController::class, 'index'])->name('admin.announcements.index');
    Route::post('/dashboard/announcement/store', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::put('/dashboard/announcement/update/{id}', [AnnouncementController::class, 'update'])->name('admin.announcements.update');
    Route::delete('/dashboard/announcement/delete/{id}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');

    // Recruitment
    Route::get('/recruitment', [JobPostController::class, 'index'])->name('admin.recruitment.index');
    Route::post('/recruitment/store', [JobPostController::class, 'store'])->name('admin.recruitment.store');
    Route::post('/recruitment/update/{id}', [JobPostController::class, 'update'])->name('admin.recruitment.update');
    Route::delete('/recruitment/delete/{id}', [JobPostController::class, 'destroy'])->name('admin.recruitment.destroy');
    Route::post('/admin/recruitment/duplicate/{id}', [JobPostController::class, 'duplicate'])->name('admin.recruitment.duplicate');
    Route::post('/recruitment/requisition/{id}/approve', [JobPostController::class, 'approveRequisition'])->name('admin.recruitment.approveRequisition');
    Route::post('/recruitment/requisition/{id}/reject', [JobPostController::class, 'rejectRequisition'])->name('admin.recruitment.rejectRequisition');

    Route::get('/recruitment/applicants', [ApplicationController::class, 'index'])->name('admin.applicants.index');
    Route::get('/recruitment/applicants/{id}', [ApplicationController::class, 'show'])->name('admin.applicants.show');
    Route::get('/recruitment/applicants/profile/{applicant}', [ApplicationController::class, 'profile'])->name('admin.applicants.profile');
    Route::post('/recruitment/applicants/{id}/evaluate', [ApplicationController::class, 'saveEvaluation'])->name('admin.applicants.evaluate');
    Route::post('/recruitment/applicants/{id}/status', [ApplicationController::class, 'updateStatus'])->name('admin.applicants.updateStatus');
    Route::post('/recruitment/applicants/{id}/onboard', [ApplicationController::class, 'onboard'])->name('admin.applicants.onboard');
    Route::post('/admin/applicants/{id}/schedule', [ApplicationController::class, 'scheduleInterview'])->name('admin.applicants.schedule');

    // Appraisal (Performance Reviews)
    Route::get('/appraisal', [KpiController::class, 'index'])->name('admin.appraisal');
    Route::get('/appraisal/initiate', [KpiController::class, 'create'])->name('admin.appraisal.add-kpi');
    Route::post('/appraisal/store', [KpiController::class, 'store'])->name('admin.appraisal.store');

    // Training
    Route::get('/training', [TrainingController::class, 'index'])->name('admin.training');
    Route::post('/training/store', [TrainingController::class, 'store'])->name('admin.training.store');
    Route::post('/training/update/{id}', [TrainingController::class, 'update'])->name('admin.training.update'); 
    Route::delete('/training/delete/{id}', [TrainingController::class, 'destroy'])->name('admin.training.delete');
    Route::get('/training/show/{id}', [TrainingController::class, 'show'])->name('admin.training.show');
    Route::post('/training/{id}/enroll', [TrainingController::class, 'storeEnrollment'])->name('admin.training.enroll');
    Route::post('/training/enrollment/{id}/update', [TrainingController::class, 'updateEnrollmentStatus'])->name('admin.training.updateStatus');
    Route::get('/training/events', [TrainingController::class, 'getEvents'])->name('admin.training.events');
    
    // Onboarding
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('admin.onboarding');
    Route::get('/onboarding/checklist/{id}', [OnboardingController::class, 'showChecklist'])->name('admin.onboarding.checklist.show');
    Route::get('/onboarding/add', [OnboardingController::class, 'create'])->name('admin.onboarding.add');
    Route::post('/onboarding/store', [OnboardingController::class, 'store'])->name('admin.onboarding.store');

    // Reports & Profile
    Route::get('/reports', fn() => view('admin.reports'))->name('admin.reports.dashboard');
    Route::get('/profile', [AdminController::class, 'profile'])->name('admin.profile');
    Route::post('/profile/update', [AdminController::class, 'updateProfile'])->name('admin.profile.update');
    
    // Area Management
    Route::get('/areas', [AdminAreaController::class, 'index'])->name('admin.areas.index');
    Route::get('/areas/create', [AdminAreaController::class, 'create'])->name('admin.areas.create');
    Route::post('/areas', [AdminAreaController::class, 'store'])->name('admin.areas.store');
    Route::get('/areas/{area}/edit', [AdminAreaController::class, 'edit'])->name('admin.areas.edit');
    Route::put('/areas/{area}', [AdminAreaController::class, 'update'])->name('admin.areas.update');
    Route::post('/areas/move-employee', [AdminAreaController::class, 'moveEmployee'])->name('admin.areas.move_employee');

    // Department Management
    Route::get('/departments', [AdminDepartmentController::class, 'index'])->name('admin.departments.index');
    Route::get('/departments/create', [AdminDepartmentController::class, 'create'])->name('admin.departments.create');
    Route::get('/departments/{department}/duplicate', [AdminDepartmentController::class, 'duplicate'])->name('admin.departments.duplicate');
    Route::post('/departments', [AdminDepartmentController::class, 'store'])->name('admin.departments.store');
    Route::get('/departments/{department}/edit', [AdminDepartmentController::class, 'edit'])->name('admin.departments.edit');
    Route::put('/departments/{department}', [AdminDepartmentController::class, 'update'])->name('admin.departments.update');
    Route::delete('/departments/{department}', [AdminDepartmentController::class, 'destroy'])->name('admin.departments.destroy');
    Route::post('/departments/{department}/assign-employees', [AdminDepartmentController::class, 'assignEmployees'])->name('admin.departments.assign_employees');

    // Employee Management
    Route::get('/employee/list', [AdminEmployeeController::class, 'index'])->name('admin.employee.list');
    Route::get('/employee/profile/{employee}', [AdminEmployeeController::class, 'show'])->name('admin.employee.profile');
    Route::get('/employee/add', [AdminEmployeeController::class, 'create'])->name('admin.employee.add');
    Route::post('/employee/add', [AdminEmployeeController::class, 'store'])->name('admin.employee.store');

    // Attendance
    Route::get('/attendance/tracking', [AdminAttendanceController::class, 'tracking'])->name('admin.attendance.tracking');
    Route::get('/attendance/tracking/data', [AdminAttendanceController::class, 'data'])->name('admin.attendance.data');
    Route::get('/attendance/penalty', [AdminPenaltyController::class, 'index'])->name('admin.attendance.penalty');
    Route::get('/attendance/penalty/data', [AdminPenaltyController::class, 'data'])->name('admin.attendance.penalty.data');
    Route::post('/attendance/penalty/{penalty}/status', [AdminPenaltyController::class, 'updateStatus'])
        ->middleware('payroll.unlocked')
        ->name('admin.attendance.penalty.status');

    // Penalty Removal Requests
    Route::get('/attendance/penalty-removal-requests', [AdminPenaltyRemovalController::class, 'index'])->name('admin.attendance.penalty_removal_requests.index');
    Route::post('/attendance/penalty-removal-requests/{removal}/approve', [AdminPenaltyRemovalController::class, 'approve'])->name('admin.attendance.penalty_removal_requests.approve');
    Route::post('/attendance/penalty-removal-requests/{removal}/reject', [AdminPenaltyRemovalController::class, 'reject'])->name('admin.attendance.penalty_removal_requests.reject');
    
    // Payroll
    Route::prefix('/payroll')->group(function () {
        Route::get('/overtime', [AdminOvertimeController::class, 'index'])->name('admin.payroll.overtime');
        Route::get('/overtime/data', [AdminOvertimeController::class, 'data'])->name('admin.payroll.overtime.data');
        Route::post('/overtime/{overtime}/status', [AdminOvertimeController::class, 'updateStatus'])
            ->middleware('payroll.unlocked')
            ->name('admin.payroll.overtime.status');

       // OT Claims
        Route::get('/overtime-claims', [AdminOvertimeClaimController::class, 'index'])->name('admin.payroll.overtime_claims');
        Route::get('/overtime-claims/{claim}', [AdminOvertimeClaimController::class, 'show'])->name('admin.payroll.overtime_claims.show');
        Route::post('/overtime-claims/bulk-approve', [AdminOvertimeClaimController::class, 'bulkApprove'])->name('admin.payroll.overtime_claims.bulk_approve');
        Route::post('/overtime-claims/bulk-reject', [AdminOvertimeClaimController::class, 'bulkReject'])->name('admin.payroll.overtime_claims.bulk_reject');
        Route::post('/overtime-claims/{claim}/approve', [AdminOvertimeClaimController::class, 'approve'])->name('admin.payroll.overtime_claims.approve');
        Route::post('/overtime-claims/{claim}/reject', [AdminOvertimeClaimController::class, 'reject'])->name('admin.payroll.overtime_claims.reject');
        Route::post('/overtime-claims/{claim}/hold', [AdminOvertimeClaimController::class, 'onHold'])->name('admin.payroll.overtime_claims.hold');

        Route::get('/salary', [AdminSalaryController::class, 'index'])->name('admin.payroll.salary');
        Route::get('/salary/data', [AdminSalaryController::class, 'data'])->name('admin.payroll.salary.data');
        Route::get('/salary/checklist', [AdminSalaryController::class, 'checklist'])->name('admin.payroll.salary.checklist');
        Route::get('/salary/detail', [AdminSalaryController::class, 'detail'])->name('admin.payroll.salary.detail');
        Route::get('/salary/adjustment-summary', [AdminSalaryController::class, 'adjustmentSummary'])->name('admin.payroll.salary.adjustment_summary');
        Route::post('/salary/generate', [AdminSalaryController::class, 'generate'])->name('admin.payroll.salary.generate');
        Route::post('/salary/adjustment', [AdminSalaryController::class, 'applyAdjustment'])->name('admin.payroll.salary.adjustment');
        Route::post('/salary/update-basic-salary', [AdminSalaryController::class, 'updateBasicSalary'])->name('admin.payroll.salary.update_basic_salary');
        Route::post('/salary/lock', [AdminSalaryController::class, 'lock'])->name('admin.payroll.salary.lock');
        Route::post('/salary/pay', [AdminSalaryController::class, 'pay'])->name('admin.payroll.salary.pay');
        Route::post('/salary/publish', [AdminSalaryController::class, 'publish'])->name('admin.payroll.salary.publish');

        Route::get('/attendance', fn() => view('admin.payroll_attendance'))->name('admin.payroll.attendance');
        Route::get('/payslip', fn() => view('admin.payroll_payslip'))->name('admin.payroll.payslip');
    });

    // Leave
    Route::prefix('/leave')->group(function () {
        Route::get('/request', [AdminLeaveController::class, 'index'])->name('admin.leave.request');
        Route::get('/request/data', [AdminLeaveController::class, 'data'])->name('admin.leave.request.data');
        Route::post('/request/{leave}/status', [AdminLeaveController::class, 'updateStatus'])
            ->middleware('payroll.unlocked')
            ->name('admin.leave.request.status');

        Route::get('/balance', [AdminLeaveBalanceController::class, 'index'])->name('admin.leave.balance');
        Route::get('/balance/data', [AdminLeaveBalanceController::class, 'data'])->name('admin.leave.balance.data');
        Route::get('/employee/{employee}/balance', [AdminLeaveBalanceController::class, 'employeeBalance'])->name('admin.leave.employee.balance');

        Route::get('/types', [AdminLeaveTypeController::class, 'index'])->name('admin.leave.types');
        Route::post('/types/{leaveType}', [AdminLeaveTypeController::class, 'update'])->name('admin.leave.types.update');
    });

    // Audit Log
    Route::get('/audit-log', [AdminAuditLogController::class, 'index'])->name('admin.audit.log');
    Route::get('/audit-log/data', [AdminAuditLogController::class, 'data'])->name('admin.audit.log.data');
    Route::get('/audit-log/{id}', [AdminAuditLogController::class, 'show'])->name('admin.audit.log.show');

    // Face Enrollment / Verification (Admin)
    Route::get('/face/enroll', [FaceRecognitionController::class, 'showEnrollForm'])->name('admin.face.enroll');
    Route::post('/face/reset/{employee}', [FaceRecognitionController::class, 'resetFace'])->name('admin.face.reset');
    Route::get('/face/verify', [FaceRecognitionController::class, 'showAdminVerifyForm'])->name('admin.face.verify');
    Route::post('/face/verify/{employee}', [FaceRecognitionController::class, 'verify'])->middleware('payroll.unlocked')->name('admin.face.verify.post');
    Route::get('/face/enroll-self', [FaceRecognitionController::class, 'showAdminSelfEnrollForm'])->name('admin.face.enroll.self');
    Route::post('/face/enroll-self', [\App\Http\Controllers\EmployeeFaceController::class, 'enroll'])->name('admin.face.enroll.self.post');
});

/*
|--------------------------------------------------------------------------
| APPLICANT ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('applicant')->middleware(['auth', 'role:applicant', 'verified'])->group(function () {
    Route::get('/jobs', [ApplicantJobController::class, 'index'])->name('applicant.jobs');
    Route::get('/jobs/{id}', [ApplicantJobController::class, 'show'])->name('applicant.jobs.show');
    Route::get('/jobs/{id}/apply', [ApplicantJobController::class, 'applyForm'])->name('applicant.jobs.apply');
    Route::post('/jobs/{id}/apply', [ApplicantJobController::class, 'submitApplication'])->name('applicant.jobs.submit');
    Route::get('/applications', [ApplicantJobController::class, 'myApplications'])->name('applicant.applications');
    
    // Core Profile
    Route::get('/profile', [ApplicantJobController::class, 'profile'])->name('applicant.profile');
    Route::post('/profile/update', [ApplicantJobController::class, 'updateProfile'])->name('applicant.profile.update');
    Route::get('/profile/resume/delete', [ApplicantJobController::class, 'deleteResume'])->name('applicant.resume.delete');
    
    Route::post('/profile/education', [ApplicantJobController::class, 'storeEducation'])->name('applicant.education.store');
    Route::delete('/profile/education/{id}', [ApplicantJobController::class, 'deleteEducation'])->name('applicant.education.destroy');
    Route::post('/profile/language', [ApplicantJobController::class, 'storeLanguage'])->name('applicant.language.store');
    Route::delete('/profile/language/{id}', [ApplicantJobController::class, 'deleteLanguage'])->name('applicant.language.destroy');
    Route::post('/profile/skill', [ApplicantJobController::class, 'storeSkill'])->name('applicant.skill.store');
    Route::delete('/profile/skill/{id}', [ApplicantJobController::class, 'deleteSkill'])->name('applicant.skill.destroy');
    Route::post('/profile/experience', [ApplicantJobController::class, 'storeExperience'])->name('applicant.experience.store');
    Route::delete('/profile/experience/{id}', [ApplicantJobController::class, 'deleteExperience'])->name('applicant.experience.destroy');
});

/*
|--------------------------------------------------------------------------
| EMPLOYEE ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:employee,supervisor'])->group(function () {

    Route::get('/employee/dashboard', [EmployeeController::class, 'index'])->name('employee.dashboard');
    
    // Employee Profile & Requisitions
    Route::get('/employee/profile', [EmployeeProfileController::class, 'show'])->name('employee.profile');
    Route::post('/employee/profile/update', [EmployeeProfileController::class, 'updateProfile'])->name('employee.profile.update');
    
    // === BUG FIX: WIRED REQUISITION STORE CORRECTLY ===
    Route::post('/employee/requisition/store', [JobPostController::class, 'storeRequisition'])->name('employee.requisition.store');

    // Employee Performance Appraisals (Self-Evaluations)
    Route::get('/employee/appraisal/reviews', [KpiController::class, 'selfEvaluationList'])->name('employee.kpis.self-eval');
    Route::post('/employee/appraisal/reviews/submit/{id}', [KpiController::class, 'submitSelfEval'])->name('employee.kpis.store-eval');
    
    Route::get('/employee/training/my-plans', [EmployeeTrainingController::class, 'index'])->name('employee.training.index');
    Route::get('/employee/training/scan/{token}', [EmployeeTrainingController::class, 'scanQr'])->name('employee.training.scan');
    Route::get('/employee/training/{id}', [EmployeeTrainingController::class, 'show'])->name('employee.training.show');

    Route::get('/employee/onboarding', [EmployeeOnboardingController::class, 'index'])->name('employee.onboarding.index');
    Route::post('/employee/onboarding/task/{id}/complete', [EmployeeOnboardingController::class, 'completeTask'])->name('employee.onboarding.complete');
    
    Route::get('/employee/assistant', fn() => view('employee.assistant'))->name('employee.assistant');
    Route::post('/employee/assistant/chat', [EmployeeAssistantController::class, 'chat'])->name('employee.assistant.chat');

    // Face enrollment (employee self)
    Route::get('/employee/face/enroll', [\App\Http\Controllers\EmployeeFaceController::class, 'enrollForm'])->name('employee.face.enroll');
    Route::post('/employee/face/enroll', [\App\Http\Controllers\EmployeeFaceController::class, 'enroll'])->name('employee.face.enroll.post');
    Route::post('/employee/face/enroll/start', [\App\Http\Controllers\EmployeeFaceController::class, 'startEnrollSession'])->name('employee.face.enroll.start');
    Route::post('/employee/face/enroll/validate-step', [\App\Http\Controllers\EmployeeFaceController::class, 'validateStep'])->name('employee.face.enroll.validate-step');
    Route::post('/employee/face/enroll/frame', [\App\Http\Controllers\EmployeeFaceController::class, 'processFrame'])->name('employee.face.enroll.frame');
    Route::post('/employee/face/enroll/finalize', [\App\Http\Controllers\EmployeeFaceController::class, 'finalizeEnroll'])->name('employee.face.enroll.finalize');
    Route::post('/employee/face/enroll/complete', [\App\Http\Controllers\EmployeeFaceController::class, 'completeEnroll'])->name('employee.face.enroll.complete');
    Route::delete('/employee/face/templates/{template}', [\App\Http\Controllers\EmployeeFaceController::class, 'destroy'])->name('employee.face.templates.destroy');
    Route::get('/employee/attendance/face', [EmployeeFaceAttendanceController::class, 'show'])->name('employee.attendance.face');
    Route::post('/employee/attendance/face', [EmployeeFaceAttendanceController::class, 'check'])->name('employee.attendance.face.post');

    // Employee self-service pages
    Route::get('/employee/attendance/log', [EmployeeAttendanceController::class, 'index'])->name('employee.attendance.log');
    Route::get('/employee/penalties', [EmployeePenaltyController::class, 'index'])->name('employee.penalties.index');
    Route::post('/employee/penalties/{penalty}/removal-request', [EmployeePenaltyController::class, 'submitRemovalRequest'])->name('employee.penalties.removal_request.submit');
    Route::post('/employee/penalties/removal-request/{removal}/cancel', [EmployeePenaltyController::class, 'cancelRemovalRequest'])->name('employee.penalties.removal_request.cancel');
    Route::get('/employee/attendance/overtime', [\App\Http\Controllers\EmployeeOvertimeController::class, 'index'])->name('employee.attendance.overtime');
    Route::post('/employee/attendance/overtime', [\App\Http\Controllers\EmployeeOvertimeController::class, 'store'])->middleware('payroll.unlocked')->name('employee.attendance.overtime.store');
    Route::delete('/employee/attendance/overtime/{overtime}', [\App\Http\Controllers\EmployeeOvertimeController::class, 'destroy'])->middleware('payroll.unlocked')->name('employee.attendance.overtime.destroy');

    // OT Claim workflow
    Route::get('/employee/ot-claims', [EmployeeOvertimeClaimController::class, 'index'])->name('employee.ot_claims.index');
    Route::get('/employee/ot-claims/create', [EmployeeOvertimeClaimController::class, 'create'])->name('employee.ot_claims.create');
    Route::get('/employee/ot-claims/check-duplicate', [EmployeeOvertimeClaimController::class, 'checkDuplicate'])->name('employee.ot_claims.check_duplicate');
    Route::get('/employee/ot-claims/day-info', [EmployeeOvertimeClaimController::class, 'dayInfo'])->name('employee.ot_claims.day_info');
    Route::post('/employee/ot-claims', [EmployeeOvertimeClaimController::class, 'store'])->name('employee.ot_claims.store');
    Route::get('/employee/ot-claims/{claim}/edit', [EmployeeOvertimeClaimController::class, 'edit'])->name('employee.ot_claims.edit');
    Route::put('/employee/ot-claims/{claim}', [EmployeeOvertimeClaimController::class, 'update'])->name('employee.ot_claims.update');
    Route::post('/employee/ot-claims/{claim}/cancel', [EmployeeOvertimeClaimController::class, 'cancel'])->name('employee.ot_claims.cancel');

    // OT Requests & OT Claims Inbox
    Route::get('/employee/overtime-requests', [SupervisorOvertimeRecordController::class, 'index'])->name('employee.overtime_requests.index');
    Route::post('/employee/overtime-requests/{overtime}/approve', [SupervisorOvertimeRecordController::class, 'approve'])->name('employee.overtime_requests.approve');
    Route::post('/employee/overtime-requests/{overtime}/reject', [SupervisorOvertimeRecordController::class, 'reject'])->name('employee.overtime_requests.reject');
    Route::post('/employee/overtime-requests/{overtime}/mark-issue', [SupervisorOvertimeRecordController::class, 'markIssue'])->name('employee.overtime_requests.mark_issue');
    Route::get('/employee/overtime-requests/approval-summary', [SupervisorOvertimeRecordController::class, 'approvalSummary'])->name('employee.overtime_requests.approval_summary');
    Route::post('/employee/overtime-requests/send-summary', [SupervisorOvertimeRecordController::class, 'sendSummary'])->name('employee.overtime_requests.send_summary');
    Route::get('/employee/ot-claims-inbox', [SupervisorOvertimeController::class, 'index'])->name('employee.overtime_inbox.index');
    Route::get('/employee/ot-claims-inbox/claim/{claim}', [SupervisorOvertimeController::class, 'show'])->name('employee.overtime_inbox.show');
    Route::post('/employee/ot-claims-inbox/claim/{claim}/approve', [SupervisorOvertimeController::class, 'approve'])->name('employee.overtime_inbox.approve');
    Route::post('/employee/ot-claims-inbox/claim/{claim}/approve-with-adjustment', [SupervisorOvertimeController::class, 'approveWithAdjustment'])->name('employee.overtime_inbox.approve_with_adjustment');
    Route::post('/employee/ot-claims-inbox/claim/{claim}/return', [SupervisorOvertimeController::class, 'returnForChanges'])->name('employee.overtime_inbox.return');
    Route::post('/employee/ot-claims-inbox/claim/{claim}/reject', [SupervisorOvertimeController::class, 'reject'])->name('employee.overtime_inbox.reject');
    Route::post('/employee/ot-claims-inbox/claim/{claim}/escalate', [SupervisorOvertimeController::class, 'escalateToAdmin'])->name('employee.overtime_inbox.escalate');
    Route::post('/employee/ot-claims-inbox/bulk', [SupervisorOvertimeController::class, 'bulkAction'])->name('employee.overtime_inbox.bulk');

    Route::get('/employee/leave/apply', [EmployeeLeaveController::class, 'index'])->name('employee.leave.apply');
    Route::post('/employee/leave/apply', [EmployeeLeaveController::class, 'store'])->name('employee.leave.store');
    Route::get('/employee/leave/balance', [EmployeeLeaveController::class, 'index'])->name('employee.leave.balance');
    Route::get('/employee/leave/history', [EmployeeLeaveController::class, 'index'])->name('employee.leave.history');
    Route::post('/employee/leave/{leave}/cancel', [EmployeeLeaveController::class, 'cancel'])->name('employee.leave.cancel');
    
    Route::get('/employee/payroll/payslips', [EmployeePayrollController::class, 'index'])->name('employee.payroll.payslips');
    Route::get('/employee/payroll/detail', [EmployeePayrollController::class, 'detail'])->name('employee.payroll.detail');
    Route::get('/employee/payroll/tax', [EmployeePayrollController::class, 'index'])->name('employee.payroll.tax');
    Route::get('/employee/payroll/payslip/{payslip}', [EmployeePayrollController::class, 'downloadPayslip'])->name('employee.payroll.download');
    Route::get('/employee/payroll/tax/{year}', [EmployeePayrollController::class, 'downloadTax'])->name('employee.payroll.tax.download');

    Route::get('/employee/face/verify', [FaceRecognitionController::class, 'showVerifyForm'])->name('employee.face.verify.form');
    Route::post('/employees/{id}/face/enroll', [FaceRecognitionController::class, 'enroll'])->name('face.enroll');
    Route::post('/employees/{id}/face/verify', [FaceRecognitionController::class, 'verify'])->middleware('payroll.unlocked')->name('face.verify');
});

/*
|--------------------------------------------------------------------------
| SUPERVISOR ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('supervisor')->middleware(['auth', 'role:supervisor'])->group(function () {
    Route::get('/profile', [SupervisorProfileController::class, 'show'])->name('supervisor.profile');
    Route::post('/profile/update', [SupervisorProfileController::class, 'updateProfile'])->name('supervisor.profile.update');
    
    // Leave
    Route::get('/leave/inbox', [SupervisorLeaveController::class, 'index'])->name('supervisor.leave.inbox');
    Route::get('/leave/employee/{employee}/balance', [AdminLeaveBalanceController::class, 'employeeBalance'])->name('supervisor.leave.employee.balance');
    Route::post('/leave/{leave}/approve', [SupervisorLeaveController::class, 'approve'])->name('supervisor.leave.approve');
    Route::post('/leave/{leave}/reject', [SupervisorLeaveController::class, 'reject'])->name('supervisor.leave.reject');
    Route::post('/leave/{leave}/upload-admin', [SupervisorLeaveController::class, 'uploadToAdmin'])->name('supervisor.leave.upload_admin');

    // Penalty removal requests
    Route::get('/penalty-removal', [SupervisorPenaltyController::class, 'index'])->name('supervisor.penalty_removal.index');
    Route::post('/penalty-removal/{removal}/approve', [SupervisorPenaltyController::class, 'approve'])->name('supervisor.penalty_removal.approve');
    Route::post('/penalty-removal/{removal}/reject', [SupervisorPenaltyController::class, 'reject'])->name('supervisor.penalty_removal.reject');
    
    // === BUG FIX: WIRED MANAGER TEAM ONBOARDING CORRECTLY ===
    Route::get('/team-onboarding', [OnboardingController::class, 'teamOnboardingIndex'])->name('manager.onboarding.index');
    Route::get('/team-onboarding/team', [OnboardingController::class, 'teamOnboardingIndex'])->name('manager.onboarding.team');
    Route::get('/team-onboarding/{id}/show', [OnboardingController::class, 'showTeamOnboarding'])->name('manager.onboarding.show');

    // Supervisor Performance Appraisals
    Route::get('/appraisals/inbox', [KpiController::class, 'supervisorInbox'])->name('supervisor.appraisal.inbox');
    Route::post('/appraisals/{id}/score', [KpiController::class, 'supervisorScore'])->name('supervisor.appraisal.score');
});