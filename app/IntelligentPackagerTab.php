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

class IntelligentPackagerTab
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
    private $analysisCompleted = false;
    private $parameterKeys = [];
    private $currentParameterIndex = 0;
    private $prevButton;
    private $nextButton;
    private $tempOutput = ''; // 临时存储输出内容，直到$outputArea初始化完成
    
    // 新增：步骤控制相关属性
    private $stepContainer;
    private $step1Container;
    private $step2Container;
    private $step3Container;
    private $stepIndicator;
    private $backToStep1Button;

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

        // 添加步骤指示器
        $this->stepIndicator = Label::create("步骤 1: 输入应用信息");
        Box::append($this->box, $this->stepIndicator, false);

        // 创建步骤容器
        $this->stepContainer = Box::newVerticalBox();
        Box::setPadded($this->stepContainer, true);
        Box::append($this->box, $this->stepContainer, true);

        // 创建三个步骤的容器
        $this->createStepContainers();

        // 显示第一步
        $this->showStep(1);

        // 检查 PHAR 配置并设置复选框状态
        $this->checkPharConfiguration();
        
        $this->appendOutput("初始化完成，显示步骤1\n");
    }

    private function createStepContainers()
    {
        $this->appendOutput("开始创建步骤容器\n");
        
        // 步骤1: 输入应用信息
        $this->step1Container = Box::newVerticalBox();
        Box::setPadded($this->step1Container, true);
        $this->addInputControls($this->step1Container);
        Box::append($this->stepContainer, $this->step1Container, true);

        // 步骤2: 分析应用后的动态元素确认界面
        $this->step2Container = Box::newVerticalBox();
        Box::setPadded($this->step2Container, true);
        $this->addDynamicParameterControls($this->step2Container);
        Box::append($this->stepContainer, $this->step2Container, true);
        Control::hide($this->step2Container); // 初始隐藏

        // 步骤3: 打包输出
        $this->step3Container = Box::newVerticalBox();
        Box::setPadded($this->step3Container, true);
        $this->addOutputControls($this->step3Container);
        Box::append($this->stepContainer, $this->step3Container, true);
        Control::hide($this->step3Container); // 初始隐藏
        
        // 添加导航按钮（仅用于测试）
        $navBox = Box::newHorizontalBox();
        Box::setPadded($navBox, true);
        Box::append($this->box, $navBox, false);

        $prevButton = Button::create("上一步(测试)");
        Button::onClicked($prevButton, function ($btn) {
            static $currentStep = 1;
            $currentStep = $currentStep > 1 ? $currentStep - 1 : 3;
            $this->showStep($currentStep);
        });
        Box::append($navBox, $prevButton, true);

        $nextButton = Button::create("下一步(测试)");
        Button::onClicked($nextButton, function ($btn) {
            static $currentStep = 1;
            $currentStep = $currentStep < 3 ? $currentStep + 1 : 1;
            $this->showStep($currentStep);
        });
        Box::append($navBox, $nextButton, true);
        
        $this->appendOutput("步骤容器创建完成\n");
    }

    private function showStep($step)
    {
        $this->appendOutput("进入 showStep 方法，参数: $step\n");
        
        // 隐藏所有步骤容器
        Control::hide($this->step1Container);
        Control::hide($this->step2Container);
        Control::hide($this->step3Container);
        
        $this->appendOutput("已隐藏所有步骤容器\n");
        $this->appendOutput("隐藏后 step1Container 是否可见: " . (Control::visible($this->step1Container) ? "是" : "否") . "\n");
        $this->appendOutput("隐藏后 step2Container 是否可见: " . (Control::visible($this->step2Container) ? "是" : "否") . "\n");
        $this->appendOutput("隐藏后 step3Container 是否可见: " . (Control::visible($this->step3Container) ? "是" : "否") . "\n");
        
        // 根据步骤显示对应内容
        switch ($step) {
            case 1:
                Control::show($this->step1Container);
                Label::setText($this->stepIndicator, "步骤 1: 输入应用信息");
                if ($this->backToStep1Button) {
                    Control::hide($this->backToStep1Button);
                }
                $this->appendOutput("显示步骤1\n");
                $this->appendOutput("显示后 step1Container 是否可见: " . (Control::visible($this->step1Container) ? "是" : "否") . "\n");
                break;
            case 2:
                Control::show($this->step2Container);
                Label::setText($this->stepIndicator, "步骤 2: 分析应用后的动态元素确认");
                if ($this->backToStep1Button) {
                    Control::hide($this->backToStep1Button);
                }
                // 确保动态容器是可见的
                if ($this->dynamicContainer) {
                    Control::show($this->dynamicContainer);
                }
                $this->appendOutput("显示步骤2\n");
                // 添加调试信息
                $this->appendOutput("显示后 step2Container 是否可见: " . (Control::visible($this->step2Container) ? "是" : "否") . "\n");
                $this->appendOutput("显示后 step1Container 是否可见: " . (Control::visible($this->step1Container) ? "是" : "否") . "\n");
                break;
            case 3:
                Control::show($this->step3Container);
                Label::setText($this->stepIndicator, "步骤 3: 打包输出");
                if ($this->backToStep1Button) {
                    Control::show($this->backToStep1Button);
                }
                $this->appendOutput("显示步骤3\n");
                $this->appendOutput("显示后 step3Container 是否可见: " . (Control::visible($this->step3Container) ? "是" : "否") . "\n");
                break;
        }
        
        // 尝试强制刷新界面
        // 注意：这里可能需要根据具体的GUI库实现来调整
        $this->appendOutput("尝试强制刷新界面\n");
        $this->appendOutput("退出 showStep 方法\n");
    }

    private function clearStep2UI()
    {
        // 清空动态参数容器
        while (true) {
            try {
                Box::delete($this->dynamicContainer, 0);
            } catch (\Exception $e) {
                // 当没有更多子元素时会抛出异常，此时退出循环
                break;
            }
        }

        // 重新添加默认提示
        $hintLabel = Label::create("请选择一个 CLI 文件，然后点击'分析参数'按钮");
        Box::append($this->dynamicContainer, $hintLabel, false);
        
        // 重置参数相关状态
        $this->cliParameters = [];
        $this->paramConfigControls = [];
        $this->analysisCompleted = false;
        $this->parameterKeys = [];
        $this->currentParameterIndex = 0;
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
            // 当源文件路径改变时，重置分析状态
            $this->analysisCompleted = false;
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

        // 分析参数按钮
        $analyzeButton = Button::create("分析参数");
        Button::onClicked($analyzeButton, function ($btn) {
            $this->appendOutput("分析参数按钮被点击\n");
            $this->analyzeCliParameters();
            $this->appendOutput("分析参数按钮点击事件处理完成\n");
        });
        Box::append($buttonBox, $analyzeButton, true);

        // 打包按钮
        $this->packageButton = Button::create("开始打包");
        Button::onClicked($this->packageButton, function ($btn) {
            if (!$this->analysisCompleted) {
                $this->appendOutput("请先点击'分析参数'按钮分析CLI程序的参数\n");
                return;
            }
            $this->startPackaging();
            // 打包开始时跳转到第三步
            $this->showStep(3);
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
        Box::append($container, $dynamicGroup, true); // 改为true以获得更多空间

        $this->dynamicContainer = Box::newVerticalBox();
        Box::setPadded($this->dynamicContainer, true);
        Group::setChild($dynamicGroup, $this->dynamicContainer);

        // 添加默认提示
        $hintLabel = Label::create("请选择一个 CLI 文件，然后点击'分析参数'按钮");
        Box::append($this->dynamicContainer, $hintLabel, false);
        
        // 确保动态容器初始是可见的
        Control::show($this->dynamicContainer);
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
        
        // 如果有临时输出内容，现在输出到控件中
        if (!empty($this->tempOutput)) {
            MultilineEntry::setText($this->outputArea, $this->tempOutput);
            $this->tempOutput = ''; // 清空临时输出
        }
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
                // 重置分析状态
                $this->analysisCompleted = false;
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

    private function analyzeCliParameters()
    {
        $this->appendOutput("进入 analyzeCliParameters 方法\n");
        
        $sourceFile = Entry::text($this->sourceEntry);

        // 检查文件是否存在
        if (empty($sourceFile) || !file_exists($sourceFile)) {
            $this->appendOutput("错误: 请指定有效的源文件路径\n");
            return;
        }

        // 保存CLI文件路径
        $this->cliFile = $sourceFile;

        // 清空之前的参数
        $this->cliParameters = [];

        // 通过运行CLI程序获取帮助信息来分析参数
        $this->parseCliParametersByExecution();

        // 标记分析已完成
        $this->analysisCompleted = true;

        // 更新动态参数界面
        $this->updateDynamicParameterControls();

        $this->appendOutput("参数分析完成，可以进行打包了\n");
        
        // 分析完成后跳转到第二步
        $this->appendOutput("准备切换到步骤2\n");
        $this->showStep(2);
        $this->appendOutput("已调用showStep(2)\n");
        $this->appendOutput("step1Container 是否可见: " . (Control::visible($this->step1Container) ? "是" : "否") . "\n");
        $this->appendOutput("step2Container 是否可见: " . (Control::visible($this->step2Container) ? "是" : "否") . "\n");
        $this->appendOutput("退出 analyzeCliParameters 方法\n");
    }

    private function parseCliParametersByExecution()
    {
        $this->appendOutput("进入 parseCliParametersByExecution 方法\n");
        
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
        $helpCommands = [' --help', ' -h', ' /?', ' --usage', ' -help', ' help'];
        $helpOutput = '';

        foreach ($helpCommands as $helpCmd) {
            $fullCommand = $command . $helpCmd . ' 2>&1';
            $output = [];
            $returnCode = 0;

            // 使用exec而不是system来捕获输出
            exec($fullCommand, $output, $returnCode);

            // 收集输出
            $helpOutput .= implode("\n", $output);

            // 如果命令成功执行或返回常见的帮助信息退出码
            if ($returnCode <= 1 && !empty($output)) {
                break;
            }
        }

        // 如果没有获取到帮助信息，尝试直接运行命令看是否有帮助信息输出
        if (empty($helpOutput)) {
            $fullCommand = $command . ' 2>&1';
            $output = [];
            exec($fullCommand, $output, $returnCode);

            // 如果程序输出了信息并且很快退出，可能是帮助信息
            if (!empty($output)) {
                $helpOutput = implode("\n", $output);
            }
        }

        // 解析帮助信息中的参数
        $this->parseHelpOutput($helpOutput);
        
        // 如果仍然没有参数，添加一个默认参数用于测试
        if (empty($this->cliParameters)) {
            $this->appendOutput("未检测到参数，添加默认参数用于测试\n");
            $this->cliParameters['test'] = [
                'name' => 'test',
                'short' => null,
                'description' => '测试参数',
                'required' => false,
                'type' => 'string',
                'default' => ''
            ];
        }
        
        $this->appendOutput("退出 parseCliParametersByExecution 方法\n");
    }

    private function parseHelpOutput($helpOutput)
    {
        if (empty($helpOutput)) {
            $this->appendOutput("未检测到 CLI 参数，将使用默认运行方式\n");
            return;
        }

        // 输出帮助信息以便调试
        $this->appendOutput("检测到的帮助信息:\n" . $helpOutput . "\n");

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
                        'type' => 'string',
                        'default' => ''
                    ];
                }
            }
        }

        // 查找短选项 -p, --param 格式并关联长短选项
        $lines = explode("\n", $helpOutput);
        foreach ($lines as $line) {
            // 查找 -f, --file FILE 格式的行
            if (preg_match('/^\s*(-[a-zA-Z]),\s*(--?[a-zA-Z0-9\-_]+)/', $line, $matches)) {
                $shortOpt = ltrim($matches[1], '-');
                $longOpt = ltrim($matches[2], '-');

                // 如果长选项已存在，更新其短选项信息
                if (isset($this->cliParameters[$longOpt])) {
                    $this->cliParameters[$longOpt]['short'] = $shortOpt;
                }
                // 如果长选项不存在但短选项不存在，添加短选项
                else if (!isset($this->cliParameters[$shortOpt])) {
                    $this->cliParameters[$shortOpt] = [
                        'name' => $shortOpt,
                        'short' => $shortOpt,
                        'description' => 'Auto-detected short parameter',
                        'required' => false,
                        'type' => 'string',
                        'default' => ''
                    ];
                }
            }
        }

        // 查找单独的短选项
        if (preg_match_all('/\s-([a-zA-Z])[\s,]/', $helpOutput, $shortOpts)) {
            foreach ($shortOpts[1] as $opt) {
                // 过滤掉一些常见的非参数选项
                if (!in_array($opt, ['h', 'v', 'V'])) {
                    if (!isset($this->cliParameters[$opt]) && !isset($this->cliParameters[$opt])) {
                        $this->cliParameters[$opt] = [
                            'name' => $opt,
                            'short' => $opt,
                            'description' => 'Auto-detected short parameter',
                            'required' => false,
                            'type' => 'string',
                            'default' => ''
                        ];
                    }
                }
            }
        }

        // 查找带值的选项 --param=value 或 --param value
        $lines = explode("\n", $helpOutput);
        foreach ($lines as $line) {
            // 查找 --file FILE 或 -f, --file FILE 格式的行
            if (preg_match('/^\s*(-[a-zA-Z]),\s*(--?[a-zA-Z0-9\-_]+)\s+([A-Z_]+)\s*(.*)$/', $line, $matches) ||
                preg_match('/^\s*(--?[a-zA-Z0-9\-_]+)\s+([A-Z_]+)\s*(.*)$/', $line, $matches)) {

                // 确定参数名和值类型
                if (count($matches) == 5) {
                    // 匹配到 -f, --file FILE 格式
                    $param = ltrim($matches[2], '-');
                    $valueType = $matches[3];
                } else if (count($matches) == 4) {
                    // 匹配到 --file FILE 格式
                    $param = ltrim($matches[1], '-');
                    $valueType = $matches[2];
                } else {
                    continue;
                }

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
                            $this->cliParameters[$param]['type'] = 'file';
                            break;
                    }
                }
            }
        }

        // 查找帮助文本中的参数描述
        $this->parseHelpDescriptions($helpOutput);

        // 特殊处理verbose参数类型
        foreach ($this->cliParameters as $name => &$param) {
            // 检查是否为verbose相关参数
            if (strpos($name, 'verbose') !== false || strpos($name, 'verbos') !== false) {
                $param['type'] = 'boolean';
            }
            // 检查描述中是否包含"详细"、"verbose"等关键词
            else if (isset($param['description']) &&
                     (strpos($param['description'], '详细') !== false ||
                      strpos($param['description'], 'verbose') !== false ||
                      strpos($param['description'], '详细输出') !== false)) {
                $param['type'] = 'boolean';
            }
            // 特殊处理互斥参数（如-h/--help）
            else if (in_array($name, ['h', 'help'])) {
                $param['type'] = 'boolean';
                $param['required'] = false; // 互斥参数通常不是必需的
            }
        }

        // 标记必需参数
        foreach ($this->cliParameters as $name => &$param) {
            // 检查描述中是否包含"必需"、"required"等关键词
            if (isset($param['description']) &&
                (strpos($param['description'], '必需') !== false ||
                 strpos($param['description'], 'required') !== false ||
                 strpos($param['description'], '必须') !== false)) {
                $param['required'] = true;
            }
        }
    }

    private function parseHelpDescriptions($helpOutput)
    {
        // 查找参数和描述的模式
        $lines = explode("\n", $helpOutput);
        foreach ($lines as $line) {
            // 查找 -f, --file FILE          Input file path (required) 格式的行
            if (preg_match('/^\s*(-[a-zA-Z]),\s*(--?[a-zA-Z0-9\-_]+)\s+[A-Z_]*\s*(.+)$/', $line, $matches)) {
                $shortOpt = ltrim($matches[1], '-');
                $longOpt = ltrim($matches[2], '-');
                $description = trim($matches[3]);

                // 更新长选项描述
                if (isset($this->cliParameters[$longOpt])) {
                    $this->cliParameters[$longOpt]['description'] = $description;
                }
                // 更新短选项描述
                if (isset($this->cliParameters[$shortOpt])) {
                    $this->cliParameters[$shortOpt]['description'] = $description;
                }
            }
            // 查找 --file FILE          Input file path (required) 格式的行
            else if (preg_match('/^\s*(--?[a-zA-Z0-9\-_]+)\s+[A-Z_]*\s*(.+)$/', $line, $matches)) {
                $param = ltrim($matches[1], '-');
                $description = trim($matches[2]);

                if (isset($this->cliParameters[$param])) {
                    $this->cliParameters[$param]['description'] = $description;
                }
            }
            // 查找 -p, --param 描述 或 --param 描述 的模式
            else if (preg_match('/^[\s]*(-[a-zA-Z],?[\s]*)?(--?[a-zA-Z0-9\-_]+)[\s,]+(.+)$/', $line, $matches)) {
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
            // 查找 -p 描述 的模式
            else if (preg_match('/^[\s]*(-[a-zA-Z])[\s]{2,}(.+)$/', $line, $matches)) {
                $param = str_replace('-', '', $matches[1]);
                $description = trim($matches[2]);

                if (isset($this->cliParameters[$param])) {
                    $this->cliParameters[$param]['description'] = $description;
                }
            }
        }
    }

    private function updateDynamicParameterControls()
    {
        $this->appendOutput("开始更新动态参数控件\n");
        
        // 清空动态容器
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
            $this->appendOutput("未检测到CLI参数\n");
            $hintLabel = Label::create("未检测到 CLI 参数，将使用默认运行方式");
            Box::append($this->dynamicContainer, $hintLabel, false);
            
            // 添加提示信息，建议用户查看参数配置
            $tipLabel = Label::create("参数配置界面已更新，请查看上方的'CLI 参数配置'区域");
            Box::append($this->dynamicContainer, $tipLabel, false);
            
            // 确保动态容器是可见的
            Control::show($this->dynamicContainer);
            $this->appendOutput("已显示动态容器（无参数情况）\n");
            return;
        }

        // 显示参数配置界面
        $this->appendOutput("显示参数配置界面，参数数量: " . count($this->cliParameters) . "\n");
        $this->showParameterConfigForm();

        // 添加提示信息，建议用户查看参数配置
        $tipLabel = Label::create("参数配置界面已更新，请查看上方的'CLI 参数配置'区域");
        Box::append($this->dynamicContainer, $tipLabel, false);
        
        // 确保动态容器是可见的
        Control::show($this->dynamicContainer);
        $this->appendOutput("已显示动态容器（有参数情况）\n");
    }

    private function showParameterConfigForm()
    {
        $this->appendOutput("开始创建参数配置表单\n");

        // 添加标题
        $titleLabel = Label::create("参数配置");
        Box::append($this->dynamicContainer, $titleLabel, false);

        $descLabel = Label::create("请配置以下参数");
        Box::append($this->dynamicContainer, $descLabel, false);

        // 创建分步导航按钮
        $navBox = Box::newHorizontalBox();
        Box::setPadded($navBox, true);
        Box::append($this->dynamicContainer, $navBox, false);

        $this->prevButton = Button::create("上一步");
        Button::onClicked($this->prevButton, function ($btn) {
            $this->showPreviousParameter();
        });
        Box::append($navBox, $this->prevButton, true);
        Control::disable($this->prevButton); // 初始禁用

        $this->nextButton = Button::create("下一步");
        Button::onClicked($this->nextButton, function ($btn) {
            $this->showNextParameter();
        });
        Box::append($navBox, $this->nextButton, true);

        // 为每个参数创建配置控件
        $this->parameterKeys = array_keys($this->cliParameters);
        $this->currentParameterIndex = 0;

        $paramCount = 0;
        foreach ($this->cliParameters as $name => $param) {
            $this->appendOutput("创建参数控件: $name\n");
            $this->createParameterConfigControl($name, $param);
            $paramCount++;
        }

        // 初始只显示第一个参数
        $this->showCurrentParameter();

        $this->appendOutput("参数配置表单创建完成，共创建了 $paramCount 个参数控件\n");
        
        // 确保动态容器是可见的
        Control::show($this->dynamicContainer);
        $this->appendOutput("已确保动态容器可见\n");
    }

    private function showCurrentParameter()
    {
        $this->appendOutput("显示当前参数，索引: " . $this->currentParameterIndex . "\n");
        
        // 隐藏所有参数控件
        foreach ($this->paramConfigControls as $name => $controls) {
            if (isset($controls['group'])) {
                Control::hide($controls['group']);
            }
        }

        // 显示当前参数控件
        if (isset($this->parameterKeys[$this->currentParameterIndex])) {
            $currentParamName = $this->parameterKeys[$this->currentParameterIndex];
            if (isset($this->paramConfigControls[$currentParamName]['group'])) {
                Control::show($this->paramConfigControls[$currentParamName]['group']);
                $this->appendOutput("显示参数控件: $currentParamName\n");
            }
        }

        // 更新导航按钮状态
        if ($this->currentParameterIndex > 0) {
            Control::enable($this->prevButton);
        } else {
            Control::disable($this->prevButton);
        }

        if ($this->currentParameterIndex < count($this->parameterKeys) - 1) {
            Control::enable($this->nextButton);
        } else {
            Control::disable($this->nextButton);
        }
        
        // 确保动态容器是可见的
        if ($this->dynamicContainer) {
            Control::show($this->dynamicContainer);
        }
    }

    private function showNextParameter()
    {
        if ($this->currentParameterIndex < count($this->parameterKeys) - 1) {
            $this->currentParameterIndex++;
            $this->showCurrentParameter();
        }
    }

    private function showPreviousParameter()
    {
        if ($this->currentParameterIndex > 0) {
            $this->currentParameterIndex--;
            $this->showCurrentParameter();
        }
    }

    private function createParameterConfigControl($name, $param)
    {
        $this->appendOutput("开始创建参数控件: $name\n");
        
        // 参数组
        $paramGroup = Group::create($name);
        Group::setMargined($paramGroup, true);
        Box::append($this->dynamicContainer, $paramGroup, false);

        $paramBox = Box::newVerticalBox();
        Box::setPadded($paramBox, true);
        Group::setChild($paramGroup, $paramBox);

        // 特殊处理互斥参数（如-h/--help）
        $isHelpParam = in_array($name, ['h', 'help']);
        if ($isHelpParam) {
            // 为互斥参数添加特殊标识
            $helpLabel = Label::create("注意: 这是一个互斥参数，勾选后其他参数将被忽略");
            Box::append($paramBox, $helpLabel, false);
        }

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
            'types' => $types,
            'group' => $paramGroup, // 添加group引用用于显示/隐藏
            'isHelpParam' => $isHelpParam // 标记是否为互斥参数
        ];

        // 初始隐藏所有参数控件，除了第一个
        if ($name !== ($this->parameterKeys[0] ?? '')) {
            Control::hide($paramGroup);
            $this->appendOutput("隐藏参数控件: $name\n");
        } else {
            Control::show($paramGroup);
            $this->appendOutput("显示参数控件: $name\n");
        }
        
        $this->appendOutput("完成创建参数控件: $name\n");
    }

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

    private function validateRequiredParameters()
    {
        foreach ($this->cliParameters as $name => $param) {
            if ($param['required'] ?? false) {
                // 检查是否有默认值或用户输入值
                $hasValue = false;
                if (isset($this->paramConfigControls[$name])) {
                    $controls = $this->paramConfigControls[$name];
                    $defaultValue = Entry::text($controls['default']);
                    if (!empty($defaultValue)) {
                        $hasValue = true;
                    }
                }

                if (!$hasValue) {
                    $this->appendOutput("错误: 必需参数 '{$name}' 没有提供值\n");
                    return false;
                }
            }
        }
        return true;
    }

    private function startPackaging()
    {
        try {
            // 更新参数配置
            $this->updateParameterConfigurations();

            // 验证必需参数
            if (!$this->validateRequiredParameters()) {
                return;
            }

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
use Kingbes\Libui\Slider;

// 初始化应用
LibuiApp::init();

// 创建主窗口（增加窗口大小以容纳更多控件）
\$window = Window::create("$appName - 参数配置", 800, 600, 1);
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
\$helpParamControl = null; // 用于存储互斥参数控件

foreach (\$cliParameters as \$param) {
    // 参数标签
    \$paramLabel = Label::create(\$param['name'] . ": " . (\$param['description'] ?? ''));
    Box::append(\$box, \$paramLabel, false);

    // 特殊处理互斥参数（如-h/--help）
    \$isHelpParam = in_array(\$param['name'], ['h', 'help']);
    if (\$isHelpParam) {
        // 为互斥参数创建复选框
        \$helpCheckbox = Checkbox::create("显示帮助信息");
        Box::append(\$box, \$helpCheckbox, false);
        \$paramControls[\$param['name']] = \$helpCheckbox;
        \$helpParamControl = \$helpCheckbox;

        // 添加分隔符
        \$separator = \Kingbes\Libui\Separator::createHorizontal();
        Box::append(\$box, \$separator, false);
        continue; // 跳过其他控件创建
    }

    // 根据参数类型创建不同的控件
    switch (\$param['type']) {
        case 'boolean':
            // 复选框
            \$checkbox = Checkbox::create("启用 " . \$param['name']);
            if (!empty(\$param['default'])) {
                Checkbox::setChecked(\$checkbox, (bool)\$param['default']);
            }
            Box::append(\$box, \$checkbox, false);
            \$paramControls[\$param['name']] = \$checkbox;
            break;

        case 'integer':
            // 微调框
            \$spinbox = Spinbox::create(-1000000, 1000000);
            if (!empty(\$param['default'])) {
                Spinbox::setValue(\$spinbox, (int)\$param['default']);
            }
            Box::append(\$box, \$spinbox, false);
            \$paramControls[\$param['name']] = \$spinbox;
            break;

        case 'file':
            // 文件路径输入框和选择按钮
            \$fileBox = Box::newHorizontalBox();
            Box::setPadded(\$fileBox, true);
            Box::append(\$box, \$fileBox, false);

            \$fileEntry = Entry::create();
            if (!empty(\$param['default'])) {
                Entry::setText(\$fileEntry, \$param['default']);
            }
            Box::append(\$fileBox, \$fileEntry, true);

            \$fileButton = Button::create("选择文件");
            // 注意：这里简化处理，实际应用中需要实现文件选择功能
            Box::append(\$fileBox, \$fileButton, false);

            \$paramControls[\$param['name']] = \$fileEntry;
            break;

        case 'string':
        default:
            // 普通输入框
            \$entry = Entry::create();
            if (!empty(\$param['default'])) {
                Entry::setText(\$entry, \$param['default']);
            }
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
Button::onClicked(\$runButton, function(\$btn) use (\$paramControls, \$cliParameters, \$helpParamControl) {
    // 检查互斥参数
    \$useHelp = \$helpParamControl && Checkbox::checked(\$helpParamControl);

    // 收集参数值
    \$params = [];
    if (\$useHelp) {
        // 如果使用了互斥参数，只添加帮助参数
        \$params[] = "--help";
    } else {
        // 否则添加其他参数
        foreach (\$cliParameters as \$param) {
            // 跳过互斥参数
            if (in_array(\$param['name'], ['h', 'help'])) {
                continue;
            }

            \$name = \$param['name'];
            if (isset(\$paramControls[\$name])) {
                \$control = \$paramControls[\$name];

                switch (\$param['type']) {
                    case 'boolean':
                        if (Checkbox::checked(\$control)) {
                            // 对于verbose等参数，可能需要特殊处理
                            if (strpos(\$name, 'verbose') !== false) {
                                // 可以根据需要添加多个-v选项
                                \$params[] = "-" . str_repeat("v", 1); // 默认一个v
                            } else {
                                \$params[] = "--" . \$name;
                            }
                        }
                        break;

                    case 'integer':
                        \$value = Spinbox::value(\$control);
                        \$params[] = "--" . \$name . "=" . \$value;
                        break;

                    case 'file':
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
        $this->appendOutput("  创建源码压缩包: $zipFile\n");

        try {
            // 创建ZIP压缩包
            $zip = new \ZipArchive();
            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                // 添加应用目录中的所有文件
                $this->addFilesToZip($zip, $outputDir, $outputDir);
                $zip->close();
                $this->appendOutput("  源码压缩包创建完成: $zipFile\n");
            } else {
                $this->appendOutput("  创建源码压缩包失败\n");
            }
        } catch (\Exception $e) {
            $this->appendOutput("  创建源码压缩包时出错: " . $e->getMessage() . "\n");
        }
    }

    private function addFilesToZip($zip, $basePath, $dir)
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dir . '/' . $file;
            $relativePath = substr($filePath, strlen($basePath) + 1);

            if (is_dir($filePath)) {
                // 添加目录
                $zip->addEmptyDir($relativePath);
                // 递归添加子目录
                $this->addFilesToZip($zip, $basePath, $filePath);
            } else {
                // 添加文件
                $zip->addFile($filePath, $relativePath);
            }
        }
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
        // 检查$outputArea是否已初始化
        if ($this->outputArea === null) {
            // 如果未初始化，暂时存储到临时变量中
            $this->tempOutput .= $text;
            return;
        }
        
        // 如果有临时输出内容，先输出临时内容
        if (!empty($this->tempOutput)) {
            $currentText = MultilineEntry::text($this->outputArea);
            $newText = $currentText . $this->tempOutput;
            MultilineEntry::setText($this->outputArea, $newText);
            $this->tempOutput = ''; // 清空临时输出
        }
        
        $currentText = MultilineEntry::text($this->outputArea);
        $newText = $currentText . $text;
        MultilineEntry::setText($this->outputArea, $newText);

        // 滚动到底部（如果可能的话）
        // 注意：libui PHP绑定可能不支持直接滚动到底部
    }

    public function getControl()
    {
        return $this->box;
    }
}
