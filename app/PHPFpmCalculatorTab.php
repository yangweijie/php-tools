<?php

namespace App;

use Kingbes\Libui\SDK\LibuiVBox;
use Kingbes\Libui\SDK\LibuiLabel;
use Kingbes\Libui\SDK\LibuiEntry;
use Kingbes\Libui\SDK\LibuiButton;
use Kingbes\Libui\SDK\LibuiGroup;
use Kingbes\Libui\SDK\LibuiMultilineEntry;

class PHPFpmCalculatorTab
{
    private LibuiVBox $box;
    private LibuiEntry $totalRamEntry;
    private LibuiEntry $reservedRamEntry;
    private LibuiEntry $ramBufferEntry;
    private LibuiEntry $processSizeEntry;
    private LibuiLabel $availableRamLabel; // Combined label for available RAM
    private LibuiLabel $maxChildrenLabel;
    private LibuiLabel $startServersLabel;
    private LibuiLabel $minSpareServersLabel;
    private LibuiLabel $maxSpareServersLabel;
    private LibuiMultilineEntry $configOutput;

    public function __construct()
    {
        // Create main vertical container
        $this->box = new LibuiVBox();
        $this->box->setPadded(true);

        // Add input controls group
        $this->addInputControls($this->box);

        // Add result controls group
        $this->addResultControls($this->box);

        // Add configuration output
        $this->addConfigOutput($this->box);

        // Initialize with default values first
        $this->initializeDefaultValues();

        // Then perform initial calculation after all UI elements are set up
        $this->calculate();

        // Now add the event handlers after initial calculation
        $this->addEventHandlers();
    }

    private function addInputControls(LibuiVBox $container)
    {
        // Input controls group
        $inputGroup = new LibuiGroup("输入参数");
        $inputGroup->setPadded(false);
        $container->append($inputGroup, false);

        // Use simple VBox for layout
        $inputBox = new LibuiVBox();
        $inputBox->setPadded(true);
        $inputGroup->append($inputBox, false);

        // Total RAM
        $totalRamLabel = new LibuiLabel("总内存 (GB):");
        $inputBox->append($totalRamLabel, false);
        $this->totalRamEntry = new LibuiEntry();
        $this->totalRamEntry->setText("8");
        $inputBox->append($this->totalRamEntry, false);

        // Reserved RAM
        $reservedRamLabel = new LibuiLabel("预留内存 (GB):");
        $inputBox->append($reservedRamLabel, false);
        $this->reservedRamEntry = new LibuiEntry();
        $this->reservedRamEntry->setText("2");
        $inputBox->append($this->reservedRamEntry, false);

        // RAM Buffer
        $ramBufferLabel = new LibuiLabel("内存缓冲 (%):");
        $inputBox->append($ramBufferLabel, false);
        $this->ramBufferEntry = new LibuiEntry();
        $this->ramBufferEntry->setText("10");
        $inputBox->append($this->ramBufferEntry, false);

        // Process size
        $processSizeLabel = new LibuiLabel("每个进程大小 (MB):");
        $inputBox->append($processSizeLabel, false);
        $this->processSizeEntry = new LibuiEntry();
        $this->processSizeEntry->setText("64");
        $inputBox->append($this->processSizeEntry, false);
    }

    private function addResultControls(LibuiVBox $container)
    {
        // Result controls group
        $resultGroup = new LibuiGroup("计算结果");
        $resultGroup->setPadded(true);
        $container->append($resultGroup, false);

        $resultBox = new LibuiVBox();
        $resultBox->setPadded(true);
        $resultGroup->append($resultBox, false);

        // Create a single line for available memory (combined GB and MB)
        $availableRamHBox = new LibuiVBox();
        $availableRamHBox->setPadded(true);
        $this->availableRamLabel = new LibuiLabel("可用内存: ");
        $availableRamHBox->append($this->availableRamLabel, false);
        $resultBox->append($availableRamHBox, false);

        // pm.max_children
        $maxChildrenHBox = new LibuiVBox();
        $maxChildrenHBox->setPadded(true);
        $maxChildrenLabel = new LibuiLabel("pm.max_children: 0");
        $maxChildrenHBox->append($maxChildrenLabel, false);
        $this->maxChildrenLabel = $maxChildrenLabel;
        $resultBox->append($maxChildrenHBox, false);

        // pm.start_servers
        $startServersHBox = new LibuiVBox();
        $startServersHBox->setPadded(true);
        $startServersLabel = new LibuiLabel("pm.start_servers: 0");
        $startServersHBox->append($startServersLabel, false);
        $this->startServersLabel = $startServersLabel;
        $resultBox->append($startServersHBox, false);

        // pm.min_spare_servers
        $minSpareServersHBox = new LibuiVBox();
        $minSpareServersHBox->setPadded(true);
        $minSpareServersLabel = new LibuiLabel("pm.min_spare_servers: 0");
        $minSpareServersHBox->append($minSpareServersLabel, false);
        $this->minSpareServersLabel = $minSpareServersLabel;
        $resultBox->append($minSpareServersHBox, false);

        // pm.max_spare_servers
        $maxSpareServersHBox = new LibuiVBox();
        $maxSpareServersHBox->setPadded(true);
        $maxSpareServersLabel = new LibuiLabel("pm.max_spare_servers: 0");
        $maxSpareServersHBox->append($maxSpareServersLabel, false);
        $this->maxSpareServersLabel = $maxSpareServersLabel;
        $resultBox->append($maxSpareServersHBox, false);
    }

    private function addConfigOutput(LibuiVBox $container)
    {
        // Configuration output group
        $configGroup = new LibuiGroup("PHP-FPM 配置示例");
        $configGroup->setPadded(false);
        $container->append($configGroup, true);

        $configBox = new LibuiVBox();
        $configBox->setPadded(true);
        $configGroup->append($configBox, false);

        $this->configOutput = new LibuiMultilineEntry();
        $this->configOutput->setReadOnly(true);
        // Set a minimum height for the multiline entry to ensure it's visible
        $this->configOutput->setText("; PHP-FPM 配置将在这里显示\n; 请输入参数并点击计算按钮");
        $configBox->append($this->configOutput, true);
    }

    private function addEventHandlers()
    {
        // Add event handlers for input fields
        $this->totalRamEntry->on('entry.changed', function () {
            $this->calculate();
        });

        $this->reservedRamEntry->on('entry.changed', function () {
            $this->calculate();
        });

        $this->ramBufferEntry->on('entry.changed', function () {
            $this->calculate();
        });

        $this->processSizeEntry->on('entry.changed', function () {
            $this->calculate();
        });
    }

    private function initializeDefaultValues()
    {
        // Set default values
        $this->totalRamEntry->setText("8");
        $this->reservedRamEntry->setText("2");
        $this->ramBufferEntry->setText("10");
        $this->processSizeEntry->setText("64");
        // Set initial value for available RAM label
    }

    private function calculate()
    {
        try {
            // Get input values
            $totalRamGb = floatval($this->totalRamEntry->getText()) ?: 0;
            $reservedRamGb = floatval($this->reservedRamEntry->getText()) ?: 0;
            $ramBufferPercent = floatval($this->ramBufferEntry->getText()) ?: 0;
            $processSizeMb = floatval($this->processSizeEntry->getText()) ?: 0;

            // Validate inputs
            if ($processSizeMb <= 0) {
                $this->setErrorResult("进程大小必须大于0");
                return;
            }

            // Calculate available RAM
            $availableRamGb = $totalRamGb - $reservedRamGb;
            $bufferMultiplier = 1 - ($ramBufferPercent / 100);
            $effectiveAvailableRamGb = $availableRamGb * $bufferMultiplier;
            $availableRamMb = $effectiveAvailableRamGb * 1024;

            // Calculate max children
            $maxChildren = floor($availableRamMb / $processSizeMb);

            // Calculate other PM settings based on max_children
            $startServers = max(1, floor($maxChildren * 0.25)); // 25% of max_children
            $minSpareServers = max(1, floor($maxChildren * 0.1)); // 10% of max_children
            $maxSpareServers = max($minSpareServers, floor($maxChildren * 0.3)); // 30% of max_children

            // Update result labels
            $this->availableRamLabel->setText('可用内存: '.number_format($effectiveAvailableRamGb, 2) . "GB | " . number_format($availableRamMb, 2) . " MB");
            $this->maxChildrenLabel->setText("pm.max_children: " . strval($maxChildren));
            $this->startServersLabel->setText("pm.start_servers: " . strval($startServers));
            $this->minSpareServersLabel->setText("pm.min_spare_servers: " . strval($minSpareServers));
            $this->maxSpareServersLabel->setText("pm.max_spare_servers: " . strval($maxSpareServers));

            // Generate configuration
            $configText = $this->generateConfig($maxChildren, $startServers, $minSpareServers, $maxSpareServers);
            $this->configOutput->setText($configText);

        } catch (\Exception $e) {
            $this->setErrorResult("计算错误: " . $e->getMessage());
        }
    }

    private function setErrorResult($errorMessage)
    {
        $this->availableRamLabel->setText("可用内存: 错误");
        $this->maxChildrenLabel->setText("pm.max_children: 错误");
        $this->startServersLabel->setText("pm.start_servers: 错误");
        $this->minSpareServersLabel->setText("pm.min_spare_servers: 错误");
        $this->maxSpareServersLabel->setText("pm.max_spare_servers: 错误");
        $this->configOutput->setText($errorMessage);
    }

    private function generateConfig($maxChildren, $startServers, $minSpareServers, $maxSpareServers)
    {
        return "; PHP-FPM 进程管理器配置\n" .
            "; 根据可用内存和进程大小自动计算\n\n" .
            "[www]\n" .
            "; 静态或动态进程管理\n" .
            "pm = dynamic\n\n" .
            "; 最大子进程数量\n" .
            "pm.max_children = $maxChildren\n\n" .
            "; 启动时的服务器数量\n" .
            "pm.start_servers = $startServers\n\n" .
            "; 最小空闲服务器数量\n" .
            "pm.min_spare_servers = $minSpareServers\n\n" .
            "; 最大空闲服务器数量\n" .
            "pm.max_spare_servers = $maxSpareServers\n\n" .
            "; 在指定时间内最多可以有多少个进程被回收\n" .
            "pm.max_requests = 500\n\n" .
            "; 优雅地重新加载，当收到重新加载信号时，等待正在处理的请求完成\n" .
            "pm.process_idle_timeout = 10s";
    }

    public function getControl()
    {
        return $this->box;
    }
}
