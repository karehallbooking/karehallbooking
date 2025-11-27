<?php
/**
 * Quick test to check Imagick status
 * Access: http://127.0.0.1:9000/test-imagick.php
 */

header('Content-Type: text/plain');

echo "=== Imagick Status Check ===\n\n";

echo "PHP Version: " . PHP_VERSION . "\n";
echo "php.ini Location: " . php_ini_loaded_file() . "\n\n";

echo "Imagick Extension Loaded: " . (extension_loaded('imagick') ? 'YES ✓' : 'NO ✗') . "\n";

if (extension_loaded('imagick')) {
    try {
        $imagick = new Imagick();
        $version = $imagick->getVersion();
        echo "Imagick Version: " . $version['versionString'] . "\n";
        echo "Imagick Class Works: YES ✓\n\n";
        
        // Test PDF capability
        echo "Testing PDF support...\n";
        $formats = $imagick->queryFormats();
        if (in_array('PDF', $formats) || in_array('pdf', $formats)) {
            echo "PDF Format Supported: YES ✓\n";
        } else {
            echo "PDF Format Supported: NO ✗\n";
            echo "Available formats: " . implode(', ', array_slice($formats, 0, 10)) . "...\n";
        }
    } catch (Exception $e) {
        echo "Imagick Error: " . $e->getMessage() . "\n";
        echo "Status: ImageMagick software may not be installed or not in PATH\n";
    }
} else {
    echo "\nTo enable Imagick:\n";
    echo "1. Open php.ini: " . php_ini_loaded_file() . "\n";
    echo "2. Add: extension=imagick\n";
    echo "3. Install ImageMagick from: https://imagemagick.org/script/download.php#windows\n";
    echo "4. Restart server\n";
}

echo "\n=== End of Check ===\n";

