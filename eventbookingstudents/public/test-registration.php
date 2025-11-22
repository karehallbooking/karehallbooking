<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: application/json');

try {
    // Check if registrations table exists
    $tableExists = \DB::select("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'registrations'");
    $registrationsExist = !empty($tableExists);
    
    // Count registrations
    $registrationCount = 0;
    $latestRegistration = null;
    if ($registrationsExist) {
        $registrationCount = \DB::table('registrations')->count();
        $latestRegistration = \DB::table('registrations')->latest('registered_at')->first();
    }
    
    // Check events
    $events = \DB::table('events')->select('id', 'title', 'status')->get();
    
    // Check registrations table structure
    $columns = [];
    if ($registrationsExist) {
        $columns = \DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'registrations'");
    }
    
    echo json_encode([
        'registrations_table_exists' => $registrationsExist,
        'total_registrations' => $registrationCount,
        'latest_registration' => $latestRegistration,
        'events' => $events,
        'registrations_columns' => $columns,
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

