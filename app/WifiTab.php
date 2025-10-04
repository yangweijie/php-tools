<?php
namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiHBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiCombobox;
use Sanchescom\WiFi\WiFi;

/**
 * WiFi工具类
 */
class WifiTab
{
    private LibuiVBox $box;
    
    public function __construct()
    {
        // 创建主垂直容器
        $this->box = new LibuiVBox();
        
        // 下拉列表框
        $comboLabel = new LibuiLabel("下拉列表框:");
        $this->box->append($comboLabel, false);
        
        $group = new LibuiHBox();
        
        $combobox = new LibuiCombobox();
        $combobox->append("选项1");
        $combobox->append("选项2");
        $combobox->append("选项3");
        $combobox->setSelected(1);
        $combobox->on('combobox.selected', function ($cb) {
            // 下拉列表框选择事件
        });
        $group->append($combobox, true);
        
        $refreshBtn = new LibuiButton('刷新');
        $refreshBtn->onClick(function(){
            $this->refreshWifiList();
        });
        $group->append($refreshBtn, false);
        
        $this->box->append($group, false);
    }

    public function getControl()
    {
        return $this->box;
    }
}