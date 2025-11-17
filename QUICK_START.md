# ⚡ Quick Start Guide (5 Minutes)

## For College IT Team - Fast Setup

### 1️⃣ Create Database
```sql
-- In SQL Server Management Studio
CREATE DATABASE event_hall_booking;
GO
```

### 2️⃣ Run SQL Script
- Open: `sample/mssql/create_all_tables.sql`
- Execute in SSMS
- ✅ All tables created!

### 3️⃣ Update .env Files

**kare-backend/.env:**
```env
DB_HOST=localhost
DB_DATABASE=event_hall_booking
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**kare-frontend/.env:**
```env
DB_HOST=localhost
DB_DATABASE=event_hall_booking
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4️⃣ Install & Run

```bash
# Terminal 1 - Backend
cd kare-backend
composer install
php artisan key:generate
php artisan storage:link
php artisan serve --port=8000

# Terminal 2 - Frontend
cd kare-frontend
composer install
php artisan key:generate
php artisan storage:link
php artisan serve --port=8001
```

### 5️⃣ Access

- Admin: http://127.0.0.1:8000
- User Portal: http://127.0.0.1:8001

**Done! 🎉**

