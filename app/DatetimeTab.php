<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiGroup;
use Kingbes\Libui\SDK\LibuiDateTimePicker;
use Kingbes\Libui\SDK\Enums\DateTimePickerType;
use Kingbes\Libui\DateTime;


class DatetimeTab
{
    private LibuiVBox $box;
    private array $tableData = [];

    public function __construct()
    {
        // 创建主垂直容器
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);

        // 添加标题
        $this->addBasicControls($this->box);
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

        // 创建日期时间选择器
        $dateTimePicker = new LibuiDateTimePicker(DateTimePickerType::DATE_TIME);
        // 创建日期选择器
        $datePicker = new LibuiDateTimePicker(DateTimePickerType::DATE_ONLY);
        // 创建时间选择器
        $timePicker = new LibuiDateTimePicker(DateTimePickerType::TIME_ONLY);

        // 设置时间为2023年
        $dateTimePicker->setDateTime(new DateTime(
            50,
            30,
            10,
            10,
            10,
            2023
        ));

        // 追加按钮到容器
        $basicBox->append($dateTimePicker, false);
        $basicBox->append($datePicker, false);
        $basicBox->append($timePicker, false);

        //时间日期时间选择器事件
        $dateTimePicker->onChanged(function ($dateTime) use ($dateTimePicker) {
            echo "时间日期时间选择器事件";
            // 显示选中的事件
            var_dump($dateTimePicker->getDateTime());
        });

        //日期选择器事件
        $datePicker->onChanged(function ($dateTime) use ($datePicker) {
            echo "日期选择器事件";
            // 显示选中的事件
            var_dump($datePicker->getDateTime());
        });

        //时间选择器事件
        $timePicker->onChanged(function ($dateTime) use ($timePicker) {
            echo "时间选择器事件";
            // 显示选中的事件
            var_dump($timePicker->getDateTime());
        });
    }

    public function getControl()
    {
        return $this->box;
    }
}
