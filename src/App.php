<?php

namespace App;

use Kingbes\Libui\App as LibuiApp;
use Kingbes\Libui\Window;
use Kingbes\Libui\Control;
use Kingbes\Libui\Tab;
use Kingbes\Libui\Box;

class App
{
    private $window;
    private $tab;
    
    public function __construct()
    {
        // 初始化应用
        LibuiApp::init();
        
        // 创建主窗口
        $this->window = Window::create("工具集", 1000, 700, 1);
        Window::setMargined($this->window, true);
        
        // 窗口关闭事件
        Window::onClosing($this->window, function ($window) {
            LibuiApp::quit();
            return 1;
        });
        
        // 创建标签页容器
        $this->tab = Tab::create();
        
        // 设置窗口内容
        Window::setChild($this->window, $this->tab);
        
        // 显示窗口
        Control::show($this->window);
    }
    
    public function addTab($name, $control)
    {
        $index = Tab::numPages($this->tab);
        Tab::append($this->tab, $name, $control);
        Tab::setMargined($this->tab, $index, true);
    }
    
    public function run()
    {
        // 主循环
        LibuiApp::main();
    }
    
    public function getWindow()
    {
        return $this->window;
    }
}