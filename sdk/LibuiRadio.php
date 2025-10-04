<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Radio;

/**
 * 单选框组件
 */
class LibuiRadio extends LibuiComponent
{
    private array $items = [];
    private int $selected = -1;

    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
        $this->setupRadioEvents();
    }

    protected function createHandle(): CData {
        return Radio::create();
    }

    private function setupRadioEvents(): void {
        Radio::onSelected($this->handle, function() {
            $this->selected = Radio::selected($this->handle);
            $this->emit('radio.selected', $this->selected);
        });
    }

    public function append(string $item): self {
        $this->items[] = $item;
        Radio::append($this->handle, $item);
        return $this;
    }

    public function setSelected(int $index): self {
        if ($index >= 0 && $index < count($this->items)) {
            $this->selected = $index;
            Radio::setSelected($this->handle, $index);
        }
        return $this;
    }

    public function getSelected(): int {
        return $this->selected;
    }

    public function getItems(): array {
        return $this->items;
    }
}