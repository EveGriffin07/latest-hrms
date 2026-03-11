<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['name', 'supervisor_id'];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    /** Users (employees/supervisors) assigned to this area. */
    public function users()
    {
        return $this->hasMany(User::class, 'area_id');
    }

    /** Employees in this area (users with area_id who have an employee record). */
    public function employees()
    {
        return $this->hasManyThrough(Employee::class, User::class, 'area_id', 'user_id', 'id', 'user_id');
    }

    public function overtimeClaims()
    {
        return $this->hasMany(OvertimeClaim::class, 'area_id');
    }
}
