<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $primaryKey = 'payslip_id';

    protected $fillable = [
        'employee_id', 'period_id', 'payroll_run_id', 'period_month',
        'basic_salary', 'total_allowances', 'total_deductions', 'total_overtime_amount',
        'net_salary', 'generated_at', 'published_at', 'publish_version',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /** Gross = basic + allowances (for display). */
    public function getGrossAttribute(): float
    {
        return (float) $this->basic_salary + (float) $this->total_allowances;
    }
}
