<?php

require_once 'vendor/autoload.php';

use Kingbes\Libui\SDK\LibuiApplication;
use Kingbes\Libui\SDK\LibuiWindow;
use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiTable;

// 获取应用程序实例
$app = LibuiApplication::getInstance();

// 创建主窗口
$window = new LibuiWindow("表格测试", 600, 400, true);

// 创建垂直容器
$vbox = new LibuiVBox();
$vbox->setPadded(true);

// 创建表格
$table = new LibuiTable();

// 添加列
$table->addCheckboxColumn("选择", 0, 1)
      ->addTextColumn("名称", 1)
      ->addTextColumn("值", 2);

// 设置数据
$data = [
    [1, "项目1", "值1"],
    [0, "项目2", "值2"],
    [1, "项目3", "值3"]
];

$table->setData($data);

// 监听复选框改变事件
$table->on('table.checkbox_changed', function($table, $data) {
    error_log("表格复选框改变: " . json_encode($data));
    echo "表格复选框改变: " . json_encode($data) . "\n";
});

// 将表格添加到容器
$vbox->append($table, true);

// 将容器添加到窗口
$window->setChild($vbox);

// 显示窗口
$window->show();

// 运行应用程序
$app->run();