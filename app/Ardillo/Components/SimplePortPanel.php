<?php

namespace App\Ardillo\Components;

use App\Ardillo\Managers\PortManager;
use App\Ardillo\Core\ArdilloApplication;

/**
 * Simple port panel that returns native Ardillo controls
 */
class SimplePortPanel implements ComponentInterface
{
    private PortManager $portManager;
    private mixed $widget = null;
    private bool $initialized = false;
    private ?\Closure $exitCallback = null;
    private mixed $portEntry = null;
    private mixed $resultsEntry = null;

    public function __construct(PortManager $portManager, ?\Closure $exitCallback = null)
    {
        $this->portManager = $portManager;
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
        $titleLabel = new \Ardillo\Label("🔌 端口管理工具");
        $vbox->append($titleLabel, false);
        
        // Add spacing
        $spacer1 = new \Ardillo\Label("");
        $vbox->append($spacer1, false);
        
        // Add input section with more visible elements
        $inputSectionLabel = new \Ardillo\Label("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $vbox->append($inputSectionLabel, false);
        
        $inputLabel = new \Ardillo\Label("📝 端口查询 - 输入端口号 (留空查询所有端口):");
        $vbox->append($inputLabel, false);
        
        // Try using a different approach - create input field separately
        $this->portEntry = new \Ardillo\Entry();
        $this->portEntry->setText("在此输入端口号...");
        $vbox->append($this->portEntry, false);
        
        // Add button section
        $buttonSectionLabel = new \Ardillo\Label("操作按钮:");
        $vbox->append($buttonSectionLabel, false);
        
        $buttonBox = new \Ardillo\HorizontalBox();
        $queryButton = new \Ardillo\Button("🔍 查询端口");
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
        $refreshLabel = new \Ardillo\Label("[ 🔄 刷新数据 ]");
        $clearLabel = new \Ardillo\Label("[ 🗑️ 清空结果 ]");
        $queryAllLabel = new \Ardillo\Label("[ 📋 查询所有端口 ]");
        
        $buttonBox->append($killLabel, false);
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
            // Query all ports initially to show data
            $ports = $this->portManager->query('');
            return $this->formatPortResults($ports);
        } catch (\Exception $e) {
            return "🔍 端口查询工具\n\n" .
                   "使用说明:\n" .
                   "• 在上方输入框中输入端口号 (如: 8080, 3000)\n" .
                   "• 留空可查询所有活动端口\n" .
                   "• 点击 '🔍 查询' 按钮开始查询\n" .
                   "• 点击 '🔄 刷新' 按钮更新结果\n\n" .
                   "⚠️ 初始化查询失败: " . $e->getMessage() . "\n\n" .
                   "💡 提示: 某些系统端口可能需要管理员权限才能查看详细信息";
        }
    }

    /**
     * Format port results for display
     */
    private function formatPortResults(array $ports): string
    {
        if (empty($ports)) {
            return "❌ 未找到端口信息\n\n" .
                   "可能原因:\n" .
                   "• 指定端口未被占用\n" .
                   "• 需要管理员权限\n" .
                   "• 系统命令执行失败\n\n" .
                   "请尝试:\n" .
                   "• 留空端口号查询所有端口\n" .
                   "• 检查输入的端口号是否正确\n" .
                   "• 以管理员权限运行应用程序";
        }

        $result = "📊 找到 " . count($ports) . " 个端口\n\n";
        $result .= "💡 操作说明: 记住要终止的PID，然后使用下方的操作按钮\n\n";
        $result .= sprintf("%-8s %-8s %-10s %-20s %-15s %-12s %s\n", 
            "端口", "PID", "协议", "本地地址", "远程地址", "状态", "进程名");
        $result .= str_repeat("-", 80) . "\n";

        foreach ($ports as $port) {
            $result .= sprintf("%-8s %-8s %-10s %-20s %-15s %-12s %s\n",
                $port->getPort(),
                $port->getPid(),
                strtoupper($port->getProtocol()),
                $this->truncateString($port->getLocalAddress(), 20),
                $this->truncateString($port->getRemoteAddress() ?: '-', 15),
                $port->getState() ?: '-',
                $this->truncateString($port->getProcessName() ?: 'Unknown', 15)
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
     * Query ports based on current input
     */
    public function queryPorts(): void
    {
        if (!$this->portEntry || !$this->resultsEntry) {
            return;
        }

        try {
            $portInput = $this->portEntry->getText();
            $this->resultsEntry->setText("🔍 正在查询端口 '$portInput'...");
            
            $ports = $this->portManager->query($portInput);
            $results = $this->formatPortResults($ports);
            
            $this->resultsEntry->setText($results);
        } catch (\Exception $e) {
            $errorMsg = "❌ 查询失败: " . $e->getMessage() . "\n\n";
            $errorMsg .= "请检查:\n";
            $errorMsg .= "• 端口号格式是否正确 (1-65535)\n";
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
        $this->queryPorts();
    }

    /**
     * Kill process by PID (simulated functionality)
     */
    public function killProcessByPid(string $pid): string
    {
        try {
            $result = $this->portManager->killSelected([$pid]);
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
     * Clear results
     */
    public function clearResults(): void
    {
        if ($this->resultsEntry) {
            $this->resultsEntry->setText("🗑️ 结果已清空\n\n请重新查询端口信息。");
        }
    }

    /**
     * Query all ports
     */
    public function queryAllPorts(): void
    {
        if ($this->portEntry) {
            $this->portEntry->setText("");
        }
        $this->queryPorts();
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
            
            case 'refresh':
                $this->refreshResults();
                return "🔄 数据已刷新";
            
            case 'clear':
                $this->clearResults();
                return "🗑️ 结果已清空";
            
            case 'query_all':
                $this->queryAllPorts();
                return "📋 正在查询所有端口...";
            
            default:
                return "❓ 未知操作: $action";
        }
    }
}