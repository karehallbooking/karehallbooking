<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'event_id',
        'session_date',
        'session_number',
        'session_count', // kept for backward compatibility, not currently used
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}


