<?php

namespace App\Ardillo\Components;

/**
 * Base abstract class for GUI components
 */
abstract class BaseComponent implements ComponentInterface
{
    protected mixed $widget = null;
    protected bool $initialized = false;

    /**
     * Initialize the component
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->createWidget();
        $this->setupEventHandlers();
        $this->initialized = true;
    }

    /**
     * Get the native widget/control for this component
     */
    public function getWidget(): mixed
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->widget;
    }

    /**
     * Get the control (alias for getWidget for backward compatibility)
     */
    public function getControl(): mixed
    {
        return $this->getWidget();
    }

    /**
     * Check if component is initialized
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Cleanup component resources
     */
    public function cleanup(): void
    {
        if ($this->widget !== null) {
            $this->destroyWidget();
            $this->widget = null;
        }
        $this->initialized = false;
    }

    /**
     * Create the native widget for this component
     */
    abstract protected function createWidget(): void;

    /**
     * Setup event handlers for the widget
     */
    protected function setupEventHandlers(): void
    {
        // Default implementation - override in subclasses as needed
    }

    /**
     * Destroy the native widget
     */
    protected function destroyWidget(): void
    {
        // Default implementation - override in subclasses as needed
    }
}