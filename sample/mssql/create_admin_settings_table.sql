/*
  Create admin_settings table to store admin email for notifications
*/

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
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

PRINT 'admin_settings table created successfully!';
GO

