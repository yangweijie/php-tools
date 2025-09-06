<?php

echo "WebUI Core Test - Bypass WebView2 Issues\n";
echo "========================================\n";

try {
    require_once 'vendor/autoload.php';
    
    echo "1. Testing WebUI class loading...\n";
    $webui = new Kingbes\Webui();
    echo "   ✅ WebUI instance created\n";
    
    echo "2. Testing window creation...\n";
    $window = $webui->newWindow();
    echo "   ✅ Window created: $window\n";
    
    echo "3. Testing basic window operations...\n";
    $webui->setSize($window, 600, 400);
    echo "   ✅ Size set\n";
    
    $webui->setPosition($window, 100, 100);
    echo "   ✅ Position set\n";
    
    echo "4. Testing simple HTML (no WebView2 dependencies)...\n";
    $html = '<html><body><h1>Simple Test</h1></body></html>';
    
    // Set a timeout for the show operation
    echo "   Attempting to show window...\n";
    
    // Use a different approach - try to show without webui.js
    $result = $webui->show($window, $html);
    
    if ($result) {
        echo "   ✅ Window show succeeded\n";
        echo "   Waiting 3 seconds...\n";
        sleep(3);
        $webui->close($window);
    } else {
        echo "   ❌ Window show failed\n";
    }
    
    $webui->clean();
    echo "5. ✅ Test completed\n";
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTesting alternative approach...\n";

// Try using a different browser or method
try {
    echo "6. Testing with different browser setting...\n";
    $webui2 = new Kingbes\Webui();
    $window2 = $webui2->newWindow();
    
    // Try to set a different browser or runtime
    $webui2->setSize($window2, 400, 300);
    
    // Use minimal HTML without scripts
    $simpleHtml = '<!DOCTYPE html><html><head><title>Test</title></head><body style="background:yellow;"><h1>YELLOW TEST WINDOW</h1><p>No JavaScript dependencies</p></body></html>';
    
    echo "   Showing yellow window without webui.js...\n";
    $result2 = $webui2->show($window2, $simpleHtml);
    
    if ($result2) {
        echo "   ✅ Alternative method worked!\n";
        sleep(2);
        $webui2->close($window2);
    } else {
        echo "   ❌ Alternative method also failed\n";
    }
    
    $webui2->clean();
    
} catch (Throwable $e) {
    echo "❌ Alternative test error: " . $e->getMessage() . "\n";
}

echo "\nCore test completed.\n";