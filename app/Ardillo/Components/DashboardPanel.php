<?php

namespace App\Ardillo\Components;

class DashboardPanel implements ComponentInterface
{
    private mixed $widget = null;
    private bool $initialized = false;

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // Create main vertical box
        $vbox = new \Ardillo\VerticalBox();
        
        // Welcome section
        $welcomeLabel = new \Ardillo\Label("欢迎使用端口和进程管理工具");
        $vbox->append($welcomeLabel, false);
        
        // Add some spacing
        $spacer1 = new \Ardillo\Label("");
        $vbox->append($spacer1, false);
        
        // Feature description
        $featuresLabel = new \Ardillo\Label("功能说明:");
        $vbox->append($featuresLabel, false);
        
        $feature1 = new \Ardillo\Label("• 端口查杀: 查询和管理系统端口占用情况");
        $vbox->append($feature1, false);
        
        $feature2 = new \Ardillo\Label("• 进程查杀: 查询和管理系统进程");
        $vbox->append($feature2, false);
        
        // Add spacing
        $spacer2 = new \Ardillo\Label("");
        $vbox->append($spacer2, false);
        
        // Usage instructions
        $usageLabel = new \Ardillo\Label("使用说明:");
        $vbox->append($usageLabel, false);
        
        $usage1 = new \Ardillo\Label("• 点击上方标签页切换不同功能");
        $vbox->append($usage1, false);
        
        $usage2 = new \Ardillo\Label("• 使用 Cmd+Q (macOS) 或 Ctrl+C 退出应用程序");
        $vbox->append($usage2, false);
        
        $usage3 = new \Ardillo\Label("• 或者直接关闭窗口退出");
        $vbox->append($usage3, false);
        
        // Add spacing
        $spacer3 = new \Ardillo\Label("");
        $vbox->append($spacer3, false);
        
        // Version info
        $versionLabel = new \Ardillo\Label("版本: 1.0.0 (基于 Ardillo GUI 框架)");
        $vbox->append($versionLabel, false);
        
        $this->widget = $vbox;
        $this->initialized = true;
    }

    public function getWidget(): mixed
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->widget;
    }

    public function getControl(): mixed
    {
        return $this->getWidget();
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function cleanup(): void
    {
        $this->widget = null;
        $this->initialized = false;
    }
}