<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Ardillo\Components\MainGuiApplication;
use App\Ardillo\Components\PortManagementPanel;
use App\Ardillo\Components\ProcessManagementPanel;
use App\Ardillo\Components\TableComponent;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Services\LoggingService;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Models\TableRow;
use App\Ardillo\Exceptions\GuiException;
use App\Ardillo\Exceptions\SystemCommandException;

/**
 * Integration tests for GUI component interactions
 * Tests the complete integration between GUI components, managers, and services
 */
class GuiComponentInteractionTest extends TestCase
{
    private MainGuiApplication $mainApp;
    private PortManager $portManager;
    private ProcessManager $processManager;
    private SystemCommandService $systemCommandService;
    private DataFormatterService $dataFormatterService;
    private LoggingService $logger;

    protected function setUp(): void
    {
        parent::setUp();

        // Create real services for integration testing
        $this->logger = new LoggingService();
        $this->systemCommandService = new SystemCommandService($this->logger);
        $this->dataFormatterService = new DataFormatterService('linux');

        // Create managers with real services
        $this->portManager = new PortManager($this->systemCommandService, $this->dataFormatterService);
        $this->processManager = new ProcessManager($this->systemCommandService, $this->dataFormatterService);

        // Create main application
        $this->mainApp = new MainGuiApplication(
            $this->portManager,
            $this->processManager,
            $this->logger
        );
    }

    public function test_complete_application_initialization_flow(): void
    {
        // Test the complete initialization flow
        $this->mainApp->initialize();

        // Verify all components are properly initialized
        $this->assertTrue($this->mainApp->isReady());
        
        // Verify component hierarchy
        $portPanel = $this->mainApp->getPortPanel();
        $processPanel = $this->mainApp->getProcessPanel();
        $tabPanel = $this->mainApp->getMainTabPanel();

        $this->assertInstanceOf(PortManagementPanel::class, $portPanel);
        $this->assertInstanceOf(ProcessManagementPanel::class, $processPanel);
        $this->assertTrue($portPanel->isReady());
        $this->assertTrue($processPanel->isReady());

        // Verify tab structure
        $this->assertEquals(2, $tabPanel->getTabCount());
        $this->assertEquals('Port Manager', $tabPanel->getTabTitle(0));
        $this->assertEquals('Process Manager', $tabPanel->getTabTitle(1));
    }

    public function test_port_panel_complete_workflow(): void
    {
        $this->mainApp->initialize();
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();
        $portInput = $portPanel->getPortInput();

        // Test input validation
        $portInput->setValue('80');
        $this->assertEquals('80', $portInput->getValue());

        // Test table data population with mock data
        $testPortData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
            new PortInfo('443', '5678', 'TCP', '0.0.0.0:443', '', 'LISTEN', 'apache'),
            new PortInfo('8080', '9999', 'TCP', '127.0.0.1:8080', '', 'LISTEN', 'node')
        ];

        $formattedData = $this->portManager->getFormattedTableData($testPortData);
        $portTable->setData($formattedData);

        // Verify table population
        $this->assertEquals(3, $portTable->getRowCount());
        $this->assertEquals(0, $portTable->getSelectedRowCount());

        // Test selection operations
        $portTable->setRowSelected($formattedData[0]['id'], true);
        $this->assertEquals(1, $portTable->getSelectedRowCount());
        $this->assertTrue($portTable->isRowSelected($formattedData[0]['id']));

        // Test select all
        $portTable->selectAll();
        $this->assertEquals(3, $portTable->getSelectedRowCount());

        // Test clear selection
        $portTable->clearSelection();
        $this->assertEquals(0, $portTable->getSelectedRowCount());

        // Test selection change events
        $eventTriggered = false;
        $selectedRowsFromEvent = [];
        
        $portTable->onSelectionChange(function ($selectedRows) use (&$eventTriggered, &$selectedRowsFromEvent) {
            $eventTriggered = true;
            $selectedRowsFromEvent = $selectedRows;
        });

        $portTable->setRowSelected($formattedData[1]['id'], true);
        $this->assertTrue($eventTriggered);
        $this->assertCount(1, $selectedRowsFromEvent);
    }

    public function test_process_panel_complete_workflow(): void
    {
        $this->mainApp->initialize();
        $processPanel = $this->mainApp->getProcessPanel();
        $processTable = $processPanel->getProcessTable();
        $processInput = $processPanel->getProcessInput();

        // Test input validation
        $processInput->setValue('chrome');
        $this->assertEquals('chrome', $processInput->getValue());

        // Test table data population with mock data
        $testProcessData = [
            new ProcessInfo('1234', 'chrome', 'user1', '15.2', '256MB', '/usr/bin/chrome', 'running'),
            new ProcessInfo('5678', 'firefox', 'user1', '8.5', '128MB', '/usr/bin/firefox', 'running'),
            new ProcessInfo('9999', 'code', 'user1', '12.1', '512MB', '/usr/bin/code', 'running')
        ];

        $formattedData = $this->processManager->getFormattedTableData($testProcessData);
        $processTable->setData($formattedData);

        // Verify table population
        $this->assertEquals(3, $processTable->getRowCount());
        $this->assertEquals(0, $processTable->getSelectedRowCount());

        // Test selection operations
        $processTable->setRowSelected($formattedData[0]['id'], true);
        $this->assertEquals(1, $processTable->getSelectedRowCount());

        // Test batch selection
        $processTable->setRowSelected($formattedData[1]['id'], true);
        $processTable->setRowSelected($formattedData[2]['id'], true);
        $this->assertEquals(3, $processTable->getSelectedRowCount());

        // Test partial clear
        $processTable->setRowSelected($formattedData[0]['id'], false);
        $this->assertEquals(2, $processTable->getSelectedRowCount());
    }

    public function test_tab_switching_preserves_panel_state(): void
    {
        $this->mainApp->initialize();
        
        // Set up data in port panel
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();
        
        $testPortData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
        ];
        $formattedPortData = $this->portManager->getFormattedTableData($testPortData);
        $portTable->setData($formattedPortData);
        $portTable->setRowSelected($formattedPortData[0]['id'], true);

        // Verify initial state
        $this->assertEquals(0, $this->mainApp->getMainTabPanel()->getActiveTabIndex());
        $this->assertEquals(1, $portTable->getSelectedRowCount());

        // Switch to process panel
        $this->mainApp->switchToProcessManager();
        $this->assertEquals(1, $this->mainApp->getMainTabPanel()->getActiveTabIndex());

        // Set up data in process panel
        $processPanel = $this->mainApp->getProcessPanel();
        $processTable = $processPanel->getProcessTable();
        
        $testProcessData = [
            new ProcessInfo('5678', 'firefox', 'user1', '8.5', '128MB', '/usr/bin/firefox', 'running')
        ];
        $formattedProcessData = $this->processManager->getFormattedTableData($testProcessData);
        $processTable->setData($formattedProcessData);
        $processTable->setRowSelected($formattedProcessData[0]['id'], true);

        // Switch back to port panel
        $this->mainApp->switchToPortManager();
        $this->assertEquals(0, $this->mainApp->getMainTabPanel()->getActiveTabIndex());

        // Verify port panel state is preserved
        $this->assertEquals(1, $portTable->getSelectedRowCount());
        $this->assertTrue($portTable->isRowSelected($formattedPortData[0]['id']));

        // Switch back to process panel and verify state
        $this->mainApp->switchToProcessManager();
        $this->assertEquals(1, $processTable->getSelectedRowCount());
        $this->assertTrue($processTable->isRowSelected($formattedProcessData[0]['id']));
    }

    public function test_cross_panel_communication(): void
    {
        $this->mainApp->initialize();

        // Test cross-panel update notifications
        $affectedIds = ['1234', '5678'];
        
        // These should complete without errors
        $this->mainApp->handleCrossPanelUpdate('port', 'kill_processes', $affectedIds);
        $this->mainApp->handleCrossPanelUpdate('process', 'kill_processes', $affectedIds);

        // Verify application remains stable
        $this->assertTrue($this->mainApp->isReady());
        
        // Test refresh all data functionality
        $this->mainApp->refreshAllData();
        $this->assertTrue($this->mainApp->isReady());
    }

    public function test_application_status_integration(): void
    {
        $this->mainApp->initialize();
        
        $status = $this->mainApp->getStatus();
        
        // Verify comprehensive status information
        $expectedKeys = [
            'initialized', 'ready', 'active_tab', 'port_manager_ready', 
            'process_manager_ready', 'tab_count'
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $status);
        }

        // Verify status values reflect actual state
        $this->assertTrue($status['initialized']);
        $this->assertTrue($status['ready']);
        $this->assertEquals(0, $status['active_tab']);
        $this->assertTrue($status['port_manager_ready']);
        $this->assertTrue($status['process_manager_ready']);
        $this->assertEquals(2, $status['tab_count']);

        // Test status after tab switch
        $this->mainApp->switchToProcessManager();
        $newStatus = $this->mainApp->getStatus();
        $this->assertEquals(1, $newStatus['active_tab']);
    }

    public function test_error_handling_integration(): void
    {
        // Test initialization with invalid dependencies
        $invalidLogger = null;
        
        $this->expectException(\TypeError::class);
        new MainGuiApplication(
            $this->portManager,
            $this->processManager,
            $invalidLogger
        );
    }

    public function test_component_event_propagation(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();
        
        // Track events at different levels
        $tableEvents = [];
        $panelEvents = [];
        
        $portTable->onSelectionChange(function ($selectedRows) use (&$tableEvents) {
            $tableEvents[] = ['type' => 'selection_change', 'count' => count($selectedRows)];
        });

        // Add test data
        $testData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
        ];
        $formattedData = $this->portManager->getFormattedTableData($testData);
        $portTable->setData($formattedData);

        // Trigger events
        $portTable->setRowSelected($formattedData[0]['id'], true);
        $portTable->selectAll();
        $portTable->clearSelection();

        // Verify events were propagated
        $this->assertCount(3, $tableEvents);
        $this->assertEquals(1, $tableEvents[0]['count']);
        $this->assertEquals(1, $tableEvents[1]['count']);
        $this->assertEquals(0, $tableEvents[2]['count']);
    }

    public function test_memory_management_during_operations(): void
    {
        $this->mainApp->initialize();
        
        $initialMemory = memory_get_usage();
        
        // Perform multiple operations that could cause memory leaks
        for ($i = 0; $i < 10; $i++) {
            $portPanel = $this->mainApp->getPortPanel();
            $portTable = $portPanel->getPortTable();
            
            // Add and remove data multiple times
            $testData = [
                new PortInfo("8{$i}", "123{$i}", 'TCP', "0.0.0.0:8{$i}", '', 'LISTEN', 'test')
            ];
            $formattedData = $this->portManager->getFormattedTableData($testData);
            $portTable->setData($formattedData);
            $portTable->selectAll();
            $portTable->clearSelection();
            $portTable->setData([]);
        }
        
        // Force garbage collection
        gc_collect_cycles();
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 1MB for this test)
        $this->assertLessThan(1024 * 1024, $memoryIncrease, 
            "Memory usage increased by {$memoryIncrease} bytes, which may indicate a memory leak");
    }

    public function test_concurrent_panel_operations(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $processPanel = $this->mainApp->getProcessPanel();
        
        // Set up data in both panels simultaneously
        $portData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
        ];
        $processData = [
            new ProcessInfo('1234', 'nginx', 'www-data', '2.1', '64MB', '/usr/sbin/nginx', 'running')
        ];
        
        $formattedPortData = $this->portManager->getFormattedTableData($portData);
        $formattedProcessData = $this->processManager->getFormattedTableData($processData);
        
        // Perform operations on both panels
        $portPanel->getPortTable()->setData($formattedPortData);
        $processPanel->getProcessTable()->setData($formattedProcessData);
        
        $portPanel->getPortTable()->selectAll();
        $processPanel->getProcessTable()->selectAll();
        
        // Verify both panels maintain their state
        $this->assertEquals(1, $portPanel->getPortTable()->getSelectedRowCount());
        $this->assertEquals(1, $processPanel->getProcessTable()->getSelectedRowCount());
        
        // Switch tabs and verify state persistence
        $this->mainApp->switchToProcessManager();
        $this->assertEquals(1, $processPanel->getProcessTable()->getSelectedRowCount());
        
        $this->mainApp->switchToPortManager();
        $this->assertEquals(1, $portPanel->getPortTable()->getSelectedRowCount());
    }

    public function test_application_shutdown_cleanup(): void
    {
        $this->mainApp->initialize();
        
        // Set up some state
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();
        
        $testData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
        ];
        $formattedData = $this->portManager->getFormattedTableData($testData);
        $portTable->setData($formattedData);
        $portTable->selectAll();
        
        // Verify state before shutdown
        $this->assertEquals(1, $portTable->getSelectedRowCount());
        $this->assertTrue($this->mainApp->isReady());
        
        // Perform shutdown
        $this->mainApp->handleShutdown();
        
        // Verify cleanup occurred (selections should be cleared)
        $this->assertEquals(0, $portTable->getSelectedRowCount());
    }

    protected function tearDown(): void
    {
        // Clean up any resources
        if (isset($this->mainApp)) {
            try {
                $this->mainApp->handleShutdown();
            } catch (\Exception $e) {
                // Ignore shutdown errors in tests
            }
        }
        
        parent::tearDown();
    }
}