<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixAttendanceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:fix-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix attendance status for registrations that have completed all sessions but are still marked as pending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting attendance status fix...');
        $this->newLine();

        $pendingRegistrations = \App\Models\Registration::where('attendance_status', 'pending')
            ->with(['event', 'attendanceLogs' => function($query) {
                $query->where('is_revoked', false);
            }])
            ->get();

        $this->info("Found {$pendingRegistrations->count()} pending registrations");
        $this->newLine();

        $fixed = 0;
        $skipped = 0;

        foreach ($pendingRegistrations as $registration) {
            if (!$registration->event) {
                $this->warn("Skipping registration #{$registration->id} - event not found");
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
            
            $this->line("Registration #{$registration->id} - {$registration->student_name}");
            $this->line("  Event: {$event->title} (ID: {$event->id})");
            $this->line("  Required sessions: {$requiredSessionsCount}");
            $this->line("  Attended sessions: " . implode(', ', $attendedSessions) . " (" . count($attendedSessions) . ")");
            $this->line("  All sessions attended: " . ($allSessionsAttended ? 'YES' : 'NO'));
            
            if ($allSessionsAttended) {
                $registration->attendance_status = 'present';
                $registration->save();
                $this->info("  ✓ Updated to 'present'");
                $fixed++;
            } else {
                $missing = array_diff($requiredSessions, $attendedSessions);
                $this->warn("  - Still pending (missing sessions: " . implode(', ', $missing) . ")");
                $skipped++;
            }
            $this->newLine();
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('Summary:');
        $this->info("  Fixed: {$fixed}");
        $this->info("  Skipped: {$skipped}");
        $this->info("  Total: " . ($fixed + $skipped));
        $this->info('========================================');

        return 0;
    }
}
