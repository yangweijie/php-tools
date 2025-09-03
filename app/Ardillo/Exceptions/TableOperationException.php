<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when table operations fail
 */
class TableOperationException extends GuiException
{
    protected bool $recoverable = true;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "Table operation failed. Please refresh the data and try again.";
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Refresh table data',
            'Clear table selection',
            'Restart the interface',
            'Check data integrity'
        ];
    }
}