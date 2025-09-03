<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when GUI component initialization fails
 */
class ComponentInitializationException extends GuiException
{
    protected bool $recoverable = true;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "Failed to initialize GUI component. Please try refreshing the interface.";
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Refresh the interface',
            'Restart the application',
            'Check system resources'
        ];
    }
}