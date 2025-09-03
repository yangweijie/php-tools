<?php

namespace App\Ardillo\Exceptions;

/**
 * Exception thrown when data validation fails
 */
class DataValidationException extends GuiException
{
    protected bool $recoverable = true;

    public function getUserMessage(): string
    {
        return $this->userMessage ?: "Invalid input data. Please check your input and try again.";
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getRecoveryActions(): array
    {
        return [
            'Check input format',
            'Verify data requirements',
            'Clear and re-enter data'
        ];
    }
}