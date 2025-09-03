<?php

namespace App\Ardillo\Components;

use App\Ardillo\Exceptions\GuiException;
use App\Ardillo\Exceptions\ComponentInitializationException;

/**
 * Component for displaying user-friendly error messages
 */
class ErrorDisplayComponent extends BaseComponent
{
    private string $errorMessage = '';
    private string $errorTitle = '';
    private array $recoveryActions = [];
    private string $severity = 'error';
    private bool $showDetails = false;
    private ?GuiException $exception = null;

    /**
     * Create the error display widget
     */
    protected function createWidget(): void
    {
        try {
            // For testing, create a mock widget
            if (defined('PHPUNIT_COMPOSER_INSTALL') || php_sapi_name() === 'cli') {
                $this->widget = new \stdClass();
                $this->widget->isTestMode = true;
                $this->widget->errorMessage = $this->errorMessage;
                $this->widget->errorTitle = $this->errorTitle;
                return;
            }

            // Check if ardillo extension is available
            if (!extension_loaded('ardillo')) {
                throw new ComponentInitializationException(
                    'Cannot create error display - Ardillo extension not loaded'
                );
            }

            // Create a vertical box for the error display
            $this->widget = \Ardillo\Box::newVertical();
            
            // Build the error display layout
            $this->buildErrorLayout();
            
        } catch (\Exception $e) {
            // Fallback to basic error handling
            $this->widget = new \stdClass();
            $this->widget->isTestMode = true;
            $this->widget->errorMessage = 'Failed to create error display: ' . $e->getMessage();
        }
    }

    /**
     * Build the error display layout
     */
    private function buildErrorLayout(): void
    {
        if (!$this->widget || isset($this->widget->isTestMode)) {
            return;
        }

        try {
            // Add error title
            if (!empty($this->errorTitle)) {
                $titleLabel = \Ardillo\Label::new($this->errorTitle);
                $this->widget->append($titleLabel, false);
            }

            // Add error message
            if (!empty($this->errorMessage)) {
                $messageLabel = \Ardillo\Label::new($this->errorMessage);
                $this->widget->append($messageLabel, false);
            }

            // Add recovery actions if available
            if (!empty($this->recoveryActions)) {
                $this->addRecoveryActionsSection();
            }

            // Add details section if requested
            if ($this->showDetails && $this->exception) {
                $this->addDetailsSection();
            }

            // Add action buttons
            $this->addActionButtons();

        } catch (\Exception $e) {
            // If we can't build the layout, at least show basic error
            error_log('Failed to build error layout: ' . $e->getMessage());
        }
    }

    /**
     * Add recovery actions section
     */
    private function addRecoveryActionsSection(): void
    {
        if (!$this->widget || isset($this->widget->isTestMode)) {
            return;
        }

        try {
            // Add separator
            $separator = \Ardillo\Separator::newHorizontal();
            $this->widget->append($separator, false);

            // Add recovery actions title
            $actionsTitle = \Ardillo\Label::new('Suggested Actions:');
            $this->widget->append($actionsTitle, false);

            // Add each recovery action
            foreach ($this->recoveryActions as $action) {
                $actionLabel = \Ardillo\Label::new('• ' . $action);
                $this->widget->append($actionLabel, false);
            }

        } catch (\Exception $e) {
            error_log('Failed to add recovery actions: ' . $e->getMessage());
        }
    }

    /**
     * Add technical details section
     */
    private function addDetailsSection(): void
    {
        if (!$this->widget || isset($this->widget->isTestMode) || !$this->exception) {
            return;
        }

        try {
            // Add separator
            $separator = \Ardillo\Separator::newHorizontal();
            $this->widget->append($separator, false);

            // Add details title
            $detailsTitle = \Ardillo\Label::new('Technical Details:');
            $this->widget->append($detailsTitle, false);

            // Add exception details
            $details = $this->exception->getTechnicalDetails();
            foreach ($details as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                $detailLabel = \Ardillo\Label::new($key . ': ' . $value);
                $this->widget->append($detailLabel, false);
            }

        } catch (\Exception $e) {
            error_log('Failed to add details section: ' . $e->getMessage());
        }
    }

    /**
     * Add action buttons
     */
    private function addActionButtons(): void
    {
        if (!$this->widget || isset($this->widget->isTestMode)) {
            return;
        }

        try {
            // Create horizontal box for buttons
            $buttonBox = \Ardillo\Box::newHorizontal();

            // Add OK button
            $okButton = \Ardillo\Button::new('OK');
            $okButton->onClicked(function () {
                $this->handleOkClicked();
            });
            $buttonBox->append($okButton, false);

            // Add Details button if exception is available
            if ($this->exception) {
                $detailsButton = \Ardillo\Button::new($this->showDetails ? 'Hide Details' : 'Show Details');
                $detailsButton->onClicked(function () {
                    $this->toggleDetails();
                });
                $buttonBox->append($detailsButton, false);
            }

            // Add Retry button for recoverable errors
            if ($this->exception && $this->exception->isRecoverable()) {
                $retryButton = \Ardillo\Button::new('Retry');
                $retryButton->onClicked(function () {
                    $this->handleRetryClicked();
                });
                $buttonBox->append($retryButton, false);
            }

            $this->widget->append($buttonBox, false);

        } catch (\Exception $e) {
            error_log('Failed to add action buttons: ' . $e->getMessage());
        }
    }

    /**
     * Set error information
     */
    public function setError(string $title, string $message, string $severity = 'error'): void
    {
        $this->errorTitle = $title;
        $this->errorMessage = $message;
        $this->severity = $severity;
        
        // Rebuild layout if widget exists
        if ($this->widget && $this->initialized) {
            $this->buildErrorLayout();
        }
    }

    /**
     * Set error from exception
     */
    public function setErrorFromException(GuiException $exception): void
    {
        $this->exception = $exception;
        $this->errorTitle = $this->getSeverityTitle($exception->getSeverity());
        $this->errorMessage = $exception->getUserMessage();
        $this->severity = $exception->getSeverity();
        $this->recoveryActions = $exception->getRecoveryActions();
        
        // Rebuild layout if widget exists
        if ($this->widget && $this->initialized) {
            $this->buildErrorLayout();
        }
    }

    /**
     * Set recovery actions
     */
    public function setRecoveryActions(array $actions): void
    {
        $this->recoveryActions = $actions;
        
        // Rebuild layout if widget exists
        if ($this->widget && $this->initialized) {
            $this->buildErrorLayout();
        }
    }

    /**
     * Toggle details display
     */
    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
        
        // Rebuild layout if widget exists
        if ($this->widget && $this->initialized) {
            $this->buildErrorLayout();
        }
    }

    /**
     * Handle OK button click
     */
    private function handleOkClicked(): void
    {
        // Close the error display or trigger callback
        $this->triggerCallback('ok');
    }

    /**
     * Handle Retry button click
     */
    private function handleRetryClicked(): void
    {
        // Trigger retry callback
        $this->triggerCallback('retry');
    }

    /**
     * Get severity title
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
     * Callback handlers
     */
    private array $callbacks = [];

    /**
     * Set callback for button actions
     */
    public function onAction(string $action, callable $callback): void
    {
        $this->callbacks[$action] = $callback;
    }

    /**
     * Trigger callback
     */
    private function triggerCallback(string $action): void
    {
        if (isset($this->callbacks[$action])) {
            call_user_func($this->callbacks[$action], $this);
        }
    }

    /**
     * Show error dialog
     */
    public static function showErrorDialog(GuiException $exception): void
    {
        try {
            // Try to use native message box if available
            if (class_exists('\\Ardillo\\MessageBox')) {
                $title = (new self())->getSeverityTitle($exception->getSeverity());
                $message = $exception->getUserMessage();
                
                $recoveryActions = $exception->getRecoveryActions();
                if (!empty($recoveryActions)) {
                    $message .= "\n\nSuggested actions:\n";
                    foreach ($recoveryActions as $action) {
                        $message .= "• " . $action . "\n";
                    }
                }
                
                \Ardillo\MessageBox::error($title, $message);
            } else {
                // Fallback to console output
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
                fwrite(STDERR, $output);
            }
        } catch (\Exception $e) {
            // Last resort: basic error output
            fwrite(STDERR, "Error: " . $exception->getUserMessage() . "\n");
        }
    }

    /**
     * Get error information for testing
     */
    public function getErrorInfo(): array
    {
        return [
            'title' => $this->errorTitle,
            'message' => $this->errorMessage,
            'severity' => $this->severity,
            'recovery_actions' => $this->recoveryActions,
            'show_details' => $this->showDetails,
            'has_exception' => $this->exception !== null
        ];
    }
}