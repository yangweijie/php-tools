<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Base;
use Kingbes\Libui\Table;
use Kingbes\Libui\TableValueType;

/**
 * 表格组件
 */
class LibuiTable extends LibuiComponent
{
    private array $columns = [];
    private array $data = [];
    private array $columnTypes = [];
    private int $initialColumnCount = 0;
    private int $initialRowCount = 0;
    private ?CData $model = null;
    private ?CData $modelHandler = null;

    public function __construct() {
        parent::__construct();
        // 不在构造函数中创建handle，而是在需要时延迟创建
        $this->handle = null;
    }
    
    public function getHandle(): CData {
        if ($this->handle === null) {
            $this->handle = $this->createHandle();
        }
        return $this->handle;
    }

    protected function createHandle(): CData {
        // 初始化行列计数
        $this->initialColumnCount = 0;
        $this->initialRowCount = 0;
        
        // 计算实际的行列数
        if (!empty($this->data)) {
            $this->initialRowCount = count($this->data);
            foreach ($this->data as $row) {
                $this->initialColumnCount = max($this->initialColumnCount, count($row));
            }
        }
        
        // 确保列数至少等于已定义列的最大索引+1
        foreach ($this->columns as $column) {
            $this->initialColumnCount = max($this->initialColumnCount, $column['index'] + 1);
        }
        
        // 确保至少有1列，行数可以为0（空表格）
        $this->initialColumnCount = max(1, $this->initialColumnCount);
        // 不再强制至少1行，允许0行的空表格
        $this->initialRowCount = $this->initialRowCount;

        // 确定列类型 - 如果有任何checkbox列，使用Int类型，否则使用String类型
        $columnType = TableValueType::String;
        foreach ($this->columnTypes as $type) {
            if ($type === 'checkbox') {
                $columnType = TableValueType::Int;
                break;
            }
        }

        // 创建表格模型处理程序
        $this->modelHandler = Table::modelHandler(
            $this->initialColumnCount, // 列数
            $columnType, // 列类型
            $this->initialRowCount, // 行数
            function ($handler, $row, $column) {
                try {
                    // 确保行和列存在
                    if (!isset($this->data[$row][$column])) {
                        // 根据列类型返回默认值
                        if (isset($this->columnTypes[$column]) && $this->columnTypes[$column] === 'checkbox') {
                            return Table::createValueInt(0);
                        } else {
                            return Table::createValueStr('');
                        }
                    }
                    
                    // 根据列类型返回相应的值
                    if (isset($this->columnTypes[$column]) && $this->columnTypes[$column] === 'checkbox') {
                        $value = $this->data[$row][$column] ?? 0;
                        return Table::createValueInt((int)$value);
                    } else {
                        $value = $this->data[$row][$column] ?? '';
                        return Table::createValueStr((string)$value);
                    }
                } catch (\Throwable $e) {
                    // 在回调函数中不能抛出异常，返回默认值
                    if (isset($this->columnTypes[$column]) && $this->columnTypes[$column] === 'checkbox') {
                        return Table::createValueInt(0);
                    } else {
                        return Table::createValueStr('');
                    }
                }
            },
            function ($handler, $row, $column, $value) {
                try {
                    error_log("SetCellValue called: row=$row, column=$column");
                    // 设置单元格值
                    if (!isset($this->data[$row])) {
                        $this->data[$row] = [];
                    }
                    
                    $oldValue = null;
                    if (isset($this->data[$row][$column])) {
                        $oldValue = $this->data[$row][$column];
                    }
                    
                    if (isset($this->columnTypes[$column]) && $this->columnTypes[$column] === 'checkbox') {
                        $this->data[$row][$column] = Table::valueInt($value);
                        error_log("Set checkbox value: row=$row, column=$column, old=$oldValue, new=".$this->data[$row][$column]);
                    } else {
                        $this->data[$row][$column] = Table::valueStr($value);
                    }
                    
                    // 如果是复选框列且值发生了变化，触发事件
                    if (isset($this->columnTypes[$column]) && $this->columnTypes[$column] === 'checkbox' && $oldValue !== $this->data[$row][$column]) {
                        error_log("Emitting checkbox changed event: row=$row, column=$column, old=$oldValue, new=".$this->data[$row][$column]);
                        $this->emit('table.checkbox_changed', [
                            'row' => $row,
                            'column' => $column,
                            'old_value' => $oldValue,
                            'new_value' => $this->data[$row][$column]
                        ]);
                    }
                } catch (\Throwable $e) {
                    error_log("SetCellValue error: " . $e->getMessage());
                    // 在回调函数中不能抛出异常，静默处理
                }
            }
        );

        // 创建表格模型
        $this->model = Table::createModel($this->modelHandler);

        // 创建表格
        $table = Table::create($this->model, -1);

        // 添加已定义的列
        foreach ($this->columns as $column) {
            switch ($column['type']) {
                case 'text':
                    Table::appendTextColumn($table, $column['name'], $column['index'], false, false);
                    break;
                case 'button':
                    Table::appendButtonColumn($table, $column['name'], $column['index'], $column['clickable']);
                    break;
                case 'checkbox':
                    // 直接传递editable参数，-1为可编辑，-2为不可编辑
                    Table::appendCheckboxColumn($table, $column['name'], $column['index'], $column['editable']);
                    break;
            }
        }

        return $table;
    }

    public function addTextColumn(string $name, int $textColumn): self {
        $this->columns[] = ['name' => $name, 'type' => 'text', 'index' => $textColumn];
        $this->columnTypes[$textColumn] = 'text';
        $this->initialColumnCount = max($this->initialColumnCount, $textColumn + 1);
        return $this;
    }

    public function addButtonColumn(string $name, int $buttonColumn, int $clickableColumn): self {
        $this->columns[] = ['name' => $name, 'type' => 'button', 'index' => $buttonColumn, 'clickable' => $clickableColumn];
        $this->columnTypes[$buttonColumn] = 'button';
        $this->initialColumnCount = max($this->initialColumnCount, $buttonColumn + 1);
        return $this;
    }

    public function addCheckboxColumn(string $name, int $checkboxColumn, int $editableColumn): self {
        $this->columns[] = ['name' => $name, 'type' => 'checkbox', 'index' => $checkboxColumn, 'editable' => $editableColumn];
        $this->columnTypes[$checkboxColumn] = 'checkbox';
        $this->initialColumnCount = max($this->initialColumnCount, $checkboxColumn + 1);
        return $this;
    }

    public function setData(array $data): self {
        // 保存旧数据
        $oldData = $this->data;
        $oldRowCount = count($oldData);
        $newRowCount = count($data);
        
        // 更新数据
        $this->data = $data;
        $this->initialRowCount = $newRowCount; // 直接使用实际行数
        
        // 如果模型已经创建，尝试更新而不是重新创建
        if ($this->model !== null) {
            // 通知模型行数变化
            if ($newRowCount > $oldRowCount) {
                // 添加新行
                for ($i = $oldRowCount; $i < $newRowCount; $i++) {
                    Table::modelRowInserted($this->model, $i);
                }
            } else if ($newRowCount < $oldRowCount) {
                // 删除多余行
                for ($i = $oldRowCount - 1; $i >= $newRowCount; $i--) {
                    Table::modelRowDeleted($this->model, $i);
                }
            } else {
                // 更新现有行
                for ($i = 0; $i < $newRowCount; $i++) {
                    Table::modelRowChanged($this->model, $i);
                }
            }
        } else {
            // 如果没有模型，重新创建handle
            $this->handle = $this->createHandle();
        }
        
        // 发送数据更新事件
        $this->emit('table.data_updated', ['row_count' => $newRowCount]);
        
        return $this;
    }

    public function addRow(array $row): self {
        $this->data[] = $row;
        $this->initialRowCount = count($this->data);
        
        // 如果模型已经创建，通知添加新行
        if ($this->model !== null) {
            $rowIndex = count($this->data) - 1;
            Table::modelRowInserted($this->model, $rowIndex);
        } else {
            // 否则重新创建handle
            $this->handle = $this->createHandle();
        }
        
        return $this;
    }

    public function removeRow(int $index): self {
        if (isset($this->data[$index])) {
            array_splice($this->data, $index, 1);
            $this->initialRowCount = count($this->data);
            
            // 如果模型已经创建，通知删除行
            if ($this->model !== null) {
                Table::modelRowDeleted($this->model, $index);
            } else {
                // 否则重新创建handle
                $this->handle = $this->createHandle();
            }
        }
        return $this;
    }

    public function getSelection(): int {
        // 获取当前选择的行号
        // 注意：selectionRow函数可能不存在，需要检查libui文档
        try {
            return Table::selectionRow($this->getHandle());
        } catch (\Throwable $e) {
            // 如果函数不存在，返回-1表示没有选择
            return -1;
        }
    }

    public function getSelections(): array {
        // 获取所有选择的行号
        try {
            $selection = Table::getSelection($this->getHandle());
            if ($selection === null) {
                return [];
            }
            
            $selectedRows = [];
            for ($i = 0; $i < $selection->NumRows; $i++) {
                $selectedRows[] = $selection->Rows[$i];
            }
            
            Table::freeSelection($selection);
            return $selectedRows;
        } catch (\Throwable $e) {
            // 如果函数不存在，返回空数组
            return [];
        }
    }

    public function onSelectionChanged(callable $callback): self {
        Table::onSelectionChanged($this->getHandle(), function($table) use ($callback) {
            // 延迟获取选择信息，确保在事件触发时获取最新状态
            $selection = -1;
            $selections = [];
            try {
                $selection = $this->getSelection();
                $selections = $this->getSelections();
            } catch (\Throwable $e) {
                // 静默处理异常
            }
            $callback($selection, $selections, $this);
            $this->emit('table.selection_changed', ['selected_row' => $selection, 'selected_rows' => $selections]);
        });
        return $this;
    }
}
