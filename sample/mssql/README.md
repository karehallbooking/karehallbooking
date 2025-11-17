# KAREHALL MS SQL Setup (PowerShell)

This folder contains a ready-to-run script to create all required MS SQL tables for the project on a developer's laptop.

Contents:
- `create_all_tables.sql`: Combined DDL for all tables
- `run-create-tables.ps1`: PowerShell wrapper that executes the SQL via `sqlcmd`

## Prerequisites
- SQL Server (LocalDB, Developer, or Express)
- `sqlcmd` CLI installed and on PATH
  - Docs: https://aka.ms/sqlcmd-docs

## Quick Start
1. Open PowerShell in this folder: `sample/mssql`
2. Run (edit credentials as needed):

```powershell
pwsh -File .\run-create-tables.ps1 -Server "localhost" -Database "KAREHALL" -Username "CHANGE_ME_USERNAME" -Password "CHANGE_ME_PASSWORD"
```

- Or edit the default values inside `run-create-tables.ps1` and run without parameters:

```powershell
pwsh -File .\run-create-tables.ps1
```

## Notes
- You can safely re-run; tables are created only if they don't exist.
- `events.status` is enforced via a CHECK constraint (`pending|approved|rejected`).
- JSON data is stored in `NVARCHAR(MAX)`; optional `ISJSON` check is commented for broad compatibility.
- Timestamps use `DATETIME2(0)`.


