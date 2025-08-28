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
    private $containerIndex = 8; // 容器在父容器中的索引位置
    
    public function __construct()
    {
        // 创建垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);
        
        // 标题
        $title = Label::create("进程查杀工具");
        Box::append($this->box, $title, false);
        
        // 说明
        $desc = Label::create("输入进程名或PID，点击查询按钮查看进程信息，点击杀按钮终止选中的进程");
        Box::append($this->box, $desc, false);
        
        // 进程输入框
        $processLabel = Label::create("进程名或PID:");
        Box::append($this->box, $processLabel, false);
        
        $this->processEntry = Entry::create();
        Box::append($this->box, $this->processEntry, false);
        
        // 按钮容器
        $buttonBox = Box::newHorizontalBox();
        Box::setPadded($buttonBox, true);
        Box::append($this->box, $buttonBox, false);
        
        // 查询按钮
        $queryBtn = Button::create("查询进程");
        Button::onClicked($queryBtn, [$this, 'queryProcess']);
        Box::append($buttonBox, $queryBtn, true);
        
        // 杀按钮
        $killBtn = Button::create("杀选中进程");
        Button::onClicked($killBtn, [$this, 'killSelectedProcesses']);
        Box::append($buttonBox, $killBtn, true);
        
        // 全选按钮
        $selectAllBtn = Button::create("全选");
        Button::onClicked($selectAllBtn, [$this, 'selectAllProcesses']);
        Box::append($buttonBox, $selectAllBtn, true);
        
        // 全不选按钮
        $selectNoneBtn = Button::create("全不选");
        Button::onClicked($selectNoneBtn, [$this, 'selectNoneProcesses']);
        Box::append($buttonBox, $selectNoneBtn, true);
        
        // 结果标签
        $resultLabel = Label::create("进程列表（勾选需要终止的进程）:");
        Box::append($this->box, $resultLabel, false);
        
        // 记住父容器
        $this->containerParent = $this->box;
        
        // 创建复选框容器
        $this->createCheckboxContainer();
        
        // 进程详情
        $detailsLabel = Label::create("进程详情:");
        Box::append($this->box, $detailsLabel, false);
        
        // 详细结果显示
        $this->resultEntry = MultilineEntry::create();
        MultilineEntry::setReadOnly($this->resultEntry, true);
        Box::append($this->box, $this->resultEntry, true);
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
            MultilineEntry::setText($this->resultEntry, "请输入进程名或PID");
            return;
        }
        
        // 查询进程
        $this->processes = $this->getProcessesInfo($process);
        
        // 清除旧的复选框
        $this->clearCheckboxes();
        
        // 显示进程列表
        $this->displayProcessList();
        
        // 显示详细结果
        $detailResult = $this->formatProcessDetails($this->processes);
        MultilineEntry::setText($this->resultEntry, $detailResult);
    }
    
    /**
     * 清除复选框
     */
    private function clearCheckboxes()
    {
        // 清空复选框引用
        $this->checkboxes = [];
        $this->checkboxRows = [];
        
        try {
            // 尝试删除旧容器
            Box::delete($this->containerParent, $this->containerIndex);
        } catch (\Exception $e) {
            // 如果删除失败，忽略错误
        }
        
        // 创建新容器
        $this->createCheckboxContainer();
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
        
        // 创建表头
        $headerBox = Box::newHorizontalBox();
        Box::setPadded($headerBox, true);
        
        $selectHeaderLabel = Label::create("选择");
        Box::append($headerBox, $selectHeaderLabel, false);
        
        $pidHeaderLabel = Label::create("PID");
        Box::append($headerBox, $pidHeaderLabel, true);
        
        $nameHeaderLabel = Label::create("映像名称");
        Box::append($headerBox, $nameHeaderLabel, true);
        
        $sessionHeaderLabel = Label::create("会话名称");
        Box::append($headerBox, $sessionHeaderLabel, true);
        
        $memoryHeaderLabel = Label::create("内存使用量");
        Box::append($headerBox, $memoryHeaderLabel, true);
        
        Box::append($this->checkboxContainer, $headerBox, false);
        
        // 添加分隔行
        $separator = Label::create("------------------------------------------------------");
        Box::append($this->checkboxContainer, $separator, false);
        
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
            
            // 映像名称
            $nameLabel = Label::create($process['name']);
            Box::append($rowBox, $nameLabel, true);
            
            // 会话名称
            $sessionLabel = Label::create($process['session'] ?? "");
            Box::append($rowBox, $sessionLabel, true);
            
            // 内存使用量
            $memoryLabel = Label::create($process['memory'] ?? "");
            Box::append($rowBox, $memoryLabel, true);
            
            Box::append($this->checkboxContainer, $rowBox, false);
            $this->checkboxRows[] = $rowBox; // 保存行引用
        }
    }
    
    /**
     * 格式化进程详情
     */
    private function formatProcessDetails($processes)
    {
        if (empty($processes)) {
            return "未找到匹配的进程";
        }
        
        $result = "映像名称                   PID   会话名称         内存使用量\n";
        $result .= "================================================\n";
        
        foreach ($processes as $process) {
            $name = str_pad($process['name'], 25);
            $pid = str_pad($process['pid'], 6);
            $session = str_pad($process['session'] ?? "", 16);
            $memory = $process['memory'] ?? "";
            
            $result .= "{$name} {$pid} {$session} {$memory}\n";
        }
        
        return $result;
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
            MultilineEntry::setText($this->resultEntry, "未选中任何进程");
            return;
        }
        
        $results = [];
        foreach ($selectedPids as $pid) {
            $results[] = $this->killProcessById($pid);
        }
        
        // 显示结果
        MultilineEntry::setText($this->resultEntry, implode("\n", $results));
        
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
                    $processes[] = [
                        'name' => trim($parts[0]),
                        'pid' => trim($parts[1]),
                        'session' => trim($parts[2]),
                        'memory' => trim($parts[4])
                    ];
                }
            }
        } elseif ($os === 'DAR' || $os === 'LIN') {
            // macOS或Linux系统
            if (is_numeric($process)) {
                // 按PID查询
                $command = "ps -p {$process} -o pid,comm,user,%mem";
            } else {
                // 按进程名查询
                $command = "ps -e | grep {$process} | grep -v grep";
            }
            
            exec($command, $output);
            
            // 解析Linux/macOS输出
            $header = true;
            foreach ($output as $line) {
                if (empty(trim($line))) continue;
                if ($header) {
                    $header = false;
                    continue;
                }
                
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 4) {
                    $processes[] = [
                        'pid' => $parts[0],
                        'name' => $parts[1],
                        'session' => $parts[2],
                        'memory' => $parts[3] . '%'
                    ];
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