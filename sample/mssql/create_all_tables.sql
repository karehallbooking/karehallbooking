/*
  MS SQL Server DDL for KAREHALL project tables
  - users, password_reset_tokens, sessions
  - events
  - cache, cache_locks
  - jobs, job_batches, failed_jobs

  Notes:
  - Uses NVARCHAR for Unicode safety
  - Uses DATETIME2 for timestamps
  - Emulates Laravel enum with CHECK constraint
  - JSON stored as NVARCHAR(MAX); validated with ISJSON where helpful
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- Optional: Create database if not exists (sqlcmd variables can override DB name)
-- :setvar DatabaseName KAREHALL
IF DB_ID(DB_NAME()) IS NULL BEGIN
    DECLARE @db sysname = DB_NAME();
    DECLARE @sql NVARCHAR(MAX) = N'CREATE DATABASE [' + @db + N']';
    EXEC(@sql);
END
GO

/* =====================================================
   users, password_reset_tokens, sessions
   ===================================================== */
IF OBJECT_ID(N'dbo.users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.users (
        id               BIGINT IDENTITY(1,1) PRIMARY KEY,
        name             NVARCHAR(255) NOT NULL,
        email            NVARCHAR(255) NOT NULL UNIQUE,
        email_verified_at DATETIME2(0) NULL,
        password         NVARCHAR(255) NOT NULL,
        remember_token   NVARCHAR(100) NULL,
        created_at       DATETIME2(0) NULL,
        updated_at       DATETIME2(0) NULL
    );
END
GO

IF OBJECT_ID(N'dbo.password_reset_tokens', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.password_reset_tokens (
        email      NVARCHAR(255) NOT NULL PRIMARY KEY,
        token      NVARCHAR(255) NOT NULL,
        created_at DATETIME2(0) NULL
    );
END
GO

IF OBJECT_ID(N'dbo.sessions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.sessions (
        id            NVARCHAR(255) NOT NULL PRIMARY KEY,
        user_id       BIGINT NULL,
        ip_address    NVARCHAR(45) NULL,
        user_agent    NVARCHAR(MAX) NULL,
        payload       NVARCHAR(MAX) NOT NULL,
        last_activity INT NOT NULL
    );
    CREATE INDEX IX_sessions_user_id ON dbo.sessions(user_id);
    CREATE INDEX IX_sessions_last_activity ON dbo.sessions(last_activity);
END
GO

/* =====================================================
   events
   ===================================================== */
IF OBJECT_ID(N'dbo.events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.events (
        id                     BIGINT IDENTITY(1,1) PRIMARY KEY,
        hall_name              NVARCHAR(255) NOT NULL,
        event_date             DATE NOT NULL,
        time_from              TIME(0) NOT NULL,
        time_to                TIME(0) NOT NULL,
        organizer_name         NVARCHAR(255) NOT NULL,
        organizer_email        NVARCHAR(255) NOT NULL,
        organizer_phone        NVARCHAR(20) NOT NULL,
        organizer_department   NVARCHAR(255) NOT NULL,
        organizer_designation  NVARCHAR(255) NULL,
        purpose                NVARCHAR(MAX) NOT NULL,
        seating_capacity       INT NOT NULL,
        facilities_required    NVARCHAR(MAX) NULL,
        status                 NVARCHAR(20) NOT NULL CONSTRAINT CK_events_status CHECK (status IN (N'pending', N'approved', N'rejected')),
        admin_comments         NVARCHAR(MAX) NULL,
        approved_by            NVARCHAR(255) NULL,
        rejected_by            NVARCHAR(255) NULL,
        created_by             NVARCHAR(255) NULL,
        updated_by             NVARCHAR(255) NULL,
        created_at             DATETIME2(0) NULL,
        updated_at             DATETIME2(0) NULL,
        deleted_at             DATETIME2(0) NULL
    );
    -- Optional JSON validation (requires SQL Server 2016+)
    -- ALTER TABLE dbo.events WITH NOCHECK ADD CONSTRAINT CK_events_facilities_required_isjson CHECK (facilities_required IS NULL OR ISJSON(facilities_required) = 1);

    CREATE INDEX IX_events_hall_eventdate ON dbo.events(hall_name, event_date);
    CREATE INDEX IX_events_status ON dbo.events(status);
    CREATE INDEX IX_events_event_date ON dbo.events(event_date);
    CREATE INDEX IX_events_organizer_email ON dbo.events(organizer_email);
    CREATE INDEX IX_events_created_at ON dbo.events(created_at);
END
GO

/* =====================================================
   cache, cache_locks
   ===================================================== */
IF OBJECT_ID(N'dbo.cache', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.cache (
        [key]       NVARCHAR(255) NOT NULL PRIMARY KEY,
        [value]     NVARCHAR(MAX) NOT NULL,
        expiration  INT NOT NULL
    );
END
GO

IF OBJECT_ID(N'dbo.cache_locks', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.cache_locks (
        [key]       NVARCHAR(255) NOT NULL PRIMARY KEY,
        owner       NVARCHAR(255) NOT NULL,
        expiration  INT NOT NULL
    );
END
GO

/* =====================================================
   jobs, job_batches, failed_jobs
   ===================================================== */
IF OBJECT_ID(N'dbo.jobs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.jobs (
        id            BIGINT IDENTITY(1,1) PRIMARY KEY,
        queue         NVARCHAR(255) NOT NULL,
        payload       NVARCHAR(MAX) NOT NULL,
        attempts      TINYINT NOT NULL,
        reserved_at   INT NULL,
        available_at  INT NOT NULL,
        created_at    INT NOT NULL
    );
    CREATE INDEX IX_jobs_queue ON dbo.jobs(queue);
END
GO

IF OBJECT_ID(N'dbo.job_batches', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.job_batches (
        id              NVARCHAR(255) NOT NULL PRIMARY KEY,
        name            NVARCHAR(255) NOT NULL,
        total_jobs      INT NOT NULL,
        pending_jobs    INT NOT NULL,
        failed_jobs     INT NOT NULL,
        failed_job_ids  NVARCHAR(MAX) NOT NULL,
        options         NVARCHAR(MAX) NULL,
        cancelled_at    INT NULL,
        created_at      INT NOT NULL,
        finished_at     INT NULL
    );
END
GO

IF OBJECT_ID(N'dbo.failed_jobs', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.failed_jobs (
        id          BIGINT IDENTITY(1,1) PRIMARY KEY,
        uuid        NVARCHAR(255) NOT NULL UNIQUE,
        connection  NVARCHAR(MAX) NOT NULL,
        queue       NVARCHAR(MAX) NOT NULL,
        payload     NVARCHAR(MAX) NOT NULL,
        exception   NVARCHAR(MAX) NOT NULL,
        failed_at   DATETIME2(0) NOT NULL CONSTRAINT DF_failed_jobs_failed_at DEFAULT (SYSUTCDATETIME())
    );
END
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
   hall_facilities - Stores facilities per hall (facility_name stored directly)
   ===================================================== */
IF OBJECT_ID(N'dbo.hall_facilities', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.hall_facilities (
        id               BIGINT IDENTITY(1,1) PRIMARY KEY,
        hall_id          BIGINT NOT NULL,
        facility_name    NVARCHAR(255) NOT NULL,
        created_at       DATETIME2(0) NULL CONSTRAINT DF_hall_facilities_created_at DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT FK_hall_facilities_hall FOREIGN KEY (hall_id) REFERENCES dbo.halls(id) ON DELETE CASCADE,
        CONSTRAINT UQ_hall_facilities_unique UNIQUE (hall_id, facility_name)
    );
    CREATE INDEX IX_hall_facilities_hall_id ON dbo.hall_facilities(hall_id);
    CREATE INDEX IX_hall_facilities_facility_name ON dbo.hall_facilities(facility_name);
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

/* =====================================================
   admin_settings - Store admin email for notifications
   ===================================================== */
IF OBJECT_ID(N'dbo.admin_settings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.admin_settings (
        id               BIGINT IDENTITY(1,1) PRIMARY KEY,
        admin_email      NVARCHAR(255) NOT NULL UNIQUE,
        is_active        BIT NOT NULL CONSTRAINT DF_admin_settings_is_active DEFAULT (1),
        created_at       DATETIME2(0) NULL CONSTRAINT DF_admin_settings_created_at DEFAULT (SYSUTCDATETIME()),
        updated_at       DATETIME2(0) NULL CONSTRAINT DF_admin_settings_updated_at DEFAULT (SYSUTCDATETIME())
    );
    CREATE INDEX IX_admin_settings_admin_email ON dbo.admin_settings(admin_email);
    CREATE INDEX IX_admin_settings_is_active ON dbo.admin_settings(is_active);
END
GO

PRINT 'All tables created successfully!';
GO


