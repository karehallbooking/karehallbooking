<?php

/**
 * Script to fix attendance status for registrations that have completed all sessions
 * but are still marked as 'pending'
 * 
 * Run this from the admin directory: php fix-attendance-status.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Registration;
use App\Models\Event;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\DB;

echo "Starting attendance status fix...\n\n";

// Get all registrations that are marked as pending
$pendingRegistrations = Registration::where('attendance_status', 'pending')
    ->with(['event', 'attendanceLogs' => function($query) {
        $query->where('is_revoked', false);
    }])
    ->get();

echo "Found " . $pendingRegistrations->count() . " pending registrations\n\n";

$fixed = 0;
$skipped = 0;

foreach ($pendingRegistrations as $registration) {
    if (!$registration->event) {
        echo "Skipping registration #{$registration->id} - event not found\n";
        $skipped++;
        continue;
    }
    
    $event = $registration->event;
    $requiredSessionsCount = (int) ($event->attendance_sessions ?? 1);
    
    // Get all unique session numbers for this registration
    $attendedSessions = $registration->attendanceLogs
        ->pluck('session_number')
        ->unique()
        ->map(function($session) {
            return (int) $session;
        })
        ->sort()
        ->values()
        ->toArray();
    
    $requiredSessions = range(1, $requiredSessionsCount);
    
    // Check if all required sessions are attended
    $countMatch = count($attendedSessions) === $requiredSessionsCount;
    
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
    
    echo "Registration #{$registration->id} - {$registration->student_name}\n";
    echo "  Event: {$event->title} (ID: {$event->id})\n";
    echo "  Required sessions: {$requiredSessionsCount}\n";
    echo "  Attended sessions: " . implode(', ', $attendedSessions) . " (" . count($attendedSessions) . ")\n";
    echo "  All sessions attended: " . ($allSessionsAttended ? 'YES' : 'NO') . "\n";
    
    if ($allSessionsAttended) {
        $registration->attendance_status = 'present';
        $registration->save();
        echo "  ✓ Updated to 'present'\n";
        $fixed++;
    } else {
        echo "  - Still pending (missing sessions: " . implode(', ', array_diff($requiredSessions, $attendedSessions)) . ")\n";
        $skipped++;
    }
    echo "\n";
}

echo "========================================\n";
echo "Summary:\n";
echo "  Fixed: {$fixed}\n";
echo "  Skipped: {$skipped}\n";
echo "  Total: " . ($fixed + $skipped) . "\n";
echo "========================================\n";


