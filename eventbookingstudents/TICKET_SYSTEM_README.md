# QR Ticket Inline Viewing System

## Overview
The ticket system allows students to view their event tickets inline in a modal instead of downloading files. Tickets are generated as PDFs with QR codes and displayed in the browser.

## Features
- **Inline PDF Display**: Tickets open in a modal with an iframe, no file downloads
- **Auto-Generation**: Tickets are generated on-the-fly when first accessed
- **QR Code Integration**: Each ticket includes a signed QR code for event entry
- **Security**: Tickets are tied to student email (session-based validation)

## Files Created/Modified

### Controllers
- `app/Http/Controllers/Student/TicketController.php` - Handles ticket generation and display

### Helpers
- `app/Helpers/QRHelper.php` - QR code generation and verification utilities

### Views
- `resources/views/student/ticket/pdf.blade.php` - Ticket PDF template
- Updated: `resources/views/student/events/show.blade.php` - "View Ticket" button
- Updated: `resources/views/student/dashboard.blade.php` - "View Ticket" links
- Updated: `resources/views/layouts/student.blade.php` - Ticket modal HTML/CSS/JS

### Routes
- `GET /student/registrations/{id}/ticket` - Route to display ticket inline

## How It Works

1. **Registration**: When a student registers, a QR code is generated and stored in the `registrations` table
2. **Ticket Access**: Student clicks "View Ticket" button
3. **Ticket Generation**: 
   - System checks if PDF exists in `storage/app/registrations/ticket_{id}.pdf`
   - If not, generates PDF on-the-fly using:
     - QR code SVG (from QRHelper)
     - Student and event details
     - Compact ticket layout (300x420px)
4. **Display**: PDF is returned with `Content-Disposition: inline` header
5. **Modal**: JavaScript opens modal with iframe showing the PDF

## Testing

1. **Register for an event** as a student
2. **Click "View Ticket"** button (appears after registration)
3. **Verify**:
   - Modal opens with ticket PDF
   - QR code is visible
   - Student name, event details are correct
   - PDF displays inline (not downloaded)

## Technical Details

### PDF Generation
- Uses `Barryvdh\DomPDF` for PDF rendering
- Paper size: 300x420px (compact ticket size)
- QR code rendered as SVG embedded in PDF

### QR Code
- Generated using `BaconQrCode` library
- Signed with HMAC-SHA256 using `QR_SECRET` from `.env`
- Contains: registration_id, event_id, student_email, timestamp

### Storage
- Tickets saved to: `storage/app/registrations/ticket_{id}.pdf`
- Generated once, cached for future access

### Security
- Tickets are validated against session email (if session exists)
- QR codes are cryptographically signed
- Tickets are only accessible for valid registrations

## Environment Variables

Ensure `.env` has:
```
QR_SECRET=your-secret-key-here
```

## Dependencies

- `bacon/bacon-qr-code: ^3.0` - QR code generation
- `barryvdh/laravel-dompdf: *` - PDF generation

Both are already in `composer.json`.

