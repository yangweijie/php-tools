<?php

echo "Testing FFI availability...\n";

// Check if FFI extension is loaded
if (extension_loaded('ffi')) {
    echo "✅ FFI extension is loaded\n";
} else {
    echo "❌ FFI extension is NOT loaded\n";
    echo "You need to enable FFI extension in php.ini\n";
    exit(1);
}

// Check FFI configuration
$ffi_enabled = ini_get('ffi.enable');
echo "FFI enabled setting: " . ($ffi_enabled ? $ffi_enabled : 'not set') . "\n";

// Try to create a simple FFI instance
try {
    $ffi = FFI::cdef("int printf(const char *format, ...);", "msvcrt.dll");
    echo "✅ FFI basic functionality works\n";
} catch (Exception $e) {
    echo "❌ FFI error: " . $e->getMessage() . "\n";
}

echo "\nTesting WebUI library...\n";

try {
    require_once 'vendor/autoload.php';
    
    $webui = new Webui();
    echo "✅ WebUI library loaded successfully\n";
    
    $window = $webui->newWindow();
    echo "✅ WebUI window created\n";
    
} catch (Exception $e) {
    echo "❌ WebUI error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}