<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiCombobox;
use Kingbes\Libui\SDK\LibuiGroup;
use Kingbes\Libui\Window;

class DownloadAcceleratorTab
{
    private LibuiVBox $box;
    private LibuiEntry $urlEntry;
    private LibuiCombobox $typeCombobox;
    
    // 支持的平台类型
    private array $platforms = [
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
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);
        
        // 添加标题
        $titleLabel = new LibuiLabel("下载加速器");
        $this->box->append($titleLabel, false);
        
        // 添加说明标签
        $descLabel = new LibuiLabel("输入原始URL，选择平台类型，点击转换获取加速链接");
        $this->box->append($descLabel, false);
        
        // 创建输入区域
        $this->addInputControls($this->box);
    }
    
    private function addInputControls(LibuiVBox $container)
    {
        // 输入控件组
        $inputGroup = new LibuiGroup("URL转换");
        $inputGroup->setPadded(true);
        $container->append($inputGroup, false);
        
        $inputBox = new LibuiVBox();
        $inputBox->setPadded(true);
        $inputGroup->append($inputBox, false);
        
        // URL输入框标签
        $urlLabel = new LibuiLabel("原始URL:");
        $inputBox->append($urlLabel, false);
        
        // URL输入框
        $this->urlEntry = new LibuiEntry();
        $this->urlEntry->setText("https://github.com/user/repo/archive/main.zip");
        $inputBox->append($this->urlEntry, false);
        
        // 类型选择器标签
        $typeLabel = new LibuiLabel("平台类型:");
        $inputBox->append($typeLabel, false);
        
        // 类型下拉框
        $this->typeCombobox = new LibuiCombobox();
        $platformNames = array_keys($this->platforms);
        foreach ($platformNames as $name) {
            $this->typeCombobox->append($name);
        }
        $this->typeCombobox->setSelected(0); // 默认选择GitHub
        $inputBox->append($this->typeCombobox, false);
        
        // 转换按钮
        $convertButton = new LibuiButton("转换为加速链接");
        $convertButton->onClick(function () {
            $this->convertUrl();
        });
        $inputBox->append($convertButton, false);
    }
    
    private function convertUrl()
    {
        try {
            // 获取输入的URL
            $originalUrl = $this->urlEntry->getText();
            
            // 获取选择的平台类型
            $selectedIndex = $this->typeCombobox->getSelected();
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
                Window::msgBox(
                    $application->getWindow()->getHandle(), 
                    "加速链接", 
                    "加速后的URL:\n\n" . $acceleratedUrl . "\n\n已复制到剪贴板"
                );
            } else {
                $errorMsg = \App\Clipboard::getLastError();
                if (!empty($errorMsg)) {
                    Window::msgBox(
                        $application->getWindow()->getHandle(), 
                        "加速链接", 
                        "加速后的URL:\n\n" . $acceleratedUrl . "\n\n复制到剪贴板失败: " . $errorMsg
                    );
                } else {
                    Window::msgBox(
                        $application->getWindow()->getHandle(), 
                        "加速链接", 
                        "加速后的URL:\n\n" . $acceleratedUrl . "\n\n复制到剪贴板失败，请手动复制"
                    );
                }
            }
        } catch (\Exception $e) {
            // 获取主窗口引用
            global $application;
            
            // 显示错误信息
            Window::msgBoxError(
                $application->getWindow()->getHandle(),
                "错误",
                "转换URL时发生错误: " . $e->getMessage()
            );
        }
    }
    
    private function generateAcceleratedUrl(string $originalUrl, string $platformPrefix)
    {
        $host = parse_url($originalUrl, PHP_URL_HOST);
        // 移除URL协议和host部分，保留URL的剩余部分
        $urlWithoutProtocol = str_ireplace([
            parse_url($originalUrl, PHP_URL_SCHEME).'://', 
            $host.'/',
        ], ['', ''], $originalUrl);
        
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