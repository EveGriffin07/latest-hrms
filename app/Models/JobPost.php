<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPost extends Model
{
    use HasFactory;

    protected $primaryKey = 'job_id'; // Important: Matches migration

    protected $fillable = [
        'requisition_id', // <-- ADDED THIS so it links to the manager's request
        'job_title',
        'job_type',
        'department',
        'location',
        'salary_range',
        'job_description',
        'requirements',
        'closing_date',
        'job_status',
        'posted_by',
    ];

    protected $casts = [
        'closing_date' => 'date',
    ];

    // Relationship to Admin who posted it
    public function recruiter()
    {
        return $this->belongsTo(User::class, 'posted_by', 'user_id');
    }
    
    // Relationship to Applications
    public function applications()
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    // ==========================================
    // NEW: Relationship to the Job Requisition
    // ==========================================
    public function requisition()
    {
        return $this->belongsTo(JobRequisition::class, 'requisition_id', 'requisition_id');
    }

    public function getJobCodeAttribute()
    {
        return 'JOB-' . str_pad($this->job_id, 3, '0', STR_PAD_LEFT);
    }
}