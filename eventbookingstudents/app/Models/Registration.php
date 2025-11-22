<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'event_id',
        'student_name',
        'student_email',
        'student_phone',
        'student_id',
        'qr_code',
        'payment_status',
        'attendance_status',
        'registered_at',
        'certificate_path',
        'certificate_issued',
        'certificate_issued_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'certificate_issued' => 'boolean',
        'certificate_issued_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }
}

