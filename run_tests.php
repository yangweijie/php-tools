#!/usr/bin/env php
<?php

echo "=== PHP HTTP压测工具 - 测试报告 ===\n\n";

// 运行单元测试
echo "正在运行单元测试...\n";
exec('vendor\\bin\\pest tests\\Unit --testdox', $unitOutput, $unitReturn);

echo "正在运行功能测试...\n";
exec('vendor\\bin\\pest tests\\Feature --testdox', $featureOutput, $featureReturn);

echo "正在运行架构测试...\n";  
exec('vendor\\bin\\pest tests\\Arch --testdox', $archOutput, $archReturn);

// 显示结果
echo "\n=== 单元测试结果 ===\n";
echo implode("\n", $unitOutput) . "\n";

echo "\n=== 功能测试结果 ===\n";
echo implode("\n", $featureOutput) . "\n";

echo "\n=== 架构测试结果 ===\n";
echo implode("\n", $archOutput) . "\n";

// 总体结果
$allPassed = ($unitReturn === 0 && $featureReturn === 0 && $archReturn === 0);
echo "\n=== 总体结果 ===\n";
echo $allPassed ? "✅ 所有测试通过!" : "❌ 部分测试失败";
echo "\n单元测试: " . ($unitReturn === 0 ? "✅ 通过" : "❌ 失败") . "\n";
echo "功能测试: " . ($featureReturn === 0 ? "✅ 通过" : "❌ 失败") . "\n";
echo "架构测试: " . ($archReturn === 0 ? "✅ 通过" : "❌ 失败") . "\n";

echo "\n测试完成!\n";