# PowerShell script to enable Imagick extension in php.ini
# Run this script as Administrator

Write-Host "=== Enable Imagick Extension ===" -ForegroundColor Cyan
Write-Host ""

$phpIniPath = "C:\Program Files\php-8.3.27-Win32-vs16-x64\php.ini"
$extPath = "C:\Program Files\php-8.3.27-Win32-vs16-x64\ext\php_imagick.dll"

# Check if php.ini exists
if (-not (Test-Path $phpIniPath)) {
    Write-Host "ERROR: php.ini not found at: $phpIniPath" -ForegroundColor Red
    exit 1
}

# Check if Imagick DLL exists
if (-not (Test-Path $extPath)) {
    Write-Host "WARNING: php_imagick.dll not found at: $extPath" -ForegroundColor Yellow
    Write-Host "Please download and install Imagick DLL first." -ForegroundColor Yellow
    exit 1
}

Write-Host "Found php.ini at: $phpIniPath" -ForegroundColor Green
Write-Host "Found Imagick DLL at: $extPath" -ForegroundColor Green
Write-Host ""

# Read php.ini content
$phpIniContent = Get-Content $phpIniPath -Raw

# Check if extension=imagick is already enabled
if ($phpIniContent -match "^\s*extension\s*=\s*imagick\s*$" -and $phpIniContent -notmatch "^\s*;\s*extension\s*=\s*imagick\s*$") {
    Write-Host "Imagick extension is already enabled in php.ini" -ForegroundColor Green
    Write-Host ""
    Write-Host "If Imagick is still not working:" -ForegroundColor Yellow
    Write-Host "1. Make sure ImageMagick is installed and in PATH" -ForegroundColor Yellow
    Write-Host "2. Restart your PHP server" -ForegroundColor Yellow
    Write-Host "3. Restart your computer if needed" -ForegroundColor Yellow
    exit 0
}

# Check if extension=imagick is commented out
if ($phpIniContent -match "^\s*;\s*extension\s*=\s*imagick\s*$") {
    Write-Host "Found commented Imagick extension. Enabling..." -ForegroundColor Yellow
    
    # Create backup
    $backupPath = "$phpIniPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIniPath $backupPath
    Write-Host "Backup created: $backupPath" -ForegroundColor Green
    
    # Uncomment the line
    $phpIniContent = $phpIniContent -replace "^\s*;\s*extension\s*=\s*imagick\s*$", "extension=imagick"
    
    # Write back
    Set-Content -Path $phpIniPath -Value $phpIniContent -NoNewline
    
    Write-Host "Imagick extension enabled!" -ForegroundColor Green
} else {
    Write-Host "Imagick extension line not found. Adding it..." -ForegroundColor Yellow
    
    # Find the extension section
    if ($phpIniContent -match "(\[.*?\]\s*\r?\n)(.*?)(; Windows Extensions|; Extensions|; Module Settings)") {
        $newContent = $phpIniContent -replace "(\[.*?\]\s*\r?\n)(.*?)(; Windows Extensions|; Extensions|; Module Settings)", "`$1`$2extension=imagick`r`n`$3"
    } else {
        # If no extension section found, add after [PHP] section
        $newContent = $phpIniContent -replace "(\[PHP\])", "`$1`r`n`r`nextension=imagick"
    }
    
    # Create backup
    $backupPath = "$phpIniPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $phpIniPath $backupPath
    Write-Host "Backup created: $backupPath" -ForegroundColor Green
    
    # Write back
    Set-Content -Path $phpIniPath -Value $newContent -NoNewline
    
    Write-Host "Imagick extension added to php.ini!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Make sure ImageMagick is installed:" -ForegroundColor White
Write-Host "   Download from: https://imagemagick.org/script/download.php#windows" -ForegroundColor White
Write-Host "2. Add ImageMagick to system PATH" -ForegroundColor White
Write-Host "3. Restart your Laravel server:" -ForegroundColor White
Write-Host "   php artisan serve --port=9000" -ForegroundColor White
Write-Host "4. Verify with: php -m | findstr -i imagick" -ForegroundColor White
Write-Host ""
Write-Host "=== Done ===" -ForegroundColor Cyan

