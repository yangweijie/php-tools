<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiHBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiGroup;
use Kingbes\Libui\SDK\LibuiMultilineEntry;
use Kingbes\Libui\SDK\LibuiProgressBar;
use Kingbes\Libui\SDK\LibuiCheckbox;
use Kingbes\Libui\SDK\LibuiApplication;

class SQLite2MySQLTab
{
    private LibuiVBox $box;
    private LibuiEntry $sqliteFileEntry;
    private LibuiEntry $mysqlDsnEntry;
    private LibuiEntry $batchSizeEntry;
    private LibuiEntry $excludeTablesEntry;
    private LibuiCheckbox $dropExistingCheckbox;
    private LibuiMultilineEntry $outputArea;
    private LibuiProgressBar $progressBar;
    private LibuiButton $convertButton;
    private LibuiLabel $statusLabel;

    public function __construct()
    {
        // 创建主垂直容器
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);

        // 添加标题
        $titleLabel = new LibuiLabel("SQLite 到 MySQL 转换器");
        $this->box->append($titleLabel, false);

        // 水平布局：端口输入框和查询按钮
        $inputBox = new LibuiHBox();
        $inputBox->setPadded(true);
        $this->box->append($inputBox, false);

        // 添加检查文件按钮
        $checkFileButton = new LibuiButton("检查/下载必要文件");
        $checkFileButton->onClick(function () {
            $this->checkAndDownloadPhar();
        });
        $inputBox->append($checkFileButton, false);

        // 添加状态标签
        $this->statusLabel = new LibuiLabel("正在检查必要的文件...");
        $inputBox->append($this->statusLabel, false);

        // 添加说明标签
        $descLabel = new LibuiLabel("将 SQLite 数据库文件同步到 MySQL 远程数据库");
        $this->box->append($descLabel, false);

        // 创建输入区域
        $this->addInputControls($this->box);

        // 创建输出区域
        $this->addOutputControls($this->box);

        // 检查并下载 sqlite2mysql.phar 文件
        $this->checkAndDownloadPhar();
    }

    /**
     * 检查并下载 sqlite2mysql.phar 文件
     */
    private function checkAndDownloadPhar()
    {
        $this->statusLabel->setText("正在检查必要的文件...");

        $pharPath = __DIR__ . '/../scripts/sqlite2mysql.phar';
        
        // 检查文件是否存在
        if (!file_exists($pharPath)) {
            $this->statusLabel->setText("正在下载 sqlite2mysql.phar 文件...");
            LibuiApplication::getInstance()->queueMain(function() use($pharPath) {
                if ($this->downloadPharFile($pharPath)) {
                    $this->statusLabel->setText("文件已下载完成");
                } else {
                    $this->statusLabel->setText("警告: 无法下载 sqlite2mysql.phar 文件");
                }
            });
        } else {
            LibuiApplication::getInstance()->queueMain(fn() => $this->statusLabel->setText("必要的文件已就绪"));
        }
    }

    /**
     * 下载 sqlite2mysql.phar 文件
     */
    private function downloadPharFile($pharPath)
    {
        $downloadUrl = 'https://xget.xi-xu.me/gh/yangweijie/SQLite2MySQL/releases/download/v1.0.0/sqlite2mysql.phar';
        
        // 使用 cURL 下载文件
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30秒超时
        
        $pharData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // 检查下载是否成功
        if ($httpCode === 200 && $pharData !== false) {
            // 确保 scripts 目录存在
            $scriptsDir = dirname($pharPath);
            if (!is_dir($scriptsDir)) {
                mkdir($scriptsDir, 0755, true);
            }
            
            // 保存文件
            if (file_put_contents($pharPath, $pharData) !== false) {
                // 设置文件权限
                chmod($pharPath, 0755);
                return true;
            }
        } else {
            // 下载失败，记录错误但不中断程序执行
            error_log("Failed to download sqlite2mysql.phar from $downloadUrl. HTTP code: $httpCode, Error: $error");
        }
        
        return false;
    }

    private function addInputControls(LibuiVBox $container)
    {
        // 输入控件组
        $inputGroup = new LibuiGroup("配置参数");
        $container->append($inputGroup, false);

        $inputBox = new LibuiVBox();
        $inputBox->setPadded(true);
        $inputGroup->append($inputBox, false);

        // SQLite 文件路径标签
        $sqliteLabel = new LibuiLabel("SQLite 文件路径:");
        $inputBox->append($sqliteLabel, false);

        // 创建水平容器用于放置输入框和选择按钮
        $sqliteFileBox = new LibuiHBox();
        $sqliteFileBox->setPadded(true);
        $inputBox->append($sqliteFileBox, false);

        // SQLite 文件路径输入框
        $this->sqliteFileEntry = new LibuiEntry();
        $this->sqliteFileEntry->setText("./test.db");
        $sqliteFileBox->append($this->sqliteFileEntry, true);

        // SQLite 文件选择按钮
        $sqliteFileButton = new LibuiButton("选择文件");
        $sqliteFileButton->onClick(function () {
            $this->selectSqliteFile();
        });
        $sqliteFileBox->append($sqliteFileButton, false);

        // MySQL DSN 标签
        $mysqlLabel = new LibuiLabel("MySQL 连接字符串:");
        $inputBox->append($mysqlLabel, false);

        // MySQL DSN 输入框
        $this->mysqlDsnEntry = new LibuiEntry();
        $this->mysqlDsnEntry->setText("mysql://root:password@localhost:3306/database_name");
        $inputBox->append($this->mysqlDsnEntry, false);

        // 批处理大小标签
        $batchSizeLabel = new LibuiLabel("批处理大小:");
        $inputBox->append($batchSizeLabel, false);

        // 批处理大小输入框
        $this->batchSizeEntry = new LibuiEntry();
        $this->batchSizeEntry->setText("1000");
        $inputBox->append($this->batchSizeEntry, false);

        // 排除表标签
        $excludeLabel = new LibuiLabel("排除表 (逗号分隔):");
        $inputBox->append($excludeLabel, false);

        // 排除表输入框
        $this->excludeTablesEntry = new LibuiEntry();
        $this->excludeTablesEntry->setText("");
        $inputBox->append($this->excludeTablesEntry, false);

        // 删除现有表复选框
        $this->dropExistingCheckbox = new LibuiCheckbox("转换前删除 MySQL 中的现有表");
        $this->dropExistingCheckbox->setChecked(false);
        $inputBox->append($this->dropExistingCheckbox, false);

        // 转换按钮
        $this->convertButton = new LibuiButton("开始转换");
        $this->convertButton->onClick(function () {
            $this->startConversion();
        });
        $inputBox->append($this->convertButton, false);
    }

    private function addOutputControls(LibuiVBox $container)
    {
        // 输出控件组
        $outputGroup = new LibuiGroup("转换进度和输出");
        $container->append($outputGroup, true);

        $outputBox = new LibuiVBox();
        $outputBox->setPadded(true);
        $outputGroup->append($outputBox, false);

        // 进度条
        $this->progressBar = new LibuiProgressBar();
        $this->progressBar->setValue(0);
        $outputBox->append($this->progressBar, false);

        // 输出区域标签
        $outputLabel = new LibuiLabel("输出信息:");
        $outputBox->append($outputLabel, false);

        // 多行输出区域
        $this->outputArea = new LibuiMultilineEntry();
        $this->outputArea->setText("等待开始转换...\n");
        $outputBox->append($this->outputArea, true);
    }

    private function startConversion()
    {
        try {
            // 获取输入参数
            $sqliteFile = $this->sqliteFileEntry->getText();
            $mysqlDsn = $this->mysqlDsnEntry->getText();
            $batchSize = (int)$this->batchSizeEntry->getText();
            $excludeTables = $this->excludeTablesEntry->getText();
            $dropExisting = $this->dropExistingCheckbox->isChecked();

            // 验证必需参数
            if (empty($sqliteFile)) {
                $this->appendOutput("错误: 请指定 SQLite 文件路径\n");
                return;
            }

            if (empty($mysqlDsn)) {
                $this->appendOutput("错误: 请指定 MySQL 连接字符串\n");
                return;
            }

            if (!file_exists($sqliteFile) || !is_readable($sqliteFile)) {
                $this->appendOutput("错误: SQLite 文件不存在或不可读\n");
                return;
            }

            // 检查 sqlite2mysql.phar 文件是否存在
            $pharPath = __DIR__ . '/../scripts/sqlite2mysql.phar';
            if (!file_exists($pharPath)) {
                $this->appendOutput("错误: sqlite2mysql.phar 文件不存在，请重新启动应用程序以自动下载\n");
                return;
            }

            // 禁用转换按钮
            // 注意：SDK中可能没有disable方法，需要重新创建按钮

            // 重置进度条
            $this->progressBar->setValue(0);

            // 清空输出区域
            $this->outputArea->setText("开始转换...\n");

            // 构建命令行参数
            $cmd = "php " . escapeshellarg($pharPath);
            $cmd .= " --sqlite=" . escapeshellarg($sqliteFile);
            $cmd .= " --mysql=" . escapeshellarg($mysqlDsn);
            $cmd .= " --batch-size=" . escapeshellarg((string)$batchSize);

            if ($dropExisting) {
                $cmd .= " --drop-existing";
            }

            if (!empty($excludeTables)) {
                $cmd .= " --exclude-tables=" . escapeshellarg($excludeTables);
            }

            // 执行转换命令
            $this->appendOutput("执行命令: $cmd\n");

            // 使用异步方式执行命令，以便更新进度
            $this->executeCommandAsync($cmd);

        } catch (\Exception $e) {
            // 显示错误信息
            // 注意：需要获取主窗口引用
            global $application;
            if ($application && $application->getWindow()) {
                \Kingbes\Libui\Window::msgBoxError(
                    $application->getWindow()->getHandle(),
                    "错误",
                    "开始转换时发生错误: " . $e->getMessage()
                );
            }

            // 重新启用转换按钮
            // 注意：SDK中可能没有enable方法，需要重新创建按钮
        }
    }

    private function executeCommandAsync($cmd)
    {
        // 在后台执行命令
        $process = proc_open(
            $cmd,
            [
                0 => ["pipe", "r"],  // stdin
                1 => ["pipe", "w"],  // stdout
                2 => ["pipe", "w"]   // stderr
            ],
            $pipes
        );

        if (!is_resource($process)) {
            $this->appendOutput("错误: 无法启动转换进程\n");
            // 重新启用转换按钮
            return;
        }

        // 关闭 stdin
        fclose($pipes[0]);

        // 异步读取输出
        $this->readProcessOutput($process, $pipes);
    }

    private function readProcessOutput($process, $pipes)
    {
        // 读取 stdout 和 stderr
        $stdout = $pipes[1];
        $stderr = $pipes[2];

        // 设置非阻塞模式
        stream_set_blocking($stdout, false);
        stream_set_blocking($stderr, false);

        // 持续读取输出直到进程结束
        $outputBuffer = "";
        $errorBuffer = "";

        // 进度跟踪变量
        $totalTables = 0;
        $processedTables = 0;
        $totalRecords = 0;
        $insertedRecords = 0;

        do {
            // 读取 stdout
            $stdoutData = fread($stdout, 4096);
            if ($stdoutData !== false && strlen($stdoutData) > 0) {
                $outputBuffer .= $stdoutData;
                $this->appendOutput($stdoutData);

                // 解析输出以更新进度
                $this->parseOutputForProgress($stdoutData, $totalTables, $processedTables, $totalRecords, $insertedRecords);
            }

            // 读取 stderr
            $stderrData = fread($stderr, 4096);
            if ($stderrData !== false && strlen($stderrData) > 0) {
                $errorBuffer .= $stderrData;
                $this->appendOutput($stderrData);
            }

            // 检查进程状态
            $status = proc_get_status($process);

            // 短暂休眠以避免占用过多 CPU
            usleep(100000); // 100ms

        } while ($status['running']);

        // 读取剩余输出
        while (!feof($stdout)) {
            $data = fread($stdout, 4096);
            if ($data !== false && strlen($data) > 0) {
                $outputBuffer .= $data;
                $this->appendOutput($data);

                // 解析输出以更新进度
                $this->parseOutputForProgress($data, $totalTables, $processedTables, $totalRecords, $insertedRecords);
            }
        }

        while (!feof($stderr)) {
            $data = fread($stderr, 4096);
            if ($data !== false && strlen($data) > 0) {
                $errorBuffer .= $data;
                $this->appendOutput($data);
            }
        }

        // 关闭管道
        fclose($stdout);
        fclose($stderr);

        // 获取退出码
        $exitCode = proc_close($process);

        // 显示完成信息
        if ($exitCode === 0) {
            $this->appendOutput("\n转换完成！\n");
            $this->progressBar->setValue(100);
        } else {
            $this->appendOutput("\n转换失败，退出码: $exitCode\n");
            if (!empty($errorBuffer)) {
                $this->appendOutput("错误信息: $errorBuffer\n");
            }
        }

        // 重新启用转换按钮
        // 注意：SDK中可能没有enable方法，需要重新创建按钮
    }

    private function parseOutputForProgress($output, &$totalTables, &$processedTables, &$totalRecords, &$insertedRecords)
    {
        // 解析输出以更新进度
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            // 查找总表数
            if (preg_match('/Processed Tables: (\d+)/', $line, $matches)) {
                $totalTables = (int)$matches[1];
            }

            // 查找总记录数
            if (preg_match('/Total Records: (\d+)/', $line, $matches)) {
                $totalRecords = (int)$matches[1];
            }

            // 查找正在处理的表
            if (preg_match('/Processing table: (.+)/', $line, $matches)) {
                // 可以在这里增加已处理表的数量，但需要更精确的逻辑
            }

            // 查找成功转换的表
            if (preg_match('/Successfully converted table: (.+)/', $line, $matches)) {
                $processedTables++;
            }

            // 查找插入的记录数
            if (preg_match('/Inserted (\d+) rows into table/', $line, $matches)) {
                $insertedRecords += (int)$matches[1];
            }
        }

        // 计算进度百分比
        $progress = 0;
        if ($totalRecords > 0) {
            // 基于插入记录数计算进度
            $progress = min(100, ($insertedRecords / $totalRecords) * 100);
        } else if ($totalTables > 0) {
            // 基于处理表数计算进度
            $progress = min(100, ($processedTables / $totalTables) * 100);
        }

        // 更新进度条
        $this->progressBar->setValue((int)$progress);
    }

    private function appendOutput($text)
    {
        $currentText = $this->outputArea->getText();
        $newText = $currentText . $text;
        $this->outputArea->setText($newText);

        // 滚动到底部
        // 注意：libui PHP 绑定可能不支持直接滚动到底部
    }

    private function selectSqliteFile()
    {
        try {
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();

            // 打开文件选择对话框
            $selectedFile = \Kingbes\Libui\Window::openFile($window->getHandle());

            // 如果用户选择了文件，更新输入框
            if (!empty($selectedFile)) {
                $this->sqliteFileEntry->setText($selectedFile);
            }
        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();

            // 显示错误信息
            \Kingbes\Libui\Window::msgBoxError(
                $window->getHandle(),
                "错误",
                "选择文件时发生错误: " . $e->getMessage()
            );
        }
    }

    public function getControl()
    {
        return $this->box;
    }
}
