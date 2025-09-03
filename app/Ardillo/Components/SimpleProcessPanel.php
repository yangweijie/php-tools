<?php

namespace App\Ardillo\Components;

use App\Ardillo\Managers\ProcessManager;

/**
 * Simple process panel that returns native Ardillo controls
 */
class SimpleProcessPanel implements ComponentInterface
{
    private ProcessManager $processManager;
    private mixed $widget = null;
    private bool $initialized = false;
    private ?\Closure $exitCallback = null;
    private mixed $processEntry = null;
    private mixed $resultsEntry = null;

    public function __construct(ProcessManager $processManager, ?\Closure $exitCallback = null)
    {
        $this->processManager = $processManager;
        $this->exitCallback = $exitCallback;
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // Create main vertical box
        $vbox = new \Ardillo\VerticalBox();
        
        // Add title
        $titleLabel = new \Ardillo\Label("⚙️ 进程管理工具");
        $vbox->append($titleLabel, false);
        
        // Add spacing
        $spacer1 = new \Ardillo\Label("");
        $vbox->append($spacer1, false);
        
        // Add input section with more visible elements
        $inputSectionLabel = new \Ardillo\Label("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $vbox->append($inputSectionLabel, false);
        
        $inputLabel = new \Ardillo\Label("📝 进程查询 - 输入进程名或PID (留空查询所有进程):");
        $vbox->append($inputLabel, false);
        
        // Try using a different approach - create input field separately
        $this->processEntry = new \Ardillo\Entry();
        $this->processEntry->setText("在此输入进程名或PID...");
        $vbox->append($this->processEntry, false);
        
        // Add button section
        $buttonSectionLabel = new \Ardillo\Label("操作按钮:");
        $vbox->append($buttonSectionLabel, false);
        
        $buttonBox = new \Ardillo\HorizontalBox();
        $queryButton = new \Ardillo\Button("🔍 查询进程");
        $refreshButton = new \Ardillo\Button("🔄 刷新列表");
        $clearButton = new \Ardillo\Button("🗑️ 清空输入");
        
        $buttonBox->append($queryButton, false);
        $buttonBox->append($refreshButton, false);
        $buttonBox->append($clearButton, false);
        $vbox->append($buttonBox, false);
        
        // Add spacing
        $spacer2 = new \Ardillo\Label("");
        $vbox->append($spacer2, false);
        
        // Add results label
        $resultsLabel = new \Ardillo\Label("📊 查询结果:");
        $vbox->append($resultsLabel, false);
        
        // Add results area
        $this->resultsEntry = new \Ardillo\MultilineEntry();
        $this->resultsEntry->setText($this->getInitialResults());
        $this->resultsEntry->setReadOnly(true);
        $vbox->append($this->resultsEntry, true);
        
        // Add action buttons (using Labels as clickable buttons)
        $buttonBox = new \Ardillo\HorizontalBox();
        
        // Create clickable labels that look like buttons
        $killLabel = new \Ardillo\Label("[ ❌ 终止选中进程 ]");
        $killAllLabel = new \Ardillo\Label("[ ⚠️ 终止所有匹配 ]");
        $refreshLabel = new \Ardillo\Label("[ 🔄 刷新数据 ]");
        $clearLabel = new \Ardillo\Label("[ 🗑️ 清空结果 ]");
        $queryAllLabel = new \Ardillo\Label("[ 📋 查询所有进程 ]");
        
        $buttonBox->append($killLabel, false);
        $buttonBox->append($killAllLabel, false);
        $buttonBox->append($refreshLabel, false);
        $buttonBox->append($clearLabel, false);
        $buttonBox->append($queryAllLabel, false);
        
        $vbox->append($buttonBox, false);
        
        // Add help text
        $helpLabel = new \Ardillo\Label("💡 提示: 使用 Cmd+Q 或 Ctrl+C 退出应用程序");
        $vbox->append($helpLabel, false);
        
        $this->widget = $vbox;
        $this->initialized = true;
    }

    public function getWidget(): mixed
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->widget;
    }

    public function getControl(): mixed
    {
        return $this->getWidget();
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function cleanup(): void
    {
        $this->widget = null;
        $this->initialized = false;
    }

    /**
     * Get initial results to display
     */
    private function getInitialResults(): string
    {
        try {
            // Query php processes initially
            $processes = $this->processManager->query('php');
            return $this->formatProcessResults($processes);
        } catch (\Exception $e) {
            return "⚙️ 进程查询工具\n\n" .
                   "使用说明:\n" .
                   "• 在上方输入框中输入进程名 (如: php, node, nginx)\n" .
                   "• 或输入PID (如: 1234)\n" .
                   "• 留空可查询所有进程\n" .
                   "• 点击 '🔍 查询' 按钮开始查询\n" .
                   "• 点击 '🔄 刷新' 按钮更新结果\n\n" .
                   "⚠️ 初始化查询失败: " . $e->getMessage() . "\n\n" .
                   "💡 提示: 某些系统进程可能需要管理员权限才能查看详细信息";
        }
    }

    /**
     * Format process results for display
     */
    private function formatProcessResults(array $processes): string
    {
        if (empty($processes)) {
            return "❌ 未找到进程信息\n\n" .
                   "可能原因:\n" .
                   "• 指定进程不存在\n" .
                   "• 需要管理员权限\n" .
                   "• 系统命令执行失败\n\n" .
                   "请尝试:\n" .
                   "• 留空进程名查询所有进程\n" .
                   "• 检查输入的进程名是否正确\n" .
                   "• 以管理员权限运行应用程序";
        }

        $result = "📊 找到 " . count($processes) . " 个进程\n\n";
        $result .= "💡 操作说明: 记住要终止的PID，然后使用下方的操作按钮\n\n";
        $result .= sprintf("%-8s %-15s %-10s %-8s %-10s %-10s %s\n", 
            "PID", "进程名", "用户", "CPU%", "内存", "状态", "命令行");
        $result .= str_repeat("-", 80) . "\n";

        foreach ($processes as $process) {
            $result .= sprintf("%-8s %-15s %-10s %-8s %-10s %-10s %s\n",
                $process->getPid(),
                $this->truncateString($process->getName(), 15),
                $this->truncateString($process->getUser() ?: '-', 10),
                $this->truncateString($process->getCpuUsage() ?: '-', 8),
                $this->truncateString($process->getMemoryUsage() ?: '-', 10),
                $this->truncateString($process->getStatus() ?: '-', 10),
                $this->truncateString($process->getCommandLine() ?: '-', 30)
            );
        }

        $result .= "\n💡 提示: 要终止进程，请记住对应的PID号码";
        return $result;
    }

    /**
     * Truncate string to specified length
     */
    private function truncateString(string $str, int $length): string
    {
        if (strlen($str) <= $length) {
            return $str;
        }
        return substr($str, 0, $length - 3) . '...';
    }

    /**
     * Query processes based on current input
     */
    public function queryProcesses(): void
    {
        if (!$this->processEntry || !$this->resultsEntry) {
            return;
        }

        try {
            $processInput = $this->processEntry->getText();
            $this->resultsEntry->setText("🔍 正在查询进程 '$processInput'...");
            
            $processes = $this->processManager->query($processInput);
            $results = $this->formatProcessResults($processes);
            
            $this->resultsEntry->setText($results);
        } catch (\Exception $e) {
            $errorMsg = "❌ 查询失败: " . $e->getMessage() . "\n\n";
            $errorMsg .= "请检查:\n";
            $errorMsg .= "• 进程名格式是否正确\n";
            $errorMsg .= "• 是否有足够的系统权限\n";
            $errorMsg .= "• 系统命令是否可用";
            
            $this->resultsEntry->setText($errorMsg);
        }
    }

    /**
     * Refresh current results
     */
    public function refreshResults(): void
    {
        $this->queryProcesses();
    }

    /**
     * Kill process by PID (simulated functionality)
     */
    public function killProcessByPid(string $pid): string
    {
        try {
            $result = $this->processManager->killSelected([$pid]);
            if ($result['success']) {
                return "✅ 成功终止进程 PID: $pid";
            } else {
                return "❌ 终止进程失败 PID: $pid - " . $result['message'];
            }
        } catch (\Exception $e) {
            return "❌ 终止进程出错 PID: $pid - " . $e->getMessage();
        }
    }

    /**
     * Kill all matching processes
     */
    public function killAllMatching(): string
    {
        try {
            if (!$this->processEntry) {
                return "❌ 无法获取查询条件";
            }
            
            $processInput = $this->processEntry->getText();
            if (empty($processInput)) {
                return "❌ 请先输入进程名进行查询，然后再终止所有匹配的进程";
            }
            
            $processes = $this->processManager->query($processInput);
            if (empty($processes)) {
                return "❌ 未找到匹配的进程: $processInput";
            }
            
            $pids = array_map(function($process) {
                return $process->getPid();
            }, $processes);
            
            $result = $this->processManager->killSelected($pids);
            return "🔄 批量终止结果: " . $result['message'];
            
        } catch (\Exception $e) {
            return "❌ 批量终止进程出错: " . $e->getMessage();
        }
    }

    /**
     * Clear results
     */
    public function clearResults(): void
    {
        if ($this->resultsEntry) {
            $this->resultsEntry->setText("🗑️ 结果已清空\n\n请重新查询进程信息。");
        }
    }

    /**
     * Query all processes
     */
    public function queryAllProcesses(): void
    {
        if ($this->processEntry) {
            $this->processEntry->setText("");
        }
        $this->queryProcesses();
    }

    /**
     * Simulate button click functionality
     * This method demonstrates how to handle different actions
     */
    public function handleAction(string $action, string $parameter = ''): string
    {
        switch ($action) {
            case 'kill':
                if (empty($parameter)) {
                    return "❌ 请提供要终止的PID";
                }
                return $this->killProcessByPid($parameter);
            
            case 'kill_all':
                return $this->killAllMatching();
            
            case 'refresh':
                $this->refreshResults();
                return "🔄 数据已刷新";
            
            case 'clear':
                $this->clearResults();
                return "🗑️ 结果已清空";
            
            case 'query_all':
                $this->queryAllProcesses();
                return "📋 正在查询所有进程...";
            
            default:
                return "❓ 未知操作: $action";
        }
    }
}