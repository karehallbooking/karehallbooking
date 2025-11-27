# How to Install PHP Extensions (GD and Imagick) on Windows

## Current Status
- ❌ GD Extension: NOT INSTALLED (Required for PNG/JPG image processing)
- ❌ Imagick Extension: NOT INSTALLED (Required for PDF template processing)

## Why You're Getting Errors

1. **PNG/JPG Upload Fails**: GD extension is required to process image templates
2. **PDF Upload Fails**: Imagick extension is required to process PDF templates

## Solution: Install Both Extensions

### Step 1: Install GD Extension (For PNG/JPG Support)

#### For XAMPP/WAMP/Laragon:

1. **Open php.ini:**
   - Location: `C:\Program Files\php-8.3.27-Win32-vs16-x64\php.ini`
   - Or find it: Run `php --ini` in terminal

2. **Find and uncomment this line:**
   ```ini
   ;extension=gd
   ```
   Change to:
   ```ini
   extension=gd
   ```

3. **Restart PHP Server:**
   ```powershell
   # Stop current server (Ctrl+C)
   php artisan serve --port=9000
   ```

4. **Verify:**
   ```powershell
   php -m | findstr -i gd
   ```
   Should show: `gd`

### Step 2: Install Imagick Extension (For PDF Support)

#### Option A: Using PECL (Recommended)

1. **Download Imagick DLL:**
   - Go to: https://windows.php.net/downloads/pecl/releases/imagick/
   - Download: `php_imagick-3.x.x-8.3-ts-vs16-x64.zip` (match your PHP version)
   - Extract `php_imagick.dll`

2. **Download ImageMagick:**
   - Go to: https://imagemagick.org/script/download.php#windows
   - Download: ImageMagick-7.x.x-Q16-HDRI-x64-dll.exe
   - Install it (remember installation path, usually `C:\Program Files\ImageMagick-7.x.x-Q16-HDRI`)

3. **Copy DLL:**
   - Copy `php_imagick.dll` to: `C:\Program Files\php-8.3.27-Win32-vs16-x64\ext\`

4. **Edit php.ini:**
   ```ini
   extension=imagick
   ```

5. **Add ImageMagick to PATH:**
   - Add to System Environment Variables PATH:
     `C:\Program Files\ImageMagick-7.x.x-Q16-HDRI`
   - Or add to php.ini:
     ```ini
     [imagick]
     imagick.set_single_thread = 1
     ```

6. **Restart PHP Server**

7. **Verify:**
   ```powershell
   php -m | findstr -i imagick
   ```
   Should show: `imagick`

#### Option B: Quick Install Script (PowerShell)

Run this in PowerShell as Administrator:

```powershell
# Check PHP version
$phpVersion = php -r "echo PHP_VERSION;"
Write-Host "PHP Version: $phpVersion"

# Download Imagick (adjust version as needed)
$imagickUrl = "https://windows.php.net/downloads/pecl/releases/imagick/3.7.1/php_imagick-3.7.1-8.3-ts-vs16-x64.zip"
$imagickZip = "$env:TEMP\php_imagick.zip"
Invoke-WebRequest -Uri $imagickUrl -OutFile $imagickZip

# Extract
Expand-Archive -Path $imagickZip -DestinationPath "$env:TEMP\imagick" -Force

# Copy DLL to PHP ext folder
$phpExtPath = "C:\Program Files\php-8.3.27-Win32-vs16-x64\ext"
Copy-Item "$env:TEMP\imagick\php_imagick.dll" -Destination $phpExtPath -Force

Write-Host "Imagick DLL copied. Now:"
Write-Host "1. Edit php.ini and add: extension=imagick"
Write-Host "2. Install ImageMagick from: https://imagemagick.org/script/download.php#windows"
Write-Host "3. Restart PHP server"
```

### Step 3: Verify Both Extensions

Run this command:
```powershell
php -r "echo 'GD: ' . (extension_loaded('gd') ? 'YES ✓' : 'NO ✗') . PHP_EOL; echo 'Imagick: ' . (extension_loaded('imagick') ? 'YES ✓' : 'NO ✗') . PHP_EOL;"
```

Expected output:
```
GD: YES ✓
Imagick: YES ✓
```

## Quick Fix: Use Image Templates Only

If you can't install extensions right now:

1. **Convert PDF to PNG:**
   - Use: https://www.ilovepdf.com/pdf-to-jpg
   - Or: https://convertio.co/pdf-png/
   - Save as PNG (1080×720px recommended)

2. **Upload PNG instead of PDF:**
   - PNG/JPG will work once GD is installed
   - No need for Imagick if using images

## Troubleshooting

### "Unable to load dynamic library 'php_imagick.dll'"
- Make sure DLL matches PHP version (8.3)
- Make sure it's Thread Safe (TS) version
- Make sure ImageMagick is installed and in PATH

### "Class 'Imagick' not found"
- Extension not loaded
- Check `php.ini` has `extension=imagick` uncommented
- Restart PHP server

### "GD functions not available"
- GD extension not loaded
- Check `php.ini` has `extension=gd` uncommented
- Restart PHP server

## After Installation

1. Restart your Laravel server
2. Try uploading a PNG template (should work with GD)
3. Try uploading a PDF template (should work with Imagick)

## Need Help?

Check PHP configuration:
```powershell
php --ini
php -m | findstr -i "gd imagick"
```

Check Laravel logs:
```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

