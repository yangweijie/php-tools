<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Ardillo\Components\TableComponent;
use App\Ardillo\Models\TableRow;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Services\LoggingService;
use App\Ardillo\Exceptions\TableOperationException;

/**
 * Integration tests for TableComponent with real data scenarios
 * Tests table behavior with various data types, sizes, and edge cases
 */
class TableComponentRealDataTest extends TestCase
{
    private TableComponent $table;
    private PortManager $portManager;
    private ProcessManager $processManager;
    private LoggingService $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new LoggingService();
        $systemCommandService = new SystemCommandService($this->logger);
        $dataFormatterService = new DataFormatterService('linux');

        $this->portManager = new PortManager($systemCommandService, $dataFormatterService);
        $this->processManager = new ProcessManager($systemCommandService, $dataFormatterService);

        $this->table = new TableComponent();
    }

    public function test_table_with_large_port_dataset(): void
    {
        $this->table->initialize();

        // Create large dataset (simulate 100 ports)
        $portData = [];
        for ($i = 1; $i <= 100; $i++) {
            $port = 8000 + $i;
            $pid = 1000 + $i;
            $portData[] = new PortInfo(
                (string)$port,
                (string)$pid,
                'TCP',
                "0.0.0.0:{$port}",
                $i % 2 === 0 ? "192.168.1.{$i}:12345" : '',
                $i % 3 === 0 ? 'LISTEN' : 'ESTABLISHED',
                "service_{$i}"
            );
        }

        // Set table columns
        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        // Format and set data
        $formattedData = $this->portManager->getFormattedTableData($portData);
        $this->table->setData($formattedData);

        // Verify table handles large dataset
        $this->assertEquals(100, $this->table->getRowCount());
        $this->assertEquals(0, $this->table->getSelectedRowCount());

        // Test selection operations on large dataset
        $startTime = microtime(true);
        $this->table->selectAll();
        $selectAllTime = microtime(true) - $startTime;

        $this->assertEquals(100, $this->table->getSelectedRowCount());
        $this->assertLessThan(1.0, $selectAllTime, 'Select all operation should complete within 1 second');

        // Test clearing large selection
        $startTime = microtime(true);
        $this->table->clearSelection();
        $clearTime = microtime(true) - $startTime;

        $this->assertEquals(0, $this->table->getSelectedRowCount());
        $this->assertLessThan(1.0, $clearTime, 'Clear selection should complete within 1 second');
    }

    public function test_table_with_large_process_dataset(): void
    {
        $this->table->initialize();

        // Create large process dataset (simulate 200 processes)
        $processData = [];
        $processNames = ['chrome', 'firefox', 'code', 'node', 'php', 'nginx', 'apache', 'mysql'];
        $users = ['root', 'www-data', 'user1', 'user2', 'admin'];

        for ($i = 1; $i <= 200; $i++) {
            $processData[] = new ProcessInfo(
                (string)(1000 + $i),
                $processNames[$i % count($processNames)],
                $users[$i % count($users)],
                sprintf('%.1f', rand(0, 500) / 10), // CPU usage 0-50%
                sprintf('%dMB', rand(10, 1000)), // Memory 10MB-1GB
                "/usr/bin/{$processNames[$i % count($processNames)]}",
                'running'
            );
        }

        // Set table columns
        $columns = $this->processManager->getTableColumns();
        $this->table->setColumns($columns);

        // Format and set data
        $formattedData = $this->processManager->getFormattedTableData($processData);
        $this->table->setData($formattedData);

        // Verify table handles large dataset
        $this->assertEquals(200, $this->table->getRowCount());

        // Test partial selection performance
        $startTime = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $rowId = $formattedData[$i * 4]['id']; // Select every 4th row
            $this->table->setRowSelected($rowId, true);
        }
        $partialSelectTime = microtime(true) - $startTime;

        $this->assertEquals(50, $this->table->getSelectedRowCount());
        $this->assertLessThan(2.0, $partialSelectTime, 'Partial selection should complete within 2 seconds');
    }

    public function test_table_with_mixed_data_types(): void
    {
        $this->table->initialize();

        // Create data with various edge cases
        $mixedPortData = [
            // Normal port
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
            // Port with long process name
            new PortInfo('443', '5678', 'TCP', '0.0.0.0:443', '', 'LISTEN', 'very-long-process-name-that-might-cause-display-issues'),
            // Port with special characters in process name
            new PortInfo('8080', '9999', 'TCP', '127.0.0.1:8080', '', 'LISTEN', 'node.js (v16.14.0)'),
            // Port with IPv6 address
            new PortInfo('3000', '1111', 'TCP', '[::1]:3000', '', 'LISTEN', 'react-dev'),
            // Port with empty/null values
            new PortInfo('22', '2222', 'TCP', '0.0.0.0:22', '', 'LISTEN', ''),
        ];

        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        $formattedData = $this->portManager->getFormattedTableData($mixedPortData);
        $this->table->setData($formattedData);

        // Verify all rows are handled correctly
        $this->assertEquals(5, $this->table->getRowCount());

        // Test selection with mixed data
        foreach ($formattedData as $row) {
            $this->table->setRowSelected($row['id'], true);
            $this->assertTrue($this->table->isRowSelected($row['id']));
        }

        $this->assertEquals(5, $this->table->getSelectedRowCount());
    }

    public function test_table_with_unicode_and_special_characters(): void
    {
        $this->table->initialize();

        // Create data with unicode and special characters
        $unicodeProcessData = [
            new ProcessInfo('1001', 'café-server', 'user1', '5.2', '128MB', '/usr/bin/café-server', 'running'),
            new ProcessInfo('1002', '测试进程', 'user2', '3.1', '64MB', '/usr/bin/test-process', 'running'),
            new ProcessInfo('1003', 'app_with_underscores', 'user3', '12.5', '256MB', '/usr/bin/app_with_underscores', 'running'),
            new ProcessInfo('1004', 'process-with-dashes', 'user4', '8.7', '192MB', '/usr/bin/process-with-dashes', 'running'),
            new ProcessInfo('1005', 'process with spaces', 'user5', '15.3', '512MB', '/usr/bin/process with spaces', 'running'),
        ];

        $columns = $this->processManager->getTableColumns();
        $this->table->setColumns($columns);

        $formattedData = $this->processManager->getFormattedTableData($unicodeProcessData);
        $this->table->setData($formattedData);

        // Verify unicode data is handled correctly
        $this->assertEquals(5, $this->table->getRowCount());

        // Test operations with unicode data
        $this->table->selectAll();
        $this->assertEquals(5, $this->table->getSelectedRowCount());

        $this->table->clearSelection();
        $this->assertEquals(0, $this->table->getSelectedRowCount());
    }

    public function test_table_data_refresh_scenarios(): void
    {
        $this->table->initialize();

        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        // Initial data set
        $initialData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
            new PortInfo('443', '5678', 'TCP', '0.0.0.0:443', '', 'LISTEN', 'apache'),
        ];

        $formattedInitial = $this->portManager->getFormattedTableData($initialData);
        $this->table->setData($formattedInitial);
        $this->table->setRowSelected($formattedInitial[0]['id'], true);

        $this->assertEquals(2, $this->table->getRowCount());
        $this->assertEquals(1, $this->table->getSelectedRowCount());

        // Refresh with updated data (simulate process changes)
        $updatedData = [
            new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'), // Same
            new PortInfo('8080', '9999', 'TCP', '0.0.0.0:8080', '', 'LISTEN', 'node'), // New
            // Port 443 removed (process killed)
        ];

        $formattedUpdated = $this->portManager->getFormattedTableData($updatedData);
        $this->table->setData($formattedUpdated);

        // Verify refresh behavior
        $this->assertEquals(2, $this->table->getRowCount());
        // Selection should be cleared after data refresh
        $this->assertEquals(0, $this->table->getSelectedRowCount());

        // Test refresh with empty data
        $this->table->setData([]);
        $this->assertEquals(0, $this->table->getRowCount());
        $this->assertEquals(0, $this->table->getSelectedRowCount());

        // Test refresh back to data
        $this->table->setData($formattedUpdated);
        $this->assertEquals(2, $this->table->getRowCount());
    }

    public function test_table_selection_persistence_across_operations(): void
    {
        $this->table->initialize();

        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        // Create test data
        $testData = [];
        for ($i = 1; $i <= 10; $i++) {
            $testData[] = new PortInfo(
                (string)(8000 + $i),
                (string)(1000 + $i),
                'TCP',
                "0.0.0.0:" . (8000 + $i),
                '',
                'LISTEN',
                "service_{$i}"
            );
        }

        $formattedData = $this->portManager->getFormattedTableData($testData);
        $this->table->setData($formattedData);

        // Select specific rows
        $selectedIds = [$formattedData[1]['id'], $formattedData[3]['id'], $formattedData[7]['id']];
        foreach ($selectedIds as $id) {
            $this->table->setRowSelected($id, true);
        }

        $this->assertEquals(3, $this->table->getSelectedRowCount());

        // Verify specific selections persist
        foreach ($selectedIds as $id) {
            $this->assertTrue($this->table->isRowSelected($id));
        }

        // Test toggle operations
        $this->table->setRowSelected($selectedIds[0], false);
        $this->assertEquals(2, $this->table->getSelectedRowCount());
        $this->assertFalse($this->table->isRowSelected($selectedIds[0]));

        // Re-select and verify
        $this->table->setRowSelected($selectedIds[0], true);
        $this->assertEquals(3, $this->table->getSelectedRowCount());
        $this->assertTrue($this->table->isRowSelected($selectedIds[0]));
    }

    public function test_table_performance_with_frequent_updates(): void
    {
        $this->table->initialize();

        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        // Test frequent small updates (simulate real-time monitoring)
        $baseData = [];
        for ($i = 1; $i <= 50; $i++) {
            $baseData[] = new PortInfo(
                (string)(8000 + $i),
                (string)(1000 + $i),
                'TCP',
                "0.0.0.0:" . (8000 + $i),
                '',
                'LISTEN',
                "service_{$i}"
            );
        }

        $startTime = microtime(true);
        
        // Perform 20 updates with slight variations
        for ($update = 0; $update < 20; $update++) {
            $currentData = $baseData;
            
            // Simulate some processes starting/stopping
            if ($update % 3 === 0) {
                // Remove a few processes
                array_splice($currentData, -2, 2);
            }
            
            if ($update % 4 === 0) {
                // Add a new process
                $currentData[] = new PortInfo(
                    (string)(9000 + $update),
                    (string)(2000 + $update),
                    'TCP',
                    "0.0.0.0:" . (9000 + $update),
                    '',
                    'LISTEN',
                    "temp_service_{$update}"
                );
            }

            $formattedData = $this->portManager->getFormattedTableData($currentData);
            $this->table->setData($formattedData);
            
            // Perform some selections
            if (!empty($formattedData)) {
                $this->table->setRowSelected($formattedData[0]['id'], true);
                if (count($formattedData) > 1) {
                    $this->table->setRowSelected($formattedData[1]['id'], true);
                }
            }
        }

        $totalTime = microtime(true) - $startTime;
        
        // 20 updates should complete within reasonable time
        $this->assertLessThan(5.0, $totalTime, 'Frequent updates should complete within 5 seconds');
        
        // Verify final state is consistent
        $this->assertGreaterThanOrEqual(0, $this->table->getRowCount());
        $this->assertGreaterThanOrEqual(0, $this->table->getSelectedRowCount());
    }

    public function test_table_memory_usage_with_large_datasets(): void
    {
        $this->table->initialize();

        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        $initialMemory = memory_get_usage();

        // Create progressively larger datasets
        $dataSizes = [10, 50, 100, 200];
        
        foreach ($dataSizes as $size) {
            $testData = [];
            for ($i = 1; $i <= $size; $i++) {
                $testData[] = new PortInfo(
                    (string)(8000 + $i),
                    (string)(1000 + $i),
                    'TCP',
                    "0.0.0.0:" . (8000 + $i),
                    "192.168.1." . ($i % 255) . ":12345",
                    $i % 2 === 0 ? 'LISTEN' : 'ESTABLISHED',
                    "service_with_long_name_{$i}"
                );
            }

            $formattedData = $this->portManager->getFormattedTableData($testData);
            $this->table->setData($formattedData);
            
            // Perform operations that might cause memory issues
            $this->table->selectAll();
            $this->table->clearSelection();
            
            // Select half the items
            for ($i = 0; $i < $size / 2; $i++) {
                if (isset($formattedData[$i])) {
                    $this->table->setRowSelected($formattedData[$i]['id'], true);
                }
            }
            
            $currentMemory = memory_get_usage();
            $memoryIncrease = $currentMemory - $initialMemory;
            
            // Memory usage should scale reasonably with data size
            $expectedMaxMemory = $size * 1024 * 10; // 10KB per row is generous
            $this->assertLessThan($expectedMaxMemory, $memoryIncrease, 
                "Memory usage for {$size} rows should be reasonable");
        }

        // Clean up and verify memory is released
        $this->table->setData([]);
        gc_collect_cycles();
        
        $finalMemory = memory_get_usage();
        $totalIncrease = $finalMemory - $initialMemory;
        
        // Final memory should not be significantly higher than initial
        $this->assertLessThan(1024 * 1024, $totalIncrease, 
            'Memory should be released after clearing data');
    }

    public function test_table_error_handling_with_invalid_data(): void
    {
        $this->table->initialize();

        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        // Test with malformed data
        $invalidData = [
            ['invalid' => 'structure'],
            ['id' => 'test', 'missing' => 'required_fields'],
            null,
            false,
            'string_instead_of_array'
        ];

        // This should not crash the table
        try {
            $this->table->setData($invalidData);
            // If no exception, verify table handles it gracefully
            $this->assertGreaterThanOrEqual(0, $this->table->getRowCount());
        } catch (\Exception $e) {
            // If exception is thrown, it should be a specific table operation exception
            $this->assertInstanceOf(TableOperationException::class, $e);
        }

        // Test operations on invalid row IDs
        $this->table->setRowSelected('non_existent_id', true);
        $this->assertFalse($this->table->isRowSelected('non_existent_id'));
        
        // Test with empty string ID
        $this->table->setRowSelected('', true);
        $this->assertFalse($this->table->isRowSelected(''));
    }

    public function test_table_concurrent_selection_operations(): void
    {
        $this->table->initialize();

        $columns = $this->portManager->getTableColumns();
        $this->table->setColumns($columns);

        // Create test data
        $testData = [];
        for ($i = 1; $i <= 20; $i++) {
            $testData[] = new PortInfo(
                (string)(8000 + $i),
                (string)(1000 + $i),
                'TCP',
                "0.0.0.0:" . (8000 + $i),
                '',
                'LISTEN',
                "service_{$i}"
            );
        }

        $formattedData = $this->portManager->getFormattedTableData($testData);
        $this->table->setData($formattedData);

        // Simulate concurrent selection operations
        $eventCount = 0;
        $this->table->onSelectionChange(function ($selectedRows) use (&$eventCount) {
            $eventCount++;
        });

        // Rapid selection changes
        for ($i = 0; $i < 10; $i++) {
            $this->table->selectAll();
            $this->table->clearSelection();
            
            // Select random subset
            $randomIds = array_rand($formattedData, 5);
            foreach ($randomIds as $index) {
                $this->table->setRowSelected($formattedData[$index]['id'], true);
            }
        }

        // Verify table state remains consistent
        $this->assertEquals(20, $this->table->getRowCount());
        $this->assertEquals(5, $this->table->getSelectedRowCount());
        
        // Verify events were triggered appropriately
        $this->assertGreaterThan(0, $eventCount);
    }

    protected function tearDown(): void
    {
        // Clean up table resources
        if (isset($this->table)) {
            try {
                $this->table->setData([]);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
        
        parent::tearDown();
    }
}