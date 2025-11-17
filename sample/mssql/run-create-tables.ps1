Param(
    [string]$Server = "localhost",
    [string]$Database = "KAREHALL",
    [string]$Username = "CHANGE_ME_USERNAME",
    [string]$Password = "CHANGE_ME_PASSWORD",
    [switch]$TrustServerCertificate
)

# Run this script in PowerShell:
#   pwsh -File .\run-create-tables.ps1 -Server "YOUR_SERVER" -Database "YOUR_DB" -Username "sa" -Password "yourStrong(!)Password"
# Or edit the defaults above and run without parameters.

$sqlFile = Join-Path $PSScriptRoot "create_all_tables.sql"

if (-not (Test-Path $sqlFile)) {
    Write-Error "SQL file not found: $sqlFile"
    exit 1
}

# Build sqlcmd arguments
$args = @(
    "-S", $Server,
    "-d", $Database,
    "-U", $Username,
    "-P", $Password,
    "-b",                 # fail on error
    "-I",                 # enable quoted identifiers
    "-i", $sqlFile
)

if ($TrustServerCertificate) {
    $args += @("-C")
}

Write-Host "Connecting to server: $Server, database: $Database" -ForegroundColor Cyan

# Ensure sqlcmd is available
$sqlcmd = Get-Command sqlcmd -ErrorAction SilentlyContinue
if (-not $sqlcmd) {
    Write-Error "sqlcmd not found. Install the SQL Server Command Line Utilities or use Azure Data Studio."
    Write-Host "Download: https://aka.ms/sqlcmd-docs" -ForegroundColor Yellow
    exit 1
}

& sqlcmd @args
$exitCode = $LASTEXITCODE

if ($exitCode -ne 0) {
    Write-Error "Table creation failed with exit code $exitCode"
    exit $exitCode
}

Write-Host "All tables created or already exist." -ForegroundColor Green


