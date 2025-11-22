<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'events' => Event::count(),
            'registrations' => Registration::count(),
            'attendance_today' => AttendanceLog::whereDate('scanned_at', today())->count(),
            'payments_total' => Payment::where('status', 'paid')->sum('amount'),
        ];

        $recentEvents = Event::orderBy('start_date', 'desc')->limit(5)->get();

        $events = Event::orderBy('title')->get();

        return view('admin.reports.index', compact('stats', 'recentEvents', 'events'));
    }

    public function registrationsCsv()
    {
        return app(RegistrationController::class)->exportCsv(request());
    }

    public function attendanceCsv()
    {
        return app(AttendanceLogController::class)->exportCsv(request());
    }

    public function paymentsCsv()
    {
        return app(PaymentController::class)->exportCsv();
    }

    public function monthlyReport()
    {
        $data = Registration::selectRaw('FORMAT(created_at, \'yyyy-MM\') as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        $pdf = Pdf::loadView('admin.reports.monthly', compact('data'));

        return $pdf->download('monthly-report-' . now()->format('Ymd') . '.pdf');
    }

    public function eventReport(Event $event)
    {
        $event->load(['registrations', 'attendanceLogs', 'payments']);

        $pdf = Pdf::loadView('admin.reports.event', compact('event'));

        return $pdf->download('event-report-' . $event->id . '.pdf');
    }

    public function printSummary()
    {
        $events = Event::withCount('registrations')->orderBy('start_date')->get();
        return view('admin.reports.summary', compact('events'));
    }

    public function exportFullReport(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'event_id' => 'nullable|exists:events,id',
        ]);

        $query = Registration::with('event')
            ->when($request->filled('event_id'), function ($q) use ($request) {
                $q->where('event_id', $request->event_id);
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->whereDate('registered_at', '>=', $request->from);
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->whereDate('registered_at', '<=', $request->to);
            })
            ->orderByDesc('registered_at');

        $fileName = 'event_registrations_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'event_id',
                'event_title',
                'event_date',
                'registration_id',
                'student_name',
                'student_email',
                'roll_no',
                'department',
                'registered_at',
                'payment_status',
                'attendance_status',
                'certificate_issued',
            ]);

            $query->chunk(100, function ($registrations) use ($handle) {
                foreach ($registrations as $registration) {
                    $event = $registration->event;
                    fputcsv($handle, [
                        $event?->id,
                        $event?->title,
                        optional($event?->start_date)->format('Y-m-d'),
                        $registration->id,
                        $registration->student_name,
                        $registration->student_email,
                        $registration->student_id,
                        $event?->department ?? 'N/A',
                        optional($registration->registered_at)->format('Y-m-d H:i:s'),
                        $registration->payment_status,
                        $registration->attendance_status,
                        $registration->certificate_issued ? 'Yes' : 'No',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}


