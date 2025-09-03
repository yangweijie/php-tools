<?php

namespace App\Ardillo\Core;

use App\Ardillo\Exceptions\ArdilloInitializationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Manages application lifecycle events and cleanup
 */
class ApplicationLifecycle
{
    private ApplicationInterface $application;
    private LoggerInterface $logger;
    private array $shutdownCallbacks = [];
    private array $startupCallbacks = [];
    private bool $shutdownRegistered = false;

    public function __construct(ApplicationInterface $application, LoggerInterface $logger = null)
    {
        $this->application = $application;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Start the application with proper lifecycle management
     */
    public function start(): void
    {
        try {
            $this->logger->info('Starting application lifecycle');
            
            // Register shutdown handler
            $this->registerShutdownHandler();
            
            // Execute startup callbacks
            $this->executeStartupCallbacks();
            
            // Initialize and run application
            $this->application->initialize();
            $this->application->run();
            
        } catch (\Exception $e) {
            $this->logger->error('Error during application startup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Attempt cleanup on error
            $this->shutdown();
            
            throw $e;
        }
    }

    /**
     * Shutdown the application gracefully
     */
    public function shutdown(): void
    {
        try {
            $this->logger->info('Starting application shutdown');
            
            // Execute shutdown callbacks
            $this->executeShutdownCallbacks();
            
            // Shutdown application
            $this->application->shutdown();
            
            $this->logger->info('Application shutdown completed');
            
        } catch (\Exception $e) {
            $this->logger->error('Error during application shutdown', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Add a callback to be executed during startup
     */
    public function addStartupCallback(callable $callback): void
    {
        $this->startupCallbacks[] = $callback;
    }

    /**
     * Add a callback to be executed during shutdown
     */
    public function addShutdownCallback(callable $callback): void
    {
        $this->shutdownCallbacks[] = $callback;
    }

    /**
     * Register signal handlers for graceful shutdown
     */
    public function registerSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            // Handle SIGTERM
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            
            // Handle SIGINT (Ctrl+C)
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            
            $this->logger->info('Signal handlers registered');
        }
    }

    /**
     * Handle system signals
     */
    public function handleSignal(int $signal): void
    {
        $this->logger->info("Received signal: {$signal}");
        
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->shutdown();
                exit(0);
                break;
        }
    }

    /**
     * Register PHP shutdown handler
     */
    private function registerShutdownHandler(): void
    {
        if (!$this->shutdownRegistered) {
            register_shutdown_function([$this, 'handlePhpShutdown']);
            $this->shutdownRegistered = true;
        }
    }

    /**
     * Handle PHP shutdown
     */
    public function handlePhpShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->critical('Fatal error detected during shutdown', [
                'error' => $error
            ]);
        }
        
        $this->shutdown();
    }

    /**
     * Execute startup callbacks
     */
    private function executeStartupCallbacks(): void
    {
        foreach ($this->startupCallbacks as $callback) {
            try {
                call_user_func($callback);
            } catch (\Exception $e) {
                $this->logger->warning('Startup callback failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Execute shutdown callbacks
     */
    private function executeShutdownCallbacks(): void
    {
        foreach ($this->shutdownCallbacks as $callback) {
            try {
                call_user_func($callback);
            } catch (\Exception $e) {
                $this->logger->warning('Shutdown callback failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get the managed application instance
     */
    public function getApplication(): ApplicationInterface
    {
        return $this->application;
    }
}