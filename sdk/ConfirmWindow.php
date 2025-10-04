<?php

namespace Kingbes\Libui\SDK;

use Kingbes\Libui\Control;

/**
 * 确认对话框窗口
 */
class ConfirmWindow extends LibuiWindow
{
    private string $message;
    private $onConfirm;
    private $onCancel;

    public function __construct(string $title, string $message, callable $onConfirm = null, callable $onCancel = null) {
        $this->message = $message;
        $this->onConfirm = $onConfirm;
        $this->onCancel = $onCancel;

        parent::__construct($title, 400, 150, false);
        $this->buildUI();
        $this->center();
    }

    private function buildUI(): void {
        $vbox = new LibuiVBox();
        $vbox->setPadded(true);

        // 消息标签
        $messageLabel = new LibuiLabel($this->message);
        $vbox->append($messageLabel, true);

        // 按钮区域
        $buttonBox = new LibuiHBox();
        $buttonBox->setPadded(true);

        $confirmBtn = new LibuiButton("确定");
        $cancelBtn = new LibuiButton("取消");

        $confirmBtn->on('button.clicked', function() {
            if ($this->onConfirm) {
                ($this->onConfirm)($this);
            }
            $this->close();
        });

        $cancelBtn->on('button.clicked', function() {
            if ($this->onCancel) {
                ($this->onCancel)($this);
            }
            $this->close();
        });

        $buttonBox->append($confirmBtn)->append($cancelBtn);
        $vbox->append($buttonBox);

        $this->setChild($vbox);
    }

    public function close(): void {
        Control::hide($this->handle);
        $this->emit('confirm_window.closed');
    }
}
