<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiHBox;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiTable;
use Kingbes\Libui\SDK\LibuiApplication;

class ProcessKiller
{
    private LibuiVBox $box;
    private LibuiEntry $processEntry;
    private array $processes = [];
    private array $checkboxes = [];
    private LibuiVBox $checkboxContainer;
    private LibuiVBox $containerParent;
    private ?LibuiButton $selectAllBtn = null;
    private ?LibuiTable $table = null;
    
    // 简单的日志函数
    private function log($message) {
        error_log("[ProcessKiller] " . $message);
    }
    
    public function __construct()
    {
        // 创建垂直容器
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);
        
        // 水平布局：进程输入框和查询按钮
        $inputBox = new LibuiHBox();
        $inputBox->setPadded(true);
        $this->box->append($inputBox, false);
        
        // 进程输入框标签
        $processLabel = new LibuiLabel("进程名或PID:");
        $inputBox->append($processLabel, false);
        
        // 进程输入框
        $this->processEntry = new LibuiEntry();
        $inputBox->append($this->processEntry, true);
        
        // 查询按钮
        $queryBtn = new LibuiButton("查询进程");
        $queryBtn->onClick(function() {
            $this->log("Query button clicked");
            $this->queryProcess();
        });
        $inputBox->append($queryBtn, false);
        
        // 结果标签
        $resultLabel = new LibuiLabel("进程列表（勾选需要终止的进程）:");
        $this->box->append($resultLabel, false);
        
        // 记住父容器
        $this->containerParent = $this->box;
        
        // 创建复选框容器
        $this->createCheckboxContainer();
        
        // 创建表格和按钮（只创建一次）
        $this->createTableAndButtons();
        
        // 初始化表格数据为空
        if ($this->table !== null) {
            $this->table->setData([]);
        }
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
        
        // 添加列 - 确保复选框列正确设置，第三个参数-1表示可编辑，-2表示不可编辑
        $this->table->addCheckboxColumn("", 0, -1)
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
    }
    
    public function getControl()
    {
        return $this->box;
    }
    
    /**
     * 查询进程
     */
    public function queryProcess()
    {
        $process = $this->processEntry->getText();
        $this->log("queryProcess called with process: " . $process);
        if (empty($process)) {
            $this->log("Empty process, returning");
            // 即使输入为空，也要更新表格显示
            $this->processes = [];
            $this->displayProcessList();
            return;
        }
        
        // 查询进程
        $this->processes = $this->getProcessesInfo($process);
        $this->log("Found " . count($this->processes) . " processes");
        
        // 显示进程列表
        $this->displayProcessList();
    }
    
    /**
     * 清除复选框
     */
    private function clearCheckboxes()
    {
        $this->log("Clearing checkboxes");
        // 清空复选框引用
        // 不再清空checkboxes数组，保持之前的选择状态
        // $this->checkboxes = [];
        
        // 由于libui的限制，我们无法直接清空容器内容
        // 所以我们简单地记录需要清空的标记
        // 实际的清空操作在displayProcessList中完成
    }
    
    /**
     * 显示进程列表
     */
    private function displayProcessList()
    {
        // 不再清除复选框，保持之前的选择状态
        // $this->clearCheckboxes();
        
        try {
            // 设置数据
            $data = [];
            foreach ($this->processes as $process) {
                $pid = $process['pid'] ?? '';
                $isChecked = isset($this->checkboxes[$pid]) ? 1 : 0;
                $data[] = [
                    $isChecked, // 复选框状态
                    $pid, // PID
                    $process['session'] ?? '', // 用户
                    isset($process['command']) ? $process['command'] : $process['name'] // 命令
                ];
            }
            // 确保即使没有数据也会设置一个空数组
            if (empty($data)) {
                $data = [];
            }
            $this->table->setData($data);

            // 更新全选按钮文本
            $this->updateSelectAllButtonText();

        } catch (\Exception $e) {
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
                    $process['session'] ?? '', // 用户
                    isset($process['command']) ? $process['command'] : $process['name'] // 命令
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

        // 重新查询进程
        $this->queryProcess();
    }
    
    // 保持兼容性，但实际调用killSelectedProcesses
    public function killSelectedProcessesForProcessKiller()
    {
        $this->killSelectedProcesses();
    }
    
    /**
     * 获取进程信息
     */
    private function getProcessesInfo($process)
    {
        $os = \App\App::getOperatingSystem();
        $command = '';
        $output = [];
        $processes = [];
        
        if ($os === 'WIN') {
            // Windows系统
            if (is_numeric($process)) {
                // 按PID查询
                $command = "tasklist /FI \"PID eq {$process}\" /FO CSV /NH";
            } else {
                // 按进程名查询
                $command = "tasklist /FI \"IMAGENAME eq {$process}*\" /FO CSV /NH";
            }
            
            exec($command, $output);
            
            // 解析CSV格式输出
            foreach ($output as $line) {
                if (empty(trim($line))) continue;
                
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
                                    break;
                                }
                            }
                        }
                    }
                    
                    $processes[] = [
                        'name' => $processName,
                        'pid' => $pid,
                        'session' => $sessionName,
                        'memory' => $memory,
                        'command' => $commandLine
                    ];
                }
            }
        } elseif ($os === 'DAR' || $os === 'LIN') {
            // macOS或Linux系统
            if (is_numeric($process)) {
                // 按PID查询
                $command = "ps -p {$process} -o pid,user,comm,%mem 2>/dev/null";
            } else {
                // 按进程名查询
                $command = "ps aux | grep {$process} | grep -v grep 2>/dev/null";
            }
            
            exec($command, $output);
            
            // 解析输出
            foreach ($output as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                if (is_numeric($process)) {
                    // 按PID查询的解析
                    $parts = preg_split('/\s+/', $line, 4); // 限制分割为4部分
                    if (count($parts) >= 4) {
                        $pid = $parts[0];
                        $user = $parts[1];
                        $processName = $parts[2];
                        $memory = $parts[3];
                        
                        // 获取完整命令行
                        $cmdOutput = [];
                        exec("ps -p {$pid} -o command= 2>/dev/null", $cmdOutput);
                        $commandLine = $processName; // 默认值
                        
                        // 如果有完整命令行，使用它
                        if (!empty($cmdOutput)) {
                            $commandLine = trim($cmdOutput[0]);
                        }
                        
                        $processes[] = [
                            'name' => $processName,
                            'pid' => $pid,
                            'session' => $user,
                            'memory' => $memory,
                            'command' => $commandLine
                        ];
                    }
                } else {
                    // 按进程名查询的解析 (ps aux 格式)
                    $parts = preg_split('/\s+/', $line);
                    if (count($parts) >= 11) {
                        $user = $parts[0];
                        $pid = $parts[1];
                        $cpu = $parts[2];
                        $mem = $parts[3];
                        $commandName = $parts[10]; // 命令名
                        $commandLine = implode(' ', array_slice($parts, 10)); // 命令行是剩余部分
                        
                        // 获取完整命令行
                        $cmdOutput = [];
                        exec("ps -p {$pid} -o command= 2>/dev/null", $cmdOutput);
                        if (!empty($cmdOutput)) {
                            $commandLine = trim($cmdOutput[0]);
                        }
                        
                        $processes[] = [
                            'name' => $commandName,
                            'pid' => $pid,
                            'session' => $user,
                            'memory' => $mem,
                            'command' => $commandLine
                        ];
                    }
                }
            }
        }
        
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
