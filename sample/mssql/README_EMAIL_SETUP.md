# Email Notification Setup Guide

This guide explains how to set up email notifications for new bookings.

## Overview

When a new booking is created, the system automatically sends an email notification to the admin email address stored in the database.

## Setup Steps

### 1. Configure Email Settings in .env

Add the following to your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**For Gmail:**
- Use `smtp.gmail.com` as MAIL_HOST
- Use `587` for MAIL_PORT (TLS) or `465` for SSL
- Use `tls` for MAIL_ENCRYPTION (port 587) or `ssl` (port 465)
- Generate an App Password from your Google Account settings (not your regular password)

**For Outlook/Hotmail:**
- Use `smtp-mail.outlook.com` as MAIL_HOST
- Use `587` for MAIL_PORT
- Use `tls` for MAIL_ENCRYPTION

### 2. Create Database Table

Run the SQL script to create the `admin_settings` table:

```bash
sqlcmd -S localhost -d KAREHALL -U sa -P YourPassword -i sample\mssql\create_admin_settings_table.sql
```

Or use the main script that includes all tables:

```bash
sqlcmd -S localhost -d KAREHALL -U sa -P YourPassword -i sample\mssql\create_all_tables.sql
```

### 3. Add Admin Email

1. Go to Admin Dashboard
2. Click on "Manage Admin" section
3. Enter the admin email address
4. Click "Add Admin Email" or "Update Admin Email"

The email will be saved in the database.

### 4. Test Email

1. Create a test booking from the user side
2. Check the admin email inbox for the notification
3. If email doesn't arrive, check:
   - Laravel logs: `storage/logs/laravel.log`
   - Email configuration in `.env`
   - Spam folder

## How It Works

1. **User creates booking** → Booking is saved to database
2. **System fetches admin email** → From `admin_settings` table
3. **Email is sent** → Using Laravel Mail with configured SMTP settings
4. **Admin receives notification** → Clean formatted email with all booking details

## Email Template

The email includes:
- Booking details (Hall, Date, Time, Purpose)
- Organizer information (Name, Email, Phone, Department)
- Required facilities
- Seating capacity
- Link to view booking in admin panel

## Troubleshooting

### Email not sending?
1. Check `.env` file has correct SMTP settings
2. Verify app password is correct (for Gmail)
3. Check Laravel logs for errors
4. Test SMTP connection manually

### Table not found error?
- The system will automatically create the table if it doesn't exist
- Or run the SQL script manually

### Email goes to spam?
- Check SPF/DKIM records for your domain
- Use a professional email service
- Add sender email to contacts

## Security Notes

- App passwords are more secure than regular passwords
- Never commit `.env` file to version control
- Use environment-specific email accounts for testing

