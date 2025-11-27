# Quick Fix: Enable Imagick Extension

## Current Status
✅ **Imagick DLL Found**: `C:\Program Files\php-8.3.27-Win32-vs16-x64\ext\php_imagick.dll`
❌ **Imagick Not Enabled**: Need to add to php.ini

## Quick Steps to Enable Imagick

### Step 1: Edit php.ini (Run as Administrator)

1. **Open PowerShell as Administrator:**
   - Press `Win + X`
   - Select "Windows PowerShell (Admin)" or "Terminal (Admin)"

2. **Open php.ini in Notepad:**
   ```powershell
   notepad "C:\Program Files\php-8.3.27-Win32-vs16-x64\php.ini"
   ```

3. **Find the extensions section** (search for `;extension=`)

4. **Add this line** (or uncomment if it exists):
   ```ini
   extension=imagick
   ```

5. **Save the file** (Ctrl+S)

### Step 2: Install ImageMagick (Required Dependency)

1. **Download ImageMagick:**
   - Go to: https://imagemagick.org/script/download.php#windows
   - Download: **ImageMagick-7.x.x-Q16-HDRI-x64-dll.exe**

2. **Install ImageMagick:**
   - Run the installer
   - ✅ **IMPORTANT:** Check "Add application directory to your system path" during installation
   - Note the installation path (usually: `C:\Program Files\ImageMagick-7.x.x-Q16-HDRI`)

3. **Add to PATH (if not done automatically):**
   - Press `Win + X` → System → Advanced system settings
   - Environment Variables → System variables → Path → Edit
   - Add: `C:\Program Files\ImageMagick-7.x.x-Q16-HDRI`
   - Click OK on all dialogs

### Step 3: Restart Everything

1. **Close all PowerShell/Command Prompt windows**
2. **Restart your computer** (to ensure PATH is loaded)
3. **Or just restart your Laravel server:**
   ```powershell
   php artisan serve --port=9000
   ```

### Step 4: Verify

Open a **NEW** PowerShell and run:

```powershell
php -m | findstr -i imagick
```

Should show: `imagick`

Also test:
```powershell
php -r "echo extension_loaded('imagick') ? 'Imagick: YES ✓' : 'Imagick: NO ✗';"
```

## Project Updates Made

✅ **Updated to PDF-only uploads:**
- Form now only accepts PDF files
- Validation checks for PDF only
- Better error messages

✅ **Improved error handling:**
- Checks if Imagick is loaded before processing
- Clear error messages if Imagick is missing

## After Enabling Imagick

1. Go to Admin → Certificates
2. Select an event
3. Upload a PDF template (should work now!)
4. Fill in the text fields
5. Generate certificates

## Troubleshooting

### "Class 'Imagick' not found" after enabling
- Make sure ImageMagick is installed
- Make sure ImageMagick is in system PATH
- Restart computer after adding to PATH
- Restart PHP server

### "Unable to load dynamic library"
- Make sure DLL matches PHP version (8.3, TS, x64)
- Make sure ImageMagick is installed first

### Still not working?
Check PHP error log:
```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

## Summary

1. ✅ Imagick DLL is already installed
2. ⚠️ Need to enable in php.ini: `extension=imagick`
3. ⚠️ Need to install ImageMagick software
4. ⚠️ Need to add ImageMagick to PATH
5. ⚠️ Restart server/computer

Once done, PDF uploads will work perfectly!

