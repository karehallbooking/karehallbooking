# 🎓 KARE Hall Booking System - Setup Guide for College

## 📋 What is This?

This is a **Hall Booking Management System** developed for college. It allows:
- Students/Staff to book halls for events
- Admin to approve/reject bookings
- Manage halls and facilities
- Email notifications for new bookings

---

## 🚀 Quick Setup (For College IT Team)

### Step 1: Install Required Software

1. **PHP 8.3+** - Download from https://www.php.net/downloads
2. **Composer** - Download from https://getcomposer.org
3. **Microsoft SQL Server** - Already installed (or install SQL Server Express)
4. **Web Server** - Apache/Nginx (or use PHP built-in server for testing)

### Step 2: Get the Project Files

Extract the project folder to a location like:
- `C:\xampp\htdocs\karehall` (if using XAMPP)
- Or any folder you prefer

### Step 3: Setup Database

#### Option A: Using SQL Script (EASIEST - Recommended) ✅

1. Open **SQL Server Management Studio (SSMS)**
2. Connect to your SQL Server
3. Create a new database (or use existing):
   ```sql
   CREATE DATABASE event_hall_booking;
   GO
   ```
4. Open the file: `sample/mssql/create_all_tables.sql`
5. Make sure the database is selected in SSMS
6. Click **Execute** (F5)
7. ✅ **All tables will be created automatically with empty data!**

#### Option B: Using Laravel Migrations

1. Open terminal/command prompt in project folder
2. Navigate to `kare-backend` folder:
   ```bash
   cd kare-backend
   ```
3. Run migration:
   ```bash
   php artisan migrate
   ```
4. Navigate to `kare-frontend` folder:
   ```bash
   cd ../kare-frontend
   php artisan migrate
   ```

### Step 4: Configure Database Connection

#### For Backend (`kare-backend/.env`):

1. Open `kare-backend/.env` file
2. Update these lines with **YOUR college database credentials**:

```env
DB_CONNECTION=sqlsrv
DB_HOST=localhost          # Your SQL Server name/IP
DB_PORT=1433               # SQL Server port (usually 1433)
DB_DATABASE=event_hall_booking    # Your database name
DB_USERNAME=your_username         # Your SQL Server username
DB_PASSWORD=your_password         # Your SQL Server password
DB_CHARSET=utf8
DB_COLLATION=utf8_unicode_ci
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=true
```

#### For Frontend (`kare-frontend/.env`):

1. Open `kare-frontend/.env` file
2. Update with **SAME database credentials**:

```env
DB_CONNECTION=sqlsrv
DB_HOST=localhost
DB_PORT=1433
DB_DATABASE=event_hall_booking
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8
DB_COLLATION=utf8_unicode_ci
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=true
```

### Step 5: Install Dependencies

Open terminal in **each** project folder and run:

```bash
# For Backend
cd kare-backend
composer install

# For Frontend  
cd kare-frontend
composer install
```

### Step 6: Generate Application Key

```bash
# Backend
cd kare-backend
php artisan key:generate

# Frontend
cd kare-frontend
php artisan key:generate
```

### Step 7: Setup Storage Link

```bash
# Backend
cd kare-backend
php artisan storage:link

# Frontend
cd kare-frontend
php artisan storage:link
```

### Step 8: Configure Email (Optional - for notifications)

If you want email notifications, update `.env` files:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-college-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-college-email@gmail.com
MAIL_FROM_NAME="KARE Hall Booking"
```

### Step 9: Start the Application

Open **TWO** terminal windows:

**Terminal 1 - Backend (Admin Panel):**
```bash
cd kare-backend
php artisan serve --port=8000
```
Access at: `http://127.0.0.1:8000`

**Terminal 2 - Frontend (User Portal):**
```bash
cd kare-frontend
php artisan serve --port=8001
```
Access at: `http://127.0.0.1:8001`

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Database connection works (no errors in terminal)
- [ ] Backend admin panel opens at `http://127.0.0.1:8000`
- [ ] Frontend user portal opens at `http://127.0.0.1:8001`
- [ ] Can add halls in admin panel
- [ ] Can view halls in user portal
- [ ] Can create a test booking

---

## 📁 Project Structure

```
KAREHALL/
├── kare-backend/          # Admin Panel (Port 8000)
│   ├── app/
│   ├── database/
│   ├── resources/views/
│   └── .env               # Backend configuration
│
├── kare-frontend/         # User Portal (Port 8001)
│   ├── app/
│   ├── database/
│   ├── resources/views/
│   └── .env               # Frontend configuration
│
└── sample/
    └── mssql/
        └── create_all_tables.sql  # Database setup script
```

---

## 🎯 Important Notes

1. **Database Tables**: All tables are created **automatically** when you run the SQL script
2. **Empty Database**: The script creates tables with **NO data** - fresh start!
3. **Two Applications**: 
   - Backend (8000) = Admin panel
   - Frontend (8001) = User booking portal
4. **Same Database**: Both apps use the **same database** but different ports

---

## 🆘 Troubleshooting

### Problem: "Table doesn't exist" error
**Solution**: Run the SQL script again: `sample/mssql/create_all_tables.sql`

### Problem: "Connection refused" or database error
**Solution**: 
- Check SQL Server is running
- Verify database credentials in `.env` files
- Make sure database name exists

### Problem: "Class not found" errors
**Solution**: Run `composer install` in both folders

### Problem: Pages show 404 errors
**Solution**: 
- Make sure both servers are running (ports 8000 and 8001)
- Check routes in `routes/web.php`

---

## 📞 Support

If you face any issues during setup, check:
1. PHP version: `php -v` (should be 8.3+)
2. Composer: `composer --version`
3. SQL Server connection
4. `.env` file configuration

---

## 🎉 Success!

Once everything is working:
1. Add halls in admin panel
2. Add facilities for each hall
3. Set admin email for notifications
4. Start accepting bookings!

**Good luck with your project submission! 🚀**

