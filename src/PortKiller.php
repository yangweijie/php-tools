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
    private $containerIndex = 8; // 容器在父容器中的索引位置
    
    public function __construct()
    {
        // 创建垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);
        
        // 标题
        $title = Label::create("端口查杀工具");
        Box::append($this->box, $title, false);
        
        // 说明
        $desc = Label::create("输入端口号，点击查询按钮查看占用进程，点击杀按钮终止选中的进程");
        Box::append($this->box, $desc, false);
        
        // 端口输入框
        $portLabel = Label::create("端口号:");
        Box::append($this->box, $portLabel, false);
        
        $this->portEntry = Entry::create();
        Box::append($this->box, $this->portEntry, false);
        
        // 按钮容器
        $buttonBox = Box::newHorizontalBox();
        Box::setPadded($buttonBox, true);
        Box::append($this->box, $buttonBox, false);
        
        // 查询按钮
        $queryBtn = Button::create("查询占用进程");
        Button::onClicked($queryBtn, [$this, 'queryPort']);
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
        $resultLabel = Label::create("端口占用进程列表（勾选需要终止的进程）:");
        Box::append($this->box, $resultLabel, false);
        
        // 记住父容器
        $this->containerParent = $this->box;
        
        // 创建复选框容器
        $this->createCheckboxContainer();
        
        // 进程详情
        $detailsLabel = Label::create("端口详情:");
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
     * 查询端口
     */
    public function queryPort()
    {
        $port = Entry::text($this->portEntry);
        if (empty($port)) {
            MultilineEntry::setText($this->resultEntry, "请输入端口号");
            return;
        }
        
        // 查询端口占用进程
        $this->processes = $this->getPortProcessesInfo($port);
        
        // 清除旧的复选框
        $this->clearCheckboxes();
        
        // 显示进程列表
        $this->displayProcessList();
        
        // 显示详细结果
        $detailResult = $this->formatPortDetails($port, $this->processes);
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
            $label = Label::create("未找到占用该端口的进程");
            Box::append($this->checkboxContainer, $label, false);
            return;
        }
        
        // 创建表头
        $headerBox = Box::newHorizontalBox();
        Box::setPadded($headerBox, true);
        
        $selectHeaderLabel = Label::create("选择");
        Box::append($headerBox, $selectHeaderLabel, false);
        
        $protocolHeaderLabel = Label::create("协议");
        Box::append($headerBox, $protocolHeaderLabel, true);
        
        $localAddrHeaderLabel = Label::create("本地地址");
        Box::append($headerBox, $localAddrHeaderLabel, true);
        
        $remoteAddrHeaderLabel = Label::create("远程地址");
        Box::append($headerBox, $remoteAddrHeaderLabel, true);
        
        $pidHeaderLabel = Label::create("PID");
        Box::append($headerBox, $pidHeaderLabel, true);
        
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
            
            // 协议
            $protocolLabel = Label::create($process['protocol'] ?? "");
            Box::append($rowBox, $protocolLabel, true);
            
            // 本地地址
            $localAddrLabel = Label::create($process['local_address'] ?? "");
            Box::append($rowBox, $localAddrLabel, true);
            
            // 远程地址
            $remoteAddrLabel = Label::create($process['remote_address'] ?? "");
            Box::append($rowBox, $remoteAddrLabel, true);
            
            // PID
            $pidLabel = Label::create($process['pid']);
            Box::append($rowBox, $pidLabel, true);
            
            Box::append($this->checkboxContainer, $rowBox, false);
            $this->checkboxRows[] = $rowBox; // 保存行引用
        }
    }
    
    /**
     * 格式化端口详情
     */
    private function formatPortDetails($port, $processes)
    {
        if (empty($processes)) {
            return "未找到占用端口 {$port} 的进程";
        }
        
        $result = "端口 {$port} 占用情况:\n";
        $result .= "协议     本地地址                远程地址                状态     PID\n";
        $result .= "=============================================================================\n";
        
        foreach ($processes as $process) {
            $protocol = str_pad($process['protocol'] ?? "", 9);
            $localAddr = str_pad($process['local_address'] ?? "", 24);
            $remoteAddr = str_pad($process['remote_address'] ?? "", 24);
            $state = str_pad($process['state'] ?? "", 9);
            $pid = $process['pid'];
            
            $result .= "{$protocol} {$localAddr} {$remoteAddr} {$state} {$pid}\n";
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
        
        if ($os === 'WIN') {
            // Windows系统
            $command = "netstat -ano | findstr :{$port}";
            exec($command, $output);
            
            foreach ($output as $line) {
                // 格式: 协议 本地地址:端口 远程地址:端口 状态 PID
                $line = trim($line);
                $parts = preg_split('/\s+/', $line);
                
                if (count($parts) >= 5) {
                    $processes[] = [
                        'protocol' => $parts[0],
                        'local_address' => $parts[1],
                        'remote_address' => $parts[2],
                        'state' => $parts[3],
                        'pid' => $parts[4]
                    ];
                }
            }
        } elseif ($os === 'DAR' || $os === 'LIN') {
            // macOS或Linux系统
            $command = "lsof -i :{$port} -n -P";
            exec($command, $output);
            
            // 跳过第一行标题
            for ($i = 1; $i < count($output); $i++) {
                $line = trim($output[$i]);
                $parts = preg_split('/\s+/', $line);
                
                if (count($parts) >= 9) {
                    $processes[] = [
                        'protocol' => $parts[4],
                        'local_address' => $parts[8],
                        'remote_address' => $parts[8],
                        'state' => isset($parts[9]) ? $parts[9] : '',
                        'pid' => $parts[1]
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