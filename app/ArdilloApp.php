<?php

namespace App;

use App\Ardillo\Core\ApplicationFactory;
use App\Ardillo\Core\ApplicationLifecycle;
use App\Ardillo\Core\ApplicationInterface;
use App\Ardillo\Exceptions\ArdilloInitializationException;
use App\Ardillo\Components\ComponentInterface;

/**
 * Main application class that replaces the old kingbes/libui App
 * This class provides a bridge between the old API and new ardillo-based implementation
 */
class ArdilloApp
{
    private ApplicationInterface $application;
    private ApplicationLifecycle $lifecycle;

    public function __construct(array $config = [])
    {
        // Validate system requirements
        $issues = ApplicationFactory::validateSystemRequirements();
        if (!empty($issues)) {
            throw new ArdilloInitializationException(
                "System requirements not met:\n" . implode("\n", $issues)
            );
        }

        // Create application instance
        $this->application = ApplicationFactory::create($config);
        
        // Create lifecycle manager
        $this->lifecycle = new ApplicationLifecycle($this->application);
        
        // Register signal handlers for graceful shutdown
        $this->lifecycle->registerSignalHandlers();
    }

    /**
     * Add a tab to the application
     * Maintains compatibility with old App::addTab method
     */
    public function addTab(string $name, ComponentInterface $control): void
    {
        $this->application->addTab($name, $control);
    }

    /**
     * Run the application
     * Maintains compatibility with old App::run method
     */
    public function run(): void
    {
        $this->lifecycle->start();
    }

    /**
     * Get the main window (for compatibility)
     */
    public function getWindow(): mixed
    {
        if (method_exists($this->application, 'getWindow')) {
            return $this->application->getWindow();
        }
        return null;
    }

    /**
     * Add startup callback
     */
    public function onStartup(callable $callback): void
    {
        $this->lifecycle->addStartupCallback($callback);
    }

    /**
     * Add shutdown callback
     */
    public function onShutdown(callable $callback): void
    {
        $this->lifecycle->addShutdownCallback($callback);
    }

    /**
     * Request application shutdown
     */
    public function shutdown(): void
    {
        $this->lifecycle->shutdown();
    }

    /**
     * Get the underlying application instance
     */
    public function getApplication(): ApplicationInterface
    {
        return $this->application;
    }

    /**
     * Get the lifecycle manager
     */
    public function getLifecycle(): ApplicationLifecycle
    {
        return $this->lifecycle;
    }

    /**
     * Create application for development
     */
    public static function createForDevelopment(): self
    {
        return new self(['log_level' => 'debug']);
    }

    /**
     * Create application for production
     */
    public static function createForProduction(): self
    {
        return new self(['log_level' => 'warning']);
    }
}