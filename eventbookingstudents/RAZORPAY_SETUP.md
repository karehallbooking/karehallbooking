# Razorpay Payment Integration Setup Guide

## Overview
This document describes the Razorpay payment integration that has been implemented for event registration in the Laravel application.

## What Has Been Implemented

### 1. Service Layer
- **`app/Services/RazorpayService.php`**: Handles Razorpay API interactions
  - Creates Razorpay orders
  - Verifies payment signatures
  - Verifies webhook signatures

### 2. Database Migrations
- **`eventbookingadmin/database/migrations/2025_01_15_000001_add_razorpay_fields_to_payments_table.php`**: Adds Razorpay-specific fields to payments table
- **`eventbookingadmin/database/migrations/2025_01_15_000002_create_tickets_table.php`**: Creates tickets table

### 3. Models
- **`app/Models/Payment.php`** (updated): Added Razorpay fields
- **`app/Models/Ticket.php`**: New model for ticket management
- **`app/Models/Registration.php`** (updated): Added payment and ticket relationships

### 4. Controllers
- **`app/Http/Controllers/EventRegistrationController.php`**: 
  - `showRegisterPage()`: Displays registration page with payment option
  - `createOrderForEvent()`: Creates Razorpay order for payment
  
- **`app/Http/Controllers/PaymentController.php`**:
  - `handlePaymentSuccess()`: Verifies and processes successful payments
  - `successPage()`: Displays payment success page with ticket
  - `failurePage()`: Displays payment failure page
  - `handleWebhook()`: Processes Razorpay webhook events
  - `generateTicket()`: Generates ticket with QR code after payment

### 5. Routes
Added to `routes/web.php`:
- `GET /events/{event}/register` - Registration page
- `POST /events/{event}/create-order` - Create Razorpay order
- `POST /payment/success` - Handle payment success callback
- `GET /payment/success` - Payment success page
- `GET /payment/failure` - Payment failure page
- `POST /webhooks/razorpay/payment` - Razorpay webhook (CSRF exempt)

### 6. Views
- **`resources/views/events/register.blade.php`**: Registration page with Razorpay checkout integration
- **`resources/views/payment/success.blade.php`**: Payment success page with ticket details
- **`resources/views/payment/failure.blade.php`**: Payment failure page

## Setup Instructions

### Step 1: Install Razorpay PHP SDK
```bash
cd eventbookingstudents
composer require razorpay/razorpay
```

### Step 2: Configure Environment Variables
Add these to your `.env` file in `eventbookingstudents`:

```env
RAZORPAY_KEY_ID=your_razorpay_key_id
RAZORPAY_KEY_SECRET=your_razorpay_key_secret
RAZORPAY_CURRENCY=INR
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
```

**Note**: Get these credentials from your Razorpay Dashboard:
- Go to https://dashboard.razorpay.com/
- Navigate to Settings > API Keys
- Copy Key ID and Key Secret
- For webhook secret, go to Settings > Webhooks and create a webhook

### Step 3: Run Migrations
```bash
cd eventbookingadmin
php artisan migrate
```

This will:
- Add Razorpay fields to the `payments` table
- Create the `tickets` table

### Step 4: Configure Razorpay Webhook (Optional for Localhost)

**For Localhost/Development:**
- Webhooks are **NOT required** for localhost testing
- The payment success callback (`POST /payment/success`) will handle payments automatically
- You can skip webhook configuration for now and set it up later for production

**For Production:**
1. Go to Razorpay Dashboard > Settings > Webhooks
2. Add webhook URL: `https://yourdomain.com/webhooks/razorpay/payment`
3. Select events: `payment.captured`
4. Copy the webhook secret and add it to `.env` as `RAZORPAY_WEBHOOK_SECRET`

**Testing Webhooks Locally (Optional):**
If you want to test webhooks on localhost, use a tunneling tool like ngrok:
```bash
# Install ngrok: https://ngrok.com/
ngrok http 8000  # Replace 8000 with your Laravel port

# Use the ngrok URL in Razorpay webhook settings:
# https://your-ngrok-url.ngrok.io/webhooks/razorpay/payment
```

### Step 5: Test the Integration

1. **Test Payment Flow**:
   - Navigate to an event registration page: `/events/{event_id}/register`
   - Fill in student details
   - Click "Pay with Razorpay"
   - Complete payment in Razorpay test mode
   - Verify ticket is generated and displayed

2. **Test Webhook**:
   - Use Razorpay's webhook testing tool or make a test payment
   - Verify webhook is received and processed correctly

## Payment Flow

1. **User Registration**:
   - Student visits `/events/{event_id}/register`
   - Fills in registration form
   - Clicks "Pay with Razorpay" button

2. **Order Creation**:
   - Frontend sends AJAX request to `/events/{event_id}/create-order`
   - Backend creates Razorpay order via `RazorpayService`
   - Payment record is created with status `pending`

3. **Payment Processing**:
   - Razorpay Checkout modal opens
   - User completes payment (UPI/QR/Card)
   - Razorpay returns payment response to frontend

4. **Payment Verification** (Primary Method):
   - Frontend sends payment response to `/payment/success` (POST)
   - Backend verifies signature using Razorpay SDK
   - Payment status updated to `success`
   - Registration status updated to `paid`
   - Ticket generated with QR code

5. **Webhook Processing** (Backup/Reliability):
   - **Note**: Webhooks are optional for localhost, required for production
   - Razorpay sends webhook to `/webhooks/razorpay/payment` (if configured)
   - Backend verifies webhook signature
   - Processes payment if not already processed (idempotent)
   - **Purpose**: Handles cases where callback might fail (network issues, user closes browser, etc.)

6. **Success Page**:
   - User redirected to `/payment/success?ticket={ticket_code}`
   - Displays ticket details and QR code

## Security Features

1. **Server-Side Verification**: All payment signatures are verified on the server
2. **Idempotent Processing**: Payments are only processed once, even if webhook and callback both fire
3. **Database Transactions**: All payment updates use database transactions
4. **Row Locking**: Payment records are locked during processing to prevent race conditions
5. **Webhook Signature Verification**: Webhook requests are verified using HMAC signature

## Important Notes

1. **Amount Calculation**: All amounts are stored in rupees, but Razorpay requires amounts in paise (smallest currency unit). The service automatically converts.

2. **Ticket Generation**: Tickets are generated automatically after successful payment. QR codes are generated using the existing `QRHelper` class.

3. **Free Events**: For free events (`is_paid = false`), the system uses the existing registration flow without payment.

4. **Error Handling**: All errors are logged and user-friendly messages are displayed. Check `storage/logs/laravel.log` for detailed error information.

5. **Testing**: Use Razorpay's test mode for development. Test credentials are available in the Razorpay Dashboard.

## Troubleshooting

### Payment Not Processing
- Check Razorpay credentials in `.env`
- Verify Razorpay SDK is installed: `composer show razorpay/razorpay`
- Check Laravel logs: `storage/logs/laravel.log`

### Webhook Not Working
- **For localhost**: Webhooks won't work without a tunneling tool (ngrok). This is normal and not required for testing.
- **For production**: Verify webhook URL is accessible from internet
- Check webhook secret in `.env` (can be empty for localhost)
- Ensure webhook route is CSRF exempt (already configured)
- Check Razorpay dashboard for webhook delivery status

### Ticket Not Generated
- Verify payment status is `success` in database
- Check `tickets` table for ticket record
- Verify QR code generation is working (check `QRHelper`)

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Razorpay Dashboard for payment status
3. Verify database records in `payments` and `tickets` tables

