<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderBy('start_date')->orderBy('title')->get();
        $selectedEventId = $request->get('event_id');
        $selectedEvent = $selectedEventId ? Event::find($selectedEventId) : null;

        $registrations = collect();
        $templateExists = false;
        $certificates = collect();

        if ($selectedEvent) {
            $registrations = Registration::with(['event', 'certificate'])
                ->where('event_id', $selectedEvent->id)
                ->where('attendance_status', 'present')
                ->orderBy('student_name')
                ->paginate(20)
                ->withQueryString();

            $templateExists = $selectedEvent->certificate_template_path
                && Storage::disk('local')->exists($selectedEvent->certificate_template_path);

            $certificates = Certificate::with(['registration'])
                ->where('event_id', $selectedEvent->id)
                ->latest()
                ->get();
        }

        return view('admin.certificates.index', compact(
            'events',
            'selectedEvent',
            'selectedEventId',
            'registrations',
            'templateExists',
            'certificates'
        ));
    }

    public function uploadTemplate(Request $request, Event $event)
    {
        $request->validate([
            'template_pdf' => 'required|mimes:pdf|max:5120',
        ]);

        $path = $request->file('template_pdf')->storeAs(
            'certificates/templates',
            'event_' . $event->id . '.pdf'
        );

        $event->update(['certificate_template_path' => $path]);

        return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
            ->with('success', 'Template uploaded successfully.');
    }

    public function generateForEvent(Event $event)
    {
        if (!$event->certificate_template_path || !Storage::disk('local')->exists($event->certificate_template_path)) {
            return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                ->with('error', 'Please upload a template before generating certificates.');
        }

        $registrations = Registration::with(['event', 'certificate'])
            ->where('event_id', $event->id)
            ->where('attendance_status', 'present')
            ->get();

        if ($registrations->isEmpty()) {
            return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                ->with('error', 'No attendees marked present for this event.');
        }

        $count = 0;
        foreach ($registrations as $registration) {
            $this->generateCertificateForRegistration($registration, $event);
            $count++;
        }

        return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
            ->with('success', "{$count} certificates generated.");
    }

    public function downloadAll(Event $event)
    {
        $certificates = Certificate::where('event_id', $event->id)
            ->where('is_revoked', false)
            ->get();

        if ($certificates->isEmpty()) {
            return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                ->with('error', 'No certificates available to download.');
        }

        $zipFile = storage_path('app/certificates/tmp/event_' . $event->id . '_certificates.zip');
        if (!is_dir(dirname($zipFile))) {
            mkdir(dirname($zipFile), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to create ZIP archive.');
        }

        foreach ($certificates as $certificate) {
            if (Storage::disk('local')->exists($certificate->file_path)) {
                $zip->addFile(
                    Storage::disk('local')->path($certificate->file_path),
                    basename($certificate->file_path)
                );
            }
        }

        $zip->close();

        return response()->download($zipFile)->deleteFileAfterSend(true);
    }

    public function download(Certificate $certificate)
    {
        if (!Storage::disk('local')->exists($certificate->file_path)) {
            abort(404);
        }

        return Storage::download($certificate->file_path);
    }

    public function revoke(Certificate $certificate)
    {
        $certificate->update([
            'is_revoked' => true,
            'revoked_at' => now(),
        ]);

        if ($certificate->registration) {
            $certificate->registration->update([
                'certificate_issued' => false,
                'certificate_issued_at' => null,
            ]);
        }

        if (Storage::disk('local')->exists($certificate->file_path)) {
            Storage::disk('local')->delete($certificate->file_path);
        }

        return back()->with('success', 'Certificate revoked.');
    }

    protected function generateCertificateForRegistration(Registration $registration, Event $event): Certificate
    {
        $storage = Storage::disk('local');

        $templatePath = $event->certificate_template_path;
        $outputDir = 'certificates/generated/event_' . $event->id;
        if (!$storage->exists($outputDir)) {
            $storage->makeDirectory($outputDir);
        }

        $placeholders = [
            '{STUDENT_NAME}' => $registration->student_name,
            '{EVENT_NAME}' => $event->title,
            '{EVENT_DATE}' => optional($event->start_date)->format('M d, Y'),
        ];

        if ($templatePath && $storage->exists($templatePath)) {
            $templateContent = $storage->get($templatePath);
            $filledContent = str_replace(array_keys($placeholders), array_values($placeholders), $templateContent);
            $filePath = $outputDir . '/certificate_' . $registration->id . '.pdf';
            $storage->put($filePath, $filledContent);
        } else {
            $bodyTemplate = Setting::get('certificate_template', 'This is to certify that {{name}} attended {{event}} on {{date}}.');
            $body = str_replace(
                ['{{name}}', '{{event}}', '{{date}}'],
                [$registration->student_name, $event->title, optional($event->start_date)->format('M d, Y')],
                $bodyTemplate
            );

            $pdf = Pdf::loadView('admin.certificates.pdf', [
                'registration' => $registration,
                'event' => $event,
                'body' => $body,
            ]);

            $filePath = $outputDir . '/certificate_' . $registration->id . '.pdf';
            $storage->put($filePath, $pdf->output());
        }

        $registration->update([
            'certificate_issued' => true,
            'certificate_issued_at' => now(),
            'certificate_path' => $filePath,
        ]);

        return Certificate::updateOrCreate(
            ['registration_id' => $registration->id],
            [
                'event_id' => $registration->event_id,
                'file_path' => $filePath,
                'is_revoked' => false,
                'revoked_at' => null,
            ]
        );
    }
}

