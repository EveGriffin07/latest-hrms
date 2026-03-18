<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Position;

class DepartmentPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the Departments and their respective Job Titles
        $structure = [
            'Human Resources' => [
                'HR Manager',
                'Recruitment Specialist',
                'HR Generalist',
                'Payroll Officer',
                'Training Coordinator'
            ],
            'Information Technology' => [
                'IT Manager',
                'Software Engineer',
                'System Administrator',
                'IT Support Specialist',
                'Data Analyst'
            ],
            'Finance' => [
                'Finance Manager',
                'Senior Accountant',
                'Financial Analyst',
                'Auditor',
                'Bookkeeper'
            ],
            'Sales' => [
                'Sales Manager',
                'Account Executive',
                'Sales Representative',
                'Business Development Manager',
                'Customer Success Manager'
            ],
            'Marketing' => [
                'Marketing Manager',
                'Digital Marketing Specialist',
                'Content Creator',
                'Social Media Manager',
                'Graphic Designer'
            ],
            // General / Unassigned (Optional)
            'General' => [
                'Supervisor',
                'Intern',
                'Office Assistant'
            ]
        ];

        foreach ($structure as $deptName => $jobs) {
            // 1. Create the Department
            // We use firstOrCreate so we don't create duplicates if run twice
            $department = Department::firstOrCreate(
                ['department_name' => $deptName],
                ['de_description' => 'Department for ' . $deptName . ' operations.']
            );

            // 2. Create the Positions for this Department
            foreach ($jobs as $jobTitle) {
                Position::firstOrCreate([
                    'position_name' => $jobTitle,
                    'department_id' => $department->department_id 
                ], [
                    'pos_description' => 'Responsible for ' . $jobTitle . ' duties.'
                ]);
            }
        }
    }
}