<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\Attendance;
use Carbon\Carbon;

/**
 * Inserts/updates user and employee "Chai Chian Zhi" with data needed for Generate Payroll:
 * - User (name CHAI CHIAN ZHI), Employee (active, base_salary, hire_date, department, position)
 * - Attendance records for the current month so payroll has present days to calculate.
 */
class ChaiChianZhiPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $dept = Department::firstOrCreate(
            ['department_name' => 'Information Technology'],
            ['de_description' => 'Handles IT infrastructure and software.']
        );

        $pos = Position::firstOrCreate(
            ['position_name' => 'Software Engineer'],
            [
                'pos_description' => 'Standard Dev Role',
                'department_id' => $dept->department_id,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'chai.chianzhi@example.com'],
            [
                'name'     => 'CHAI CHIAN ZHI',
                'password' => Hash::make('password123'),
                'role'     => 'employee',
            ]
        );

        if (!$user->wasRecentlyCreated) {
            $user->update(['name' => 'CHAI CHIAN ZHI']);
        }

        $employee = Employee::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'department_id'   => $dept->department_id,
                'position_id'     => $pos->position_id,
                'employee_code'   => 'EMP-CHAI',
                'employee_status' => 'active',
                'hire_date'       => Carbon::now()->subMonths(6)->toDateString(),
                'base_salary'     => 5500.00,
                'phone'           => '012-9876543',
                'address'         => 'Kuala Lumpur',
            ]
        );

        $employee->update([
            'employee_status' => 'active',
            'base_salary'     => 5500.00,
            'department_id'   => $dept->department_id,
            'position_id'     => $pos->position_id,
        ]);

        $periodStart = Carbon::now()->startOfMonth();
        $periodEnd   = Carbon::now()->endOfMonth();
        $today       = Carbon::today();

        for ($d = $periodStart->copy(); $d->lte($periodEnd) && $d->lte($today); $d->addDay()) {
            if ($d->isWeekend()) {
                continue;
            }
            Attendance::firstOrCreate(
                [
                    'employee_id' => $employee->employee_id,
                    'date'        => $d->toDateString(),
                ],
                [
                    'clock_in_time'  => '08:55:00',
                    'clock_out_time' => '17:30:00',
                    'at_status'      => 'present',
                    'late_minutes'   => 0,
                ]
            );
        }

        $this->command->info('Chai Chian Zhi: user_id=' . $user->user_id . ', employee_id=' . $employee->employee_id . '. Attendance seeded for ' . $periodStart->format('Y-m') . '.');
    }
}
