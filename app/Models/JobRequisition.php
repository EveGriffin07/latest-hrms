<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRequisition extends Model
{
    use HasFactory;

    // Tell Laravel the primary key is 'requisition_id', not 'id'
    protected $primaryKey = 'requisition_id';

    // Allow these fields to be mass-assigned
    protected $fillable = [
        'department_id',
        'requested_by',
        'job_title',
        'employment_type',
        'headcount',
        'justification',
        'status',
    ];

    // Relationship to the Department
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Relationship to the Employee who requested it (the Manager)
    public function requester()
    {
        return $this->belongsTo(Employee::class, 'requested_by', 'employee_id');
    }
}