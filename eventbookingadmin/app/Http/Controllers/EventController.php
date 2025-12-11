<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::orderBy('start_date', 'desc')->paginate(20);
        return view('admin.events.index', compact('events'));
    }

    public function create()
    {
        return redirect(route('admin.events.index') . '#create-event');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_club' => 'required|string|max:255',
            'event_club_other' => 'required_if:event_club,Other|nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'venue' => 'required|string|max:255',
            'faculty_coordinator_name' => 'required|string|max:255',
            'faculty_coordinator_contact' => 'required|string|max:20',
            'student_coordinator_name' => 'required|string|max:255',
            'student_coordinator_contact' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'capacity' => 'required|integer|min:1',
            'attendance_sessions' => 'required|array',
            'attendance_sessions.*' => 'required|integer|min:0|max:10',
            'pricing_type' => 'required|in:free,paid',
            'amount' => 'nullable|numeric|min:0',
            'brochure_pdf' => 'nullable|mimes:pdf|max:10240',
            'attachment_pdf' => 'nullable|mimes:pdf|max:10240',
        ]);

        $totalSessionsInput = collect($validated['attendance_sessions'])->map(fn ($count) => (int) $count)->sum();
        if ($totalSessionsInput < 1) {
            return back()
                ->withErrors(['attendance_sessions' => 'Please set at least one attendance session across the selected dates.'])
                ->withInput();
        }

        $payload = collect($validated)->except(['pricing_type', 'brochure_pdf', 'attachment_pdf', 'attendance_sessions'])->toArray();
        $payload['is_paid'] = $validated['pricing_type'] === 'paid';
        $payload['amount'] = $payload['is_paid'] ? ($validated['amount'] ?? 0) : null;
        // Set organizer field for backward compatibility (use event_club or event_club_other)
        $payload['organizer'] = $validated['event_club'] === 'Other' ? ($validated['event_club_other'] ?? '') : $validated['event_club'];
        if (!isset($payload['status'])) {
            $payload['status'] = 'upcoming';
        }

        if ($request->hasFile('brochure_pdf')) {
            // Ensure directory exists
            $brochureDir = storage_path('app/event_brochures');
            if (!is_dir($brochureDir)) {
                mkdir($brochureDir, 0755, true);
            }
            // Save file directly to storage/app/event_brochures
            $fileName = $request->file('brochure_pdf')->hashName();
            $request->file('brochure_pdf')->move($brochureDir, $fileName);
            $payload['brochure_path'] = 'event_brochures/' . $fileName;
        }

        if ($request->hasFile('attachment_pdf')) {
            // Ensure directory exists
            $attachmentDir = storage_path('app/event_attachments');
            if (!is_dir($attachmentDir)) {
                mkdir($attachmentDir, 0755, true);
            }
            // Save file directly to storage/app/event_attachments
            $fileName = $request->file('attachment_pdf')->hashName();
            $request->file('attachment_pdf')->move($attachmentDir, $fileName);
            $payload['attachment_path'] = 'event_attachments/' . $fileName;
        }

        $event = Event::create($payload);

        // Store date-wise attendance sessions (one row per session per date)
        // and also keep total in events.attendance_sessions for compatibility
        $totalSessions = 0;
        foreach ($validated['attendance_sessions'] as $date => $count) {
            $count = (int) $count;
            for ($i = 1; $i <= $count; $i++) {
                AttendanceSession::create([
                    'event_id'      => $event->id,
                    'session_date'  => $date,
                    'session_number'=> $i,
                ]);
                $totalSessions++;
            }
        }

        $event->attendance_sessions = $totalSessions;
        $event->save();
        return redirect()->route('admin.events.index', ['view' => 'list'])->with('success', 'Event created successfully.');
    }

    public function show($id)
    {
        $event = Event::with(['registrations', 'attendanceLogs'])->findOrFail($id);
        return view('admin.events.show', compact('event'));
    }

    public function downloadBrochure($id)
    {
        $event = Event::findOrFail($id);
        if (!$event->brochure_path || !file_exists(storage_path('app/' . $event->brochure_path))) {
            abort(404);
        }
        return response()->file(storage_path('app/' . $event->brochure_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($event->brochure_path) . '"',
        ]);
    }

    public function downloadAttachment($id)
    {
        $event = Event::findOrFail($id);
        if (!$event->attachment_path || !file_exists(storage_path('app/' . $event->attachment_path))) {
            abort(404);
        }
        return response()->file(storage_path('app/' . $event->attachment_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($event->attachment_path) . '"',
        ]);
    }

    public function edit($id)
    {
        $event = Event::with('attendanceSessions')->findOrFail($id);
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $validated = $request->validate([
            'event_club' => 'required|string|max:255',
            'event_club_other' => 'required_if:event_club,Other|nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'venue' => 'required|string|max:255',
            'faculty_coordinator_name' => 'required|string|max:255',
            'faculty_coordinator_contact' => 'required|string|max:20',
            'student_coordinator_name' => 'required|string|max:255',
            'student_coordinator_contact' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'capacity' => 'required|integer|min:1',
            'attendance_sessions' => 'required|array',
            'attendance_sessions.*' => 'required|integer|min:0|max:10',
            'pricing_type' => 'required|in:free,paid',
            'amount' => 'nullable|numeric|min:0',
            'brochure_pdf' => 'nullable|mimes:pdf|max:10240',
            'attachment_pdf' => 'nullable|mimes:pdf|max:10240',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
        ]);

        $totalSessionsInput = collect($validated['attendance_sessions'])->map(fn ($count) => (int) $count)->sum();
        if ($totalSessionsInput < 1) {
            return back()
                ->withErrors(['attendance_sessions' => 'Please set at least one attendance session across the selected dates.'])
                ->withInput();
        }

        $payload = collect($validated)->except(['pricing_type', 'brochure_pdf', 'attachment_pdf', 'attendance_sessions'])->toArray();
        $payload['is_paid'] = $validated['pricing_type'] === 'paid';
        $payload['amount'] = $payload['is_paid'] ? ($validated['amount'] ?? 0) : null;

        if ($request->hasFile('brochure_pdf')) {
            // Delete old file if exists
            if ($event->brochure_path) {
                $oldPath = storage_path('app/' . $event->brochure_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            // Ensure directory exists
            $brochureDir = storage_path('app/event_brochures');
            if (!is_dir($brochureDir)) {
                mkdir($brochureDir, 0755, true);
            }
            // Save file directly to storage/app/event_brochures
            $fileName = $request->file('brochure_pdf')->hashName();
            $request->file('brochure_pdf')->move($brochureDir, $fileName);
            $payload['brochure_path'] = 'event_brochures/' . $fileName;
        }

        if ($request->hasFile('attachment_pdf')) {
            // Delete old file if exists
            if ($event->attachment_path) {
                $oldPath = storage_path('app/' . $event->attachment_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            // Ensure directory exists
            $attachmentDir = storage_path('app/event_attachments');
            if (!is_dir($attachmentDir)) {
                mkdir($attachmentDir, 0755, true);
            }
            // Save file directly to storage/app/event_attachments
            $fileName = $request->file('attachment_pdf')->hashName();
            $request->file('attachment_pdf')->move($attachmentDir, $fileName);
            $payload['attachment_path'] = 'event_attachments/' . $fileName;
        }

        $event->update($payload);

        // Refresh date-wise attendance sessions
        AttendanceSession::where('event_id', $event->id)->delete();

        $totalSessions = 0;
        foreach ($validated['attendance_sessions'] as $date => $count) {
            $count = (int) $count;
            for ($i = 1; $i <= $count; $i++) {
                AttendanceSession::create([
                    'event_id'      => $event->id,
                    'session_date'  => $date,
                    'session_number'=> $i,
                ]);
                $totalSessions++;
            }
        }

        $event->attendance_sessions = $totalSessions;
        $event->save();
        return redirect()->route('admin.events.index', ['view' => 'list'])->with('success', 'Event updated successfully.');
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        if ($event->brochure_path) {
            Storage::delete($event->brochure_path);
        }
        if ($event->attachment_path) {
            Storage::delete($event->attachment_path);
        }

        // Delete completion files if they exist
        $completionFiles = [
            $event->completion_event_form_path,
            $event->completion_circular_path,
            $event->completion_brochure_path,
            $event->completion_report_path,
            $event->completion_attendance_path,
            $event->completion_feedback_path,
            $event->completion_sample_certificate_path,
        ];
        foreach ($completionFiles as $path) {
            if ($path) {
                Storage::delete($path);
            }
        }

        if ($event->completion_image_paths) {
            $images = json_decode($event->completion_image_paths, true) ?: [];
            foreach ($images as $imagePath) {
                Storage::delete($imagePath);
            }
        }

        $event->delete();
        return redirect()->route('admin.events.index', ['view' => 'list'])->with('success', 'Event deleted successfully.');
    }

    /**
     * Mark an event as completed and upload required completion documents.
     */
    public function complete(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'completion_event_form' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'completion_circular' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
            'completion_brochure' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            'completion_report' => 'required|file|mimes:pdf,doc,docx|max:20480',
            'completion_attendance' => 'required|file|mimes:pdf,xls,xlsx,csv|max:20480',
            'completion_feedback' => 'required|file|mimes:pdf,xls,xlsx,csv|max:20480',
            'completion_sample_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
            // Allow images OR a combined PDF/DOC/DOCX file for event images
            'completion_images.*' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:20480',
        ]);

        $baseDir = 'event_completion/' . $event->id;

        // Helper closure to store a single file
        $storeFile = function ($field, $subDir) use ($request, $baseDir) {
            if ($request->hasFile($field)) {
                return $request->file($field)->store($baseDir . '/' . $subDir);
            }
            return null;
        };

        $update = [];
        $update['completion_event_form_path'] = $storeFile('completion_event_form', 'event_form');
        $update['completion_circular_path'] = $storeFile('completion_circular', 'circular');
        $update['completion_brochure_path'] = $storeFile('completion_brochure', 'brochure');
        $update['completion_report_path'] = $storeFile('completion_report', 'report');
        $update['completion_attendance_path'] = $storeFile('completion_attendance', 'attendance');
        $update['completion_feedback_path'] = $storeFile('completion_feedback', 'feedback');
        $update['completion_sample_certificate_path'] = $storeFile('completion_sample_certificate', 'sample_certificate');

        // Multiple images
        $imagePaths = [];
        if ($request->hasFile('completion_images')) {
            foreach ($request->file('completion_images') as $image) {
                $imagePaths[] = $image->store($baseDir . '/images');
            }
        }
        if (!empty($imagePaths)) {
            $update['completion_image_paths'] = json_encode($imagePaths);
        }

        $update['status'] = 'completed';

        $event->update($update);

        return redirect()->route('admin.events.index', ['view' => 'list'])
            ->with('success', 'Event marked as completed and documents uploaded successfully.');
    }

    public function exportCsv()
    {
        $events = Event::all();
        $filename = 'events_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($events) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Organizer',
                'Department',
                'Title',
                'Venue',
                'Start Date',
                'End Date',
                'Start Time',
                'End Time',
                'Capacity',
                'Is Paid',
                'Amount',
                'Registrations',
                'Status',
            ]);
            
            foreach ($events as $event) {
                fputcsv($file, [
                    $event->id,
                    $event->organizer,
                    $event->department,
                    $event->title,
                    $event->venue,
                    $event->start_date,
                    $event->end_date,
                    $event->start_time,
                    $event->end_time,
                    $event->capacity,
                    $event->is_paid ? 'Yes' : 'No',
                    $event->amount ?? '0.00',
                    $event->registrations_count,
                    $event->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

