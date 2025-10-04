<?php

namespace Kingbes\Libui\SDK;

use Kingbes\Libui\Window;

/**
 * 消息框窗口 - 包含各种消息提示的窗口
 */
class MessageWindow extends LibuiWindow
{
    private LibuiEntry $titleEntry;
    private LibuiEntry $messageEntry;
    private LibuiButton $infoBtn;
    private LibuiButton $errorBtn;

    public function __construct(string $title = "消息提示") {
        parent::__construct($title, 450, 300);
        $this->buildUI();
        $this->center();
    }

    private function buildUI(): void {
        $vbox = new LibuiVBox();
        $vbox->setPadded(true);

        // 输入区域
        $form = new LibuiForm();
        $form->setPadded(true);

        $this->titleEntry = new LibuiEntry();
        $this->titleEntry->setText("提示");
        $form->append("标题:", $this->titleEntry);

        $this->messageEntry = new LibuiEntry();
        $this->messageEntry->setText("这是一条消息");
        $form->append("消息:", $this->messageEntry);

        // 按钮区域
        $buttonBox = new LibuiHBox();
        $buttonBox->setPadded(true);

        $this->infoBtn = new LibuiButton("显示信息");
        $this->errorBtn = new LibuiButton("显示错误");

        $this->infoBtn->on('button.clicked', function() {
            $this->showInfoMessage();
        });

        $this->errorBtn->on('button.clicked', function() {
            $this->showErrorMessage();
        });

        $buttonBox->append($this->infoBtn)->append($this->errorBtn);

        $vbox->append($form, true)->append($buttonBox);
        $this->setChild($vbox);
    }

    private function showInfoMessage(): void {
        Window::msgBox(
            $this->handle,
            $this->titleEntry->getText(),
            $this->messageEntry->getText()
        );

        $this->emit('message.info_shown', [
            'title' => $this->titleEntry->getText(),
            'message' => $this->messageEntry->getText()
        ]);
    }

    private function showErrorMessage(): void {
        Window::msgBoxError(
            $this->handle,
            $this->titleEntry->getText(),
            $this->messageEntry->getText()
        );

        $this->emit('message.error_shown', [
            'title' => $this->titleEntry->getText(),
            'message' => $this->messageEntry->getText()
        ]);
    }
}
