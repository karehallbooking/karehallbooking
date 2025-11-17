<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'events';

    protected $fillable = [
        'hall_name',
        'event_date',
        'event_date_checkout',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'organizer_department',
        'organizer_designation',
        'purpose',
        'seating_capacity',
        'facilities_required',
        'time_from',
        'time_to',
        'status',
        'admin_comments',
        'approved_by',
        'rejected_by',
        'created_by',
        'updated_by',
        'event_brochure_path',
        'approval_letter_path',
    ];

    protected $casts = [
        'event_date' => 'date',
        'facilities_required' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Available statuses
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected'
        ];
    }

    // Scope for filtering by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope for filtering by hall
    public function scopeByHall($query, $hallName)
    {
        return $query->where('hall_name', $hallName);
    }

    // Scope for filtering by date range
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    // Scope for upcoming events
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }

    // Scope for past events
    public function scopePast($query)
    {
        return $query->where('event_date', '<', now()->toDateString());
    }

    // Check if event is pending
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    // Check if event is approved
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    // Check if event is rejected
    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    // Get status badge class for UI
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_PENDING => 'badge-warning',
            default => 'badge-secondary'
        };
    }

    // Get formatted event date
    public function getFormattedEventDateAttribute()
    {
        return $this->event_date->format('M d, Y');
    }

    // Get formatted time range
    public function getFormattedTimeRangeAttribute()
    {
        return $this->time_from . ' - ' . $this->time_to;
    }

    // Check if event is today
    public function isToday()
    {
        return $this->event_date->isToday();
    }

    // Check if event is in the past
    public function isPast()
    {
        return $this->event_date->isPast();
    }

    // Check if event is in the future
    public function isFuture()
    {
        return $this->event_date->isFuture();
    }

    // Get event duration in hours
    public function getDurationInHours()
    {
        // Handles SQL Server times with possible microseconds (e.g. 09:00:00.0000000)
        $start = \Carbon\Carbon::createFromFormat('H:i:s', substr($this->time_from, 0, 8));
        $end = \Carbon\Carbon::createFromFormat('H:i:s', substr($this->time_to, 0, 8));
        return $end->diffInHours($start);
    }

    // Validation rules for creating events
    public static function getCreateRules()
    {
        return [
            'hall_name' => 'required|string|max:255',
            'event_date' => 'required|date|after:today',
            'organizer_name' => 'required|string|max:255',
            'organizer_email' => 'required|email|max:255',
            'organizer_phone' => 'required|string|max:20',
            'organizer_department' => 'required|string|max:255',
            'organizer_designation' => 'nullable|string|max:255',
            'purpose' => 'required|string|max:1000',
            'seating_capacity' => 'required|integer|min:1|max:10000',
            'facilities_required' => 'nullable|array',
            'facilities_required.*' => 'string|max:255',
            'time_from' => 'required|date_format:H:i',
            'time_to' => 'required|date_format:H:i', // removed after:time_from
            'status' => 'nullable|in:' . implode(',', array_keys(self::getStatuses()))
        ];
    }

    // Validation rules for updating events
    public static function getUpdateRules()
    {
        return [
            'hall_name' => 'sometimes|required|string|max:255',
            'event_date' => 'sometimes|required|date',
            'organizer_name' => 'sometimes|required|string|max:255',
            'organizer_email' => 'sometimes|required|email|max:255',
            'organizer_phone' => 'sometimes|required|string|max:20',
            'organizer_department' => 'sometimes|required|string|max:255',
            'organizer_designation' => 'nullable|string|max:255',
            'purpose' => 'sometimes|required|string|max:1000',
            'seating_capacity' => 'sometimes|required|integer|min:1|max:10000',
            'facilities_required' => 'nullable|array',
            'facilities_required.*' => 'string|max:255',
            'time_from' => 'sometimes|required|date_format:H:i',
            'time_to' => 'sometimes|required|date_format:H:i|after:time_from',
            'status' => 'sometimes|required|in:' . implode(',', array_keys(self::getStatuses())),
            'admin_comments' => 'nullable|string|max:1000'
        ];
    }

    // Check for booking conflicts
    public static function checkConflicts($hallName, $eventDate, $timeFrom, $timeTo, $excludeId = null)
    {
        $query = self::where('hall_name', $hallName)
            ->where('event_date', $eventDate)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflicts = $query->get()->filter(function ($event) use ($timeFrom, $timeTo) {
            return self::timesOverlap($timeFrom, $timeTo, $event->time_from, $event->time_to);
        });

        return $conflicts;
    }

    // Check if two time ranges overlap
    private static function timesOverlap($start1, $end1, $start2, $end2)
    {
        $start1Minutes = self::timeToMinutes($start1);
        $end1Minutes = self::timeToMinutes($end1);
        $start2Minutes = self::timeToMinutes($start2);
        $end2Minutes = self::timeToMinutes($end2);

        return $start1Minutes < $end2Minutes && $start2Minutes < $end1Minutes;
    }

    // Convert time string to minutes
    private static function timeToMinutes($time)
    {
        list($hours, $minutes) = explode(':', $time);
        return $hours * 60 + $minutes;
    }

    // Get available halls (you can customize this based on your halls)
    public static function getAvailableHalls()
    {
        return [
            'Main Auditorium' => 'Main Auditorium',
            'Conference Hall A' => 'Conference Hall A',
            'Conference Hall B' => 'Conference Hall B',
            'Seminar Room 1' => 'Seminar Room 1',
            'Seminar Room 2' => 'Seminar Room 2',
            'Meeting Room' => 'Meeting Room',
            'Lecture Hall 1' => 'Lecture Hall 1',
            'Lecture Hall 2' => 'Lecture Hall 2'
        ];
    }

    // Get available facilities
    public static function getAvailableFacilities()
    {
        return [
            'Projector',
            'Sound System',
            'Microphone',
            'Whiteboard',
            'Air Conditioning',
            'WiFi',
            'Stage',
            'Lighting',
            'Video Conferencing',
            'Catering Setup',
            'Parking',
            'Security'
        ];
    }
}

