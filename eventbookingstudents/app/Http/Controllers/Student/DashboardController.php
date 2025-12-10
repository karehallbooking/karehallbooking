<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Helpers\StudentTokenHelper;
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
        // Get student token from header/session (college portal integration)
        $studentToken = StudentTokenHelper::getToken($request);
        
        // Fallback: For backward compatibility, still use email-based session if no token
        // This allows existing sessions to continue working until portal integration is complete
        $studentEmail = $request->session()->get('student_email');
        $studentName = $request->session()->get('student_name');
        $studentRoll = $request->session()->get('student_roll');
        
        // If no token and no session, try to get from latest registration (backward compatibility)
        if (!$studentToken && !$studentEmail) {
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
            'student_token' => $studentToken ? 'present' : 'missing',
            'student_email' => $studentEmail,
            'has_session' => $request->session()->has('student_email'),
        ]);

        $selectedSection = $request->get('section', null);

        // Available Events: published or upcoming events with end_date >= today
        $availableEvents = collect();
        if (!$selectedSection || $selectedSection === 'available') {
            $availableEvents = Event::whereIn('status', ['published', 'upcoming'])
                ->whereDate('end_date', '>=', now()->toDateString())
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($event) {
                    $event->seats_remaining = max(0, $event->capacity - $event->registrations_count);
                    return $event;
                });
        }

        // Upcoming: events student registered for with date >= today
        // Filter by student_token if available, otherwise fallback to email (backward compatibility)
        $upcomingRegistrations = collect();
        if (!$selectedSection || $selectedSection === 'upcoming') {
            try {
                $query = Registration::with('event')
                    ->whereHas('event', function ($query) {
                        $query->whereDate('end_date', '>=', now()->toDateString());
                    });
                
                // Filter by token if available (primary method), otherwise by email (backward compatibility)
                if ($studentToken) {
                    $query->where('student_token', $studentToken);
                } elseif ($studentEmail) {
                    $query->where('student_email', $studentEmail);
                } else {
                    // No identifier available - return empty collection
                    $query->whereRaw('1 = 0'); // Force empty result
                }
                
                $upcomingRegistrations = $query->orderBy('registered_at', 'desc')->get();
                    
                \Log::info('Fetched upcoming registrations', [
                    'student_token' => $studentToken ? 'present' : 'missing',
                    'student_email' => $studentEmail,
                    'count' => $upcomingRegistrations->count(),
                    'registration_ids' => $upcomingRegistrations->pluck('id')->toArray(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Error fetching upcoming registrations', [
                    'student_token' => $studentToken,
                    'student_email' => $studentEmail,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // History: past events student registered for
        // Filter by student_token if available, otherwise fallback to email (backward compatibility)
        $historyRegistrations = collect();
        if (!$selectedSection || $selectedSection === 'history') {
            try {
                $query = Registration::with('event')
                    ->whereHas('event', function ($query) {
                        $query->whereDate('end_date', '<', now()->toDateString());
                    });
                
                // Filter by token if available (primary method), otherwise by email (backward compatibility)
                if ($studentToken) {
                    $query->where('student_token', $studentToken);
                } elseif ($studentEmail) {
                    $query->where('student_email', $studentEmail);
                } else {
                    // No identifier available - return empty collection
                    $query->whereRaw('1 = 0'); // Force empty result
                }
                
                $historyRegistrations = $query->orderBy('registered_at', 'desc')->get();
                    
                \Log::info('Fetched history registrations', [
                    'student_token' => $studentToken ? 'present' : 'missing',
                    'student_email' => $studentEmail,
                    'count' => $historyRegistrations->count(),
                    'registration_ids' => $historyRegistrations->pluck('id')->toArray(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Error fetching history registrations', [
                    'student_token' => $studentToken,
                    'student_email' => $studentEmail,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Certificates: certificates issued to this student
        // Filter by student_token if available, otherwise fallback to email (backward compatibility)
        $certificates = collect();
        if (!$selectedSection || $selectedSection === 'certificates') {
            $certificates = Certificate::whereHas('registration', function ($query) use ($studentToken, $studentEmail) {
                    // Filter by token if available (primary method), otherwise by email (backward compatibility)
                    if ($studentToken) {
                        $query->where('student_token', $studentToken);
                    } elseif ($studentEmail) {
                        $query->where('student_email', $studentEmail);
                    } else {
                        // No identifier available - return empty collection
                        $query->whereRaw('1 = 0'); // Force empty result
                    }
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

            $event = Event::findOrFail($id);

            // Check if event is available (published or upcoming)
            if (!in_array($event->status, ['published', 'upcoming'])) {
                return redirect()->back()->with('error', 'This event is not available for registration.');
            }

            // Check if event date is still valid
            if ($event->end_date < now()->toDateString()) {
                return redirect()->back()->with('error', 'Registration for this event has closed.');
            }

            // Get student token from header/session
            $studentToken = StudentTokenHelper::getToken($request);
            
            // Check if already registered - check by token if available, otherwise by email
            $existingRegistration = null;
            if ($studentToken) {
                // Primary check: by token + event (prevents duplicate registration per student)
                $existingRegistration = Registration::where('event_id', $id)
                    ->where('student_token', $studentToken)
                    ->first();
            } else {
                // Fallback: by email (backward compatibility)
                $existingRegistration = Registration::where('event_id', $id)
                    ->where('student_email', $request->student_email)
                    ->first();
            }

            if ($existingRegistration) {
                return redirect()->back()->with('error', 'You are already registered for this event.');
            }

            // Check seats availability
            $seatsRemaining = $event->capacity - $event->registrations_count;
            if ($seatsRemaining <= 0) {
                return redirect()->back()->with('error', 'All seats are booked for this event.');
            }

            $qrCode = null;

            // Create registration with student token
            $registration = Registration::create([
                'event_id' => $event->id,
                'student_name' => $request->student_name,
                'student_email' => $request->student_email,
                'student_phone' => $request->student_phone,
                'student_id' => $request->student_roll,
                'student_token' => $studentToken, // Store token from college portal
                'payment_status' => $event->is_paid ? 'pending' : 'paid', // For free events, mark as paid
                'attendance_status' => 'pending', // Default to pending, will be marked present when scanned
                'registered_at' => now(),
                'certificate_issued' => false,
            ]);

            // Only generate QR code for FREE events (paid events will get QR code after payment)
            if (!$event->is_paid) {
                // Generate QR code data
                $qrData = [
                    'reg_id' => $registration->id,
                    'event_id' => $event->id,
                    'student_email' => $request->student_email,
                    'ts' => now()->timestamp,
                ];
                
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

                // Update registration with QR code (only for free events)
                $registration->qr_code = $qrCode;
                $registration->save();
            }
            
            \Log::info('Registration created successfully', [
                'registration_id' => $registration->id,
                'event_id' => $event->id,
                'student_email' => $registration->student_email,
                'qr_code_length' => $qrCode ? strlen($qrCode) : null,
            ]);

            DB::commit();

            // Store student info in session for future requests (after commit)
            $request->session()->put('student_email', $request->student_email);
            $request->session()->put('student_name', $request->student_name);
            $request->session()->put('student_roll', $request->student_roll);

            if ($event->is_paid) {
                return redirect()->route('events.register', $event->id)
                    ->with('success', 'Registration saved. Please complete the payment to confirm.')
                    ->with('registration_id', $registration->id);
            }

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
        // Get student token from header/session
        $studentToken = StudentTokenHelper::getToken($request);
        
        // Get student info from session (will be empty if not set)
        $studentEmail = $request->session()->get('student_email', '');
        $studentName = $request->session()->get('student_name', '');
        $studentRoll = $request->session()->get('student_roll', '');

        $event = Event::findOrFail($id);
        
        // Check if already registered - check by token if available, otherwise by email
        $existingRegistration = null;
        if ($studentToken) {
            $existingRegistration = Registration::where('event_id', $id)
                ->where('student_token', $studentToken)
                ->first();
        } elseif ($studentEmail) {
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
        
        // For students: only brochure is visible. Approval/attachment is admin-only.
        $pdfCount = 0;
        if($event->brochure_path) $pdfCount++;

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

    public function viewCertificate($id)
    {
        $certificate = Certificate::with(['registration', 'event'])->findOrFail($id);
        
        // Verify student owns this certificate - check by token if available, otherwise by email
        $studentToken = StudentTokenHelper::getToken($request);
        $studentEmail = session('student_email');
        
        $isAuthorized = false;
        if ($studentToken && $certificate->registration->student_token === $studentToken) {
            $isAuthorized = true;
        } elseif ($studentEmail && $certificate->registration->student_email === $studentEmail) {
            $isAuthorized = true;
        }
        
        if (!$isAuthorized) {
            abort(403, 'Unauthorized access to certificate.');
        }
        
        if (!$certificate->file_path) {
            abort(404, 'Certificate file not found.');
        }
        
        // Check if file exists in storage (try both student and admin storage)
        $filePath = storage_path('app/' . $certificate->file_path);
        if (!file_exists($filePath)) {
            // Try admin storage as fallback
            $adminPath = base_path('../eventbookingadmin/storage/app/' . $certificate->file_path);
            if (file_exists($adminPath)) {
                $filePath = $adminPath;
            } else {
                abort(404, 'Certificate file not found.');
            }
        }
        
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificate_' . $certificate->id . '.pdf"',
        ]);
    }
}

