<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kingbes\Libui\App;
use Kingbes\Libui\Window;
use Kingbes\Libui\Control;
use Kingbes\Libui\Box;
use Kingbes\Libui\Button;
use Kingbes\Libui\Table;
use Kingbes\Libui\TableValueType;

// 初始化应用
App::init();

// 创建窗口
$window = Window::create("表格测试", 800, 600, 1);
Window::setMargined($window, true);

// 窗口关闭事件
Window::onClosing($window, function ($window) {
    App::quit();
    return 1;
});

// 创建垂直布局
$box = Box::newVerticalBox();
Box::setPadded($box, true);
Window::setChild($window, $box);

// 创建测试数据
$data = [
    ['id' => 1, 'name' => '张三', 'age' => 25, 'selected' => 0],
    ['id' => 2, 'name' => '李四', 'age' => 30, 'selected' => 0],
    ['id' => 3, 'name' => '王五', 'age' => 28, 'selected' => 0],
];

// 创建表格模型处理程序
$handler = \FFI::addr(Table::modelHandler());

// 列数回调
$numColumns = function ($handler, $model) {
    return 4; // ID, 选择框, 姓名, 年龄
};

// 行数回调
$numRows = function ($handler, $model) use ($data) {
    return count($data);
};

// 列类型回调
$columnType = function ($handler, $model, $column) {
    if ($column == 0) { // ID
        return TableValueType::Int->value;
    } elseif ($column == 1) { // 选择框
        return TableValueType::Int->value;
    } else { // 姓名, 年龄
        return TableValueType::String->value;
    }
};

// 单元格值回调
$cellValue = function ($handler, $model, $row, $column) use ($data) {
    if ($row >= count($data)) {
        return Table::createValueStr("");
    }
    
    switch ($column) {
        case 0: // ID
            return Table::createValueInt($data[$row]['id']);
        case 1: // 选择框
            return Table::createValueInt($data[$row]['selected']);
        case 2: // 姓名
            return Table::createValueStr($data[$row]['name']);
        case 3: // 年龄
            return Table::createValueStr($data[$row]['age']);
        default:
            return Table::createValueStr("");
    }
};

// 设置单元格值回调
$setCellValue = function ($handler, $model, $row, $column, $value) use (&$data) {
    if ($column == 1 && $row < count($data)) { // 选择框列
        $data[$row]['selected'] = Table::valueInt($value);
        return 1;
    }
    return 0;
};

// 设置回调函数
$handler->NumColumns = $numColumns;
$handler->ColumnType = $columnType;
$handler->NumRows = $numRows;
$handler->CellValue = $cellValue;
$handler->SetCellValue = $setCellValue;

// 创建表格模型
$model = Table::createModel($handler);

// 添加列
$textParams = \Kingbes\Libui\Base::ffi()->new("uiTableTextColumnOptionalParams");
Table::appendTextColumn($model, "ID", 0, -1, $textParams);
Table::appendCheckboxColumn($model, "选择", 1);
Table::appendTextColumn($model, "姓名", 2, -1, $textParams);
Table::appendTextColumn($model, "年龄", 3, -1, $textParams);

// 创建表格参数
$params = \Kingbes\Libui\Base::ffi()->new("uiTableParams");
$params->Model = $model;
$params->RowBackgroundColorModelColumn = -1;

// 创建表格
$table = Table::create($params);
Box::append($box, $table, true);

// 按钮：显示选中行
$btn = Button::create("显示选中行");
Button::onClicked($btn, function () use ($data) {
    $selected = [];
    foreach ($data as $row) {
        if ($row['selected'] == 1) {
            $selected[] = $row['name'];
        }
    }
    
    if (empty($selected)) {
        echo "没有选中任何行\n";
    } else {
        echo "选中的行: " . implode(", ", $selected) . "\n";
    }
});
Box::append($box, $btn, false);

// 显示窗口
Control::show($window);

// 主循环
App::main();