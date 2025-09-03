<?php

namespace App\Ardillo\Core;

use App\Ardillo\Components\ComponentInterface;

/**
 * Core application interface for ardillo-based GUI application
 */
interface ApplicationInterface
{
    /**
     * Initialize the application and GUI framework
     */
    public function initialize(): void;

    /**
     * Add a tab to the application with the given component
     */
    public function addTab(string $name, ComponentInterface $component): void;

    /**
     * Start the main application event loop
     */
    public function run(): void;

    /**
     * Shutdown the application and cleanup resources
     */
    public function shutdown(): void;
}