<?php

// 简单测试lsof命令是否能正确执行
echo "Testing lsof command for port 8080:\n";

// 执行lsof命令
$command = "lsof -i :8080 -n -P 2>/dev/null";
echo "Executing: $command\n";

$output = [];
exec($command, $output);

echo "Command returned " . count($output) . " lines:\n";
foreach ($output as $line) {
    echo $line . "\n";
}

// 检查是否有进程在监听8080端口
if (count($output) > 1) {
    echo "\nFound processes listening on port 8080\n";
    
    // 跳过标题行，处理数据行
    for ($i = 1; $i < count($output); $i++) {
        $line = trim($output[$i]);
        if (!empty($line)) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 5) {
                $processName = $parts[0];
                $pid = $parts[1];
                $user = $parts[2];
                $localAddr = isset($parts[8]) ? $parts[8] : (isset($parts[4]) ? $parts[4] : '');
                
                echo "Process: $processName, PID: $pid, User: $user, Address: $localAddr\n";
                
                // 获取进程的完整命令
                $cmdOutput = [];
                $cmdCmd = "ps -p $pid -o command= 2>/dev/null";
                exec($cmdCmd, $cmdOutput);
                if (!empty($cmdOutput)) {
                    echo "  Command: " . trim($cmdOutput[0]) . "\n";
                }
            }
        }
    }
} else {
    echo "\nNo processes found listening on port 8080\n";
}
