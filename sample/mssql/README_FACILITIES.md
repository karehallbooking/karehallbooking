# Facilities Management - Database Setup

This document explains how to set up the facilities management system in MS SQL Server.

## Database Tables Created

1. **facilities** - Master table storing all available facilities
2. **halls** - Hall information table
3. **hall_facilities** - Pivot table linking halls to facilities (many-to-many relationship)

## Running the SQL Script

### Option 1: Using sqlcmd (Command Line)

```bash
sqlcmd -S <server_name> -d <database_name> -U <username> -P <password> -i create_all_tables.sql
```

Example:
```bash
sqlcmd -S localhost -d KAREHALL -U sa -P YourPassword -i create_all_tables.sql
```

### Option 2: Using SQL Server Management Studio (SSMS)

1. Open SQL Server Management Studio
2. Connect to your SQL Server instance
3. Open the `create_all_tables.sql` file
4. Select your database from the dropdown
5. Click "Execute" or press F5

### Option 3: Using Azure Data Studio

1. Open Azure Data Studio
2. Connect to your SQL Server
3. Open the `create_all_tables.sql` file
4. Click "Run" button

## Features Implemented

### Admin Features:
- ✅ Add new facilities with name and description
- ✅ Edit existing facilities
- ✅ Delete/deactivate facilities (soft delete if in use)
- ✅ Assign facilities to halls when creating/editing halls
- ✅ View all facilities in a dedicated management modal

### User Features:
- ✅ View facilities for each hall in the user dashboard
- ✅ See available facilities when booking a hall
- ✅ Facilities are loaded from database (not hardcoded)

## Database Schema

### facilities Table
- `id` (BIGINT, Primary Key, Identity)
- `name` (NVARCHAR(255), Unique, Not Null)
- `description` (NVARCHAR(MAX), Nullable)
- `is_active` (BIT, Default: 1)
- `created_at` (DATETIME2)
- `updated_at` (DATETIME2)

### halls Table
- `id` (BIGINT, Primary Key, Identity)
- `name` (NVARCHAR(255), Unique, Not Null)
- `capacity` (INT, Not Null)
- `status` (NVARCHAR(32), Check Constraint: Available/Not Available/Maintenance)
- `description` (NVARCHAR(MAX), Nullable)
- `location` (NVARCHAR(255), Nullable)
- `created_at` (DATETIME2)
- `updated_at` (DATETIME2)

### hall_facilities Table (Pivot)
- `id` (BIGINT, Primary Key, Identity)
- `hall_id` (BIGINT, Foreign Key to halls)
- `facility_id` (BIGINT, Foreign Key to facilities)
- `created_at` (DATETIME2)
- Unique constraint on (hall_id, facility_id)

## Default Facilities

The script automatically inserts 10 default facilities:
1. Audio System
2. Projector
3. Air Conditioning
4. Stage
5. WiFi
6. Whiteboard
7. Conference Table
8. Microphones
9. Lighting
10. Catering Setup

## Notes

- Facilities are stored in a normalized database structure (not JSON)
- The system supports many-to-many relationship between halls and facilities
- Facilities can be soft-deleted (marked inactive) if they're in use
- All changes are stored in the database tables, not duplicated

