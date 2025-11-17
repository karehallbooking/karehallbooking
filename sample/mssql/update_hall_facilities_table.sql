/*
  Update hall_facilities table to store facility_name directly
  This allows each hall to have its own facilities (not shared)
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- Drop existing foreign key constraints if they exist
IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_hall_facilities_facility')
BEGIN
    ALTER TABLE dbo.hall_facilities DROP CONSTRAINT FK_hall_facilities_facility;
END
GO

-- Add facility_name column if it doesn't exist
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'dbo.hall_facilities') AND name = 'facility_name')
BEGIN
    ALTER TABLE dbo.hall_facilities ADD facility_name NVARCHAR(255) NULL;
END
GO

-- Migrate data from facilities table if facility_id exists
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'dbo.hall_facilities') AND name = 'facility_id')
    AND EXISTS (SELECT * FROM sys.tables WHERE name = 'facilities')
BEGIN
    UPDATE hf
    SET hf.facility_name = f.name
    FROM dbo.hall_facilities hf
    INNER JOIN dbo.facilities f ON hf.facility_id = f.id
    WHERE hf.facility_name IS NULL;
END
GO

-- Make facility_name NOT NULL after migration
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'dbo.hall_facilities') AND name = 'facility_name')
BEGIN
    ALTER TABLE dbo.hall_facilities ALTER COLUMN facility_name NVARCHAR(255) NOT NULL;
END
GO

-- Drop facility_id column if it exists (optional - comment out if you want to keep it)
-- ALTER TABLE dbo.hall_facilities DROP COLUMN facility_id;
-- GO

-- Update unique constraint to use facility_name instead of facility_id
IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'UQ_hall_facilities_unique')
BEGIN
    ALTER TABLE dbo.hall_facilities DROP CONSTRAINT UQ_hall_facilities_unique;
END
GO

-- Add new unique constraint on hall_id and facility_name
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'UQ_hall_facilities_unique')
BEGIN
    ALTER TABLE dbo.hall_facilities 
    ADD CONSTRAINT UQ_hall_facilities_unique UNIQUE (hall_id, facility_name);
END
GO

PRINT 'hall_facilities table updated successfully!';
GO

