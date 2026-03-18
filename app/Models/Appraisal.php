<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    use HasFactory;

    protected $primaryKey = 'appraisal_id';

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'review_period',
        'score_attendance',
        'score_teamwork',
        'score_productivity',
        'score_communication',
        'overall_score',
        'employee_comments',
        'manager_comments',
        'status'
    ];

    // Relationship: The employee being reviewed
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    // Relationship: The manager conducting the review
    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id', 'employee_id');
    }
}