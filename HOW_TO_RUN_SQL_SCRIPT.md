# 🗄️ How to Run SQL Script - Complete Guide

## 📋 Methods to Run SQL Script

There are **3 ways** to run the SQL script. Choose the easiest for you!

---

## Method 1: Using SQL Server Management Studio (SSMS) - EASIEST ✅

### Step-by-Step:

1. **Open SQL Server Management Studio (SSMS)**
   - If you don't have it, download from: https://aka.ms/ssmsfullsetup

2. **Connect to SQL Server**
   - Server name: `localhost` or your SQL Server name
   - Authentication: SQL Server Authentication
   - Login: Your username
   - Password: Your password
   - Click **Connect**

3. **Create Database (if needed)**
   ```sql
   CREATE DATABASE event_hall_booking;
   GO
   ```

4. **Select Your Database**
   - In the left panel, expand "Databases"
   - Right-click on `event_hall_booking`
   - Click "New Query"
   - OR click on database name to select it

5. **Open SQL Script**
   - Click **File → Open → File**
   - Navigate to: `sample/mssql/create_all_tables.sql`
   - Click **Open**

6. **Execute Script**
   - Press **F5** key
   - OR click **Execute** button (green play icon)
   - Wait for "All tables created successfully!" message

7. **Verify**
   - Expand your database in left panel
   - Expand "Tables"
   - You should see all tables listed!

✅ **Done!**

---

## Method 2: Using Command Line (sqlcmd) - TERMINAL COMMAND

### Prerequisites:
- SQL Server Command Line Utilities installed
- Or use PowerShell with sqlcmd

### Step 1: Check if sqlcmd is installed

**Windows PowerShell:**
```powershell
sqlcmd -?
```

If you see help text, it's installed! If not, install it:
- Download: https://aka.ms/sqlcmd

### Step 2: Run SQL Script via Command Line

**Basic Command:**
```powershell
sqlcmd -S localhost -U your_username -P your_password -d event_hall_booking -i "sample\mssql\create_all_tables.sql"
```

**With Windows Authentication:**
```powershell
sqlcmd -S localhost -E -d event_hall_booking -i "sample\mssql\create_all_tables.sql"
```

**Full Example:**
```powershell
# Navigate to project folder
cd C:\Users\sande\Downloads\KAREHALL

# Run SQL script
sqlcmd -S localhost -U kare_user_1 -P Karehall@123 -d event_hall_booking -i "sample\mssql\create_all_tables.sql"
```

### Step 3: Verify Success

You should see:
```
All tables created successfully!
```

---

## Method 3: Using PowerShell Script (Automated)

### Create a PowerShell Script:

Create file: `setup-database.ps1`

```powershell
# Database Setup Script
# Usage: .\setup-database.ps1

$server = "localhost"
$database = "event_hall_booking"
$username = "kare_user_1"
$password = "Karehall@123"
$scriptPath = "sample\mssql\create_all_tables.sql"

Write-Host "Setting up database..." -ForegroundColor Green

# Run SQL script
sqlcmd -S $server -U $username -P $password -d $database -i $scriptPath

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Database setup completed successfully!" -ForegroundColor Green
} else {
    Write-Host "❌ Error occurred. Please check your credentials." -ForegroundColor Red
}
```

### Run the Script:

```powershell
# Make sure you're in project root folder
cd C:\Users\sande\Downloads\KAREHALL

# Run the script
.\setup-database.ps1
```

---

## Method 4: Using Laravel Migrations (Alternative)

If you prefer Laravel's migration system:

### Step 1: Ensure migrations exist

Check if you have migration files in:
- `kare-backend/database/migrations/`
- `kare-frontend/database/migrations/`

### Step 2: Run Migrations

**Backend:**
```bash
cd kare-backend
php artisan migrate
```

**Frontend:**
```bash
cd kare-frontend
php artisan migrate
```

**Note:** This method requires Laravel migration files. The SQL script method is recommended for college setup.

---

## 🔧 Command Line Options Explained

### sqlcmd Parameters:

| Parameter | Description | Example |
|-----------|-------------|---------|
| `-S` | Server name | `-S localhost` or `-S .\SQLEXPRESS` |
| `-U` | Username | `-U sa` |
| `-P` | Password | `-P MyPassword123` |
| `-E` | Windows Authentication | `-E` (no username/password needed) |
| `-d` | Database name | `-d event_hall_booking` |
| `-i` | Input file (SQL script) | `-i "path\to\script.sql"` |
| `-o` | Output file | `-o "output.txt"` |

### Common Server Names:

- `localhost` - Local SQL Server
- `.\SQLEXPRESS` - SQL Server Express instance
- `localhost\SQLEXPRESS` - Named instance
- `192.168.1.100` - Remote server IP

---

## ✅ Quick Reference Commands

### For Your Project:

```powershell
# Navigate to project
cd C:\Users\sande\Downloads\KAREHALL

# Method 1: SQL Server Authentication
sqlcmd -S localhost -U kare_user_1 -P Karehall@123 -d event_hall_booking -i "sample\mssql\create_all_tables.sql"

# Method 2: Windows Authentication (if you have Windows auth)
sqlcmd -S localhost -E -d event_hall_booking -i "sample\mssql\create_all_tables.sql"

# Method 3: SQL Server Express
sqlcmd -S .\SQLEXPRESS -U sa -P YourPassword -d event_hall_booking -i "sample\mssql\create_all_tables.sql"
```

---

## 🐛 Troubleshooting

### Problem: "sqlcmd is not recognized"
**Solution:** 
- Install SQL Server Command Line Utilities
- Or use SSMS method instead

### Problem: "Login failed"
**Solution:**
- Check username and password
- Try Windows Authentication: `-E` instead of `-U` and `-P`

### Problem: "Database does not exist"
**Solution:**
- Create database first:
  ```sql
  CREATE DATABASE event_hall_booking;
  GO
  ```

### Problem: "Cannot open file"
**Solution:**
- Check file path is correct
- Use full path: `C:\Users\sande\Downloads\KAREHALL\sample\mssql\create_all_tables.sql`
- Make sure file exists

---

## 📝 Recommended Method for College

**For College Submission, recommend:**

1. **SSMS Method** (Easiest - GUI)
   - Most colleges have SSMS
   - Visual interface
   - Easy to verify results

2. **Command Line Method** (If they prefer terminal)
   - Faster for automation
   - Can be scripted

---

## 🎯 Summary

| Method | Difficulty | Best For |
|--------|-----------|----------|
| SSMS (GUI) | ⭐ Easy | Beginners, College setup |
| sqlcmd (Terminal) | ⭐⭐ Medium | Automation, Scripts |
| PowerShell Script | ⭐⭐ Medium | Automated setup |
| Laravel Migrations | ⭐⭐⭐ Advanced | Developers familiar with Laravel |

**For college: Recommend SSMS method!** ✅

---

## 💡 Pro Tip

You can create a simple batch file for college:

**Create: `setup-database.bat`**
```batch
@echo off
echo Setting up database...
sqlcmd -S localhost -U kare_user_1 -P Karehall@123 -d event_hall_booking -i "sample\mssql\create_all_tables.sql"
echo.
echo Database setup complete!
pause
```

College just double-clicks the `.bat` file! 🚀


