<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when process/port killing operations fail
 */
class ProcessKillException extends GuiException
{
    protected bool $recoverable = true;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "Failed to kill the selected process/port. You may need administrator privileges.";
    }

    public function getSeverity(): string
    {
        return 'error';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Run as administrator',
            'Check process permissions',
            'Verify process is not protected',
            'Try killing parent process first'
        ];
    }
}