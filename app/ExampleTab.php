<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Button;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Checkbox;
use Kingbes\Libui\Spinbox;
use Kingbes\Libui\Slider;
use Kingbes\Libui\ProgressBar;
use Kingbes\Libui\Combobox;
use Kingbes\Libui\EditableCombobox;
use Kingbes\Libui\Radio;
use Kingbes\Libui\MultilineEntry;
use Kingbes\Libui\Group;
use Kingbes\Libui\Control;
use Kingbes\Libui\ColorButton;
use Kingbes\Libui\FontButton;

class ExampleTab
{
    private $box;
    
    public function __construct()
    {
        // 创建主垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);
        
        // 添加标题
        $titleLabel = Label::create("UI组件示例");
        Box::append($this->box, $titleLabel, false);
        
        // 创建水平容器用于分栏
        $mainBox = Box::newHorizontalBox();
        Box::setPadded($mainBox, true);
        Box::append($this->box, $mainBox, true);
        
        // 左侧栏
        $leftBox = Box::newVerticalBox();
        Box::setPadded($leftBox, true);
        Box::append($mainBox, $leftBox, true);
        
        // 右侧栏
        $rightBox = Box::newVerticalBox();
        Box::setPadded($rightBox, true);
        Box::append($mainBox, $rightBox, true);
        
        // 添加各种组件到左侧栏
        $this->addBasicControls($leftBox);
        
        // 添加各种组件到右侧栏
        $this->addAdvancedControls($rightBox);
    }
    
    private function addBasicControls($container)
    {
        // 基础控件组
        $basicGroup = Group::create("基础控件");
        Group::setMargined($basicGroup, true);
        Box::append($container, $basicGroup, false);
        
        $basicBox = Box::newVerticalBox();
        Box::setPadded($basicBox, true);
        Group::setChild($basicGroup, $basicBox);
        
        // 标签
        $label = Label::create("这是一个标签");
        Box::append($basicBox, $label, false);
        
        // 普通输入框
        $entryLabel = Label::create("普通输入框:");
        Box::append($basicBox, $entryLabel, false);
        $entry = Entry::create();
        Entry::setText($entry, "普通输入框");
        Box::append($basicBox, $entry, false);
        
        // 密码输入框
        $pwdLabel = Label::create("密码输入框:");
        Box::append($basicBox, $pwdLabel, false);
        $pwdEntry = Entry::createPwd();
        Entry::setText($pwdEntry, "password");
        Box::append($basicBox, $pwdEntry, false);
        
        // 搜索输入框
        $searchLabel = Label::create("搜索输入框:");
        Box::append($basicBox, $searchLabel, false);
        $searchEntry = Entry::createSearch();
        Entry::setText($searchEntry, "搜索内容");
        Box::append($basicBox, $searchEntry, false);
        
        // 按钮
        $button = Button::create("点击我");
        Button::onClicked($button, function ($btn) {
            // 按钮点击事件
        });
        Box::append($basicBox, $button, false);
        
        // 复选框
        $checkbox1 = Checkbox::create("选项1");
        Checkbox::onToggled($checkbox1, function ($cb) {
            // 复选框切换事件
        });
        Box::append($basicBox, $checkbox1, false);
        
        $checkbox2 = Checkbox::create("选项2");
        Checkbox::setChecked($checkbox2, true);
        Box::append($basicBox, $checkbox2, false);
        
        // 微调框
        $spinboxLabel = Label::create("微调框 (0-100):");
        Box::append($basicBox, $spinboxLabel, false);
        $spinbox = Spinbox::create(0, 100);
        Spinbox::setValue($spinbox, 50);
        Spinbox::onChanged($spinbox, function ($sb) {
            // 微调框值改变事件
        });
        Box::append($basicBox, $spinbox, false);
        
        // 滑块
        $sliderLabel = Label::create("滑块 (0-100):");
        Box::append($basicBox, $sliderLabel, false);
        $slider = Slider::create(0, 100);
        Slider::setValue($slider, 50);
        Slider::onChanged($slider, function ($sl) {
            // 滑块值改变事件
        });
        Box::append($basicBox, $slider, false);
        
        // 进度条
        $progressLabel = Label::create("进度条:");
        Box::append($basicBox, $progressLabel, false);
        $progress = ProgressBar::create();
        ProgressBar::setValue($progress, 50);
        Box::append($basicBox, $progress, false);
    }
    
    private function addAdvancedControls($container)
    {
        // 高级控件组
        $advancedGroup = Group::create("高级控件");
        Group::setMargined($advancedGroup, true);
        Box::append($container, $advancedGroup, false);
        
        $advancedBox = Box::newVerticalBox();
        Box::setPadded($advancedBox, true);
        Group::setChild($advancedGroup, $advancedBox);
        
        // 下拉列表框
        $comboLabel = Label::create("下拉列表框:");
        Box::append($advancedBox, $comboLabel, false);
        $combobox = Combobox::create();
        Combobox::append($combobox, "选项1");
        Combobox::append($combobox, "选项2");
        Combobox::append($combobox, "选项3");
        Combobox::setSelected($combobox, 1);
        Combobox::onSelected($combobox, function ($cb) {
            // 下拉列表框选择事件
        });
        Box::append($advancedBox, $combobox, false);
        
        // 可编辑下拉列表框
        $editComboLabel = Label::create("可编辑下拉列表框:");
        Box::append($advancedBox, $editComboLabel, false);
        $editCombobox = EditableCombobox::create();
        EditableCombobox::append($editCombobox, "苹果");
        EditableCombobox::append($editCombobox, "香蕉");
        EditableCombobox::append($editCombobox, "橙子");
        EditableCombobox::setText($editCombobox, "苹果");
        EditableCombobox::onChanged($editCombobox, function ($ecb) {
            // 可编辑下拉列表框文本改变事件
        });
        Box::append($advancedBox, $editCombobox, false);
        
        // 单选框
        $radioLabel = Label::create("单选框:");
        Box::append($advancedBox, $radioLabel, false);
        $radio = Radio::create();
        Radio::append($radio, "单选1");
        Radio::append($radio, "单选2");
        Radio::append($radio, "单选3");
        Radio::setSelected($radio, 0);
        Radio::onSelected($radio, function ($r) {
            // 单选框选择事件
        });
        Box::append($advancedBox, $radio, false);
        
        // 多行文本框
        $multiLabel = Label::create("多行文本框:");
        Box::append($advancedBox, $multiLabel, false);
        $multiline = MultilineEntry::create();
        MultilineEntry::setText($multiline, "这是多行文本框\n可以输入多行内容");
        MultilineEntry::onChanged($multiline, function ($me) {
            // 多行文本框文本改变事件
        });
        Box::append($advancedBox, $multiline, true);
        
        // 颜色按钮
        $colorLabel = Label::create("颜色按钮:");
        Box::append($advancedBox, $colorLabel, false);
        $colorButton = ColorButton::create();
        ColorButton::setColor($colorButton, 1.0, 0.0, 0.0, 1.0); // 红色
        ColorButton::onChanged($colorButton, function ($cb) {
            // 颜色按钮颜色改变事件
        });
        Box::append($advancedBox, $colorButton, false);
        
        // 字体按钮
        $fontLabel = Label::create("字体按钮:");
        Box::append($advancedBox, $fontLabel, false);
        $fontButton = FontButton::create();
        FontButton::onChanged($fontButton, function ($fb) {
            // 字体按钮字体改变事件
        });
        Box::append($advancedBox, $fontButton, false);
    }
    
    public function getControl()
    {
        return $this->box;
    }
}