<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;
    protected $primaryKey = 'department_id';
    protected $guarded = [];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    /** Department manager (user who approves OT/claims for this dept). */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'user_id');
    }
}
