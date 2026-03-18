<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Penalty;
use App\Services\PenaltyGenerationService;
use App\Services\AttendanceEvaluationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillAttendancePenalties extends Command
{
    protected $signature = 'penalties:backfill-attendance 
        {--employee_id= : Limit to a specific employee_id} 
        {--start= : Start date (Y-m-d)} 
        {--end= : End date (Y-m-d)}';

    protected $description = 'Backfill system-generated penalties from historical attendance records';

    public function handle(PenaltyGenerationService $service): int
    {
        $startOpt = $this->option('start');
        $endOpt = $this->option('end');

        $startDate = $startOpt ? Carbon::parse($startOpt)->startOfDay() : Carbon::today()->subDays(30);
        $endDate = $endOpt ? Carbon::parse($endOpt)->endOfDay() : Carbon::today();

        if ($startDate->gt($endDate)) {
            $this->error('Start date must be before or equal to end date.');
            return Command::FAILURE;
        }

        $empQuery = Employee::query();
        if ($empId = $this->option('employee_id')) {
            $empQuery->where('employee_id', $empId);
        }

        $holidays = config('hrms.overtime.holidays', []);

        $employeesChecked = 0;
        $workdaysScanned = 0;
        $penaltiesCreated = 0;
        $penaltiesUpdated = 0;
        $penaltiesSkipped = 0;
        $absencesSkippedForLeave = 0;
        $datesSkippedInactive = 0;

        $this->info("Backfilling penalties from {$startDate->toDateString()} to {$endDate->toDateString()}...");
        $evalService = app(AttendanceEvaluationService::class);

        $empQuery->chunkById(100, function ($employees) use (
            $service,
            $startDate,
            $endDate,
            $holidays,
            &$employeesChecked,
            &$workdaysScanned,
            &$penaltiesCreated,
            &$penaltiesUpdated,
            &$penaltiesSkipped,
            &$absencesSkippedForLeave,
            &$datesSkippedInactive
        ) {
            /** @var \Illuminate\Support\Collection<int, Employee> $employees */
            foreach ($employees as $emp) {
                $employeesChecked++;

                $current = $startDate->copy();
                while ($current->lte($endDate)) {
                    $dateStr = $current->toDateString();

                    // Employment window checks (hire_date + optional termination_date)
                    if ($emp->hire_date && $current->lt(Carbon::parse($emp->hire_date))) {
                        $datesSkippedInactive++;
                        $current->addDay();
                        continue;
                    }
                    if (! empty($emp->termination_date) && $current->gt(Carbon::parse($emp->termination_date))) {
                        $datesSkippedInactive++;
                        $current->addDay();
                        continue;
                    }

                    // Skip weekends and configured holidays
                    if (in_array($current->dayOfWeekIso, [6, 7], true) || in_array($dateStr, $holidays, true)) {
                        $current->addDay();
                        continue;
                    }

                    $workdaysScanned++;

                    // Real attendance row (if any)
                    $att = Attendance::where('employee_id', $emp->employee_id)
                        ->whereDate('date', $dateStr)
                        ->first();

                    if ($att) {
                        // Evaluate and sync penalties from actual attendance row.
                        $beforePenalties = Penalty::where('attendance_id', $att->attendance_id)
                            ->where('employee_id', $att->employee_id)
                            ->whereIn('penalty_type', ['late', 'absent', 'early_leave'])
                            ->where('created_source', 'system')
                            ->count();

                        $service->syncForAttendance($att);

                        $afterPenalties = Penalty::where('attendance_id', $att->attendance_id)
                            ->where('employee_id', $att->employee_id)
                            ->whereIn('penalty_type', ['late', 'absent', 'early_leave'])
                            ->where('created_source', 'system')
                            ->count();

                        if ($afterPenalties > $beforePenalties) {
                            $penaltiesCreated += ($afterPenalties - $beforePenalties);
                        } elseif ($afterPenalties === $beforePenalties && $afterPenalties > 0) {
                            $penaltiesUpdated += $afterPenalties;
                        } else {
                            $penaltiesSkipped++;
                        }

                        $current->addDay();
                        continue;
                    }

                    // No attendance row – treat as potential absence, but check for leave.
                    $onLeave = LeaveRequest::where('employee_id', $emp->employee_id)
                        ->whereDate('start_date', '<=', $dateStr)
                        ->whereDate('end_date', '>=', $dateStr)
                        ->whereIn('leave_status', ['approved', 'supervisor_approved', 'pending_admin'])
                        ->exists();

                    if ($onLeave) {
                        $absencesSkippedForLeave++;
                        $current->addDay();
                        continue;
                    }

                    // Upsert missing-attendance absence penalty.
                    $existing = Penalty::whereNull('attendance_id')
                        ->where('employee_id', $emp->employee_id)
                        ->where('assigned_at', $dateStr)
                        ->where('penalty_type', 'absent')
                        ->where('created_source', 'system_missing_attendance')
                        ->first();

                    $config = config('hrms.penalties', []);
                    $points = (float) ($config['absent_points'] ?? 3);

                    if ($existing) {
                        $existing->default_amount = $points;
                        $existing->penalty_name = 'Absent from scheduled workday (no check-in)';
                        $existing->status = $existing->status ?: 'pending';
                        $existing->save();
                        $penaltiesUpdated++;
                    } else {
                        Penalty::create([
                            'employee_id'    => $emp->employee_id,
                            'attendance_id'  => null,
                            'penalty_type'   => 'absent',
                            'penalty_name'   => 'Absent from scheduled workday (no check-in)',
                            'default_amount' => $points,
                            'assigned_at'    => $dateStr,
                            'status'         => 'pending',
                            'created_source' => 'system_missing_attendance',
                        ]);
                        $penaltiesCreated++;
                    }

                    $current->addDay();
                }
            }
        });

        $this->info("Employees checked: {$employeesChecked}");
        $this->info("Workdays scanned: {$workdaysScanned}");
        $this->info("Penalties created: {$penaltiesCreated}");
        $this->info("Penalties updated: {$penaltiesUpdated}");
        $this->info("Penalties skipped (no change or not applicable): {$penaltiesSkipped}");
        $this->info("Absence days skipped due to approved leave: {$absencesSkippedForLeave}");
        $this->info("Dates skipped because employee inactive: {$datesSkippedInactive}");

        return Command::SUCCESS;
    }
}

