<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Ardillo\Components\TableComponent;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;

echo "TableComponent Example\n";
echo "=====================\n\n";

try {
    // Create a new table component
    $table = new TableComponent();
    
    echo "1. Creating table component...\n";
    
    // Define columns for port data
    $portColumns = [
        ['title' => 'Port', 'key' => 'port', 'type' => 'text'],
        ['title' => 'PID', 'key' => 'pid', 'type' => 'text'],
        ['title' => 'Protocol', 'key' => 'protocol', 'type' => 'text'],
        ['title' => 'Local Address', 'key' => 'localAddress', 'type' => 'text'],
        ['title' => 'Process Name', 'key' => 'processName', 'type' => 'text']
    ];
    
    echo "2. Setting up columns...\n";
    $table->setColumns($portColumns);
    
    // Create sample port data
    $portData = [
        [
            'id' => 'port-8080',
            'data' => [
                'port' => '8080',
                'pid' => '1234',
                'protocol' => 'TCP',
                'localAddress' => '127.0.0.1:8080',
                'processName' => 'node'
            ]
        ],
        [
            'id' => 'port-3000',
            'data' => [
                'port' => '3000',
                'pid' => '5678',
                'protocol' => 'TCP',
                'localAddress' => '0.0.0.0:3000',
                'processName' => 'php'
            ],
            'selected' => true
        ],
        [
            'id' => 'port-80',
            'data' => [
                'port' => '80',
                'pid' => '9012',
                'protocol' => 'TCP',
                'localAddress' => '0.0.0.0:80',
                'processName' => 'nginx'
            ]
        ]
    ];
    
    echo "3. Adding sample data...\n";
    $table->setData($portData);
    
    echo "   - Total rows: " . $table->getRowCount() . "\n";
    echo "   - Selected rows: " . $table->getSelectedRowCount() . "\n";
    
    // Test selection operations
    echo "\n4. Testing selection operations...\n";
    
    echo "   - Selecting port-8080...\n";
    $table->setRowSelected('port-8080', true);
    echo "   - Selected rows: " . $table->getSelectedRowCount() . "\n";
    
    echo "   - Selecting all rows...\n";
    $table->selectAll();
    echo "   - Selected rows: " . $table->getSelectedRowCount() . "\n";
    
    echo "   - Getting selected rows:\n";
    $selectedRows = $table->getSelectedRows();
    foreach ($selectedRows as $row) {
        $data = $row->getData();
        echo "     * Port {$data['port']} (PID: {$data['pid']})\n";
    }
    
    echo "   - Clearing selection...\n";
    $table->clearSelection();
    echo "   - Selected rows: " . $table->getSelectedRowCount() . "\n";
    
    // Test adding individual rows
    echo "\n5. Testing individual row addition...\n";
    
    // Add using PortInfo model
    $portInfo = new PortInfo('9000', '3456', 'TCP', '127.0.0.1:9000', '', 'LISTEN', 'python');
    $table->addRow([
        'id' => $portInfo->getId(),
        'data' => $portInfo->toArray()
    ]);
    
    echo "   - Added port info row\n";
    echo "   - Total rows: " . $table->getRowCount() . "\n";
    
    // Test refresh
    echo "\n6. Testing table refresh...\n";
    $table->refresh();
    echo "   - Table refreshed successfully\n";
    
    // Test checkbox column toggle
    echo "\n7. Testing checkbox column management...\n";
    echo "   - Checkbox column enabled: " . ($table->isCheckboxColumnEnabled() ? 'Yes' : 'No') . "\n";
    
    $table->setCheckboxColumnEnabled(false);
    echo "   - Disabled checkbox column\n";
    echo "   - Checkbox column enabled: " . ($table->isCheckboxColumnEnabled() ? 'Yes' : 'No') . "\n";
    
    $table->setCheckboxColumnEnabled(true);
    echo "   - Re-enabled checkbox column\n";
    echo "   - Checkbox column enabled: " . ($table->isCheckboxColumnEnabled() ? 'Yes' : 'No') . "\n";
    
    echo "\n✓ TableComponent example completed successfully!\n";
    echo "\nThe TableComponent provides:\n";
    echo "- Checkbox selection support\n";
    echo "- Data binding with TableRow models\n";
    echo "- Selection state management\n";
    echo "- Integration with PortInfo and ProcessInfo models\n";
    echo "- Ardillo-php/ext compatibility\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "This is expected if running without a GUI context.\n";
    echo "The component is designed to work in both GUI and test environments.\n";
}