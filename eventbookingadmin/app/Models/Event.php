<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Event extends Model
{

    protected $fillable = [
        'organizer',
        'department',
        'title',
        'description',
        'venue',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'capacity',
        'is_paid',
        'amount',
        'registrations_count',
        'brochure_path',
        'attachment_path',
        'certificate_template_path',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_paid' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}

