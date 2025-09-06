<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$action = $_GET["action"] ?? "";

switch ($action) {
    case "searchByPort":
        echo json_encode(searchByPort($_GET["port"] ?? ""));
        break;
    case "searchByProcess":
        echo json_encode(searchByProcess($_GET["keyword"] ?? ""));
        break;
    case "killProcess":
        echo json_encode(killProcess($_GET["pid"] ?? ""));
        break;
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
}

function searchByPort($port) {
    if (empty($port) || !is_numeric($port)) {
        return ["success" => false, "message" => "Invalid port number"];
    }
    
    $command = "netstat -ano | findstr :$port";
    $output = shell_exec($command);
    
    $processes = [];
    if ($output) {
        $lines = explode("\n", trim($output));
        $seenPids = [];
        
        foreach ($lines as $line) {
            if (preg_match('/\s+(\d+)$/', $line, $matches)) {
                $pid = $matches[1];
                
                // Skip if we already processed this PID
                if (in_array($pid, $seenPids)) continue;
                $seenPids[] = $pid;
                
                $processInfo = shell_exec("tasklist /fi \"PID eq $pid\" /fo csv /nh 2>nul");
                if ($processInfo && !strpos($processInfo, "INFO: No tasks")) {
                    $info = str_getcsv(trim($processInfo));
                    if (count($info) >= 5) {
                        $processes[] = [
                            "pid" => $pid,
                            "name" => $info[0] ?? "Unknown",
                            "memory" => $info[4] ?? "Unknown"
                        ];
                    }
                }
            }
        }
    }
    
    return ["success" => true, "processes" => $processes];
}

function searchByProcess($keyword) {
    if (empty($keyword)) {
        return ["success" => false, "message" => "Keyword cannot be empty"];
    }
    
    $processes = getProcessesInfo($keyword);
    
    return ["success" => true, "processes" => $processes];
}

/**
 * 获取进程信息 - 跨平台支持
 */
function getProcessesInfo($process)
{
    $os = strtoupper(substr(PHP_OS, 0, 3));
    $command = '';
    $output = [];
    $processes = [];
    
    error_log("操作系统: {$os}, 查询进程: {$process}");
    
    if ($os === 'WIN') {
        // Windows系统
        if (is_numeric($process)) {
            // 按PID查询
            $command = "tasklist /FI \"PID eq {$process}\" /FO CSV /NH";
        } else {
            // 按进程名查询 - 使用通配符匹配
            $command = "tasklist /FI \"IMAGENAME eq {$process}*\" /FO CSV /NH";
        }
        
        error_log("执行命令: {$command}");
        exec($command, $output);
        error_log("命令输出: " . print_r($output, true));
        
        // 如果没有找到精确匹配，尝试模糊搜索
        if (empty($output) || (count($output) == 1 && strpos($output[0], 'INFO: No tasks') !== false)) {
            // 获取所有进程然后过滤
            $output = [];
            $command = "tasklist /FO CSV /NH";
            exec($command, $output);
            
            $filteredOutput = [];
            foreach ($output as $line) {
                if (stripos($line, $process) !== false) {
                    $filteredOutput[] = $line;
                }
            }
            $output = $filteredOutput;
        }
        
        // 解析CSV格式输出
        foreach ($output as $line) {
            if (empty(trim($line))) continue;
            if (strpos($line, 'INFO: No tasks') !== false) continue;
            
            // 移除引号并分割CSV
            $line = str_replace('"', '', $line);
            $parts = explode(',', $line);
            
            if (count($parts) >= 5) {
                $processName = trim($parts[0]);
                $pid = trim($parts[1]);
                $sessionName = trim($parts[2]);
                $memory = trim($parts[4]);
                
                // 尝试获取完整命令行 - 使用PowerShell命令
                $cmdOutput = [];
                $psCommand = "powershell.exe -Command \"Get-WmiObject Win32_Process -Filter \\\"ProcessId = {$pid}\\\" | Select-Object CommandLine | Format-List\"";
                exec($psCommand, $cmdOutput);
                $commandLine = $processName; // 默认使用进程名
                
                // 解析PowerShell输出获取完整命令行
                if (!empty($cmdOutput)) {
                    foreach ($cmdOutput as $outputLine) {
                        if (strpos($outputLine, 'CommandLine') !== false) {
                            // 提取冒号后的内容作为命令行
                            $parts = explode(':', $outputLine, 2);
                            if (isset($parts[1])) {
                                $commandLine = trim($parts[1]);
                                // 移除可能的空格和引号
                                $commandLine = trim($commandLine, " \t\n\r\0\x0B\"");
                                if (!empty($commandLine)) {
                                    break;
                                }
                            }
                        }
                    }
                }
                
                $processes[] = [
                    'pid' => $pid,
                    'name' => $processName,
                    'memory' => $memory,
                    'session' => $sessionName,
                    'command' => $commandLine
                ];
            }
        }
    } elseif ($os === 'DAR' || $os === 'LIN') {
        // macOS或Linux系统
        if (is_numeric($process)) {
            // 按PID查询
            $command = "ps -p {$process} -o pid,user,comm,%mem";
        } else {
            // 按进程名查询
            $command = "ps -e | grep {$process} | grep -v grep";
        }
        
        error_log("执行命令: {$command}");
        exec($command, $output);
        error_log("命令输出: " . print_r($output, true));
        
        // 解析输出
        foreach ($output as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = preg_split('/\s+/', $line, 4); // 限制分割为4部分
            if (count($parts) >= 2) {
                $pid = $parts[0];
                $processName = isset($parts[2]) ? $parts[2] : $parts[1];
                $user = isset($parts[1]) ? $parts[1] : 'unknown';
                $memory = isset($parts[3]) ? $parts[3] : '';
                
                // 获取完整命令行
                $cmdOutput = [];
                exec("ps -p {$pid} -o command=", $cmdOutput);
                $commandLine = $processName; // 默认值
                
                // 如果有完整命令行，使用它
                if (!empty($cmdOutput)) {
                    $commandLine = trim($cmdOutput[0]);
                }
                
                $processes[] = [
                    'pid' => $pid,
                    'name' => $processName,
                    'memory' => $memory,
                    'session' => $user,
                    'command' => $commandLine
                ];
            }
        }
    }
    
    error_log("进程查询结果: " . print_r($processes, true));
    return $processes;
}

function killProcess($pid) {
    if (empty($pid) || !is_numeric($pid)) {
        return ["success" => false, "message" => "Invalid PID"];
    }
    
    // Check if process exists first
    $checkCommand = "tasklist /fi \"PID eq $pid\" /fo csv /nh 2>nul";
    $checkOutput = shell_exec($checkCommand);
    
    if (!$checkOutput || strpos($checkOutput, "INFO: No tasks") !== false) {
        return ["success" => false, "message" => "进程 $pid 不存在或已经终止"];
    }
    
    $command = "taskkill /f /pid $pid 2>&1";
    $output = shell_exec($command);
    
    $success = strpos($output, "SUCCESS") !== false;
    $message = $success ? "✅ 进程 $pid 已成功终止" : "❌ 终止进程失败: " . trim($output);
    
    return [
        "success" => $success,
        "message" => $message
    ];
}
?>