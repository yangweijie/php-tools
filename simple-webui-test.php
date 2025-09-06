<?php

echo "Simple WebUI Test\n";
echo "================\n";

try {
    require_once 'vendor/autoload.php';
    
    echo "1. Loading WebUI class...\n";
    use Kingbes\Webui;
    
    echo "2. Creating WebUI instance...\n";
    $webui = new Webui();
    echo "   ✅ WebUI instance created\n";
    
    echo "3. Creating new window...\n";
    $window = $webui->newWindow();
    echo "   ✅ Window created (ID: $window)\n";
    
    echo "4. Setting window size...\n";
    $webui->setSize($window, 800, 600);
    echo "   ✅ Window size set\n";
    
    echo "5. Preparing HTML content...\n";
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>Simple WebUI Test</title>
    <script src="webui.js"></script>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h1>WebUI Test Window</h1>
    <p>If you can see this window, WebUI is working correctly!</p>
    <button onclick="alert(\'Hello from WebUI!\')">Test Button</button>
</body>
</html>';
    
    echo "6. Showing window...\n";
    $result = $webui->show($window, $html);
    echo "   ✅ Window show result: " . ($result ? 'true' : 'false') . "\n";
    
    echo "7. Starting WebUI wait loop...\n";
    echo "   GUI window should appear now!\n";
    echo "   Close the window to exit.\n";
    
    $webui->wait();
    
    echo "8. Cleaning up...\n";
    $webui->clean();
    echo "   ✅ Cleanup completed\n";
    
} catch (Throwable $e) {
    echo "❌ Error occurred:\n";
    echo "   Type: " . get_class($e) . "\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";