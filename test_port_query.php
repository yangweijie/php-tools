<?php

require_once __DIR__ . '/vendor/autoload.php';

// 模拟App类的getOperatingSystem方法
namespace App {
    class App {
        public static function getOperatingSystem() {
            $os = php_uname('s');
            if (stripos($os, 'Windows') !== false) {
                return 'WIN';
            } elseif (stripos($os, 'Darwin') !== false) {
                return 'DAR';
            } else {
                return 'LIN';
            }
        }
    }
}

// 测试端口查询功能
namespace {
    require_once __DIR__ . '/app/PortKiller.php';
    
    use App\App;
    
    // 创建PortKiller实例的简化版本来测试getPortProcessesInfo方法
    class PortQueryTest {
        private function log($message) {
            echo "[PortQueryTest] " . $message . "\n";
        }
        
        private function getPortProcessesInfo($port) {
            $this->log("getPortProcessesInfo called with port: " . $port);
            $os = App::getOperatingSystem();
            $this->log("Operating system detected: " . $os);
            $command = '';
            $output = [];
            $processes = [];

            if ($os === 'WIN') {
                // Windows系统
                $command = "netstat -ano | findstr :{$port}";
                $this->log("Executing command: " . $command);
                exec($command, $output);
                $this->log("Command returned " . count($output) . " lines");

                foreach ($output as $line) {
                    // 格式: 协议 本地地址:端口 远程地址:端口 状态 PID
                    $line = trim($line);
                    $parts = preg_split('/\s+/', $line);

                    if (count($parts) >= 5) {
                        $pid = $parts[4];
                        $this->log("Found process with PID: " . $pid);
                        // 获取该进程的更多信息
                        $processInfo = [];
                        $processInfoCmd = "tasklist /FI \"PID eq {$pid}\" /FO CSV /NH";
                        $processInfoOutput = [];
                        exec($processInfoCmd, $processInfoOutput);

                        $user = $parts[0]; // 默认使用协议作为User
                        $command = $parts[1]; // 默认使用本地地址作为Command

                        // 如果找到进程信息，更新User和Command
                        if (!empty($processInfoOutput)) {
                            $processLine = str_replace('"', '', $processInfoOutput[0]);
                            $processParts = explode(',', $processLine);
                            if (count($processParts) >= 3) {
                                $command = $processParts[0]; // 进程名称
                                $user = $processParts[2];    // 用户名

                                // 使用PowerShell获取完整命令行
                                $cmdOutput = [];
                                $psCommand = "powershell.exe -Command \"Get-WmiObject Win32_Process -Filter \"ProcessId = {$pid}\" | Select-Object CommandLine | Format-List\"";
                                exec($psCommand, $cmdOutput);

                                // 解析PowerShell输出获取完整命令行
                                if (!empty($cmdOutput)) {
                                    foreach ($cmdOutput as $outputLine) {
                                        if (strpos($outputLine, 'CommandLine') !== false) {
                                            // 提取冒号后的内容作为命令行
                                            $cmdParts = explode(':', $outputLine, 2);
                                            if (isset($cmdParts[1])) {
                                                $command = trim($cmdParts[1]);
                                                // 移除可能的空格和引号
                                                $command = trim($command, " \t\n\r\0\x0B\"");
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $processes[] = [
                        'protocol' => $parts[0],
                        'local_address' => $parts[1],
                        'remote_address' => $parts[2],
                        'state' => $parts[3],
                        'pid' => $pid,
                        'session' => $user,   // 用于User列
                        'name' => $command    // 用于Command列
                    ];
                }
            }
        } elseif ($os === 'DAR' || $os === 'LIN') {
                // macOS或Linux系统
                $command = "lsof -i :{$port} -n -P 2>/dev/null";
                $this->log("Executing command: " . $command);
                exec($command, $output);
                $this->log("Command returned " . count($output) . " lines");

                $this->log("Processing " . count($output) . " lines of output");
                // 处理每一行输出，跳过标题行
                for ($i = 0; $i < count($output); $i++) {
                    $line = trim($output[$i]);
                    $this->log("Processing line: " . $line);
                    // 跳过标题行和其他无关行
                    if (empty($line) || strpos($line, 'COMMAND') !== false) {
                        $this->log("Skipping header or empty line");
                        continue;
                    }

                    $parts = preg_split('/\s+/', $line);
                    $this->log("Line split into " . count($parts) . " parts");

                    // lsof 输出格式: COMMAND PID USER FD TYPE DEVICE SIZE/OFF NODE NAME
                    // 我们需要至少5个字段（COMMAND, PID, USER, FD, TYPE）
                    if (count($parts) >= 5) {
                        $processName = $parts[0]; // 进程名称
                        $pid = $parts[1];    // 进程 ID
                        $user = $parts[2];    // 用户名
                        $protocol = isset($parts[4]) ? $parts[4] : ''; // 协议类型
                        $localAddr = isset($parts[8]) ? $parts[8] : (isset($parts[4]) ? $parts[4] : ''); // 本地地址和端口

                        $this->log("Found process: name={$processName}, pid={$pid}, user={$user}");

                        // 获取进程的完整命令
                        $cmdOutput = [];
                        $cmdCmd = "ps -p {$pid} -o command= 2>/dev/null";
                        $this->log("Executing command: " . $cmdCmd);
                        exec($cmdCmd, $cmdOutput);
                        $commandLine = $processName; // 默认使用进程名称
                        if (!empty($cmdOutput)) {
                            $commandLine = trim($cmdOutput[0]); // 更新为完整命令行
                            $this->log("Command line: " . $commandLine);
                        }

                        $processes[] = [
                            'protocol' => $protocol,
                            'local_address' => $localAddr,
                            'remote_address' => '',
                            'state' => 'LISTEN',
                            'pid' => $pid,
                            'session' => $user,   // 用于User列
                            'name' => $commandLine    // 用于Command列
                        ];
                        $this->log("Added process to list");
                    } else {
                        $this->log("Skipping line with insufficient parts: " . count($parts));
                    }
                }
            }

            $this->log("Returning " . count($processes) . " processes");
            return $processes;
        }
        
        public function testPortQuery($port) {
            $this->log("Testing port query for port: " . $port);
            $processes = $this->getPortProcessesInfo($port);
            $this->log("Found " . count($processes) . " processes");
            
            foreach ($processes as $index => $process) {
                $this->log("Process " . ($index + 1) . ": " . json_encode($process));
            }
            
            return $processes;
        }
    }
    
    // 执行测试
    $test = new PortQueryTest();
    $test->testPortQuery("8080");
}