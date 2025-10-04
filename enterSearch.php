<?php

require_once __DIR__.'/vendor/autoload.php';

use Kingbes\Libui\SDK\{LibuiApplication, LibuiLabel, LibuiTable, LibuiVBox, SearchBuilder};


$app = LibuiApplication::getInstance()->init();

// 示例1: 横向搜索组件
$window1 = $app->createWindow("横向搜索示例", 600, 200);

$horizontalSearch = SearchBuilder::create()
    ->horizontal()
    ->placeholder("输入关键词搜索...")
    ->buttonText("🔍 立即搜索")
    ->showKeyboardHints()
    ->onSearch(function ($text, $component) {
        echo "搜索: $text\n";
        // 执行搜索逻辑
        performActualSearch($text);
    })
    ->onTextChanged(function ($text, $component) {
        echo "输入变化: $text\n";
        // 实时搜索建议
        if (strlen($text) >= 2) {
            showSearchSuggestions($text);
        }
    })
    ->build();

$window1->setChild($horizontalSearch);
$window1->center()->show();

// 示例2: 纵向搜索组件
$window2 = $app->createWindow("纵向搜索示例", 300, 300);

$verticalSearch = SearchBuilder::create()
    ->vertical()
    ->placeholder("搜索文件...")
    ->buttonText("📁 浏览文件")
    ->onSearch(function ($text, $component) {
        echo "文件搜索: $text\n";
        searchFiles($text);
    })
    ->build();

$window2->setChild($verticalSearch);
$window2->topRight()->show();

// 示例3: 集成到复杂界面
$window3 = $app->createWindow("搜索应用", 800, 600);

$mainVBox = new LibuiVBox();
$mainVBox->setPadded(true);

// 顶部搜索栏
$topSearch = SearchBuilder::create()
    ->horizontal()
    ->placeholder("全局搜索...")
    ->onSearch(function ($text) {
        globalSearch($text);
    })
    ->build();

// 结果显示区域
$resultsTable = new LibuiTable();
$resultsTable->addTextColumn("标题", 0)
    ->addTextColumn("描述", 1)
    ->addTextColumn("时间", 2);

// 状态栏
$statusLabel = new LibuiLabel("准备搜索");

$mainVBox->append($topSearch, false)
    ->append($resultsTable, true)
    ->append($statusLabel, false);

// 搜索事件处理
$topSearch->onSearch(function ($text) use ($resultsTable, $statusLabel) {
    $statusLabel->setText("搜索中: $text");

    // 模拟搜索结果
    $results = [
        ["结果1", "这是第一个搜索结果", "2024-01-01"],
        ["结果2", "这是第二个搜索结果", "2024-01-02"],
    ];

    $resultsTable->setData($results);
    $statusLabel->setText("找到 " . count($results) . " 个结果");
});

$window3->setChild($mainVBox);
$window3->center()->show();

// 辅助函数
function performActualSearch(string $query): void
{
    // 实际搜索逻辑
    echo "执行搜索: $query\n";
}

function showSearchSuggestions(string $partial): void
{
    // 显示搜索建议
    echo "搜索建议: $partial\n";
}

function searchFiles(string $pattern): void
{
    // 文件搜索逻辑
    echo "搜索文件: $pattern\n";
}

function globalSearch(string $text): void
{
    // 全局搜索逻辑
    echo "全局搜索: $text\n";
}

$app->run();
