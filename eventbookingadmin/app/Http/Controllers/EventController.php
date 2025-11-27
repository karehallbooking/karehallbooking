<?php

namespace App\Http\Controllers;

use App\Models\Event;
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
            'pricing_type' => 'required|in:free,paid',
            'amount' => 'nullable|numeric|min:0',
            'brochure_pdf' => 'nullable|mimes:pdf|max:10240',
            'attachment_pdf' => 'nullable|mimes:pdf|max:10240',
        ]);

        $payload = collect($validated)->except(['pricing_type', 'brochure_pdf', 'attachment_pdf'])->toArray();
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

        Event::create($payload);
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
        $event = Event::findOrFail($id);
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
            'pricing_type' => 'required|in:free,paid',
            'amount' => 'nullable|numeric|min:0',
            'brochure_pdf' => 'nullable|mimes:pdf|max:10240',
            'attachment_pdf' => 'nullable|mimes:pdf|max:10240',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
        ]);

        $payload = collect($validated)->except(['pricing_type', 'brochure_pdf', 'attachment_pdf'])->toArray();
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

        $event->delete();
        return redirect()->route('admin.events.index', ['view' => 'list'])->with('success', 'Event deleted successfully.');
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

