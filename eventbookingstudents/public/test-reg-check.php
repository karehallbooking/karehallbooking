<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: application/json');

try {
    // Get latest registration
    $latestReg = \DB::table('registrations')->latest('registered_at')->first();
    
    // Get all registrations count
    $totalRegs = \DB::table('registrations')->count();
    
    // Get registrations for event 2
    $event2Regs = \DB::table('registrations')->where('event_id', 2)->get();
    
    // Get registrations for event 3
    $event3Regs = \DB::table('registrations')->where('event_id', 3)->get();
    
    echo json_encode([
        'total_registrations' => $totalRegs,
        'latest_registration' => $latestReg,
        'event_2_registrations' => $event2Regs,
        'event_3_registrations' => $event3Regs,
        'events_table_check' => [
            'event_2' => \DB::table('events')->where('id', 2)->first(['id', 'title', 'status', 'capacity', 'registrations_count']),
            'event_3' => \DB::table('events')->where('id', 3)->first(['id', 'title', 'status', 'capacity', 'registrations_count']),
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

