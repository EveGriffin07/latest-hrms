<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EmployeeFace extends Model
{
    protected $fillable = [
        'employee_id',
        'photo_path',
        'embedding',
        'model_name',
        'model_version',
        'enrollment_quality_score',
        'status',
        'enrolled_at',
    ];

    protected $casts = [
        'model_version' => 'string',
        'enrollment_quality_score' => 'float',
    ];

    /** Do not expose embeddings in API or serialization (privacy). */
    protected $hidden = [
        'embedding',
        'embedding_encrypted',
    ];

    /**
     * Get embedding (decrypted at rest). Supports legacy plain JSON column during migration.
     */
    public function getEmbeddingAttribute($value): ?array
    {
        $encrypted = $this->attributes['embedding_encrypted'] ?? null;
        if ($encrypted !== null && $encrypted !== '') {
            try {
                return json_decode(Crypt::decryptString($encrypted), true);
            } catch (\Throwable) {
                return null;
            }
        }
        if (isset($this->attributes['embedding']) && $this->attributes['embedding'] !== null) {
            return is_string($this->attributes['embedding'])
                ? json_decode($this->attributes['embedding'], true)
                : $this->attributes['embedding'];
        }
        return null;
    }

    /**
     * Set embedding (encrypt at rest). Stores in embedding_encrypted; legacy column not updated.
     */
    public function setEmbeddingAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['embedding_encrypted'] = null;
            return;
        }
        $encoded = is_array($value) ? json_encode($value) : $value;
        $this->attributes['embedding_encrypted'] = Crypt::encryptString($encoded);
        $this->attributes['embedding'] = null; // avoid storing plain in legacy column
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
