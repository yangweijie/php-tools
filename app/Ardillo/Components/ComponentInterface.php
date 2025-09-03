<?php

namespace App\Ardillo\Components;

/**
 * Base interface for all GUI components
 */
interface ComponentInterface
{
    /**
     * Initialize the component
     */
    public function initialize(): void;

    /**
     * Check if component is initialized
     */
    public function isInitialized(): bool;

    /**
     * Get the native widget/control for this component
     */
    public function getWidget(): mixed;

    /**
     * Get the control (alias for getWidget for backward compatibility)
     */
    public function getControl(): mixed;

    /**
     * Cleanup component resources
     */
    public function cleanup(): void;
}