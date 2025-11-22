<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // For now, we'll use a session-based student identifier
        // In production, this would come from authentication
        $studentEmail = $request->session()->get('student_email');
        $studentName = $request->session()->get('student_name');
        $studentRoll = $request->session()->get('student_roll');
        
        // If no session, try to get from latest registration
        if (!$studentEmail) {
            $latestReg = Registration::orderBy('registered_at', 'desc')->first();
            if ($latestReg) {
                $studentEmail = $latestReg->student_email;
                $studentName = $latestReg->student_name;
                $studentRoll = $latestReg->student_id;
                // Store in session for future
                $request->session()->put('student_email', $studentEmail);
                $request->session()->put('student_name', $studentName);
                $request->session()->put('student_roll', $studentRoll);
            }
        }
        
        \Log::info('Dashboard accessed', [
            'student_email' => $studentEmail,
            'has_session' => $request->session()->has('student_email'),
        ]);

        $selectedSection = $request->get('section', null);

        // Available Events: published or upcoming events with end_date >= today
        $availableEvents = collect();
        if (!$selectedSection || $selectedSection === 'available') {
            $availableEvents = Event::whereIn('status', ['published', 'upcoming'])
                ->whereDate('end_date', '>=', now()->toDateString())
                ->withCount('registrations')
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($event) {
                    $event->seats_remaining = max(0, $event->capacity - $event->registrations_count);
                    return $event;
                });
        }

        // Upcoming: events student registered for with date >= today
        $upcomingRegistrations = collect();
        if (!$selectedSection || $selectedSection === 'upcoming') {
            try {
                $upcomingRegistrations = Registration::where('student_email', $studentEmail)
                    ->with('event')
                    ->whereHas('event', function ($query) {
                        $query->whereDate('end_date', '>=', now()->toDateString());
                    })
                    ->orderBy('registered_at', 'desc')
                    ->get();
                    
                \Log::info('Fetched upcoming registrations', [
                    'student_email' => $studentEmail,
                    'count' => $upcomingRegistrations->count(),
                    'registration_ids' => $upcomingRegistrations->pluck('id')->toArray(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Error fetching upcoming registrations', [
                    'student_email' => $studentEmail,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // History: past events student registered for
        $historyRegistrations = collect();
        if (!$selectedSection || $selectedSection === 'history') {
            try {
                $historyRegistrations = Registration::where('student_email', $studentEmail)
                    ->with('event')
                    ->whereHas('event', function ($query) {
                        $query->whereDate('end_date', '<', now()->toDateString());
                    })
                    ->orderBy('registered_at', 'desc')
                    ->get();
                    
                \Log::info('Fetched history registrations', [
                    'student_email' => $studentEmail,
                    'count' => $historyRegistrations->count(),
                    'registration_ids' => $historyRegistrations->pluck('id')->toArray(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Error fetching history registrations', [
                    'student_email' => $studentEmail,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Certificates: certificates issued to this student
        $certificates = collect();
        if (!$selectedSection || $selectedSection === 'certificates') {
            $certificates = Certificate::whereHas('registration', function ($query) use ($studentEmail) {
                    $query->where('student_email', $studentEmail);
                })
                ->where('is_revoked', false)
                ->with(['registration', 'event'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('student.dashboard', compact(
            'availableEvents',
            'upcomingRegistrations',
            'historyRegistrations',
            'certificates',
            'studentEmail',
            'studentName',
            'studentRoll',
            'selectedSection'
        ));
    }

    public function register(Request $request, $id)
    {
        $request->validate([
            'student_name' => 'required|string|max:255',
            'student_email' => 'required|email|max:255',
            'student_roll' => 'required|string|max:50',
            'student_phone' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            $event = Event::withCount('registrations')->findOrFail($id);

            // Check if event is available (published or upcoming)
            if (!in_array($event->status, ['published', 'upcoming'])) {
                return redirect()->back()->with('error', 'This event is not available for registration.');
            }

            // Check if event date is still valid
            if ($event->end_date < now()->toDateString()) {
                return redirect()->back()->with('error', 'Registration for this event has closed.');
            }

            // Check if already registered
            $existingRegistration = Registration::where('event_id', $id)
                ->where('student_email', $request->student_email)
                ->first();

            if ($existingRegistration) {
                return redirect()->back()->with('error', 'You are already registered for this event.');
            }

            // Check seats availability
            $seatsRemaining = $event->capacity - $event->registrations_count;
            if ($seatsRemaining <= 0) {
                return redirect()->back()->with('error', 'All seats are booked for this event.');
            }

            // Generate QR code data
            $qrData = [
                'reg_id' => null, // Will be set after creation
                'event_id' => $event->id,
                'student_email' => $request->student_email,
                'ts' => now()->timestamp,
            ];

            // Create registration
            $registration = Registration::create([
                'event_id' => $event->id,
                'student_name' => $request->student_name,
                'student_email' => $request->student_email,
                'student_phone' => $request->student_phone,
                'student_id' => $request->student_roll,
                'payment_status' => $event->is_paid ? 'pending' : 'paid', // For free events, mark as paid
                'attendance_status' => 'pending', // Default to pending, will be marked present when scanned
                'registered_at' => now(),
                'certificate_issued' => false,
            ]);

            // Update QR data with registration ID
            $qrData['reg_id'] = $registration->id;
            $qrPayload = base64_encode(json_encode($qrData));
            
            // Generate HMAC signature (using QR_SECRET from env - must match admin verification)
            $secret = env('QR_SECRET', 'default-secret-key');
            
            // Remove base64: prefix if present (Laravel encrypted env format)
            if (str_starts_with($secret, 'base64:')) {
                $secret = base64_decode(substr($secret, 7));
            }
            
            if (empty($secret) || $secret === 'default-secret-key') {
                \Log::warning('QR_SECRET is not set or using default value. QR codes may not verify correctly.');
            }
            $signature = hash_hmac('sha256', $qrPayload, $secret);
            $qrCode = $qrPayload . '.' . $signature;

            // Update registration with QR code
            $registration->qr_code = $qrCode;
            $registration->save();
            
            \Log::info('Registration created successfully', [
                'registration_id' => $registration->id,
                'event_id' => $event->id,
                'student_email' => $registration->student_email,
                'qr_code_length' => strlen($qrCode),
            ]);

            DB::commit();

            // Store student info in session for future requests (after commit)
            $request->session()->put('student_email', $request->student_email);
            $request->session()->put('student_name', $request->student_name);
            $request->session()->put('student_roll', $request->student_roll);

            return redirect()->route('student.events.show', $event->id)
                ->with('success', 'Successfully registered for the event! Your QR code has been generated.')
                ->with('registration_id', $registration->id);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration failed: ' . $e->getMessage(), [
                'event_id' => $id,
                'student_email' => $request->student_email,
                'student_name' => $request->student_name,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
            return redirect()->route('student.events.show', $id)->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    public function show($id, Request $request)
    {
        // Get student info from session (will be empty if not set)
        $studentEmail = $request->session()->get('student_email', '');
        $studentName = $request->session()->get('student_name', '');
        $studentRoll = $request->session()->get('student_roll', '');

        $event = Event::withCount('registrations')->findOrFail($id);
        
        // Check if already registered - check by email from session
        $existingRegistration = null;
        if ($studentEmail) {
            $existingRegistration = Registration::where('event_id', $id)
                ->where('student_email', $studentEmail)
                ->first();
        }
        
        // If registration was just created (from success message), reload it
        if (session('registration_id') && !$existingRegistration) {
            $existingRegistration = Registration::find(session('registration_id'));
            if ($existingRegistration && $existingRegistration->event_id == $id) {
                // Update session email if not set
                if (!$studentEmail) {
                    $studentEmail = $existingRegistration->student_email;
                    $studentName = $existingRegistration->student_name;
                    $studentRoll = $existingRegistration->student_id;
                }
            }
        }

        $event->seats_remaining = max(0, $event->capacity - $event->registrations_count);
        
        $pdfCount = 0;
        if($event->brochure_path) $pdfCount++;
        if($event->attachment_path) $pdfCount++;

        return view('student.events.show', compact('event', 'existingRegistration', 'studentEmail', 'studentName', 'studentRoll', 'pdfCount'));
    }

    public function downloadBrochure($id)
    {
        $event = Event::findOrFail($id);
        if (!$event->brochure_path) {
            abort(404, 'Brochure not found');
        }
        
        // Check in student storage first
        $studentPath = storage_path('app/' . $event->brochure_path);
        // Check in admin storage as fallback
        $adminPath = base_path('../eventbookingadmin/storage/app/' . $event->brochure_path);
        
        $filePath = null;
        if (file_exists($studentPath)) {
            $filePath = $studentPath;
        } elseif (file_exists($adminPath)) {
            $filePath = $adminPath;
            // Copy to student storage for future use
            $studentDir = dirname($studentPath);
            if (!is_dir($studentDir)) {
                mkdir($studentDir, 0755, true);
            }
            copy($adminPath, $studentPath);
        } else {
            abort(404, 'PDF file not found');
        }
        
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($event->brochure_path) . '"',
        ]);
    }

    public function downloadAttachment($id)
    {
        $event = Event::findOrFail($id);
        if (!$event->attachment_path) {
            abort(404, 'Attachment not found');
        }
        
        // Check in student storage first
        $studentPath = storage_path('app/' . $event->attachment_path);
        // Check in admin storage as fallback
        $adminPath = base_path('../eventbookingadmin/storage/app/' . $event->attachment_path);
        
        $filePath = null;
        if (file_exists($studentPath)) {
            $filePath = $studentPath;
        } elseif (file_exists($adminPath)) {
            $filePath = $adminPath;
            // Copy to student storage for future use
            $studentDir = dirname($studentPath);
            if (!is_dir($studentDir)) {
                mkdir($studentDir, 0755, true);
            }
            copy($adminPath, $studentPath);
        } else {
            abort(404, 'PDF file not found');
        }
        
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($event->attachment_path) . '"',
        ]);
    }

    public function downloadCertificate($id)
    {
        $certificate = Certificate::findOrFail($id);
        if (!$certificate->file_path) {
            abort(404);
        }
        
        // Check if file exists in storage
        $filePath = storage_path('app/' . $certificate->file_path);
        if (!file_exists($filePath)) {
            abort(404, 'Certificate file not found.');
        }
        
        return response()->download($filePath, basename($certificate->file_path));
    }
}

