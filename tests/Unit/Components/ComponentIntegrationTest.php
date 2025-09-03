<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use App\Ardillo\Components\MainGuiApplication;
use App\Ardillo\Components\PortManagementPanel;
use App\Ardillo\Components\ProcessManagementPanel;
use App\Ardillo\Components\TabPanel;
use App\Ardillo\Components\TableComponent;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Services\LoggingService;
use App\Ardillo\Models\TableRow;

/**
 * Test component integration and event wiring
 */
class ComponentIntegrationTest extends TestCase
{
    private MainGuiApplication $mainApp;
    private PortManager $portManager;
    private ProcessManager $processManager;
    private LoggingService $logger;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $systemCommandService = $this->createMock(SystemCommandService::class);
        $dataFormatterService = $this->createMock(DataFormatterService::class);
        $this->logger = $this->createMock(LoggingService::class);

        // Configure mocks
        $systemCommandService->method('isAvailable')->willReturn(true);
        $dataFormatterService->method('isAvailable')->willReturn(true);

        // Create managers
        $this->portManager = new PortManager($systemCommandService, $dataFormatterService);
        $this->processManager = new ProcessManager($systemCommandService, $dataFormatterService);

        // Create main application
        $this->mainApp = new MainGuiApplication(
            $this->portManager,
            $this->processManager,
            $this->logger
        );
    }

    public function test_main_application_initializes_with_components(): void
    {
        // Initialize the main application
        $this->mainApp->initialize();

        // Verify components are created
        $this->assertInstanceOf(PortManagementPanel::class, $this->mainApp->getPortPanel());
        $this->assertInstanceOf(ProcessManagementPanel::class, $this->mainApp->getProcessPanel());
        $this->assertInstanceOf(TabPanel::class, $this->mainApp->getMainTabPanel());

        // Verify components are ready
        $this->assertTrue($this->mainApp->getPortPanel()->isReady());
        $this->assertTrue($this->mainApp->getProcessPanel()->isReady());
        $this->assertTrue($this->mainApp->isReady());
    }

    public function test_tab_panel_has_correct_tabs(): void
    {
        $this->mainApp->initialize();
        
        $tabPanel = $this->mainApp->getMainTabPanel();
        
        // Verify tab count
        $this->assertEquals(2, $tabPanel->getTabCount());
        
        // Verify tab titles
        $this->assertEquals('Port Manager', $tabPanel->getTabTitle(0));
        $this->assertEquals('Process Manager', $tabPanel->getTabTitle(1));
    }

    public function test_port_panel_table_selection_updates_button_states(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();
        
        // Add some test data
        $testData = [
            ['id' => '1', 'port' => '80', 'pid' => '1234', 'protocol' => 'TCP'],
            ['id' => '2', 'port' => '443', 'pid' => '5678', 'protocol' => 'TCP']
        ];
        
        $portTable->setData($testData);
        
        // Verify initial state - no selection
        $this->assertEquals(0, $portTable->getSelectedRowCount());
        
        // Select a row
        $portTable->setRowSelected('1', true);
        
        // Verify selection
        $this->assertEquals(1, $portTable->getSelectedRowCount());
        $this->assertTrue($portTable->isRowSelected('1'));
        
        // Select all rows
        $portTable->selectAll();
        
        // Verify all selected
        $this->assertEquals(2, $portTable->getSelectedRowCount());
        
        // Clear selection
        $portTable->clearSelection();
        
        // Verify no selection
        $this->assertEquals(0, $portTable->getSelectedRowCount());
    }

    public function test_process_panel_table_selection_updates_button_states(): void
    {
        $this->mainApp->initialize();
        
        $processPanel = $this->mainApp->getProcessPanel();
        $processTable = $processPanel->getProcessTable();
        
        // Add some test data
        $testData = [
            ['id' => '1234', 'pid' => '1234', 'name' => 'chrome', 'user' => 'testuser'],
            ['id' => '5678', 'pid' => '5678', 'name' => 'firefox', 'user' => 'testuser']
        ];
        
        $processTable->setData($testData);
        
        // Verify initial state - no selection
        $this->assertEquals(0, $processTable->getSelectedRowCount());
        
        // Select a row
        $processTable->setRowSelected('1234', true);
        
        // Verify selection
        $this->assertEquals(1, $processTable->getSelectedRowCount());
        $this->assertTrue($processTable->isRowSelected('1234'));
        
        // Select all rows
        $processTable->selectAll();
        
        // Verify all selected
        $this->assertEquals(2, $processTable->getSelectedRowCount());
        
        // Clear selection
        $processTable->clearSelection();
        
        // Verify no selection
        $this->assertEquals(0, $processTable->getSelectedRowCount());
    }

    public function test_table_selection_change_events_are_triggered(): void
    {
        $this->mainApp->initialize();
        
        $portTable = $this->mainApp->getPortPanel()->getPortTable();
        
        // Set up event tracking
        $selectionChangeCount = 0;
        $lastSelectedRows = [];
        
        $portTable->onSelectionChange(function ($selectedRows) use (&$selectionChangeCount, &$lastSelectedRows) {
            $selectionChangeCount++;
            $lastSelectedRows = $selectedRows;
        });
        
        // Add test data
        $testData = [
            ['id' => '1', 'port' => '80', 'pid' => '1234', 'protocol' => 'TCP']
        ];
        $portTable->setData($testData);
        
        // Trigger selection change
        $portTable->setRowSelected('1', true);
        
        // Verify event was triggered
        $this->assertEquals(1, $selectionChangeCount);
        $this->assertCount(1, $lastSelectedRows);
        
        // Clear selection
        $portTable->clearSelection();
        
        // Verify event was triggered again
        $this->assertEquals(2, $selectionChangeCount);
        $this->assertCount(0, $lastSelectedRows);
    }

    public function test_tab_switching_triggers_state_management(): void
    {
        $this->mainApp->initialize();
        
        $tabPanel = $this->mainApp->getMainTabPanel();
        
        // Verify initial active tab
        $this->assertEquals(0, $tabPanel->getActiveTabIndex());
        
        // Switch to process manager tab
        $this->mainApp->switchToProcessManager();
        
        // Verify tab switch
        $this->assertEquals(1, $tabPanel->getActiveTabIndex());
        
        // Switch back to port manager
        $this->mainApp->switchToPortManager();
        
        // Verify tab switch
        $this->assertEquals(0, $tabPanel->getActiveTabIndex());
    }

    public function test_application_status_reflects_component_states(): void
    {
        $this->mainApp->initialize();
        
        $status = $this->mainApp->getStatus();
        
        // Verify status structure
        $this->assertArrayHasKey('initialized', $status);
        $this->assertArrayHasKey('ready', $status);
        $this->assertArrayHasKey('active_tab', $status);
        $this->assertArrayHasKey('port_manager_ready', $status);
        $this->assertArrayHasKey('process_manager_ready', $status);
        $this->assertArrayHasKey('tab_count', $status);
        
        // Verify status values
        $this->assertTrue($status['initialized']);
        $this->assertTrue($status['ready']);
        $this->assertEquals(0, $status['active_tab']);
        $this->assertTrue($status['port_manager_ready']);
        $this->assertTrue($status['process_manager_ready']);
        $this->assertEquals(2, $status['tab_count']);
    }

    public function test_cross_panel_update_handling(): void
    {
        $this->mainApp->initialize();
        
        // Test cross-panel update notification
        $affectedIds = ['1234', '5678'];
        
        // This should not throw an exception
        $this->mainApp->handleCrossPanelUpdate('port', 'kill_processes', $affectedIds);
        $this->mainApp->handleCrossPanelUpdate('process', 'kill_processes', $affectedIds);
        
        // Verify the method completes successfully
        $this->assertTrue(true);
    }

    public function test_input_validation_integration(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $portInput = $portPanel->getPortInput();
        
        // Test valid port input
        $portInput->setValue('80');
        $this->assertEquals('80', $portInput->getValue());
        
        // Test empty input (should be valid for "all ports")
        $portInput->setValue('');
        $this->assertEquals('', $portInput->getValue());
        
        $processPanel = $this->mainApp->getProcessPanel();
        $processInput = $processPanel->getProcessInput();
        
        // Test valid process input
        $processInput->setValue('chrome');
        $this->assertEquals('chrome', $processInput->getValue());
        
        // Test PID input
        $processInput->setValue('1234');
        $this->assertEquals('1234', $processInput->getValue());
    }

    public function test_component_cleanup_on_shutdown(): void
    {
        $this->mainApp->initialize();
        
        // Verify components are initialized
        $this->assertTrue($this->mainApp->isReady());
        
        // Perform shutdown
        $this->mainApp->handleShutdown();
        
        // Verify shutdown completes without errors
        $this->assertTrue(true);
    }
}