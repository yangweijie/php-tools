<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Group;
use Kingbes\Libui\DateTimePicker;
use Kingbes\Libui\DateTime;


class DatetimeTab
{
    private $box;
    private $tableData = [];
    
    public function __construct()
    {
       
         // 创建主垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);
        
        // 添加标题
        $titleLabel = Label::create("基础控件");
        Box::append($this->box, $titleLabel, false);
        
        $this->addBasicControls($this->box);
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
       // 创建日期时间选择器
        $dateTimePicker = DateTimePicker::createDataTime();
        // 创建日期选择器
        $datePicker = DateTimePicker::createDate();
        // 创建时间选择器
        $timePicker = DateTimePicker::createTime();

        // 设置时间为2023年
        DateTimePicker::setTime($dateTimePicker, new DateTime(
            50,
            30,
            10,
            10,
            10,
            2023
        ));

        // 追加按钮到容器
        Box::append($basicBox, $dateTimePicker, false);
        Box::append($basicBox, $datePicker, false);
        Box::append($basicBox, $timePicker, false);

        //时间日期时间选择器事件
        DateTimePicker::onChanged($dateTimePicker, function ($dateTimePicker) {
            echo "时间日期时间选择器事件";
            // 显示选中的事件
            var_dump(DateTimePicker::time($dateTimePicker));
        });
        //日期选择器事件
        DateTimePicker::onChanged($datePicker, function ($datePicker) {
            echo "日期选择器事件";
            // 显示选中的事件
            var_dump(DateTimePicker::time($datePicker));
        });
        //时间选择器事件
        DateTimePicker::onChanged($timePicker, function ($timePicker) {
            echo "时间选择器事件";
            // 显示选中的事件
            var_dump(DateTimePicker::time($timePicker));
        });
        
       
    }

    public function getControl()
    {
        return $this->box;
    }
}