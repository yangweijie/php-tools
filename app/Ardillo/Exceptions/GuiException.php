<?php

namespace App\Ardillo\Exceptions;

use Exception;

/**
 * Base exception class for GUI-related errors
 */
abstract class GuiException extends Exception
{
    protected string $userMessage = '';
    protected array $context = [];
    protected bool $recoverable = false;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return $this->userMessage ?: $this->getMessage();
    }

    /**
     * Set user-friendly error message
     */
    public function setUserMessage(string $message): self
    {
        $this->userMessage = $message;
        return $this;
    }

    /**
     * Get technical details for logging
     */
    public function getTechnicalDetails(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            'context' => $this->context,
            'recoverable' => $this->recoverable,
        ];
    }

    /**
     * Get exception context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set exception context
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Check if the error is recoverable
     */
    public function isRecoverable(): bool
    {
        return $this->recoverable;
    }

    /**
     * Set whether the error is recoverable
     */
    public function setRecoverable(bool $recoverable): self
    {
        $this->recoverable = $recoverable;
        return $this;
    }

    /**
     * Get error severity level
     */
    public function getSeverity(): string
    {
        return 'error';
    }

    /**
     * Get suggested recovery actions
     */
    public function getRecoveryActions(): array
    {
        return [];
    }
}