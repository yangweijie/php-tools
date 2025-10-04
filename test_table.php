<?php

require_once __DIR__ . '/vendor/autoload.php';

use Kingbes\Libui\SDK\LibuiApplication;
use Kingbes\Libui\SDK\LibuiWindow;
use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiTable;

// 获取应用实例
$app = LibuiApplication::getInstance();

// 创建窗口
$window = new LibuiWindow("表格测试", 800, 600, true);

// 创建垂直容器
$vbox = new LibuiVBox();
$vbox->setPadded(true);

// 创建表格
$table = new LibuiTable();

// 添加列
$table->addCheckboxColumn("选择", 0, 0)
      ->addTextColumn("名称", 1)
      ->addTextColumn("值", 2);

// 设置数据
$data = [
    [0, "项目1", "值1"],
    [1, "项目2", "值2"],
    [0, "项目3", "值3"]
];

$table->setData($data);

// 设置选择改变事件
$table->onSelectionChanged(function($selectedRow, $selectedRows, $tableComponent) {
    error_log("选择改变: 行=$selectedRow, 多选=" . json_encode($selectedRows));
});

// 将表格添加到容器
$vbox->append($table->getHandle(), true);

// 将容器设置为窗口内容
$window->setChild($vbox);

// 显示窗口
$window->show();

// 运行应用
$app->run();