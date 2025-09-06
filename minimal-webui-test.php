<?php

echo "Minimal WebUI Test - Checking Core Functionality\n";
echo "===============================================\n";

try {
    require_once 'vendor/autoload.php';
    
    echo "1. Creating WebUI instance...\n";
    $webui = new Kingbes\Webui();
    echo "   ✅ Success\n";
    
    echo "2. Creating window...\n";
    $window = $webui->newWindow();
    echo "   ✅ Window ID: $window\n";
    
    echo "3. Setting basic properties...\n";
    $webui->setSize($window, 400, 300);
    $webui->setPosition($window, 100, 100);
    echo "   ✅ Properties set\n";
    
    echo "4. Showing minimal HTML...\n";
    $html = '<html><head><script src="webui.js"></script></head><body><h1>Test</h1></body></html>';
    
    $result = $webui->show($window, $html);
    echo "   Show result: " . ($result ? "Success" : "Failed") . "\n";
    
    if ($result) {
        echo "5. Window should be visible now!\n";
        echo "   Look for a small window with 'Test' heading\n";
        echo "   Waiting 5 seconds...\n";
        
        // Wait 5 seconds instead of indefinite wait
        for ($i = 5; $i > 0; $i--) {
            echo "   $i...\n";
            sleep(1);
        }
        
        echo "6. Closing window...\n";
        $webui->close($window);
    }
    
    echo "7. Cleaning up...\n";
    $webui->clean();
    echo "   ✅ Test completed\n";
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}