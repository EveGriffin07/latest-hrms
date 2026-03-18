<?php

return [
    /**
     * Payroll release window for previous months only.
     * For a payroll month (e.g. March 2026), release is allowed only from
     * day release_window_start through release_window_end of the following month (e.g. April 1–7).
     * Current and future months have no restriction.
     */
    'payroll_release_window' => [
        'start_day' => 1,  // first day of next month
        'end_day'   => 7,  // last day of window (e.g. 7 = April 1 to April 7)
    ],

    'payroll' => [
        'working_days_per_month'   => 26,
        'daily_rate_divisor'       => 26,
        'epf_employee_rate'        => 0.11,
        'tax_rate'                 => 0.03,
        'late_deduction_mode'      => 'per_minute',
        'late_deduction_per_record' => 10.00,
        'late_deduction_per_minute' => 0.50,
        'fixed_allowance_default'  => 0,
    ],

    'penalties' => [
        // Simple defaults; can be tuned later.
        'late_points'        => 1,
        'absent_points'      => 3,
        'early_leave_points' => 1,
    ],

    'overtime' => [
        'multiplier_weekday' => 1.5,
        'multiplier_weekend' => 2.0,
        'multiplier_holiday' => 3.0,
        'working_hours_per_month' => 160,
        'holidays' => [
            // Add dates as 'Y-m-d', e.g. '2026-01-01', '2026-05-01'
        ],
    ],

    'banks' => [
        'MBB'   => 'Maybank',
        'CIMB'  => 'CIMB',
        'PBB'   => 'Public Bank',
        'RHB'   => 'RHB',
        'HLB'   => 'Hong Leong Bank',
        'AMB'   => 'AmBank',
        'BIMB'  => 'Bank Islam',
        'BSN'   => 'BSN',
        'BKRM'  => 'Bank Rakyat',
        'OCBC'  => 'OCBC',
        'UOB'   => 'UOB',
        'HSBC'  => 'HSBC',
        'SCB'   => 'Standard Chartered',
    ],

    'bank_account_types' => [
        'savings' => 'Savings Account',
        'current' => 'Current Account',
    ],
];
