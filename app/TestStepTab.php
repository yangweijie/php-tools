<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Button;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Group;
use Kingbes\Libui\Control;

class TestStepTab
{
    private $box;
    private $stepContainer;
    private $step1Container;
    private $step2Container;
    private $step3Container;
    private $stepIndicator;
    
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

        // 步骤2: 详细信息
        $this->step2Container = Box::newVerticalBox();
        Box::setPadded($this->step2Container, true);
        
        $step2Group = Group::create("详细信息");
        Group::setMargined($step2Group, true);
        Box::append($this->step2Container, $step2Group, false);
        
        $step2Box = Box::newVerticalBox();
        Box::setPadded($step2Box, true);
        Group::setChild($step2Group, $step2Box);
        
        $phoneLabel = Label::create("电话:");
        Box::append($step2Box, $phoneLabel, false);
        
        $phoneEntry = Entry::create();
        Box::append($step2Box, $phoneEntry, false);
        
        $addressLabel = Label::create("地址:");
        Box::append($step2Box, $addressLabel, false);
        
        $addressEntry = Entry::create();
        Box::append($step2Box, $addressEntry, false);
        
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

        $prevButton = Button::create("上一步");
        Button::onClicked($prevButton, function ($btn) {
            static $currentStep = 1;
            $currentStep = $currentStep > 1 ? $currentStep - 1 : 3;
            $this->showStep($currentStep);
        });
        Box::append($navBox, $prevButton, true);

        $nextButton = Button::create("下一步");
        Button::onClicked($nextButton, function ($btn) {
            static $currentStep = 1;
            $currentStep = $currentStep < 3 ? $currentStep + 1 : 1;
            $this->showStep($currentStep);
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
                Label::setText($this->stepIndicator, "步骤 2: 详细信息");
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