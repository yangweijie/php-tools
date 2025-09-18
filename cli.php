#!/usr/bin/env php
<?php

/**
 * PHP Tools CLI Entry Point
 * 
 * A native PHP CLI implementation without Laravel Zero
 */

// Define application root path
define('APP_ROOT', __DIR__);

// Require the Composer autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Get command line arguments
$args = $argv ?? [];
$command = $args[1] ?? 'help';

try {
    switch ($command) {
        case 'gui':
            // Run the GUI application
            runGuiApplication();
            break;
            
        case 'build':
            // Build PHAR file
            buildPhar();
            break;
            
        case 'help':
        default:
            showHelp();
            break;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

/**
 * Run the GUI application
 */
function runGuiApplication()
{
    // Initialize the GUI application
    global $application;
    $application = new App\App();
    
    // Create port killer tool
    $portKiller = new App\PortKiller();
    $application->addTab("端口查杀", $portKiller->getControl());
    
    // Create process killer tool
    $processKiller = new App\ProcessKiller();
    $application->addTab("进程查杀", $processKiller->getControl());
    
    // Create download accelerator tab
    $downloadAcceleratorTab = new App\DownloadAcceleratorTab();
    $application->addTab("下载加速", $downloadAcceleratorTab->getControl());

    // Create SQLite2MySQL tab
    $sqlite2MysqlTab = new App\SQLite2MySQLTab();
    $application->addTabWithCallback("SQLite转MySQL", $sqlite2MysqlTab->getControl(), function() use ($sqlite2MysqlTab) {
        $sqlite2MysqlTab->checkAndDownloadPhar();
    });

    // Create packager tab
    $packagerTab = new App\PackagerTab();
    $application->addTab("PHP打包工具", $packagerTab->getControl());

    // Create smart packager tab
    $smartPackagerTab = new App\SmartPackagerTab();
    $application->addTab("智能打包工具", $smartPackagerTab->getControl());

    // Create example tab
    $exampleTab = new App\ExampleTab();
    $application->addTab("示例", $exampleTab->getControl());
    
    // Create datetime tab
    $exampleTab2 = new App\DatetimeTab();
    $application->addTab("示例2", $exampleTab2->getControl());


    // Run the application
    $application->run();
}

/**
 * Build PHAR file using Box
 */
function buildPhar()
{
    echo "Building PHAR file...\n";
    
    // Check if Box is available
    $boxPath = __DIR__ . '/vendor/bin/box';
    if (!file_exists($boxPath)) {
        echo "Error: Box not found at $boxPath\n";
        exit(1);
    }
    
    // 执行 Box 编译命令
    $command = "php $boxPath compile";
    $output = shell_exec("$command 2>&1");
    
    echo $output;
    
    // 检查构建是否成功
    if (file_exists(__DIR__ . '/builds/tools.phar')) {
        echo "Build successful! PHAR file created at builds/tools.phar\n";
    } else {
        echo "Build failed!\n";
        exit(1);
    }
}

/**
 * Show help information
 */
function showHelp()
{
    echo "PHP Tools - A collection of system utilities\n";
    echo "Usage: php cli.php [command]\n\n";
    echo "Available commands:\n";
    echo "  gui     Start the GUI application\n";
    echo "  build   Build the PHAR file\n";
    echo "  help    Show this help message\n";
    echo "\n";
    echo "Examples:\n";
    echo "  php cli.php gui     # Start the GUI tools\n";
    echo "  php cli.php build   # Build the PHAR file\n";
    echo "  php cli.php help    # Show help\n";
}