<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Button;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Group;
use Kingbes\Libui\Control;
use Kingbes\Libui\MultilineEntry;
use Kingbes\Libui\ProgressBar;
use Kingbes\Libui\Checkbox;
use Kingbes\Libui\MsgBox;
use Kingbes\Libui\App as LibuiApp;
use Kingbes\Libui\Window;
use Kingbes\Libui\Combobox;
use Kingbes\Libui\Spinbox;
use Kingbes\Libui\Table;
use Kingbes\Libui\TableValueType;

class SmartPackagerTab
{
    private $box;
    private $sourceEntry;
    private $outputEntry;
    private $appNameEntry;
    private $appVersionEntry;
    private $includeVendorCheckbox;
    private $includePharCheckbox;
    private $outputArea;
    private $progressBar;
    private $packageButton;
    private $downloadButton;
    private $dynamicContainer;
    private $cliFile;
    private $cliParameters = [];
    private $paramConfigControls = [];

    public function __construct()
    {
        // 创建主垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);

        // 添加标题
        $titleLabel = Label::create("智能 PHP 打包工具");
        Box::append($this->box, $titleLabel, false);

        // 添加说明标签
        $descLabel = Label::create("将 PHP 命令行程序智能打包成基于 GUI 的独立应用程序");
        Box::append($this->box, $descLabel, false);

        // 创建输入区域
        $this->addInputControls($this->box);

        // 创建动态参数区域
        $this->addDynamicParameterControls($this->box);

        // 创建输出区域
        $this->addOutputControls($this->box);

        // 检查 PHAR 配置并设置复选框状态
        $this->checkPharConfiguration();
    }

    private function addInputControls($container)
    {
        // 输入控件组
        $inputGroup = Group::create("打包配置");
        Group::setMargined($inputGroup, true);
        Box::append($container, $inputGroup, false);

        $inputBox = Box::newVerticalBox();
        Box::setPadded($inputBox, true);
        Group::setChild($inputGroup, $inputBox);

        // 源文件路径标签
        $sourceLabel = Label::create("源文件路径:");
        Box::append($inputBox, $sourceLabel, false);

        // 创建水平容器用于放置输入框和选择按钮
        $sourceBox = Box::newHorizontalBox();
        Box::setPadded($sourceBox, true);
        Box::append($inputBox, $sourceBox, false);

        // 源文件路径输入框
        $this->sourceEntry = Entry::create();
        Entry::setText($this->sourceEntry, "./cli.php");
        Entry::onChanged($this->sourceEntry, function ($entry) {
            $this->analyzeCliFile();
        });
        Box::append($sourceBox, $this->sourceEntry, true);

        // 源文件选择按钮
        $sourceButton = Button::create("选择文件");
        Button::onClicked($sourceButton, function ($btn) {
            $this->selectSourceFile();
        });
        Box::append($sourceBox, $sourceButton, false);

        // 输出目录标签
        $outputLabel = Label::create("输出目录:");
        Box::append($inputBox, $outputLabel, false);

        // 创建水平容器用于放置输入框和选择按钮
        $outputBox = Box::newHorizontalBox();
        Box::setPadded($outputBox, true);
        Box::append($inputBox, $outputBox, false);

        // 输出目录输入框
        $this->outputEntry = Entry::create();
        Entry::setText($this->outputEntry, "./build");
        Box::append($outputBox, $this->outputEntry, true);

        // 输出目录选择按钮
        $outputButton = Button::create("选择目录");
        Button::onClicked($outputButton, function ($btn) {
            $this->selectOutputDirectory();
        });
        Box::append($outputBox, $outputButton, false);

        // 应用名称标签
        $appNameLabel = Label::create("应用名称:");
        Box::append($inputBox, $appNameLabel, false);

        // 应用名称输入框
        $this->appNameEntry = Entry::create();
        Entry::setText($this->appNameEntry, "MyApp");
        Box::append($inputBox, $this->appNameEntry, false);

        // 应用版本标签
        $appVersionLabel = Label::create("应用版本:");
        Box::append($inputBox, $appVersionLabel, false);

        // 应用版本输入框
        $this->appVersionEntry = Entry::create();
        Entry::setText($this->appVersionEntry, "1.0.0");
        Box::append($inputBox, $this->appVersionEntry, false);

        // 包含 vendor 目录复选框
        $this->includeVendorCheckbox = Checkbox::create("包含 vendor 目录");
        Checkbox::setChecked($this->includeVendorCheckbox, true);
        Box::append($inputBox, $this->includeVendorCheckbox, false);

        // 打包 PHAR 文件复选框
        $this->includePharCheckbox = Checkbox::create("打包为 PHAR 文件");
        Checkbox::setChecked($this->includePharCheckbox, true);
        Box::append($inputBox, $this->includePharCheckbox, false);

        // 按钮容器
        $buttonBox = Box::newHorizontalBox();
        Box::setPadded($buttonBox, true);
        Box::append($inputBox, $buttonBox, false);

        // 打包按钮
        $this->packageButton = Button::create("开始打包");
        Button::onClicked($this->packageButton, function ($btn) {
            // 先分析CLI参数，再开始打包
            $this->analyzeCliFile();
            // 注意：这里不会直接开始打包，而是在参数配置完成后才打包
        });
        Box::append($buttonBox, $this->packageButton, true);

        // 打开目录按钮
        $this->downloadButton = Button::create("打开输出目录");
        Button::onClicked($this->downloadButton, function ($btn) {
            $this->openOutputDirectory();
        });
        Box::append($buttonBox, $this->downloadButton, true);
        Control::disable($this->downloadButton); // 初始禁用按钮
    }

    private function addDynamicParameterControls($container)
    {
        // 动态参数控件组
        $dynamicGroup = Group::create("CLI 参数配置");
        Group::setMargined($dynamicGroup, true);
        Box::append($container, $dynamicGroup, false);

        $this->dynamicContainer = Box::newVerticalBox();
        Box::setPadded($this->dynamicContainer, true);
        Group::setChild($dynamicGroup, $this->dynamicContainer);

        // 添加默认提示
        $hintLabel = Label::create("请选择一个 CLI 文件以分析其参数");
        Box::append($this->dynamicContainer, $hintLabel, false);
    }

    private function addOutputControls($container)
    {
        // 输出控件组
        $outputGroup = Group::create("打包进度和输出");
        Group::setMargined($outputGroup, true);
        Box::append($container, $outputGroup, true);

        $outputBox = Box::newVerticalBox();
        Box::setPadded($outputBox, true);
        Group::setChild($outputGroup, $outputBox);

        // 进度条
        $this->progressBar = ProgressBar::create();
        ProgressBar::setValue($this->progressBar, 0);
        Box::append($outputBox, $this->progressBar, false);

        // 输出区域标签
        $outputLabel = Label::create("输出信息:");
        Box::append($outputBox, $outputLabel, false);

        // 多行输出区域
        $this->outputArea = MultilineEntry::create();
        MultilineEntry::setText($this->outputArea, "等待开始打包...\n");
        Box::append($outputBox, $this->outputArea, true);
    }

    private function checkPharConfiguration()
    {
        // 检查 phar.readonly 配置
        $pharReadonly = ini_get('phar.readonly');

        if ($pharReadonly === '1' || strtolower($pharReadonly) === 'on') {
            // PHAR 创建被禁用，禁用复选框并取消勾选
            Checkbox::setChecked($this->includePharCheckbox, false);
            Control::disable($this->includePharCheckbox);
            $this->appendOutput("注意: PHAR 创建功能已被 php.ini 中的 phar.readonly=1 禁用\n");
        } else {
            // PHAR 创建可用，保持复选框启用并默认勾选
            Checkbox::setChecked($this->includePharCheckbox, true);
            Control::enable($this->includePharCheckbox);
        }
    }

    private function selectSourceFile()
    {
        try {
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();

            // 打开文件选择对话框
            $selectedFile = Window::openFile($window);

            // 如果用户选择了文件，更新输入框
            if (!empty($selectedFile) && $selectedFile !== "") {
                Entry::setText($this->sourceEntry, $selectedFile);
                $this->analyzeCliFile();
            }
        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;
            $window = $application->getWindow();

            // 显示错误信息
            Window::msgBoxError(
                $window,
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

    private function analyzeCliFile()
    {
        $sourceFile = Entry::text($this->sourceEntry);

        // 检查文件是否存在
        if (empty($sourceFile) || !file_exists($sourceFile)) {
            return;
        }

        // 保存CLI文件路径
        $this->cliFile = $sourceFile;

        // 清空之前的参数
        $this->cliParameters = [];

        // 通过运行CLI程序获取帮助信息来分析参数
        $this->parseCliParametersByExecution();

        // 更新动态参数界面
        $this->updateDynamicParameterControls();
    }

    private function parseCliParametersByExecution()
    {
        $sourceFile = $this->cliFile;

        // 确定如何运行该文件
        $command = '';
        if (substr($sourceFile, -4) === '.php' || substr($sourceFile, -5) === '.phar') {
            $command = 'php ' . escapeshellarg($sourceFile);
        } else {
            // 对于二进制文件或其他脚本，直接运行
            $command = escapeshellarg($sourceFile);
        }

        // 尝试不同的帮助命令
        $helpCommands = [' --help', ' -h', ' /?', ' --usage', ' -help'];
        $helpOutput = '';

        foreach ($helpCommands as $helpCmd) {
            $fullCommand = $command . $helpCmd . ' 2>&1';
            $output = [];
            $returnCode = 0;

            // 使用exec而不是system来捕获输出
            exec($fullCommand, $output, $returnCode);

            // 如果命令成功执行或返回常见的帮助信息退出码
            if ($returnCode <= 1) {
                $helpOutput = implode("\n", $output);
                if (!empty($helpOutput)) {
                    break;
                }
            }
        }

        // 如果没有获取到帮助信息，尝试直接运行命令看是否有帮助信息输出
        if (empty($helpOutput)) {
            $fullCommand = $command . ' 2>&1';
            $output = [];
            exec($fullCommand, $output, $returnCode);

            // 如果程序输出了信息并且很快退出，可能是帮助信息
            if (!empty($output) && $returnCode <= 1) {
                $helpOutput = implode("\n", $output);
            }
        }

        // 解析帮助信息中的参数
        $this->parseHelpOutput($helpOutput);
    }

    private function parseHelpOutput($helpOutput)
    {
        if (empty($helpOutput)) {
            return;
        }

        // 查找长选项 --param
        if (preg_match_all('/--([a-zA-Z0-9\-_]+)/', $helpOutput, $longOpts)) {
            foreach ($longOpts[1] as $opt) {
                // 过滤掉一些常见的非参数选项
                if (!in_array($opt, ['help', 'version', 'usage'])) {
                    $this->cliParameters[$opt] = [
                        'name' => $opt,
                        'short' => null,
                        'description' => 'Auto-detected parameter',
                        'required' => false,
                        'type' => 'string'
                    ];
                }
            }
        }

        // 查找短选项 -p
        if (preg_match_all('/\s-([a-zA-Z])[\s,]/', $helpOutput, $shortOpts)) {
            foreach ($shortOpts[1] as $opt) {
                // 过滤掉一些常见的非参数选项
                if (!in_array($opt, ['h', 'v', 'V'])) {
                    if (!isset($this->cliParameters[$opt])) {
                        $this->cliParameters[$opt] = [
                            'name' => $opt,
                            'short' => $opt,
                            'description' => 'Auto-detected short parameter',
                            'required' => false,
                            'type' => 'string'
                        ];
                    }
                }
            }
        }

        // 查找带值的选项 --param=value 或 --param value
        if (preg_match_all('/--([a-zA-Z0-9\-_]+)[\s=]+([A-Z_]+)/', $helpOutput, $valueOpts)) {
            for ($i = 0; $i < count($valueOpts[1]); $i++) {
                $param = $valueOpts[1][$i];
                $valueType = $valueOpts[2][$i];

                if (isset($this->cliParameters[$param])) {
                    // 根据值类型推测参数类型
                    switch (strtoupper($valueType)) {
                        case 'NUMBER':
                        case 'INT':
                        case 'INTEGER':
                            $this->cliParameters[$param]['type'] = 'integer';
                            break;
                        case 'FILE':
                        case 'PATH':
                            $this->cliParameters[$param]['type'] = 'string';
                            break;
                    }
                }
            }
        }

        // 查找帮助文本中的参数描述
        $this->parseHelpDescriptions($helpOutput);
    }

    private function parseHelpDescriptions($helpOutput)
    {
        // 查找参数和描述的模式
        $lines = explode("\n", $helpOutput);
        foreach ($lines as $line) {
            // 查找 -p, --param 描述 或 --param 描述 的模式
            if (preg_match('/^[\s]*(-[a-zA-Z],?[\s]*)?(--?[a-zA-Z0-9\-_]+)[\s,]+(.+)$/', $line, $matches)) {
                $param = str_replace(['--', '-'], '', $matches[2]);
                $description = trim($matches[3]);

                if (isset($this->cliParameters[$param])) {
                    $this->cliParameters[$param]['description'] = $description;
                }
            }
            // 查找 --param 描述 的模式
            else if (preg_match('/^[\s]*(--?[a-zA-Z0-9\-_]+)[\s]{2,}(.+)$/', $line, $matches)) {
                $param = str_replace(['--', '-'], '', $matches[1]);
                $description = trim($matches[2]);

                if (isset($this->cliParameters[$param])) {
                    $this->cliParameters[$param]['description'] = $description;
                }
            }
        }
    }

    // 新增：显示参数配置界面
    private function showParameterConfigurationInterface()
    {
        // 创建参数配置窗口
        global $application;
        $window = $application->getWindow();

        // 创建配置对话框
        $this->createParameterConfigDialog($window);
    }

    // 新增：创建参数配置对话框
    private function createParameterConfigDialog($parentWindow)
    {
        // 创建参数配置界面
        $this->showParameterConfigForm();
    }

    // 新增：显示参数配置表单
    private function showParameterConfigForm()
    {
        // 清空动态容器
        while (true) {
            try {
                Box::delete($this->dynamicContainer, 0);
            } catch (\Exception $e) {
                // 当没有更多子元素时会抛出异常，此时退出循环
                break;
            }
        }

        // 添加标题
        $titleLabel = Label::create("参数配置");
        Box::append($this->dynamicContainer, $titleLabel, false);

        $descLabel = Label::create("请配置以下参数，然后点击'预览GUI'按钮");
        Box::append($this->dynamicContainer, $descLabel, false);

        // 为每个参数创建配置控件
        foreach ($this->cliParameters as $name => $param) {
            $this->createParameterConfigControl($name, $param);
        }

        // 添加按钮
        $buttonBox = Box::newHorizontalBox();
        Box::setPadded($buttonBox, true);
        Box::append($this->dynamicContainer, $buttonBox, false);

        // 预览GUI按钮
        $previewButton = Button::create("预览GUI");
        Button::onClicked($previewButton, function ($btn) {
            $this->showGuiPreview();
        });
        Box::append($buttonBox, $previewButton, true);

        // 返回按钮
        $backButton = Button::create("返回");
        Button::onClicked($backButton, function ($btn) {
            $this->updateDynamicParameterControls();
        });
        Box::append($buttonBox, $backButton, true);
    }

    // 新增：创建参数配置控件
    private function createParameterConfigControl($name, $param)
    {
        // 参数组
        $paramGroup = Group::create($name);
        Group::setMargined($paramGroup, true);
        Box::append($this->dynamicContainer, $paramGroup, false);

        $paramBox = Box::newVerticalBox();
        Box::setPadded($paramBox, true);
        Group::setChild($paramGroup, $paramBox);

        // 参数描述
        $descLabel = Label::create("描述: " . ($param['description'] ?? '无描述'));
        Box::append($paramBox, $descLabel, false);

        // 必填选项
        $requiredCheckbox = Checkbox::create("必填参数");
        Checkbox::setChecked($requiredCheckbox, $param['required'] ?? false);
        Box::append($paramBox, $requiredCheckbox, false);

        // 参数类型选择
        $typeLabel = Label::create("参数类型:");
        Box::append($paramBox, $typeLabel, false);

        $typeCombobox = Combobox::create();
        $types = ['string' => '文本', 'integer' => '整数', 'boolean' => '布尔值', 'file' => '文件路径'];
        foreach ($types as $type => $label) {
            Combobox::append($typeCombobox, $label);
        }

        // 设置默认选中项
        $defaultType = $param['type'] ?? 'string';
        $selectedIndex = array_search($defaultType, array_keys($types));
        if ($selectedIndex !== false) {
            Combobox::setSelected($typeCombobox, $selectedIndex);
        }

        Box::append($paramBox, $typeCombobox, false);

        // 默认值输入
        $defaultLabel = Label::create("默认值:");
        Box::append($paramBox, $defaultLabel, false);

        $defaultEntry = Entry::create();
        Entry::setText($defaultEntry, $param['default'] ?? '');
        Box::append($paramBox, $defaultEntry, false);

        // 保存控件引用以便后续获取值
        $this->paramConfigControls[$name] = [
            'required' => $requiredCheckbox,
            'type' => $typeCombobox,
            'default' => $defaultEntry,
            'types' => $types
        ];
    }

    // 新增：显示GUI预览
    private function showGuiPreview()
    {
        // 更新参数配置
        $this->updateParameterConfigurations();

        // 清空动态容器
        while (true) {
            try {
                Box::delete($this->dynamicContainer, 0);
            } catch (\Exception $e) {
                // 当没有更多子元素时会抛出异常，此时退出循环
                break;
            }
        }

        // 添加标题
        $titleLabel = Label::create("GUI预览");
        Box::append($this->dynamicContainer, $titleLabel, false);

        $descLabel = Label::create("以下是将要生成的GUI界面预览");
        Box::append($this->dynamicContainer, $descLabel, false);

        // 显示预览控件
        foreach ($this->cliParameters as $name => $param) {
            $this->createPreviewControl($name, $param);
        }

        // 添加按钮
        $buttonBox = Box::newHorizontalBox();
        Box::setPadded($buttonBox, true);
        Box::append($this->dynamicContainer, $buttonBox, false);

        // 生成GUI按钮
        $generateButton = Button::create("生成GUI");
        Button::onClicked($generateButton, function ($btn) {
            $this->generateSmartGuiWrapper();
        });
        Box::append($buttonBox, $generateButton, true);

        // 返回配置按钮
        $backButton = Button::create("返回配置");
        Button::onClicked($backButton, function ($btn) {
            $this->showParameterConfigForm();
        });
        Box::append($buttonBox, $backButton, true);
    }

    // 新增：更新参数配置
    private function updateParameterConfigurations()
    {
        foreach ($this->paramConfigControls as $name => $controls) {
            // 获取配置值
            $required = Checkbox::checked($controls['required']);
            $typeIndex = Combobox::selected($controls['type']);
            $types = array_keys($controls['types']);
            $type = $types[$typeIndex] ?? 'string';
            $default = Entry::text($controls['default']);

            // 更新参数
            if (isset($this->cliParameters[$name])) {
                $this->cliParameters[$name]['required'] = $required;
                $this->cliParameters[$name]['type'] = $type;
                $this->cliParameters[$name]['default'] = $default;
            }
        }
    }

    // 新增：创建预览控件
    private function createPreviewControl($name, $param)
    {
        // 参数标签
        $paramLabel = Label::create($name . ": " . ($param['description'] ?? ''));
        Box::append($this->dynamicContainer, $paramLabel, false);

        // 根据参数类型创建不同的预览控件
        switch ($param['type']) {
            case 'boolean':
                // 复选框
                $checkbox = Checkbox::create("启用 " . $name);
                if (!empty($param['default'])) {
                    Checkbox::setChecked($checkbox, (bool)$param['default']);
                }
                Box::append($this->dynamicContainer, $checkbox, false);
                break;

            case 'integer':
                // 微调框
                $spinbox = Spinbox::create(-1000000, 1000000);
                if (!empty($param['default'])) {
                    Spinbox::setValue($spinbox, (int)$param['default']);
                }
                Box::append($this->dynamicContainer, $spinbox, false);
                break;

            case 'file':
                // 文件路径输入框和选择按钮
                $fileBox = Box::newHorizontalBox();
                Box::setPadded($fileBox, true);
                Box::append($this->dynamicContainer, $fileBox, false);

                $fileEntry = Entry::create();
                if (!empty($param['default'])) {
                    Entry::setText($fileEntry, $param['default']);
                }
                Box::append($fileBox, $fileEntry, true);

                $fileButton = Button::create("选择文件");
                Box::append($fileBox, $fileButton, false);
                break;

            case 'string':
            default:
                // 普通输入框
                $entry = Entry::create();
                if (!empty($param['default'])) {
                    Entry::setText($entry, $param['default']);
                }
                Box::append($this->dynamicContainer, $entry, false);
                break;
        }

        // 添加分隔符
        $separator = \Kingbes\Libui\Separator::createHorizontal();
        Box::append($this->dynamicContainer, $separator, false);
    }

    // 新增：生成智能GUI包装器
    private function generateSmartGuiWrapper()
    {
        // 获取输出目录和应用名称
        $outputDir = Entry::text($this->outputEntry);
        $appName = Entry::text($this->appNameEntry);
        $sourceFile = Entry::text($this->sourceEntry);

        // 确保输出目录存在
        $appOutputDir = $outputDir . '/' . $appName;
        if (!is_dir($appOutputDir)) {
            mkdir($appOutputDir, 0755, true);
        }

        // 创建智能GUI包装器
        $this->createSmartGuiWrapper($appOutputDir, $appName, $sourceFile, true);

        // 显示完成信息
        $this->appendOutput("智能GUI包装器已生成到: " . $appOutputDir . "/smart_gui_wrapper.php\n");
    }

    private function updateDynamicParameterControls()
    {
        // 由于libui PHP绑定的限制，我们采用简单的重建方式
        // 清空现有的容器
        while (true) {
            try {
                Box::delete($this->dynamicContainer, 0);
            } catch (\Exception $e) {
                // 当没有更多子元素时会抛出异常，此时退出循环
                break;
            }
        }

        // 如果没有参数，显示提示信息
        if (empty($this->cliParameters)) {
            $hintLabel = Label::create("未检测到 CLI 参数，将使用默认运行方式");
            Box::append($this->dynamicContainer, $hintLabel, false);
            return;
        }

        // 显示参数配置界面
        $this->showParameterConfigForm();
    }

    private function createParameterControl($param)
    {
        // 参数标签
        $paramLabel = Label::create($param['name'] . ": " . $param['description']);
        Box::append($this->dynamicContainer, $paramLabel, false);

        // 根据参数类型创建不同的控件
        switch ($param['type']) {
            case 'boolean':
                // 复选框
                $checkbox = Checkbox::create("启用 " . $param['name']);
                Box::append($this->dynamicContainer, $checkbox, false);
                break;

            case 'integer':
                // 微调框
                $spinbox = Spinbox::create(-1000000, 1000000);
                Box::append($this->dynamicContainer, $spinbox, false);
                break;

            case 'choice':
                // 下拉框
                if (isset($param['choices'])) {
                    $combobox = Combobox::create();
                    foreach ($param['choices'] as $choice) {
                        Combobox::append($combobox, $choice);
                    }
                    Box::append($this->dynamicContainer, $combobox, false);
                } else {
                    // 普通输入框
                    $entry = Entry::create();
                    Box::append($this->dynamicContainer, $entry, false);
                }
                break;

            case 'string':
            default:
                // 普通输入框
                $entry = Entry::create();
                Box::append($this->dynamicContainer, $entry, false);
                break;
        }

        // 添加分隔符
        $separator = \Kingbes\Libui\Separator::createHorizontal();
        Box::append($this->dynamicContainer, $separator, false);
    }

    private function startPackaging()
    {
        try {
            // 获取输入参数
            $sourceFile = Entry::text($this->sourceEntry);
            $outputDir = Entry::text($this->outputEntry);
            $appName = Entry::text($this->appNameEntry);
            $appVersion = Entry::text($this->appVersionEntry);
            $includeVendor = Checkbox::checked($this->includeVendorCheckbox);
            $includePhar = Checkbox::checked($this->includePharCheckbox);

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
                    $window,
                    "确认覆盖",
                    "输出目录下已存在名为 '{$appName}' 的目录，将继续打包并覆盖该目录。"
                );
            }

            // 禁用打包按钮
            Control::disable($this->packageButton);

            // 重置进度条
            ProgressBar::setValue($this->progressBar, 0);

            // 清空输出区域
            MultilineEntry::setText($this->outputArea, "开始打包...\n");

            // 使用 LibuiApp::queueMain 异步执行打包操作
            LibuiApp::queueMain(function() use ($sourceFile, $outputDir, $appName, $appVersion, $includeVendor, $includePhar) {
                $this->executePackagingStep1($sourceFile, $outputDir, $appName, $appVersion, $includeVendor, $includePhar);
            });

        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;

            // 显示错误信息
            Window::msgBoxError(
                $application->getWindow(),
                "错误",
                "开始打包时发生错误: " . $e->getMessage()
            );

            // 重新启用打包按钮
            Control::enable($this->packageButton);
        }
    }

    private function executePackagingStep1($sourceFile, $outputDir, $appName, $appVersion, $includeVendor, $includePhar)
    {
        // 第一步：分析项目结构和创建目录
        $this->appendOutput("正在分析项目结构...\n");
        ProgressBar::setValue($this->progressBar, 10);

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
        ProgressBar::setValue($this->progressBar, 20);

        // 使用 queueMain 调用下一步
        LibuiApp::queueMain(function() use ($sourceFile, $appOutputDir, $includeVendor, $includePhar, $appName, $appVersion) {
            $this->executePackagingStep2($sourceFile, $appOutputDir, $includeVendor, $includePhar, $appName, $appVersion);
        });
    }

    private function executePackagingStep2($sourceFile, $appOutputDir, $includeVendor, $includePhar, $appName, $appVersion)
    {
        // 第二步：复制源文件
        $this->appendOutput("复制源文件...\n");
        $targetSource = $appOutputDir . '/' . basename($sourceFile);
        copy($sourceFile, $targetSource);
        ProgressBar::setValue($this->progressBar, 30);

        // 使用 queueMain 调用下一步
        LibuiApp::queueMain(function() use ($appOutputDir, $includeVendor, $includePhar, $appName, $appVersion, $sourceFile) {
            $this->executePackagingStep3($appOutputDir, $includeVendor, $includePhar, $appName, $appVersion, $sourceFile);
        });
    }

    private function executePackagingStep3($appOutputDir, $includeVendor, $includePhar, $appName, $appVersion, $sourceFile)
    {
        // 第三步：复制 vendor 目录
        if ($includeVendor && is_dir('vendor')) {
            $this->appendOutput("复制 vendor 目录...\n");
            $this->copyDirectory('vendor', $appOutputDir . '/vendor');
            ProgressBar::setValue($this->progressBar, 50);
        } else {
            ProgressBar::setValue($this->progressBar, 50);
        }

        // 使用 queueMain 调用下一步
        LibuiApp::queueMain(function() use ($appOutputDir, $includePhar, $appName, $sourceFile, $includeVendor) {
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
        ProgressBar::setValue($this->progressBar, 80);

        // 使用 queueMain 调用下一步
        LibuiApp::queueMain(function() use ($appOutputDir, $appName, $sourceFile, $includePhar) {
            $this->executePackagingStep5($appOutputDir, $appName, $sourceFile, $includePhar);
        });
    }

    private function executePackagingStep5($appOutputDir, $appName, $sourceFile, $includePhar)
    {
        // 第五步：创建智能 GUI 包装器
        $this->appendOutput("创建智能 GUI 包装器...\n");
        $this->createSmartGuiWrapper($appOutputDir, $appName, $sourceFile, $includePhar);
        ProgressBar::setValue($this->progressBar, 90);

        // 使用 queueMain 调用下一步
        LibuiApp::queueMain(function() use ($appOutputDir, $appName) {
            $this->executePackagingStep6($appOutputDir, $appName);
        });
    }

    private function executePackagingStep6($appOutputDir, $appName)
    {
        // 第六步：创建源码压缩包
        $this->appendOutput("创建源码压缩包...\n");
        $this->createSourcePackage($appOutputDir, $appName);
        ProgressBar::setValue($this->progressBar, 100);

        // 显示完成信息
        $this->appendOutput("\n打包完成！\n");
        $this->appendOutput("输出目录: $appOutputDir\n");

        // 启用打开目录按钮
        Control::enable($this->downloadButton);

        // 重新启用打包按钮
        Control::enable($this->packageButton);
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

    private function createSmartGuiWrapper($outputDir, $appName, $sourceFile, $includePhar)
    {
        // 创建智能 GUI 包装器
        $cliParams = var_export($this->cliParameters, true);

        $wrapperContent = <<<EOT
<?php
// 智能 GUI 包装器 for $appName

require_once __DIR__ . '/vendor/autoload.php';

use Kingbes\Libui\App as LibuiApp;
use Kingbes\Libui\Window;
use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Button;
use Kingbes\Libui\Control;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Checkbox;
use Kingbes\Libui\Combobox;
use Kingbes\Libui\Spinbox;

// 初始化应用
LibuiApp::init();

// 创建主窗口
\$window = Window::create("$appName", 600, 500, 1);
Window::setMargined(\$window, true);

// 创建主容器
\$box = Box::newVerticalBox();
Box::setPadded(\$box, true);

// 添加标题
\$titleLabel = Label::create("$appName v1.0");
Box::append(\$box, \$titleLabel, false);

// 添加说明
\$descLabel = Label::create("智能参数配置界面");
Box::append(\$box, \$descLabel, false);

// CLI 参数定义
\$cliParameters = $cliParams;

// 为每个参数创建控件
\$paramControls = [];
foreach (\$cliParameters as \$param) {
    // 参数标签
    \$paramLabel = Label::create(\$param['name'] . ": " . \$param['description']);
    Box::append(\$box, \$paramLabel, false);

    // 根据参数类型创建不同的控件
    switch (\$param['type']) {
        case 'boolean':
            // 复选框
            \$checkbox = Checkbox::create("启用 " . \$param['name']);
            Box::append(\$box, \$checkbox, false);
            \$paramControls[\$param['name']] = \$checkbox;
            break;

        case 'integer':
            // 微调框
            \$spinbox = Spinbox::create(-1000000, 1000000);
            Box::append(\$box, \$spinbox, false);
            \$paramControls[\$param['name']] = \$spinbox;
            break;

        case 'choice':
            // 下拉框
            if (isset(\$param['choices'])) {
                \$combobox = Combobox::create();
                foreach (\$param['choices'] as \$choice) {
                    Combobox::append(\$combobox, \$choice);
                }
                Box::append(\$box, \$combobox, false);
                \$paramControls[\$param['name']] = \$combobox;
            } else {
                // 普通输入框
                \$entry = Entry::create();
                Box::append(\$box, \$entry, false);
                \$paramControls[\$param['name']] = \$entry;
            }
            break;

        case 'string':
        default:
            // 普通输入框
            \$entry = Entry::create();
            Box::append(\$box, \$entry, false);
            \$paramControls[\$param['name']] = \$entry;
            break;
    }

    // 添加分隔符
    \$separator = \Kingbes\Libui\Separator::createHorizontal();
    Box::append(\$box, \$separator, false);
}

// 添加运行按钮
\$runButton = Button::create("运行程序");
Button::onClicked(\$runButton, function(\$btn) use (\$paramControls, \$cliParameters) {
    // 收集参数值
    \$params = [];
    foreach (\$cliParameters as \$param) {
        \$name = \$param['name'];
        if (isset(\$paramControls[\$name])) {
            \$control = \$paramControls[\$name];

            switch (\$param['type']) {
                case 'boolean':
                    if (Checkbox::checked(\$control)) {
                        \$params[] = "--" . \$name;
                    }
                    break;

                case 'integer':
                    \$value = Spinbox::value(\$control);
                    \$params[] = "--" . \$name . "=" . \$value;
                    break;

                case 'choice':
                case 'string':
                default:
                    \$value = Entry::text(\$control);
                    if (!empty(\$value)) {
                        \$params[] = "--" . \$name . "=" . escapeshellarg(\$value);
                    }
                    break;
            }
        }
    }

    // 确定如何运行源文件
    \$sourceFile = "$sourceFile";
    if (substr(\$sourceFile, -4) === '.php' || substr(\$sourceFile, -5) === '.phar') {
        \$command = "php " . escapeshellarg(\$sourceFile) . " " . implode(" ", \$params);
    } else {
        // 对于二进制文件或其他脚本，直接运行
        \$command = escapeshellarg(\$sourceFile) . " " . implode(" ", \$params);
    }

    // 执行命令
    echo "运行命令: " . \$command . "\\n";

    // 使用异步方式执行命令以避免阻塞GUI
    \$descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w"),
       2 => array("pipe", "w")
    );

    \$process = proc_open(\$command, \$descriptorspec, \$pipes);

    if (is_resource(\$process)) {
        // 关闭输入管道
        fclose(\$pipes[0]);

        // 读取输出
        \$output = stream_get_contents(\$pipes[1]);
        \$errors = stream_get_contents(\$pipes[2]);

        // 关闭管道
        fclose(\$pipes[1]);
        fclose(\$pipes[2]);

        // 获取返回码
        \$return_value = proc_close(\$process);

        // 显示输出
        echo "输出:\\n" . \$output;
        if (!empty(\$errors)) {
            echo "错误:\\n" . \$errors;
        }
        echo "返回码: " . \$return_value . "\\n";
    } else {
        echo "无法启动进程\\n";
    }
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

        file_put_contents($outputDir . '/smart_gui_wrapper.php', $wrapperContent);
        $this->appendOutput("  智能 GUI 包装器创建完成\n");
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
            $outputDir = Entry::text($this->outputEntry);
            $appName = Entry::text($this->appNameEntry);
            $appOutputDir = $outputDir . '/' . $appName;

            if (empty($outputDir) || empty($appName) || !is_dir($appOutputDir)) {
                // 获取主窗口引用
                global $application;
                $window = $application->getWindow();

                // 显示错误信息
                Window::msgBoxError(
                    $window,
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
                $window,
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
                $this->appendOutput("不支持的操作系统: " . PHP_OS_FAMILY . "\n");
                return;
            }
        } catch (\Exception $e) {
            $this->appendOutput("无法打开目录: " . $e->getMessage() . "\n");
        }
    }

    private function appendOutput($text)
    {
        $currentText = MultilineEntry::text($this->outputArea);
        $newText = $currentText . $text;
        MultilineEntry::setText($this->outputArea, $newText);
    }

    public function getControl()
    {
        return $this->box;
    }
}