# Event Booking Admin (SQL Server)

## Requirements

- PHP 8.2+
- Composer
- Microsoft SQL Server (sqlsrv)

## Setup

```bash
cp .env.example .env
php artisan key:generate
# update DB_* env vars to sqlsrv credentials
php artisan migrate
php artisan serve --host=0.0.0.0 --port=9000
```

## Creating Sample Data

1. Visit `http://localhost:9000/`.
2. Use the **Quick Create Event** form to add your first hall/event.
3. Open **Registrations** page and add a registration for that event.
4. The system automatically generates an HMAC QR string for each registration.

## QR Generation & Verification

- Every registration stores a QR payload like `base64(payload).signature`.
- Payload includes `reg_id`, `event_id`, `user_email`, `ts`.
- Edit the QR secret under **Settings → QR Secret Key**.
- Go to **QR Scanner** page, paste the QR string, and submit.
- On success the system logs attendance and stores a scan entry.

## Certificate Workflow

1. Mark a registration as `present` (from Registrations page or QR scan).
2. Open **Certificates** page → generate certificate.
3. PDF files are stored under `storage/app/certificates` (configurable).
4. Download or revoke certificates from the same page.

## Reports & CSV

- **Events** page → `Export Events CSV`
- **Registrations/Attendance/Payments** exports under **Reports** page
- Monthly and per-event PDF reports are generated via DomPDF.

## Testing Checklist

- [ ] Create event and verify it appears on Events page
- [ ] Register student, regenerate/download QR
- [ ] Verify QR via Scanner and see attendance log
- [ ] Mark payments as paid/refunded
- [ ] Generate certificates (single + bulk)
- [ ] Export CSVs and download PDF reports

## Notes

- All UI blocks follow the requirement “3 sections per row”.
- No frontend framework or animation is used (plain Blade + CSS).
- Student portal placeholder lives inside `../eventbookingstudents`.


