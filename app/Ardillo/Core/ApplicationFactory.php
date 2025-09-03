<?php

namespace App\Ardillo\Core;

use App\Ardillo\Services\LoggingService;
use App\Ardillo\Exceptions\ArdilloInitializationException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Factory for creating and configuring Application instances
 */
class ApplicationFactory
{
    /**
     * Create a new ArdilloApplication instance with proper configuration
     */
    public static function create(array $config = []): ApplicationInterface
    {
        try {
            // Create logger
            $logger = self::createLogger($config);
            
            // Create application instance
            $application = new ArdilloApplication($logger);
            
            $logger->info('Application created successfully', [
                'config' => $config
            ]);
            
            return $application;
            
        } catch (\Exception $e) {
            throw new ArdilloInitializationException(
                "Failed to create application: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create logger instance based on configuration
     */
    private static function createLogger(array $config): LoggerInterface
    {
        $logFile = $config['log_file'] ?? null;
        $logLevel = $config['log_level'] ?? LogLevel::INFO;
        
        return new LoggingService($logFile, $logLevel);
    }

    /**
     * Create application with development configuration
     */
    public static function createForDevelopment(): ApplicationInterface
    {
        return self::create([
            'log_level' => LogLevel::DEBUG,
            'log_file' => sys_get_temp_dir() . '/ardillo_app_dev.log'
        ]);
    }

    /**
     * Create application with production configuration
     */
    public static function createForProduction(): ApplicationInterface
    {
        return self::create([
            'log_level' => LogLevel::WARNING,
            'log_file' => sys_get_temp_dir() . '/ardillo_app.log'
        ]);
    }

    /**
     * Validate system requirements for running the application
     */
    public static function validateSystemRequirements(): array
    {
        $issues = [];
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $issues[] = 'PHP 8.1.0 or higher is required';
        }
        
        // Check if ardillo extension is loaded
        if (!extension_loaded('ardillo')) {
            $issues[] = 'Ardillo PHP extension is not loaded';
        }
        
        // Only check classes if extension is loaded
        if (extension_loaded('ardillo')) {
            // Check if required classes exist
            $requiredClasses = [
                '\\Ardillo\\App',
                '\\Ardillo\\Window',
                '\\Ardillo\\Tab'
            ];
            
            foreach ($requiredClasses as $class) {
                if (!class_exists($class)) {
                    $issues[] = "Required class {$class} is not available";
                }
            }
        }
        
        return $issues;
    }

    /**
     * Check if the system can run the application
     */
    public static function canRun(): bool
    {
        return empty(self::validateSystemRequirements());
    }
}