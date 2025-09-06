<?php

require_once 'vendor/autoload.php';

use Kingbes\Webui;

echo "Starting Fixed WebUI Application...\n";

try {
    $webui = new Webui();
    echo "✅ WebUI created\n";
    
    $window = $webui->newWindow();
    echo "✅ Window created: $window\n";
    
    // Try different window settings
    $webui->setSize($window, 800, 600);
    $webui->setPosition($window, 0, 0); // Top-left corner
    echo "✅ Window configured\n";
    
    // Simple HTML without complex dependencies
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>System Toolbox - WORKING!</title>
    <style>
        body { 
            background: linear-gradient(45deg, #ff0000, #00ff00); 
            color: white; 
            font-family: Arial; 
            text-align: center; 
            padding: 50px;
            font-size: 24px;
        }
        .blink { animation: blink 1s infinite; }
        @keyframes blink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }
    </style>
</head>
<body>
    <h1 class="blink">🎉 GUI IS WORKING! 🎉</h1>
    <p>If you can see this colorful window, WebUI is working perfectly!</p>
    <p>Window Size: 800x600</p>
    <p>Position: Top-left corner (0,0)</p>
    <hr>
    <h2>System Administration Toolbox</h2>
    <p>✅ Port Management Ready</p>
    <p>✅ Process Management Ready</p>
</body>
</html>';
    
    echo "✅ HTML prepared\n";
    echo "🚀 Showing window at top-left corner...\n";
    echo "👀 LOOK FOR A COLORFUL BLINKING WINDOW!\n";
    
    $result = $webui->show($window, $html);
    echo "Show result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    
    if ($result) {
        echo "🎯 Window should be visible at screen position (0,0)\n";
        echo "🔍 Check the very top-left corner of your screen!\n";
        echo "⏰ Keeping window open for 30 seconds...\n";
        
        // Keep alive longer
        for ($i = 30; $i > 0; $i--) {
            echo "⏳ $i seconds remaining...\n";
            sleep(1);
        }
    }
    
    echo "🔚 Closing application...\n";
    $webui->clean();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "✅ Application finished\n";
?>