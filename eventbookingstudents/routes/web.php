<?php

use App\Http\Controllers\Student\DashboardController;
use Illuminate\Support\Facades\Route;

// Root redirect to dashboard
Route::get('/', function () {
    return redirect()->route('student.dashboard');
});

// Test Database Connection (temporary)
Route::get('/test-db', function () {
    try {
        \DB::connection()->getPdo();
        $dbName = \DB::connection()->getDatabaseName();
        $eventCount = \App\Models\Event::count();
        $publishedCount = \App\Models\Event::where('status', 'published')->count();
        $availableCount = \App\Models\Event::where('status', 'published')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->count();
        
        return response()->json([
            'status' => 'connected',
            'database' => $dbName,
            'total_events' => $eventCount,
            'published_events' => $publishedCount,
            'available_events' => $availableCount,
            'sample_events' => \App\Models\Event::where('status', 'published')
                ->whereDate('end_date', '>=', now()->toDateString())
                ->take(3)
                ->get(['id', 'title', 'status', 'start_date', 'end_date'])
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Student Dashboard
Route::get('/student/dashboard', [DashboardController::class, 'index'])->name('student.dashboard');

// Event Details
Route::get('/student/events/{id}', [DashboardController::class, 'show'])->name('student.events.show');

// Event Registration
Route::post('/student/events/{id}/register', [DashboardController::class, 'register'])->name('student.events.register');

// PDF Downloads
Route::get('/student/events/{id}/brochure', [DashboardController::class, 'downloadBrochure'])->name('student.events.brochure');
Route::get('/student/events/{id}/attachment', [DashboardController::class, 'downloadAttachment'])->name('student.events.attachment');
Route::get('/student/certificates/{id}/download', [DashboardController::class, 'downloadCertificate'])->name('student.certificates.download');

// Ticket Viewing (Inline)
Route::get('/student/registrations/{id}/ticket', [\App\Http\Controllers\Student\TicketController::class, 'show'])->name('student.ticket.show');

