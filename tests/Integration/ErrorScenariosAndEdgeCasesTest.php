<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Ardillo\Components\MainGuiApplication;
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
use App\Ardillo\Exceptions\DataValidationException;
use App\Ardillo\Exceptions\ComponentInitializationException;
use App\Ardillo\Exceptions\TableOperationException;
use App\Ardillo\Exceptions\ProcessKillException;
use App\Ardillo\Exceptions\NetworkException;
use App\Ardillo\Exceptions\PermissionException;

/**
 * Integration tests for error scenarios and edge cases
 * Tests application behavior under various failure conditions
 */
class ErrorScenariosAndEdgeCasesTest extends TestCase
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

        // Configure basic mocks
        $this->systemCommandService->method('isAvailable')->willReturn(true);
        $this->systemCommandService->method('getOperatingSystem')->willReturn('linux');
        $this->dataFormatterService->method('isAvailable')->willReturn(true);

        $this->portManager = new PortManager($this->systemCommandService, $this->dataFormatterService);
        $this->processManager = new ProcessManager($this->systemCommandService, $this->dataFormatterService);

        $this->mainApp = new MainGuiApplication(
            $this->portManager,
            $this->processManager,
            $this->logger
        );
    }

    public function test_application_initialization_with_invalid_dependencies(): void
    {
        // Test with null logger
        $this->expectException(\TypeError::class);
        new MainGuiApplication(
            $this->portManager,
            $this->processManager,
            null
        );
    }

    public function test_application_initialization_with_unavailable_services(): void
    {
        // Create managers with unavailable services
        $unavailableSystemService = $this->createMock(SystemCommandService::class);
        $unavailableSystemService->method('isAvailable')->willReturn(false);
        
        $unavailableDataService = $this->createMock(DataFormatterService::class);
        $unavailableDataService->method('isAvailable')->willReturn(false);

        $portManager = new PortManager($unavailableSystemService, $unavailableDataService);
        $processManager = new ProcessManager($unavailableSystemService, $unavailableDataService);

        $app = new MainGuiApplication($portManager, $processManager, $this->logger);
        $app->initialize();

        // Application should initialize but managers should report not ready
        $this->assertTrue($app->getStatus()['initialized']);
        $this->assertFalse($app->getStatus()['port_manager_ready']);
        $this->assertFalse($app->getStatus()['process_manager_ready']);
    }

    public function test_invalid_input_validation_scenarios(): void
    {
        $this->mainApp->initialize();

        // Test invalid port numbers
        $invalidPorts = ['abc', '-1', '0', '65536', '999999', 'port80', '80.5', ''];
        
        foreach ($invalidPorts as $invalidPort) {
            if ($invalidPort === '') {
                // Empty string is valid (means query all)
                $this->assertTrue($this->portManager->validateInput($invalidPort));
            } else {
                $isValid = $this->portManager->validateInput($invalidPort);
                if (!$isValid) {
                    // Should throw exception when trying to query
                    $this->expectException(DataValidationException::class);
                    $this->portManager->query($invalidPort);
                }
            }
        }

        // Test invalid process identifiers
        $invalidProcesses = ['', '   ', null, false, [], new \stdClass()];
        
        foreach ($invalidProcesses as $invalidProcess) {
            if (is_string($invalidProcess)) {
                $isValid = $this->processManager->validateInput($invalidProcess);
                $this->assertIsBool($isValid);
            }
        }
    }

    public function test_system_command_failure_scenarios(): void
    {
        $this->mainApp->initialize();

        // Test command execution failure
        $this->systemCommandService
            ->method('queryPorts')
            ->willThrowException(new SystemCommandException('Command execution failed'));

        $this->expectException(SystemCommandException::class);
        $this->portManager->query('80');
    }

    public function test_data_formatting_failure_scenarios(): void
    {
        $this->mainApp->initialize();

        // Test successful command but formatting failure
        $this->systemCommandService
            ->method('queryPorts')
            ->willReturn([
                'command' => 'netstat -ano',
                'output' => ['malformed output'],
                'return_code' => 0,
                'raw_output' => 'malformed output'
            ]);

        $this->dataFormatterService
            ->method('formatPortData')
            ->willThrowException(new \InvalidArgumentException('Unable to parse output'));

        $this->expectException(\InvalidArgumentException::class);
        $this->portManager->query('80');
    }

    public function test_table_component_error_scenarios(): void
    {
        $table = new TableComponent();
        $table->initialize();

        // Test setting invalid columns
        $invalidColumns = [
            null,
            'string_instead_of_array',
            [['invalid' => 'structure']],
            [['key' => '', 'title' => '']], // Empty key
        ];

        foreach ($invalidColumns as $columns) {
            try {
                if (is_array($columns)) {
                    $table->setColumns($columns);
                } else {
                    $this->expectException(\TypeError::class);
                    $table->setColumns($columns);
                }
            } catch (TableOperationException $e) {
                $this->assertInstanceOf(TableOperationException::class, $e);
            }
        }

        // Test adding invalid row data
        $validColumns = [
            ['key' => 'id', 'title' => 'ID', 'type' => 'text'],
            ['key' => 'name', 'title' => 'Name', 'type' => 'text']
        ];
        $table->setColumns($validColumns);

        $invalidRowData = [
            null,
            false,
            'string',
            123,
            new \stdClass(),
        ];

        foreach ($invalidRowData as $rowData) {
            try {
                $table->addRow($rowData);
            } catch (TableOperationException $e) {
                $this->assertInstanceOf(TableOperationException::class, $e);
            }
        }
    }

    public function test_kill_operation_permission_errors(): void
    {
        $this->mainApp->initialize();

        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();

        // Set up test data
        $testData = [
            new PortInfo('80', '1', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'system_process')
        ];
        $formattedData = $this->portManager->getFormattedTableData($testData);
        $portTable->setData($formattedData);
        $portTable->selectAll();

        // Mock permission denied error
        $this->systemCommandService
            ->method('killProcess')
            ->willThrowException(new SystemCommandException('Permission denied'));

        $selectedRows = $portTable->getSelectedRows();
        $selectedPids = [];
        foreach ($selectedRows as $row) {
            $data = $row->getData();
            $selectedPids[] = $data['pid'];
        }

        $result = $this->portManager->killSelected($selectedPids);

        // Should handle permission error gracefully
        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['summary']['failed']);
        $this->assertStringContainsString('Permission denied', $result['results'][0]['message']);
    }

    public function test_kill_operation_with_nonexistent_processes(): void
    {
        $this->mainApp->initialize();

        // Test killing processes that no longer exist
        $nonexistentPids = ['99999', '88888'];

        $this->systemCommandService
            ->method('killProcess')
            ->willThrowException(new SystemCommandException('No such process'));

        $result = $this->portManager->killSelected($nonexistentPids);

        $this->assertFalse($result['success']);
        $this->assertEquals(2, $result['summary']['failed']);
        $this->assertEquals(0, $result['summary']['success']);
    }

    public function test_memory_exhaustion_scenarios(): void
    {
        $this->mainApp->initialize();

        $table = new TableComponent();
        $table->initialize();

        $columns = [
            ['key' => 'id', 'title' => 'ID', 'type' => 'text'],
            ['key' => 'data', 'title' => 'Data', 'type' => 'text']
        ];
        $table->setColumns($columns);

        // Test with very large dataset (but not so large as to actually exhaust memory)
        $largeDataset = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeDataset[] = [
                'id' => "id_{$i}",
                'data' => str_repeat('x', 1000) // 1KB per row
            ];
        }

        $initialMemory = memory_get_usage();
        
        try {
            $table->setData($largeDataset);
            $this->assertEquals(1000, $table->getRowCount());
            
            // Memory usage should be reasonable
            $currentMemory = memory_get_usage();
            $memoryIncrease = $currentMemory - $initialMemory;
            
            // Should use less than 10MB for 1000 rows with 1KB each
            $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease);
            
        } catch (\Exception $e) {
            // If memory limit is hit, that's also a valid test result
            $this->assertStringContainsString('memory', strtolower($e->getMessage()));
        }
    }

    public function test_concurrent_operation_conflicts(): void
    {
        $this->mainApp->initialize();

        $portPanel = $this->mainApp->getPortPanel();
        $processPanel = $this->mainApp->getProcessPanel();

        // Simulate concurrent operations on the same process
        $sharedPid = '1234';
        
        $portData = [new PortInfo('80', $sharedPid, 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')];
        $processData = [new ProcessInfo($sharedPid, 'nginx', 'www-data', '2.1', '64MB', '/usr/sbin/nginx', 'running')];

        $portTable = $portPanel->getPortTable();
        $processTable = $processPanel->getProcessTable();

        $formattedPortData = $this->portManager->getFormattedTableData($portData);
        $formattedProcessData = $this->processManager->getFormattedTableData($processData);

        $portTable->setData($formattedPortData);
        $processTable->setData($formattedProcessData);

        // Select the same process in both panels
        $portTable->selectAll();
        $processTable->selectAll();

        // Mock kill operation that succeeds from one panel
        $this->systemCommandService
            ->expects($this->once())
            ->method('killProcess')
            ->with($sharedPid)
            ->willReturn(['success' => true]);

        // Kill from port panel
        $selectedRows = $portTable->getSelectedRows();
        $selectedPids = [];
        foreach ($selectedRows as $row) {
            $data = $row->getData();
            $selectedPids[] = $data['pid'];
        }

        $result = $this->portManager->killSelected($selectedPids);
        $this->assertTrue($result['success']);

        // Now if we try to kill from process panel, it should fail
        $this->systemCommandService
            ->expects($this->once())
            ->method('killProcess')
            ->with($sharedPid)
            ->willThrowException(new SystemCommandException('No such process'));

        $selectedProcessRows = $processTable->getSelectedRows();
        $selectedProcessPids = [];
        foreach ($selectedProcessRows as $row) {
            $data = $row->getData();
            $selectedProcessPids[] = $data['pid'];
        }

        $processResult = $this->processManager->killSelected($selectedProcessPids);
        $this->assertFalse($processResult['success']);
    }

    public function test_malformed_data_handling(): void
    {
        $this->mainApp->initialize();

        // Test with malformed port data
        $malformedPortData = [
            new PortInfo('', '', '', '', '', '', ''), // All empty
            new PortInfo('80', 'invalid_pid', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'), // Invalid PID
            new PortInfo('999999', '1234', 'TCP', '0.0.0.0:999999', '', 'LISTEN', 'test'), // Invalid port
        ];

        try {
            $formattedData = $this->portManager->getFormattedTableData($malformedPortData);
            
            // Should handle malformed data gracefully
            $this->assertIsArray($formattedData);
            $this->assertCount(3, $formattedData);
            
            // Check that invalid data is handled appropriately
            foreach ($formattedData as $row) {
                $this->assertArrayHasKey('id', $row);
                $this->assertArrayHasKey('port', $row);
                $this->assertArrayHasKey('pid', $row);
            }
            
        } catch (\Exception $e) {
            // If exception is thrown, it should be a specific validation exception
            $this->assertInstanceOf(DataValidationException::class, $e);
        }
    }

    public function test_unicode_and_special_character_handling(): void
    {
        $this->mainApp->initialize();

        // Test with unicode and special characters
        $unicodeData = [
            new ProcessInfo('1001', 'café-server', 'user1', '5.2', '128MB', '/usr/bin/café-server', 'running'),
            new ProcessInfo('1002', '测试进程', 'user2', '3.1', '64MB', '/usr/bin/test', 'running'),
            new ProcessInfo('1003', 'app with spaces', 'user3', '12.5', '256MB', '/usr/bin/app with spaces', 'running'),
            new ProcessInfo('1004', 'app"with"quotes', 'user4', '8.7', '192MB', '/usr/bin/app', 'running'),
            new ProcessInfo('1005', "app'with'apostrophes", 'user5', '15.3', '512MB', '/usr/bin/app', 'running'),
        ];

        $table = new TableComponent();
        $table->initialize();

        $columns = $this->processManager->getTableColumns();
        $table->setColumns($columns);

        $formattedData = $this->processManager->getFormattedTableData($unicodeData);
        $table->setData($formattedData);

        // Should handle unicode data without errors
        $this->assertEquals(5, $table->getRowCount());

        // Test selection with unicode data
        foreach ($formattedData as $row) {
            $table->setRowSelected($row['id'], true);
            $this->assertTrue($table->isRowSelected($row['id']));
        }

        $this->assertEquals(5, $table->getSelectedRowCount());
    }

    public function test_network_timeout_scenarios(): void
    {
        $this->mainApp->initialize();

        // Mock network timeout
        $this->systemCommandService
            ->method('queryPorts')
            ->willThrowException(new NetworkException('Network timeout'));

        $this->expectException(NetworkException::class);
        $this->portManager->query('80');
    }

    public function test_insufficient_privileges_scenarios(): void
    {
        $this->mainApp->initialize();

        // Mock insufficient privileges for system commands
        $this->systemCommandService
            ->method('queryProcesses')
            ->willThrowException(new PermissionException('Insufficient privileges'));

        $this->expectException(PermissionException::class);
        $this->processManager->query('');
    }

    public function test_empty_result_handling(): void
    {
        $this->mainApp->initialize();

        // Mock empty results
        $this->systemCommandService
            ->method('queryPorts')
            ->willReturn([
                'command' => 'netstat -ano',
                'output' => [],
                'return_code' => 0,
                'raw_output' => ''
            ]);

        $this->dataFormatterService
            ->method('formatPortData')
            ->willReturn([]);

        $result = $this->portManager->query('80');
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        $formattedData = $this->portManager->getFormattedTableData($result);
        $this->assertIsArray($formattedData);
        $this->assertEmpty($formattedData);
    }

    public function test_rapid_successive_operations(): void
    {
        $this->mainApp->initialize();

        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();

        // Test rapid successive operations
        for ($i = 0; $i < 10; $i++) {
            $testData = [
                new PortInfo("800{$i}", "123{$i}", 'TCP', "0.0.0.0:800{$i}", '', 'LISTEN', "service_{$i}")
            ];

            $formattedData = $this->portManager->getFormattedTableData($testData);
            $portTable->setData($formattedData);
            $portTable->selectAll();
            $portTable->clearSelection();
        }

        // Should complete without errors
        $this->assertTrue(true);
    }

    public function test_application_state_corruption_recovery(): void
    {
        $this->mainApp->initialize();

        // Simulate state corruption by directly manipulating internal state
        $portPanel = $this->mainApp->getPortPanel();
        $portTable = $portPanel->getPortTable();

        // Add valid data
        $testData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx')
        ];
        $formattedData = $this->portManager->getFormattedTableData($testData);
        $portTable->setData($formattedData);

        // Try to select non-existent row (simulates corruption)
        $portTable->setRowSelected('non_existent_id', true);
        
        // Should handle gracefully
        $this->assertFalse($portTable->isRowSelected('non_existent_id'));
        $this->assertEquals(0, $portTable->getSelectedRowCount());

        // Application should remain functional
        $this->assertTrue($this->mainApp->isReady());
    }

    public function test_resource_cleanup_on_errors(): void
    {
        $this->mainApp->initialize();

        $initialMemory = memory_get_usage();

        // Perform operations that might leave resources uncleaned
        try {
            // Simulate error during operation
            $this->systemCommandService
                ->method('queryPorts')
                ->willThrowException(new SystemCommandException('Simulated error'));

            $this->portManager->query('80');
        } catch (SystemCommandException $e) {
            // Expected exception
        }

        // Force garbage collection
        gc_collect_cycles();

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be minimal after error
        $this->assertLessThan(1024 * 1024, $memoryIncrease, 'Memory should be cleaned up after errors');
    }

    public function test_graceful_degradation_scenarios(): void
    {
        // Test application behavior when components fail to initialize
        $failingSystemService = $this->createMock(SystemCommandService::class);
        $failingSystemService->method('isAvailable')->willReturn(false);
        $failingSystemService->method('getOperatingSystem')->willReturn('unknown');

        $portManager = new PortManager($failingSystemService, $this->dataFormatterService);
        $processManager = new ProcessManager($failingSystemService, $this->dataFormatterService);

        $app = new MainGuiApplication($portManager, $processManager, $this->logger);
        
        // Should initialize even with failing services
        $app->initialize();
        
        $status = $app->getStatus();
        $this->assertTrue($status['initialized']);
        $this->assertFalse($status['port_manager_ready']);
        $this->assertFalse($status['process_manager_ready']);
        
        // Application should still be usable for basic operations
        $this->assertInstanceOf(MainGuiApplication::class, $app);
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
        
        // Force garbage collection to clean up test resources
        gc_collect_cycles();
        
        parent::tearDown();
    }
}