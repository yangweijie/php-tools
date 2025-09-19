<?php

// 直接测试参数分析逻辑
require_once '/Volumes/data/git/php/php-tools/vendor/autoload.php';

// 模拟帮助输出
$helpOutput = '简单CLI程序
用法: php simple_cli.php [选项]

选项:
  -f, --file FILE     输入文件 (必需)
  -n, --name NAME     名称标识 (必需)
  -v, --verbose       详细输出 (可选)
  -h, --help          显示帮助信息';

echo "测试帮助输出:\n";
echo $helpOutput . "\n\n";

// 模拟参数分析逻辑
$cliParameters = [];

// 查找长选项 --param
if (preg_match_all('/--([a-zA-Z0-9\-_]+)/', $helpOutput, $longOpts)) {
    foreach ($longOpts[1] as $opt) {
        // 过滤掉一些常见的非参数选项
        if (!in_array($opt, ['help', 'version', 'usage'])) {
            $cliParameters[$opt] = [
                'name' => $opt,
                'short' => null,
                'description' => 'Auto-detected parameter',
                'required' => false,
                'type' => 'string',
                'default' => ''
            ];
        }
    }
}

// 查找短选项 -p, --param 格式并关联长短选项
$lines = explode("\n", $helpOutput);
foreach ($lines as $line) {
    // 查找 -f, --file FILE 格式的行
    if (preg_match('/^\s*(-[a-zA-Z]),\s*(--?[a-zA-Z0-9\-_]+)/', $line, $matches)) {
        $shortOpt = ltrim($matches[1], '-');
        $longOpt = ltrim($matches[2], '-');

        // 如果长选项已存在，更新其短选项信息
        if (isset($cliParameters[$longOpt])) {
            $cliParameters[$longOpt]['short'] = $shortOpt;
        }
    }
}

// 查找带值的选项 --param=value 或 --param value
foreach ($lines as $line) {
    // 查找 --file FILE 或 -f, --file FILE 格式的行
    if (preg_match('/^\s*(-[a-zA-Z]),\s*(--?[a-zA-Z0-9\-_]+)\s+([A-Z_]+)\s*(.*)$/', $line, $matches) ||
        preg_match('/^\s*(--?[a-zA-Z0-9\-_]+)\s+([A-Z_]+)\s*(.*)$/', $line, $matches)) {

        // 确定参数名和值类型
        if (count($matches) == 5) {
            // 匹配到 -f, --file FILE 格式
            $param = ltrim($matches[2], '-');
            $valueType = $matches[3];
        } else if (count($matches) == 4) {
            // 匹配到 --file FILE 格式
            $param = ltrim($matches[1], '-');
            $valueType = $matches[2];
        } else {
            continue;
        }

        if (isset($cliParameters[$param])) {
            // 根据值类型推测参数类型
            switch (strtoupper($valueType)) {
                case 'NUMBER':
                case 'INT':
                case 'INTEGER':
                    $cliParameters[$param]['type'] = 'integer';
                    break;
                case 'FILE':
                case 'PATH':
                    $cliParameters[$param]['type'] = 'file';
                    break;
            }
        }
    }
}

// 查找帮助文本中的参数描述
foreach ($lines as $line) {
    // 查找 -f, --file FILE          Input file path (required) 格式的行
    if (preg_match('/^\s*(-[a-zA-Z]),\s*(--?[a-zA-Z0-9\-_]+)\s+[A-Z_]*\s*(.+)$/', $line, $matches)) {
        $shortOpt = ltrim($matches[1], '-');
        $longOpt = ltrim($matches[2], '-');
        $description = trim($matches[3]);

        // 更新长选项描述
        if (isset($cliParameters[$longOpt])) {
            $cliParameters[$longOpt]['description'] = $description;
        }
        // 更新短选项描述
        if (isset($cliParameters[$shortOpt])) {
            $cliParameters[$shortOpt]['description'] = $description;
        }
    }
}

echo "解析到的参数:\n";
print_r($cliParameters);
