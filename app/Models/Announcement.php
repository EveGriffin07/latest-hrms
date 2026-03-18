<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    // DEFINING THE CUSTOM PRIMARY KEY
    protected $primaryKey = 'announcement_id';

    protected $fillable = [
        'title',
        'content',       // Mapped from 'message'
        'audience_type', // Mapped from 'audience'
        'publish_at',
        'priority',
        'department',
        'expires_at',
        'remarks',
        'posted_by',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'expires_at' => 'date',
    ];

    public function getAnnouncementCodeAttribute()
    {
        return 'ANN-' . str_pad($this->announcement_id, 3, '0', STR_PAD_LEFT);
    }
}