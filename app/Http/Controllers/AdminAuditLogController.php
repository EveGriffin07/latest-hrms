<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuditLogController extends Controller
{
    private const ALLOWED_ROLES = ['admin', 'administrator', 'hr', 'manager'];

    private function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $role = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($role, self::ALLOWED_ROLES, true)) {
            return true;
        }
        // Fallback: first user (user_id 1) is often the main admin
        $userId = $user->user_id ?? $user->id ?? null;
        if ($userId === 1 || $userId === '1') {
            return true;
        }
        return false;
    }

    /**
     * Audit Log page. Only Admin / HR / Manager. Else redirect + "Access denied".
     */
    public function index()
    {
        if (! $this->canAccess()) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied. Only Admin, HR, or Manager can view the audit log.');
        }

        return view('admin.audit_log');
    }

    /**
     * Paginated data: keyword search (actor name, employee id/name, entity id, message),
     * filters (action, type, environment, date_range, status), server-side.
     */
    public function data(Request $request)
    {
        if (! $this->canAccess()) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'max:64'],
            'type' => ['nullable', 'string', 'max:64'], // entity_type / Face / Leave / Attendance / Auth
            'environment' => ['nullable', 'string', 'in:Production,Staging,Demo'],
            'date_range' => ['nullable', 'string', 'in:all,today,last7,last30,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'in:SUCCESS,FAILED'],
        ]);

        $perPage = (int) ($request->input('per_page') ?: 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        $query = AuditLog::with(['employee.user', 'actor'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('keyword')) {
            $kw = trim($request->input('keyword'));
            $query->where(function ($q) use ($kw) {
                $q->where('message', 'like', '%' . $kw . '%')
                    ->orWhere('actor_name', 'like', '%' . $kw . '%')
                    ->orWhere('entity_id', 'like', '%' . $kw . '%')
                    ->orWhere('action_type', 'like', '%' . $kw . '%');
                $q->orWhereHas('employee', function ($e) use ($kw) {
                    $e->where('employee_code', 'like', '%' . $kw . '%')
                        ->orWhere('employee_id', $kw);
                });
                $q->orWhereHas('employee.user', function ($u) use ($kw) {
                    $u->where('name', 'like', '%' . $kw . '%')
                        ->orWhere('email', 'like', '%' . $kw . '%');
                });
            });
        }

        if ($request->filled('action')) {
            $query->where('action_type', $request->input('action'));
        }
        if ($request->filled('type')) {
            $typeVal = $request->input('type');
            $query->where(function ($q) use ($typeVal) {
                $q->where('entity_type', $typeVal)->orWhere('action_category', $typeVal);
            });
        }
        if ($request->filled('environment')) {
            $query->where('environment', $request->input('environment'));
        }
        if ($request->filled('status')) {
            $query->where('action_status', $request->input('status'));
        }

        $dateFrom = null;
        $dateTo = null;
        switch ($request->input('date_range')) {
            case 'today':
                $dateFrom = now()->startOfDay();
                $dateTo = now();
                break;
            case 'last7':
                $dateFrom = now()->subDays(7)->startOfDay();
                $dateTo = now();
                break;
            case 'last30':
                $dateFrom = now()->subDays(30)->startOfDay();
                $dateTo = now();
                break;
            case 'custom':
                if ($request->filled('date_from')) {
                    $dateFrom = \Carbon\Carbon::parse($request->input('date_from'))->startOfDay();
                }
                if ($request->filled('date_to')) {
                    $dateTo = \Carbon\Carbon::parse($request->input('date_to'))->endOfDay();
                }
                break;
        }
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(function ($log) {
            $emp = $log->employee;
            return [
                'id' => $log->id,
                'user' => [
                    'name' => $log->actor_name ?? $log->actor?->name ?? '—',
                    'role' => $log->actor_role ?? $log->actor?->role ?? $log->actor_type ?? '—',
                    'avatar_url' => $log->actor_avatar_url ?? null,
                ],
                'entity_id' => $log->entity_id ?? '—',
                'action' => $log->action_type,
                'type' => $log->log_type ?? $log->entity_type ?? $log->action_category ?? '—',
                'environment' => $log->environment ?? 'Production',
                'timestamp' => $log->created_at?->format('Y-m-d H:i:s'),
                'status' => $log->action_status,
                'message' => $log->message,
                'employee_id' => $log->employee_id,
                'employee_name' => $emp?->user?->name ?? null,
                'employee_code' => $emp?->employee_code ?? null,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ]);
    }

    /**
     * Single log for View detail. Never expose embeddings.
     */
    public function show(int $id)
    {
        if (! $this->canAccess()) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $log = AuditLog::with(['employee.user', 'actor'])->findOrFail($id);

        $metadata = $log->metadata ?? [];
        unset($metadata['embedding'], $metadata['embeddings']);
        if (isset($metadata['similarity_score'])) {
            $metadata['similarity_score'] = round((float) $metadata['similarity_score'], 4);
        }

        return response()->json([
            'id' => $log->id,
            'actor_name' => $log->actor_name ?? $log->actor?->name ?? '—',
            'actor_role' => $log->actor_role ?? $log->actor?->role ?? $log->actor_type ?? '—',
            'actor_avatar_url' => $log->actor_avatar_url ?? null,
            'action' => $log->action_type,
            'status' => $log->action_status,
            'entity_type' => $log->entity_type ?? $log->action_category ?? '—',
            'entity_id' => $log->entity_id ?? '—',
            'type' => $log->log_type ?? 'Web',
            'environment' => $log->environment ?? 'Production',
            'timestamp' => $log->created_at?->format('Y-m-d H:i:s'),
            'message' => $log->message,
            'metadata' => $metadata,
            'employee_id' => $log->employee_id,
            'employee_name' => $log->employee?->user?->name ?? '—',
        ]);
    }
}
