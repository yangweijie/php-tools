<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiGroup;
use Kingbes\Libui\SDK\LibuiMultilineEntry;
use Kingbes\Libui\SDK\LibuiProgressBar;
use Kingbes\Libui\SDK\LibuiCheckbox;
use Kingbes\Libui\SDK\LibuiApplication;
use Kingbes\Libui\Window;

class PackagerTab
{
    private LibuiVBox $box;
    private LibuiEntry $sourceEntry;
    private LibuiEntry $outputEntry;
    private LibuiEntry $appNameEntry;
    private LibuiEntry $appVersionEntry;
    private LibuiCheckbox $includeVendorCheckbox;
    private LibuiCheckbox $includePharCheckbox;
    private LibuiMultilineEntry $outputArea;
    private LibuiProgressBar $progressBar;
    private LibuiButton $packageButton;
    private LibuiButton $downloadButton;

    public function __construct()
    {
        // 创建主垂直容器
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);

        // 添加标题
        $titleLabel = new LibuiLabel("PHP 打包工具");
        $this->box->append($titleLabel, false);

        // 添加说明标签
        $descLabel = new LibuiLabel("将 PHP 命令行程序打包成基于 GUI 的独立应用程序");
        $this->box->append($descLabel, false);

        // 创建输入区域
        $this->addInputControls($this->box);

        // 创建输出区域
        $this->addOutputControls($this->box);

        // 检查 PHAR 配置并设置复选框状态
        $this->checkPharConfiguration();
    }

    private function addInputControls(LibuiVBox $container)
    {
        // 输入控件组
        $inputGroup = new LibuiGroup("打包配置");
        $inputGroup->setPadded(true);
        $container->append($inputGroup, false);

        $inputBox = new LibuiVBox();
        $inputBox->setPadded(true);
        $inputGroup->append($inputBox, false);

        // 源文件路径标签
        $sourceLabel = new LibuiLabel("源文件路径:");
        $inputBox->append($sourceLabel, false);

        // 创建水平容器用于放置输入框和选择按钮
        $sourceBox = new \Kingbes\Libui\SDK\LibuiHBox();
        $sourceBox->setPadded(true);
        $inputBox->append($sourceBox, false);

        // 源文件路径输入框
        $this->sourceEntry = new LibuiEntry();
        $this->sourceEntry->setText("./cli.php");
        $this->sourceEntry->on('entry.changed', function ($text) {
            // 当源文件路径改变时，重置分析状态
        });
        $sourceBox->append($this->sourceEntry, true);

        // 源文件选择按钮
        $sourceButton = new LibuiButton("选择文件");
        $sourceButton->onClick(function () {
            $this->selectSourceFile();
        });
        $sourceBox->append($sourceButton, false);

        // 输出目录标签
        $outputLabel = new LibuiLabel("输出目录:");
        $inputBox->append($outputLabel, false);

        // 创建水平容器用于放置输入框和选择按钮
        $outputBox = new \Kingbes\Libui\SDK\LibuiHBox();
        $outputBox->setPadded(true);
        $inputBox->append($outputBox, false);

        // 输出目录输入框
        $this->outputEntry = new LibuiEntry();
        $this->outputEntry->setText("./build");
        $outputBox->append($this->outputEntry, true);

        // 输出目录选择按钮
        $outputButton = new LibuiButton("选择目录");
        $outputButton->onClick(function () {
            $this->selectOutputDirectory();
        });
        $outputBox->append($outputButton, false);

        // 应用名称标签
        $appNameLabel = new LibuiLabel("应用名称:");
        $inputBox->append($appNameLabel, false);

        // 应用名称输入框
        $this->appNameEntry = new LibuiEntry();
        $this->appNameEntry->setText("MyApp");
        $inputBox->append($this->appNameEntry, false);

        // 应用版本标签
        $appVersionLabel = new LibuiLabel("应用版本:");
        $inputBox->append($appVersionLabel, false);

        // 应用版本输入框
        $this->appVersionEntry = new LibuiEntry();
        $this->appVersionEntry->setText("1.0.0");
        $inputBox->append($this->appVersionEntry, false);

        // 包含 vendor 目录复选框
        $this->includeVendorCheckbox = new LibuiCheckbox("包含 vendor 目录");
        $this->includeVendorCheckbox->setChecked(true);
        $inputBox->append($this->includeVendorCheckbox, false);

        // 打包 PHAR 文件复选框
        $this->includePharCheckbox = new LibuiCheckbox("打包为 PHAR 文件");
        $this->includePharCheckbox->setChecked(true);
        $inputBox->append($this->includePharCheckbox, false);

        // 按钮容器
        $buttonBox = new \Kingbes\Libui\SDK\LibuiHBox();
        $buttonBox->setPadded(true);
        $inputBox->append($buttonBox, false);

        // 打包按钮
        $this->packageButton = new LibuiButton("开始打包");
        $this->packageButton->onClick(function () {
            $this->startPackaging();
        });
        $buttonBox->append($this->packageButton, true);

        // 打开目录按钮
        $this->downloadButton = new LibuiButton("打开输出目录");
        $this->downloadButton->onClick(function () {
            $this->openOutputDirectory();
        });
        $buttonBox->append($this->downloadButton, true);
        // 初始禁用按钮状态需要在SDK中实现
    }

    private function addOutputControls(LibuiVBox $container)
    {
        // 输出控件组
        $outputGroup = new LibuiGroup("打包进度和输出");
        $outputGroup->setPadded(true);
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
        $this->outputArea->setText("等待开始打包...\n");
        $outputBox->append($this->outputArea, true);
    }

    private function checkPharConfiguration()
    {
        // 检查 phar.readonly 配置
        $pharReadonly = ini_get('phar.readonly');

        if ($pharReadonly === '1' || strtolower($pharReadonly) === 'on') {
            // PHAR 创建被禁用，禁用复选框并取消勾选
            $this->includePharCheckbox->setChecked(false);
            // 在SDK中需要实现禁用功能
            $this->appendOutput("注意: PHAR 创建功能已被 php.ini 中的 phar.readonly=1 禁用\n");
        } else {
            // PHAR 创建可用，保持复选框启用并默认勾选
            $this->includePharCheckbox->setChecked(true);
            // 在SDK中需要实现启用功能
        }
    }

    private function selectSourceFile()
    {
        try {
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();

            // 打开文件选择对话框
            $selectedFile = Window::openFile($window->getHandle());

            // 如果用户选择了文件，更新输入框
            if (!empty($selectedFile) && $selectedFile !== "") {
                $this->sourceEntry->setText($selectedFile);
            }
        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();

            // 显示错误信息
            Window::msgBoxError(
                $window->getHandle(),
                "错误",
                "选择文件时发生错误: " . $e->getMessage()
            );
        }
    }

    private function selectOutputDirectory()
    {
        // 注意：libui 可能没有直接选择目录的 API，我们可以让用户手动输入
        $this->appendOutput("请手动输入输出目录路径\n");
    }

    private function startPackaging()
    {
        try {
            // 获取输入参数
            $sourceFile = $this->sourceEntry->getText();
            $outputDir = $this->outputEntry->getText();
            $appName = $this->appNameEntry->getText();
            $appVersion = $this->appVersionEntry->getText();
            $includeVendor = $this->includeVendorCheckbox->isChecked();
            $includePhar = $this->includePharCheckbox->isChecked();

            // 验证必需参数
            if (empty($sourceFile)) {
                $this->appendOutput("错误: 请指定源文件路径\n");
                return;
            }

            if (empty($outputDir)) {
                $this->appendOutput("错误: 请指定输出目录\n");
                return;
            }

            if (empty($appName)) {
                $this->appendOutput("错误: 请指定应用名称\n");
                return;
            }

            if (!file_exists($sourceFile)) {
                $this->appendOutput("错误: 源文件不存在\n");
                return;
            }

            // 检查目标目录是否存在，如果存在则提示用户确认
            $appOutputDir = $outputDir . '/' . $appName;
            if (is_dir($appOutputDir)) {
                // 获取主窗口引用
                global $application;
                $window = $application->getWindow();

                // 显示确认对话框
                Window::msgBox(
                    $window->getHandle(),
                    "确认覆盖",
                    "输出目录下已存在名为 '{$appName}' 的目录，将继续打包并覆盖该目录。"
                );
            }

            // 禁用打包按钮
            // 在SDK中需要实现禁用功能

            // 重置进度条
            $this->progressBar->setValue(0);

            // 清空输出区域
            $this->outputArea->setText("开始打包...\n");

            // 使用 LibuiApplication::queueMain 异步执行打包操作
            LibuiApplication::getInstance()->queueMain(function() use ($sourceFile, $outputDir, $appName, $appVersion, $includeVendor, $includePhar) {
                $this->executePackagingStep1($sourceFile, $outputDir, $appName, $appVersion, $includeVendor, $includePhar);
            });

        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;

            // 显示错误信息
            Window::msgBoxError(
                $application->getWindow()->getHandle(),
                "错误",
                "开始打包时发生错误: " . $e->getMessage()
            );

            // 重新启用打包按钮
            // 在SDK中需要实现启用功能
        }
    }

    private function executePackagingStep1($sourceFile, $outputDir, $appName, $appVersion, $includeVendor, $includePhar)
    {
        // 第一步：分析项目结构和创建目录
        $this->appendOutput("正在分析项目结构...\n");
        $this->progressBar->setValue(10);

        // 根据应用名称创建独立的目录
        $appOutputDir = $outputDir . '/' . $appName;

        // 如果目录已存在，先删除它
        if (is_dir($appOutputDir)) {
            $this->appendOutput("删除已存在的目录: $appOutputDir\n");
            $this->deleteDirectory($appOutputDir);
        }

        // 创建新的目录
        if (!is_dir($appOutputDir)) {
            mkdir($appOutputDir, 0755, true);
        }

        $this->appendOutput("创建应用目录: $appOutputDir\n");
        $this->progressBar->setValue(20);

        // 使用 queueMain 调用下一步
        LibuiApplication::getInstance()->queueMain(function() use ($sourceFile, $appOutputDir, $includeVendor, $includePhar, $appName, $appVersion) {
            $this->executePackagingStep2($sourceFile, $appOutputDir, $includeVendor, $includePhar, $appName, $appVersion);
        });
    }

    private function executePackagingStep2($sourceFile, $appOutputDir, $includeVendor, $includePhar, $appName, $appVersion)
    {
        // 第二步：复制源文件
        $this->appendOutput("复制源文件...\n");
        $targetSource = $appOutputDir . '/' . basename($sourceFile);
        copy($sourceFile, $targetSource);
        $this->progressBar->setValue(30);

        // 使用 queueMain 调用下一步
        LibuiApplication::getInstance()->queueMain(function() use ($appOutputDir, $includeVendor, $includePhar, $appName, $appVersion, $sourceFile) {
            $this->executePackagingStep3($appOutputDir, $includeVendor, $includePhar, $appName, $appVersion, $sourceFile);
        });
    }

    private function executePackagingStep3($appOutputDir, $includeVendor, $includePhar, $appName, $appVersion, $sourceFile)
    {
        // 第三步：复制 vendor 目录
        if ($includeVendor && is_dir('vendor')) {
            $this->appendOutput("复制 vendor 目录...\n");
            $this->copyDirectory('vendor', $appOutputDir . '/vendor');
            $this->progressBar->setValue(50);
        } else {
            $this->progressBar->setValue(50);
        }

        // 使用 queueMain 调用下一步
        LibuiApplication::getInstance()->queueMain(function() use ($appOutputDir, $includePhar, $appName, $sourceFile, $includeVendor) {
            $this->executePackagingStep4($appOutputDir, $includePhar, $appName, $sourceFile, $includeVendor);
        });
    }

    private function executePackagingStep4($appOutputDir, $includePhar, $appName, $sourceFile, $includeVendor)
    {
        // 第四步：创建 PHAR 文件
        if ($includePhar) {
            // 检查 PHAR 配置
            $pharReadonly = ini_get('phar.readonly');
            if ($pharReadonly === '1' || strtolower($pharReadonly) === 'on') {
                $this->appendOutput("警告: 无法创建 PHAR 文件，因为 php.ini 中的 phar.readonly=1\n");
            } else {
                $this->appendOutput("创建 PHAR 文件...\n");
                $pharFile = $appOutputDir . '/' . $appName . '.phar';

                // 这里应该调用实际的 PHAR 创建逻辑
                // 为了演示，我们只是创建一个简单的 PHAR
                $this->createPharFile($sourceFile, $pharFile, $includeVendor);
            }
        }
        $this->progressBar->setValue(80);

        // 使用 queueMain 调用下一步
        LibuiApplication::getInstance()->queueMain(function() use ($appOutputDir, $appName, $sourceFile, $includePhar) {
            $this->executePackagingStep5($appOutputDir, $appName, $sourceFile, $includePhar);
        });
    }

    private function executePackagingStep5($appOutputDir, $appName, $sourceFile, $includePhar)
    {
        // 第五步：创建 GUI 包装器
        $this->appendOutput("创建 GUI 包装器...\n");
        $this->createGuiWrapper($appOutputDir, $appName, $sourceFile, $includePhar);
        $this->progressBar->setValue(90);

        // 使用 queueMain 调用下一步
        LibuiApplication::getInstance()->queueMain(function() use ($appOutputDir, $appName) {
            $this->executePackagingStep6($appOutputDir, $appName);
        });
    }

    private function executePackagingStep6($appOutputDir, $appName)
    {
        // 第六步：创建源码压缩包
        $this->appendOutput("创建源码压缩包...\n");
        $this->createSourcePackage($appOutputDir, $appName);
        $this->progressBar->setValue(100);

        // 显示完成信息
        $this->appendOutput("\n打包完成！\n");
        $this->appendOutput("输出目录: $appOutputDir\n");

        // 启用打开目录按钮
        // 在SDK中需要实现启用功能

        // 重新启用打包按钮
        // 在SDK中需要实现启用功能
    }

    private function createPharFile($sourceFile, $pharFile, $includeVendor)
    {
        try {
            // 创建 PHAR 文件
            $phar = new \Phar($pharFile);
            $phar->startBuffering();

            // 添加源文件
            $phar->addFile($sourceFile, basename($sourceFile));

            // 如果需要包含 vendor 目录
            if ($includeVendor && is_dir('vendor')) {
                $this->appendOutput("  添加 vendor 目录到 PHAR...\n");
                $phar->buildFromDirectory('vendor', '/vendor/');
            }

            // 设置默认运行脚本
            $phar->setStub("#!/usr/bin/env php\n<?php\nPhar::mapPhar();\ninclude 'phar://".basename($pharFile)."/".basename($sourceFile)."';\n__HALT_COMPILER();\n");

            $phar->stopBuffering();

            $this->appendOutput("  PHAR 文件创建完成: $pharFile\n");
        } catch (\Exception $e) {
            $this->appendOutput("  创建 PHAR 文件时出错: " . $e->getMessage() . "\n");
        }
    }

    private function createGuiWrapper($outputDir, $appName, $sourceFile, $includePhar)
    {
        // 创建一个简单的 GUI 包装器
        $wrapperContent = <<<EOT
<?php
// GUI 包装器 for $appName

require_once __DIR__ . '/vendor/autoload.php';

use Kingbes\Libui\App as LibuiApp;
use Kingbes\Libui\Window;
use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Button;
use Kingbes\Libui\Control;

// 初始化应用
LibuiApp::init();

// 创建主窗口
\$window = Window::create("$appName", 400, 300, 1);
Window::setMargined(\$window, true);

// 创建主容器
\$box = Box::newVerticalBox();
Box::setPadded(\$box, true);

// 添加标题
\$titleLabel = Label::create("$appName v1.0");
Box::append(\$box, \$titleLabel, false);

// 添加说明
\$descLabel = Label::create("点击下面的按钮运行命令行程序");
Box::append(\$box, \$descLabel, false);

// 添加运行按钮
\$runButton = Button::create("运行程序");
Button::onClicked(\$runButton, function(\$btn) {
    // 这里应该执行实际的命令行程序
    // 例如：system('php $sourceFile');
    echo "运行命令: php $sourceFile\n";
});

Box::append(\$box, \$runButton, false);

// 设置窗口内容
Window::setChild(\$window, \$box);

// 显示窗口
Control::show(\$window);

// 窗口关闭事件
Window::onClosing(\$window, function (\$window) {
    LibuiApp::quit();
    return 1;
});

// 主循环
LibuiApp::main();
EOT;

        file_put_contents($outputDir . '/gui_wrapper.php', $wrapperContent);
        $this->appendOutput("  GUI 包装器创建完成\n");
    }

    private function createSourcePackage($outputDir, $appName)
    {
        // 创建源码压缩包的逻辑
        $zipFile = $outputDir . '/' . $appName . '_source.zip';
        $this->appendOutput("  源码包将保存为: $zipFile\n");
        // 实际的 ZIP 创建逻辑会在这里实现
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function openOutputDirectory()
    {
        try {
            $outputDir = $this->outputEntry->getText();
            $appName = $this->appNameEntry->getText();
            $appOutputDir = $outputDir . '/' . $appName;

            if (empty($outputDir) || empty($appName) || !is_dir($appOutputDir)) {
                // 获取主窗口引用
                global $application;
                $window = $application->getWindow();

                // 显示错误信息
                Window::msgBoxError(
                    $window->getHandle(),
                    "错误",
                    "应用输出目录不存在，请先完成打包操作。"
                );
                return;
            }

            // 跨平台打开目录
            $this->openDirectory($appOutputDir);

        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();

            // 显示错误信息
            Window::msgBoxError(
                $window->getHandle(),
                "错误",
                "打开目录时发生错误: " . $e->getMessage()
            );
        }
    }

    private function openDirectory($path)
    {
        try {
            // 跨平台打开目录
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows 系统
                shell_exec('explorer "' . str_replace('/', '\\', $path) . '"');
            } else if (PHP_OS_FAMILY === 'Darwin') {
                // macOS 系统
                shell_exec('open "' . $path . '"');
            } else if (PHP_OS_FAMILY === 'Linux') {
                // Linux 系统
                shell_exec('xdg-open "' . $path . '"');
            } else {
                // 其他系统
                throw new \Exception("不支持的操作系统: " . PHP_OS_FAMILY);
            }
        } catch (\Exception $e) {
            throw new \Exception("无法打开目录: " . $e->getMessage());
        }
    }

    private function appendOutput($text)
    {
        $currentText = $this->outputArea->getText();
        $newText = $currentText . $text;
        $this->outputArea->setText($newText);
    }

    public function getControl()
    {
        return $this->box;
    }
}
