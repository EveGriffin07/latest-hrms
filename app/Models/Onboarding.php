<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Onboarding extends Model
{
    use HasFactory;

    protected $table = 'onboarding';
    protected $primaryKey = 'onboarding_id';

    protected $fillable = [
        'employee_id',
        'assigned_by',
        'start_date',
        'end_date',
        'status', 
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function tasks()
    {
        return $this->hasMany(OnboardingTask::class, 'onboarding_id', 'onboarding_id');
    }

    public function getProgressAttribute()
    {
        $total = $this->tasks->count();
        if ($total == 0) return 0;

        $completed = $this->tasks->where('is_completed', true)->count();
        return round(($completed / $total) * 100);
    }

    // ======================================================
    // NEW: REUSABLE ONBOARDING GENERATOR
    // Tell your friend to call \App\Models\Onboarding::generateForNewEmployee($emp_id, $admin_id)
    // ======================================================
    public static function generateForNewEmployee($employeeId, $adminId)
    {
        $onboarding = self::create([
            'employee_id' => $employeeId,
            'assigned_by' => $adminId, 
            'start_date'  => now(),
            'end_date'    => now()->addDays(7),
            'status'      => 'pending'
        ]);

        $defaultTasks = [
            ['name' => 'Submit Identity Documents',   'cat' => 'HR Docs'],
            ['name' => 'Sign Employment Contract',    'cat' => 'Legal'],
            ['name' => 'Setup Corporate Email',       'cat' => 'IT Setup'],
            ['name' => 'Attend Company Orientation',  'cat' => 'Training'],
            ['name' => 'Meet Reporting Manager',      'cat' => 'Integration'],
        ];

        foreach ($defaultTasks as $task) {
            \App\Models\OnboardingTask::create([
                'onboarding_id' => $onboarding->onboarding_id,
                'task_name'     => $task['name'],
                'category'      => $task['cat'],
                'is_completed'  => false,
                'due_date'      => now()->addDays(5),
            ]);
        }
    }
}