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

    protected static function booted(): void
    {
        static::created(function (Registration $registration) {
            if ($registration->payment_status === 'paid' && $registration->event) {
                $registration->event()->increment('registrations_count');
            }
        });

        static::updated(function (Registration $registration) {
            if ($registration->wasChanged('payment_status') && $registration->event) {
                $original = $registration->getOriginal('payment_status');
                $current = $registration->payment_status;

                if ($original !== 'paid' && $current === 'paid') {
                    $registration->event()->increment('registrations_count');
                } elseif ($original === 'paid' && $current !== 'paid') {
                    $registration->event()
                        ->where('registrations_count', '>', 0)
                        ->decrement('registrations_count');
                }
            }
        });

        static::deleted(function (Registration $registration) {
            if ($registration->payment_status === 'paid' && $registration->event_id) {
                $registration->event()
                    ->where('registrations_count', '>', 0)
                    ->decrement('registrations_count');
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}

