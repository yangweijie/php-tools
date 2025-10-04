<?php

namespace Kingbes\Libui\SDK;

use Kingbes\Libui\Control;
use Kingbes\Libui\Window;

/**
 * 保存文件对话框窗口
 */
/**
 * 文件保存窗口 - 包含保存文件按钮的窗口
 */
class SaveWindow extends LibuiWindow
{
    private LibuiEntry $filenameEntry;
    private LibuiButton $saveBtn;
    private LibuiLabel $statusLabel;
    private $onFileSaved = null;

    public function __construct(string $title = "保存文件") {
        parent::__construct($title, 500, 250);
        $this->buildUI();
        $this->center();
    }

    private function buildUI(): void {
        $vbox = new LibuiVBox();
        $vbox->setPadded(true);

        // 文件名输入
        $form = new LibuiForm();
        $form->setPadded(true);

        $this->filenameEntry = new LibuiEntry();
        $this->filenameEntry->setText("untitled.txt");
        $form->append("文件名:", $this->filenameEntry);

        // 保存按钮
        $this->saveBtn = new LibuiButton("另存为...");
        $this->saveBtn->on('button.clicked', function() {
            $this->saveFileDialog();
        });

        // 状态显示
        $this->statusLabel = new LibuiLabel("准备保存");

        $vbox->append($form)
            ->append($this->saveBtn)
            ->append($this->statusLabel, true);

        $this->setChild($vbox);
    }

    private function saveFileDialog(): void {
        $filePath = Window::saveFile($this->handle);

        if (!empty($filePath)) {
            $this->statusLabel->setText("保存到: " . $filePath);

            if ($this->onFileSaved) {
                ($this->onFileSaved)($filePath, $this->filenameEntry->getText(), $this);
            }

            $this->emit('file.saved', [
                'path' => $filePath,
                'filename' => $this->filenameEntry->getText()
            ]);
        }
    }

    public function onFileSaved(callable $callback): self {
        $this->onFileSaved = $callback;
        return $this;
    }
}
