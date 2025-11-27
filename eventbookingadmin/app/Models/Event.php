<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Event extends Model
{

    protected $fillable = [
        'organizer',
        'department',
        'event_club',
        'event_club_other',
        'title',
        'description',
        'venue',
        'faculty_coordinator_name',
        'faculty_coordinator_contact',
        'student_coordinator_name',
        'student_coordinator_contact',
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
        'certificate_text_prefix',
        'certificate_text_before_date',
        'certificate_text_after_date',
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

