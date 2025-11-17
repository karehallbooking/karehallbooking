@echo off
REM Database Setup Script for KARE Hall Booking System
REM This script runs the SQL script to create all database tables

echo ========================================
echo  KARE Hall Booking - Database Setup
echo ========================================
echo.

REM Check if sqlcmd is available
where sqlcmd >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: sqlcmd is not installed or not in PATH
    echo.
    echo Please install SQL Server Command Line Utilities:
    echo https://aka.ms/sqlcmd
    echo.
    echo OR use SQL Server Management Studio (SSMS) method instead.
    echo See: HOW_TO_RUN_SQL_SCRIPT.md
    echo.
    pause
    exit /b 1
)

REM Configuration - UPDATE THESE VALUES
set SERVER=localhost
set DATABASE=event_hall_booking
set USERNAME=kare_user_1
set PASSWORD=Karehall@123
set SCRIPT_PATH=sample\mssql\create_all_tables.sql

echo Server: %SERVER%
echo Database: %DATABASE%
echo Username: %USERNAME%
echo.
echo NOTE: Please update SERVER, DATABASE, USERNAME, and PASSWORD in this file
echo       if your credentials are different!
echo.
echo Press any key to continue or Ctrl+C to cancel...
pause >nul
echo.

echo Creating database tables...
echo.

REM Run SQL script
sqlcmd -S %SERVER% -U %USERNAME% -P %PASSWORD% -d %DATABASE% -i "%SCRIPT_PATH%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  SUCCESS! Database setup completed!
    echo ========================================
    echo.
    echo All tables have been created successfully.
    echo You can now start using the application!
) else (
    echo.
    echo ========================================
    echo  ERROR! Database setup failed!
    echo ========================================
    echo.
    echo Please check:
    echo 1. SQL Server is running
    echo 2. Database exists: %DATABASE%
    echo 3. Username and password are correct
    echo 4. You have permission to create tables
    echo.
    echo For help, see: HOW_TO_RUN_SQL_SCRIPT.md
)

echo.
pause


