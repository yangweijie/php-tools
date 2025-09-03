<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when operations fail due to insufficient permissions
 */
class PermissionException extends GuiException
{
    protected bool $recoverable = true;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "Insufficient permissions to perform this operation. Please run as administrator or check your privileges.";
    }

    public function getSeverity(): string
    {
        return 'error';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Run as administrator',
            'Check user permissions',
            'Contact system administrator'
        ];
    }
}