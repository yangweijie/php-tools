<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Checkbox;

/**
 * 复选框组件
 */
class LibuiCheckbox extends LibuiComponent
{
    private string $text;
    private bool $checked = false;

    public function __construct(string $text) {
        parent::__construct();
        $this->text = $text;
        $this->handle = $this->createHandle();
        $this->setupCheckboxEvents();
    }

    protected function createHandle(): CData {
        return Checkbox::create($this->text);
    }

    private function setupCheckboxEvents(): void {
        Checkbox::onToggled($this->handle, function() {
            $this->checked = Checkbox::checked($this->handle);
            $this->emit('checkbox.toggled.' . $this->getId(), $this->checked);
        });
    }

    // 便捷方法
    public function onToggled(callable $callback, int $priority = 0): string {
        return $this->on('checkbox.toggled', $callback, $priority);
    }

    public function setText(string $text): self {
        $this->text = $text;
        Checkbox::setText($this->handle, $text);
        return $this;
    }

    public function setChecked(bool $checked): self {
        $this->checked = $checked;
        Checkbox::setChecked($this->handle, $checked);
        return $this;
    }

    public function isChecked(): bool {
        return $this->checked;
    }
}
