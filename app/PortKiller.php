<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Button;
use Kingbes\Libui\Control;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Label;
use Kingbes\Libui\MultilineEntry;
use Kingbes\Libui\Checkbox;

class PortKiller
{
    private $box;
    private $portEntry;
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
        // $title = Label::create("端口查杀工具");
        // Box::append($this->box, $title, false);
        
        // 说明
        // $desc = Label::create("输入端口号，点击“查询占用进程”按钮查看占用进程，点击“清除选择”终止选中的进程");
        // Box::append($this->box, $desc, false);
        
        // 水平布局：端口输入框和查询按钮
        $inputBox = Box::newHorizontalBox();
        Box::setPadded($inputBox, true);
        Box::append($this->box, $inputBox, false);
        
        // 端口输入框标签
        $portLabel = Label::create("端口号:");
        Box::append($inputBox, $portLabel, false);
        
        // 端口输入框
        $this->portEntry = Entry::create();
        Box::append($inputBox, $this->portEntry, true);
        
        // 查询按钮
        $queryBtn = Button::create("查询占用进程");
        Button::onClicked($queryBtn, [$this, 'queryPort']);
        Box::append($inputBox, $queryBtn, false);
        
        // 结果标签
        $resultLabel = Label::create("端口占用进程列表（勾选需要终止的进程）:");
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
     * 查询端口
     */
    public function queryPort()
    {
        $port = Entry::text($this->portEntry);
        if (empty($port)) {
            return;
        }
        
        // 打印调试信息
        error_log("端口查询: {$port}");
        
        // 查询端口占用进程
        $this->processes = $this->getPortProcessesInfo($port);
        
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
        
        // 移除旧容器（在索引3的位置）
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
            $label = Label::create("未找到占用该端口的进程");
            Box::append($this->checkboxContainer, $label, false);
            return;
        }
        
        // 打印调试信息
        error_log("显示端口进程列表，数量: " . count($this->processes));
        
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
            
            // User - 对应协议或用户名
            $userLabel = Label::create($process['session'] ?? $process['protocol'] ?? "");
            Box::append($rowBox, $userLabel, true);
            
            // Command - 对应本地地址或进程名
            $commandText = isset($process['name']) ? $process['name'] : ($process['local_address'] ?? "");
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
        $this->queryPort();
    }
    
    /**
     * 获取端口进程详细信息
     */
    private function getPortProcessesInfo($port)
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        $command = '';
        $output = [];
        $processes = [];
        
        error_log("操作系统: {$os}, 查询端口: {$port}");
        
        if ($os === 'WIN') {
            // Windows系统
            $command = "netstat -ano | findstr :{$port}";
            exec($command, $output);
            
            foreach ($output as $line) {
                // 格式: 协议 本地地址:端口 远程地址:端口 状态 PID
                $line = trim($line);
                $parts = preg_split('/\s+/', $line);
                
                if (count($parts) >= 5) {
                    $pid = $parts[4];
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
            $command = "lsof -i :{$port} -n -P";
            exec($command, $output);
            error_log("执行命令: {$command}");
            error_log("端口命令输出: " . print_r($output, true));
            
            // 如果有输出且不只是标题行
            if (count($output) > 1) {
                for ($i = 1; $i < count($output); $i++) {
                    $line = trim($output[$i]);
                    $parts = preg_split('/\s+/', $line);
                    
                    if (count($parts) >= 9) {
                        $command = $parts[0]; // 进程名称
                        $pid = $parts[1];    // 进程 ID
                        $user = $parts[2];    // 用户名
                        $protocol = $parts[4]; // 协议
                        $localAddr = $parts[8]; // 本地地址
                        
                        // 获取进程的完整命令
                        $cmdOutput = [];
                        $cmdCmd = "ps -p {$pid} -o comm=";
                        exec($cmdCmd, $cmdOutput);
                        if (!empty($cmdOutput)) {
                            $command = $cmdOutput[0]; // 更新进程名称
                        }
                        
                        $processes[] = [
                            'protocol' => $protocol,
                            'local_address' => $localAddr,
                            'remote_address' => isset($parts[9]) ? $parts[9] : '',
                            'state' => isset($parts[9]) ? $parts[9] : '',
                            'pid' => $pid,
                            'session' => $user,   // 用于User列
                            'name' => $command    // 用于Command列
                        ];
                    }
                }
            }
        }
        
        // 打印结果调试信息
        error_log("端口查询结果: " . print_r($processes, true));
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