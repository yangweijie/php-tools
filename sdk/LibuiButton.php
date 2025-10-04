<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Button;

/**
 * 按钮组件
 */
class LibuiButton extends LibuiComponent
{
    private string $text;

    public function __construct(string $text) {
        parent::__construct();
        $this->text = $text;
        $this->handle = $this->createHandle();
        $this->setupButtonEvents();
    }

    protected function createHandle(): CData {
        return Button::create($this->text);
    }

    private function setupButtonEvents(): void {
        Button::onClicked($this->handle, function() {
            // 发出特定于该按钮的事件
            $this->emit('button.clicked.' . $this->getId());
        });
    }

    // 便捷方法
    public function onClick(callable $callback, int $priority = 0): string {
        return $this->on('button.clicked', $callback, $priority);
    }

    public function setText(string $text): self {
        $this->text = $text;
        Button::setText($this->handle, $text);
        $this->emit('button.text_changed', $text);
        return $this;
    }

    public function getText(): string {
        return $this->text;
    }
}
