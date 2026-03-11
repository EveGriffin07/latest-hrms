<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollLineItem;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_salary',
        'allowance_total',
        'ot_total',
        'unpaid_leave_deduction',
        'absent_deduction',
        'late_deduction',
        'penalty_total',
        'adjustment_total',
        'epf_total',
        'tax_total',
        'gross_pay',
        'net_pay',
        'status',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'basic_salary'           => 'decimal:2',
        'allowance_total'        => 'decimal:2',
        'ot_total'               => 'decimal:2',
        'unpaid_leave_deduction' => 'decimal:2',
        'absent_deduction'       => 'decimal:2',
        'late_deduction'         => 'decimal:2',
        'penalty_total'          => 'decimal:2',
        'adjustment_total'       => 'decimal:2',
        'epf_total'              => 'decimal:2',
        'tax_total'              => 'decimal:2',
        'gross_pay'              => 'decimal:2',
        'net_pay'                => 'decimal:2',
        'is_published'           => 'boolean',
        'published_at'           => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id', 'period_id');
    }

    public function lineItems()
    {
        return $this->hasMany(PayrollLineItem::class, 'payroll_run_id');
    }
}
