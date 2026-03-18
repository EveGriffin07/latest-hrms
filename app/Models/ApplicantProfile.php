<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantProfile extends Model
{
    use HasFactory;

    // Specify the primary key if it's not 'id'
    protected $primaryKey = 'applicant_id';

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone',
        'location',
        'resume_path',
        'avatar_path',
        'linkedin_url',
        'portfolio_url',
        
        // --- New Professional Fields ---
        'personal_summary',
        'career_history',
        'education_details',
        'licenses_certifications',
        'skills',
        'languages',
        
        // --- Malaysian Address Fields ---
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postcode',
        
        // --- Preferences ---
        'industry_interest',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the applications associated with this profile.
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'applicant_id', 'applicant_id');
    }

    // Add these inside the ApplicantProfile class
    public function educations()
    {
        return $this->hasMany(ApplicantEducation::class, 'applicant_id', 'applicant_id')->orderBy('start_date', 'desc');
    }

    public function experiences()
    {
        return $this->hasMany(ApplicantExperience::class, 'applicant_id', 'applicant_id')->orderBy('start_date', 'desc');
    }

    public function skills()
    {
        return $this->hasMany(ApplicantSkill::class, 'applicant_id', 'applicant_id');
    }

    public function languages()
    {
        return $this->hasMany(ApplicantLanguage::class, 'applicant_id', 'applicant_id');
    }
}