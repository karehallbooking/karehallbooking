# Fix Upload Limit Error

If you're getting "POST Content-Length too large" error when uploading PDFs:

## Quick Fix (Recommended)

1. **Find your php.ini file:**
   - Run: `php --ini`
   - Look for "Loaded Configuration File"

2. **Edit php.ini and update these values:**
   ```
   upload_max_filesize = 50M
   post_max_size = 50M
   max_execution_time = 300
   max_input_time = 300
   memory_limit = 256M
   ```

3. **Restart your PHP server:**
   - Stop `php artisan serve`
   - Start again: `php artisan serve`

## Alternative: Update php.ini via Command

If you're using XAMPP/WAMP:
- XAMPP: `C:\xampp\php\php.ini`
- WAMP: `C:\wamp\bin\php\php8.x.x\php.ini`

## Current Limits

- Maximum file size per PDF: **10MB**
- Maximum total upload: **50MB** (both files combined)

## Note

The form now includes client-side validation to warn you if files are too large before upload.

