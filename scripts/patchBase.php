<?php
echo "检查 Base 文件...\n";
$checkFile  = __DIR__ . '/../vendor/kingbes/libui/src/Base.php';
if (!file_exists($checkFile)) {
    echo "Base 文件不存在，libui 还未安装\n";
    exit(0);
}

$file = file_get_contents($checkFile);
if(str_contains($file, 'getLibFilePath')){
    echo "Base 已修复\n";
    exit(0);
}

file_put_contents($checkFile, file_get_contents(__DIR__ . '../kingbes/libui/patchBase.php'), FILE_APPEND);

echo "Base 已修复\n";
    exit(0);