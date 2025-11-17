/*
  MS SQL Server DDL for Facilities and Halls Management
  - facilities: Master table for all available facilities
  - halls: Hall information table
  - hall_facilities: Pivot table linking halls to facilities (many-to-many)
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

/* =====================================================
   facilities - Master table for all facilities
   ===================================================== */
IF OBJECT_ID(N'dbo.facilities', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.facilities (
        id               BIGINT IDENTITY(1,1) PRIMARY KEY,
        name             NVARCHAR(255) NOT NULL UNIQUE,
        description      NVARCHAR(MAX) NULL,
        is_active        BIT NOT NULL CONSTRAINT DF_facilities_is_active DEFAULT (1),
        created_at       DATETIME2(0) NULL CONSTRAINT DF_facilities_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at       DATETIME2(0) NULL CONSTRAINT DF_facilities_updated_at DEFAULT (SYSUTCDATETIME())
    );
    CREATE INDEX IX_facilities_name ON dbo.facilities(name);
    CREATE INDEX IX_facilities_is_active ON dbo.facilities(is_active);
END
GO

/* =====================================================
   halls - Hall information table
   ===================================================== */
IF OBJECT_ID(N'dbo.halls', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.halls (
        id               BIGINT IDENTITY(1,1) PRIMARY KEY,
        name             NVARCHAR(255) NOT NULL UNIQUE,
        capacity         INT NOT NULL,
        status           NVARCHAR(32) NOT NULL CONSTRAINT CK_halls_status CHECK (status IN (N'Available', N'Not Available', N'Maintenance')),
        description      NVARCHAR(MAX) NULL,
        location         NVARCHAR(255) NULL,
        created_at       DATETIME2(0) NULL CONSTRAINT DF_halls_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at       DATETIME2(0) NULL CONSTRAINT DF_halls_updated_at DEFAULT (SYSUTCDATETIME())
    );
    CREATE INDEX IX_halls_name ON dbo.halls(name);
    CREATE INDEX IX_halls_status ON dbo.halls(status);
END
GO

/* =====================================================
   hall_facilities - Pivot table (many-to-many relationship)
   ===================================================== */
IF OBJECT_ID(N'dbo.hall_facilities', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.hall_facilities (
        id               BIGINT IDENTITY(1,1) PRIMARY KEY,
        hall_id          BIGINT NOT NULL,
        facility_id      BIGINT NOT NULL,
        created_at       DATETIME2(0) NULL CONSTRAINT DF_hall_facilities_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT FK_hall_facilities_hall FOREIGN KEY (hall_id) REFERENCES dbo.halls(id) ON DELETE CASCADE,
        CONSTRAINT FK_hall_facilities_facility FOREIGN KEY (facility_id) REFERENCES dbo.facilities(id) ON DELETE CASCADE,
        CONSTRAINT UQ_hall_facilities_unique UNIQUE (hall_id, facility_id)
    );
    CREATE INDEX IX_hall_facilities_hall_id ON dbo.hall_facilities(hall_id);
    CREATE INDEX IX_hall_facilities_facility_id ON dbo.hall_facilities(facility_id);
END
GO

/* =====================================================
   Insert default facilities
   ===================================================== */
IF NOT EXISTS (SELECT 1 FROM dbo.facilities WHERE name = N'Audio System')
BEGIN
    INSERT INTO dbo.facilities (name, description, is_active) VALUES
    (N'Audio System', N'Professional audio system with speakers', 1),
    (N'Projector', N'HD Projector for presentations', 1),
    (N'Air Conditioning', N'Climate control system', 1),
    (N'Stage', N'Raised stage platform', 1),
    (N'WiFi', N'High-speed wireless internet', 1),
    (N'Whiteboard', N'Whiteboard for presentations', 1),
    (N'Conference Table', N'Large conference table', 1),
    (N'Microphones', N'Wireless and wired microphones', 1),
    (N'Lighting', N'Professional lighting system', 1),
    (N'Catering Setup', N'Catering facilities available', 1);
END
GO

PRINT 'Facilities and Halls tables created successfully!';
GO

