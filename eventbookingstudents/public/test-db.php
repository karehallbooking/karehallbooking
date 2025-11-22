<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: application/json');

try {
    // Test database connection
    \DB::connection()->getPdo();
    $dbName = \DB::connection()->getDatabaseName();
    
    // Count events
    $totalEvents = \DB::table('events')->count();
    $publishedEvents = \DB::table('events')->where('status', 'published')->count();
    $upcomingEvents = \DB::table('events')->where('status', 'upcoming')->count();
    
    // Get all events with their status
    $allEvents = \DB::table('events')
        ->get(['id', 'title', 'status', 'start_date', 'end_date']);
    
    // Get available events (upcoming or published, with future end_date)
    $availableEvents = \DB::table('events')
        ->whereIn('status', ['published', 'upcoming'])
        ->whereDate('end_date', '>=', now()->toDateString())
        ->get(['id', 'title', 'status', 'start_date', 'end_date']);
    
    echo json_encode([
        'status' => 'connected',
        'database' => $dbName,
        'connection' => config('database.default'),
        'total_events' => $totalEvents,
        'published_events' => $publishedEvents,
        'upcoming_events' => $upcomingEvents,
        'available_events_count' => $availableEvents->count(),
        'all_events' => $allEvents,
        'available_events' => $availableEvents,
        'query_check' => [
            'status_field_exists' => \DB::table('events')->whereNotNull('status')->exists(),
            'end_date_field_exists' => \DB::table('events')->whereNotNull('end_date')->exists(),
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}

