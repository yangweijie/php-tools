<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when network-related operations fail
 */
class NetworkException extends GuiException
{
    protected bool $recoverable = true;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "Network operation failed. Please check your connection and try again.";
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Check network connection',
            'Retry the operation',
            'Check firewall settings'
        ];
    }
}