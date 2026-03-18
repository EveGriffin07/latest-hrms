<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $primaryKey = 'training_id';

    // Add ALL columns that you want to save to the database here
    protected $fillable = [
        'department_id',
        'training_name',
        'tr_description',
        'start_date',
        'start_time',       // ADDED
        'end_date',
        'provider',
        'trainer_company',  // ADDED
        'trainer_email',    // ADDED
        'tr_status',
        'mode',
        'max_participants', // ADDED
        'location',
        'qr_token',
    ];

    // --- Relationships ---
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class, 'training_id');
    }
}