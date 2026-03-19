<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class AssistantController extends Controller
{
    public function chat(Request $request)
    {
        set_time_limit(300);

        $userInput = trim((string) $request->input('message', ''));
        $history = $request->input('history', []);
        $today = date('Y-m-d'); 

        if ($userInput === '') {
            return response()->json(['reply' => 'Please type a question.'], 400);
        }

        $user = auth()->user();
        $role = $user->role ?? null;
        if ($role !== 'admin') {
            return response()->json(['reply' => 'Forbidden: admin only.'], 403);
        }

        $usersPk = Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id';

        $system = <<<SYS
You are an HRMS Assistant for ADMIN users. Today's Date is: {$today}.

If you need info from the database, respond ONLY with RAW valid JSON (no markdown, no conversational text before or after), exactly like:

1) FAQ / policy:
{"action":"tool","tool":"search_faqs","args":{"query":"..."}}

2) Attendance summary:
{"action":"tool","tool":"attendance_summary","args":{"from":"YYYY-MM-DD","to":"YYYY-MM-DD"}}

3) Recruitment - Search job posts:
{"action":"tool","tool":"search_job_posts","args":{"keyword":"optional text", "status":"Open|Closed|Draft"}}

4) Recruitment - Job post details:
{"action":"tool","tool":"job_post_details","args":{"job_title":"optional title", "job_type":"optional type", "department":"optional department"}}

5) Appraisal - Get employee KPI scores:
{"action":"tool","tool":"employee_kpi_summary","args":{"employee_name":"name of employee"}}

6) Appraisal - Get department KPI scores:
{"action":"tool","tool":"department_kpi_summary","args":{"department_name":"IT"}}

7) Training - Search training programs:
{"action":"tool","tool":"search_training_programs","args":{"status":"active|planned|completed (optional)", "keyword":"optional text"}}

8) Training - Get specific training details and enrollments:
{"action":"tool","tool":"training_details","args":{"training_name":"name of training"}}

9) Onboarding - General status/list:
{"action":"tool","tool":"onboarding_status_summary","args":{"status":"pending|in_progress|completed (optional)"}}

10) Onboarding - Specific employee tasks/details:
{"action":"tool","tool":"employee_onboarding_details","args":{"employee_name":"name of employee"}}

11) Recruitment - Search applicants:
{"action":"tool","tool":"search_applicants","args":{"job_title":"optional job title", "app_stage":"optional stage", "keyword":"optional name"}}

12) Recruitment - Applicant details:
{"action":"tool","tool":"applicant_details","args":{"applicant_name":"name of applicant"}}

13) Leave - Check leave requests:
{"action":"tool","tool":"leave_requests_summary","args":{"status":"pending|approved|rejected (optional)", "date":"YYYY-MM-DD (optional)"}}

14) Leave - Check employee leave balance:
{"action":"tool","tool":"employee_leave_balance","args":{"employee_name":"name of employee"}}

IMPORTANT ROUTING SCENARIOS & RULES:
- STRICT RULE: ONLY use the 14 exact tools listed above. NEVER invent tools.
- ONLY OUTPUT JSON if using a tool. Do not say "Here is the JSON:" beforehand.
- NEVER guess an ID. ALWAYS use names or titles for arguments.

[SCENARIO MAP: HOW TO CHOOSE A TOOL]
1. GENERAL CAPABILITIES:
   - If Admin asks: "What can you do?" -> Output a natural language list of your modules. No JSON.

2. RECRUITMENT:
   - If Admin asks: "What jobs are open?" -> `search_job_posts`
   - If Admin asks: "Who applied for [Job]?" -> `search_applicants`
   - If Admin asks: "Show [Name]'s application" -> `applicant_details`

3. TRAINING (Admin Specific Rules):
   - ONLY use `search_training_programs` for general questions like: "What trainings are planned?", "Show active training programs", or "List all completed trainings."
   - ONLY use `training_details` when the Admin asks about a specific program by name, or asks who is attending it: "Who is enrolled in the Leadership Workshop?", "What is the start date for Cyber Security Training?", "Show details for [Training Name]."

4. ONBOARDING (Admin Specific Rules):
   - ONLY use `onboarding_status_summary` to get a high-level view of the company: "How many employees are currently onboarding?", "List completed onboardings", or "Show pending onboarding sessions."
   - ONLY use `employee_onboarding_details` when the Admin asks about a specific person: "Has [Name] finished their onboarding?", "Show [Name]'s onboarding checklist", "What tasks are pending for [Name]?"

5. APPRAISAL & KPIs (Admin Specific Rules):
   - ONLY use `employee_kpi_summary` when the Admin asks about an individual's performance: "Show [Name]'s KPI", "What is [Name]'s appraisal score?", "List KPIs for [Name]."
   - ONLY use `department_kpi_summary` when the Admin asks about a whole department: "What are the KPIs for the IT department?", "Show the Marketing team's KPIs." (Ensure you extract the department name correctly).

6. LEAVE & ATTENDANCE:
   - If Admin asks: "Who is on leave?" -> `leave_requests_summary`
   - If Admin asks: "[Name]'s leave balance" -> `employee_leave_balance`
   - If Admin asks: "Attendance for [Month]" -> `attendance_summary`

Final answer rules:
- After receiving TOOL_RESULT, return ONLY plain text or markdown formatting. No JSON.
- CURRENCY: Always use "RM " (Ringgit Malaysia) for all salaries, money, and financial figures. NEVER use the "$" symbol.
SYS;

        $messages = [
            ["role" => "system", "content" => $system],
        ];

        if (is_array($history)) {
            foreach ($history as $h) {
                if (isset($h['role']) && isset($h['content'])) {
                    $messages[] = [
                        "role" => $h['role'] === 'user' ? 'user' : 'assistant',
                        "content" => (string)$h['content']
                    ];
                }
            }
        }

        $messages[] = ["role" => "user", "content" => $userInput];

        $first = $this->ollamaChat($messages);
        $reply = $first['message']['content'] ?? '';

        $toolCall = $this->tryParseToolJson($reply);

        if ($this->isRecruitmentQuery($userInput) && !preg_match('/\b(onboarding|leave|balance)\b/i', $userInput)) {
            $pickedTool = is_array($toolCall) ? ($toolCall['tool'] ?? '') : '';
            if ($pickedTool === '' || $pickedTool === 'search_faqs') {
                $toolCall = [
                    'action' => 'tool',
                    'tool'   => 'search_job_posts',
                    'args'   => [
                        'keyword' => $this->extractRecruitmentKeyword($userInput)
                    ],
                ];
            }
        }

        // =========================
        // TOOL IMPLEMENTATIONS 
        // =========================
        
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_faqs') {
            $query = trim((string) ($toolCall['args']['query'] ?? ''));
            if ($query === '') $query = $userInput;

            $rows = DB::table('faqs')
                ->select('kb_id', 'module_scope', 'question', 'answer', 'keywords')
                ->where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('question', 'like', "%{$query}%")
                      ->orWhere('answer', 'like', "%{$query}%")
                      ->orWhere('keywords', 'like', "%{$query}%")
                      ->orWhere('module_scope', 'like', "%{$query}%");
                })
                ->limit(3)
                ->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT search_faqs:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown. Use ONLY the tool result to answer directly."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'attendance_summary') {
            $from = (string) ($toolCall['args']['from'] ?? '');
            $to   = (string) ($toolCall['args']['to'] ?? '');
            
            $isDate = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
            if (!$isDate($from) || !$isDate($to)) {
                return response()->json(['reply' => 'Please provide dates in YYYY-MM-DD format.']);
            }

            $table = Schema::hasTable('attendances') ? 'attendances' : 'attendance';
            $dateCol   = Schema::hasColumn($table, 'date') ? 'date' : 'attendance_date';
            $statusCol = Schema::hasColumn($table, 'at_status') ? 'at_status' : 'status';

            $base = DB::table($table)->whereBetween($dateCol, [$from, $to]);

            $total = (clone $base)->count();
            $byStatus = (clone $base)->select($statusCol . ' as status', DB::raw('COUNT(*) as total'))->groupBy($statusCol)->get();

            $payload = ["range" => ["from" => $from, "to" => $to], "total_records" => $total, "by_status" => $byStatus];

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT attendance_summary:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_job_posts') {
            $keyword = trim((string) ($toolCall['args']['keyword'] ?? ''));
            $status = trim((string) ($toolCall['args']['status'] ?? ''));

            $q = DB::table('job_posts as jp')
                ->select('jp.job_id', 'jp.job_title', 'jp.job_type', 'jp.department', 'jp.job_status', 'jp.salary_range', 'jp.requirements', 'jp.job_description');
            if ($status !== '') $q->where('jp.job_status', $status);
            if ($keyword !== '') $q->where('jp.job_title', 'like', "%{$keyword}%");
            $rows = $q->limit(5)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT search_job_posts:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'job_post_details') {
            $jobTitle = trim((string)($toolCall['args']['job_title'] ?? ''));
            $department = trim((string)($toolCall['args']['department'] ?? ''));
            $jobType = trim((string)($toolCall['args']['job_type'] ?? ''));
            
            if ($jobTitle === '' && $jobType === '') return response()->json(['reply' => 'Please provide a valid job title to get the details.']);

            $q = DB::table('job_posts as jp')->leftJoin('users as u', "u.{$usersPk}", '=', 'jp.posted_by')->select('jp.*', 'u.name as posted_by_name');
                
            if ($jobTitle !== '') $q->where('jp.job_title', 'like', "%{$jobTitle}%");
            if ($department !== '') $q->where('jp.department', 'like', "%{$department}%");
            if ($jobType !== '') $q->where('jp.job_type', 'like', "%{$jobType}%");
            
            $jobs = $q->get();

            if ($jobs->count() === 0) return response()->json(['reply' => "I could not find a job post matching that description."]);
            if ($jobs->count() > 1) {
                $options = $jobs->map(fn($j) => "- **{$j->job_title}** ({$j->job_type}) in **{$j->department}** department")->implode("\n");
                return response()->json(['reply' => "I found multiple job posts matching that description. Please clarify which one you mean:\n\n{$options}"]);
            }

            $job = $jobs->first();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT job_post_details:\n" . json_encode(["job" => $job]) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_applicants') {
            $jobTitle = trim((string) ($toolCall['args']['job_title'] ?? ''));
            $appStage = trim((string) ($toolCall['args']['app_stage'] ?? ''));
            $keyword = trim((string) ($toolCall['args']['keyword'] ?? ''));

            $q = DB::table('applications as a')
                ->join('applicant_profiles as ap', 'a.applicant_id', '=', 'ap.applicant_id')
                ->leftJoin('job_posts as jp', 'a.job_id', '=', 'jp.job_id')
                ->select('ap.full_name', 'ap.email', 'ap.phone', DB::raw("COALESCE(jp.job_title, 'Deleted/Unknown Job') as job_title"), 'a.app_stage', 'a.test_score', 'a.interview_score', 'a.overall_score', 'a.evaluation_notes');

            if ($appStage !== '') $q->where('a.app_stage', 'like', "%{$appStage}%");
            if ($jobTitle !== '') $q->where('jp.job_title', 'like', "%{$jobTitle}%");
            if ($keyword !== '') {
                $q->where(function($sub) use ($keyword) {
                    $sub->where('ap.full_name', 'like', "%{$keyword}%")->orWhere('ap.email', 'like', "%{$keyword}%");
                });
            }

            $rows = $q->orderBy('a.created_at', 'desc')->limit(5)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT search_applicants:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown. Answer directly based on the data provided."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'applicant_details') {
            $applicantName = trim((string) ($toolCall['args']['applicant_name'] ?? ''));
            if ($applicantName === '') return response()->json(['reply' => 'Please provide an applicant name to get their details.']);

            $profiles = DB::table('applicant_profiles')->where('full_name', 'like', "%{$applicantName}%")->get();

            if ($profiles->count() === 0) return response()->json(['reply' => "I could not find an applicant matching '{$applicantName}'."]);
            if ($profiles->count() > 1) {
                $names = $profiles->pluck('full_name')->implode(', ');
                return response()->json(['reply' => "I found multiple applicants matching '{$applicantName}' ({$names}). Please provide their exact full name."]);
            }

            $profile = $profiles->first();
            $applications = DB::table('applications as a')
                ->leftJoin('job_posts as jp', 'a.job_id', '=', 'jp.job_id')
                ->select(DB::raw("COALESCE(jp.job_title, 'Deleted/Unknown Job') as job_title"), 'a.app_stage', 'a.test_score', 'a.interview_score', 'a.overall_score', 'a.evaluation_notes', 'a.created_at')
                ->where('a.applicant_id', $profile->applicant_id)->get();

            $payload = ["applicant_info" => $profile, "applications" => $applications];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT applicant_details:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\nReturn ONLY clear text with markdown. Answer directly."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'leave_requests_summary') {
            $status = trim((string) ($toolCall['args']['status'] ?? ''));
            $date = trim((string) ($toolCall['args']['date'] ?? ''));

            $q = DB::table('leave_requests as lr')
                ->join('employees as e', 'lr.employee_id', '=', 'e.employee_id')
                ->join('users as u', "u.{$usersPk}", '=', 'e.user_id')
                ->join('leave_types as lt', 'lr.leave_type_id', '=', 'lt.leave_type_id')
                ->select('u.name', 'e.employee_code', 'lt.leave_name', 'lr.start_date', 'lr.end_date', 'lr.total_days', 'lr.leave_status', 'lr.reason');

            if ($status !== '' && in_array(strtolower($status), ['pending', 'approved', 'rejected'])) $q->where('lr.leave_status', strtolower($status));
            if ($date !== '') {
                $q->where('lr.start_date', '<=', $date)->where('lr.end_date', '>=', $date);
            }

            $rows = $q->orderBy('lr.start_date', 'desc')->limit(5)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT leave_requests_summary:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'employee_leave_balance') {
            $employeeName = trim((string) ($toolCall['args']['employee_name'] ?? ''));
            if ($employeeName === '') return response()->json(['reply' => 'Please provide an employee name.']);

            $empQuery = DB::table('employees as e')->join('users as u', "u.{$usersPk}", '=', 'e.user_id')->select('e.employee_id', 'e.employee_code', 'u.name');
            $empQuery->where('u.name', 'like', "%{$employeeName}%");
            $employees = $empQuery->get();

            if ($employees->count() === 0) return response()->json(['reply' => "I could not find an employee matching '{$employeeName}'."]);
            $employee = $employees->first();

            $leaveTypes = DB::table('leave_types')->get();
            $balances = [];

            foreach ($leaveTypes as $lt) {
                $usedDays = DB::table('leave_requests')->where('employee_id', $employee->employee_id)->where('leave_type_id', $lt->leave_type_id)->where('leave_status', 'approved')->sum('total_days');
                $balances[] = ['leave_type' => $lt->leave_name, 'total_entitlement' => $lt->default_days_year, 'used_days' => $usedDays, 'remaining_balance' => $lt->default_days_year - $usedDays];
            }

            $payload = ["employee" => $employee, "leave_balances" => $balances];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT employee_leave_balance:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'employee_kpi_summary') {
            $employeeName = trim((string) ($toolCall['args']['employee_name'] ?? ''));
            if ($employeeName === '') return response()->json(['reply' => 'Please provide an employee name.']);

            $empQuery = DB::table('employees as e')->join('users as u', "u.{$usersPk}", '=', 'e.user_id')->select('e.employee_id', 'e.employee_code', 'u.name');
            $empQuery->where('u.name', 'like', "%{$employeeName}%");
            $employees = $empQuery->get();

            if ($employees->count() === 0) return response()->json(['reply' => "I could not find an employee matching '{$employeeName}'."]);
            $employee = $employees->first();

            $kpis = DB::table('employee_kpis as ek')->join('kpi_templates as kt', 'ek.kpi_id', '=', 'kt.kpi_id')->where('ek.employee_id', $employee->employee_id)->get();
            $payload = ["employee" => $employee, "total_kpis" => $kpis->count(), "kpis" => $kpis];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT employee_kpi_summary:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'department_kpi_summary') {
            $departmentName = trim((string) ($toolCall['args']['department_name'] ?? ''));
            if ($departmentName === '') return response()->json(['reply' => 'Please provide a department name.']);

            $acronyms = ['IT' => 'Information Technology', 'HR' => 'Human Resources'];
            $upperName = strtoupper($departmentName);
            if (isset($acronyms[$upperName])) $departmentName = $acronyms[$upperName];

            $department = DB::table('departments')->where('department_name', 'like', "%{$departmentName}%")->first();
            if (!$department) return response()->json(['reply' => "I could not find a department matching '{$departmentName}'."]);

            $kpis = DB::table('department_kpis as dk')->join('kpi_templates as kt', 'dk.kpi_id', '=', 'kt.kpi_id')->where('dk.department_id', $department->department_id)->get();
            $payload = ["department" => $department->department_name, "total_kpis" => $kpis->count(), "kpis" => $kpis];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT department_kpi_summary:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_training_programs') {
            $status = trim((string) ($toolCall['args']['status'] ?? ''));
            $keyword = trim((string) ($toolCall['args']['keyword'] ?? ''));

            $q = DB::table('training_programs as tp')
                ->leftJoin('departments as d', 'tp.department_id', '=', 'd.department_id')
                ->select('tp.training_id', 'tp.training_name', 'tp.start_date', 'tp.end_date', 'tp.provider', 'tp.tr_status', 'tp.mode', 'tp.location', 'd.department_name');

            if ($status !== '' && in_array(strtolower($status), ['planned', 'active', 'completed'])) $q->where('tp.tr_status', strtolower($status));
            if ($keyword !== '') {
                $q->where(function($sub) use ($keyword) { $sub->where('tp.training_name', 'like', "%{$keyword}%")->orWhere('tp.provider', 'like', "%{$keyword}%"); });
            }

            $rows = $q->orderBy('tp.start_date', 'desc')->limit(5)->get();
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT search_training_programs:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'training_details') {
            $trainingName = trim((string) ($toolCall['args']['training_name'] ?? ''));
            if ($trainingName === '') return response()->json(['reply' => 'Please provide a training name.']);

            $training = DB::table('training_programs as tp')->leftJoin('departments as d', 'tp.department_id', '=', 'd.department_id')->select('tp.*', 'd.department_name')->where('tp.training_name', 'like', "%{$trainingName}%")->first();
            if (!$training) return response()->json(['reply' => "I could not find a training program matching '{$trainingName}'."]);

            $enrollments = DB::table('training_enrollments as te')->join('employees as e', 'te.employee_id', '=', 'e.employee_id')->join('users as u', "u.{$usersPk}", '=', 'e.user_id')->select('e.employee_code', 'u.name', 'te.enrollment_date', 'te.completion_status')->where('te.training_id', $training->training_id)->get();
            $payload = ["training_info" => $training, "total_enrolled" => $enrollments->count(), "enrollments" => $enrollments];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT training_details:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'onboarding_status_summary') {
            $status = trim((string) ($toolCall['args']['status'] ?? ''));
            $q = DB::table('onboarding as o')->join('employees as e', 'o.employee_id', '=', 'e.employee_id')->join('users as u', "u.{$usersPk}", '=', 'e.user_id')->select('o.onboarding_id', 'o.start_date', 'o.end_date', 'o.status', 'e.employee_code', 'u.name');
            if ($status !== '' && in_array(strtolower($status), ['pending', 'in_progress', 'completed'])) $q->where('o.status', strtolower($status));
            
            $rows = $q->orderBy('o.start_date', 'desc')->limit(5)->get();
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT onboarding_status_summary:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'employee_onboarding_details') {
            $employeeName = trim((string) ($toolCall['args']['employee_name'] ?? ''));
            if ($employeeName === '') return response()->json(['reply' => 'Please provide an employee name.']);

            $empQuery = DB::table('employees as e')->join('users as u', "u.{$usersPk}", '=', 'e.user_id')->select('e.employee_id', 'e.employee_code', 'u.name');
            $empQuery->where('u.name', 'like', "%{$employeeName}%");
            $employees = $empQuery->get();

            if ($employees->count() === 0) return response()->json(['reply' => "I could not find an employee matching '{$employeeName}'."]);
            if ($employees->count() > 1) {
                $names = $employees->pluck('name')->implode(', ');
                return response()->json(['reply' => "I found multiple employees matching '{$employeeName}' ({$names}). Please provide their exact name."]);
            }

            $employee = $employees->first();
            $onboarding = DB::table('onboarding')->where('employee_id', $employee->employee_id)->orderBy('start_date', 'desc')->first();
            if (!$onboarding) return response()->json(['reply' => "No onboarding record found for {$employee->name}."]);

            $tasks = DB::table('onboarding_task')->select('task_name', 'is_completed', 'completed_at', 'category', 'due_date')->where('onboarding_id', $onboarding->onboarding_id)->get();
            $payload = ["employee" => $employee, "onboarding_record" => $onboarding, "total_tasks" => $tasks->count(), "tasks" => $tasks];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "USER_QUESTION:\n{$userInput}\n\nTOOL_RESULT employee_onboarding_details:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '(no response)')]);
        }

        return response()->json(['reply' => $this->normalizeFinalReply($reply)]);
    }

    private function isRecruitmentQuery(string $text): bool
    {
        $t = strtolower($text);
        return preg_match('/\b(job post|vacancy|closing date|job descriptions?|open job|job_id)\b/', $t) === 1;
    }

    private function extractRecruitmentKeyword(string $text): string
    {
        $t = trim($text);
        $t = preg_replace('/\b(show|display|list|give|please|requirements?|job descriptions?|job post|vacancy|recruitment|for)\b/i', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    private function ollamaChat(array $messages): array
    {
        try {
            $res = Http::timeout(180)->post('http://localhost:11434/api/chat', [
                'model' => 'qwen2.5:7b', // Set back to your working tag
                'stream' => false,
                'messages' => $messages,
            ]);

            if (!$res->ok()) return ['message' => ['content' => 'Ollama API error: HTTP ' . $res->status()]];
            return $res->json();
        } catch (\Exception $e) {
            return ['message' => ['content' => "⚠️ **Local AI Connection Error:** The AI assistant ran out of memory or timed out. \n\n*Technical Details: " . $e->getMessage() . "*"]];
        }
    }

    private function tryParseToolJson(string $text): ?array
    {
        $clean = trim($text);
        $clean = preg_replace('/<think>.*?<\/think>/is', '', $clean);
        $clean = trim($clean);
        $clean = preg_replace('/\A```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```\z/', '', $clean);
        $clean = trim($clean);

        $start = strpos($clean, '{');
        $end   = strrpos($clean, '}');

        if ($start === false || $end === false || $end <= $start) return null;

        $json = substr($clean, $start, $end - $start + 1);
        $arr = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($arr)) return null;
        return $arr;
    }

    private function normalizeFinalReply(string $text): string
    {
        $clean = trim($text);
        $clean = preg_replace('/<think>.*?<\/think>/is', '', $clean);
        $clean = trim($clean);
        $clean = preg_replace('/\A```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```\z/', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach (['reply', 'answer', 'summary', 'text', 'message'] as $k) {
                if (isset($decoded[$k]) && is_string($decoded[$k]) && trim($decoded[$k]) !== '') return trim($decoded[$k]);
            }
        }
        return $clean;
    }
}