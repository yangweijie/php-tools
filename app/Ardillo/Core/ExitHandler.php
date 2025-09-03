<?php

namespace App\Ardillo\Core;

/**
 * Simple exit handler for Ardillo applications
 */
class ExitHandler
{
    private static bool $shouldExit = false;
    private static mixed $app = null;

    /**
     * Set the application instance
     */
    public static function setApp(mixed $app): void
    {
        self::$app = $app;
    }

    /**
     * Request application exit
     */
    public static function requestExit(): void
    {
        self::$shouldExit = true;
        if (self::$app) {
            self::$app->stop();
        }
        
        // Force exit if app doesn't stop gracefully
        register_shutdown_function(function() {
            if (self::$shouldExit) {
                exit(0);
            }
        });
    }

    /**
     * Check if exit was requested
     */
    public static function shouldExit(): bool
    {
        return self::$shouldExit;
    }

    /**
     * Reset exit state
     */
    public static function reset(): void
    {
        self::$shouldExit = false;
        self::$app = null;
    }
}