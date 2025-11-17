<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewBookingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Hall Booking Request - ' . $this->event->hall_name)
                    ->view('emails.new-booking-notification')
                    ->with([
                        'event' => $this->event,
                        'organizer' => [
                            'name' => $this->event->organizer_name,
                            'email' => $this->event->organizer_email,
                            'phone' => $this->event->organizer_phone,
                            'department' => $this->event->organizer_department,
                            'designation' => $this->event->organizer_designation,
                        ],
                        'booking' => [
                            'hall_name' => $this->event->hall_name,
                            'event_date' => $this->event->event_date->format('F d, Y'),
                            'time_from' => $this->event->time_from,
                            'time_to' => $this->event->time_to,
                            'purpose' => $this->event->purpose,
                            'seating_capacity' => $this->event->seating_capacity,
                            'facilities_required' => $this->event->facilities_required ?? [],
                        ],
                    ]);
    }
}

