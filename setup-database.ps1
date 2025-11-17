# Database Setup Script for KARE Hall Booking System
# PowerShell version - More flexible and informative

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " KARE Hall Booking - Database Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration - UPDATE THESE VALUES
$SERVER = "localhost"
$DATABASE = "event_hall_booking"
$USERNAME = "kare_user_1"
$PASSWORD = "Karehall@123"
$SCRIPT_PATH = "sample\mssql\create_all_tables.sql"

# Check if sqlcmd is available
try {
    $null = Get-Command sqlcmd -ErrorAction Stop
    Write-Host "✓ sqlcmd found" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: sqlcmd is not installed or not in PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install SQL Server Command Line Utilities:" -ForegroundColor Yellow
    Write-Host "https://aka.ms/sqlcmd" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "OR use SQL Server Management Studio (SSMS) method instead." -ForegroundColor Yellow
    Write-Host "See: HOW_TO_RUN_SQL_SCRIPT.md" -ForegroundColor Yellow
    exit 1
}

# Check if script file exists
if (-not (Test-Path $SCRIPT_PATH)) {
    Write-Host "✗ ERROR: SQL script not found: $SCRIPT_PATH" -ForegroundColor Red
    Write-Host "Please make sure you're in the project root folder." -ForegroundColor Yellow
    exit 1
}

Write-Host "Configuration:" -ForegroundColor Cyan
Write-Host "  Server: $SERVER" -ForegroundColor White
Write-Host "  Database: $DATABASE" -ForegroundColor White
Write-Host "  Username: $USERNAME" -ForegroundColor White
Write-Host "  Script: $SCRIPT_PATH" -ForegroundColor White
Write-Host ""
Write-Host "NOTE: Update SERVER, DATABASE, USERNAME, and PASSWORD in this file" -ForegroundColor Yellow
Write-Host "      if your credentials are different!" -ForegroundColor Yellow
Write-Host ""

$confirmation = Read-Host "Press Enter to continue or Ctrl+C to cancel"
Write-Host ""

Write-Host "Creating database tables..." -ForegroundColor Cyan
Write-Host ""

# Run SQL script
$result = sqlcmd -S $SERVER -U $USERNAME -P $PASSWORD -d $DATABASE -i $SCRIPT_PATH 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host " SUCCESS! Database setup completed!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "All tables have been created successfully." -ForegroundColor Green
    Write-Host "You can now start using the application!" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host " ERROR! Database setup failed!" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please check:" -ForegroundColor Yellow
    Write-Host "  1. SQL Server is running" -ForegroundColor White
    Write-Host "  2. Database exists: $DATABASE" -ForegroundColor White
    Write-Host "  3. Username and password are correct" -ForegroundColor White
    Write-Host "  4. You have permission to create tables" -ForegroundColor White
    Write-Host ""
    Write-Host "Error details:" -ForegroundColor Yellow
    Write-Host $result -ForegroundColor Red
    Write-Host ""
    Write-Host "For help, see: HOW_TO_RUN_SQL_SCRIPT.md" -ForegroundColor Yellow
    exit 1
}


