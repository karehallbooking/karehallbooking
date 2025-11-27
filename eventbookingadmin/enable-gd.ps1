# PowerShell script to help enable GD extension in PHP
# Run this script as Administrator

Write-Host "=== PHP GD Extension Enabler ===" -ForegroundColor Cyan
Write-Host ""

$phpIniPath = "C:\Program Files\php-8.3.27-Win32-vs16-x64\php.ini"
$phpExtPath = "C:\Program Files\php-8.3.27-Win32-vs16-x64\ext"

# Check if php.ini exists
if (-not (Test-Path $phpIniPath)) {
    Write-Host "ERROR: php.ini not found at: $phpIniPath" -ForegroundColor Red
    Write-Host "Please update the path in this script to match your PHP installation." -ForegroundColor Yellow
    exit 1
}

Write-Host "Found php.ini at: $phpIniPath" -ForegroundColor Green
Write-Host ""

# Check if GD DLL exists
$gdDll = Join-Path $phpExtPath "php_gd2.dll"
if (-not (Test-Path $gdDll)) {
    Write-Host "WARNING: php_gd2.dll not found at: $gdDll" -ForegroundColor Yellow
    Write-Host "You may need to download PHP with GD support or install it separately." -ForegroundColor Yellow
    Write-Host ""
}

# Read php.ini content
$phpIniContent = Get-Content $phpIniPath -Raw

# Check if extension=gd is already enabled
if ($phpIniContent -match "^\s*extension\s*=\s*gd\s*$" -or $phpIniContent -match "^\s*extension\s*=\s*php_gd2\.dll\s*$") {
    Write-Host "GD extension is already enabled in php.ini" -ForegroundColor Green
    Write-Host ""
    Write-Host "If GD is still not working, try:" -ForegroundColor Yellow
    Write-Host "1. Restart your PHP server" -ForegroundColor Yellow
    Write-Host "2. Check if php_gd2.dll exists in the ext folder" -ForegroundColor Yellow
    exit 0
}

# Check if extension=gd is commented out
if ($phpIniContent -match "^\s*;\s*extension\s*=\s*gd\s*$" -or $phpIniContent -match "^\s*;\s*extension\s*=\s*php_gd2\.dll\s*$") {
    Write-Host "Found commented GD extension. Attempting to enable..." -ForegroundColor Yellow
    
    # Create backup
    $backupPath = "$phpIniPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIniPath $backupPath
    Write-Host "Backup created: $backupPath" -ForegroundColor Green
    
    # Uncomment the line
    $phpIniContent = $phpIniContent -replace "^\s*;\s*extension\s*=\s*gd\s*$", "extension=gd"
    $phpIniContent = $phpIniContent -replace "^\s*;\s*extension\s*=\s*php_gd2\.dll\s*$", "extension=php_gd2.dll"
    
    # Write back
    Set-Content -Path $phpIniPath -Value $phpIniContent -NoNewline
    
    Write-Host "GD extension enabled!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Restart your Laravel server" -ForegroundColor White
    Write-Host "2. Run: php -m | findstr -i gd (to verify)" -ForegroundColor White
} else {
    Write-Host "GD extension line not found. Adding it..." -ForegroundColor Yellow
    
    # Find the extension section (usually after [PHP] or [Extensions])
    if ($phpIniContent -match "(\[.*?\]\s*\r?\n)(.*?)(; Windows Extensions|; Extensions|; Module Settings)") {
        $newContent = $phpIniContent -replace "(\[.*?\]\s*\r?\n)(.*?)(; Windows Extensions|; Extensions|; Module Settings)", "`$1`$2extension=gd`r`n`$3"
    } else {
        # If no extension section found, add at the end of [PHP] section
        $newContent = $phpIniContent -replace "(\[PHP\])", "`$1`r`n`r`nextension=gd"
    }
    
    # Create backup
    $backupPath = "$phpIniPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIniPath $backupPath
    Write-Host "Backup created: $backupPath" -ForegroundColor Green
    
    # Write back
    Set-Content -Path $phpIniPath -Value $newContent -NoNewline
    
    Write-Host "GD extension added to php.ini!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Restart your Laravel server" -ForegroundColor White
    Write-Host "2. Run: php -m | findstr -i gd (to verify)" -ForegroundColor White
}

Write-Host ""
Write-Host "=== Done ===" -ForegroundColor Cyan

