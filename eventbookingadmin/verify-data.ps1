# Verification Script for Event Data and PDFs
Write-Host "=== Checking Database Records ===" -ForegroundColor Cyan

# Check database
php artisan tinker --execute="`$count = App\Models\Event::count(); echo 'Total Events: ' . `$count . PHP_EOL; if(`$count > 0) { `$event = App\Models\Event::latest()->first(); echo 'Latest Event: ' . `$event->title . PHP_EOL; echo 'Brochure: ' . (`$event->brochure_path ?? 'None') . PHP_EOL; echo 'Attachment: ' . (`$event->attachment_path ?? 'None') . PHP_EOL; }"

Write-Host "`n=== Checking PDF Storage ===" -ForegroundColor Cyan

# Check if directories exist
if (Test-Path "storage\app\event_brochures") {
    Write-Host "Brochures directory: EXISTS" -ForegroundColor Green
    $brochureFiles = Get-ChildItem "storage\app\event_brochures" -File -Recurse
    Write-Host "Brochure files found: $($brochureFiles.Count)" -ForegroundColor Green
    if ($brochureFiles.Count -gt 0) {
        $brochureFiles | ForEach-Object { Write-Host "  - $($_.Name) ($([math]::Round($_.Length/1KB, 2)) KB)" }
    }
} else {
    Write-Host "Brochures directory: NOT FOUND" -ForegroundColor Yellow
}

if (Test-Path "storage\app\event_attachments") {
    Write-Host "Attachments directory: EXISTS" -ForegroundColor Green
    $attachmentFiles = Get-ChildItem "storage\app\event_attachments" -File -Recurse
    Write-Host "Attachment files found: $($attachmentFiles.Count)" -ForegroundColor Green
    if ($attachmentFiles.Count -gt 0) {
        $attachmentFiles | ForEach-Object { Write-Host "  - $($_.Name) ($([math]::Round($_.Length/1KB, 2)) KB)" }
    }
} else {
    Write-Host "Attachments directory: NOT FOUND" -ForegroundColor Yellow
}

Write-Host "`n=== Verification Complete ===" -ForegroundColor Cyan

