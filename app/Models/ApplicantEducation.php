<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ApplicantEducation extends Model
{
    // Add this line to override Laravel's grammar guesser
    protected $table = 'applicant_educations'; 

    protected $fillable = [
        'applicant_id', 
        'institution_name', 
        'degree_title', 
        'field_of_study', 
        'start_date', 
        'end_date', 
        'is_current'
    ];

    public function profile()
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_id', 'applicant_id');
    }
}