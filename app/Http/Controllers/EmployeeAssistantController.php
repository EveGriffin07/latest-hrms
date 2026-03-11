<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class EmployeeAssistantController extends Controller
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

        // ==========================================
        // SECURITY CHECK: Employee Access Only
        // ==========================================
        $user = auth()->user();
        $role = $user->role ?? null;
        if ($role !== 'employee') {
            return response()->json(['reply' => 'Forbidden: Employee access only.'], 403);
        }

$employeeRecord = DB::table('employees')->where('user_id', $user->user_id)->first();

if (!$employeeRecord) {
    return response()->json(['reply' => 'Error: We could not find your employee profile.'], 400);
}

$myEmployeeId = $employeeRecord->employee_id;
        $myEmployeeId = $employeeRecord->employee_id;

        // ==========================================
        // SYSTEM PROMPT: Employee Scoped Tools
        // ==========================================
        $system = <<<SYS
You are an Employee Self-Service HR Assistant. Today's Date is: {$today}.

If you need info from the database, respond ONLY with RAW valid JSON (no markdown, no conversational text before or after), exactly like:

1) My Leave Balance:
{"action":"tool","tool":"my_leave_balance"}

2) My Leave Requests:
{"action":"tool","tool":"my_leave_requests","args":{"status":"pending|approved|rejected (optional)"}}

3) My KPI Progress:
{"action":"tool","tool":"my_kpi_summary"}

4) My Onboarding Tasks:
{"action":"tool","tool":"my_onboarding_tasks"}

5) FAQ / policy:
{"action":"tool","tool":"search_faqs","args":{"query":"..."}}

6) Company Job Posts (Internal Mobility):
{"action":"tool","tool":"search_job_posts","args":{"keyword":"optional text"}}

IMPORTANT ROUTING SCENARIOS & RULES:
- STRICT RULE: ONLY use the 6 exact tools listed above. NEVER invent tools.
- ONLY OUTPUT JSON if using a tool. Do not say "Here is the JSON:" beforehand.
- SECURITY RULE: You DO NOT need to ask for the user's name or ID. The system already knows exactly who you are talking to.
- SECURITY RULE: If the user asks about another employee's private data (leave, KPIs, etc.), politely decline and say you only have access to their personal data.

[SCENARIO MAP: HOW TO CHOOSE A TOOL]
1. GENERAL: If user asks "What can you do?" -> DO NOT use a tool. Output a clean, natural language bulleted list of your Employee modules.
2. MY LEAVE: "How much leave do I have?", "Leave balance" -> Tool: `my_leave_balance`
3. MY LEAVE REQUESTS: "Did my leave get approved?", "My pending leave" -> Tool: `my_leave_requests`
4. MY KPIs: "What are my KPIs?", "My performance" -> Tool: `my_kpi_summary`
5. MY ONBOARDING: "What onboarding tasks do I have left?" -> Tool: `my_onboarding_tasks`
6. FAQs: "What is the policy on...", "How do I..." -> Tool: `search_faqs`
7. JOBS: "What jobs are open?", "Internal vacancies" -> Tool: `search_job_posts`

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

        // Security check before hitting AI: Reject explicit requests for others
        if (preg_match('/\b(harley|hakim|powderin|admin)\b/i', $userInput) && !preg_match('/\b(my|i|me)\b/i', $userInput)) {
             return response()->json(['reply' => 'For security reasons, I can only provide information regarding your own employee profile. I cannot look up data for other employees.']);
        }

        $first = $this->ollamaChat($messages);
        $reply = $first['message']['content'] ?? '';

        $toolCall = $this->tryParseToolJson($reply);

        // ==========================================
        // SECURE TOOL IMPLEMENTATIONS
        // ==========================================
        
        // 1. MY LEAVE BALANCE
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_leave_balance') {
            $leaveTypes = DB::table('leave_types')->get();
            $balances = [];

            foreach ($leaveTypes as $lt) {
                $usedDays = DB::table('leave_requests')
                    ->where('employee_id', $myEmployeeId) // Strictly locked to session user
                    ->where('leave_type_id', $lt->leave_type_id)
                    ->where('leave_status', 'approved')
                    ->sum('total_days');
                
                $balances[] = [
                    'leave_type' => $lt->leave_name, 
                    'used_days' => $usedDays, 
                    'remaining_balance' => $lt->default_days_year - $usedDays
                ];
            }

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_leave_balance:\n" . json_encode(["my_balances" => $balances]) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // 2. MY LEAVE REQUESTS
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_leave_requests') {
            $status = trim((string) ($toolCall['args']['status'] ?? ''));

            $q = DB::table('leave_requests as lr')
                ->join('leave_types as lt', 'lr.leave_type_id', '=', 'lt.leave_type_id')
                ->where('lr.employee_id', $myEmployeeId) // Strictly locked to session user
                ->select('lt.leave_name', 'lr.start_date', 'lr.end_date', 'lr.total_days', 'lr.leave_status', 'lr.reason');

            if ($status !== '' && in_array(strtolower($status), ['pending', 'approved', 'rejected'])) {
                $q->where('lr.leave_status', strtolower($status));
            }

            $rows = $q->orderBy('lr.start_date', 'desc')->limit(5)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_leave_requests:\n" . $rows->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // 3. MY KPI SUMMARY
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_kpi_summary') {
            $kpis = DB::table('employee_kpis as ek')
                ->join('kpi_templates as kt', 'ek.kpi_id', '=', 'kt.kpi_id')
                ->where('ek.employee_id', $myEmployeeId) // Strictly locked to session user
                ->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_kpi_summary:\n" . $kpis->toJson() . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // 4. MY ONBOARDING TASKS
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'my_onboarding_tasks') {
            $onboarding = DB::table('onboarding')
                ->where('employee_id', $myEmployeeId) // Strictly locked to session user
                ->orderBy('start_date', 'desc')
                ->first();

            if (!$onboarding) {
                return response()->json(['reply' => "You do not have any active onboarding records."]);
            }

            $tasks = DB::table('onboarding_task')
                ->select('task_name', 'is_completed', 'completed_at', 'category', 'due_date')
                ->where('onboarding_id', $onboarding->onboarding_id)
                ->get();

            $payload = ["onboarding_status" => $onboarding->status, "tasks" => $tasks];
            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT my_onboarding_tasks:\n" . json_encode($payload) . "\n\nReturn ONLY clear text with markdown."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // 5. PUBLIC FAQs
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_faqs') {
            $query = trim((string) ($toolCall['args']['query'] ?? ''));
            if ($query === '') $query = $userInput;

            $rows = DB::table('faqs')->select('question', 'answer')
                ->where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('question', 'like', "%{$query}%")->orWhere('answer', 'like', "%{$query}%");
                })->limit(3)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT search_faqs:\n" . $rows->toJson() . "\n\nReturn ONLY clear text."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        // 6. PUBLIC JOB POSTS
        if (is_array($toolCall) && ($toolCall['tool'] ?? '') === 'search_job_posts') {
            $keyword = trim((string) ($toolCall['args']['keyword'] ?? ''));

            $q = DB::table('job_posts')
                ->select('job_title', 'job_type', 'department', 'salary_range', 'requirements')
                ->where('job_status', 'Open'); // Employees can ONLY see Open jobs

            if ($keyword !== '') $q->where('job_title', 'like', "%{$keyword}%");
            $rows = $q->limit(5)->get();

            $messages[] = ["role" => "assistant", "content" => json_encode($toolCall, JSON_UNESCAPED_UNICODE)];
            $messages[] = ["role" => "user", "content" => "TOOL_RESULT search_job_posts:\n" . $rows->toJson() . "\n\nReturn ONLY clear text."];
            $second = $this->ollamaChat($messages);
            return response()->json(['reply' => $this->normalizeFinalReply($second['message']['content'] ?? '')]);
        }

        return response()->json(['reply' => $this->normalizeFinalReply($reply)]);
    }

    private function ollamaChat(array $messages): array
    {
        try {
            $res = Http::timeout(180)->post('http://localhost:11434/api/chat', [
                'model' => 'qwen2.5:7b',
                'stream' => false,
                'messages' => $messages,
            ]);
            if (!$res->ok()) return ['message' => ['content' => 'Ollama error.']];
            return $res->json();
        } catch (\Exception $e) {
            return ['message' => ['content' => "AI Connection Error."]];
        }
    }

    private function tryParseToolJson(string $text): ?array
    {
        $clean = trim(preg_replace('/<think>.*?<\/think>/is', '', $text));
        $clean = trim(preg_replace('/\A```(?:json)?\s*/i', '', $clean));
        $clean = trim(preg_replace('/\s*```\z/', '', $clean));

        $start = strpos($clean, '{');
        $end   = strrpos($clean, '}');
        if ($start === false || $end === false) return null;

        $arr = json_decode(substr($clean, $start, $end - $start + 1), true);
        return is_array($arr) ? $arr : null;
    }

    private function normalizeFinalReply(string $text): string
    {
        $clean = trim(preg_replace('/<think>.*?<\/think>/is', '', $text));
        $clean = trim(preg_replace('/\A```(?:json)?\s*/i', '', $clean));
        $clean = trim(preg_replace('/\s*```\z/', '', $clean));

        $decoded = json_decode($clean, true);
        if (is_array($decoded) && isset($decoded['reply'])) return $decoded['reply'];
        return $clean;
    }
}