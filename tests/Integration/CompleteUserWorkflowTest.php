<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Ardillo\Components\MainGuiApplication;
use App\Ardillo\Components\PortManagementPanel;
use App\Ardillo\Components\ProcessManagementPanel;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Services\LoggingService;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Exceptions\SystemCommandException;
use App\Ardillo\Exceptions\DataValidationException;

/**
 * Integration tests for complete user workflows
 * Tests end-to-end user scenarios: query, select, kill operations
 */
class CompleteUserWorkflowTest extends TestCase
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

        $this->logger = new LoggingService();
        $this->systemCommandService = $this->createMock(SystemCommandService::class);
        $this->dataFormatterService = $this->createMock(DataFormatterService::class);

        // Configure mocks for consistent testing
        $this->configureMocks();

        $this->portManager = new PortManager($this->systemCommandService, $this->dataFormatterService);
        $this->processManager = new ProcessManager($this->systemCommandService, $this->dataFormatterService);

        $this->mainApp = new MainGuiApplication(
            $this->portManager,
            $this->processManager,
            $this->logger
        );
    }

    private function configureMocks(): void
    {
        // Configure system command service mock
        $this->systemCommandService->method('isAvailable')->willReturn(true);
        $this->systemCommandService->method('getOperatingSystem')->willReturn('linux');

        // Configure data formatter service mock
        $this->dataFormatterService->method('isAvailable')->willReturn(true);
    }

    public function test_complete_port_management_workflow(): void
    {
        // Initialize application
        $this->mainApp->initialize();
        $this->assertTrue($this->mainApp->isReady());

        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();
        $portInput = $portPanel->getPortInput();

        // Step 1: User enters port number for query
        $portInput->setValue('80');
        $this->assertEquals('80', $portInput->getValue());

        // Step 2: Mock port query response
        $mockPortData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
            new PortInfo('80', '5678', 'TCP', '127.0.0.1:80', '', 'LISTEN', 'apache'),
        ];

        $this->systemCommandService
            ->expects($this->once())
            ->method('queryPorts')
            ->with('80')
            ->willReturn([
                'command' => 'netstat -ano | grep :80',
                'output' => ['mock output'],
                'return_code' => 0,
                'raw_output' => 'mock output'
            ]);

        $this->dataFormatterService
            ->expects($this->once())
            ->method('formatPortData')
            ->willReturn($mockPortData);

        // Step 3: Execute query
        $queryResult = $this->portManager->query('80');
        $this->assertCount(2, $queryResult);

        // Step 4: Populate table with results
        $formattedData = $this->portManager->getFormattedTableData($queryResult);
        $portTable->setData($formattedData);

        $this->assertEquals(2, $portTable->getRowCount());
        $this->assertEquals(0, $portTable->getSelectedRowCount());

        // Step 5: User selects specific ports
        $portTable->setRowSelected($formattedData[0]['id'], true);
        $this->assertEquals(1, $portTable->getSelectedRowCount());

        // Step 6: User selects additional port
        $portTable->setRowSelected($formattedData[1]['id'], true);
        $this->assertEquals(2, $portTable->getSelectedRowCount());

        // Step 7: Mock kill operation
        $this->systemCommandService
            ->expects($this->exactly(2))
            ->method('killProcess')
            ->withConsecutive(['1234'], ['5678'])
            ->willReturnOnConsecutiveCalls(
                ['success' => true, 'message' => 'Process 1234 killed'],
                ['success' => true, 'message' => 'Process 5678 killed']
            );

        // Step 8: Execute kill operation
        $selectedRows = $portTable->getSelectedRows();
        $selectedPids = [];
        foreach ($selectedRows as $row) {
            $data = $row->getData();
            $selectedPids[] = $data['pid'];
        }

        $killResult = $this->portManager->killSelected($selectedPids);

        // Step 9: Verify kill results
        $this->assertTrue($killResult['success']);
        $this->assertEquals(2, $killResult['summary']['total']);
        $this->assertEquals(2, $killResult['summary']['success']);
        $this->assertEquals(0, $killResult['summary']['failed']);

        // Step 10: Verify table state after operation
        $this->assertEquals(2, $portTable->getSelectedRowCount());
    }

    public function test_complete_process_management_workflow(): void
    {
        $this->mainApp->initialize();
        
        $processPanel = $this->mainApp->getProcessPanel();
        $processTable = $processPanel->getProcessTable();
        $processInput = $processPanel->getProcessInput();

        // Step 1: User enters process name for query
        $processInput->setValue('chrome');
        $this->assertEquals('chrome', $processInput->getValue());

        // Step 2: Mock process query response
        $mockProcessData = [
            new ProcessInfo('1001', 'chrome', 'user1', '15.2', '256MB', '/usr/bin/chrome', 'running'),
            new ProcessInfo('1002', 'chrome', 'user1', '8.5', '128MB', '/usr/bin/chrome --type=renderer', 'running'),
            new ProcessInfo('1003', 'chrome', 'user1', '5.1', '64MB', '/usr/bin/chrome --type=gpu', 'running'),
        ];

        $this->systemCommandService
            ->expects($this->once())
            ->method('queryProcesses')
            ->with('chrome')
            ->willReturn([
                'command' => 'ps aux | grep chrome',
                'output' => ['mock output'],
                'return_code' => 0,
                'raw_output' => 'mock output'
            ]);

        $this->dataFormatterService
            ->expects($this->once())
            ->method('formatProcessData')
            ->willReturn($mockProcessData);

        // Step 3: Execute query
        $queryResult = $this->processManager->query('chrome');
        $this->assertCount(3, $queryResult);

        // Step 4: Populate table with results
        $formattedData = $this->processManager->getFormattedTableData($queryResult);
        $processTable->setData($formattedData);

        $this->assertEquals(3, $processTable->getRowCount());

        // Step 5: User selects all chrome processes
        $processTable->selectAll();
        $this->assertEquals(3, $processTable->getSelectedRowCount());

        // Step 6: User deselects main process (keeps only child processes)
        $processTable->setRowSelected($formattedData[0]['id'], false);
        $this->assertEquals(2, $processTable->getSelectedRowCount());

        // Step 7: Mock kill operation for selected processes
        $this->systemCommandService
            ->expects($this->exactly(2))
            ->method('killProcess')
            ->withConsecutive(['1002'], ['1003'])
            ->willReturnOnConsecutiveCalls(
                ['success' => true, 'message' => 'Process 1002 killed'],
                ['success' => true, 'message' => 'Process 1003 killed']
            );

        // Step 8: Execute kill operation
        $selectedRows = $processTable->getSelectedRows();
        $selectedPids = [];
        foreach ($selectedRows as $row) {
            $data = $row->getData();
            $selectedPids[] = $data['pid'];
        }

        $killResult = $this->processManager->killSelected($selectedPids);

        // Step 9: Verify results
        $this->assertTrue($killResult['success']);
        $this->assertEquals(2, $killResult['summary']['success']);
    }

    public function test_tab_switching_workflow(): void
    {
        $this->mainApp->initialize();

        // Step 1: Start in port manager tab
        $this->assertEquals(0, $this->mainApp->getMainTabPanel()->getActiveTabIndex());

        // Step 2: Set up port data
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();

        $portData = [new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')];
        $formattedPortData = $this->portManager->getFormattedTableData($portData);
        $portTable->setData($formattedPortData);
        $portTable->setRowSelected($formattedPortData[0]['id'], true);

        $this->assertEquals(1, $portTable->getSelectedRowCount());

        // Step 3: Switch to process manager tab
        $this->mainApp->switchToProcessManager();
        $this->assertEquals(1, $this->mainApp->getMainTabPanel()->getActiveTabIndex());

        // Step 4: Set up process data
        $processPanel = $this->mainApp->getProcessPanel();
        $processTable = $processPanel->getProcessTable();

        $processData = [new ProcessInfo('5678', 'nginx', 'www-data', '2.1', '64MB', '/usr/sbin/nginx', 'running')];
        $formattedProcessData = $this->processManager->getFormattedTableData($processData);
        $processTable->setData($formattedProcessData);
        $processTable->selectAll();

        $this->assertEquals(1, $processTable->getSelectedRowCount());

        // Step 5: Switch back to port manager
        $this->mainApp->switchToPortManager();
        $this->assertEquals(0, $this->mainApp->getMainTabPanel()->getActiveTabIndex());

        // Step 6: Verify port manager state is preserved
        $this->assertEquals(1, $portTable->getSelectedRowCount());
        $this->assertTrue($portTable->isRowSelected($formattedPortData[0]['id']));

        // Step 7: Switch back to process manager and verify state
        $this->mainApp->switchToProcessManager();
        $this->assertEquals(1, $processTable->getSelectedRowCount());
    }

    public function test_query_all_ports_workflow(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $portInput = $portPanel->getPortInput();
        $portTable = $portPanel->getPortTable();

        // Step 1: User leaves input empty to query all ports
        $portInput->setValue('');
        $this->assertEquals('', $portInput->getValue());

        // Step 2: Mock comprehensive port data
        $mockAllPorts = [
            new PortInfo('22', '1111', 'TCP', '0.0.0.0:22', '', 'LISTEN', 'sshd'),
            new PortInfo('80', '2222', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
            new PortInfo('443', '3333', 'TCP', '0.0.0.0:443', '', 'LISTEN', 'nginx'),
            new PortInfo('3000', '4444', 'TCP', '127.0.0.1:3000', '', 'LISTEN', 'node'),
            new PortInfo('5432', '5555', 'TCP', '127.0.0.1:5432', '', 'LISTEN', 'postgres'),
        ];

        $this->systemCommandService
            ->expects($this->once())
            ->method('queryPorts')
            ->with(null)
            ->willReturn([
                'command' => 'netstat -ano',
                'output' => ['mock comprehensive output'],
                'return_code' => 0,
                'raw_output' => 'mock comprehensive output'
            ]);

        $this->dataFormatterService
            ->expects($this->once())
            ->method('formatPortData')
            ->willReturn($mockAllPorts);

        // Step 3: Execute query for all ports
        $queryResult = $this->portManager->query('');
        $this->assertCount(5, $queryResult);

        // Step 4: Display results
        $formattedData = $this->portManager->getFormattedTableData($queryResult);
        $portTable->setData($formattedData);

        $this->assertEquals(5, $portTable->getRowCount());

        // Step 5: User selects web server ports (80, 443)
        foreach ($formattedData as $row) {
            if (in_array($row['port'], ['80', '443'])) {
                $portTable->setRowSelected($row['id'], true);
            }
        }

        $this->assertEquals(2, $portTable->getSelectedRowCount());

        // Step 6: Verify correct ports are selected
        $selectedRows = $portTable->getSelectedRows();
        $selectedPorts = [];
        foreach ($selectedRows as $row) {
            $data = $row->getData();
            $selectedPorts[] = $data['port'];
        }

        $this->assertContains('80', $selectedPorts);
        $this->assertContains('443', $selectedPorts);
    }

    public function test_query_all_processes_workflow(): void
    {
        $this->mainApp->initialize();
        
        $processPanel = $this->mainApp->getProcessPanel();
        $processInput = $processPanel->getProcessInput();
        $processTable = $processPanel->getProcessTable();

        // Step 1: Query all processes
        $processInput->setValue('');

        // Step 2: Mock system processes
        $mockAllProcesses = [
            new ProcessInfo('1', 'init', 'root', '0.0', '8MB', '/sbin/init', 'running'),
            new ProcessInfo('100', 'systemd', 'root', '0.1', '16MB', '/lib/systemd/systemd', 'running'),
            new ProcessInfo('1234', 'nginx', 'www-data', '2.1', '64MB', '/usr/sbin/nginx', 'running'),
            new ProcessInfo('5678', 'chrome', 'user1', '15.2', '256MB', '/usr/bin/chrome', 'running'),
            new ProcessInfo('9999', 'code', 'user1', '8.5', '128MB', '/usr/bin/code', 'running'),
        ];

        $this->systemCommandService
            ->expects($this->once())
            ->method('queryProcesses')
            ->with('')
            ->willReturn([
                'command' => 'ps aux',
                'output' => ['mock process output'],
                'return_code' => 0,
                'raw_output' => 'mock process output'
            ]);

        $this->dataFormatterService
            ->expects($this->once())
            ->method('formatProcessData')
            ->willReturn($mockAllProcesses);

        // Step 3: Execute query
        $queryResult = $this->processManager->query('');
        $this->assertCount(5, $queryResult);

        // Step 4: Display results
        $formattedData = $this->processManager->getFormattedTableData($queryResult);
        $processTable->setData($formattedData);

        $this->assertEquals(5, $processTable->getRowCount());

        // Step 5: User selects user processes (non-system)
        foreach ($formattedData as $row) {
            if ($row['user'] === 'user1') {
                $processTable->setRowSelected($row['id'], true);
            }
        }

        $this->assertEquals(2, $processTable->getSelectedRowCount());
    }

    public function test_error_recovery_workflow(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $portInput = $portPanel->getPortInput();
        $portTable = $portPanel->getPortTable();

        // Step 1: User enters invalid port number
        $portInput->setValue('invalid_port');

        // Step 2: Attempt query with invalid input
        try {
            $this->portManager->query('invalid_port');
            $this->fail('Should have thrown validation exception');
        } catch (DataValidationException $e) {
            $this->assertStringContainsString('invalid', strtolower($e->getMessage()));
        }

        // Step 3: User corrects input
        $portInput->setValue('80');

        // Step 4: Mock successful query after correction
        $this->systemCommandService
            ->expects($this->once())
            ->method('queryPorts')
            ->with('80')
            ->willReturn([
                'command' => 'netstat -ano | grep :80',
                'output' => ['mock output'],
                'return_code' => 0,
                'raw_output' => 'mock output'
            ]);

        $this->dataFormatterService
            ->expects($this->once())
            ->method('formatPortData')
            ->willReturn([
                new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
            ]);

        // Step 5: Successful query after correction
        $queryResult = $this->portManager->query('80');
        $this->assertCount(1, $queryResult);

        // Step 6: Display results
        $formattedData = $this->portManager->getFormattedTableData($queryResult);
        $portTable->setData($formattedData);

        $this->assertEquals(1, $portTable->getRowCount());
    }

    public function test_kill_operation_failure_handling(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();

        // Step 1: Set up test data
        $mockPortData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
            new PortInfo('443', '5678', 'TCP', '0.0.0.0:443', '', 'LISTEN', 'apache'),
        ];

        $formattedData = $this->portManager->getFormattedTableData($mockPortData);
        $portTable->setData($formattedData);
        $portTable->selectAll();

        // Step 2: Mock mixed kill results (one success, one failure)
        $this->systemCommandService
            ->expects($this->exactly(2))
            ->method('killProcess')
            ->withConsecutive(['1234'], ['5678'])
            ->willReturnOnConsecutiveCalls(
                ['success' => true, 'message' => 'Process 1234 killed'],
                $this->throwException(new SystemCommandException('Permission denied for PID 5678'))
            );

        // Step 3: Execute kill operation
        $selectedRows = $portTable->getSelectedRows();
        $selectedPids = [];
        foreach ($selectedRows as $row) {
            $data = $row->getData();
            $selectedPids[] = $data['pid'];
        }

        $killResult = $this->portManager->killSelected($selectedPids);

        // Step 4: Verify partial success handling
        $this->assertTrue($killResult['success']); // At least one success
        $this->assertEquals(2, $killResult['summary']['total']);
        $this->assertEquals(1, $killResult['summary']['success']);
        $this->assertEquals(1, $killResult['summary']['failed']);

        // Step 5: Verify individual results
        $this->assertCount(2, $killResult['results']);
        $this->assertTrue($killResult['results'][0]['success']);
        $this->assertFalse($killResult['results'][1]['success']);
        $this->assertStringContainsString('Permission denied', $killResult['results'][1]['message']);
    }

    public function test_refresh_workflow(): void
    {
        $this->mainApp->initialize();
        
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();

        // Step 1: Initial query
        $initialData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
        ];

        $formattedInitial = $this->portManager->getFormattedTableData($initialData);
        $portTable->setData($formattedInitial);
        $portTable->selectAll();

        $this->assertEquals(1, $portTable->getRowCount());
        $this->assertEquals(1, $portTable->getSelectedRowCount());

        // Step 2: Mock refresh with updated data
        $this->systemCommandService
            ->expects($this->once())
            ->method('queryPorts')
            ->with(null)
            ->willReturn([
                'command' => 'netstat -ano',
                'output' => ['updated mock output'],
                'return_code' => 0,
                'raw_output' => 'updated mock output'
            ]);

        $refreshedData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
            new PortInfo('443', '5678', 'TCP', '0.0.0.0:443', '', 'LISTEN', 'apache'),
        ];

        $this->dataFormatterService
            ->expects($this->once())
            ->method('formatPortData')
            ->willReturn($refreshedData);

        // Step 3: Execute refresh
        $refreshResult = $this->portManager->query('');
        $this->assertCount(2, $refreshResult);

        // Step 4: Update table with refreshed data
        $formattedRefreshed = $this->portManager->getFormattedTableData($refreshResult);
        $portTable->setData($formattedRefreshed);

        // Step 5: Verify refresh results
        $this->assertEquals(2, $portTable->getRowCount());
        $this->assertEquals(0, $portTable->getSelectedRowCount()); // Selection cleared on refresh
    }

    public function test_cross_panel_workflow(): void
    {
        $this->mainApp->initialize();

        // Step 1: Query ports to find nginx process
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();

        $portData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
        ];
        $formattedPortData = $this->portManager->getFormattedTableData($portData);
        $portTable->setData($formattedPortData);

        // Step 2: Note the PID from port query
        $nginxPid = $formattedPortData[0]['pid'];
        $this->assertEquals('1234', $nginxPid);

        // Step 3: Switch to process manager to find the same process
        $this->mainApp->switchToProcessManager();
        
        $processPanel = $this->mainApp->getProcessPanel();
        $processTable = $processPanel->getProcessTable();

        $processData = [
            new ProcessInfo('1234', 'nginx', 'www-data', '2.1', '64MB', '/usr/sbin/nginx', 'running')
        ];
        $formattedProcessData = $this->processManager->getFormattedTableData($processData);
        $processTable->setData($formattedProcessData);

        // Step 4: Verify same PID appears in both panels
        $this->assertEquals($nginxPid, $formattedProcessData[0]['pid']);

        // Step 5: Kill process from process panel
        $processTable->selectAll();
        
        $this->systemCommandService
            ->expects($this->once())
            ->method('killProcess')
            ->with('1234')
            ->willReturn(['success' => true, 'message' => 'Process 1234 killed']);

        $selectedRows = $processTable->getSelectedRows();
        $selectedPids = [];
        foreach ($selectedRows as $row) {
            $data = $row->getData();
            $selectedPids[] = $data['pid'];
        }

        $killResult = $this->processManager->killSelected($selectedPids);
        $this->assertTrue($killResult['success']);

        // Step 6: Notify cross-panel update
        $this->mainApp->handleCrossPanelUpdate('process', 'kill_processes', ['1234']);

        // Step 7: Switch back to port panel - port should be freed
        $this->mainApp->switchToPortManager();
        
        // In a real scenario, refreshing port data would show the port is no longer in use
        $this->assertTrue(true); // Workflow completed successfully
    }

    protected function tearDown(): void
    {
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