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
    private $selectAllBtn = null; // 存储全选按钮引用
    private $tableModel = null; // 存储表格模型引用
    private $allSelected = false; // 跟踪是否全选

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
        $queryBtn = Button::create("查询占用");
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
        Box::append($this->containerParent, $this->checkboxContainer, true);
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

        // 移除旧容器（在索引2的位置）
        Box::delete($this->containerParent, 2);

        // 添加新容器
        Box::append($this->containerParent, $newContainer, true);

        // 更新引用
        $this->checkboxContainer = $newContainer;
    }

    /**
     * 显示进程列表
     */
    private function displayProcessList()
    {
        error_log("端口查询: 开始显示进程列表，进程数量: " . count($this->processes));

        // 清除旧的内容
        $this->clearCheckboxes();

        if (empty($this->processes)) {
            $label = Label::create("No Data");
            Box::append($this->checkboxContainer, $label, false);
            return;
        }

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
                            return Table::createValueStr($process['session'] ?? $process['protocol'] ?? '');
                        case 3: // 命令列
                            return Table::createValueStr(isset($process['name']) ? $process['name'] : ($process['local_address'] ?? ''));
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
            error_log("端口查询: 创建表格，进程数量: " . count($this->processes));
            $table = Table::create($this->tableModel, -1);
            // 表格追加复选框列（第4个参数为0表示可编辑）
            Table::appendCheckboxColumn($table, "", 0, 0);
            // 表格追加文本列
            Table::appendTextColumn($table, "PID", 1, 100);
            // 表格追加文本列
            Table::appendTextColumn($table, "User", 2, 150);
            // 表格追加文本列（使用-2填充剩余空间）
            Table::appendTextColumn($table, "Command", 3, -2);

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
        error_log("端口查询: 更新表格复选框状态");

        // 如果有表格模型引用，则更新所有行的复选框状态
        if ($this->tableModel !== null) {
            for ($i = 0; $i < count($this->processes); $i++) {
                Table::modelRowChanged($this->tableModel, $i);
            }
        } else {
            error_log("端口查询: 表格模型引用为空，无法更新复选框状态");
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
        $this->queryPort();
    }

    /**
     * 获取端口进程详细信息
     */
    private function getPortProcessesInfo($port)
    {
        $os = App::getOperatingSystem();
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

                            // 使用PowerShell获取完整命令行
                            $cmdOutput = [];
                            $psCommand = "powershell.exe -Command \"Get-WmiObject Win32_Process -Filter \\\"ProcessId = {$pid}\\\" | Select-Object CommandLine | Format-List\"";
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
            exec($command, $output);
            error_log("执行命令: {$command}");
            error_log("端口命令输出: " . print_r($output, true));

            // 如果有输出且不只是标题行
            if (count($output) > 1) {
                for ($i = 1; $i < count($output); $i++) {
                    $line = trim($output[$i]);
                    // 跳过标题行和其他无关行
                    if (empty($line) || strpos($line, 'COMMAND') !== false) {
                        continue;
                    }

                    $parts = preg_split('/\s+/', $line);

                    // lsof 输出格式: COMMAND PID USER FD TYPE DEVICE SIZE/OFF NODE NAME
                    // 我们需要至少9个字段
                    if (count($parts) >= 9) {
                        $processName = $parts[0]; // 进程名称
                        $pid = $parts[1];    // 进程 ID
                        $user = $parts[2];    // 用户名
                        // $fd = $parts[3];   // 文件描述符
                        // $type = $parts[4]; // 类型 (IPv4/IPv6)
                        // $device = $parts[5]; // 设备
                        // $size = $parts[6]; // 大小
                        // $node = $parts[7]; // 节点
                        $protocol = isset($parts[4]) ? $parts[4] : ''; // 协议类型
                        $localAddr = isset($parts[8]) ? $parts[8] : ''; // 本地地址和端口

                        // 获取进程的完整命令
                        $cmdOutput = [];
                        $cmdCmd = "ps -p {$pid} -o command= 2>/dev/null";
                        exec($cmdCmd, $cmdOutput);
                        $commandLine = $processName; // 默认使用进程名称
                        if (!empty($cmdOutput)) {
                            $commandLine = trim($cmdOutput[0]); // 更新为完整命令行
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
        $os = App::getOperatingSystem();
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
