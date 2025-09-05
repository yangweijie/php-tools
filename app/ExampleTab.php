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
use Kingbes\Libui\Separator;
use Kingbes\Libui\Table;
use Kingbes\Libui\TableValueType;

class ExampleTab
{
    private $box;
    private $tableData = [];
    
    public function __construct()
    {
        // 创建主垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);
        
        // 添加标题
        $titleLabel = Label::create("进程表格示例");
        Box::append($this->box, $titleLabel, false);
        
        // 直接添加表格控件，不再分栏，让表格占用全部宽度
        $this->addSimpleTableControls($this->box);
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
        
    }
    
    private function addTableControls($container)
    {
        // 表格控件组
        $tableGroup = Group::create("表格控件");
        Group::setMargined($tableGroup, true);
        Box::append($container, $tableGroup, true);
        
        $tableBox = Box::newVerticalBox();
        Box::setPadded($tableBox, true);
        Group::setChild($tableGroup, $tableBox);
        
        // 创建表格模型处理程序
        $handlerStruct = Table::modelHandler();
        $handler = \FFI::addr($handlerStruct);
        
        // 设置回调函数 (使用静态数据，避免使用use clause)
        $handler->NumColumns = function ($h, $m) {
            return 4; // ID, 选择框, 姓名, 年龄
        };
        
        $handler->ColumnType = function ($h, $m, $column) {
            if ($column == 0) { // ID
                return TableValueType::String->value; // 使用字符串类型避免混合类型问题
            } elseif ($column == 1) { // 选择框
                return TableValueType::String->value; // 使用字符串类型避免混合类型问题
            } else { // 姓名, 年龄
                return TableValueType::String->value;
            }
        };
        
        $handler->NumRows = function ($h, $m) {
            return 3; // 固定3行
        };
        
        $handler->CellValue = function ($h, $m, $row, $column) {
            // 静态测试数据
            switch ($column) {
                case 0: // ID
                    return Table::createValueStr((string)($row + 1));
                case 1: // 选择框
                    return Table::createValueStr("0");
                case 2: // 姓名
                    $names = ["张三", "李四", "王五"];
                    return Table::createValueStr($names[$row]);
                case 3: // 年龄
                    $ages = [25, 30, 28];
                    return Table::createValueStr((string)$ages[$row]);
                default:
                    return Table::createValueStr("");
            }
        };
        
        $handler->SetCellValue = function ($h, $m, $row, $column, $value) {
            // 简单的设置处理
            return $m->SetCellValue($row, $column, Table::createValueStr($value));
        };
        
        // 创建表格模型 - 使用正确的 Table::createModel 方法
        $model = Table::createModel(\FFI::addr($handler));
        
        // 创建表格参数
        $params = \Kingbes\Libui\Base::ffi()->new("uiTableParams");
        $params->Model = $model;
        $params->RowBackgroundColorModelColumn = -1;
        
        // 创建表格
        $table = Table::create($params);
        
        // 添加列 (全部使用文本列避免混合类型问题)
        $textParams = \Kingbes\Libui\Base::ffi()->new("uiTableTextColumnOptionalParams");
        Table::appendTextColumn($table, "ID", 0, -1, \FFI::addr($textParams));
        Table::appendTextColumn($table, "选择", 1, -1, \FFI::addr($textParams));
        Table::appendTextColumn($table, "姓名", 2, -1, \FFI::addr($textParams));
        Table::appendTextColumn($table, "年龄", 3, -1, \FFI::addr($textParams));
        
        // 添加操作按钮
        $actionBox = Box::newHorizontalBox();
        Box::setPadded($actionBox, true);
        
        $selectAllBtn = Button::create("全选");
        Button::onClicked($selectAllBtn, function() {
            echo "全选按钮被点击\n";
        });
        
        $clearBtn = Button::create("清空选择");
        Button::onClicked($clearBtn, function() {
            echo "清空选择按钮被点击\n";
        });
        
        Box::append($actionBox, $selectAllBtn, false);
        Box::append($actionBox, $clearBtn, false);
        Box::append($tableBox, $actionBox, false);
        
        // 按钮：显示信息
        $btn = Button::create("显示信息");
        Button::onClicked($btn, function () {
            echo "表格按钮被点击\n";
        });
        Box::append($tableBox, $btn, false);
    }
    
    private function addSimpleTableControls($container)
    {
        // 表格控件组
        $tableGroup = Group::create("进程表格 (libui Table)");
        Group::setMargined($tableGroup, true);
        Box::append($container, $tableGroup, true);
        
        $tableBox = Box::newVerticalBox();
        Box::setPadded($tableBox, true);
        Group::setChild($tableGroup, $tableBox);
        
        try {
            // 创建表格模型处理器
            $handler = \Kingbes\Libui\Base::ffi()->new("uiTableModelHandler");
            
            // 定义回调函数
            $numColumnsFunc = function($h, $m) {
                return 4; // ID, PID, User, Command
            };
            
            $columnTypeFunc = function($h, $m, $column) {
                // 所有列都使用字符串类型
                return TableValueType::String->value;
            };
            
            $numRowsFunc = function($h, $m) {
                return 3; // 固定3行数据
            };
            
            $cellValueFunc = function($h, $m, $row, $column) {
                // 模拟数据
                $mockData = [
                    ['1', '1234', 'root', '/usr/bin/systemd'],
                    ['2', '5678', 'www-data', 'nginx: worker process'],
                    ['3', '9012', 'mysql', '/usr/sbin/mysqld']
                ];
                
                // 确保行索引不超出范围
                $rowIndex = min($row, count($mockData) - 1);
                
                // 返回对应单元格的数据
                if (isset($mockData[$rowIndex][$column])) {
                    return Table::createValueStr($mockData[$rowIndex][$column]);
                }
                
                return Table::createValueStr('');
            };
            
            $setCellValueFunc = function($h, $m, $row, $column, $value) {
                // 简单的设置处理
                return 0;
            };

            // 设置回调函数
            $handler->NumColumns = $numColumnsFunc;
            $handler->ColumnType = $columnTypeFunc;
            $handler->NumRows = $numRowsFunc;
            $handler->CellValue = $cellValueFunc;
            $handler->SetCellValue = $setCellValueFunc;

            // 创建文本列参数
            $textParams = \Kingbes\Libui\Base::ffi()->new("uiTableTextColumnOptionalParams");
            $textParamsPtr = \FFI::addr($textParams);

            // 创建表格模型
            $model = Table::createModel(\FFI::addr($handler));

            // 创建表格参数
            $params = \Kingbes\Libui\Base::ffi()->new("uiTableParams");
            $params->Model = $model;
            $params->RowBackgroundColorModelColumn = -1;

            // 创建表格
            $table = Table::create($params);

            // 添加列
            Table::appendTextColumn($table, "ID", 0, -1, $textParamsPtr);
            Table::appendTextColumn($table, "PID", 1, -1, $textParamsPtr);
            Table::appendTextColumn($table, "User", 2, -1, $textParamsPtr);
            Table::appendTextColumn($table, "Command", 3, -1, $textParamsPtr);

            // 将表格添加到容器
            Box::append($tableBox, $table, true);
            
            // 添加说明标签
            $infoLabel = Label::create("这是简单的表格示例 - 显示ID、PID、User、Command四列");
            Box::append($tableBox, $infoLabel, false);
            
        } catch (\Exception $e) {
            // 如果表格创建失败，显示错误信息
            $errorLabel = Label::create("表格创建失败: " . $e->getMessage());
            Box::append($tableBox, $errorLabel, false);
        }
    }
    


    public function getControl()
    {
        return $this->box;
    }
}