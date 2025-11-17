<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestEmailConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration and send a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Email Configuration Test ===');
        $this->newLine();

        // Check mail configuration
        $this->info('1. Checking Mail Configuration:');
        $mailer = config('mail.default');
        $mailHost = config('mail.mailers.smtp.host');
        $mailPort = config('mail.mailers.smtp.port');
        $mailUsername = config('mail.mailers.smtp.username');
        $mailFrom = config('mail.from.address');
        $mailFromName = config('mail.from.name');

        $this->line("   Mailer: {$mailer}");
        $this->line("   Host: {$mailHost}");
        $this->line("   Port: {$mailPort}");
        $this->line("   Username: " . ($mailUsername ?: 'NOT SET'));
        $this->line("   From Address: {$mailFrom}");
        $this->line("   From Name: {$mailFromName}");

        if ($mailer === 'log') {
            $this->warn('   ⚠️  WARNING: Mailer is set to "log" - emails will be written to log file, not actually sent!');
            $this->line("   To send real emails, set MAIL_MAILER=smtp in your .env file");
        }

        $this->newLine();

        // Check admin email in database
        $this->info('2. Checking Admin Email in Database:');
        try {
            $admin = DB::table('admin_settings')
                ->where('is_active', 1)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($admin) {
                $this->info("   ✓ Admin email found: {$admin->admin_email}");
            } else {
                $this->error('   ✗ No active admin email found in admin_settings table');
                $this->line('   Please add an admin email in the "Manage Admin" section');
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Error checking admin_settings table: " . $e->getMessage());
        }

        $this->newLine();

        // Test email sending
        $testEmail = $this->argument('email') ?: ($admin->admin_email ?? null);
        
        if (!$testEmail) {
            $this->error('No email address provided. Usage: php artisan email:test your@email.com');
            return 1;
        }

        $this->info("3. Sending Test Email to: {$testEmail}");
        
        try {
            Mail::raw('This is a test email from KARE Hall Booking System. If you receive this, your email configuration is working correctly!', function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Test Email - KARE Hall Booking System');
            });

            if ($mailer === 'log') {
                $this->warn('   ⚠️  Email was logged (not actually sent) because MAIL_MAILER=log');
                $this->line('   Check storage/logs/laravel.log for the email content');
            } else {
                $this->info('   ✓ Test email sent successfully!');
                $this->line('   Please check your inbox (and spam folder)');
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Failed to send test email: " . $e->getMessage());
            $this->line("   Error details: " . $e->getTraceAsString());
            return 1;
        }

        $this->newLine();
        $this->info('=== Test Complete ===');
        
        return 0;
    }
}
