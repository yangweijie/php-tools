<?php

require_once 'vendor/autoload.php';

use Kingbes\Webui;
use Kingbes\JavaScript;

$webui = new Webui();
$window = $webui->newWindow();

// Set window size
$webui->setSize($window, 800, 600);

// Simple HTML content
$html = '<!DOCTYPE html>
<html>
<head>
    <title>Simple Test</title>
    <script src="webui.js"></script>
</head>
<body>
    <h1>WebUI Test</h1>
    <p>If you can see this, WebUI is working!</p>
    <button onclick="testClick()">Test Button</button>
    <div id="result"></div>
    
    <script>
        function testClick() {
            document.getElementById("result").innerHTML = "Button clicked!";
        }
    </script>
</body>
</html>';

echo "Starting WebUI application...\n";
echo "A browser window should open. Close it to exit.\n";

$webui->show($window, $html);
$webui->wait();
$webui->clean();

echo "Application closed.\n";