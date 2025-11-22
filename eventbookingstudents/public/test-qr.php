<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/html; charset=utf-8');

try {
    $reg = \App\Models\Registration::with('event')->first();
    
    if (!$reg) {
        echo "No registration found";
        exit;
    }
    
    echo "<h2>Testing QR Code Generation</h2>";
    echo "<p><strong>Registration ID:</strong> " . $reg->id . "</p>";
    echo "<p><strong>QR Code String:</strong> " . substr($reg->qr_code ?? 'NULL', 0, 100) . "...</p>";
    
    if ($reg->qr_code) {
        $qrSvg = \App\Helpers\QRHelper::renderSvg($reg->qr_code, 200);
        echo "<p><strong>SVG Length:</strong> " . strlen($qrSvg) . " bytes</p>";
        echo "<p><strong>SVG Preview (first 200 chars):</strong> " . htmlspecialchars(substr($qrSvg, 0, 200)) . "...</p>";
        echo "<hr>";
        echo "<h3>QR Code SVG:</h3>";
        echo $qrSvg;
    } else {
        echo "<p style='color: red;'>No QR code found for this registration!</p>";
    }
    
} catch (\Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

