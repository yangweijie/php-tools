<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when ardillo framework initialization fails
 */
class ArdilloInitializationException extends GuiException
{
    protected bool $recoverable = false;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "Failed to initialize the GUI framework. Please ensure ardillo-php/ext is properly installed.";
    }

    public function getSeverity(): string
    {
        return 'critical';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Install ardillo-php/ext extension',
            'Check PHP extension configuration',
            'Verify system requirements',
            'Contact technical support'
        ];
    }
}