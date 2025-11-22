# Commands to Check Data and PDF Storage

## 1. Check Database Records

### Using Laravel Tinker (Recommended)
```powershell
cd eventbookingadmin
php artisan tinker
```

Then in tinker, run:
```php
// Check all events
Event::all();

// Check latest event
Event::latest()->first();

// Check event with PDFs
Event::whereNotNull('brochure_path')->orWhereNotNull('attachment_path')->get();

// Check specific event by ID
Event::find(1);

// Count total events
Event::count();
```

Type `exit` to leave tinker.

### Using SQL Query (Direct Database)
```powershell
cd eventbookingadmin
php artisan db
```

Then run SQL:
```sql
SELECT id, title, organizer, start_date, brochure_path, attachment_path FROM events;
SELECT COUNT(*) as total_events FROM events;
```

## 2. Check PDF Files in Storage

### Check if storage directories exist
```powershell
cd eventbookingadmin
Test-Path storage\app\event_brochures
Test-Path storage\app\event_attachments
```

### List all PDF files
```powershell
cd eventbookingadmin
Get-ChildItem storage\app\event_brochures -Recurse
Get-ChildItem storage\app\event_attachments -Recurse
```

### Count PDF files
```powershell
cd eventbookingadmin
(Get-ChildItem storage\app\event_brochures -Recurse -File).Count
(Get-ChildItem storage\app\event_attachments -Recurse -File).Count
```

### Check file sizes
```powershell
cd eventbookingadmin
Get-ChildItem storage\app\event_brochures -Recurse -File | Select-Object Name, Length, LastWriteTime
Get-ChildItem storage\app\event_attachments -Recurse -File | Select-Object Name, Length, LastWriteTime
```

## 3. Quick Verification Script

Run this to check everything at once:
```powershell
cd eventbookingadmin
php artisan tinker --execute="echo 'Total Events: ' . App\Models\Event::count() . PHP_EOL; echo 'Events with Brochures: ' . App\Models\Event::whereNotNull('brochure_path')->count() . PHP_EOL; echo 'Events with Attachments: ' . App\Models\Event::whereNotNull('attachment_path')->count() . PHP_EOL;"
```

