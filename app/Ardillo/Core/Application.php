<?php

namespace App\Ardillo\Core;

use App\Ardillo\Components\ComponentInterface;
use App\Ardillo\Exceptions\ArdilloInitializationException;

/**
 * Main application class for ardillo-based GUI
 */
abstract class Application implements ApplicationInterface
{
    protected array $tabs = [];
    protected mixed $mainWindow = null;
    protected bool $initialized = false;

    /**
     * Initialize the application and GUI framework
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $this->initializeArdillo();
            $this->createMainWindow();
            $this->initialized = true;
        } catch (\Exception $e) {
            throw new ArdilloInitializationException(
                "Failed to initialize ardillo application: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Add a tab to the application with the given component
     */
    public function addTab(string $name, ComponentInterface $component): void
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $component->initialize();
        $this->tabs[$name] = $component;
        $this->addTabToWindow($name, $component);
    }

    /**
     * Start the main application event loop
     */
    public function run(): void
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $this->startEventLoop();
    }

    /**
     * Shutdown the application and cleanup resources
     */
    public function shutdown(): void
    {
        foreach ($this->tabs as $component) {
            $component->cleanup();
        }

        $this->cleanupArdillo();
        $this->initialized = false;
    }

    /**
     * Initialize the ardillo framework
     */
    abstract protected function initializeArdillo(): void;

    /**
     * Create the main application window
     */
    abstract protected function createMainWindow(): void;

    /**
     * Add a tab to the main window
     */
    abstract protected function addTabToWindow(string $name, ComponentInterface $component): void;

    /**
     * Start the main event loop
     */
    abstract protected function startEventLoop(): void;

    /**
     * Cleanup ardillo resources
     */
    abstract protected function cleanupArdillo(): void;
}