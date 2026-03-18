<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ApplicantExperience extends Model
{
    protected $fillable = ['applicant_id', 'job_title', 'company_name', 'start_date', 'end_date', 'is_current', 'description'];

    public function profile()
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_id', 'applicant_id');
    }
}