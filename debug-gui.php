<?php

echo "GUI Debug Tool - Comprehensive Diagnostics\n";
echo "==========================================\n";

// Check system environment
echo "1. System Environment Check:\n";
echo "   OS: " . PHP_OS_FAMILY . "\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Architecture: " . php_uname('m') . "\n";

// Check display environment
echo "\n2. Display Environment:\n";
$display = getenv('DISPLAY');
echo "   DISPLAY variable: " . ($display ? $display : 'Not set') . "\n";

// Check if running in console/service mode
echo "   Session Type: " . (php_sapi_name() === 'cli' ? 'CLI' : php_sapi_name()) . "\n";

// Check WebView2 more thoroughly
echo "\n3. WebView2 Detailed Check:\n";
$webview2_paths = [
    'C:\Program Files (x86)\Microsoft\EdgeWebView\Application',
    'C:\Program Files\Microsoft\EdgeWebView\Application',
    getenv('LOCALAPPDATA') . '\Microsoft\EdgeWebView\Application'
];

foreach ($webview2_paths as $path) {
    if (is_dir($path)) {
        echo "   ✅ Found WebView2 at: $path\n";
        $versions = glob($path . '\*', GLOB_ONLYDIR);
        foreach ($versions as $version) {
            echo "      Version: " . basename($version) . "\n";
        }
    }
}

// Test WebUI with more verbose output
echo "\n4. WebUI Detailed Test:\n";

try {
    require_once 'vendor/autoload.php';
    
    echo "   Creating WebUI instance...\n";
    $webui = new Kingbes\Webui();
    
    echo "   Getting WebUI version/info...\n";
    // Try to get more info about WebUI
    
    echo "   Creating window...\n";
    $window = $webui->newWindow();
    echo "   Window handle: $window\n";
    
    echo "   Setting window properties...\n";
    $webui->setSize($window, 800, 600);
    $webui->setPosition($window, 200, 200);
    
    // Try to set window to be always on top
    echo "   Attempting to make window visible...\n";
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>DEBUG - WebUI Test Window</title>
    <script src="webui.js"></script>
    <style>
        body { 
            font-family: Arial; 
            background: #ff0000; 
            color: white; 
            padding: 20px;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <h1>🚨 DEBUG WINDOW 🚨</h1>
    <p>This is a bright red debug window!</p>
    <p>If you can see this, WebUI is working!</p>
    <script>
        document.body.onclick = function() {
            alert("Window is interactive!");
        };
        
        // Try to bring window to front
        window.focus();
        
        // Log to console
        console.log("WebUI Debug Window Loaded");
    </script>
</body>
</html>';
    
    echo "   Showing window with bright red background...\n";
    $result = $webui->show($window, $html);
    echo "   Show result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    
    if ($result) {
        echo "\n   🔍 LOOK FOR A BRIGHT RED WINDOW! 🔍\n";
        echo "   Window should be 800x600 at position 200,200\n";
        echo "   Background is bright red (#ff0000)\n";
        echo "   Waiting 10 seconds for you to find it...\n";
        
        for ($i = 10; $i > 0; $i--) {
            echo "   Countdown: $i seconds...\n";
            sleep(1);
            
            // Try to bring window to front periodically
            if ($i % 3 == 0) {
                echo "   (Attempting to bring window to front)\n";
            }
        }
        
        echo "   Closing window...\n";
        $webui->close($window);
    } else {
        echo "   ❌ Failed to show window!\n";
    }
    
    echo "   Cleaning up...\n";
    $webui->clean();
    
} catch (Throwable $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n5. Process Check:\n";
echo "   Current PHP processes:\n";
$processes = shell_exec('tasklist /fi "imagename eq php.exe" /fo csv');
if ($processes) {
    $lines = explode("\n", trim($processes));
    foreach ($lines as $line) {
        if (strpos($line, 'php.exe') !== false) {
            $fields = str_getcsv($line);
            if (count($fields) >= 5) {
                echo "   PID: {$fields[1]}, Memory: {$fields[4]}\n";
            }
        }
    }
}

echo "\nDiagnostics completed.\n";