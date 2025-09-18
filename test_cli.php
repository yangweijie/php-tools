#!/usr/bin/env php
<?php

/**
 * 测试CLI程序，用于验证智能打包工具的参数分析功能
 */

// 获取命令行参数
$options = getopt("f:o:v::h", ["file:", "output:", "verbose::", "help"]);

// 检查是否有帮助参数
if (isset($options['h']) || isset($options['help'])) {
    showHelp();
    exit(0);
}

// 显示帮助信息
function showHelp() {
    echo "测试CLI程序\n";
    echo "用法: php test_cli.php [选项]\n\n";
    echo "选项:\n";
    echo "  -f, --file FILE        输入文件路径\n";
    echo "  -o, --output DIR       输出目录\n";
    echo "  -v, --verbose[=LEVEL]  详细级别 (可选)\n";
    echo "  -h, --help             显示此帮助信息\n";
    echo "\n";
    echo "示例:\n";
    echo "  php test_cli.php -f input.txt -o /tmp\n";
    echo "  php test_cli.php --file input.txt --output /tmp --verbose=2\n";
}

// 如果没有参数，显示帮助
if (empty($options)) {
    showHelp();
    exit(1);
}

// 处理参数
$file = $options['f'] ?? $options['file'] ?? null;
$output = $options['o'] ?? $options['output'] ?? null;
$verbose = $options['v'] ?? $options['verbose'] ?? false;

// 输出参数信息
echo "参数分析结果:\n";
echo "  文件: " . ($file ?? "未指定") . "\n";
echo "  输出目录: " . ($output ?? "未指定") . "\n";
echo "  详细级别: " . ($verbose === false ? "未启用" : ($verbose === null ? "启用" : $verbose)) . "\n";

// 模拟处理过程
if ($file && $output) {
    echo "正在处理文件 '$file' 并输出到 '$output'...\n";
    // 模拟一些工作
    sleep(1);
    echo "处理完成!\n";
} else {
    echo "缺少必要的参数。\n";
    exit(1);
}