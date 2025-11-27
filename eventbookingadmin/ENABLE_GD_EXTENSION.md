# How to Enable PHP GD Extension

## Problem
You're getting the error: "The PHP GD extension is required, but is not installed."

## Solution

### Step 1: Locate php.ini
Your PHP configuration file is located at:
```
C:\Program Files\php-8.3.27-Win32-vs16-x64\php.ini
```

### Step 2: Edit php.ini
1. Open the `php.ini` file in a text editor (you may need Administrator privileges)
2. Search for `;extension=gd` (it will have a semicolon `;` at the start, which comments it out)
3. Remove the semicolon to uncomment it:
   ```
   extension=gd
   ```
4. If you don't find `extension=gd`, add this line in the extensions section:
   ```
   extension=gd
   ```

### Step 3: Verify GD Extension Files
Make sure these DLL files exist in your PHP `ext` folder:
- `php_gd2.dll` (should be in `C:\Program Files\php-8.3.27-Win32-vs16-x64\ext\`)

If the DLL is missing, you may need to download the PHP Windows binaries that include GD support.

### Step 4: Restart PHP Server
After making changes:
1. Stop your Laravel development server (if running)
2. Restart it:
   ```powershell
   php artisan serve --port=9000
   ```

### Step 5: Verify GD is Loaded
Run this command to check:
```powershell
php -m | findstr -i gd
```

You should see `gd` in the output.

## Alternative: Use PDF Template Instead
If you cannot enable GD extension, you can:
1. Convert your image template to PDF format
2. Upload the PDF template instead
3. PDF templates don't require GD extension

## Quick Test
After enabling GD, test with:
```powershell
php -r "echo extension_loaded('gd') ? 'GD is loaded' : 'GD is NOT loaded';"
```

