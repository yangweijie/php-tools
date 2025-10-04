<?php

namespace Kingbes\Libui\SDK;

use Kingbes\Libui\Window;

/**
 * 浏览器窗口（模拟，实际需要WebView组件）
 */
/**
 * 文件浏览窗口 - 包含打开文件按钮的窗口
 */
class BrowserWindow extends LibuiWindow
{
    private LibuiButton $openFileBtn;
    private LibuiLabel $selectedFileLabel;
    private $onFileSelected = null;

    public function __construct(string $title = "文件浏览器") {
        parent::__construct($title, 500, 300);
        $this->buildUI();
        $this->center();
    }

    private function buildUI(): void {
        $vbox = new LibuiVBox();
        $vbox->setPadded(true);

        // 文件选择区域
        $fileGroup = new LibuiGroup("选择文件");
        $fileGroup->setPadded(true);

        $fileVBox = new LibuiVBox();
        $fileVBox->setPadded(true);

        $this->openFileBtn = new LibuiButton("浏览文件...");
        $this->selectedFileLabel = new LibuiLabel("未选择文件");

        $this->openFileBtn->on('button.clicked', function() {
            $this->openFileDialog();
        });

        $fileVBox->append($this->openFileBtn)
            ->append($this->selectedFileLabel);

        $fileGroup->append($fileVBox);
        $vbox->append($fileGroup, true);

        $this->setChild($vbox);
    }

    private function openFileDialog(): void {
        $filePath = Window::openFile($this->handle);

        if (!empty($filePath)) {
            $this->selectedFileLabel->setText("选中: " . basename($filePath));

            if ($this->onFileSelected) {
                ($this->onFileSelected)($filePath, $this);
            }

            $this->emit('file.selected', $filePath);
        }
    }

    public function onFileSelected(callable $callback): self {
        $this->onFileSelected = $callback;
        return $this;
    }

    public function getSelectedFile(): string {
        return $this->selectedFileLabel->getText();
    }
}
