<?php

namespace App\Ardillo\Services;

/**
 * Base interface for service classes
 */
interface ServiceInterface
{
    /**
     * Initialize the service
     */
    public function initialize(): void;

    /**
     * Check if the service is available and ready
     */
    public function isAvailable(): bool;
}