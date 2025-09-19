#!/usr/bin/env php
<?php

// 简单的CLI程序用于测试参数分析
$options = getopt("f:n:v::h", [
    "file:",
    "name:",
    "verbose::",
    "help"
]);

// 检查帮助参数
if (isset($options['h']) || isset($options['help'])) {
    echo "简单CLI程序\n";
    echo "用法: php simple_cli.php [选项]\n\n";
    echo "选项:\n";
    echo "  -f, --file FILE     输入文件 (必需)\n";
    echo "  -n, --name NAME     名称标识 (必需)\n";
    echo "  -v, --verbose       详细输出 (可选)\n";
    echo "  -h, --help          显示帮助信息\n";
    exit(0);
}

// 获取参数
$file = $options['f'] ?? $options['file'] ?? null;
$name = $options['n'] ?? $options['name'] ?? null;
$verbose = isset($options['v']) || isset($options['verbose']);

// 验证必需参数
if (!$file || !$name) {
    echo "错误: 必须提供 -f/--file 和 -n/--name 参数\n";
    echo "使用 -h 或 --help 查看帮助信息\n";
    exit(1);
}

// 执行程序逻辑
echo "处理文件: $file\n";
echo "名称标识: $name\n";
if ($verbose) {
    echo "详细模式已启用\n";
}
echo "处理完成!\n";
