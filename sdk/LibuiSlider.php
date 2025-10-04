<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Slider;

/**
 * 滑块组件
 */
class LibuiSlider extends LibuiComponent
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
        $this->setupSliderEvents();
    }

    protected function createHandle(): CData {
        return Slider::create($this->min, $this->max);
    }

    private function setupSliderEvents(): void {
        Slider::onChanged($this->handle, function() {
            $this->value = Slider::value($this->handle);
            $this->emit('slider.changed.' . $this->getId(), $this->value);
        });
    }

    // 便捷方法
    public function onChange(callable $callback, int $priority = 0): string {
        return $this->on('slider.changed', $callback, $priority);
    }

    public function setValue(int $value): self {
        $this->value = max($this->min, min($this->max, $value));
        Slider::setValue($this->handle, $this->value);
        return $this;
    }

    public function getValue(): int {
        return $this->value;
    }
}
