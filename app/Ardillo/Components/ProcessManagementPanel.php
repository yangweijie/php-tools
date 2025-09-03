<?php

namespace App\Ardillo\Components;

use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Exceptions\GuiException;
use App\Ardillo\Exceptions\SystemCommandException;

/**
 * Process management panel with input controls, table, and action buttons
 */
class ProcessManagementPanel extends BaseComponent
{
    private ProcessManager $processManager;
    private LayoutContainer $mainLayout;
    private LayoutContainer $controlsLayout;
    private InputComponent $processInput;
    private ButtonComponent $queryButton;
    private ButtonComponent $refreshButton;
    private ButtonComponent $killSelectedButton;
    private ButtonComponent $selectAllButton;
    private ButtonComponent $clearSelectionButton;
    private TableComponent $processTable;
    private array $currentProcessData = [];
    private StatusMessageComponent $statusMessage;
    private ProgressIndicatorComponent $progressIndicator;

    public function __construct(ProcessManager $processManager)
    {
        $this->processManager = $processManager;
    }

    /**
     * Create the process management panel widget
     */
    protected function createWidget(): void
    {
        try {
            // Create main vertical layout
            $this->mainLayout = LayoutContainer::createVertical(true);
            
            // Create controls layout (horizontal)
            $this->controlsLayout = LayoutContainer::createHorizontal(true);
            
            // Create input components
            $this->createInputControls();
            
            // Create action buttons
            $this->createActionButtons();
            
            // Create table component
            $this->createTableComponent();
            
            // Create feedback components
            $this->createFeedbackComponents();
            
            // Build the layout
            $this->buildLayout();
            
            // Initialize all components
            $this->initializeComponents();
            
            // Use the main layout as our widget
            $this->widget = $this->mainLayout->getControl();
            
        } catch (\Exception $e) {
            throw new GuiException(
                'Failed to create process management panel: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Create input controls
     */
    private function createInputControls(): void
    {
        // Process name/PID input
        $this->processInput = new InputComponent();
        $this->processInput->setPlaceholder('Enter process name or PID (optional)');
        
        // Query button
        $this->queryButton = ButtonComponent::createPrimary('Query Processes');
        
        // Refresh button
        $this->refreshButton = new ButtonComponent();
        $this->refreshButton->setText('Refresh');
    }

    /**
     * Create action buttons
     */
    private function createActionButtons(): void
    {
        // Kill selected button
        $this->killSelectedButton = ButtonComponent::createDanger('Kill Selected');
        
        // Select all button
        $this->selectAllButton = new ButtonComponent();
        $this->selectAllButton->setText('Select All');
        
        // Clear selection button
        $this->clearSelectionButton = new ButtonComponent();
        $this->clearSelectionButton->setText('Clear Selection');
    }

    /**
     * Create table component
     */
    private function createTableComponent(): void
    {
        $this->processTable = new TableComponent();
        
        // Set table columns based on process manager configuration
        $columns = $this->processManager->getTableColumns();
        $this->processTable->setColumns($columns);
    }

    /**
     * Create feedback components
     */
    private function createFeedbackComponents(): void
    {
        // Create status message component
        $this->statusMessage = new StatusMessageComponent();
        
        // Create progress indicator component
        $this->progressIndicator = ProgressIndicatorComponent::createIndeterminate(
            'Processing Process Operation',
            'Please wait while the operation completes...'
        );
        $this->progressIndicator->setCancellable(false);
    }

    /**
     * Build the layout structure
     */
    private function buildLayout(): void
    {
        // Add input controls to controls layout
        $this->controlsLayout->addChild($this->processInput, ['stretchy' => true]);
        $this->controlsLayout->addChild($this->queryButton);
        $this->controlsLayout->addChild($this->refreshButton);
        
        // Create action buttons layout
        $actionsLayout = LayoutContainer::createHorizontal(true);
        $actionsLayout->addChild($this->selectAllButton);
        $actionsLayout->addChild($this->clearSelectionButton);
        $actionsLayout->addChild($this->killSelectedButton);
        
        // Add all sections to main layout
        $this->mainLayout->addChild($this->controlsLayout);
        $this->mainLayout->addChild($actionsLayout);
        $this->mainLayout->addChild($this->statusMessage);
        $this->mainLayout->addChild($this->processTable, ['stretchy' => true]);
    }

    /**
     * Initialize all components
     */
    private function initializeComponents(): void
    {
        // Initialize layout containers
        $this->mainLayout->initialize();
        $this->controlsLayout->initialize();
        
        // Initialize input components
        $this->processInput->initialize();
        
        // Initialize buttons
        $this->queryButton->initialize();
        $this->refreshButton->initialize();
        $this->killSelectedButton->initialize();
        $this->selectAllButton->initialize();
        $this->clearSelectionButton->initialize();
        
        // Initialize table
        $this->processTable->initialize();
        
        // Initialize feedback components
        $this->statusMessage->initialize();
        $this->progressIndicator->initialize();
        
        // Setup event handlers after all components are initialized
        $this->setupEventHandlers();
    }

    /**
     * Setup event handlers for all components
     */
    protected function setupEventHandlers(): void
    {
        // Process input events
        $this->processInput->onEnter(function () {
            $this->handleQueryProcesses();
        });

        // Button click events
        $this->queryButton->onClick(function () {
            $this->handleQueryProcesses();
        });

        $this->refreshButton->onClick(function () {
            $this->handleRefreshProcesses();
        });

        $this->killSelectedButton->onClick(function () {
            $this->handleKillSelected();
        });

        $this->selectAllButton->onClick(function () {
            $this->handleSelectAll();
        });

        $this->clearSelectionButton->onClick(function () {
            $this->handleClearSelection();
        });

        // Table selection events
        $this->processTable->onSelectionChange(function ($selectedRows) {
            $this->handleSelectionChange($selectedRows);
        });
    }

    /**
     * Handle process query operation
     */
    private function handleQueryProcesses(): void
    {
        try {
            $processIdentifier = $this->processInput->getValue();
            
            // Validate input
            if (!$this->processManager->validateInput($processIdentifier)) {
                $this->showError('Invalid process name or PID format');
                return;
            }
            
            // Show progress indicator
            $this->progressIndicator->setTitle('Querying Processes');
            $this->progressIndicator->setMessage('Searching for running processes...');
            $this->progressIndicator->show();
            
            // Disable buttons during query
            $this->setButtonsEnabled(false);
            
            // Query processes
            $processes = $this->processManager->query($processIdentifier);
            
            // Format data for table display
            $tableData = $this->processManager->getFormattedTableData($processes);
            
            // Update table
            $this->processTable->setData($tableData);
            $this->currentProcessData = $tableData;
            
            // Hide progress indicator
            $this->progressIndicator->hide();
            
            // Show success message
            $processCount = count($tableData);
            if ($processCount > 0) {
                $this->showSuccess("Found {$processCount} running process(es)");
            } else {
                $this->showInfo('No running processes found matching the criteria');
            }
            
            // Re-enable buttons
            $this->setButtonsEnabled(true);
            
        } catch (SystemCommandException $e) {
            $this->progressIndicator->hide();
            $this->showError('Failed to query processes: ' . $e->getMessage());
            $this->setButtonsEnabled(true);
        } catch (\Exception $e) {
            $this->progressIndicator->hide();
            $this->showError('Unexpected error: ' . $e->getMessage());
            $this->setButtonsEnabled(true);
        }
    }

    /**
     * Handle process refresh operation
     */
    private function handleRefreshProcesses(): void
    {
        // Use the current process input value for refresh
        $this->handleQueryProcesses();
    }

    /**
     * Handle kill selected processes operation
     */
    private function handleKillSelected(): void
    {
        try {
            $selectedRows = $this->processTable->getSelectedRows();
            
            if (empty($selectedRows)) {
                $this->showWarning('No processes selected for killing');
                return;
            }
            
            // Extract PIDs from selected rows
            $selectedPids = [];
            foreach ($selectedRows as $row) {
                $data = $row->getData();
                if (isset($data['pid'])) {
                    $selectedPids[] = $data['pid'];
                }
            }
            
            if (empty($selectedPids)) {
                $this->showError('No valid PIDs found in selection');
                return;
            }
            
            // Check for system processes
            $systemProcesses = [];
            foreach ($selectedPids as $pid) {
                if ($this->processManager->isSystemProcess($pid)) {
                    $systemProcesses[] = $pid;
                }
            }
            
            if (!empty($systemProcesses)) {
                $this->showError('Cannot kill system processes: ' . implode(', ', $systemProcesses));
                return;
            }
            
            // Confirm kill operation
            if (!$this->confirmKillOperation(count($selectedPids))) {
                return;
            }
            
            // Show progress indicator for batch operation
            $this->progressIndicator->setTitle('Killing Processes');
            $this->progressIndicator->setMessage("Killing " . count($selectedPids) . " process(es)...");
            $this->progressIndicator->show();
            
            // Disable buttons during operation
            $this->setButtonsEnabled(false);
            
            // Kill selected processes with progress updates
            $result = $this->performBatchKillOperation($selectedPids);
            
            // Hide progress indicator
            $this->progressIndicator->hide();
            
            // Show results
            $this->showKillResults($result);
            
            // Refresh the table
            $this->handleRefreshProcesses();
            
            // Re-enable buttons
            $this->setButtonsEnabled(true);
            
        } catch (SystemCommandException $e) {
            $this->progressIndicator->hide();
            $this->showError('Failed to kill processes: ' . $e->getMessage());
            $this->setButtonsEnabled(true);
        } catch (\Exception $e) {
            $this->progressIndicator->hide();
            $this->showError('Unexpected error: ' . $e->getMessage());
            $this->setButtonsEnabled(true);
        }
    }

    /**
     * Handle select all operation
     */
    private function handleSelectAll(): void
    {
        $this->processTable->selectAll();
    }

    /**
     * Handle clear selection operation
     */
    private function handleClearSelection(): void
    {
        $this->processTable->clearSelection();
    }

    /**
     * Set enabled state for all buttons
     */
    private function setButtonsEnabled(bool $enabled): void
    {
        $this->queryButton->setEnabled($enabled);
        $this->refreshButton->setEnabled($enabled);
        $this->killSelectedButton->setEnabled($enabled);
        $this->selectAllButton->setEnabled($enabled);
        $this->clearSelectionButton->setEnabled($enabled);
    }

    /**
     * Show error message to user
     */
    private function showError(string $message): void
    {
        $this->statusMessage->showTemporary($message, 'error', 5000);
    }

    /**
     * Show warning message to user
     */
    private function showWarning(string $message): void
    {
        $this->statusMessage->showTemporary($message, 'warning', 4000);
    }

    /**
     * Show success message to user
     */
    private function showSuccess(string $message): void
    {
        $this->statusMessage->showTemporary($message, 'success', 3000);
    }

    /**
     * Show info message to user
     */
    private function showInfo(string $message): void
    {
        $this->statusMessage->showTemporary($message, 'info', 3000);
    }

    /**
     * Confirm kill operation with user
     */
    private function confirmKillOperation(int $processCount): bool
    {
        $message = "Are you sure you want to kill {$processCount} process(es)?\n\nThis action cannot be undone and may affect system stability.";
        $dialog = DialogComponent::createConfirmation('Confirm Kill Operation', $message);
        
        $result = $dialog->show();
        return $dialog->isConfirmed();
    }

    /**
     * Show kill operation results
     */
    private function showKillResults(array $result): void
    {
        // Show summary message in status bar
        $message = $result['message'] ?? 'Operation completed';
        
        if ($result['success'] ?? false) {
            $this->showSuccess($message);
        } else {
            $this->showError($message);
        }
        
        // Show detailed results dialog if there are individual results
        if (!empty($result['results'])) {
            $resultsDialog = BatchResultsDialogComponent::createKillResults($result);
            $resultsDialog->show();
        }
    }

    /**
     * Perform batch kill operation with progress updates
     */
    private function performBatchKillOperation(array $selectedPids): array
    {
        $totalPids = count($selectedPids);
        $processedCount = 0;
        
        // Create a determinate progress indicator for batch operations
        $batchProgress = ProgressIndicatorComponent::createDeterminate(
            'Killing Processes',
            'Processing batch kill operation...'
        );
        $batchProgress->show();
        
        try {
            // Update progress as we process each PID
            foreach ($selectedPids as $index => $pid) {
                $progress = ($index + 1) / $totalPids;
                $batchProgress->setProgress($progress);
                $batchProgress->setMessage("Processing PID {$pid} (" . ($index + 1) . " of {$totalPids})...");
                
                // Small delay to show progress (in real implementation, this would be the actual operation time)
                if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
                    usleep(100000); // 0.1 second delay for demonstration
                }
                
                $processedCount++;
            }
            
            // Perform the actual kill operation
            $result = $this->processManager->killSelected($selectedPids);
            
            $batchProgress->hide();
            return $result;
            
        } catch (\Exception $e) {
            $batchProgress->hide();
            throw $e;
        }
    }

    /**
     * Get the process input component
     */
    public function getProcessInput(): InputComponent
    {
        return $this->processInput;
    }

    /**
     * Get the process table component
     */
    public function getProcessTable(): TableComponent
    {
        return $this->processTable;
    }

    /**
     * Get current process data
     */
    public function getCurrentProcessData(): array
    {
        return $this->currentProcessData;
    }

    /**
     * Set process manager
     */
    public function setProcessManager(ProcessManager $processManager): void
    {
        $this->processManager = $processManager;
    }

    /**
     * Check if panel is ready for operations
     */
    public function isReady(): bool
    {
        return $this->processManager->isReady() && $this->initialized;
    }

    /**
     * Query processes by user
     */
    public function queryProcessesByUser(string $username): void
    {
        try {
            $this->setButtonsEnabled(false);
            
            $processes = $this->processManager->getProcessesByUser($username);
            $tableData = $this->processManager->getFormattedTableData($processes);
            
            $this->processTable->setData($tableData);
            $this->currentProcessData = $tableData;
            
            $this->setButtonsEnabled(true);
            
        } catch (SystemCommandException $e) {
            $this->showError('Failed to query processes by user: ' . $e->getMessage());
            $this->setButtonsEnabled(true);
        }
    }

    /**
     * Query high CPU processes
     */
    public function queryHighCpuProcesses(float $threshold = 10.0): void
    {
        try {
            $this->setButtonsEnabled(false);
            
            $processes = $this->processManager->getHighCpuProcesses($threshold);
            $tableData = $this->processManager->getFormattedTableData($processes);
            
            $this->processTable->setData($tableData);
            $this->currentProcessData = $tableData;
            
            $this->setButtonsEnabled(true);
            
        } catch (SystemCommandException $e) {
            $this->showError('Failed to query high CPU processes: ' . $e->getMessage());
            $this->setButtonsEnabled(true);
        }
    }

    /**
     * Query high memory processes
     */
    public function queryHighMemoryProcesses(string $threshold = '100MB'): void
    {
        try {
            $this->setButtonsEnabled(false);
            
            $processes = $this->processManager->getHighMemoryProcesses($threshold);
            $tableData = $this->processManager->getFormattedTableData($processes);
            
            $this->processTable->setData($tableData);
            $this->currentProcessData = $tableData;
            
            $this->setButtonsEnabled(true);
            
        } catch (SystemCommandException $e) {
            $this->showError('Failed to query high memory processes: ' . $e->getMessage());
            $this->setButtonsEnabled(true);
        }
    }

    /**
     * Handle table selection changes
     */
    private function handleSelectionChange(array $selectedRows): void
    {
        $selectedCount = count($selectedRows);
        
        // Update button states based on selection
        $this->updateButtonStates($selectedCount);
        
        // Update status message
        if ($selectedCount > 0) {
            $this->showInfo("{$selectedCount} process(es) selected");
        } else {
            $this->statusMessage->clear();
        }
    }

    /**
     * Update button states based on selection count
     */
    private function updateButtonStates(int $selectedCount): void
    {
        $hasSelection = $selectedCount > 0;
        
        // Kill button should only be enabled when items are selected
        $this->killSelectedButton->setEnabled($hasSelection);
        
        // Clear selection button should only be enabled when items are selected
        $this->clearSelectionButton->setEnabled($hasSelection);
        
        // Select all button should be enabled when there are items but not all are selected
        $totalRows = $this->processTable->getRowCount();
        $this->selectAllButton->setEnabled($totalRows > 0 && $selectedCount < $totalRows);
    }
}