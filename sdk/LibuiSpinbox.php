<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Spinbox;

/**
 * 数字输入框组件
 */
class LibuiSpinbox extends LibuiComponent
{
    private int $min;
    private int $max;
    private int $value;

    public function __construct(int $min, int $max) {
        parent::__construct();
        $this->min = $min;
        $this->max = $max;
        $this->value = $min;
        $this->handle = $this->createHandle();
        $this->setupSpinboxEvents();
    }

    protected function createHandle(): CData {
        return Spinbox::create($this->min, $this->max);
    }

    private function setupSpinboxEvents(): void {
        Spinbox::onChanged($this->handle, function() {
            $this->value = Spinbox::value($this->handle);
            $this->emit('spinbox.changed.' . $this->getId(), $this->value);
        });
    }

    // 便捷方法
    public function onChange(callable $callback, int $priority = 0): string {
        return $this->on('spinbox.changed', $callback, $priority);
    }

    public function setValue(int $value): self {
        $this->value = max($this->min, min($this->max, $value));
        Spinbox::setValue($this->handle, $this->value);
        return $this;
    }

    public function getValue(): int {
        return $this->value;
    }
}
