# How to Install Imagick Extension for PDF Template Support

## Problem
You're getting the error: "Imagick extension is required to process PDF templates."

## Solution Options

### Option 1: Install Imagick Extension (Recommended for PDF Support)

#### Windows (XAMPP/WAMP/Laragon)

1. **Download Imagick DLL:**
   - Go to: https://pecl.php.net/package/imagick
   - Download the Windows DLL for PHP 8.3 (TS - Thread Safe version)
   - Or use: https://windows.php.net/downloads/pecl/releases/imagick/

2. **Download ImageMagick:**
   - Download ImageMagick from: https://imagemagick.org/script/download.php#windows
   - Install ImageMagick (remember the installation path)

3. **Install Imagick:**
   - Copy `php_imagick.dll` to your PHP `ext` folder (e.g., `C:\Program Files\php-8.3.27-Win32-vs16-x64\ext\`)
   - Edit `php.ini` and add: `extension=imagick`
   - Add ImageMagick path to system PATH or set in php.ini:
     ```
     [imagick]
     imagick.set_single_thread = 1
     ```

4. **Restart PHP Server:**
   ```powershell
   # Stop your Laravel server (Ctrl+C)
   php artisan serve --port=9000
   ```

5. **Verify Installation:**
   ```powershell
   php -m | findstr -i imagick
   ```

#### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install php-imagick
sudo systemctl restart php8.3-fpm  # or your PHP service
```

#### macOS (Homebrew)
```bash
brew install imagemagick
pecl install imagick
# Add extension=imagick to php.ini
```

### Option 2: Use Image Templates Instead (No Installation Required)

If you cannot install Imagick, you can:

1. **Convert PDF to Image:**
   - Use an online tool: https://www.ilovepdf.com/pdf-to-jpg
   - Or use Adobe Acrobat, Preview (Mac), or any PDF viewer to export as PNG/JPG
   - Save as 1080×720px image

2. **Upload Image Instead:**
   - Go to Admin → Certificates
   - Upload the converted PNG/JPG image instead of PDF
   - Image templates work without Imagick (only require GD extension)

### Option 3: Quick Test

Check if Imagick is installed:
```powershell
php -r "echo extension_loaded('imagick') ? 'Imagick is installed' : 'Imagick is NOT installed';"
```

## Troubleshooting

### "Unable to load dynamic library"
- Make sure `php_imagick.dll` matches your PHP version (8.3)
- Make sure it's the correct architecture (x64 for 64-bit PHP)
- Make sure ImageMagick is installed and in PATH

### "Class 'Imagick' not found"
- Imagick extension is not loaded
- Check `php.ini` has `extension=imagick` uncommented
- Restart PHP server

### PDF conversion fails
- Make sure ImageMagick is properly installed
- Check file permissions on template files
- Verify PDF file is not corrupted
- Install Ghostscript and expose it to PHP:
  1. Install the Windows package from [ghostscript.com](https://ghostscript.com/releases/gsdnld.html)
  2. Set the `GHOSTSCRIPT_PATH` variable in `.env` to the `gswin64c.exe` path, for example:
     ```
     GHOSTSCRIPT_PATH="C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe"
     ```
  3. Restart your terminal and `php artisan serve` so the new PATH is loaded.

## Alternative: Manual PDF to Image Conversion

If Imagick installation is difficult, you can:
1. Convert your PDF template to PNG/JPG manually
2. Upload the image file instead
3. Image templates work with just GD extension (usually pre-installed)

## Need Help?

If you continue having issues:
1. Check PHP error logs: `storage/logs/laravel.log`
2. Verify PHP extensions: `php -m`
3. Check php.ini location: `php --ini`

