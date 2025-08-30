<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Button;
use Kingbes\Libui\Control;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Label;
use Kingbes\Libui\MultilineEntry;
use Kingbes\Libui\Checkbox;

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
        if (empty($this->processes)) {
            $label = Label::create("未找到匹配的进程");
            Box::append($this->checkboxContainer, $label, false);
            return;
        }
        
        // 打印调试信息
        error_log("显示进程列表，数量: " . count($this->processes));
        
        // 创建表头
        $headerBox = Box::newHorizontalBox();
        Box::setPadded($headerBox, true);
        
        // 复选框列（空标签，保持一致性）
        $checkboxHeaderLabel = Label::create("");
        Box::append($headerBox, $checkboxHeaderLabel, false);
        
        $pidHeaderLabel = Label::create("    PID");
        Box::append($headerBox, $pidHeaderLabel, true);
        
        $userHeaderLabel = Label::create("  User");
        Box::append($headerBox, $userHeaderLabel, true);
        
        $commandHeaderLabel = Label::create(" Command");
        Box::append($headerBox, $commandHeaderLabel, true);
        
        Box::append($this->checkboxContainer, $headerBox, false);
        
        // 添加进程行
        foreach ($this->processes as $process) {
            $rowBox = Box::newHorizontalBox();
            Box::setPadded($rowBox, true);
            
            // 创建复选框
            $checkbox = Checkbox::create("");
            $this->checkboxes[$process['pid']] = $checkbox;
            Box::append($rowBox, $checkbox, false);
            
            // PID
            $pidLabel = Label::create($process['pid']);
            Box::append($rowBox, $pidLabel, true);
            
            // User列 - 使用session或user信息
            $userLabel = Label::create($process['session'] ?? "");
            Box::append($rowBox, $userLabel, true);
            
            // Command列 - 使用完整命令行或进程名
            $commandText = isset($process['command']) ? $process['command'] : $process['name'];
            // 限制命令长度以防止显示过长
            if (strlen($commandText) > 50) {
                $commandText = substr($commandText, 0, 47) . '...';
            }
            $commandLabel = Label::create($commandText);
            Box::append($rowBox, $commandLabel, true);
            
            Box::append($this->checkboxContainer, $rowBox, false);
            $this->checkboxRows[] = $rowBox; // 保存行引用
        }
        
        // 添加按钮
        $buttonBox = Box::newHorizontalBox();
        Box::setPadded($buttonBox, true);
        
        // 杀选中进程按钮
        $killBtn = Button::create("清除选择");
        Button::onClicked($killBtn, [$this, 'killSelectedProcesses']);
        Box::append($buttonBox, $killBtn, true);
        
        // 全选按钮
        $selectAllBtn = Button::create("全选");
        Button::onClicked($selectAllBtn, [$this, 'selectAllProcesses']);
        Box::append($buttonBox, $selectAllBtn, true);
        
        Box::append($this->checkboxContainer, $buttonBox, false);
    }
    
    /**
     * 全选进程
     */
    public function selectAllProcesses()
    {
        foreach ($this->checkboxes as $pid => $checkbox) {
            Checkbox::setChecked($checkbox, true);
        }
    }
    
    /**
     * 全不选进程
     */
    public function selectNoneProcesses()
    {
        foreach ($this->checkboxes as $pid => $checkbox) {
            Checkbox::setChecked($checkbox, false);
        }
    }
    
    /**
     * 杀选中的进程
     */
    public function killSelectedProcesses()
    {
        $selectedPids = [];
        
        foreach ($this->checkboxes as $pid => $checkbox) {
            if (Checkbox::checked($checkbox)) {
                $selectedPids[] = $pid;
            }
        }
        
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
                    
                    // 尝试获取完整命令行
                    $cmdOutput = [];
                    exec("wmic process where processid={$pid} get commandline", $cmdOutput);
                    $command = $processName; // 默认使用进程名
                    
                    // 如果有完整命令行，使用它
                    if (count($cmdOutput) > 1) {
                        $command = trim($cmdOutput[1]);
                    }
                    
                    $processes[] = [
                        'name' => $processName,
                        'pid' => $pid,
                        'session' => $sessionName,
                        'memory' => $memory,
                        'command' => $command
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
                    $command = $processName; // 默认值
                    
                    // 如果有完整命令行，使用它
                    if (!empty($cmdOutput)) {
                        $command = trim($cmdOutput[0]);
                    }
                    
                    $processes[] = [
                        'name' => $processName,
                        'pid' => $pid,
                        'session' => $user,
                        'memory' => $memory,
                        'command' => $command
                    ];
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