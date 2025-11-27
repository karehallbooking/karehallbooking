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

        $imagickAvailable = extension_loaded('imagick');
        
        return view('admin.certificates.index', compact(
            'events',
            'selectedEvent',
            'selectedEventId',
            'registrations',
            'templateExists',
            'certificates',
            'imagickAvailable'
        ));
    }

    public function uploadTemplate(Request $request, Event $event)
    {
        $request->validate([
            'template_file' => 'nullable|mimes:pdf|max:10240',
            'certificate_text_prefix' => 'nullable|string|max:500',
            'certificate_text_before_date' => 'nullable|string|max:500',
            'certificate_text_after_date' => 'nullable|string|max:500',
        ]);

        $updateData = [];
        $hasChanges = false;

        // Only update template path if a new file is uploaded
        if ($request->hasFile('template_file')) {
            try {
                $file = $request->file('template_file');
                
                if (!$file->isValid()) {
                    return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                        ->with('error', 'File upload failed: ' . $file->getErrorMessage());
                }
                
                $extension = strtolower($file->getClientOriginalExtension());
                
                // Validate extension (PDF only)
                if ($extension !== 'pdf') {
                    return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                        ->with('error', 'Invalid file type. Please upload PDF files only.');
                }
                
                // Check if Imagick is available for PDF processing
                if (!extension_loaded('imagick')) {
                    $phpIniPath = php_ini_loaded_file();
                    return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                        ->with('error', 'Imagick extension is required for PDF templates. The DLL is found but not enabled. Please: 1) Open php.ini as Administrator: ' . $phpIniPath . ' 2) Add line: extension=imagick 3) Install ImageMagick from imagemagick.org 4) Restart server. See ENABLE_IMAGICK_NOW.txt for detailed steps.');
                }
                
                $filename = 'event_' . $event->id . '.' . $extension;
                
                // Ensure directory exists
                $storage = Storage::disk('local');
                if (!$storage->exists('certificates/templates')) {
                    $storage->makeDirectory('certificates/templates');
                }
                
                $path = $file->storeAs(
                    'certificates/templates',
                    $filename
                );

                if (!$path) {
                    return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                        ->with('error', 'Failed to save file. Please check storage permissions.');
                }

                $updateData['certificate_template_path'] = $path;
                $hasChanges = true;
            } catch (\Exception $e) {
                \Log::error('Template upload failed', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                    ->with('error', 'File upload failed: ' . $e->getMessage());
            }
        }

        // Update text fields only if they are provided
        if ($request->filled('certificate_text_prefix')) {
            $updateData['certificate_text_prefix'] = $request->input('certificate_text_prefix');
            $hasChanges = true;
        }
        
        if ($request->filled('certificate_text_before_date')) {
            $updateData['certificate_text_before_date'] = $request->input('certificate_text_before_date');
            $hasChanges = true;
        }
        
        if ($request->filled('certificate_text_after_date')) {
            $updateData['certificate_text_after_date'] = $request->input('certificate_text_after_date');
            $hasChanges = true;
        }

        if ($hasChanges) {
            $event->update($updateData);
            $message = $request->hasFile('template_file') 
                ? 'Certificate template and text saved successfully.' 
                : 'Certificate text saved successfully.';
            
            return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                ->with('success', $message);
        }

        // No changes made
        return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
            ->with('info', 'No changes to save.');
    }

    public function generateForEvent(Event $event)
    {
        if (!$event->certificate_template_path || !Storage::disk('local')->exists($event->certificate_template_path)) {
            return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                ->with('error', 'Please upload a background template before generating certificates.');
        }
        
        // Check if template is PDF and Imagick is required
        $templateExtension = strtolower(pathinfo($event->certificate_template_path, PATHINFO_EXTENSION));
        if ($templateExtension === 'pdf' && !extension_loaded('imagick')) {
            return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                ->with('error', 'Imagick extension is required to process PDF templates. Please install php-imagick extension or upload an image template (PNG/JPG) instead. See installation guide in ENABLE_IMAGICK.md');
        }
        
        // Validate that text fields are set (optional but recommended)
        if (empty($event->certificate_text_prefix) && empty($event->certificate_text_before_date) && empty($event->certificate_text_after_date)) {
            return redirect()->route('admin.certificates.index', ['event_id' => $event->id])
                ->with('warning', 'Certificate text fields are not configured. Certificates will use default text.');
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
        $outputDir = 'certificates/' . $event->id;
        if (!$storage->exists($outputDir)) {
            $storage->makeDirectory($outputDir);
        }

        $filePath = $outputDir . '/' . $registration->id . '.pdf';

        // Get certificate text fields from event
        $textPrefix = $event->certificate_text_prefix ?? 'This is to certify that Mr./Ms.';
        $textBeforeDate = $event->certificate_text_before_date ?? 'has participated in';
        $textAfterDate = $event->certificate_text_after_date ?? 'organized by KARE';

        // Prepare data for certificate
        $data = [
            'STUDENT_NAME' => $registration->student_name,
            'EVENT_DATE' => optional($event->start_date)->format('M d, Y') ?? 'TBA',
        ];

        if ($templatePath && $storage->exists($templatePath)) {
            // Get template file info
            $templateFullPath = $storage->path($templatePath);
            $templateExtension = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));
            
            // Check if required extensions are available
            if (in_array($templateExtension, ['jpg', 'jpeg', 'png']) && !extension_loaded('gd')) {
                throw new \Exception('PHP GD extension is required to process image templates. Please enable the GD extension in your php.ini file. Location: ' . php_ini_loaded_file());
            }
            
            // Prepare template image path for view
            $templateImagePath = null;
            
            if ($templateExtension === 'pdf') {
                // Handle PDF template - convert first page to image using Ghostscript CLI
                try {
                    $relativePngPath = $this->convertPdfTemplateToImage($templateFullPath, $event->id);
                    $templateImagePath = $this->normalizePathForPdf(storage_path('app/' . $relativePngPath));
                    
                    \Log::info('PDF template converted to image', [
                        'registration_id' => $registration->id,
                        'template_path' => $templatePath,
                        'cached_png' => $relativePngPath,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to convert PDF template to image (Ghostscript)', [
                        'registration_id' => $registration->id,
                        'template_path' => $templatePath,
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception('Failed to process PDF template: ' . $e->getMessage());
                }
            } elseif (in_array($templateExtension, ['jpg', 'jpeg', 'png'])) {
                // Read image file
                if (!file_exists($templateFullPath)) {
                    throw new \Exception("Template file not found: {$templateFullPath}");
                }
                
                $imageContent = file_get_contents($templateFullPath);
                if ($imageContent === false) {
                    throw new \Exception("Failed to read template file: {$templateFullPath}");
                }

                $templateImagePath = $this->normalizePathForPdf($templateFullPath);
                
                \Log::info('Certificate template loaded', [
                    'registration_id' => $registration->id,
                    'template_path' => $templatePath,
                    'template_size' => strlen($imageContent),
                ]);
            }

            // Render Blade HTML manually so we can inspect/debug easily
            $viewData = [
                'registration' => $registration,
                'event' => $event,
                'data' => $data,
                'textPrefix' => $textPrefix,
                'textBeforeDate' => $textBeforeDate,
                'textAfterDate' => $textAfterDate,
                'templatePath' => $templatePath,
                'templateImagePath' => $templateImagePath,
                'templateExtension' => $templateExtension,
            ];

            $html = view('admin.certificates.template', $viewData)->render();

            if (config('app.debug')) {
                $debugPath = "certificates/debug/{$registration->id}.html";
                if (!Storage::disk('local')->exists('certificates/debug')) {
                    Storage::disk('local')->makeDirectory('certificates/debug');
                }
                Storage::disk('local')->put($debugPath, $html);
            }

            // Generate PDF using Blade template with template as background
            $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 1080, 720], 'portrait'); // 1080×720px custom size
            
            $pdf->setOption('enable-html5-parser', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('isPhpEnabled', true);
            $pdf->setOption('chroot', storage_path('app'));
            
            try {
                $pdfOutput = $pdf->output();
                
                if (empty($pdfOutput) || strlen($pdfOutput) < 100) {
                    \Log::error('PDF output is empty or too small', [
                        'registration_id' => $registration->id,
                        'output_size' => strlen($pdfOutput),
                        'template_path' => $templatePath,
                'has_template_image' => !empty($templateImagePath),
                    ]);
                    throw new \Exception('Failed to generate PDF. Output is empty. Check logs for details.');
                }
                
                $storage->put($filePath, $pdfOutput);
                
                \Log::info('Certificate PDF generated successfully', [
                    'registration_id' => $registration->id,
                    'file_path' => $filePath,
                    'file_size' => strlen($pdfOutput),
                    'template_extension' => $templateExtension,
                ]);
            } catch (\Exception $e) {
                \Log::error('Certificate generation failed', [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        } else {
            // Fallback: Generate simple certificate without template
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

    /**
     * Ensure Imagick knows where Ghostscript is installed.
     *
     * @return void
     */
    protected function ensureGhostscriptConfigured(): string
    {
        static $cachedPath = null;

        if ($cachedPath && file_exists($cachedPath)) {
            return $cachedPath;
        }

        $ghostscriptPath = $this->resolveGhostscriptPath();

        putenv('MAGICK_GHOSTSCRIPT_PATH=' . $ghostscriptPath);

        $ghostscriptDir = dirname($ghostscriptPath);
        $currentPath = getenv('PATH') ?: '';

        if (stripos($currentPath, $ghostscriptDir) === false) {
            putenv('PATH=' . $ghostscriptDir . PATH_SEPARATOR . $currentPath);
        }

        $cachedPath = $ghostscriptPath;

        return $ghostscriptPath;
    }

    /**
     * Convert the first page of the uploaded PDF into a PNG background.
     */
    protected function convertPdfTemplateToImage(string $pdfPath, int $eventId): string
    {
        $ghostscriptPath = $this->ensureGhostscriptConfigured();

        $relativeDir = 'certificates/templates_cache';
        $absoluteDir = storage_path('app/' . $relativeDir);
        if (!is_dir($absoluteDir)) {
            if (!mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
                throw new \Exception('Unable to create cache directory for converted templates.');
            }
        }

        $relativePath = $relativeDir . '/event_' . $eventId . '.png';
        $outputFile = storage_path('app/' . $relativePath);

        $command = sprintf(
            '"%s" -sstdout=%%stderr -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -sDEVICE=pngalpha -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -r200 -g1080x720 -dPDFFitPage -dFirstPage=1 -dLastPage=1 -sOutputFile="%s" "%s"',
            $ghostscriptPath,
            $outputFile,
            $pdfPath
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($outputFile)) {
            throw new \Exception('Ghostscript conversion failed: ' . implode(PHP_EOL, $output));
        }

        return $relativePath;
    }

    /**
     * Resolve Ghostscript executable path or throw helpful error.
     */
    protected function resolveGhostscriptPath(): string
    {
        $ghostscriptPath = config('services.ghostscript_path');

        if (empty($ghostscriptPath)) {
            throw new \Exception('Ghostscript executable path not configured. Set GHOSTSCRIPT_PATH in the .env file (point it to gswin64c.exe).');
        }

        if (!file_exists($ghostscriptPath)) {
            throw new \Exception("Ghostscript executable not found at {$ghostscriptPath}. Please verify the file exists and update GHOSTSCRIPT_PATH.");
        }

        return $ghostscriptPath;
    }

    /**
     * Convert Windows-style paths to forward slashes for DomPDF.
     */
    protected function normalizePathForPdf(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}

