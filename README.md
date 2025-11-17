# 🏛️ KARE Hall Booking Management System

A complete web-based hall booking system developed for college, allowing students and staff to book event halls with admin approval workflow.

## 📚 Table of Contents

- [Features](#features)
- [Project Structure](#project-structure)
- [Setup Instructions](#setup-instructions)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [How It Works](#how-it-works)

---

## ✨ Features

- ✅ **User Portal**: Students/staff can browse halls and submit booking requests
- ✅ **Admin Panel**: Approve/reject bookings, manage halls and facilities
- ✅ **Hall Management**: Add, edit, delete halls with custom facilities
- ✅ **Per-Hall Facilities**: Each hall can have its own set of facilities
- ✅ **Email Notifications**: Admin receives email when new booking is created
- ✅ **Booking Status**: Track pending, approved, and rejected bookings
- ✅ **Conflict Detection**: Prevents double-booking of same time slot
- ✅ **File Uploads**: Support for event brochures and approval letters

---

## 📁 Project Structure

```
KAREHALL/
├── kare-backend/              # Admin Panel Application
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   └── EventController.php    # Admin booking management
│   │   └── Mail/
│   │       └── NewBookingNotification.php
│   ├── resources/views/
│   │   ├── dashboard.blade.php
│   │   └── sections/
│   │       ├── hall-management.blade.php
│   │       └── admin-management.blade.php
│   ├── routes/web.php
│   └── .env                   # Backend configuration
│
├── kare-frontend/             # User Portal Application
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   └── UserEventController.php # User booking management
│   │   └── Models/
│   │       └── Event.php
│   ├── resources/views/kare/
│   │   ├── halls.blade.php
│   │   ├── book.blade.php
│   │   └── my-bookings.blade.php
│   ├── routes/web.php
│   └── .env                   # Frontend configuration
│
└── sample/
    └── mssql/
        └── create_all_tables.sql    # Database setup script
```

---

## 🚀 Setup Instructions

### Prerequisites

- PHP 8.3 or higher
- Composer
- Microsoft SQL Server
- Web server (Apache/Nginx) or PHP built-in server

### Step 1: Install Dependencies

```bash
# Backend
cd kare-backend
composer install

# Frontend
cd kare-frontend
composer install
```

### Step 2: Database Setup

**Option A: Using SQL Script (Recommended)**

1. Open SQL Server Management Studio (SSMS)
2. Create database: `CREATE DATABASE event_hall_booking;`
3. Open `sample/mssql/create_all_tables.sql`
4. Execute the script (F5)
5. ✅ All tables created with **empty data** (no bookings)

**Option B: Using Laravel Migrations**

```bash
cd kare-backend
php artisan migrate

cd ../kare-frontend
php artisan migrate
```

### Step 3: Configure Environment

Update `.env` files in both `kare-backend` and `kare-frontend`:

```env
DB_CONNECTION=sqlsrv
DB_HOST=localhost
DB_PORT=1433
DB_DATABASE=event_hall_booking
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 4: Generate Keys & Setup Storage

```bash
# Backend
cd kare-backend
php artisan key:generate
php artisan storage:link

# Frontend
cd kare-frontend
php artisan key:generate
php artisan storage:link
```

### Step 5: Start Servers

**Terminal 1 - Backend (Admin):**
```bash
cd kare-backend
php artisan serve --port=8000
```
Access: http://127.0.0.1:8000

**Terminal 2 - Frontend (Users):**
```bash
cd kare-frontend
php artisan serve --port=8001
```
Access: http://127.0.0.1:8001

---

## 🗄️ Database Setup

### What Happens When College Sets Up?

1. **College creates a new database** (or uses existing)
2. **College runs the SQL script** (`sample/mssql/create_all_tables.sql`)
3. **All tables are created automatically** ✅
4. **Database starts EMPTY** - no bookings, no halls (fresh start!)
5. **College updates `.env` files** with their database credentials
6. **Everything works!** 🎉

### Tables Created:

- `halls` - Hall information
- `hall_facilities` - Facilities for each hall
- `events` - Booking requests
- `admin_settings` - Admin email for notifications
- `users`, `sessions`, `cache`, `jobs` - Laravel system tables

### Important Notes:

- ✅ **No data is inserted** - completely empty database
- ✅ **Tables created automatically** when SQL script runs
- ✅ **College just updates credentials** in `.env` files
- ✅ **No manual table creation needed**

---

## ⚙️ Configuration

### Email Setup (Optional)

For email notifications, add to `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="KARE Hall Booking"
```

Then add admin email in admin panel: `http://127.0.0.1:8000/admin-management`

---

## 🔄 How It Works

### For Users (Frontend - Port 8001):

1. Browse available halls
2. Select a hall and fill booking form
3. Upload required documents
4. Submit booking request
5. View booking status (Pending/Approved/Rejected)

### For Admin (Backend - Port 8000):

1. View all booking requests
2. Approve or reject bookings
3. Manage halls (add/edit/delete)
4. Manage facilities for each hall
5. Set admin email for notifications

### Database Flow:

- Both applications use **same database**
- Backend and Frontend connect to **same SQL Server**
- All data stored in **MS SQL Server**
- Tables created once, used by both apps

---

## 📝 For College Submission

### What to Include:

1. ✅ Complete project folder
2. ✅ `SETUP_GUIDE_FOR_COLLEGE.md` - Detailed setup instructions
3. ✅ `QUICK_START.md` - Quick 5-minute setup
4. ✅ `sample/mssql/create_all_tables.sql` - Database script
5. ✅ This README.md

### What College Needs to Do:

1. Install PHP, Composer, SQL Server
2. Run the SQL script to create tables
3. Update `.env` files with their database credentials
4. Run `composer install` in both folders
5. Start both servers
6. Done! ✅

---

## 🆘 Troubleshooting

**Problem**: "Table doesn't exist"
- **Solution**: Run `sample/mssql/create_all_tables.sql` again

**Problem**: "Database connection failed"
- **Solution**: Check SQL Server is running and credentials in `.env`

**Problem**: "Class not found"
- **Solution**: Run `composer install` in both folders

---

## 📧 Support

For setup issues, refer to:
- `SETUP_GUIDE_FOR_COLLEGE.md` - Complete guide
- `QUICK_START.md` - Fast setup

---

## 🎉 Success!

Once setup is complete:
1. Add halls in admin panel
2. Add facilities for each hall
3. Set admin email
4. Start accepting bookings!

**Good luck with your project! 🚀**
