<?php

namespace App\Ardillo\Core;

use App\Ardillo\Exceptions\GuiException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Centralized error handling for the application
 */
class ErrorHandler
{
    private LoggerInterface $logger;
    private array $errorCallbacks = [];

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Handle an exception with appropriate logging and user feedback
     */
    public function handleException(\Throwable $exception): void
    {
        // Log the exception
        $this->logException($exception);
        
        // Execute error callbacks
        $this->executeErrorCallbacks($exception);
        
        // Show user-friendly error if it's a GUI exception
        if ($exception instanceof GuiException) {
            $this->showUserError($exception);
        }
    }

    /**
     * Add a callback to be executed when an error occurs
     */
    public function addErrorCallback(callable $callback): void
    {
        $this->errorCallbacks[] = $callback;
    }

    /**
     * Set up global error and exception handlers
     */
    public function registerGlobalHandlers(): void
    {
        // Set exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Set error handler
        set_error_handler([$this, 'handleError']);
        
        $this->logger->info('Global error handlers registered');
    }

    /**
     * Handle PHP errors
     */
    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $exception = new \ErrorException($message, 0, $severity, $file, $line);
        $this->handleException($exception);
        
        return true;
    }

    /**
     * Log exception details
     */
    private function logException(\Throwable $exception): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        if ($exception instanceof GuiException) {
            $context['technical_details'] = $exception->getTechnicalDetails();
        }

        $this->logger->error('Exception occurred', $context);
    }

    /**
     * Execute error callbacks
     */
    private function executeErrorCallbacks(\Throwable $exception): void
    {
        foreach ($this->errorCallbacks as $callback) {
            try {
                call_user_func($callback, $exception);
            } catch (\Exception $e) {
                $this->logger->warning('Error callback failed', [
                    'callback_error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Show user-friendly error message
     */
    private function showUserError(GuiException $exception): void
    {
        try {
            $userMessage = $exception->getUserMessage();
            $severity = $exception->getSeverity();
            $recoveryActions = $exception->getRecoveryActions();
            
            // Try to show a GUI error dialog if possible
            if (class_exists('\\Ardillo\\MessageBox')) {
                $title = $this->getSeverityTitle($severity);
                $message = $userMessage;
                
                // Add recovery actions if available
                if (!empty($recoveryActions)) {
                    $message .= "\n\nSuggested actions:\n";
                    foreach ($recoveryActions as $action) {
                        $message .= "• " . $action . "\n";
                    }
                }
                
                // Show appropriate message box based on severity
                switch ($severity) {
                    case 'critical':
                        \Ardillo\MessageBox::error($title, $message);
                        break;
                    case 'error':
                        \Ardillo\MessageBox::error($title, $message);
                        break;
                    case 'warning':
                        if (method_exists('\\Ardillo\\MessageBox', 'warning')) {
                            \Ardillo\MessageBox::warning($title, $message);
                        } else {
                            \Ardillo\MessageBox::error($title, $message);
                        }
                        break;
                    default:
                        \Ardillo\MessageBox::error($title, $message);
                        break;
                }
            } else {
                // Fallback to stderr with enhanced formatting
                $output = $this->formatConsoleError($exception);
                fwrite(STDERR, $output);
            }
        } catch (\Exception $e) {
            // If we can't show the error, just log it
            $this->logger->error('Failed to show user error', [
                'original_error' => $exception->getMessage(),
                'display_error' => $e->getMessage()
            ]);
            
            // Last resort: basic stderr output
            fwrite(STDERR, "Error: " . $exception->getUserMessage() . PHP_EOL);
        }
    }

    /**
     * Create a formatted error report
     */
    public function createErrorReport(\Throwable $exception): string
    {
        $report = "Error Report\n";
        $report .= "============\n\n";
        $report .= "Exception: " . get_class($exception) . "\n";
        $report .= "Message: " . $exception->getMessage() . "\n";
        $report .= "File: " . $exception->getFile() . "\n";
        $report .= "Line: " . $exception->getLine() . "\n";
        $report .= "Time: " . date('Y-m-d H:i:s') . "\n\n";
        
        if ($exception instanceof GuiException) {
            $report .= "Severity: " . $exception->getSeverity() . "\n";
            $report .= "Recoverable: " . ($exception->isRecoverable() ? 'Yes' : 'No') . "\n";
            $report .= "User Message: " . $exception->getUserMessage() . "\n\n";
            
            $recoveryActions = $exception->getRecoveryActions();
            if (!empty($recoveryActions)) {
                $report .= "Recovery Actions:\n";
                foreach ($recoveryActions as $action) {
                    $report .= "- " . $action . "\n";
                }
                $report .= "\n";
            }
            
            $context = $exception->getContext();
            if (!empty($context)) {
                $report .= "Context:\n";
                $report .= json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
            }
        }
        
        $report .= "Stack Trace:\n";
        $report .= $exception->getTraceAsString() . "\n\n";
        
        if ($exception instanceof GuiException) {
            $report .= "Technical Details:\n";
            $report .= json_encode($exception->getTechnicalDetails(), JSON_PRETTY_PRINT) . "\n";
        }
        
        return $report;
    }

    /**
     * Get severity title for display
     */
    private function getSeverityTitle(string $severity): string
    {
        switch ($severity) {
            case 'critical':
                return 'Critical Error';
            case 'error':
                return 'Error';
            case 'warning':
                return 'Warning';
            default:
                return 'Error';
        }
    }

    /**
     * Format error for console output
     */
    private function formatConsoleError(GuiException $exception): string
    {
        $output = "\n" . str_repeat('=', 60) . "\n";
        $output .= strtoupper($exception->getSeverity()) . ": " . $exception->getUserMessage() . "\n";
        $output .= str_repeat('-', 60) . "\n";
        
        $recoveryActions = $exception->getRecoveryActions();
        if (!empty($recoveryActions)) {
            $output .= "Suggested actions:\n";
            foreach ($recoveryActions as $action) {
                $output .= "• " . $action . "\n";
            }
        }
        
        $output .= str_repeat('=', 60) . "\n\n";
        
        return $output;
    }

    /**
     * Handle graceful degradation for framework failures
     */
    public function handleGracefulDegradation(\Throwable $exception): bool
    {
        if (!($exception instanceof GuiException)) {
            return false;
        }

        if (!$exception->isRecoverable()) {
            $this->logger->critical('Non-recoverable error occurred', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage()
            ]);
            return false;
        }

        $this->logger->warning('Attempting graceful degradation', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'recovery_actions' => $exception->getRecoveryActions()
        ]);

        // Attempt recovery based on exception type
        try {
            switch (get_class($exception)) {
                case 'App\\Ardillo\\Exceptions\\ComponentInitializationException':
                    return $this->recoverFromComponentFailure($exception);
                    
                case 'App\\Ardillo\\Exceptions\\TableOperationException':
                    return $this->recoverFromTableFailure($exception);
                    
                case 'App\\Ardillo\\Exceptions\\SystemCommandException':
                    return $this->recoverFromCommandFailure($exception);
                    
                default:
                    return $this->attemptGenericRecovery($exception);
            }
        } catch (\Exception $recoveryException) {
            $this->logger->error('Recovery attempt failed', [
                'original_exception' => $exception->getMessage(),
                'recovery_exception' => $recoveryException->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Recover from component initialization failure
     */
    private function recoverFromComponentFailure(GuiException $exception): bool
    {
        $this->logger->info('Attempting component recovery');
        
        // Try to reinitialize components with fallback options
        // This would involve resetting component state and trying again
        
        return true; // Assume recovery is possible for components
    }

    /**
     * Recover from table operation failure
     */
    private function recoverFromTableFailure(GuiException $exception): bool
    {
        $this->logger->info('Attempting table recovery');
        
        // Try to clear table state and reinitialize
        // This would involve clearing selections and refreshing data
        
        return true; // Assume recovery is possible for tables
    }

    /**
     * Recover from system command failure
     */
    private function recoverFromCommandFailure(GuiException $exception): bool
    {
        $this->logger->info('Attempting command recovery');
        
        // Try alternative commands or fallback methods
        // This would involve using different system commands
        
        return false; // System command failures are harder to recover from
    }

    /**
     * Attempt generic recovery
     */
    private function attemptGenericRecovery(GuiException $exception): bool
    {
        $this->logger->info('Attempting generic recovery');
        
        // Generic recovery strategies
        // This would involve basic cleanup and state reset
        
        return $exception->isRecoverable();
    }
}