<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'events' => Event::count(),
            'registrations' => Registration::count(),
            'attendance_today' => AttendanceLog::whereDate('scanned_at', today())->count(),
            'payments_total' => Payment::where('status', 'paid')->sum('amount'),
        ];

        $recentEvents = Event::orderBy('start_date', 'desc')->limit(5)->get();

        $filterFrom = $request->get('from');
        $filterTo = $request->get('to');

        $filteredEvents = collect();
        if ($filterFrom || $filterTo) {
            $query = Event::query();
            if ($filterFrom) {
                $query->whereDate('start_date', '>=', $filterFrom);
            }
            if ($filterTo) {
                $query->whereDate('end_date', '<=', $filterTo);
            }
            $filteredEvents = $query->orderBy('start_date')->get();
        }

        return view('admin.reports.index', compact('stats', 'recentEvents', 'filteredEvents', 'filterFrom', 'filterTo'));
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
        $events = Event::orderBy('start_date')->get();
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

    /**
     * Export per-event document links (Excel-friendly CSV with HYPERLINK formulas).
     */
    public function eventsExcel(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $eventId = $request->get('event_id');

        $query = Event::query();

        if ($eventId) {
            $query->where('id', $eventId);
        }
        if ($from) {
            $query->whereDate('start_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('end_date', '<=', $to);
        }

        $events = $query->orderBy('start_date', 'desc')->get();

        $fileName = 'event_documents_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($events) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Event ID',
                'Title',
                'Start Date',
                'End Date',
                'Event Form',
                'Circular',
                'Event Brochure',
                'Event Approval',
                'Event Report',
                'Attendance',
                'Feedback',
                'Sample Certificate',
                'Images (ZIP)',
                'Certificates (ZIP)',
                'All Files (ZIP)',
            ]);

            foreach ($events as $event) {
                // Ensure dates export as plain text to avoid Excel "#######" rendering
                $startDate = $event->start_date ? $event->start_date->format('Y-m-d') : '';
                $endDate   = $event->end_date ? $event->end_date->format('Y-m-d') : '';

                $row = [
                    $event->id,
                    $event->title,
                    $startDate,
                    $endDate,
                ];

        $makeLink = function (?string $url) {
                    if (!$url) {
                        return '';
                    }
                    // Excel-friendly hyperlink formula
                    return '=HYPERLINK("' . $url . '","Download")';
                };

                // Map attributes to secure download URLs
                $eventFormUrl      = $event->completion_event_form_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => 'completion_event_form_path'])
                    : null;
                $circularUrl       = $event->completion_circular_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => 'completion_circular_path'])
                    : null;
                $brochureUrl       = $event->completion_brochure_path ?? $event->attachment_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => $event->completion_brochure_path ? 'completion_brochure_path' : 'attachment_path'])
                    : null;
                $approvalUrl       = $event->brochure_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => 'brochure_path'])
                    : null;
                $reportUrl         = $event->completion_report_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => 'completion_report_path'])
                    : null;
                $attendanceUrl     = $event->completion_attendance_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => 'completion_attendance_path'])
                    : null;
                $feedbackUrl       = $event->completion_feedback_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => 'completion_feedback_path'])
                    : null;
                $sampleCertUrl     = $event->completion_sample_certificate_path
                    ? route('admin.reports.event.file', ['event' => $event->id, 'field' => 'completion_sample_certificate_path'])
                    : null;

                // Images as ZIP (via event ZIP endpoint)
                $imagesZipUrl = $event->completion_image_paths
                    ? route('admin.reports.event.zip', ['event' => $event->id, 'scope' => 'images'])
                    : null;

                // Certificates ZIP (existing certificates route)
                $certsZipUrl = route('admin.certificates.download-all', ['event' => $event->id]);

                // All files ZIP
                $allZipUrl = route('admin.reports.event.zip', ['event' => $event->id]);

                $row = array_merge($row, [
                    $makeLink($eventFormUrl),
                    $makeLink($circularUrl),
                    $makeLink($brochureUrl),
                    $makeLink($approvalUrl),
                    $makeLink($reportUrl),
                    $makeLink($attendanceUrl),
                    $makeLink($feedbackUrl),
                    $makeLink($sampleCertUrl),
                    $makeLink($imagesZipUrl),
                    $makeLink($certsZipUrl),
                    $makeLink($allZipUrl),
                ]);

                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download all event-related documents as a ZIP.
     *
     * @param  Event  $event
     * @param  Request  $request
     */
    public function eventZip(Event $event, Request $request)
    {
        $scope = $request->get('scope'); // null, or 'images'

        $files = [];

        $addFile = function (?string $path, string $name) use (&$files) {
            if (!$path) {
                return;
            }
            $full = $this->resolveStoragePath($path);
            if ($full) {
                $files[] = ['path' => $full, 'name' => $name];
            }
        };

        if ($scope === 'images') {
            if ($event->completion_image_paths) {
                $images = json_decode($event->completion_image_paths, true) ?: [];
                foreach ($images as $idx => $imgPath) {
                    $addFile($imgPath, 'images/image_' . ($idx + 1) . '.' . pathinfo($imgPath, PATHINFO_EXTENSION));
                }
            }
        } else {
            // Pre-event docs
            $addFile($event->brochure_path, 'pre_event/approval_' . $event->id . '.' . pathinfo($event->brochure_path ?? '', PATHINFO_EXTENSION));
            $addFile($event->attachment_path, 'pre_event/brochure_' . $event->id . '.' . pathinfo($event->attachment_path ?? '', PATHINFO_EXTENSION));

            // Completion docs
            $addFile($event->completion_event_form_path, 'post_event/event_form_' . $event->id . '.' . pathinfo($event->completion_event_form_path ?? '', PATHINFO_EXTENSION));
            $addFile($event->completion_circular_path, 'post_event/circular_' . $event->id . '.' . pathinfo($event->completion_circular_path ?? '', PATHINFO_EXTENSION));
            $addFile($event->completion_brochure_path, 'post_event/brochure_' . $event->id . '.' . pathinfo($event->completion_brochure_path ?? '', PATHINFO_EXTENSION));
            $addFile($event->completion_report_path, 'post_event/report_' . $event->id . '.' . pathinfo($event->completion_report_path ?? '', PATHINFO_EXTENSION));
            $addFile($event->completion_attendance_path, 'post_event/attendance_' . $event->id . '.' . pathinfo($event->completion_attendance_path ?? '', PATHINFO_EXTENSION));
            $addFile($event->completion_feedback_path, 'post_event/feedback_' . $event->id . '.' . pathinfo($event->completion_feedback_path ?? '', PATHINFO_EXTENSION));
            $addFile($event->completion_sample_certificate_path, 'post_event/sample_certificate_' . $event->id . '.' . pathinfo($event->completion_sample_certificate_path ?? '', PATHINFO_EXTENSION));

            if ($event->completion_image_paths) {
                $images = json_decode($event->completion_image_paths, true) ?: [];
                foreach ($images as $idx => $imgPath) {
                    $addFile($imgPath, 'images/image_' . ($idx + 1) . '.' . pathinfo($imgPath, PATHINFO_EXTENSION));
                }
            }

            // Certificates
            $certificates = Certificate::where('event_id', $event->id)->get();
            foreach ($certificates as $certificate) {
                if ($certificate->file_path) {
                    $addFile($certificate->file_path, 'certificates/certificate_' . $certificate->id . '.' . pathinfo($certificate->file_path, PATHINFO_EXTENSION));
                }
            }
        }

        if (empty($files)) {
            return back()->with('error', 'No files available to download for this event.');
        }

        $zip = new ZipArchive();
        $zipFileName = storage_path('app/temp/event_' . $event->id . '_files_' . now()->format('Ymd_His') . '.zip');

        if (!is_dir(dirname($zipFileName))) {
            mkdir(dirname($zipFileName), 0755, true);
        }

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to create ZIP archive.');
        }

        foreach ($files as $file) {
            $zip->addFile($file['path'], $file['name']);
        }

        $zip->close();

        return response()->download($zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Download or view a single event file by field name.
     */
    public function downloadEventFile(Event $event, string $field)
    {
        $allowed = [
            'brochure_path',
            'attachment_path',
            'completion_event_form_path',
            'completion_circular_path',
            'completion_brochure_path',
            'completion_report_path',
            'completion_attendance_path',
            'completion_feedback_path',
            'completion_sample_certificate_path',
        ];

        if (!in_array($field, $allowed, true)) {
            abort(404);
        }

        $path = $event->{$field};
        if (!$path) {
            abort(404);
        }

        // Files are stored in two places:
        // - Pre-event uploads (brochure_path, attachment_path) are under storage/app/...
        // - Completion uploads are stored via Storage::disk('local') => storage/app/private/...
        $fullPath = $this->resolveStoragePath($path);
        if (!$fullPath || !is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }

    /**
     * Resolve a file path that may live under storage/app (public-ish) or storage/app/private.
     */
    protected function resolveStoragePath(string $relativePath): ?string
    {
        $candidates = [
            storage_path('app/private/' . ltrim($relativePath, '/')),
            storage_path('app/' . ltrim($relativePath, '/')),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}


