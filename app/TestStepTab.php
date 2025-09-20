<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Button;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Group;
use Kingbes\Libui\Control;
use Kingbes\Libui\Checkbox;
use Kingbes\Libui\Spinbox;
use Kingbes\Libui\Slider;
use Kingbes\Libui\ProgressBar;
use Kingbes\Libui\Combobox;
use Kingbes\Libui\EditableCombobox;
use Kingbes\Libui\MultilineEntry;
use Kingbes\Libui\Radio;

class TestStepTab
{
    private $box;
    private $stepContainer;
    private $step1Container;
    private $step2Container;
    private $step3Container;
    private $stepIndicator;
    private $currentStep;
    
    public function __construct()
    {
        // 创建主垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);

        // 添加标题
        $titleLabel = Label::create("测试步骤切换功能");
        Box::append($this->box, $titleLabel, false);

        // 添加说明标签
        $descLabel = Label::create("这是一个用于测试步骤切换功能的简单表单");
        Box::append($this->box, $descLabel, false);

        // 添加步骤指示器
        $this->stepIndicator = Label::create("步骤 1: 基本信息");
        Box::append($this->box, $this->stepIndicator, false);

        // 创建步骤容器
        $this->stepContainer = Box::newVerticalBox();
        Box::setPadded($this->stepContainer, true);
        Box::append($this->box, $this->stepContainer, true);

        // 创建三个步骤的容器
        $this->createStepContainers();

        // 显示第一步
        $this->showStep(1);
    }

    private function addRandomComponents($container)
    {
        // 生成3-6个随机组件
        $componentCount = rand(3, 6);

        for ($i = 0; $i < $componentCount; $i++) {
            $componentType = rand(1, 8);

            switch ($componentType) {
                case 1:
                    // Checkbox
                    $checkbox = Checkbox::create("选项 " . ($i + 1));
                    Box::append($container, $checkbox, false);
                    break;

                case 2:
                    // Spinbox
                    $spinbox = Spinbox::create(0, 100);
                    Spinbox::setValue($spinbox, rand(0, 100));
                    Box::append($container, $spinbox, false);
                    break;

                case 3:
                    // Slider
                    $slider = Slider::create(0, 100);
                    Slider::setValue($slider, rand(0, 100));
                    Box::append($container, $slider, false);
                    break;

                case 4:
                    // Progress Bar
                    $progress = ProgressBar::create();
                    ProgressBar::setValue($progress, rand(0, 100));
                    Box::append($container, $progress, false);
                    break;

                case 5:
                    // Combobox
                    $combobox = Combobox::create();
                    Combobox::append($combobox, "选项 A");
                    Combobox::append($combobox, "选项 B");
                    Combobox::append($combobox, "选项 C");
                    Combobox::setSelected($combobox, rand(0, 2));
                    Box::append($container, $combobox, false);
                    break;

                case 6:
                    // Editable Combobox
                    $editableCombobox = EditableCombobox::create();
                    EditableCombobox::append($editableCombobox, "项目 1");
                    EditableCombobox::append($editableCombobox, "项目 2");
                    EditableCombobox::append($editableCombobox, "项目 3");
                    Box::append($container, $editableCombobox, false);
                    break;

                case 7:
                    // Multiline Entry
                    $multilineEntry = MultilineEntry::create();
                    MultilineEntry::setText($multilineEntry, "这是多行文本输入框 " . ($i + 1));
                    Box::append($container, $multilineEntry, false);
                    break;

                case 8:
                    // Radio buttons
                    $radioButtons = Radio::create();
                    Radio::append($radioButtons, "单选 A");
                    Radio::append($radioButtons, "单选 B");
                    Radio::append($radioButtons, "单选 C");
                    Radio::setSelected($radioButtons, rand(0, 2));
                    Box::append($container, $radioButtons, false);
                    break;
            }
        }

        // 添加一个刷新按钮，用于重新生成组件
        $refreshButton = Button::create("刷新组件");
        Button::onClicked($refreshButton, function ($btn) use ($container) {
            // 清除现有组件
            // 注意：在实际应用中，可能需要更复杂的清理逻辑
            $this->refreshRandomComponents($container);
        });
        Box::append($container, $refreshButton, false);
    }

    private function refreshRandomComponents($container)
    {
        // 移除除刷新按钮外的所有组件
        // 注意：这是一个简化实现，实际应用中可能需要更复杂的清理
        // 这里我们只是添加一个新的组件来演示动态性
        $newLabel = Label::create("组件已刷新: " . date("H:i:s"));
        // 在刷新按钮前插入新组件
        // 注意：libui 的 API 限制，我们无法直接在特定位置插入，
        // 所以这里只是演示动态添加组件
        Box::append($container, $newLabel, false);
    }

    private function createStepContainers()
    {
        // 步骤1: 基本信息
        $this->step1Container = Box::newVerticalBox();
        Box::setPadded($this->step1Container, true);

        $step1Group = Group::create("基本信息");
        Group::setMargined($step1Group, true);
        Box::append($this->step1Container, $step1Group, false);

        $step1Box = Box::newVerticalBox();
        Box::setPadded($step1Box, true);
        Group::setChild($step1Group, $step1Box);

        $nameLabel = Label::create("姓名:");
        Box::append($step1Box, $nameLabel, false);

        $nameEntry = Entry::create();
        Box::append($step1Box, $nameEntry, false);

        $emailLabel = Label::create("邮箱:");
        Box::append($step1Box, $emailLabel, false);

        $emailEntry = Entry::create();
        Box::append($step1Box, $emailEntry, false);

        Box::append($this->stepContainer, $this->step1Container, true);

        // 步骤2: 随机UI组件测试
        $this->step2Container = Box::newVerticalBox();
        Box::setPadded($this->step2Container, true);

        $step2Group = Group::create("随机UI组件测试");
        Group::setMargined($step2Group, true);
        Box::append($this->step2Container, $step2Group, false);

        $step2Box = Box::newVerticalBox();
        Box::setPadded($step2Box, true);
        Group::setChild($step2Group, $step2Box);

        // 添加随机UI组件
        $this->addRandomComponents($step2Box);

        Box::append($this->stepContainer, $this->step2Container, true);
        Control::hide($this->step2Container); // 初始隐藏

        // 步骤3: 确认信息
        $this->step3Container = Box::newVerticalBox();
        Box::setPadded($this->step3Container, true);

        $step3Group = Group::create("确认信息");
        Group::setMargined($step3Group, true);
        Box::append($this->step3Container, $step3Group, false);

        $step3Box = Box::newVerticalBox();
        Box::setPadded($step3Box, true);
        Group::setChild($step3Group, $step3Box);

        $confirmLabel = Label::create("请确认您输入的所有信息都是正确的");
        Box::append($step3Box, $confirmLabel, false);

        Box::append($this->stepContainer, $this->step3Container, true);
        Control::hide($this->step3Container); // 初始隐藏

        // 添加导航按钮
        $navBox = Box::newHorizontalBox();
        Box::setPadded($navBox, true);
        Box::append($this->box, $navBox, false);

        // 使用类属性来跟踪当前步骤，确保在两个按钮间共享
        $this->currentStep = 1;

        $prevButton = Button::create("上一步");
        Button::onClicked($prevButton, function ($btn) {
            $this->currentStep = $this->currentStep > 1 ? $this->currentStep - 1 : 3;
            $this->showStep($this->currentStep);
        });
        Box::append($navBox, $prevButton, true);

        $nextButton = Button::create("下一步");
        Button::onClicked($nextButton, function ($btn) {
            $this->currentStep = $this->currentStep < 3 ? $this->currentStep + 1 : 1;
            $this->showStep($this->currentStep);
        });
        Box::append($navBox, $nextButton, true);
    }

    private function showStep($step)
    {
        // 隐藏所有步骤容器
        Control::hide($this->step1Container);
        Control::hide($this->step2Container);
        Control::hide($this->step3Container);

        // 根据步骤显示对应内容
        switch ($step) {
            case 1:
                Control::show($this->step1Container);
                Label::setText($this->stepIndicator, "步骤 1: 基本信息");
                break;
            case 2:
                Control::show($this->step2Container);
                Label::setText($this->stepIndicator, "步骤 2: 随机UI组件测试");
                break;
            case 3:
                Control::show($this->step3Container);
                Label::setText($this->stepIndicator, "步骤 3: 确认信息");
                break;
        }
    }

    public function getBox()
    {
        return $this->box;
    }
}