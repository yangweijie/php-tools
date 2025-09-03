<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use App\Ardillo\Components\PortManagementPanel;
use App\Ardillo\Components\ProcessManagementPanel;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;

class ManagementPanelFeedbackTest extends TestCase
{
    private PortManager $portManager;
    private ProcessManager $processManager;
    private SystemCommandService $systemCommandService;
    private DataFormatterService $dataFormatterService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock services
        $this->systemCommandService = $this->createMock(SystemCommandService::class);
        $this->dataFormatterService = $this->createMock(DataFormatterService::class);
        
        // Create managers
        $this->portManager = new PortManager(
            $this->systemCommandService,
            $this->dataFormatterService
        );
        
        $this->processManager = new ProcessManager(
            $this->systemCommandService,
            $this->dataFormatterService
        );
    }

    public function test_port_management_panel_has_feedback_components()
    {
        $panel = new PortManagementPanel($this->portManager);
        $panel->initialize();
        
        $this->assertTrue($panel->isInitialized());
        $this->assertInstanceOf(PortManagementPanel::class, $panel);
    }

    public function test_process_management_panel_has_feedback_components()
    {
        $panel = new ProcessManagementPanel($this->processManager);
        $panel->initialize();
        
        $this->assertTrue($panel->isInitialized());
        $this->assertInstanceOf(ProcessManagementPanel::class, $panel);
    }

    public function test_port_panel_handles_empty_selection_gracefully()
    {
        // Mock the port manager to return empty results
        $this->systemCommandService
            ->method('queryPorts')
            ->willReturn(['output' => [], 'raw_output' => '']);
        
        $this->dataFormatterService
            ->method('formatPortData')
            ->willReturn([]);
        
        $panel = new PortManagementPanel($this->portManager);
        $panel->initialize();
        
        // Get the port table and verify it's empty
        $portTable = $panel->getPortTable();
        $this->assertEquals(0, $portTable->getRowCount());
        $this->assertEquals(0, $portTable->getSelectedRowCount());
    }

    public function test_process_panel_handles_empty_selection_gracefully()
    {
        // Mock the process manager to return empty results
        $this->systemCommandService
            ->method('queryProcesses')
            ->willReturn(['output' => [], 'raw_output' => '']);
        
        $this->dataFormatterService
            ->method('formatProcessData')
            ->willReturn([]);
        
        $panel = new ProcessManagementPanel($this->processManager);
        $panel->initialize();
        
        // Get the process table and verify it's empty
        $processTable = $panel->getProcessTable();
        $this->assertEquals(0, $processTable->getRowCount());
        $this->assertEquals(0, $processTable->getSelectedRowCount());
    }

    public function test_port_panel_input_validation()
    {
        $panel = new PortManagementPanel($this->portManager);
        $panel->initialize();
        
        // Test that the port input component exists
        $portInput = $panel->getPortInput();
        $this->assertNotNull($portInput);
        
        // Test input validation through the manager
        $this->assertTrue($this->portManager->validateInput(''));
        $this->assertTrue($this->portManager->validateInput('80'));
        $this->assertTrue($this->portManager->validateInput('8080'));
        $this->assertFalse($this->portManager->validateInput('invalid'));
        $this->assertFalse($this->portManager->validateInput('99999'));
    }

    public function test_process_panel_input_validation()
    {
        $panel = new ProcessManagementPanel($this->processManager);
        $panel->initialize();
        
        // Test that the process input component exists
        $processInput = $panel->getProcessInput();
        $this->assertNotNull($processInput);
        
        // Test input validation through the manager
        $this->assertTrue($this->processManager->validateInput(''));
        $this->assertTrue($this->processManager->validateInput('1234'));
        $this->assertTrue($this->processManager->validateInput('chrome'));
        $this->assertFalse($this->processManager->validateInput('invalid<>'));
    }

    public function test_batch_kill_operation_structure()
    {
        // Test that the managers return proper batch operation results
        $mockResults = [
            'success' => true,
            'message' => 'Successfully killed 2 of 3 processes',
            'results' => [
                ['pid' => '1234', 'success' => true, 'message' => 'Killed successfully'],
                ['pid' => '5678', 'success' => true, 'message' => 'Killed successfully'],
                ['pid' => '9012', 'success' => false, 'message' => 'Permission denied']
            ],
            'summary' => [
                'total' => 3,
                'success' => 2,
                'failed' => 1
            ]
        ];
        
        // Mock the kill operation
        $this->systemCommandService
            ->method('killProcess')
            ->willReturn(['success' => true]);
        
        $result = $this->portManager->killSelected(['1234', '5678', '9012']);
        
        // Verify the result structure
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('summary', $result);
        
        // Verify summary structure
        $this->assertArrayHasKey('total', $result['summary']);
        $this->assertArrayHasKey('success', $result['summary']);
        $this->assertArrayHasKey('failed', $result['summary']);
    }

    public function test_system_process_protection()
    {
        // Test that system processes are protected from killing
        $this->assertTrue($this->processManager->isSystemProcess('1'));
        $this->assertTrue($this->processManager->isSystemProcess('0'));
        $this->assertFalse($this->processManager->isSystemProcess('1000'));
        $this->assertFalse($this->processManager->isSystemProcess('5678'));
    }

    public function test_panel_readiness_check()
    {
        // Mock services as available
        $this->systemCommandService
            ->method('isAvailable')
            ->willReturn(true);
        
        $this->dataFormatterService
            ->method('isAvailable')
            ->willReturn(true);
        
        $portPanel = new PortManagementPanel($this->portManager);
        $portPanel->initialize();
        
        $processPanel = new ProcessManagementPanel($this->processManager);
        $processPanel->initialize();
        
        // Both panels should be ready when services are available
        $this->assertTrue($portPanel->isReady());
        $this->assertTrue($processPanel->isReady());
    }

    public function test_panel_not_ready_when_services_unavailable()
    {
        // Mock services as unavailable
        $this->systemCommandService
            ->method('isAvailable')
            ->willReturn(false);
        
        $this->dataFormatterService
            ->method('isAvailable')
            ->willReturn(false);
        
        $portPanel = new PortManagementPanel($this->portManager);
        $portPanel->initialize();
        
        $processPanel = new ProcessManagementPanel($this->processManager);
        $processPanel->initialize();
        
        // Panels should not be ready when services are unavailable
        $this->assertFalse($portPanel->isReady());
        $this->assertFalse($processPanel->isReady());
    }
}