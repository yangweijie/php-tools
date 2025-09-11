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
        
        // 注册应用程序退出事件
        LibuiApp::onShouldQuit(function () {
            LibuiApp::quit();
            return true;
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
    
    /**
     * 获取操作系统类型
     *
     * @return string 操作系统类型 (WIN, DAR, LIN)
     */
    public static function getOperatingSystem()
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        
        if ($os === 'WIN') {
            return 'WIN';  // Windows
        } elseif ($os === 'DAR') {
            return 'DAR';  // macOS (Darwin)
        } elseif ($os === 'LIN') {
            return 'LIN';  // Linux
        } else {
            // 其他Unix-like系统也归类为Linux
            return 'LIN';
        }
    }
    
    /**
     * 检查是否为Windows系统
     *
     * @return bool 是否为Windows系统
     */
    public static function isWindows()
    {
        return self::getOperatingSystem() === 'WIN';
    }
    
    /**
     * 检查是否为macOS系统
     *
     * @return bool 是否为macOS系统
     */
    public static function isMac()
    {
        return self::getOperatingSystem() === 'DAR';
    }
    
    /**
     * 检查是否为Linux系统
     *
     * @return bool 是否为Linux系统
     */
    public static function isLinux()
    {
        return self::getOperatingSystem() === 'LIN';
    }
}