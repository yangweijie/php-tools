<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when system command execution fails
 */
class SystemCommandException extends GuiException
{
    protected bool $recoverable = true;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "System command execution failed. Please check your permissions and try again.";
    }

    public function getSeverity(): string
    {
        return 'error';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Check system permissions',
            'Verify command availability',
            'Run as administrator',
            'Check system resources'
        ];
    }
}