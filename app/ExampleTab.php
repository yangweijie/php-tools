<?php

namespace App;

use Exception;
use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiCheckbox;
use Kingbes\Libui\SDK\LibuiSpinbox;
use Kingbes\Libui\SDK\LibuiSlider;
use Kingbes\Libui\SDK\LibuiProgressBar;
use Kingbes\Libui\SDK\LibuiCombobox;
use Kingbes\Libui\SDK\LibuiEditableCombobox;
use Kingbes\Libui\SDK\LibuiRadio;
use Kingbes\Libui\SDK\LibuiMultilineEntry;
use Kingbes\Libui\SDK\LibuiGroup;
use Kingbes\Libui\SDK\LibuiTable;

class ExampleTab
{
    private LibuiVBox $box;
    private array $tableData = [];

    public function __construct()
    {
        // 创建主垂直容器
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);

        // 添加标题
        $titleLabel = new LibuiLabel("进程表格示例");
        $this->box->append($titleLabel, false);

        // 直接添加表格控件，不再分栏，让表格占用全部宽度
        $this->addSimpleTableControls($this->box);
    }

    private function addBasicControls(LibuiVBox $container)
    {
        // 基础控件组
        $basicGroup = new LibuiGroup("基础控件");
        $basicGroup->setPadded(true);
        $container->append($basicGroup, false);

        $basicBox = new LibuiVBox();
        $basicBox->setPadded(true);
        $basicGroup->append($basicBox, false);

        // 标签
        $label = new LibuiLabel("这是一个标签");
        $basicBox->append($label, false);

        // 普通输入框
        $entryLabel = new LibuiLabel("普通输入框:");
        $basicBox->append($entryLabel, false);
        $entry = new LibuiEntry();
        $entry->setText("普通输入框");
        $basicBox->append($entry, false);

        // 密码输入框
        $pwdLabel = new LibuiLabel("密码输入框:");
        $basicBox->append($pwdLabel, false);
        $pwdEntry = new LibuiEntry('password');
        $pwdEntry->setText("password");
        $basicBox->append($pwdEntry, false);

        // 搜索输入框
        $searchLabel = new LibuiLabel("搜索输入框:");
        $basicBox->append($searchLabel, false);
        $searchEntry = new LibuiEntry('search');
        $searchEntry->setText("搜索内容");
        $basicBox->append($searchEntry, false);

        // 按钮
        $button = new LibuiButton("点击我");
        $button->onClick(function () {
            // 按钮点击事件
        });
        $basicBox->append($button, false);

        // 复选框
        $checkbox1 = new LibuiCheckbox("选项1");
        $checkbox1->on('checkbox.toggled', function ($checked) {
            // 复选框切换事件
        });
        $basicBox->append($checkbox1, false);

        $checkbox2 = new LibuiCheckbox("选项2");
        $checkbox2->setChecked(true);
        $basicBox->append($checkbox2, false);

        // 微调框
        $spinboxLabel = new LibuiLabel("微调框 (0-100):");
        $basicBox->append($spinboxLabel, false);
        $spinbox = new LibuiSpinbox(0, 100);
        $spinbox->setValue(50);
        $spinbox->on('spinbox.changed', function ($value) {
            // 微调框值改变事件
        });
        $basicBox->append($spinbox, false);

        // 滑块
        $sliderLabel = new LibuiLabel("滑块 (0-100):");
        $basicBox->append($sliderLabel, false);
        $slider = new LibuiSlider(0, 100);
        $slider->setValue(50);
        $slider->on('slider.changed', function ($value) {
            // 滑块值改变事件
        });
        $basicBox->append($slider, false);

        // 进度条
        $progressLabel = new LibuiLabel("进度条:");
        $basicBox->append($progressLabel, false);
        $progress = new LibuiProgressBar();
        $progress->setValue(50);
        $basicBox->append($progress, false);
    }

    private function addAdvancedControls(LibuiVBox $container)
    {
        // 高级控件组
        $advancedGroup = new LibuiGroup("高级控件");
        $advancedGroup->setPadded(true);
        $container->append($advancedGroup, false);

        $advancedBox = new LibuiVBox();
        $advancedBox->setPadded(true);
        $advancedGroup->append($advancedBox, false);

        // 下拉列表框
        $comboLabel = new LibuiLabel("下拉列表框:");
        $advancedBox->append($comboLabel, false);
        $combobox = new LibuiCombobox();
        $combobox->append("选项1");
        $combobox->append("选项2");
        $combobox->append("选项3");
        $combobox->setSelected(1);
        $combobox->on('combobox.selected', function ($selected) {
            // 下拉列表框选择事件
        });
        $advancedBox->append($combobox, false);

        // 可编辑下拉列表框
        $editComboLabel = new LibuiLabel("可编辑下拉列表框:");
        $advancedBox->append($editComboLabel, false);
        $editCombobox = new LibuiEditableCombobox();
        $editCombobox->append("苹果");
        $editCombobox->append("香蕉");
        $editCombobox->append("橙子");
        $editCombobox->setText("苹果");
        $editCombobox->on('editablecombobox.changed', function ($text) {
            // 可编辑下拉列表框文本改变事件
        });
        $advancedBox->append($editCombobox, false);

        // 单选框
        $radioLabel = new LibuiLabel("单选框:");
        $advancedBox->append($radioLabel, false);
        $radio = new LibuiRadio();
        $radio->append("单选1");
        $radio->append("单选2");
        $radio->append("单选3");
        $radio->setSelected(0);
        $radio->on('radio.selected', function ($selected) {
            // 单选框选择事件
        });
        $advancedBox->append($radio, false);

        // 多行文本框
        $multiLabel = new LibuiLabel("多行文本框:");
        $advancedBox->append($multiLabel, false);
        $multiline = new LibuiMultilineEntry();
        $multiline->setText("这是多行文本框\n可以输入多行内容");
        $multiline->on('multilineentry.changed', function ($text) {
            // 多行文本框文本改变事件
        });
        $advancedBox->append($multiline, true);
    }

    private function addSimpleTableControls(LibuiVBox $container)
    {
        // 表格控件组
        $tableGroup = new LibuiGroup("进程表格 (libui Table)");
        $tableGroup->setPadded(true);
        $container->append($tableGroup, true);

        $tableBox = new LibuiVBox();
        $tableBox->setPadded(true);
        $tableGroup->append($tableBox, false);

        try {
            // 使用SDK中的LibuiTable组件
            $table = new LibuiTable();

            // 添加列 - 按照数据的列顺序添加
            $table->addTextColumn("姓名", 0)
                  ->addTextColumn("年龄", 1)
                  ->addButtonColumn("操作", 2, 2)
                  ->addCheckboxColumn("选择", 3, 3);

            // 设置数据
            $data = [
                ["小李", "18", "编辑1", 1],
                ["小成", "20", "编辑2", 0],
                ["多多", "32", "编辑3", 1]
            ];
            $table->setData($data);

            // 将表格添加到容器
            $tableBox->append($table->getHandle(), true);

        } catch (Exception $e) {
            // 如果表格创建失败，显示错误信息
            $errorLabel = new LibuiLabel("表格创建失败: " . $e->getMessage());
            $tableBox->append($errorLabel, false);
        }
    }

    public function getControl()
    {
        return $this->box;
    }
}
