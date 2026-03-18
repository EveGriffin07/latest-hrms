<?php

namespace App\Http\Middleware;

use App\Models\LeaveRequest;
use App\Models\OvertimeRecord;
use App\Models\PayrollPeriod;
use App\Models\Penalty;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class EnsurePayrollUnlocked
{
    public function handle(Request $request, Closure $next)
    {
        // Determine relevant date range from route model bindings or request inputs
        [$start, $end] = $this->extractDateRange($request);

        if ($start && $this->isLockedRange($start, $end ?? $start)) {
            $message = 'Payroll period locked. Changes must be applied to next period.';

            // For API / AJAX calls, keep JSON response with 423 status
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => $message], 423);
            }

            // For normal web requests, show a UI message on the previous page
            return redirect()->back()->with('error', $message);
        }

        return $next($request);
    }

    private function extractDateRange(Request $request): array
    {
        // Route-model aware checks
        if ($request->route('overtime') instanceof OvertimeRecord) {
            $d = $request->route('overtime')->date;
            return [$d, $d];
        }
        if ($request->route('penalty') instanceof Penalty) {
            $d = $request->route('penalty')->assigned_at;
            return [$d, $d];
        }
        if ($request->route('leave') instanceof LeaveRequest) {
            $leave = $request->route('leave');
            return [$leave->start_date, $leave->end_date];
        }

        // Fallback to request dates
        $date = $request->input('date') ?? $request->input('assigned_at');
        if ($date) return [$date, $date];

        $start = $request->input('start_date') ?? $request->input('start');
        $end   = $request->input('end_date') ?? $request->input('end');

        // Face verify routes (no date in payload) → assume today
        if (!$start && str_contains($request->path(), 'face/verify')) {
            $today = now()->toDateString();
            return [$today, $today];
        }

        return [$start, $end];
    }

    private function isLockedRange($start, $end): bool
    {
        if (!$start) return false;
        $s = Carbon::parse($start)->startOfDay();
        $e = $end ? Carbon::parse($end)->endOfDay() : $s->copy()->endOfDay();

        return PayrollPeriod::where('status', 'LOCKED')
            ->whereDate('start_date', '<=', $e->toDateString())
            ->whereDate('end_date', '>=', $s->toDateString())
            ->exists();
    }
}
