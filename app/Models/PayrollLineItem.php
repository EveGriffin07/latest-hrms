<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'item_type',
        'code',
        'source_ref_type',
        'source_ref_id',
        'quantity',
        'rate',
        'amount',
        'description',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate'     => 'decimal:2',
        'amount'   => 'decimal:2',
    ];
}
