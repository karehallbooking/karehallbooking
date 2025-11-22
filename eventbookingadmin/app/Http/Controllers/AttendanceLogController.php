<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderBy('title')->get();
        $query = AttendanceLog::with(['registration', 'event'])->orderBy('scanned_at', 'desc');

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('scanned_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('scanned_at', '<=', $request->to_date);
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.attendance.index', compact('logs', 'events'));
    }

    public function exportCsv(Request $request)
    {
        $fileName = 'attendance_' . now()->format('Ymd_His') . '.csv';
        $query = AttendanceLog::with(['registration', 'event'])->orderBy('scanned_at', 'desc');

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('scanned_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('scanned_at', '<=', $request->to_date);
        }

        $logs = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Registration ID',
                'Student',
                'Event',
                'Scanned At',
                'Scanner IP',
                'Notes',
                'Is Revoked',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->registration_id,
                    optional($log->registration)->student_name,
                    optional($log->event)->title,
                    $log->scanned_at,
                    $log->scanner_ip,
                    $log->notes,
                    $log->is_revoked ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkMarkAbsent(Request $request)
    {
        $request->validate([
            'registration_ids' => 'required|array',
        ]);

        $ids = $request->registration_ids;
        Registration::whereIn('id', $ids)->update(['attendance_status' => 'absent']);

        return back()->with('success', 'Selected registrations marked absent.');
    }
}


