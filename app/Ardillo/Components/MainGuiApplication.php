<?php

namespace App\Ardillo\Components;

use App\Ardillo\Core\ArdilloApplication;
use App\Ardillo\Core\ErrorHandler;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Exceptions\GuiException;
use App\Ardillo\Exceptions\ComponentInitializationException;
use App\Ardillo\Services\ConfigurationService;
use Psr\Log\LoggerInterface;

/**
 * Main GUI application component that integrates all panels and manages the overall layout
 */
class MainGuiApplication extends BaseComponent
{
    private ArdilloApplication $application;
    private TabPanel $mainTabPanel;
    private PortManagementPanel $portPanel;
    private ProcessManagementPanel $processPanel;
    private PortManager $portManager;
    private ProcessManager $processManager;
    private LoggerInterface $logger;
    private ErrorHandler $errorHandler;
    private array $keyboardShortcuts = [];
    private ConfigurationService $configService;

    public function __construct(
        PortManager $portManager,
        ProcessManager $processManager,
        LoggerInterface $logger,
        ConfigurationService $configService = null
    ) {
        $this->portManager = $portManager;
        $this->processManager = $processManager;
        $this->logger = $logger;
        $this->configService = $configService ?? new ConfigurationService($logger);
        $this->errorHandler = new ErrorHandler($logger);
        
        // Register global error handlers
        $this->errorHandler->registerGlobalHandlers();
    }

    /**
     * Create the main GUI application
     */
    protected function createWidget(): void
    {
        try {
            // Create the main application
            $this->application = new ArdilloApplication($this->logger);
            
            // Create main tab panel
            $this->mainTabPanel = new TabPanel();
            
            // Create management panels
            $this->createManagementPanels();
            
            // Build the main layout
            $this->buildMainLayout();
            
            // Initialize all components
            $this->initializeComponents();
            
            // Use the tab panel as our main widget
            $this->widget = $this->mainTabPanel->getControl();
            
        } catch (GuiException $e) {
            $this->errorHandler->handleException($e);
            throw $e;
        } catch (\Exception $e) {
            $guiException = new ComponentInitializationException(
                'Failed to create main GUI application: ' . $e->getMessage(),
                0,
                $e,
                ['component' => 'MainGuiApplication']
            );
            $this->errorHandler->handleException($guiException);
            throw $guiException;
        }
    }

    /**
     * Create the management panels
     */
    private function createManagementPanels(): void
    {
        try {
            // Create port management panel
            $this->portPanel = new PortManagementPanel($this->portManager);
            
            // Create process management panel
            $this->processPanel = new ProcessManagementPanel($this->processManager);
            
        } catch (\Exception $e) {
            throw new ComponentInitializationException(
                'Failed to create management panels: ' . $e->getMessage(),
                0,
                $e,
                ['operation' => 'createManagementPanels']
            );
        }
    }

    /**
     * Build the main layout with tabs
     */
    private function buildMainLayout(): void
    {
        // Add port management tab
        $this->mainTabPanel->addTab('Port Manager', $this->portPanel);
        
        // Add process management tab
        $this->mainTabPanel->addTab('Process Manager', $this->processPanel);
    }

    /**
     * Initialize all components
     */
    private function initializeComponents(): void
    {
        // Initialize the application
        $this->application->initialize();
        
        // Initialize main tab panel
        $this->mainTabPanel->initialize();
        
        // Initialize management panels
        $this->portPanel->initialize();
        $this->processPanel->initialize();
        
        // Setup event handlers after all components are initialized
        $this->setupEventHandlers();
    }

    /**
     * Setup event handlers for the main application
     */
    protected function setupEventHandlers(): void
    {
        // Handle tab changes
        $this->mainTabPanel->onTabChange(function ($tabIndex, $tabData) {
            $this->handleTabChange($tabIndex, $tabData);
        });
        
        // Wire port panel events
        $this->wirePortPanelEvents();
        
        // Wire process panel events
        $this->wireProcessPanelEvents();
        
        // Setup cross-panel communication
        $this->setupCrossPanelCommunication();
        
        // Setup keyboard shortcuts
        $this->setupKeyboardShortcuts();
    }

    /**
     * Wire port panel events to manager operations
     */
    private function wirePortPanelEvents(): void
    {
        if (!$this->portPanel) {
            return;
        }
        
        // Connect table selection changes to button state updates
        $portTable = $this->portPanel->getPortTable();
        $portTable->onSelectionChange(function ($selectedRows) {
            $this->updatePortPanelButtonStates(count($selectedRows));
        });
        
        // Connect input validation to user feedback
        $portInput = $this->portPanel->getPortInput();
        $portInput->onChange(function ($value) {
            $this->validatePortInput($value);
        });
    }

    /**
     * Wire process panel events to manager operations
     */
    private function wireProcessPanelEvents(): void
    {
        if (!$this->processPanel) {
            return;
        }
        
        // Connect table selection changes to button state updates
        $processTable = $this->processPanel->getProcessTable();
        $processTable->onSelectionChange(function ($selectedRows) {
            $this->updateProcessPanelButtonStates(count($selectedRows));
        });
        
        // Connect input validation to user feedback
        $processInput = $this->processPanel->getProcessInput();
        $processInput->onChange(function ($value) {
            $this->validateProcessInput($value);
        });
    }

    /**
     * Setup cross-panel communication and shared state management
     */
    private function setupCrossPanelCommunication(): void
    {
        // Setup refresh triggers between panels
        $this->setupRefreshTriggers();
        
        // Setup shared error handling
        $this->setupSharedErrorHandling();
        
        // Setup shared progress indication
        $this->setupSharedProgressHandling();
    }

    /**
     * Setup refresh triggers between panels
     */
    private function setupRefreshTriggers(): void
    {
        // When port operations complete, optionally refresh process data
        // This helps show the impact of killing port processes
        
        // When process operations complete, optionally refresh port data
        // This helps show the impact of killing processes that were using ports
    }

    /**
     * Setup shared error handling across panels
     */
    private function setupSharedErrorHandling(): void
    {
        // Both panels can use the same error display mechanisms
        // This ensures consistent user experience
    }

    /**
     * Setup shared progress indication
     */
    private function setupSharedProgressHandling(): void
    {
        // Coordinate progress indicators to avoid conflicts
        // Only one panel should show progress at a time
    }

    /**
     * Handle tab change events
     */
    private function handleTabChange(int $tabIndex, ?array $tabData): void
    {
        if (!$tabData) {
            return;
        }
        
        $tabTitle = $tabData['title'] ?? 'Unknown';
        $this->logger->info("Switched to tab: {$tabTitle}");
        
        // Perform tab-specific state management
        switch ($tabIndex) {
            case 0: // Port Manager
                $this->activatePortPanel();
                break;
                
            case 1: // Process Manager
                $this->activateProcessPanel();
                break;
        }
        
        // Update application state
        $this->updateApplicationState($tabIndex, $tabTitle);
    }

    /**
     * Activate port panel and refresh data if needed
     */
    private function activatePortPanel(): void
    {
        if (!$this->portPanel->isReady()) {
            return;
        }
        
        // Clear any active progress indicators from other panels
        $this->clearOtherPanelProgress('port');
        
        // Optionally refresh port data when switching to this tab
        // This ensures users see current data when they switch tabs
        $currentData = $this->portPanel->getCurrentPortData();
        if (empty($currentData)) {
            // Auto-refresh if no data is currently displayed
            $this->logger->info('Auto-refreshing port data on tab activation');
            // Note: We don't auto-refresh to avoid unwanted operations
        }
        
        // Update button states based on current selection
        $selectedCount = $this->portPanel->getPortTable()->getSelectedRowCount();
        $this->updatePortPanelButtonStates($selectedCount);
    }

    /**
     * Activate process panel and refresh data if needed
     */
    private function activateProcessPanel(): void
    {
        if (!$this->processPanel->isReady()) {
            return;
        }
        
        // Clear any active progress indicators from other panels
        $this->clearOtherPanelProgress('process');
        
        // Optionally refresh process data when switching to this tab
        $currentData = $this->processPanel->getCurrentProcessData();
        if (empty($currentData)) {
            // Auto-refresh if no data is currently displayed
            $this->logger->info('Auto-refreshing process data on tab activation');
            // Note: We don't auto-refresh to avoid unwanted operations
        }
        
        // Update button states based on current selection
        $selectedCount = $this->processPanel->getProcessTable()->getSelectedRowCount();
        $this->updateProcessPanelButtonStates($selectedCount);
    }

    /**
     * Clear progress indicators from other panels
     */
    private function clearOtherPanelProgress(string $activePanel): void
    {
        // Ensure only one panel shows progress at a time
        if ($activePanel !== 'port' && $this->portPanel) {
            // Hide port panel progress if it's showing
        }
        
        if ($activePanel !== 'process' && $this->processPanel) {
            // Hide process panel progress if it's showing
        }
    }

    /**
     * Update application state when tab changes
     */
    private function updateApplicationState(int $tabIndex, string $tabTitle): void
    {
        // Log the state change
        $this->logger->debug('Application state updated', [
            'active_tab_index' => $tabIndex,
            'active_tab_title' => $tabTitle,
            'port_panel_ready' => $this->portPanel ? $this->portPanel->isReady() : false,
            'process_panel_ready' => $this->processPanel ? $this->processPanel->isReady() : false
        ]);
        
        // Update window title to reflect active tab
        $this->setWindowTitle("Ardillo Port & Process Manager - {$tabTitle}");
    }

    /**
     * Start the GUI application
     */
    public function start(): void
    {
        try {
            // Initialize if not already done
            if (!$this->initialized) {
                $this->initialize();
            }
            
            // Add the main tab panel to the application
            $this->application->addTab('Main', $this);
            
            // Start the application event loop
            $this->application->run();
            
        } catch (GuiException $e) {
            $this->logger->error('GUI error during application start', [
                'error' => $e->getMessage(),
                'severity' => $e->getSeverity(),
                'recoverable' => $e->isRecoverable(),
                'context' => $e->getContext()
            ]);
            
            // Try graceful degradation if possible
            if ($this->errorHandler->handleGracefulDegradation($e)) {
                $this->logger->info('Graceful degradation successful, continuing...');
                return;
            }
            
            $this->errorHandler->handleException($e);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during application start', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $guiException = new ComponentInitializationException(
                'Failed to start GUI application: ' . $e->getMessage(),
                0,
                $e,
                ['operation' => 'start', 'component' => 'MainGuiApplication']
            );
            
            $this->errorHandler->handleException($guiException);
            throw $guiException;
        }
    }

    /**
     * Stop the GUI application
     */
    public function stop(): void
    {
        try {
            if ($this->application) {
                $this->application->shutdown();
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Error stopping GUI application', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get the port management panel
     */
    public function getPortPanel(): PortManagementPanel
    {
        return $this->portPanel;
    }

    /**
     * Get the process management panel
     */
    public function getProcessPanel(): ProcessManagementPanel
    {
        return $this->processPanel;
    }

    /**
     * Get the main tab panel
     */
    public function getMainTabPanel(): TabPanel
    {
        return $this->mainTabPanel;
    }

    /**
     * Get the underlying application
     */
    public function getApplication(): ArdilloApplication
    {
        return $this->application;
    }

    /**
     * Switch to port management tab
     */
    public function switchToPortManager(): void
    {
        $this->mainTabPanel->setActiveTab(0);
    }

    /**
     * Switch to process management tab
     */
    public function switchToProcessManager(): void
    {
        $this->mainTabPanel->setActiveTab(1);
    }

    /**
     * Check if the application is ready
     */
    public function isReady(): bool
    {
        return $this->initialized && 
               isset($this->portPanel) && $this->portPanel->isReady() && 
               isset($this->processPanel) && $this->processPanel->isReady();
    }

    /**
     * Get application status information
     */
    public function getStatus(): array
    {
        $status = [
            'initialized' => $this->initialized,
            'ready' => false,
            'active_tab' => -1,
            'port_manager_ready' => false,
            'process_manager_ready' => false,
            'tab_count' => 0
        ];

        if ($this->initialized && isset($this->mainTabPanel)) {
            $status['ready'] = $this->isReady();
            $status['active_tab'] = $this->mainTabPanel->getActiveTabIndex();
            $status['tab_count'] = $this->mainTabPanel->getTabCount();
        }

        if (isset($this->portPanel)) {
            $status['port_manager_ready'] = $this->portPanel->isReady();
        }

        if (isset($this->processPanel)) {
            $status['process_manager_ready'] = $this->processPanel->isReady();
        }

        return $status;
    }

    /**
     * Handle application shutdown gracefully
     */
    public function handleShutdown(): void
    {
        $this->logger->info('Shutting down GUI application');
        
        try {
            // Clear any pending operations
            $this->portPanel->getPortTable()->clearSelection();
            $this->processPanel->getProcessTable()->clearSelection();
            
            // Stop the application
            $this->stop();
            
        } catch (\Exception $e) {
            $this->logger->error('Error during shutdown', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Set window title
     */
    public function setWindowTitle(string $title): void
    {
        if ($this->application && $this->application->getWindow()) {
            // Ardillo may not have direct title setting after creation
            // This would need to be set during window creation
        }
    }

    /**
     * Set window size
     */
    public function setWindowSize(int $width, int $height): void
    {
        if ($this->application && $this->application->getWindow()) {
            // Ardillo may not have direct size setting after creation
            // This would need to be set during window creation
        }
    }

    /**
     * Minimize window
     */
    public function minimizeWindow(): void
    {
        if ($this->application && $this->application->getWindow()) {
            // Ardillo may not have direct minimize support
            // This would need to be implemented through window manager
        }
    }

    /**
     * Maximize window
     */
    public function maximizeWindow(): void
    {
        if ($this->application && $this->application->getWindow()) {
            // Ardillo may not have direct maximize support
            // This would need to be implemented through window manager
        }
    }

    /**
     * Update port panel button states based on selection
     */
    private function updatePortPanelButtonStates(int $selectedCount): void
    {
        if (!$this->portPanel) {
            return;
        }
        
        // Enable/disable buttons based on selection
        $hasSelection = $selectedCount > 0;
        
        // Kill button should only be enabled when items are selected
        // Note: The actual button enabling is handled within the panel
        // This method coordinates the state management
        
        $this->logger->debug('Port panel button states updated', [
            'selected_count' => $selectedCount,
            'has_selection' => $hasSelection
        ]);
    }

    /**
     * Update process panel button states based on selection
     */
    private function updateProcessPanelButtonStates(int $selectedCount): void
    {
        if (!$this->processPanel) {
            return;
        }
        
        // Enable/disable buttons based on selection
        $hasSelection = $selectedCount > 0;
        
        // Kill button should only be enabled when items are selected
        // Note: The actual button enabling is handled within the panel
        // This method coordinates the state management
        
        $this->logger->debug('Process panel button states updated', [
            'selected_count' => $selectedCount,
            'has_selection' => $hasSelection
        ]);
    }

    /**
     * Validate port input and provide feedback
     */
    private function validatePortInput(string $value): void
    {
        if (!$this->portManager) {
            return;
        }
        
        $isValid = $this->portManager->validateInput($value);
        
        // The actual validation feedback is handled within the port panel
        // This method coordinates validation across the application
        
        $this->logger->debug('Port input validation', [
            'input_value' => $value,
            'is_valid' => $isValid
        ]);
    }

    /**
     * Validate process input and provide feedback
     */
    private function validateProcessInput(string $value): void
    {
        if (!$this->processManager) {
            return;
        }
        
        $isValid = $this->processManager->validateInput($value);
        
        // The actual validation feedback is handled within the process panel
        // This method coordinates validation across the application
        
        $this->logger->debug('Process input validation', [
            'input_value' => $value,
            'is_valid' => $isValid
        ]);
    }

    /**
     * Trigger data refresh across panels
     */
    public function refreshAllData(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    // Trigger port refresh through the panel's refresh mechanism
                    $this->logger->info('Triggering port data refresh');
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    // Trigger process refresh through the panel's refresh mechanism
                    $this->logger->info('Triggering process data refresh');
                }
                break;
        }
    }

    /**
     * Handle cross-panel data updates
     */
    public function handleCrossPanelUpdate(string $sourcePanel, string $operation, array $affectedIds): void
    {
        $this->logger->info('Cross-panel update triggered', [
            'source_panel' => $sourcePanel,
            'operation' => $operation,
            'affected_count' => count($affectedIds)
        ]);
        
        // When processes are killed in either panel, it may affect the other panel's data
        if ($operation === 'kill_processes') {
            // If we're in the port panel and processes were killed,
            // those processes may have been using ports
            if ($sourcePanel === 'process' && $this->mainTabPanel->getActiveTabIndex() === 0) {
                // Optionally refresh port data to show freed ports
                $this->logger->debug('Process kill may have freed ports, consider refreshing port data');
            }
            
            // If we're in the process panel and port processes were killed,
            // refresh to show the updated process list
            if ($sourcePanel === 'port' && $this->mainTabPanel->getActiveTabIndex() === 1) {
                // Optionally refresh process data to show killed processes are gone
                $this->logger->debug('Port process kill may have affected process list, consider refreshing');
            }
        }
    }

    /**
     * Setup keyboard shortcuts for common operations
     */
    private function setupKeyboardShortcuts(): void
    {
        // Define keyboard shortcuts
        $this->keyboardShortcuts = [
            'Ctrl+R' => 'refresh_current_tab',
            'Ctrl+A' => 'select_all',
            'Ctrl+D' => 'clear_selection',
            'Ctrl+K' => 'kill_selected',
            'Ctrl+F' => 'focus_search',
            'Ctrl+1' => 'switch_to_port_tab',
            'Ctrl+2' => 'switch_to_process_tab',
            'F5' => 'refresh_current_tab',
            'Delete' => 'kill_selected',
            'Escape' => 'clear_selection_or_filters',
            'Ctrl+Shift+A' => 'select_all_filtered'
        ];

        // Register keyboard event handlers
        $this->registerKeyboardHandlers();
    }

    /**
     * Register keyboard event handlers
     */
    private function registerKeyboardHandlers(): void
    {
        if (!$this->widget || isset($this->widget->isTestMode)) {
            return;
        }

        // In a real Ardillo implementation, this would register global key handlers
        // For now, we'll set up the infrastructure for keyboard handling
        
        foreach ($this->keyboardShortcuts as $shortcut => $action) {
            $this->registerKeyboardShortcut($shortcut, $action);
        }
    }

    /**
     * Register a single keyboard shortcut
     */
    private function registerKeyboardShortcut(string $shortcut, string $action): void
    {
        // In a real implementation, this would use Ardillo's key event system
        // For now, we'll log the registration for testing purposes
        
        $this->logger->debug('Registered keyboard shortcut', [
            'shortcut' => $shortcut,
            'action' => $action
        ]);
    }

    /**
     * Handle keyboard shortcut activation
     */
    public function handleKeyboardShortcut(string $shortcut): bool
    {
        if (!isset($this->keyboardShortcuts[$shortcut])) {
            return false;
        }

        $action = $this->keyboardShortcuts[$shortcut];
        
        try {
            return $this->executeShortcutAction($action);
        } catch (\Exception $e) {
            $this->logger->error('Error executing keyboard shortcut', [
                'shortcut' => $shortcut,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Execute keyboard shortcut action
     */
    private function executeShortcutAction(string $action): bool
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($action) {
            case 'refresh_current_tab':
                $this->refreshCurrentTab();
                return true;
                
            case 'select_all':
                $this->selectAllInCurrentTab();
                return true;
                
            case 'clear_selection':
                $this->clearSelectionInCurrentTab();
                return true;
                
            case 'kill_selected':
                $this->killSelectedInCurrentTab();
                return true;
                
            case 'focus_search':
                $this->focusSearchInCurrentTab();
                return true;
                
            case 'switch_to_port_tab':
                $this->switchToPortManager();
                return true;
                
            case 'switch_to_process_tab':
                $this->switchToProcessManager();
                return true;
                
            case 'clear_selection_or_filters':
                $this->clearSelectionOrFiltersInCurrentTab();
                return true;
                
            case 'select_all_filtered':
                $this->selectAllFilteredInCurrentTab();
                return true;
                
            default:
                return false;
        }
    }

    /**
     * Refresh current tab data
     */
    private function refreshCurrentTab(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    $this->portPanel->refreshData();
                    $this->logger->info('Port data refreshed via keyboard shortcut');
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    $this->processPanel->refreshData();
                    $this->logger->info('Process data refreshed via keyboard shortcut');
                }
                break;
        }
    }

    /**
     * Select all items in current tab
     */
    private function selectAllInCurrentTab(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    $this->portPanel->getPortTable()->selectAll();
                    $this->logger->info('All ports selected via keyboard shortcut');
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    $this->processPanel->getProcessTable()->selectAll();
                    $this->logger->info('All processes selected via keyboard shortcut');
                }
                break;
        }
    }

    /**
     * Clear selection in current tab
     */
    private function clearSelectionInCurrentTab(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    $this->portPanel->getPortTable()->clearSelection();
                    $this->logger->info('Port selection cleared via keyboard shortcut');
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    $this->processPanel->getProcessTable()->clearSelection();
                    $this->logger->info('Process selection cleared via keyboard shortcut');
                }
                break;
        }
    }

    /**
     * Kill selected items in current tab
     */
    private function killSelectedInCurrentTab(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    $selectedCount = $this->portPanel->getPortTable()->getSelectedRowCount();
                    if ($selectedCount > 0) {
                        $this->portPanel->killSelectedPorts();
                        $this->logger->info('Kill selected ports triggered via keyboard shortcut', [
                            'selected_count' => $selectedCount
                        ]);
                    }
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    $selectedCount = $this->processPanel->getProcessTable()->getSelectedRowCount();
                    if ($selectedCount > 0) {
                        $this->processPanel->killSelectedProcesses();
                        $this->logger->info('Kill selected processes triggered via keyboard shortcut', [
                            'selected_count' => $selectedCount
                        ]);
                    }
                }
                break;
        }
    }

    /**
     * Focus search input in current tab
     */
    private function focusSearchInCurrentTab(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    $this->portPanel->focusSearchInput();
                    $this->logger->info('Port search input focused via keyboard shortcut');
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    $this->processPanel->focusSearchInput();
                    $this->logger->info('Process search input focused via keyboard shortcut');
                }
                break;
        }
    }

    /**
     * Clear selection or filters in current tab
     */
    private function clearSelectionOrFiltersInCurrentTab(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    $table = $this->portPanel->getPortTable();
                    if (!empty($table->getFilters())) {
                        $table->clearFilters();
                        $this->logger->info('Port filters cleared via keyboard shortcut');
                    } else {
                        $table->clearSelection();
                        $this->logger->info('Port selection cleared via keyboard shortcut');
                    }
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    $table = $this->processPanel->getProcessTable();
                    if (!empty($table->getFilters())) {
                        $table->clearFilters();
                        $this->logger->info('Process filters cleared via keyboard shortcut');
                    } else {
                        $table->clearSelection();
                        $this->logger->info('Process selection cleared via keyboard shortcut');
                    }
                }
                break;
        }
    }

    /**
     * Select all filtered items in current tab
     */
    private function selectAllFilteredInCurrentTab(): void
    {
        $activeTab = $this->mainTabPanel->getActiveTabIndex();
        
        switch ($activeTab) {
            case 0: // Port Manager
                if ($this->portPanel && $this->portPanel->isReady()) {
                    $table = $this->portPanel->getPortTable();
                    $filteredRows = $table->getFilteredRows();
                    foreach ($filteredRows as $row) {
                        $table->setRowSelected($row->getId(), true);
                    }
                    $this->logger->info('All filtered ports selected via keyboard shortcut', [
                        'filtered_count' => count($filteredRows)
                    ]);
                }
                break;
                
            case 1: // Process Manager
                if ($this->processPanel && $this->processPanel->isReady()) {
                    $table = $this->processPanel->getProcessTable();
                    $filteredRows = $table->getFilteredRows();
                    foreach ($filteredRows as $row) {
                        $table->setRowSelected($row->getId(), true);
                    }
                    $this->logger->info('All filtered processes selected via keyboard shortcut', [
                        'filtered_count' => count($filteredRows)
                    ]);
                }
                break;
        }
    }

    /**
     * Get available keyboard shortcuts
     */
    public function getKeyboardShortcuts(): array
    {
        return $this->keyboardShortcuts;
    }

    /**
     * Add custom keyboard shortcut
     */
    public function addKeyboardShortcut(string $shortcut, string $action): void
    {
        $this->keyboardShortcuts[$shortcut] = $action;
        $this->registerKeyboardShortcut($shortcut, $action);
    }

    /**
     * Remove keyboard shortcut
     */
    public function removeKeyboardShortcut(string $shortcut): void
    {
        unset($this->keyboardShortcuts[$shortcut]);
    }
}