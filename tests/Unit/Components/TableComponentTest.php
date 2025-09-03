<?php

use App\Ardillo\Components\TableComponent;
use App\Ardillo\Models\TableRow;
use App\Ardillo\Exceptions\TableOperationException;

describe('TableComponent', function () {
    beforeEach(function () {
        $this->component = new TableComponent();
    });

    describe('initialization', function () {
        it('should implement TableInterface', function () {
            expect($this->component)->toBeInstanceOf(App\Ardillo\Components\TableInterface::class);
        });

        it('should extend BaseComponent', function () {
            expect($this->component)->toBeInstanceOf(App\Ardillo\Components\BaseComponent::class);
        });

        it('should have checkbox column enabled by default', function () {
            expect($this->component->isCheckboxColumnEnabled())->toBe(true);
        });
    });

    describe('column management', function () {
        it('should set columns correctly', function () {
            $columns = [
                ['title' => 'Port', 'key' => 'port', 'type' => 'text'],
                ['title' => 'PID', 'key' => 'pid', 'type' => 'text'],
                ['title' => 'Process', 'key' => 'processName', 'type' => 'text']
            ];

            expect(fn() => $this->component->setColumns($columns))->not->toThrow(\Exception::class);
        });

        it('should handle empty columns array', function () {
            expect(fn() => $this->component->setColumns([]))->not->toThrow(\Exception::class);
        });
    });

    describe('data management', function () {
        beforeEach(function () {
            $this->columns = [
                ['title' => 'Port', 'key' => 'port', 'type' => 'text'],
                ['title' => 'PID', 'key' => 'pid', 'type' => 'text'],
                ['title' => 'Process', 'key' => 'processName', 'type' => 'text']
            ];
            $this->component->setColumns($this->columns);
        });

        it('should add single row correctly', function () {
            $rowData = [
                'id' => 'test-1',
                'data' => [
                    'port' => '8080',
                    'pid' => '1234',
                    'processName' => 'node'
                ]
            ];

            expect(fn() => $this->component->addRow($rowData))->not->toThrow(\Exception::class);
            expect($this->component->getRowCount())->toBe(1);
        });

        it('should add multiple rows via setData', function () {
            $data = [
                [
                    'id' => 'test-1',
                    'data' => [
                        'port' => '8080',
                        'pid' => '1234',
                        'processName' => 'node'
                    ]
                ],
                [
                    'id' => 'test-2',
                    'data' => [
                        'port' => '3000',
                        'pid' => '5678',
                        'processName' => 'php'
                    ]
                ]
            ];

            expect(fn() => $this->component->setData($data))->not->toThrow(\Exception::class);
            expect($this->component->getRowCount())->toBe(2);
        });

        it('should handle TableRow objects', function () {
            $tableRow = new TableRow('test-1', [
                'port' => '8080',
                'pid' => '1234',
                'processName' => 'node'
            ]);

            expect(fn() => $this->component->addRow($tableRow))->not->toThrow(\Exception::class);
            expect($this->component->getRowCount())->toBe(1);
        });

        it('should clear table correctly', function () {
            $rowData = [
                'id' => 'test-1',
                'data' => [
                    'port' => '8080',
                    'pid' => '1234',
                    'processName' => 'node'
                ]
            ];

            $this->component->addRow($rowData);
            expect($this->component->getRowCount())->toBe(1);

            $this->component->clearTable();
            expect($this->component->getRowCount())->toBe(0);
        });
    });

    describe('selection management', function () {
        beforeEach(function () {
            $this->columns = [
                ['title' => 'Port', 'key' => 'port', 'type' => 'text'],
                ['title' => 'PID', 'key' => 'pid', 'type' => 'text']
            ];
            $this->component->setColumns($this->columns);

            // Add test data
            $this->testData = [
                [
                    'id' => 'row-1',
                    'data' => ['port' => '8080', 'pid' => '1234']
                ],
                [
                    'id' => 'row-2',
                    'data' => ['port' => '3000', 'pid' => '5678']
                ],
                [
                    'id' => 'row-3',
                    'data' => ['port' => '9000', 'pid' => '9012']
                ]
            ];
            $this->component->setData($this->testData);
        });

        it('should have no selections initially', function () {
            expect($this->component->getSelectedRowCount())->toBe(0);
            expect($this->component->getSelectedRows())->toBe([]);
        });

        it('should select individual rows', function () {
            $this->component->setRowSelected('row-1', true);
            
            expect($this->component->getSelectedRowCount())->toBe(1);
            expect($this->component->isRowSelected('row-1'))->toBe(true);
            expect($this->component->isRowSelected('row-2'))->toBe(false);
        });

        it('should select all rows', function () {
            $this->component->selectAll();
            
            expect($this->component->getSelectedRowCount())->toBe(3);
            expect($this->component->isRowSelected('row-1'))->toBe(true);
            expect($this->component->isRowSelected('row-2'))->toBe(true);
            expect($this->component->isRowSelected('row-3'))->toBe(true);
        });

        it('should clear all selections', function () {
            $this->component->selectAll();
            expect($this->component->getSelectedRowCount())->toBe(3);
            
            $this->component->clearSelection();
            expect($this->component->getSelectedRowCount())->toBe(0);
        });

        it('should toggle row selection', function () {
            expect($this->component->isRowSelected('row-1'))->toBe(false);
            
            $this->component->toggleRowSelection(0); // First row
            expect($this->component->isRowSelected('row-1'))->toBe(true);
            
            $this->component->toggleRowSelection(0); // Toggle again
            expect($this->component->isRowSelected('row-1'))->toBe(false);
        });

        it('should return selected rows correctly', function () {
            $this->component->setRowSelected('row-1', true);
            $this->component->setRowSelected('row-3', true);
            
            $selectedRows = $this->component->getSelectedRows();
            expect(count($selectedRows))->toBe(2);
            
            $selectedIds = array_map(fn($row) => $row->getId(), $selectedRows);
            expect($selectedIds)->toContain('row-1');
            expect($selectedIds)->toContain('row-3');
            expect($selectedIds)->not->toContain('row-2');
        });
    });

    describe('checkbox column management', function () {
        it('should enable/disable checkbox column', function () {
            expect($this->component->isCheckboxColumnEnabled())->toBe(true);
            
            $this->component->setCheckboxColumnEnabled(false);
            expect($this->component->isCheckboxColumnEnabled())->toBe(false);
            
            $this->component->setCheckboxColumnEnabled(true);
            expect($this->component->isCheckboxColumnEnabled())->toBe(true);
        });
    });

    describe('refresh functionality', function () {
        it('should refresh table without errors', function () {
            $columns = [
                ['title' => 'Port', 'key' => 'port', 'type' => 'text'],
                ['title' => 'PID', 'key' => 'pid', 'type' => 'text']
            ];
            $this->component->setColumns($columns);

            $data = [
                [
                    'id' => 'test-1',
                    'data' => ['port' => '8080', 'pid' => '1234']
                ]
            ];
            $this->component->setData($data);

            expect(fn() => $this->component->refresh())->not->toThrow(\Exception::class);
        });
    });

    describe('error handling', function () {
        it('should handle invalid row indices gracefully', function () {
            expect(fn() => $this->component->toggleRowSelection(999))->not->toThrow(\Exception::class);
        });

        it('should handle invalid row IDs gracefully', function () {
            expect(fn() => $this->component->setRowSelected('invalid-id', true))->not->toThrow(\Exception::class);
            expect($this->component->isRowSelected('invalid-id'))->toBe(false);
        });
    });

    describe('data retrieval', function () {
        beforeEach(function () {
            $this->columns = [
                ['title' => 'Port', 'key' => 'port', 'type' => 'text'],
                ['title' => 'PID', 'key' => 'pid', 'type' => 'text']
            ];
            $this->component->setColumns($this->columns);

            $this->testData = [
                [
                    'id' => 'row-1',
                    'data' => ['port' => '8080', 'pid' => '1234'],
                    'selected' => true
                ],
                [
                    'id' => 'row-2',
                    'data' => ['port' => '3000', 'pid' => '5678'],
                    'selected' => false
                ]
            ];
            $this->component->setData($this->testData);
        });

        it('should return all rows', function () {
            $allRows = $this->component->getAllRows();
            expect(count($allRows))->toBe(2);
            expect($allRows[0])->toBeInstanceOf(TableRow::class);
        });

        it('should preserve selection state when adding data', function () {
            expect($this->component->getSelectedRowCount())->toBe(1);
            expect($this->component->isRowSelected('row-1'))->toBe(true);
            expect($this->component->isRowSelected('row-2'))->toBe(false);
        });
    });
});