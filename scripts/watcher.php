#!/usr/bin/env php
<?php

/**
 * Hot Reload Watcher for PHP GUI Applications
 *
 * Monitors PHP files for changes and automatically restarts the GUI application
 */

// Define application root path
define('APP_ROOT', __DIR__ . '/..');

// Require the Composer autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Get command line arguments
$args = $argv ?? [];
$command = $args[1] ?? 'help';

// Check if we're in watch mode
$isWatchMode = ($args[2] ?? '') === '--watch' || ($args[2] ?? '') === '-w';

if ($isWatchMode) {
    // Watch mode - monitor files and restart on changes
    runWatchMode($command);
} else {
    // Normal mode - show help or run command with watch option
    switch ($command) {
        case 'gui':
            showWatchHelp();
            break;

        case 'help':
        default:
            showHelp();
            break;
    }
}

/**
 * Run in watch mode
 */
function runWatchMode($command)
{
    echo "Starting hot reload watcher for command: $command\n";
    echo "Monitoring PHP files in " . APP_ROOT . "/app directory...\n";
    echo "Press Ctrl+C to stop\n\n";

    // Get initial file timestamps
    $fileTimestamps = getPhpFileTimestamps();

    // Run the initial command
    $process = startProcess($command);

    // Monitor loop
    while (true) {
        sleep(1); // Check every second

        // Get current file timestamps
        $currentTimestamps = getPhpFileTimestamps();

        // Check for changes
        if (hasFileChanges($fileTimestamps, $currentTimestamps)) {
            echo "File changes detected! Restarting application...\n";

            // Kill the current process
            if ($process) {
                killProcess($process);
            }

            // Update timestamps
            $fileTimestamps = $currentTimestamps;

            // Start new process
            $process = startProcess($command);
        }
    }
}

/**
 * Get timestamps for all PHP files in the app directory
 */
function getPhpFileTimestamps()
{
    $timestamps = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(APP_ROOT . '/app')
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $timestamps[$file->getPathname()] = $file->getMTime();
        }
    }

    return $timestamps;
}

/**
 * Check if any files have changed
 */
function hasFileChanges($oldTimestamps, $newTimestamps)
{
    // Check for modified files
    foreach ($newTimestamps as $file => $timestamp) {
        if (!isset($oldTimestamps[$file]) || $oldTimestamps[$file] !== $timestamp) {
            return true;
        }
    }

    // Check for deleted files
    foreach ($oldTimestamps as $file => $timestamp) {
        if (!isset($newTimestamps[$file])) {
            return true;
        }
    }

    return false;
}

/**
 * Start a process for the given command
 */
function startProcess($command)
{
    $cmd = 'php ' . APP_ROOT . '/cli.php ' . $command;
    echo "Starting process: $cmd\n";

    // Start process in background
    $process = proc_open(
        $cmd,
        [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ],
        $pipes
    );

    if (is_resource($process)) {
        // Close pipes to prevent blocking
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        echo "Process started successfully\n";
        return $process;
    }

    echo "Failed to start process\n";
    return null;
}

/**
 * Kill a process
 */
function killProcess($process)
{
    echo "Terminating process...\n";
    $status = proc_get_status($process);

    if ($status['running']) {
        // Try graceful termination first
        if (PHP_OS_FAMILY === 'Windows') {
            proc_terminate($process);
        } else {
            proc_terminate($process, SIGTERM);
        }

        // Wait a bit for graceful shutdown
        sleep(1);

        // Force kill if still running
        $status = proc_get_status($process);
        if ($status['running']) {
            proc_terminate($process, SIGKILL);
        }
    }

    proc_close($process);
    echo "Process terminated\n";
}

/**
 * Show help for watch mode
 */
function showWatchHelp()
{
    echo "PHP Tools Hot Reload Watcher\n";
    echo "Usage: php scripts/watcher.php gui --watch\n";
    echo "       php scripts/watcher.php gui -w\n\n";
    echo "This will monitor PHP files in the app directory and automatically\n";
    echo "restart the GUI application when changes are detected.\n\n";
    echo "Examples:\n";
    echo "  php scripts/watcher.php gui --watch   # Start GUI with hot reload\n";
    echo "  php scripts/watcher.php gui -w        # Start GUI with hot reload (short form)\n";
}

/**
 * Show general help
 */
function showHelp()
{
    echo "PHP Tools Hot Reload Watcher\n";
    echo "Usage: php scripts/watcher.php [command] [options]\n\n";
    echo "Available commands:\n";
    echo "  gui     Start the GUI application with hot reload\n";
    echo "  help    Show this help message\n\n";
    echo "Options:\n";
    echo "  --watch, -w    Enable hot reload monitoring\n\n";
    echo "Examples:\n";
    echo "  php scripts/watcher.php gui --watch   # Start GUI with hot reload\n";
    echo "  php scripts/watcher.php help          # Show help\n";
}