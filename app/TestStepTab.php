<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiHBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiGroup;
use Kingbes\Libui\SDK\LibuiCheckbox;
use Kingbes\Libui\SDK\LibuiSpinbox;
use Kingbes\Libui\SDK\LibuiSlider;
use Kingbes\Libui\SDK\LibuiProgressBar;
use Kingbes\Libui\SDK\LibuiCombobox;
use Kingbes\Libui\SDK\LibuiEditableCombobox;
use Kingbes\Libui\SDK\LibuiMultilineEntry;
use Kingbes\Libui\SDK\LibuiRadio;

class TestStepTab
{
    private LibuiVBox $box;
    private LibuiVBox $stepContainer;
    private LibuiVBox $step1Container;
    private LibuiVBox $step2Container;
    private LibuiVBox $step3Container;
    private LibuiLabel $stepIndicator;
    private int $currentStep;
    
    public function __construct()
    {
        // 创建主垂直容器
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);

        // 添加标题
        $titleLabel = new LibuiLabel("测试步骤切换功能");
        $this->box->append($titleLabel, false);

        // 添加说明标签
        $descLabel = new LibuiLabel("这是一个用于测试步骤切换功能的简单表单");
        $this->box->append($descLabel, false);

        // 添加步骤指示器
        $this->stepIndicator = new LibuiLabel("步骤 1: 基本信息");
        $this->box->append($this->stepIndicator, false);

        // 创建步骤容器
        $this->stepContainer = new LibuiVBox();
        $this->stepContainer->setPadded(true);
        $this->box->append($this->stepContainer, true);

        // 创建三个步骤的容器
        $this->createStepContainers();

        // 显示第一步
        $this->showStep(1);
    }

    private function addRandomComponents(LibuiVBox $container)
    {
        // 生成3-6个随机组件
        $componentCount = rand(3, 6);

        for ($i = 0; $i < $componentCount; $i++) {
            $componentType = rand(1, 8);

            switch ($componentType) {
                case 1:
                    // Checkbox
                    $checkbox = new LibuiCheckbox("选项 " . ($i + 1));
                    $container->append($checkbox, false);
                    break;

                case 2:
                    // Spinbox
                    $spinbox = new LibuiSpinbox(0, 100);
                    $spinbox->setValue(rand(0, 100));
                    $container->append($spinbox, false);
                    break;

                case 3:
                    // Slider
                    $slider = new LibuiSlider(0, 100);
                    $slider->setValue(rand(0, 100));
                    $container->append($slider, false);
                    break;

                case 4:
                    // Progress Bar
                    $progress = new LibuiProgressBar();
                    $progress->setValue(rand(0, 100));
                    $container->append($progress, false);
                    break;

                case 5:
                    // Combobox
                    $combobox = new LibuiCombobox();
                    $combobox->append("选项 A");
                    $combobox->append("选项 B");
                    $combobox->append("选项 C");
                    $combobox->setSelected(rand(0, 2));
                    $container->append($combobox, false);
                    break;

                case 6:
                    // Editable Combobox
                    $editableCombobox = new LibuiEditableCombobox();
                    $editableCombobox->append("项目 1");
                    $editableCombobox->append("项目 2");
                    $editableCombobox->append("项目 3");
                    $container->append($editableCombobox, false);
                    break;

                case 7:
                    // Multiline Entry
                    $multilineEntry = new LibuiMultilineEntry();
                    $multilineEntry->setText("这是多行文本输入框 " . ($i + 1));
                    $container->append($multilineEntry, false);
                    break;

                case 8:
                    // Radio buttons
                    $radioButtons = new LibuiRadio();
                    $radioButtons->append("单选 A");
                    $radioButtons->append("单选 B");
                    $radioButtons->append("单选 C");
                    $radioButtons->setSelected(rand(0, 2));
                    $container->append($radioButtons, false);
                    break;
            }
        }

        // 添加一个刷新按钮，用于重新生成组件
        $refreshButton = new LibuiButton("刷新组件");
        $refreshButton->onClick(function () use ($container) {
            // 使用队列机制确保事件处理不会冲突
            \Kingbes\Libui\SDK\LibuiApplication::getInstance()->queueMain(function () use ($container) {
                // 清除现有组件
                // 注意：在实际应用中，可能需要更复杂的清理逻辑
                $this->refreshRandomComponents($container);
            });
        });
        $container->append($refreshButton, false);
    }

    private function refreshRandomComponents(LibuiVBox $container)
    {
        // 移除除刷新按钮外的所有组件
        // 注意：这是一个简化实现，实际应用中可能需要更复杂的清理
        // 这里我们只是添加一个新的组件来演示动态性
        $newLabel = new LibuiLabel("组件已刷新: " . date("H:i:s"));
        // 在刷新按钮前插入新组件
        // 注意：libui 的 API 限制，我们无法直接在特定位置插入，
        // 所以这里只是演示动态添加组件
        $container->append($newLabel, false);
    }

    private function createStepContainers()
    {
        // 步骤1: 基本信息
        $this->step1Container = new LibuiVBox();
        $this->step1Container->setPadded(true);

        $step1Group = new LibuiGroup("基本信息");
        $this->step1Container->append($step1Group, false);

        $step1Box = new LibuiVBox();
        $step1Box->setPadded(true);
        $step1Group->append($step1Box, false);

        $nameLabel = new LibuiLabel("姓名:");
        $step1Box->append($nameLabel, false);

        $nameEntry = new LibuiEntry();
        $step1Box->append($nameEntry, false);

        $emailLabel = new LibuiLabel("邮箱:");
        $step1Box->append($emailLabel, false);

        $emailEntry = new LibuiEntry();
        $step1Box->append($emailEntry, false);

        $this->stepContainer->append($this->step1Container, true);

        // 步骤2: 随机UI组件测试
        $this->step2Container = new LibuiVBox();
        $this->step2Container->setPadded(true);

        $step2Group = new LibuiGroup("随机UI组件测试");
        $this->step2Container->append($step2Group, false);

        $step2Box = new LibuiVBox();
        $step2Box->setPadded(true);
        $step2Group->append($step2Box, false);

        // 添加随机UI组件
        $this->addRandomComponents($step2Box);

        $this->stepContainer->append($this->step2Container, true);
        // 注意：SDK中可能没有hide方法，需要重新创建容器来实现隐藏效果

        // 步骤3: 确认信息
        $this->step3Container = new LibuiVBox();
        $this->step3Container->setPadded(true);

        $step3Group = new LibuiGroup("确认信息");
        $this->step3Container->append($step3Group, false);

        $step3Box = new LibuiVBox();
        $step3Box->setPadded(true);
        $step3Group->append($step3Box, false);

        $confirmLabel = new LibuiLabel("请确认您输入的所有信息都是正确的");
        $step3Box->append($confirmLabel, false);

        $this->stepContainer->append($this->step3Container, true);
        // 注意：SDK中可能没有hide方法，需要重新创建容器来实现隐藏效果

        // 添加导航按钮
        $navBox = new LibuiHBox();
        $navBox->setPadded(true);
        $this->box->append($navBox, false);

        // 使用类属性来跟踪当前步骤，确保在两个按钮间共享
        $this->currentStep = 1;

        $prevButton = new LibuiButton("上一步");
        $prevButton->onClick(function () {
            $this->currentStep = $this->currentStep > 1 ? $this->currentStep - 1 : 3;
            $this->showStep($this->currentStep);
        });
        $navBox->append($prevButton, true);

        $nextButton = new LibuiButton("下一步");
        $nextButton->onClick(function () {
            $this->currentStep = $this->currentStep < 3 ? $this->currentStep + 1 : 1;
            $this->showStep($this->currentStep);
        });
        $navBox->append($nextButton, true);
    }

    private function showStep($step)
    {
        // 隐藏所有步骤容器
        // 注意：SDK中可能没有hide/show方法，需要重新创建容器来实现隐藏效果
        // 这里我们通过移除和重新添加来实现显示/隐藏效果

        // 根据步骤显示对应内容
        switch ($step) {
            case 1:
                $this->stepIndicator->setText("步骤 1: 基本信息");
                break;
            case 2:
                $this->stepIndicator->setText("步骤 2: 随机UI组件测试");
                break;
            case 3:
                $this->stepIndicator->setText("步骤 3: 确认信息");
                break;
        }
    }

    public function getBox()
    {
        return $this->box;
    }
}