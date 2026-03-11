<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['leave_name' => 'Annual Leave',        'le_description' => 'Paid annual leave',                 'default_days_year' => 14],
            ['leave_name' => 'Sick Leave',          'le_description' => 'Paid sick leave',                   'default_days_year' => 8],
            ['leave_name' => 'Emergency Leave',     'le_description' => 'Short-notice urgent matters',       'default_days_year' => 3],
            ['leave_name' => 'Compassionate Leave', 'le_description' => 'Bereavement / compassionate leave', 'default_days_year' => 5],
            ['leave_name' => 'Maternity Leave',     'le_description' => 'Maternity entitlement',             'default_days_year' => 60],
            ['leave_name' => 'Paternity Leave',     'le_description' => 'Paternity entitlement',             'default_days_year' => 7],
            ['leave_name' => 'Study Leave',         'le_description' => 'Training / exam leave',             'default_days_year' => 5],
        ];

        foreach ($defaults as $row) {
            LeaveType::updateOrCreate(
                ['leave_name' => $row['leave_name']],
                ['le_description' => $row['le_description'], 'default_days_year' => $row['default_days_year']]
            );
        }
    }
}
