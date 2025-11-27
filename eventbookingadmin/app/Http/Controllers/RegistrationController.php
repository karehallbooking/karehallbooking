<?php

namespace App\Http\Controllers;

use App\Helpers\QRHelper;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderBy('start_date', 'desc')->orderBy('title')->get();
        $registrations = null;
        $selectedEvent = null;

        if ($request->filled('view_event_id')) {
            $eventId = $request->get('view_event_id');
            $selectedEvent = Event::findOrFail($eventId);
            $registrations = Registration::with(['event', 'ticket'])
                ->where('event_id', $eventId)
                ->orderBy('registered_at', 'desc')
                ->paginate(20);
        }

        return view('admin.registrations.index', compact(
            'events',
            'registrations',
            'selectedEvent'
        ));
    }

    public function create()
    {
        return redirect(route('admin.registrations.index') . '#create-registration');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'student_name' => 'required|string|max:255',
            'student_email' => 'required|email|max:255',
            'student_phone' => 'nullable|string|max:20',
            'student_id' => 'nullable|string|max:100',
        ]);

        $registration = Registration::create($validated + [
            'registered_at' => now(),
        ]);
        $qrCode = QRHelper::generate($registration->id, $registration->event_id, $registration->student_email);
        $registration->update(['qr_code' => $qrCode]);

        return redirect()->route('admin.registrations.index', ['view_event_id' => $registration->event_id])->with('success', 'Registration added and QR generated.');
    }

    public function byEvent(Event $event)
    {
        $registrations = $event->registrations()->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.registrations.by-event', compact('event', 'registrations'));
    }

    public function edit(Registration $registration)
    {
        $events = Event::orderBy('title')->get();
        return view('admin.registrations.edit', compact('registration', 'events'));
    }

    public function update(Request $request, Registration $registration)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'student_name' => 'required|string|max:255',
            'student_email' => 'required|email|max:255',
            'student_phone' => 'nullable|string|max:20',
            'student_id' => 'nullable|string|max:100',
            'payment_status' => 'required|in:pending,paid,refunded',
            'attendance_status' => 'required|in:pending,present,absent',
        ]);

        $originalEventId = $registration->event_id;
        $registration->update($validated);

        if ($originalEventId !== (int) $registration->event_id) {
            $registration->event()->increment('registrations_count');
            Event::where('id', $originalEventId)->where('registrations_count', '>', 0)->decrement('registrations_count');
        }

        return redirect()->route('admin.registrations.index', ['view_event_id' => $registration->event_id])->with('success', 'Registration updated.');
    }

    public function markAttendance(Request $request, Registration $registration)
    {
        $request->validate([
            'status' => 'required|in:present,absent',
        ]);

        $registration->update(['attendance_status' => $request->status]);

        return back()->with('success', 'Attendance updated.');
    }

    public function regenerateQr(Registration $registration)
    {
        $qrCode = QRHelper::generate($registration->id, $registration->event_id, $registration->student_email);
        $registration->update(['qr_code' => $qrCode]);

        // If payment is paid, also generate/regenerate ticket
        if ($registration->payment_status === 'paid') {
            $this->generateTicketForRegistration($registration);
        }

        return back()->with('success', 'QR regenerated' . ($registration->payment_status === 'paid' ? ' and ticket generated.' : '.'));
    }

    /**
     * Generate ticket for a single registration
     */
    public function generateTicket(Registration $registration)
    {
        if ($registration->payment_status !== 'paid') {
            return back()->with('error', 'Cannot generate ticket. Payment status must be "paid".');
        }

        try {
            $ticket = $this->generateTicketForRegistration($registration);
            return back()->with('success', 'Ticket generated successfully. Ticket Code: ' . $ticket->ticket_code);
        } catch (\Exception $e) {
            Log::error('Failed to generate ticket', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to generate ticket: ' . $e->getMessage());
        }
    }

    /**
     * Bulk generate tickets for all paid registrations of an event
     */
    public function bulkGenerateTickets(Request $request, Event $event)
    {
        try {
            $registrations = Registration::where('event_id', $event->id)
                ->where('payment_status', 'paid')
                ->get();

            if ($registrations->isEmpty()) {
                return back()->with('error', 'No paid registrations found for this event.');
            }

            $generated = 0;
            $skipped = 0;

            foreach ($registrations as $registration) {
                // Check if ticket already exists
                if ($registration->ticket) {
                    $skipped++;
                    continue;
                }

                try {
                    $this->generateTicketForRegistration($registration);
                    $generated++;
                } catch (\Exception $e) {
                    Log::error('Failed to generate ticket for registration', [
                        'registration_id' => $registration->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $message = "Generated {$generated} ticket(s)";
            if ($skipped > 0) {
                $message .= ", {$skipped} already had tickets";
            }

            return back()->with('success', $message . '.');
        } catch (\Exception $e) {
            Log::error('Bulk ticket generation failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to generate tickets: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to generate ticket for a registration
     */
    protected function generateTicketForRegistration(Registration $registration): Ticket
    {
        // Check if ticket already exists
        $existingTicket = $registration->ticket;
        if ($existingTicket) {
            return $existingTicket;
        }

        return DB::transaction(function () use ($registration) {
            // Generate unique ticket code
            $ticketCode = 'EVT' . $registration->event_id . '-U' . $registration->id . '-' . Str::random(8);

            // Generate QR code if not exists
            if (!$registration->qr_code) {
                $qrCode = QRHelper::generate(
                    $registration->id,
                    $registration->event_id,
                    $registration->student_email
                );
                $registration->update(['qr_code' => $qrCode]);
            }

            // Generate QR image
            $qrSvg = QRHelper::renderSvg($registration->qr_code, 300);

            // Save QR image
            $qrPath = 'tickets/' . $ticketCode . '.svg';
            $qrDir = storage_path('app/tickets');
            if (!is_dir($qrDir)) {
                mkdir($qrDir, 0755, true);
            }
            file_put_contents(storage_path('app/' . $qrPath), $qrSvg);

            // Create ticket
            $ticket = Ticket::create([
                'registration_id' => $registration->id,
                'ticket_code' => $ticketCode,
                'qr_path' => $qrPath,
                'generated_at' => now(),
            ]);

            Log::info('Ticket generated', [
                'ticket_code' => $ticketCode,
                'registration_id' => $registration->id,
            ]);

            return $ticket;
        });
    }

    public function updatePaymentStatus(Request $request, Registration $registration)
    {
        $request->validate([
            'payment_status' => 'required|in:paid,pending,not_required,refunded',
        ]);

        $registration->update([
            'payment_status' => $request->payment_status,
        ]);

        return back()->with('success', 'Payment status updated.');
    }

    public function downloadQr(Registration $registration)
    {
        if (!$registration->qr_code) {
            abort(404);
        }

        $svg = QRHelper::renderSvg($registration->qr_code);

        return Response::make($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-registration-' . $registration->id . '.svg"',
        ]);
    }

    public function exportCsv(Request $request)
    {
        $fileName = 'registrations_' . now()->format('Ymd_His') . '.csv';
        $registrations = Registration::with('event')->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($registrations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Event',
                'Student Name',
                'Email',
                'Phone',
                'Student ID',
                'Payment Status',
                'Attendance Status',
                'Registered At',
            ]);

            foreach ($registrations as $registration) {
                fputcsv($handle, [
                    $registration->id,
                    optional($registration->event)->title,
                    $registration->student_name,
                    $registration->student_email,
                    $registration->student_phone,
                    $registration->student_id,
                    $registration->payment_status,
                    $registration->attendance_status,
                    $registration->created_at,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

