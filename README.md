# KARE Hall Booking – Email Notification System

This package includes Firebase Cloud Functions that send branded email notifications via SMTP (Nodemailer), along with minimal frontend examples.

Emails sent:
- Welcome email on user creation
- Booking request acknowledgement to user
- Booking notification to admin
- Booking result (approved / rejected) to user

## Structure

```
functions/
  package.json
  .env (local only)
  index.js
  mailer.js
  templates/
    welcome.{html,txt}
    booking_received_user.{html,txt}
    booking_received_admin.{html,txt}
    booking_approved.{html,txt}
    booking_rejected.{html,txt}
frontend/
  BookingForm.jsx
  api.js
.env.example
```

## Prerequisites
- Node.js 20
- Firebase CLI: `npm i -g firebase-tools`
- Firebase project with Firestore enabled

## Environment Variables
Copy `.env.example` to `functions/.env` and fill values:

```
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_USER=your@gmail.com
SMTP_PASS=your-app-password
ADMIN_EMAIL=admin@example.com
PUBLIC_SITE_URL=https://your-site.example.com
FIREBASE_CREDENTIALS_BASE64= # base64 of service account JSON (optional locally)
FIREBASE_PROJECT_ID=
```

For Gmail, create an App Password and use it as `SMTP_PASS`.

## Install

```
cd functions
npm install
```

## Deploy

```
firebase login
firebase deploy --only functions
```

## Testing
1. In Firestore, add a doc in `users/{uid}` with `email` and optional `name` → expect Welcome email.
2. Add a doc in `bookings/{bid}` with fields:
   - hall, startDateTime, endDateTime, purpose, peopleCount, bookedBy, contact, email
   - status defaults to `pending`
   → expect user acknowledgement and admin notification.
3. Update `bookings/{bid}.status` to `approved` or `rejected` (optionally set `rejectionReason`) → expect corresponding email to user.
4. Optional HTTPS test: POST to `https://<region>-<project>.cloudfunctions.net/createBooking` with JSON body matching the booking fields.

## Local Emulation

```
cd functions
firebase emulators:start --only functions
```

Then POST to `http://localhost:5001/<project-id>/us-central1/createBooking`.

## Frontend Example
Use `frontend/BookingForm.jsx`. It posts to `/createBooking` which maps to the Cloud Function when deployed behind Firebase Hosting rewrites, or to the emulator URL locally.

## Notes
- All templates include inline-friendly CSS using the brand colors:
  - Primary dark: #154D71
  - Primary mid: #1C6EA4
  - Accent: #33A1E0
- Plain-text alternatives are provided in `.txt` files.
- Delivery attempts are logged to Firestore collection `mailLogs`.


