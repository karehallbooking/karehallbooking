# Install Imagick Extension for Windows - Step by Step

## Quick Installation Guide for PHP 8.3 on Windows

### Step 1: Download ImageMagick (Required Dependency)

1. Go to: https://imagemagick.org/script/download.php#windows
2. Download: **ImageMagick-7.x.x-Q16-HDRI-x64-dll.exe** (Latest version)
3. Run the installer
4. **Important:** During installation, check "Add application directory to your system path"
5. Note the installation path (usually: `C:\Program Files\ImageMagick-7.x.x-Q16-HDRI`)

### Step 2: Download Imagick PHP Extension

1. Go to: https://windows.php.net/downloads/pecl/releases/imagick/
2. Find version compatible with PHP 8.3:
   - Look for: `php_imagick-3.7.x-8.3-ts-vs16-x64.zip`
   - **Important:** Must match:
     - PHP 8.3
     - TS (Thread Safe)
     - x64 (64-bit)
     - vs16 (Visual Studio 2019)

3. Download the ZIP file

### Step 3: Extract and Copy DLL

1. Extract the downloaded ZIP file
2. Find `php_imagick.dll` inside
3. Copy `php_imagick.dll` to your PHP extensions folder:
   ```
   C:\Program Files\php-8.3.27-Win32-vs16-x64\ext\
   ```

### Step 4: Edit php.ini

1. Open php.ini in a text editor (as Administrator):
   ```
   C:\Program Files\php-8.3.27-Win32-vs16-x64\php.ini
   ```

2. Find the section with extensions (search for `;extension=`)

3. Add this line (or uncomment if it exists):
   ```ini
   extension=imagick
   ```

4. Save the file

### Step 5: Add ImageMagick to System PATH

1. Press `Win + X` and select "System"
2. Click "Advanced system settings"
3. Click "Environment Variables"
4. Under "System variables", find "Path" and click "Edit"
5. Click "New" and add:
   ```
   C:\Program Files\ImageMagick-7.x.x-Q16-HDRI
   ```
   (Replace with your actual ImageMagick path)
6. Click "OK" on all dialogs

### Step 6: Restart Everything

1. **Close all command prompts/PowerShell windows**
2. **Restart your computer** (recommended to ensure PATH is loaded)
3. Or restart your web server/PHP service

### Step 7: Verify Installation

Open a **NEW** PowerShell/Command Prompt and run:

```powershell
php -m | findstr -i imagick
```

Should show: `imagick`

Also test:
```powershell
php -r "echo extension_loaded('imagick') ? 'Imagick: YES ✓' : 'Imagick: NO ✗';"
```

### Step 8: Test in Laravel

1. Start your Laravel server:
   ```powershell
   cd C:\Users\sande\Downloads\KAREHALL\eventbookingadmin
   php artisan serve --port=9000
   ```

2. Go to Admin → Certificates
3. Upload a PDF template
4. Should work without errors!

## Troubleshooting

### Error: "Unable to load dynamic library 'php_imagick.dll'"

**Solutions:**
1. Make sure DLL matches your PHP version (8.3)
2. Make sure it's Thread Safe (TS) version
3. Make sure ImageMagick is installed and in PATH
4. Restart computer after adding to PATH

### Error: "Class 'Imagick' not found"

**Solutions:**
1. Check `php.ini` has `extension=imagick` (no semicolon)
2. Restart PHP server
3. Verify with: `php -m | findstr imagick`

### Error: "PDF conversion fails"

**Solutions:**
1. Make sure ImageMagick is properly installed
2. Check ImageMagick is in system PATH
3. Verify PDF file is not corrupted
4. Check file permissions

### Still Not Working?

1. Check PHP error log:
   ```powershell
   Get-Content storage\logs\laravel.log -Tail 50
   ```

2. Check PHP configuration:
   ```powershell
   php --ini
   php -m
   ```

3. Verify ImageMagick command line works:
   ```powershell
   magick -version
   ```
   (Should show ImageMagick version if installed correctly)

## Alternative: Use Pre-compiled Package

If manual installation is difficult, you can use:

1. **XAMPP with Imagick:**
   - Download XAMPP that includes Imagick
   - Or use Laragon which often includes Imagick

2. **Docker:**
   - Use a Docker image with Imagick pre-installed
   - Example: `php:8.3-fpm` with Imagick

## Quick Verification Script

Save this as `check-imagick.php` and run: `php check-imagick.php`

```php
<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "GD Extension: " . (extension_loaded('gd') ? 'YES ✓' : 'NO ✗') . "\n";
echo "Imagick Extension: " . (extension_loaded('imagick') ? 'YES ✓' : 'NO ✗') . "\n";

if (extension_loaded('imagick')) {
    try {
        $imagick = new Imagick();
        echo "Imagick Version: " . $imagick->getVersion()['versionString'] . "\n";
        echo "✓ Imagick is working correctly!\n";
    } catch (Exception $e) {
        echo "✗ Imagick error: " . $e->getMessage() . "\n";
    }
}
```

## Need Help?

If you continue having issues:
1. Check that all versions match (PHP 8.3, TS, x64, vs16)
2. Make sure ImageMagick is installed before Imagick
3. Restart computer after PATH changes
4. Check Windows Event Viewer for PHP errors

