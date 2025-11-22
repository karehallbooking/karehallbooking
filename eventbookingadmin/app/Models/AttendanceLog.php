<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'registration_id',
        'event_id',
        'scanned_at',
        'scanner_ip',
        'notes',
        'is_revoked',
        'revoked_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

