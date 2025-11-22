# Student Dashboard - Setup Commands

## PowerShell Commands to Set Up and Run Student Dashboard

### Step 1: Create New Laravel Project (if not already done)

```powershell
# Navigate to parent directory
cd C:\Users\sande\Downloads\KAREHALL

# Create new Laravel project (this will create eventbookingstudents_new)
composer create-project laravel/laravel eventbookingstudents_new

# Move the new Laravel files to eventbookingstudents (backup existing first)
# OR rename and merge
```

### Step 2: Alternative - Initialize in Existing Folder

```powershell
# Navigate to student folder
cd C:\Users\sande\Downloads\KAREHALL\eventbookingstudents

# Initialize Composer (if composer.json doesn't exist)
# Copy composer.json from admin folder and modify, OR create new Laravel project

# Better approach: Create Laravel project in temp location, then copy structure
cd C:\Users\sande\Downloads\KAREHALL
composer create-project laravel/laravel eventbookingstudents_temp

# Copy essential Laravel files to eventbookingstudents
Copy-Item -Path "eventbookingstudents_temp\*" -Destination "eventbookingstudents\" -Recurse -Force -Exclude "app","resources","routes"

# Remove temp folder
Remove-Item -Path "eventbookingstudents_temp" -Recurse -Force
```

### Step 3: Install Dependencies

```powershell
cd C:\Users\sande\Downloads\KAREHALL\eventbookingstudents

# Install Composer packages
composer install

# Install additional packages (same as admin)
composer require bacon/bacon-qr-code
composer require barryvdh/laravel-dompdf
```

### Step 4: Configure Environment

```powershell
# Copy .env.example to .env (if exists)
if (Test-Path ".env.example") { Copy-Item ".env.example" ".env" }

# Or create .env manually with database settings
# Use same database as admin panel

# Generate application key
php artisan key:generate
```

### Step 5: Configure Database in .env

Edit `.env` file and set:
```
DB_CONNECTION=sqlsrv
DB_HOST=your_sql_server_host
DB_PORT=1433
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 6: Run Migrations (if needed)

```powershell
# Run migrations (tables should already exist from admin panel)
php artisan migrate
```

### Step 7: Start Development Server

```powershell
# Start server on different port (8001) to avoid conflict with admin (9000)
php artisan serve --port=8001
```

### Step 8: Access Dashboard

Open browser: `http://127.0.0.1:8001/student/dashboard`

---

## Quick Setup (All-in-One)

```powershell
# Navigate to parent directory
cd C:\Users\sande\Downloads\KAREHALL

# Create new Laravel project
composer create-project laravel/laravel eventbookingstudents_new

# Copy your existing app files to the new project
Copy-Item -Path "eventbookingstudents\app\*" -Destination "eventbookingstudents_new\app\" -Recurse -Force
Copy-Item -Path "eventbookingstudents\routes\*" -Destination "eventbookingstudents_new\routes\" -Recurse -Force
Copy-Item -Path "eventbookingstudents\resources\*" -Destination "eventbookingstudents_new\resources\" -Recurse -Force

# Navigate to new project
cd eventbookingstudents_new

# Install dependencies
composer install
composer require bacon/bacon-qr-code barryvdh/laravel-dompdf

# Setup environment
Copy-Item ".env.example" ".env"
php artisan key:generate

# Configure .env with your database credentials

# Start server
php artisan serve --port=8001
```

---

## Troubleshooting

```powershell
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check routes
php artisan route:list | Select-String "student"

# Check if server is running
netstat -ano | findstr :8001
```

