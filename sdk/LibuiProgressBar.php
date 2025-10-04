<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\ProgressBar;

/**
 * 进度条组件
 */
class LibuiProgressBar extends LibuiComponent
{
    private int $value = 0;

    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
    }

    protected function createHandle(): CData {
        return ProgressBar::create();
    }

    public function setValue(int $value): self {
        $this->value = max(0, min(100, $value));
        ProgressBar::setValue($this->handle, $this->value);
        $this->emit('progressbar.value_changed', $this->value);
        return $this;
    }

    public function getValue(): int {
        return $this->value;
    }
}
