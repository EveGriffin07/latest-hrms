<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ApplicantSkill extends Model
{
    protected $fillable = ['applicant_id', 'skill_name', 'proficiency'];

    public function profile()
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_id', 'applicant_id');
    }
}