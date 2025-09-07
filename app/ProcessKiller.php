<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Button;
use Kingbes\Libui\Control;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Label;
use Kingbes\Libui\MultilineEntry;
use Kingbes\Libui\Checkbox;
use Kingbes\Libui\Table;
use Kingbes\Libui\TableValueType;

class ProcessKiller
{
    private $box;
    private $processEntry;
    private $resultEntry;
    private $processes = [];
    private $checkboxes = [];
    private $checkboxRows = []; // 存储行容器引用
    private $checkboxContainer;
    private $containerParent; // 存储容器的父容器
    private $selectAllBtn = null; // 存储全选按钮引用
    private $tableModel = null; // 存储表格模型引用
    private $allSelected = false; // 跟踪是否全选
    
    public function __construct()
    {
        // 创建垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);
        
        // 标题
        // $title = Label::create("进程查杀工具");
        // Box::append($this->box, $title, false);
        
        // 说明
        // $desc = Label::create("输入进程名或PID，点击查询按钮查看进程信息，点击“清除选择” 终止选中的进程");
        // Box::append($this->box, $desc, false);
        
        // 水平布局：进程输入框和查询按钮
        $inputBox = Box::newHorizontalBox();
        Box::setPadded($inputBox, true);
        Box::append($this->box, $inputBox, false);
        
        // 进程输入框标签
        $processLabel = Label::create("进程名或PID:");
        Box::append($inputBox, $processLabel, false);
        
        // 进程输入框
        $this->processEntry = Entry::create();
        Box::append($inputBox, $this->processEntry, true);
        
        // 查询按钮
        $queryBtn = Button::create("查询进程");
        Button::onClicked($queryBtn, [$this, 'queryProcess']);
        Box::append($inputBox, $queryBtn, false);
        
        // 结果标签
        $resultLabel = Label::create("进程列表（勾选需要终止的进程）:");
        Box::append($this->box, $resultLabel, false);
        
        // 记住父容器
        $this->containerParent = $this->box;
        
        // 创建复选框容器
        $this->createCheckboxContainer();
    }
    
    /**
     * 创建复选框容器
     */
    private function createCheckboxContainer()
    {
        $this->checkboxContainer = Box::newVerticalBox();
        Box::setPadded($this->checkboxContainer, true);
        Box::append($this->containerParent, $this->checkboxContainer, false);
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
        $process = Entry::text($this->processEntry);
        if (empty($process)) {
            return;
        }
        
        // 打印调试信息
        error_log("进程查询: {$process}");
        
        // 查询进程
        $this->processes = $this->getProcessesInfo($process);
        
        // 打印结果
        error_log("查询结果: " . print_r($this->processes, true));
        
        // 清除旧的复选框
        $this->clearCheckboxes();
        
        // 显示进程列表
        $this->displayProcessList();
    }
    
    /**
     * 清除复选框
     */
    private function clearCheckboxes()
    {
        // 清空复选框引用
        $this->checkboxes = [];
        $this->checkboxRows = [];
        
        // 创建新容器替换旧容器
        $newContainer = Box::newVerticalBox();
        Box::setPadded($newContainer, true);
        
        // 移除旧容器（在索引2的位置）
        Box::delete($this->containerParent, 2);
        
        // 添加新容器
        Box::append($this->containerParent, $newContainer, false);
        
        // 更新引用
        $this->checkboxContainer = $newContainer;
    }
    
    /**
     * 显示进程列表
     */
    private function displayProcessList()
    {
        error_log("进程查询: 开始显示进程列表，进程数量: " . count($this->processes));
        
        // 清除旧的内容
        $this->clearCheckboxes();
        
        if (empty($this->processes)) {
            error_log("没有数据，显示 No Data 标签");
            $label = Label::create("No Data");
            Box::append($this->checkboxContainer, $label, false);
            return;
        }
        
        error_log("开始创建表格");
        try {
            // 保存进程数据以便在回调中使用
            $processesRef = &$this->processes;
            
            // 创建表格模型处理器
            $handler = Table::modelHandler(
                4, // 列数：PID、用户、命令、复选框
                TableValueType::String, // 列类型（使用String作为默认类型）
                count($this->processes), // 行数
                function ($handler, $row, $column) use ($processesRef) {
                    if ($row < 0 || $row >= count($processesRef)) {
                        return Table::createValueStr('');
                    }
                    
                    $process = $processesRef[$row];
                    
                    switch ($column) {
                        case 0: // 复选框列
                            $pid = $process['pid'] ?? '';
                            $isChecked = isset($this->checkboxes[$pid]) ? 1 : 0;
                            return Table::createValueInt($isChecked);
                        case 1: // PID列
                            return Table::createValueStr($process['pid'] ?? '');
                        case 2: // 用户列
                            return Table::createValueStr($process['session'] ?? '');
                        case 3: // 命令列
                            return Table::createValueStr(isset($process['command']) ? $process['command'] : $process['name']);
                        default:
                            return Table::createValueStr('');
                    }
                },
                function ($handler, $row, $column, $value) use ($processesRef) {
                    if ($column == 0 && $value !== null) { // 复选框列
                        $checked = Table::valueInt($value);
                        $pid = $processesRef[$row]['pid'] ?? '';
                        if (!empty($pid)) {
                            if ($checked) {
                                // 选中进程
                                $this->checkboxes[$pid] = true;
                            } else {
                                // 取消选中进程
                                if (isset($this->checkboxes[$pid])) {
                                    unset($this->checkboxes[$pid]);
                                }
                            }
                            
                            // 更新全选按钮文本
                            $this->updateSelectAllButtonText();
                        }
                    }
                    return 1; // 返回1表示处理成功
                }
            );

            // 创建表格模型
            $this->tableModel = Table::createModel($handler);
            // 创建表格
            error_log("进程查询: 创建表格，进程数量: " . count($this->processes));
            $table = Table::create($this->tableModel, -1);
            // 表格追加复选框列（第4个参数为0表示可编辑）
            Table::appendCheckboxColumn($table, "", 0, 0);
            // 表格追加文本列
            Table::appendTextColumn($table, "PID", 1, -1);
            // 表格追加文本列
            Table::appendTextColumn($table, "User", 2, -1);
            // 表格追加文本列
            Table::appendTextColumn($table, "Command", 3, -1);

            // 将表格添加到容器
            Box::append($this->checkboxContainer, $table, true);
            
            // 添加按钮
            $buttonBox = Box::newHorizontalBox();
            Box::setPadded($buttonBox, true);
            
            // 全选按钮
            $buttonText = $this->getSelectAllButtonText();
            $this->selectAllBtn = Button::create($buttonText);
            Button::onClicked($this->selectAllBtn, [$this, 'toggleSelectAllProcesses']);
            Box::append($buttonBox, $this->selectAllBtn, true);
            
            // 杀选中进程按钮 (红色背景，白色字体)
            $killBtn = Button::create("清除选择");
            Button::onClicked($killBtn, [$this, 'killSelectedProcesses']);
            Box::append($buttonBox, $killBtn, true);
            
            Box::append($this->checkboxContainer, $buttonBox, false);
            
        } catch (\Exception $e) {
            // 如果表格创建失败，显示错误信息
            error_log("表格创建失败: " . $e->getMessage());
            $errorLabel = Label::create("表格创建失败: " . $e->getMessage());
            Box::append($this->checkboxContainer, $errorLabel, false);
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
            Button::setText($this->selectAllBtn, $buttonText);
        }
    }
    
    /**
     * 切换全选/全否进程
     */
    public function toggleSelectAllProcesses()
    {
        // 检查是否所有进程都被选中
        $allChecked = true;
        $hasProcesses = count($this->processes) > 0;
        
        // 如果没有进程，直接返回
        if (!$hasProcesses) {
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
        
        if ($allChecked && $hasChecked) {
            // 当前是全选状态，切换到全否
            $this->checkboxes = [];
        } else {
            // 当前不是全选状态，切换到全选
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
        // 更新表格中的复选框状态
        error_log("进程查询: 更新表格复选框状态");
        
        // 如果有表格模型引用，则更新所有行的复选框状态
        if ($this->tableModel !== null) {
            for ($i = 0; $i < count($this->processes); $i++) {
                Table::modelRowChanged($this->tableModel, $i);
            }
        } else {
            error_log("进程查询: 表格模型引用为空，无法更新复选框状态");
        }
    }
    
    /**
     * 全不选进程
     */
    public function selectNoneProcesses()
    {
        // 清空选中的进程
        $this->checkboxes = [];
    }
    
    /**
     * 杀选中的进程
     */
    public function killSelectedProcesses()
    {
        $selectedPids = array_keys($this->checkboxes);
        
        if (empty($selectedPids)) {
            // 显示提示消息
            $label = Label::create("未选中任何进程");
            Box::append($this->checkboxContainer, $label, false);
            return;
        }
        
        $results = [];
        foreach ($selectedPids as $pid) {
            $results[] = $this->killProcessById($pid);
        }
        
        // 清空选中状态
        $this->checkboxes = [];
        
        // 重新查询进程
        $this->queryProcess();
    }
    
    /**
     * 获取进程信息
     */
    private function getProcessesInfo($process)
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
                // 按进程名查询
                $command = "tasklist /FI \"IMAGENAME eq {$process}*\" /FO CSV /NH";
            }
            
            error_log("执行命令: {$command}");
            exec($command, $output);
            error_log("命令输出: " . print_r($output, true));
            
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
            
            error_log("执行命令: {$command}");
            exec($command, $output);
            error_log("命令输出: " . print_r($output, true));
            
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
                    error_log("进程查询行解析: " . print_r($parts, true));
                    if (count($parts) >= 11) {
                        $user = $parts[0];
                        $pid = $parts[1];
                        $cpu = $parts[2];
                        $mem = $parts[3];
                        // $vsz = $parts[4]; // 不需要
                        // $rss = $parts[5]; // 不需要
                        // $tty = $parts[6]; // 不需要
                        // $stat = $parts[7]; // 不需要
                        // $start = $parts[8]; // 不需要
                        // $time = $parts[9]; // 不需要
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
                    } else {
                        error_log("进程查询行解析失败，部分数不足: " . count($parts));
                    }
                }
            }
        }
        
        error_log("进程查询结果: " . print_r($processes, true));
        return $processes;
    }
    
    /**
     * 杀进程
     */
    private function killProcessById($pid)
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
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