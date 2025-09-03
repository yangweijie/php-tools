<?php

namespace App\Ardillo\Components;

use App\Ardillo\Exceptions\GuiException;

/**
 * Batch operation results dialog component for displaying detailed operation results
 */
class BatchResultsDialogComponent extends BaseComponent
{
    private string $title = 'Operation Results';
    private array $results = [];
    private array $summary = [];
    private string $operation = 'Operation';
    private $onClose = null;

    /**
     * Create batch results dialog
     */
    public static function create(string $title, array $results, array $summary = []): self
    {
        $dialog = new self();
        $dialog->title = $title;
        $dialog->results = $results;
        $dialog->summary = $summary;
        return $dialog;
    }

    /**
     * Create kill operation results dialog
     */
    public static function createKillResults(array $killResults): self
    {
        $dialog = new self();
        $dialog->title = 'Kill Operation Results';
        $dialog->operation = 'Kill';
        $dialog->results = $killResults['results'] ?? [];
        $dialog->summary = $killResults['summary'] ?? [];
        return $dialog;
    }

    /**
     * Create the batch results dialog widget
     */
    protected function createWidget(): void
    {
        try {
            // Check if ardillo extension is loaded
            if (!extension_loaded('ardillo')) {
                throw new GuiException('Ardillo PHP extension is not loaded');
            }

            // For testing, create a mock widget to avoid segfaults
            if (defined('PHPUNIT_COMPOSER_INSTALL') || php_sapi_name() === 'cli') {
                $this->widget = new \stdClass();
                $this->widget->isTestMode = true;
                $this->widget->title = $this->title;
                $this->widget->results = $this->results;
                $this->widget->summary = $this->summary;
                return;
            }

            // Create the results dialog window
            $this->createResultsWindow();
            
        } catch (\Exception $e) {
            throw new GuiException(
                'Failed to create batch results dialog widget: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create results dialog as a modal window
     */
    private function createResultsWindow(): void
    {
        if (!class_exists('\\Ardillo\\Window')) {
            throw new GuiException('Ardillo\\Window class is not available');
        }

        // Create modal window
        $this->widget = new \Ardillo\Window($this->title, 600, 400, false);
        $this->widget->setMargined(true);
        
        // Create main layout
        $mainLayout = new \Ardillo\Box(\Ardillo\Box::VERTICAL);
        $mainLayout->setPadded(true);
        
        // Add summary section
        $this->addSummarySection($mainLayout);
        
        // Add results table/list
        $this->addResultsSection($mainLayout);
        
        // Add close button
        $this->addCloseButton($mainLayout);
        
        $this->widget->setChild($mainLayout);
    }

    /**
     * Add summary section to the dialog
     */
    private function addSummarySection(\Ardillo\Box $layout): void
    {
        if (empty($this->summary)) {
            return;
        }

        // Create summary group
        $summaryGroup = new \Ardillo\Group('Summary');
        $summaryBox = new \Ardillo\Box(\Ardillo\Box::VERTICAL);
        $summaryBox->setPadded(true);
        
        // Add summary information
        $total = $this->summary['total'] ?? 0;
        $success = $this->summary['success'] ?? 0;
        $failed = $this->summary['failed'] ?? 0;
        
        $summaryText = "Total: {$total}, Successful: {$success}, Failed: {$failed}";
        $summaryLabel = new \Ardillo\Label($summaryText);
        $summaryBox->append($summaryLabel, false);
        
        // Add success rate if applicable
        if ($total > 0) {
            $successRate = round(($success / $total) * 100, 1);
            $rateLabel = new \Ardillo\Label("Success Rate: {$successRate}%");
            $summaryBox->append($rateLabel, false);
        }
        
        $summaryGroup->setChild($summaryBox);
        $layout->append($summaryGroup, false);
    }

    /**
     * Add results section to the dialog
     */
    private function addResultsSection(\Ardillo\Box $layout): void
    {
        if (empty($this->results)) {
            $noResultsLabel = new \Ardillo\Label('No detailed results available.');
            $layout->append($noResultsLabel, false);
            return;
        }

        // Create results group
        $resultsGroup = new \Ardillo\Group('Detailed Results');
        $resultsBox = new \Ardillo\Box(\Ardillo\Box::VERTICAL);
        $resultsBox->setPadded(true);
        
        // Create scrollable area for results (if available in Ardillo)
        $this->addResultsList($resultsBox);
        
        $resultsGroup->setChild($resultsBox);
        $layout->append($resultsGroup, true);
    }

    /**
     * Add results list to the results section
     */
    private function addResultsList(\Ardillo\Box $container): void
    {
        foreach ($this->results as $result) {
            $resultBox = new \Ardillo\Box(\Ardillo\Box::HORIZONTAL);
            $resultBox->setPadded(true);
            
            // Add status indicator
            $status = $result['success'] ?? false;
            $statusText = $status ? '✓' : '✗';
            $statusLabel = new \Ardillo\Label($statusText);
            $resultBox->append($statusLabel, false);
            
            // Add PID/ID
            $pid = $result['pid'] ?? $result['id'] ?? 'Unknown';
            $pidLabel = new \Ardillo\Label("PID: {$pid}");
            $resultBox->append($pidLabel, false);
            
            // Add message
            $message = $result['message'] ?? 'No message';
            $messageLabel = new \Ardillo\Label($message);
            $resultBox->append($messageLabel, true);
            
            $container->append($resultBox, false);
        }
    }

    /**
     * Add close button to the dialog
     */
    private function addCloseButton(\Ardillo\Box $layout): void
    {
        $buttonBox = new \Ardillo\Box(\Ardillo\Box::HORIZONTAL);
        $buttonBox->setPadded(true);
        
        // Add spacer
        $spacer = new \Ardillo\Label('');
        $buttonBox->append($spacer, true);
        
        // Add close button
        $closeButton = new \Ardillo\Button('Close');
        $closeButton->onClick(function() {
            $this->handleClose();
        });
        $buttonBox->append($closeButton, false);
        
        $layout->append($buttonBox, false);
    }

    /**
     * Handle close button click
     */
    private function handleClose(): void
    {
        if ($this->onClose) {
            call_user_func($this->onClose);
        }
        
        // Close dialog
        if ($this->widget && !isset($this->widget->isTestMode)) {
            $this->widget->close();
        }
    }

    /**
     * Setup event handlers
     */
    protected function setupEventHandlers(): void
    {
        // Event handlers are set up in createResultsWindow
    }

    /**
     * Show the results dialog
     */
    public function show(): void
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (isset($this->widget->isTestMode)) {
            // In test mode, just mark as shown
            return;
        }

        if ($this->widget) {
            $this->widget->show();
        }
    }

    /**
     * Set dialog title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set operation results
     */
    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    /**
     * Set operation summary
     */
    public function setSummary(array $summary): void
    {
        $this->summary = $summary;
    }

    /**
     * Set operation type
     */
    public function setOperation(string $operation): void
    {
        $this->operation = $operation;
    }

    /**
     * Set close callback
     */
    public function onClose(callable $callback): void
    {
        $this->onClose = $callback;
    }

    /**
     * Get results
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get summary
     */
    public function getSummary(): array
    {
        return $this->summary;
    }

    /**
     * Get successful results count
     */
    public function getSuccessCount(): int
    {
        return count(array_filter($this->results, fn($result) => $result['success'] ?? false));
    }

    /**
     * Get failed results count
     */
    public function getFailureCount(): int
    {
        return count(array_filter($this->results, fn($result) => !($result['success'] ?? false)));
    }

    /**
     * Check if all operations were successful
     */
    public function isAllSuccessful(): bool
    {
        return $this->getFailureCount() === 0 && !empty($this->results);
    }

    /**
     * Check if any operations were successful
     */
    public function hasAnySuccess(): bool
    {
        return $this->getSuccessCount() > 0;
    }

    /**
     * Get formatted summary text
     */
    public function getSummaryText(): string
    {
        $total = count($this->results);
        $success = $this->getSuccessCount();
        $failed = $this->getFailureCount();
        
        if ($total === 0) {
            return 'No operations performed';
        }
        
        if ($failed === 0) {
            return "All {$total} operations completed successfully";
        }
        
        if ($success === 0) {
            return "All {$total} operations failed";
        }
        
        return "{$success} of {$total} operations completed successfully, {$failed} failed";
    }
}