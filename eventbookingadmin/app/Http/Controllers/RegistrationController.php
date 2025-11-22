<?php

namespace App\Http\Controllers;

use App\Helpers\QRHelper;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::withCount('registrations')->orderBy('start_date', 'desc')->orderBy('title')->get();
        $registrations = null;
        $selectedEvent = null;

        if ($request->filled('view_event_id')) {
            $eventId = $request->get('view_event_id');
            $selectedEvent = Event::withCount('registrations')->findOrFail($eventId);
            $registrations = Registration::with('event')
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

        return back()->with('success', 'QR regenerated.');
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

