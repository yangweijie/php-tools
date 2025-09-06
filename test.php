<?php

require_once 'vendor/autoload.php';

use Kingbes\Webui;
use Kingbes\JavaScript;

$webui = new Webui();
$window = $webui->newWindow();

$webui->bind($window, 'test', function($event, JavaScript $js) {
    $js->returnString($event, 'Hello from PHP!');
});

$html = '<!DOCTYPE html>
<html>
<head>
    <title>Test</title>
    <script src="webui.js"></script>
</head>
<body>
    <h1>WebUI Test</h1>
    <button onclick="testFunction()">Test</button>
    <div id="result"></div>
    
    <script>
        async function testFunction() {
            try {
                const result = await test();
                document.getElementById("result").innerHTML = result;
            } catch (error) {
                document.getElementById("result").innerHTML = "Error: " + error;
            }
        }
    </script>
</body>
</html>';

$webui->show($window, $html);
$webui->wait();
$webui->clean();