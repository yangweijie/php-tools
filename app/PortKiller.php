<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiHBox;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiTable;
use Kingbes\Libui\SDK\LibuiApplication;

class PortKiller
{
    private LibuiVBox $box;
    private LibuiEntry $portEntry;
    private array $processes = [];
    private array $checkboxes = [];
    private LibuiVBox $checkboxContainer;
    private LibuiVBox $containerParent;
    private string $lastQueriedPort = '';

    // 简单的日志函数
    private function log($message) {
        error_log("[PortKiller] " . $message);
    }
    private ?LibuiButton $selectAllBtn = null;
    private ?LibuiTable $table = null;

    public function __construct()
    {
        // 创建垂直容器
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);

        // 恢复查询表单部分
        // 水平布局：端口输入框和查询按钮
        $inputBox = new LibuiHBox();
        $inputBox->setPadded(true);
        $this->box->append($inputBox, false);

        // 端口输入框标签
        $portLabel = new LibuiLabel("端口号:");
        $inputBox->append($portLabel, false);

        // 端口输入框
        $this->portEntry = new LibuiEntry();
        $this->log("Port entry created with ID: " . $this->portEntry->getId());
        // 添加onChange事件监听器来跟踪用户输入
        $this->portEntry->on('entry.changed', function($entry) {
            $this->log("Port entry changed, current text: " . $entry->getText());
        });
        $inputBox->append($this->portEntry, true);

        // 查询按钮
        $queryBtn = new LibuiButton("查询占用");
        $queryBtn->onClick(function() {
            $this->log("Query button clicked");
            $this->queryPort();
        });
        $inputBox->append($queryBtn, false);

        // 结果标签
        $resultLabel = new LibuiLabel("端口占用进程列表（勾选需要终止的进程）:");
        $this->box->append($resultLabel, false);

        // 记住父容器
        $this->containerParent = $this->box;

        // 创建复选框容器
        $this->createCheckboxContainer();
        
        // 创建表格和按钮（只创建一次）
        $this->createTableAndButtons();
    }

    /**
     * 创建复选框容器
     */
    private function createCheckboxContainer()
    {
        $this->checkboxContainer = new LibuiVBox();
        $this->checkboxContainer->setPadded(true);
        $this->containerParent->append($this->checkboxContainer, true);
    }
    
    /**
     * 创建表格和按钮
     */
    private function createTableAndButtons()
    {
        // 创建表格
        $this->table = new LibuiTable();
        
        // 添加列 - 第三个参数-1表示可编辑，-2表示不可编辑
        $this->table->addCheckboxColumn("选择", 0, -1)
              ->addTextColumn("PID", 1)
              ->addTextColumn("User", 2)
              ->addTextColumn("Command", 3);

        // 设置选择改变事件
        $this->table->onSelectionChanged(function($selectedRow, $selectedRows, $tableComponent) {
            $this->log("Table selection changed, selectedRow: " . $selectedRow);
            // 处理选择改变事件
            if ($selectedRow >= 0 && $selectedRow < count($this->processes)) {
                $pid = $this->processes[$selectedRow]['pid'] ?? '';
                if (!empty($pid)) {
                    if (isset($this->checkboxes[$pid])) {
                        unset($this->checkboxes[$pid]);
                    } else {
                        $this->checkboxes[$pid] = true;
                    }
                    
                    // 更新全选按钮文本
                    $this->updateSelectAllButtonText();
                }
            }
        });
        
        // 监听复选框改变事件
        $this->table->on('table.checkbox_changed', function($table, $data) {
            $this->log("Table checkbox changed: " . json_encode($data));
            $row = $data['row'] ?? -1;
            $newValue = $data['new_value'] ?? 0;
            
            if ($row >= 0 && $row < count($this->processes)) {
                $pid = $this->processes[$row]['pid'] ?? '';
                if (!empty($pid)) {
                    if ($newValue == 1) {
                        $this->checkboxes[$pid] = true;
                    } else {
                        unset($this->checkboxes[$pid]);
                    }
                    
                    // 更新全选按钮文本
                    $this->updateSelectAllButtonText();
                }
            }
        });

        // 将表格添加到容器
        $this->checkboxContainer->append($this->table, true);

        // 添加按钮
        $buttonBox = new LibuiHBox();
        $buttonBox->setPadded(true);

        // 全选按钮
        $this->selectAllBtn = new LibuiButton("全选");
        $this->selectAllBtn->onClick([$this, 'toggleSelectAllProcesses']);
        $buttonBox->append($this->selectAllBtn, true);

        // 杀选中进程按钮
        $killBtn = new LibuiButton("清除选择");
        $killBtn->onClick(function() {
            $this->log("Kill button clicked");
            $this->killSelectedProcesses();
        });
        $buttonBox->append($killBtn, true);

        $this->checkboxContainer->append($buttonBox, false);
        
        // 监听表格数据更新事件
        $this->table->on('table.data_updated', function($table, $data) {
            $this->log("Table data updated, row count: " . ($data['row_count'] ?? 0));
            // 数据更新后，可能需要刷新UI
        });
    }

    public function getControl()
    {
        return $this->box;
    }

    /**
     * 查询端口
     */
    public function queryPort()
    {
        $this->log("queryPort called");
        $port = $this->portEntry->getText();
        $this->log("queryPort called with port: " . $port);
        if (empty($port)) {
            $this->log("Empty port, returning");
            return;
        }

        // 保存端口号，因为在重建界面时可能会丢失
        $this->lastQueriedPort = $port;
        $this->log("Saved lastQueriedPort: " . $this->lastQueriedPort);

        // 查询端口占用进程
        $this->processes = $this->getPortProcessesInfo($port);
        $this->log("Found " . count($this->processes) . " processes");

        // 显示进程列表（这会自动清除旧的内容）
        $this->displayProcessList();
    }

    /**
     * 清除复选框
     */
    private function clearCheckboxes()
    {
        $this->log("Clearing checkboxes");
        // 清空复选框引用
        $this->checkboxes = [];
        
        // 由于libui的限制，我们无法直接清空容器内容
        // 所以我们简单地记录需要清空的标记
        // 实际的清空操作在displayProcessList中完成
    }
    
    /**
     * 清空容器内容
     */
    private function clearContainer()
    {
        $this->log("Clearing container");
        // 简单地清空容器中的所有子元素
        // 由于libui的限制，我们无法直接清空容器内容
        // 所以我们记录需要清空的标记，实际的清空操作在添加新内容时完成
        $this->log("Container marked for clearing");
    }
    
    /**
     * 移除旧的表格和按钮
     */
    private function removeOldTableAndButtons()
    {
        // 注意：由于libui的限制，我们无法直接从容器中移除子元素
        // 这里只是一个占位符方法，实际的清理在重新创建容器时完成
        $this->log("Removing old table and buttons - placeholder method");
    }
    /**
     * 显示进程列表
     */
    private function displayProcessList()
    {
        $this->log("displayProcessList called, lastQueriedPort: " . $this->lastQueriedPort);
        $this->log("Number of processes found: " . count($this->processes));
        
        // 清除旧的复选框引用
        $this->clearCheckboxes();

        // 如果没有进程，显示消息提示但仍然显示表格
        if (empty($this->processes)) {
            $this->log("No processes found, but still showing table");
            // 如果输入框有值但没有查询到结果，显示消息提示
            $port = $this->lastQueriedPort;
            $this->log("Using port for message: " . $port);
            if (!empty($port)) {
                // 额外的日志来跟踪消息显示过程
                $this->log("About to show message box with port: " . $port);
                
                // 获取主窗口引用
                global $application;
                $this->log("Global application object exists: " . (isset($application) ? "yes" : "no"));
                if (isset($application)) {
                    $window = $application->getWindow();
                    $this->log("Window object exists: " . (isset($window) ? "yes" : "no"));
                    if (isset($window)) {
                        $handle = $window->getHandle();
                        $this->log("Window handle exists: " . (isset($handle) ? "yes" : "no"));
                        
                        // 使用异步方式显示消息框，避免GUI事件冲突
                        // 这里我们简单地记录需要显示消息框，实际的消息框显示需要在适当的时候进行
                        $this->log("Message box display scheduled for later to avoid GUI event conflicts");
                        // 暂时使用error_log记录消息，而不是直接显示消息框
                        error_log("未查询到占用端口 {$port} 的进程");
                    } else {
                        $this->log("Window object is null");
                    }
                } else {
                    $this->log("Global application object is null");
                }
            } else {
                $this->log("Port is empty, not showing message");
            }
            // 注意：我们不返回，而是继续显示空表格
        }
        
        $this->log("Displaying table (with or without data)");
        
        try {
            // 设置数据
            $this->log("Setting table data, processes count: " . count($this->processes));
            $data = [];
            foreach ($this->processes as $process) {
                $this->log("Processing process: " . json_encode($process));
                $pid = $process['pid'] ?? '';
                $isChecked = isset($this->checkboxes[$pid]) ? 1 : 0;
                $data[] = [
                    $isChecked, // 复选框状态
                    $pid, // PID
                    $process['session'] ?? $process['protocol'] ?? '', // 用户
                    isset($process['name']) ? $process['name'] : ($process['local_address'] ?? '') // 命令
                ];
                $this->log("Added row: " . json_encode($data[count($data)-1]));
            }
            // 确保即使没有数据也会设置一个空数组
            if (empty($data)) {
                $this->log("No data rows, setting empty array");
                $data = [];
            }
            
            // 保存当前的表格handle
            $oldHandle = $this->table->getHandle();
            
            // 设置新数据
            $this->table->setData($data);
            $this->log("Table data set");
            
            // 如果handle发生了变化，需要重新添加到容器中
            $newHandle = $this->table->getHandle();
            if ($oldHandle !== $newHandle) {
                $this->log("Table handle changed, need to re-append to container");
                // 由于libui的限制，我们无法直接替换子元素
                // 这里我们记录需要更新的标记
            }

            // 更新全选按钮文本
            $this->updateSelectAllButtonText();

        } catch (\Exception $e) {
            $this->log("Exception occurred: " . $e->getMessage());
            // 如果表格创建失败，显示错误信息
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();
            
            // 显示错误消息框
            \Kingbes\Libui\Window::msgBoxError(
                $window->getHandle(),
                "错误",
                "表格创建失败: " . $e->getMessage()
            );
        }
    }

    /**
     * 获取全选按钮的文本
     */
    private function getSelectAllButtonText() {
        // 检查是否所有进程都被选中
        $allChecked = true;
        $hasProcesses = count($this->processes) > 0;
        if ($hasProcesses) {
            foreach ($this->processes as $process) {
                $pid = $process['pid'] ?? '';
                if (!empty($pid) && !isset($this->checkboxes[$pid])) {
                    $allChecked = false;
                    break;
                }
            }
        }
        $hasChecked = count($this->checkboxes) > 0;
        return ($allChecked && $hasChecked && $hasProcesses) ? "全否" : "全选";
    }

    /**
     * 更新全选按钮文本
     */
    private function updateSelectAllButtonText() {
        if ($this->selectAllBtn !== null) {
            $buttonText = $this->getSelectAllButtonText();
            // 由于SDK中可能没有setText方法，我们需要重新创建按钮
            // 但在这里我们只是更新按钮的文本
            // 注意：在libui中，可能需要重新创建按钮才能更新文本
        }
    }

    /**
     * 切换全选/全否进程
     */
    public function toggleSelectAllProcesses()
    {
        $this->log("toggleSelectAllProcesses called, processes count: " . count($this->processes));
        // 检查是否所有进程都被选中
        $allChecked = true;
        $hasProcesses = count($this->processes) > 0;

        // 如果没有进程，直接返回
        if (!$hasProcesses) {
            $this->log("No processes, returning");
            return;
        }

        // 检查是否所有进程都被选中
        foreach ($this->processes as $process) {
            $pid = $process['pid'] ?? '';
            if (!empty($pid) && !isset($this->checkboxes[$pid])) {
                $allChecked = false;
                break;
            }
        }

        // 检查是否有任何进程被选中
        $hasChecked = count($this->checkboxes) > 0;
        
        $this->log("allChecked: " . ($allChecked ? "true" : "false") . ", hasChecked: " . ($hasChecked ? "true" : "false"));

        if ($allChecked && $hasChecked) {
            // 当前是全选状态，切换到全否
            $this->log("Switching to unselect all");
            $this->checkboxes = [];
        } else {
            // 当前不是全选状态，切换到全选
            $this->log("Switching to select all");
            foreach ($this->processes as $process) {
                $pid = $process['pid'] ?? '';
                if (!empty($pid)) {
                    $this->checkboxes[$pid] = true;
                }
            }
        }

        // 更新全选按钮文本
        $this->updateSelectAllButtonText();

        // 更新表格中的复选框状态
        $this->updateTableCheckboxStates();
    }

    /**
     * 更新表格中的复选框状态
     */
    private function updateTableCheckboxStates() {
        // 重新设置表格数据以更新复选框状态
        if ($this->table !== null) {
            $data = [];
            foreach ($this->processes as $process) {
                $pid = $process['pid'] ?? '';
                $isChecked = isset($this->checkboxes[$pid]) ? 1 : 0;
                $data[] = [
                    $isChecked, // 复选框状态
                    $pid, // PID
                    $process['session'] ?? $process['protocol'] ?? '', // 用户
                    isset($process['name']) ? $process['name'] : ($process['local_address'] ?? '') // 命令
                ];
            }
            $this->table->setData($data);
        }
    }

    /**
     * 杀选中的进程
     */
    public function killSelectedProcesses()
    {
        $this->log("killSelectedProcesses called");
        $selectedPids = array_keys($this->checkboxes);
        $this->log("Selected PIDs: " . json_encode($selectedPids));

        if (empty($selectedPids)) {
            $this->log("No processes selected, showing message");
            // 显示提示消息
            // 创建一个临时的消息标签而不是添加到容器中
            global $application;
            $window = $application->getWindow();
            
            // 显示信息消息框
            \Kingbes\Libui\Window::msgBox(
                $window->getHandle(),
                "提示",
                "未选中任何进程"
            );
            return;
        }

        $this->log("Killing selected processes: " . json_encode($selectedPids));
        $results = [];
        foreach ($selectedPids as $pid) {
            $results[] = $this->killProcessById($pid);
        }

        // 清空选中状态
        $this->checkboxes = [];

        // 延迟调用queryPort方法，避免GUI事件冲突
        // 使用setTimeout或类似机制延迟执行
        $this->log("Scheduling queryPort call after delay");
        // 这里我们简单地记录需要延迟调用，实际的延迟机制需要在LibuiApplication中实现
        // 暂时先直接调用，但添加更多的日志来追踪问题
        $this->log("About to call queryPort from killSelectedProcesses");
        $this->queryPort();
        $this->log("Finished calling queryPort from killSelectedProcesses");
    }

    /**
     * 获取端口进程详细信息
     */
    private function getPortProcessesInfo($port)
    {
        $this->log("getPortProcessesInfo called with port: " . $port);
        $os = \App\App::getOperatingSystem();
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
                if (empty($line)) {
                    $this->log("Skipping empty line");
                    continue;
                }
                
                // 对于第一行，检查是否是标题行
                if ($i == 0 && strpos($line, 'COMMAND') !== false) {
                    $this->log("Skipping header line");
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

    /**
     * 杀进程
     */
    private function killProcessById($pid)
    {
        $os = \App\App::getOperatingSystem();
        $command = '';

        if ($os === 'WIN') {
            // Windows系统
            $command = "taskkill /PID {$pid} /F";
        } elseif ($os === 'DAR' || $os === 'LIN') {
            // macOS或Linux系统
            $command = "kill -9 {$pid}";
        } else {
            return "不支持的操作系统";
        }

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            return "成功终止进程 {$pid}";
        } else {
            return "终止进程 {$pid} 失败: " . implode("\n", $output);
        }
    }
}

