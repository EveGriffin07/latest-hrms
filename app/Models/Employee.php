<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;
    protected $primaryKey = 'employee_id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** Prevent mass-assignment of PK; let DB AUTO_INCREMENT handle it. */
    protected $guarded = ['employee_id'];

    protected $casts = [
        'service_years' => 'integer',
        'face_enrolled' => 'boolean',
        'face_enrolled_at' => 'datetime',
    ];

    // Relationship: Belongs to Department
    public function department() {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // Relationship: Belongs to Position
    public function position() {
        return $this->belongsTo(Position::class, 'position_id');
    }

    // Relationship: Belongs to User (Login account)
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Supervisor (user_id who reviews this employee's OT/leave etc.)
   // Supervisor (Points to the Employee table now!)
    public function supervisor() {
        return $this->belongsTo(Employee::class, 'supervisor_id', 'employee_id');
    }

    // Subordinates (employees who have this user as supervisor)
    public function subordinates() {
        return $this->hasMany(Employee::class, 'supervisor_id', 'employee_id');
    }

    public function overtimeClaims() {
        return $this->hasMany(OvertimeClaim::class, 'employee_id', 'employee_id');
    }

    // Relationship: One Employee has many Attendance records
    public function attendance() {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    // Add this relationship method
    public function onboarding()
    {
        // One Employee has One Onboarding record
        return $this->hasOne(Onboarding::class, 'employee_id', 'employee_id');
    }

    public function employeeKpis()
    {
        // Links to the EmployeeKpi model using 'employee_id'
        return $this->hasMany(EmployeeKpi::class, 'employee_id', 'employee_id');
    }

    public function faceData()
    {
        return $this->hasOne(EmployeeFace::class, 'employee_id', 'employee_id');
    }

    public function faceTemplates()
    {
        return $this->hasMany(EmployeeFaceTemplate::class, 'employee_id', 'employee_id');
    }

    public function faceEnrollmentSessions()
    {
        return $this->hasMany(FaceEnrollmentSession::class, 'employee_id', 'employee_id');
    }

    public function attendanceScanSessions()
    {
        return $this->hasMany(AttendanceScanSession::class, 'employee_id', 'employee_id');
    }

    /**
     * Recompute and persist service years/band based on hire_date and today.
     */
    public function recomputeServiceBand(): void
    {
        if (!$this->hire_date) {
            return;
        }
        $hire = \Illuminate\Support\Carbon::parse($this->hire_date)->startOfDay();
        $today = \Illuminate\Support\Carbon::today();
        $serviceDays = $hire->diffInDays($today);
        $serviceYears = (int) floor($serviceDays / 365);

        $band = 'BAND_A';
        if ($serviceYears < 2) {
            $band = 'BAND_A'; // New / <2 years
        } elseif ($serviceYears <= 5) {
            $band = 'BAND_B'; // Mid / 2–5 years
        } else {
            $band = 'BAND_C'; // Senior / >5 years
        }

        $dirty = false;
        if ($this->service_years !== $serviceYears) {
            $this->service_years = $serviceYears;
            $dirty = true;
        }
        if ($this->service_band !== $band) {
            $this->service_band = $band;
            $dirty = true;
        }

        // ✨ THE FIX: Only call saveQuietly if the model already exists in the database!
        // If it doesn't exist yet, Laravel is already in the middle of inserting it 
        // and will automatically save these new attributes for us without double-inserting.
        if ($dirty && $this->exists) {
            $this->saveQuietly();
        }
    }

    /**
    * Snapshot of service metrics for display.
    */
    public function serviceSnapshot(): array
    {
        if (!$this->hire_date) {
            return [
                'years'   => 0,
                'months'  => 0,
                'days'    => 0,
                'band'    => 'BAND_A',
                'label'   => 'New Staff (<2 years)',
                'inactive'=> $this->employee_status !== 'active',
                'status_label' => ucfirst($this->employee_status ?? 'inactive'),
            ];
        }

        $today = \Illuminate\Support\Carbon::today();
        $endDate = $today;
        if (($this->employee_status ?? 'active') !== 'active' && !empty($this->end_date)) {
            $endDate = \Illuminate\Support\Carbon::parse($this->end_date)->startOfDay();
        }

        $hire = \Illuminate\Support\Carbon::parse($this->hire_date)->startOfDay();
        $serviceDays = max($hire->diffInDays($endDate), 0);
        $years = (int) floor($serviceDays / 365);
        $months = (int) floor($serviceDays / 30);

        $band = 'BAND_A';
        $label = 'New Staff (<2 years)';
        if ($years < 2) {
            $band = 'BAND_A';
            $label = 'New Staff (<2 years)';
        } elseif ($years <= 5) {
            $band = 'BAND_B';
            $label = 'Established (2–5 years)';
        } else {
            $band = 'BAND_C';
            $label = 'Senior (>5 years)';
        }

        $inactive = ($this->employee_status ?? 'active') !== 'active';
        $statusLabel = $inactive ? ucfirst($this->employee_status) : $label;

        return [
            'years'        => $years,
            'months'       => $months,
            'days'         => $serviceDays,
            'band'         => $band,
            'label'        => $label,
            'inactive'     => $inactive,
            'status_label' => $statusLabel,
        ];
    }

    /**
     * Hook into model events to set service band on create.
     */
    protected static function booted()
    {
        static::creating(function (Employee $employee) {
            $employee->recomputeServiceBand();
        });
    }

    /** Bank display name: from config by bank_code, or stored bank_name. */
    public function getBankDisplayName(): ?string
    {
        if ($this->bank_code && config('hrms.banks')) {
            return config('hrms.banks')[$this->bank_code] ?? $this->bank_name;
        }
        return $this->bank_name;
    }

    /** Masked account number for list/display (e.g. ****1234). */
    public function getMaskedAccountNumber(?int $visibleLast = 4): ?string
    {
        $num = $this->bank_account_number;
        if ($num === null || $num === '') {
            return null;
        }
        $num = preg_replace('/\s+/', '', (string) $num);
        if ($num === '') {
            return null;
        }
        $len = strlen($num);
        if ($visibleLast >= $len) {
            return $num;
        }
        return str_repeat('*', $len - $visibleLast) . substr($num, -$visibleLast);
    }

    /** Account type label for display. */
    public function getAccountTypeLabel(): ?string
    {
        $type = $this->account_type ?? null;
        if (!$type) {
            return null;
        }
        return config('hrms.bank_account_types')[$type] ?? ucfirst($type);
    }
}