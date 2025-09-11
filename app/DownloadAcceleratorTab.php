<?php

namespace App;

use Kingbes\Libui\Box;
use Kingbes\Libui\Label;
use Kingbes\Libui\Button;
use Kingbes\Libui\Entry;
use Kingbes\Libui\Combobox;
use Kingbes\Libui\Group;
use Kingbes\Libui\Control;
use Kingbes\Libui\MsgBox;

class DownloadAcceleratorTab
{
    private $box;
    
    // 支持的平台类型
    private $platforms = [
        'GitHub' => 'gh',
        'GitLab' => 'gl',
        'Gitea' => 'gitea',
        'Codeberg' => 'codeberg',
        'SourceForge' => 'sf',
        'AOSP' => 'aosp',
        'Hugging Face' => 'hf',
        'Civitai' => 'civitai',
        'npm' => 'npm',
        'PyPI' => 'pypi',
        'conda' => 'conda',
        'Maven' => 'maven',
        'Apache' => 'apache',
        'Gradle' => 'gradle',
        'Homebrew' => 'homebrew',
        'RubyGems' => 'rubygems',
        'CRAN' => 'cran',
        'CPAN' => 'cpan',
        'CTAN' => 'ctan',
        'Go' => 'golang',
        'NuGet' => 'nuget',
        'Rust' => 'crates',
        'Packagist' => 'packagist',
        'Debian' => 'debian',
        'Ubuntu' => 'ubuntu',
        'Fedora' => 'fedora',
        'Rocky Linux' => 'rocky',
        'openSUSE' => 'opensuse',
        'Arch Linux' => 'arch',
        'arXiv' => 'arxiv',
        'F-Droid' => 'fdroid',
        'AI 推理提供商' => 'ip',
        '容器注册表' => 'cr'
    ];
    
    public function __construct()
    {
        // 创建主垂直容器
        $this->box = Box::newVerticalBox();
        Box::setPadded($this->box, true);
        
        // 添加标题
        $titleLabel = Label::create("下载加速器");
        Box::append($this->box, $titleLabel, false);
        
        // 添加说明标签
        $descLabel = Label::create("输入原始URL，选择平台类型，点击转换获取加速链接");
        Box::append($this->box, $descLabel, false);
        
        // 创建输入区域
        $this->addInputControls($this->box);
    }
    
    private function addInputControls($container)
    {
        // 输入控件组
        $inputGroup = Group::create("URL转换");
        Group::setMargined($inputGroup, true);
        Box::append($container, $inputGroup, false);
        
        $inputBox = Box::newVerticalBox();
        Box::setPadded($inputBox, true);
        Group::setChild($inputGroup, $inputBox);
        
        // URL输入框标签
        $urlLabel = Label::create("原始URL:");
        Box::append($inputBox, $urlLabel, false);
        
        // URL输入框
        $urlEntry = Entry::create();
        Entry::setText($urlEntry, "https://github.com/user/repo/archive/main.zip");
        Box::append($inputBox, $urlEntry, false);
        
        // 类型选择器标签
        $typeLabel = Label::create("平台类型:");
        Box::append($inputBox, $typeLabel, false);
        
        // 类型下拉框
        $typeCombobox = Combobox::create();
        $platformNames = array_keys($this->platforms);
        foreach ($platformNames as $name) {
            Combobox::append($typeCombobox, $name);
        }
        Combobox::setSelected($typeCombobox, 0); // 默认选择GitHub
        Box::append($inputBox, $typeCombobox, false);
        
        // 转换按钮
        $convertButton = Button::create("转换为加速链接");
        Button::onClicked($convertButton, function ($btn) use ($urlEntry, $typeCombobox) {
            $this->convertUrl($urlEntry, $typeCombobox);
        });
        Box::append($inputBox, $convertButton, false);
    }
    
    private function convertUrl($urlEntry, $typeCombobox)
    {
        try {
            // 获取输入的URL
            $originalUrl = Entry::text($urlEntry);
            
            // 获取选择的平台类型
            $selectedIndex = Combobox::selected($typeCombobox);
            $platformNames = array_keys($this->platforms);
            $selectedPlatformName = $platformNames[$selectedIndex];
            $platformPrefix = $this->platforms[$selectedPlatformName];
            
            // 转换URL
            $acceleratedUrl = $this->generateAcceleratedUrl($originalUrl, $platformPrefix);
            
            // 复制到剪贴板
            $clipboardSuccess = \App\Clipboard::copy($acceleratedUrl);
            
            // 获取主窗口引用
            global $application;
            
            // 显示结果弹窗
            if ($clipboardSuccess) {
                \Kingbes\Libui\Window::msgBox(
                    $application->getWindow(), 
                    "加速链接", 
                    "加速后的URL:\n\n" . $acceleratedUrl . "\n\n已复制到剪贴板"
                );
            } else {
                $errorMsg = \App\Clipboard::getLastError();
                if (!empty($errorMsg)) {
                    \Kingbes\Libui\Window::msgBox(
                        $application->getWindow(), 
                        "加速链接", 
                        "加速后的URL:\n\n" . $acceleratedUrl . "\n\n复制到剪贴板失败: " . $errorMsg
                    );
                } else {
                    \Kingbes\Libui\Window::msgBox(
                        $application->getWindow(), 
                        "加速链接", 
                        "加速后的URL:\n\n" . $acceleratedUrl . "\n\n复制到剪贴板失败，请手动复制"
                    );
                }
            }
        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;
            
            // 显示错误信息
            \Kingbes\Libui\Window::msgBoxError(
                $application->getWindow(),
                "错误",
                "转换URL时发生错误: " . $e->getMessage()
            );
        }
    }
    
    private function generateAcceleratedUrl($originalUrl, $platformPrefix)
    {
        // 移除URL协议部分
        $urlWithoutProtocol = preg_replace('#^https?://#', '', $originalUrl);
        
        // 构造加速URL
        return "https://xget.xi-xu.me/" . $platformPrefix . "/" . $urlWithoutProtocol;
    }
    
    /**
     * 检查系统是否为Windows
     *
     * @return bool 是否为Windows系统
     */
    private function isWindows()
    {
        return App::isWindows();
    }
    
    /**
     * 检查系统是否为macOS
     *
     * @return bool 是否为macOS系统
     */
    private function isMac()
    {
        return App::isMac();
    }
    
    /**
     * 检查系统是否为Linux
     *
     * @return bool 是否为Linux系统
     */
    private function isLinux()
    {
        return App::isLinux();
    }
    
    public function getControl()
    {
        return $this->box;
    }
}