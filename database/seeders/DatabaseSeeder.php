<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Position;
use App\Models\Employee;
use App\Models\User;
use App\Models\ApplicantProfile;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Core Structure
        $this->call([
            AdminSeeder::class,              // Creates Admin User (ID 1)
            DepartmentPositionSeeder::class, // Creates HR Dept & Positions
            EmployeeSeeder::class,           // Creates John Employee
            KpiTemplateSeeder::class,
            LeaveTypeSeeder::class,
        ]);

        // =========================================================
        // 2. THE ADMIN PROFILE FIX (CRITICAL STEP)
        // =========================================================
        
        // Find the Admin User created by AdminSeeder
        $adminUser = User::where('email', 'admin@example.com')->first();
        
        // Find the HR Department created by DepartmentPositionSeeder
        $hrDept = Department::where('department_name', 'Human Resources')->first();
        
        // Find the HR Manager Position
        $hrPos = Position::where('position_name', 'HR Manager')->first();

        // Create the Employee Record for Admin so the Profile Page works
        if ($adminUser && $hrDept && $hrPos) {
            Employee::firstOrCreate(
                ['user_id' => $adminUser->user_id], 
                [
                    'department_id'   => $hrDept->department_id,
                    'position_id'     => $hrPos->position_id,
                    'employee_code'   => 'ADMIN-001',
                    'employee_status' => 'active',
                    'hire_date'       => now(),
                    'base_salary'     => 8000.00,
                    'phone'           => '012-3456789', // Default Phone
                    'address'         => 'HQ Office, Level 10',
                ]
            );
        }

        // =========================================================
        // 3. Continue with Dummy Data Generation
        // =========================================================

        $departments = Department::all();
        $positions = Position::all();

        // Generate 20 Dummy EMPLOYEES linked to real Departments
        User::factory(20)->create(['role' => 'employee'])->each(function ($user) use ($departments, $positions) {
            $dept = $departments->random();
            $pos = $positions->where('department_id', $dept->department_id)->first() ?? $positions->random();

            Employee::factory()->create([
                'user_id' => $user->user_id,
                'department_id' => $dept->department_id,
                'position_id' => $pos->position_id,
                'employee_status' => 'active',
            ]);
        });

        // Generate 10 Dummy APPLICANTS
        User::factory(10)->create(['role' => 'applicant'])->each(function ($user) {
            ApplicantProfile::factory()->create([
                'applicant_id' => $user->user_id, // Ensure ID matches if using shared ID strategy
                'user_id' => $user->user_id,      // Explicitly link user_id
                'full_name' => $user->name,
            ]);
        });

        // Generate Transactional Data
        $this->call([
            PayrollSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
