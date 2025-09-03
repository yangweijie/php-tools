<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use App\Ardillo\Components\TableComponent;
use App\Ardillo\Models\TableRow;
use App\Ardillo\Exceptions\TableOperationException;

/**
 * Test enhanced features of TableComponent (Task 15)
 * 
 * Tests the new sorting, filtering, and performance optimization features
 * added to the TableComponent as part of the final polish phase.
 */
class EnhancedTableComponentTest extends TestCase
{
    private TableComponent $table;
    private array $sampleData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->table = new TableComponent();
        
        // Set up sample columns
        $this->table->setColumns([
            ['key' => 'port', 'title' => 'Port', 'type' => 'text'],
            ['key' => 'pid', 'title' => 'PID', 'type' => 'text'],
            ['key' => 'protocol', 'title' => 'Protocol', 'type' => 'text'],
            ['key' => 'process_name', 'title' => 'Process', 'type' => 'text'],
            ['key' => 'cpu_usage', 'title' => 'CPU %', 'type' => 'text']
        ]);
        
        // Sample data for testing
        $this->sampleData = [
            ['id' => '1', 'data' => ['port' => '80', 'pid' => '1234', 'protocol' => 'TCP', 'process_name' => 'nginx', 'cpu_usage' => '15.5']],
            ['id' => '2', 'data' => ['port' => '443', 'pid' => '1235', 'protocol' => 'TCP', 'process_name' => 'nginx', 'cpu_usage' => '12.3']],
            ['id' => '3', 'data' => ['port' => '22', 'pid' => '567', 'protocol' => 'TCP', 'process_name' => 'sshd', 'cpu_usage' => '0.1']],
            ['id' => '4', 'data' => ['port' => '3306', 'pid' => '2345', 'protocol' => 'TCP', 'process_name' => 'mysql', 'cpu_usage' => '25.7']],
            ['id' => '5', 'data' => ['port' => '8080', 'pid' => '3456', 'protocol' => 'TCP', 'process_name' => 'tomcat', 'cpu_usage' => '8.9']]
        ];
        
        $this->table->initialize();
        $this->table->setData($this->sampleData);
    }

    /**
     * Test basic sorting functionality
     */
    public function test_basic_sorting(): void
    {
        // Test ascending sort by port
        $this->table->sortByColumn('port', 'asc');
        
        $sortInfo = $this->table->getSortInfo();
        $this->assertEquals('port', $sortInfo['column']);
        $this->assertEquals('asc', $sortInfo['direction']);
        
        // Verify sort order (numeric sorting should work)
        $rows = $this->table->getAllRows();
        $ports = array_map(fn($row) => $row->getData()['port'], $rows);
        $this->assertEquals(['22', '80', '443', '3306', '8080'], $ports);
    }

    /**
     * Test descending sort
     */
    public function test_descending_sort(): void
    {
        $this->table->sortByColumn('port', 'desc');
        
        $sortInfo = $this->table->getSortInfo();
        $this->assertEquals('desc', $sortInfo['direction']);
        
        $rows = $this->table->getAllRows();
        $ports = array_map(fn($row) => $row->getData()['port'], $rows);
        $this->assertEquals(['8080', '3306', '443', '80', '22'], $ports);
    }

    /**
     * Test text-based sorting
     */
    public function test_text_sorting(): void
    {
        $this->table->sortByColumn('process_name', 'asc');
        
        $rows = $this->table->getAllRows();
        $processes = array_map(fn($row) => $row->getData()['process_name'], $rows);
        $this->assertEquals(['mysql', 'nginx', 'nginx', 'sshd', 'tomcat'], $processes);
    }

    /**
     * Test invalid sort column
     */
    public function test_invalid_sort_column(): void
    {
        $this->expectException(TableOperationException::class);
        $this->expectExceptionMessage('Column \'invalid_column\' not found for sorting');
        
        $this->table->sortByColumn('invalid_column', 'asc');
    }

    /**
     * Test invalid sort direction
     */
    public function test_invalid_sort_direction(): void
    {
        $this->expectException(TableOperationException::class);
        $this->expectExceptionMessage('Invalid sort direction. Must be "asc" or "desc"');
        
        $this->table->sortByColumn('port', 'invalid');
    }

    /**
     * Test basic filtering with contains operator
     */
    public function test_basic_filtering_contains(): void
    {
        $this->table->addFilter('process_name', 'nginx', 'contains');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(2, $filteredRows);
        
        foreach ($filteredRows as $row) {
            $this->assertStringContainsString('nginx', $row->getData()['process_name']);
        }
    }

    /**
     * Test filtering with equals operator
     */
    public function test_filtering_equals(): void
    {
        $this->table->addFilter('protocol', 'TCP', 'equals');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(5, $filteredRows); // All are TCP
        
        $this->table->addFilter('process_name', 'sshd', 'equals');
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(1, $filteredRows);
        $this->assertEquals('sshd', $filteredRows[0]->getData()['process_name']);
    }

    /**
     * Test filtering with greater_than operator
     */
    public function test_filtering_greater_than(): void
    {
        $this->table->addFilter('cpu_usage', '10', 'greater_than');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(3, $filteredRows); // nginx (15.5, 12.3) and mysql (25.7)
        
        foreach ($filteredRows as $row) {
            $cpuUsage = floatval($row->getData()['cpu_usage']);
            $this->assertGreaterThan(10.0, $cpuUsage);
        }
    }

    /**
     * Test filtering with less_than operator
     */
    public function test_filtering_less_than(): void
    {
        $this->table->addFilter('cpu_usage', '10', 'less_than');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(2, $filteredRows); // sshd (0.1) and tomcat (8.9)
        
        foreach ($filteredRows as $row) {
            $cpuUsage = floatval($row->getData()['cpu_usage']);
            $this->assertLessThan(10.0, $cpuUsage);
        }
    }

    /**
     * Test filtering with starts_with operator
     */
    public function test_filtering_starts_with(): void
    {
        $this->table->addFilter('port', '8', 'starts_with');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(2, $filteredRows); // 80 and 8080
        
        foreach ($filteredRows as $row) {
            $this->assertStringStartsWith('8', $row->getData()['port']);
        }
    }

    /**
     * Test filtering with ends_with operator
     */
    public function test_filtering_ends_with(): void
    {
        $this->table->addFilter('port', '80', 'ends_with');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(2, $filteredRows); // 80 and 8080
        
        foreach ($filteredRows as $row) {
            $this->assertStringEndsWith('80', $row->getData()['port']);
        }
    }

    /**
     * Test multiple filters
     */
    public function test_multiple_filters(): void
    {
        $this->table->addFilter('protocol', 'TCP', 'equals');
        $this->table->addFilter('cpu_usage', '10', 'greater_than');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(3, $filteredRows);
        
        foreach ($filteredRows as $row) {
            $data = $row->getData();
            $this->assertEquals('TCP', $data['protocol']);
            $this->assertGreaterThan(10.0, floatval($data['cpu_usage']));
        }
    }

    /**
     * Test removing filters
     */
    public function test_remove_filter(): void
    {
        $this->table->addFilter('process_name', 'nginx', 'contains');
        $this->table->addFilter('cpu_usage', '10', 'greater_than');
        
        $this->assertCount(2, $this->table->getFilteredRows());
        
        $this->table->removeFilter('process_name');
        $this->assertCount(3, $this->table->getFilteredRows()); // Only CPU filter remains
        
        $this->table->removeFilter('cpu_usage');
        $this->assertCount(5, $this->table->getFilteredRows()); // No filters, all rows
    }

    /**
     * Test clearing all filters
     */
    public function test_clear_filters(): void
    {
        $this->table->addFilter('process_name', 'nginx', 'contains');
        $this->table->addFilter('cpu_usage', '10', 'greater_than');
        
        $this->assertCount(2, $this->table->getFilteredRows());
        
        $this->table->clearFilters();
        $this->assertCount(5, $this->table->getFilteredRows());
        $this->assertEmpty($this->table->getFilters());
    }

    /**
     * Test getting current filters
     */
    public function test_get_filters(): void
    {
        $this->table->addFilter('process_name', 'nginx', 'contains');
        $this->table->addFilter('cpu_usage', '10', 'greater_than');
        
        $filters = $this->table->getFilters();
        
        $this->assertArrayHasKey('process_name', $filters);
        $this->assertArrayHasKey('cpu_usage', $filters);
        
        $this->assertEquals('nginx', $filters['process_name']['value']);
        $this->assertEquals('contains', $filters['process_name']['operator']);
        
        $this->assertEquals('10', $filters['cpu_usage']['value']);
        $this->assertEquals('greater_than', $filters['cpu_usage']['operator']);
    }

    /**
     * Test virtual scrolling configuration
     */
    public function test_virtual_scrolling_configuration(): void
    {
        // Test enabling virtual scrolling
        $this->table->enableVirtualScrolling(true);
        
        $stats = $this->table->getPerformanceStats();
        $this->assertTrue($stats['virtual_scroll_enabled']);
        
        // Test setting thresholds
        $this->table->setVirtualScrollThreshold(100);
        $this->table->setMaxVisibleRows(50);
        
        $stats = $this->table->getPerformanceStats();
        $this->assertEquals(100, $stats['virtual_scroll_threshold']);
        $this->assertEquals(50, $stats['max_visible_rows']);
    }

    /**
     * Test performance statistics
     */
    public function test_performance_statistics(): void
    {
        // Add some filters and sorting
        $this->table->sortByColumn('port', 'asc');
        $this->table->addFilter('cpu_usage', '10', 'greater_than');
        
        // Select some rows
        $this->table->setRowSelected('1', true);
        $this->table->setRowSelected('2', true);
        
        $stats = $this->table->getPerformanceStats();
        
        $this->assertEquals(5, $stats['total_rows']);
        $this->assertEquals(3, $stats['filtered_rows']); // 3 rows with CPU > 10
        $this->assertEquals(2, $stats['selected_rows']);
        $this->assertTrue($stats['filters_active']);
        $this->assertTrue($stats['sort_active']);
        $this->assertIsInt($stats['virtual_scroll_threshold']);
        $this->assertIsInt($stats['max_visible_rows']);
    }

    /**
     * Test scroll to row functionality
     */
    public function test_scroll_to_row(): void
    {
        $this->table->enableVirtualScrolling(true);
        $this->table->setVirtualScrollThreshold(3); // Force virtual scrolling
        $this->table->setMaxVisibleRows(3); // Set smaller visible count
        
        // Debug the state before scrolling
        $statsBefore = $this->table->getPerformanceStats();
        $this->assertEquals(3, $statsBefore['current_visible_rows']);
        
        $this->table->scrollToRow(2);
        
        $stats = $this->table->getPerformanceStats();
        // With 5 total rows and 3 visible, scrolling to row 2 should set offset to 2
        // But the calculation is: min(2, 5-3) = min(2, 2) = 2
        $this->assertEquals(2, $stats['scroll_offset']);
    }

    /**
     * Test sorting with filtering
     */
    public function test_sorting_with_filtering(): void
    {
        // Apply filter first
        $this->table->addFilter('cpu_usage', '10', 'greater_than');
        
        // Then sort
        $this->table->sortByColumn('cpu_usage', 'desc');
        
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(3, $filteredRows);
        
        // Verify sort order within filtered results
        $cpuValues = array_map(fn($row) => floatval($row->getData()['cpu_usage']), $filteredRows);
        $this->assertEquals([25.7, 15.5, 12.3], $cpuValues);
    }

    /**
     * Test data update with existing sort and filters
     */
    public function test_data_update_preserves_sort_and_filters(): void
    {
        // Set up sort and filter
        $this->table->sortByColumn('port', 'asc');
        $this->table->addFilter('protocol', 'TCP', 'equals');
        
        // Update data
        $newData = [
            ['id' => '6', 'data' => ['port' => '25', 'pid' => '4567', 'protocol' => 'TCP', 'process_name' => 'smtp', 'cpu_usage' => '5.0']],
            ['id' => '7', 'data' => ['port' => '53', 'pid' => '5678', 'protocol' => 'UDP', 'process_name' => 'dns', 'cpu_usage' => '2.1']]
        ];
        
        $this->table->setData(array_merge($this->sampleData, $newData));
        
        // Verify sort and filter are still applied
        $sortInfo = $this->table->getSortInfo();
        $this->assertEquals('port', $sortInfo['column']);
        $this->assertEquals('asc', $sortInfo['direction']);
        
        $filters = $this->table->getFilters();
        $this->assertArrayHasKey('protocol', $filters);
        
        // Verify filtered results (should exclude UDP entry)
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(6, $filteredRows); // 5 original TCP + 1 new TCP
        
        foreach ($filteredRows as $row) {
            $this->assertEquals('TCP', $row->getData()['protocol']);
        }
    }

    /**
     * Test large dataset virtual scrolling activation
     */
    public function test_large_dataset_virtual_scrolling(): void
    {
        // Create large dataset
        $largeData = [];
        for ($i = 1; $i <= 600; $i++) {
            $largeData[] = [
                'id' => (string)$i,
                'data' => [
                    'port' => (string)(8000 + $i),
                    'pid' => (string)(1000 + $i),
                    'protocol' => 'TCP',
                    'process_name' => "process_{$i}",
                    'cpu_usage' => (string)(rand(1, 100) / 10)
                ]
            ];
        }
        
        $this->table->setData($largeData);
        
        $stats = $this->table->getPerformanceStats();
        $this->assertEquals(600, $stats['total_rows']);
        $this->assertTrue($stats['virtual_scroll_enabled']); // Should auto-enable
    }

    /**
     * Test empty and not_empty filters
     */
    public function test_empty_and_not_empty_filters(): void
    {
        // Add data with empty values
        $dataWithEmpties = $this->sampleData;
        $dataWithEmpties[] = ['id' => '6', 'data' => ['port' => '', 'pid' => '6789', 'protocol' => 'TCP', 'process_name' => '', 'cpu_usage' => '0']];
        
        $this->table->setData($dataWithEmpties);
        
        // Test not_empty filter
        $this->table->addFilter('process_name', '', 'not_empty');
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(5, $filteredRows); // Excludes the empty process_name
        
        // Test empty filter
        $this->table->clearFilters();
        $this->table->addFilter('port', '', 'empty');
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(1, $filteredRows); // Only the empty port
    }

    /**
     * Test filter operators with edge cases
     */
    public function test_filter_edge_cases(): void
    {
        // Test case-insensitive contains
        $this->table->addFilter('process_name', 'NGINX', 'contains');
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(2, $filteredRows);
        
        // Test case-insensitive equals
        $this->table->clearFilters();
        $this->table->addFilter('process_name', 'SSHD', 'equals');
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(1, $filteredRows);
        
        // Test numeric comparison with non-numeric values
        $this->table->clearFilters();
        $this->table->addFilter('process_name', '10', 'greater_than');
        $filteredRows = $this->table->getFilteredRows();
        $this->assertCount(0, $filteredRows); // No numeric process names
    }
}