<?php

namespace App;

use Kingbes\Libui\SDK\LibuiApplication;
use Kingbes\Libui\SDK\LibuiWindow;
use Kingbes\Libui\SDK\LibuiTab;

class App
{
    private LibuiWindow $window;
    private LibuiTab $tab;
    private array $tabs = []; // 保存标签页引用
    
    public function __construct()
    {
        // 初始化应用
        LibuiApplication::getInstance()->init();
        
        // 创建主窗口
        $this->window = new LibuiWindow("工具集", 1000, 700, true);
        
        // 窗口关闭事件
        $this->window->on('window.closing', function () {
            LibuiApplication::getInstance()->quit();
        });
        
        // 创建标签页容器
        $this->tab = new LibuiTab();
        
        // 设置窗口内容
        $this->window->setChild($this->tab);
        
        // 显示窗口
        $this->window->show();
    }
    
    public function addTab(string $name, $control)
    {
        $this->tab->append($name, $control);
    }
    
    /**
     * 添加带有回调函数的标签页
     */
    public function addTabWithCallback(string $name, $control, callable $callback = null)
    {
        $this->tab->appendWithCallback($name, $control, $callback);
    }
    
    public function run()
    {
        // 主循环
        LibuiApplication::getInstance()->run();
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