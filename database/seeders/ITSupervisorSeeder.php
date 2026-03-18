<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Support\Facades\Hash;

class ITSupervisorSeeder extends Seeder
{
    public function run()
    {

        // 2. Ensure a Managerial Position exists for IT
        // The is_manager => true is the secret key that grants them Supervisor access!
        $itSupervisorPos = Position::firstOrCreate(
            [
                'position_name' => 'IT Supervisor',
                'department_id' => $itDept->department_id
            ],
            [
                'is_manager' => true, 
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // 3. Create the User account for logging in
        $user = User::firstOrCreate(
            ['email' => 'itsupervisor@company.com'],
            [
                'name' => 'Alex IT Supervisor',
                'password' => Hash::make('password123'), // Default test password
                'role' => 'employee', // They are technically an 'employee' role in the users table
            ]
        );

        // 4. Create the Employee profile linked to everything above
        Employee::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'department_id' => department_id,
                'position_id' => $itSupervisorPos->position_id,
                'supervisor_id' => null, // They are the boss of their team, so they might not have a direct supervisor
                'employee_code' => 'EMP-IT-999',
                'employee_status' => 'active',
                'hire_date' => now()->subYears(2), // Hired 2 years ago
                'base_salary' => 8000.00,
                'phone' => '012-3456789',
                'address' => 'IT Dept, Corporate HQ',
            ]
        );

        $this->command->info('IT Supervisor account created successfully!');
        $this->command->info('Email: itsupervisor@company.com');
        $this->command->info('Password: password123');
    }
}