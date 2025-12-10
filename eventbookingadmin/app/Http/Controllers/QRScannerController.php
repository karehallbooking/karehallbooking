<?php

namespace App\Http\Controllers;

use App\Helpers\QRHelper;
use App\Models\AttendanceLog;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QRScannerController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderBy('start_date', 'desc')->orderBy('title')->get();
        $selectedEventId = $request->get('event_id');
        $selectedEvent = null;
        $students = collect();
        $result = session('scanner_result');
        $currentSession = $request->has('session') ? (int) $request->get('session') : null;
        $today = Carbon::today();
        $todaySessions = collect();

        if ($selectedEventId) {
            $selectedEvent = Event::with('attendanceSessions')->findOrFail($selectedEventId);

            // Sessions allowed only on today's date
            $todaySessions = $selectedEvent->attendanceSessions()
                ->whereDate('session_date', $today->toDateString())
                ->orderBy('session_number')
                ->get();

            $validSessionNumbers = $todaySessions->pluck('session_number')->all();

            if (empty($validSessionNumbers)) {
                $currentSession = null;
            } else {
                if (!$currentSession || !in_array($currentSession, $validSessionNumbers, true)) {
                    $currentSession = (int) $validSessionNumbers[0];
                }
            }
            
            // Get students with their session attendance details
            $students = Registration::with(['event', 'attendanceLogs' => function($query) use ($selectedEventId) {
                $query->where('event_id', $selectedEventId)
                      ->where('is_revoked', false);
            }])
                ->where('event_id', $selectedEventId)
                ->select('id', 'student_name', 'student_email', 'student_id', 'attendance_status', 'registered_at')
                ->orderBy('attendance_status', 'desc')
                ->orderBy('student_name')
                ->get()
                ->map(function($student) use ($selectedEvent) {
                    $attendedSessions = $student->attendanceLogs->pluck('session_number')->unique()->sort()->values()->toArray();
                    $student->attended_sessions = $attendedSessions;
                    $student->sessions_completed = count($attendedSessions);
                    $student->all_sessions_completed = count($attendedSessions) === $selectedEvent->attendance_sessions;
                    return $student;
                });
        }

        return view('admin.scanner.index', compact('events', 'selectedEvent', 'students', 'result', 'selectedEventId', 'currentSession', 'todaySessions', 'today'));
    }

    public function scan(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'required|exists:events,id',
            'qr_value' => 'required|string',
            'session_number' => 'required|integer|min:1',
        ]);

        $event = Event::findOrFail($data['event_id']);
        $sessionNumber = (int) $data['session_number'];

        // Enforce date-wise attendance: only allow if today's date matches session_date
        $today = Carbon::today();
        $session = AttendanceSession::where('event_id', $event->id)
            ->where('session_number', $sessionNumber)
            ->whereDate('session_date', $today->toDateString())
            ->first();

        if (!$session) {
            return redirect()->route('admin.scanner.index', ['event_id' => $event->id])
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => 'Attendance is allowed only on the scheduled event date.',
                ]);
        }
        
        // Trim whitespace from QR value
        $qrValue = trim($data['qr_value']);
        
        // Log the received QR value for debugging (first 50 chars only)
        \Log::info('QR Scan Attempt', [
            'event_id' => $event->id,
            'qr_length' => strlen($qrValue),
            'qr_preview' => substr($qrValue, 0, 50) . '...',
            'has_dot' => strpos($qrValue, '.') !== false,
        ]);
        
        // Validate QR format (should be base64.signature)
        if (empty($qrValue) || strpos($qrValue, '.') === false) {
            return redirect()->route('admin.scanner.index', ['event_id' => $event->id])
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => 'Invalid QR code format. Please ensure you scanned a valid QR code. Expected format: base64.signature',
                ]);
        }
        
        $payload = QRHelper::verify($qrValue);

        if (!$payload) {
            // Try to get more details about why verification failed
            $parts = explode('.', $qrValue);
            $errorDetails = [];
            
            if (count($parts) === 2) {
                $payloadBase64 = $parts[0];
                $signature = $parts[1];
                
                // Check secret key (must match QRHelper logic - env() first)
                $envSecret = env('QR_SECRET', 'default-secret-key');
                // Remove base64: prefix if present
                if (str_starts_with($envSecret, 'base64:')) {
                    $envSecret = base64_decode(substr($envSecret, 7));
                }
                $settingSecret = \App\Models\Setting::get('qr_secret_key', null);
                
                $errorDetails[] = 'Secret key from env: ' . (empty($envSecret) ? 'EMPTY' : 'SET (' . strlen($envSecret) . ' chars)');
                $errorDetails[] = 'Secret key from settings: ' . (empty($settingSecret) ? 'EMPTY' : 'SET (' . strlen($settingSecret) . ' chars)');
                $errorDetails[] = 'Secrets match: ' . ($envSecret === $settingSecret ? 'YES' : 'NO');
                
                // Try decoding payload
                try {
                    $payloadJson = base64_decode($payloadBase64);
                    $decodedPayload = json_decode($payloadJson, true);
                    if ($decodedPayload) {
                        $errorDetails[] = 'Payload decoded: YES';
                        $errorDetails[] = 'Has reg_id: ' . (isset($decodedPayload['reg_id']) ? 'YES' : 'NO');
                        $errorDetails[] = 'Has event_id: ' . (isset($decodedPayload['event_id']) ? 'YES' : 'NO');
                        
                        // Check timestamp
                        if (isset($decodedPayload['ts'])) {
                            $age = time() - $decodedPayload['ts'];
                            $errorDetails[] = 'QR age: ' . round($age / 3600, 2) . ' hours';
                            if ($age > 24 * 60 * 60) {
                                $errorDetails[] = 'QR EXPIRED (older than 24 hours)';
                            }
                        }
                    } else {
                        $errorDetails[] = 'Payload decoded: NO (invalid JSON)';
                    }
                } catch (\Exception $e) {
                    $errorDetails[] = 'Payload decode error: ' . $e->getMessage();
                }
                
                // Check signature with env secret (matching QRHelper)
                $expectedSig = hash_hmac('sha256', $payloadBase64, $envSecret);
                $errorDetails[] = 'Signature match with env secret: ' . (hash_equals($expectedSig, $signature) ? 'YES' : 'NO');
                
                // Also try default secret
                $expectedSigDefault = hash_hmac('sha256', $payloadBase64, 'default-secret-key');
                $errorDetails[] = 'Signature match with default secret: ' . (hash_equals($expectedSigDefault, $signature) ? 'YES' : 'NO');
            }
            
            \Log::warning('QR Verification Failed', [
                'event_id' => $event->id,
                'qr_preview' => substr($qrValue, 0, 50) . '...',
                'error_details' => $errorDetails,
            ]);
            
            $errorMessage = 'QR code verification failed. ';
            if (isset($decodedPayload['ts'])) {
                $age = time() - $decodedPayload['ts'];
                if ($age > 24 * 60 * 60) {
                    $errorMessage .= 'The QR code has expired (older than 24 hours). ';
                } else {
                    $errorMessage .= 'Possible causes: Secret key mismatch between admin and student apps, or QR code format issue. ';
                    $errorMessage .= 'Please ensure both apps have the same QR_SECRET in their .env files. ';
                }
            } else {
                $errorMessage .= 'The QR code may be invalid or tampered with. ';
            }
            $errorMessage .= 'Please check logs for details or contact support.';
            
            return redirect()->route('admin.scanner.index', ['event_id' => $event->id])
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => $errorMessage,
                ]);
        }

        if ((int) $payload['event_id'] !== (int) $event->id) {
            return redirect()->route('admin.scanner.index', ['event_id' => $event->id])
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => 'QR code belongs to a different event.',
                ]);
        }

        $registration = Registration::with('event')->find($payload['reg_id']);

        if (!$registration) {
            return redirect()->route('admin.scanner.index', ['event_id' => $event->id])
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => 'Registration not found for this QR.',
                ]);
        }

        // Check if student has already scanned in this session
        $existingLog = AttendanceLog::where('registration_id', $registration->id)
            ->where('event_id', $event->id)
            ->where('session_number', $sessionNumber)
            ->where('is_revoked', false)
            ->first();

        if ($existingLog) {
            return redirect()->route('admin.scanner.index', ['event_id' => $event->id])
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => $registration->student_name . ' has already been marked present for Session ' . $sessionNumber . '. Each student can only scan once per session.',
                ]);
        }

        // Store student details in session for confirmation
        $request->session()->put('scanner_pending_registration', [
            'registration_id' => $registration->id,
            'student_name' => $registration->student_name,
            'student_email' => $registration->student_email,
            'student_id' => $registration->student_id,
            'event_id' => $event->id,
            'session_number' => $sessionNumber,
            'session_id' => $session->id,
            'session_date' => $session->session_date->toDateString(),
            'qr_value' => $data['qr_value'],
        ]);

        return redirect()->route('admin.scanner.index', ['event_id' => $event->id, 'session' => $sessionNumber])
            ->with('scanner_result', [
                'status' => 'confirm',
                'message' => 'QR code verified. Please confirm to mark attendance for Session ' . $sessionNumber . '.',
                'student' => $registration->student_name,
                'reg_id' => $registration->id,
                'student_email' => $registration->student_email,
                'student_id' => $registration->student_id,
                'session_number' => $sessionNumber,
            ]);
    }

    public function confirmAttendance(Request $request)
    {
        $pending = $request->session()->get('scanner_pending_registration');
        
        if (!$pending) {
            return redirect()->route('admin.scanner.index')
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => 'No pending attendance to confirm.',
                ]);
        }

        $registration = Registration::with('event')->findOrFail($pending['registration_id']);
        $event = Event::findOrFail($pending['event_id']);
        $sessionModel = AttendanceSession::findOrFail($pending['session_id'] ?? 0);
        
        // Ensure we have the latest event data
        $event->refresh();

        $sessionNumber = (int) $pending['session_number'];

        // Enforce date-wise rule again at confirmation time
        $today = Carbon::today();
        if (!$sessionModel || !$today->isSameDay($sessionModel->session_date)) {
            return redirect()->route('admin.scanner.index', ['event_id' => $event->id])
                ->with('scanner_result', [
                    'status' => 'error',
                    'message' => 'Attendance is allowed only on the scheduled event date.',
                ]);
        }

        $log = DB::transaction(function () use ($registration, $request, $sessionNumber, $event, $sessionModel, $today) {
            // Create attendance log for this session
            $attendanceLog = AttendanceLog::create([
                'registration_id'  => $registration->id,
                'event_id'         => $registration->event_id,
                'session_id'       => $sessionModel->id,
                'session_number'   => $sessionNumber,
                'attendance_date'  => $today->toDateString(),
                'scanned_at'       => now(),
                'scanner_ip'       => $request->ip(),
                'notes'            => 'QR verified via admin panel - Session ' . $sessionNumber,
            ]);

            // Refresh event to get latest attendance_sessions value
            $event->refresh();
            
            // Get required sessions count (default to 1 if null)
            $requiredSessionsCount = (int) ($event->attendance_sessions ?? 1);
            
            // Check if student has attended all required sessions
            // Get all unique session numbers for this registration (including the one just created)
            $attendedSessions = AttendanceLog::where('registration_id', $registration->id)
                ->where('event_id', $event->id)
                ->where('is_revoked', false)
                ->pluck('session_number')
                ->unique()
                ->map(function($session) {
                    return (int) $session; // Ensure integer type
                })
                ->sort()
                ->values()
                ->toArray();

            $requiredSessions = range(1, $requiredSessionsCount);
            
            // Check if all required sessions are attended
            // Method 1: Count check
            $countMatch = count($attendedSessions) === $requiredSessionsCount;
            
            // Method 2: Verify all required session numbers are present
            $allRequiredPresent = true;
            if ($requiredSessionsCount > 0) {
                for ($i = 1; $i <= $requiredSessionsCount; $i++) {
                    if (!in_array($i, $attendedSessions, true)) {
                        $allRequiredPresent = false;
                        break;
                    }
                }
            }
            
            $allSessionsAttended = $countMatch && $allRequiredPresent && $requiredSessionsCount > 0;

            \Log::info('Checking session attendance', [
                'registration_id' => $registration->id,
                'event_id' => $event->id,
                'event_attendance_sessions_raw' => $event->attendance_sessions,
                'attendance_sessions' => $requiredSessionsCount,
                'attended_sessions' => $attendedSessions,
                'required_sessions' => $requiredSessions,
                'count_match' => $countMatch,
                'all_required_present' => $allRequiredPresent,
                'all_sessions_attended' => $allSessionsAttended,
            ]);

            // Update attendance status: 'present' only if all sessions are attended
            if ($allSessionsAttended) {
                $registration->attendance_status = 'present';
                $registration->save();
                \Log::info('Attendance status updated to present', [
                    'registration_id' => $registration->id,
                    'attended_sessions' => $attendedSessions,
                    'required_sessions' => $requiredSessions,
                ]);
            } else {
                // Keep as 'pending' until all sessions are completed
                $registration->attendance_status = 'pending';
                $registration->save();
                \Log::info('Attendance status kept as pending', [
                    'registration_id' => $registration->id,
                    'attended_sessions' => $attendedSessions,
                    'required_sessions' => $requiredSessions,
                    'missing_sessions' => array_diff($requiredSessions, $attendedSessions),
                ]);
            }

            // Refresh registration to get updated status
            $registration->refresh();

            return $attendanceLog;
        });
        
        // Refresh registration after transaction
        $registration->refresh();

        $request->session()->forget('scanner_pending_registration');

        // Get session attendance summary
        $attendedSessions = AttendanceLog::where('registration_id', $registration->id)
            ->where('event_id', $event->id)
            ->where('is_revoked', false)
            ->distinct()
            ->pluck('session_number')
            ->sort()
            ->values()
            ->toArray();

        $sessionsMessage = 'Session ' . $sessionNumber . ' marked. ';
        if (count($attendedSessions) < $event->attendance_sessions) {
            $remaining = $event->attendance_sessions - count($attendedSessions);
            $sessionsMessage .= $remaining . ' session(s) remaining.';
        } else {
            $sessionsMessage .= 'All sessions completed!';
        }

        return redirect()->route('admin.scanner.index', ['event_id' => $event->id, 'session' => $sessionNumber])
            ->with('scanner_result', [
                'status' => 'success',
                'message' => 'Attendance marked for ' . $registration->student_name . '. ' . $sessionsMessage,
                'student' => $registration->student_name,
                'reg_id' => $registration->id,
                'time' => now()->format('Y-m-d H:i:s'),
                'log_id' => $log?->id,
                'session_number' => $sessionNumber,
            ]);
    }

    public function markAbsent(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|exists:registrations,id',
        ]);

        $registration = Registration::with('event')->findOrFail($request->registration_id);
        
        // Check if event is completed
        $event = $registration->event;
        $isEventComplete = $event->end_date && \Carbon\Carbon::parse($event->end_date)->isPast();

        if (!$isEventComplete) {
            return back()->with('scanner_result', [
                'status' => 'error',
                'message' => 'Cannot mark absent. Event is not yet completed.',
            ]);
        }

        if ($registration->attendance_status !== 'pending') {
            return back()->with('scanner_result', [
                'status' => 'error',
                'message' => 'Only pending registrations can be marked as absent.',
            ]);
        }

        $registration->update(['attendance_status' => 'absent']);

        return back()->with('scanner_result', [
            'status' => 'success',
            'message' => $registration->student_name . ' marked as absent.',
        ]);
    }

    public function revoke(Request $request)
    {
        $request->validate([
            'log_id' => 'required|exists:attendance_logs,id',
        ]);

        $log = AttendanceLog::with('registration')->findOrFail($request->log_id);

        DB::transaction(function () use ($log) {
            $log->update([
                'is_revoked' => true,
                'revoked_at' => now(),
                'notes' => 'Attendance revoked via admin panel',
            ]);

            $log->registration?->update(['attendance_status' => 'pending']);
        });

        return back()->with('scanner_result', [
            'status' => 'info',
            'message' => 'Attendance revoked for ' . optional($log->registration)->student_name,
        ]);
    }
}


