<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $primaryKey = 'period_id';

    protected $fillable = [
        'period_month',
        'start_date',
        'end_date',
        'status',
        'locked_at',
        'locked_by',
        'release_note',
        'snapshot',
        'paid_at',
        'paid_by',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'locked_at'    => 'datetime',
        'paid_at'      => 'datetime',
        'published_at' => 'datetime',
        'snapshot'     => 'array',
    ];
}
