<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    public const PROOF_NONE = 'none';
    public const PROOF_OPTIONAL = 'optional';
    public const PROOF_REQUIRED = 'required';

    protected $primaryKey = 'leave_type_id';

    protected $fillable = [
        'leave_name',
        'le_description',
        'default_days_year',
        'proof_requirement',
        'proof_label',
    ];

    public function isProofRequired(): bool
    {
        return ($this->proof_requirement ?? self::PROOF_NONE) === self::PROOF_REQUIRED;
    }

    public function isProofOptional(): bool
    {
        return ($this->proof_requirement ?? self::PROOF_NONE) === self::PROOF_OPTIONAL;
    }

    public function isProofNone(): bool
    {
        return ($this->proof_requirement ?? self::PROOF_NONE) === self::PROOF_NONE;
    }

    public function getProofLabel(): string
    {
        return $this->proof_label ?? 'Supporting document';
    }
}
