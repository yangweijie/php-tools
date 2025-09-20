#!/usr/bin/env php
<?php

/**
 * 检查并替换 macOS ARM 架构下的 libui.dylib 文件
 * 
 * 该脚本用于检查系统是否为 macOS ARM 架构，
 * 并验证 vendor/kingbes/libui/lib/macos/libui.dylib 文件的 MD5 值。
 * 如果 MD5 不匹配，则替换为正确的文件。
 */

echo "检查 libui.dylib 文件...\n";

// 检查系统架构
$arch = shell_exec('uname -m')? trim(shell_exec('uname -m')):'';
$isMacOS = PHP_OS_FAMILY === 'Darwin';
$isARM = $arch === 'arm64';

if (!$isMacOS || !$isARM) {
    echo "当前系统不是 macOS ARM 架构，无需处理。\n";
    exit(0);
}

echo "检测到 macOS ARM 架构。\n";

// 定义文件路径
$libuiPath = __DIR__ . '/../vendor/kingbes/libui/lib/macos/libui.dylib';

// 检查文件是否存在
if (!file_exists($libuiPath)) {
    echo "libui.dylib 文件不存在: $libuiPath\n";
    exit(1);
}

// 计算当前文件的 MD5 值
$currentMd5 = md5_file($libuiPath);
$expectedMd5 = '46722841c0b859c10745df15e647be1f';

echo "当前文件 MD5: $currentMd5\n";
echo "期望文件 MD5: $expectedMd5\n";

// 检查 MD5 是否匹配
if ($currentMd5 === $expectedMd5) {
    echo "MD5 值匹配，无需替换。\n";
    exit(0);
}

echo "MD5 值不匹配，需要替换文件。\n";

// 复制正确的文件
$sourcePath = __DIR__ . '/../kingbes/libui/lib/macos/libui.dylib';

if (!file_exists($sourcePath)) {
    echo "源文件不存在: $sourcePath\n";
    exit(1);
}

// 备份原文件
$backupPath = $libuiPath . '.backup';
if (!copy($libuiPath, $backupPath)) {
    echo "无法备份原文件。\n";
    exit(1);
}

echo "已备份原文件到: $backupPath\n";

// 替换文件
if (!copy($sourcePath, $libuiPath)) {
    echo "无法替换文件。\n";
    exit(1);
}

// 验证替换后的 MD5
$newMd5 = md5_file($libuiPath);
if ($newMd5 !== $expectedMd5) {
    echo "替换后文件 MD5 仍然不匹配，回滚备份。\n";
    copy($backupPath, $libuiPath);
    exit(1);
}

echo "文件替换成功，当前 MD5: $newMd5\n";
unlink($backupPath);
echo "备份文件已删除。\n";

exit(0);