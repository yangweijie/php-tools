<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Combobox;

/**
 * 下拉框组件
 */
class LibuiCombobox extends LibuiComponent
{
    private array $items = [];
    private int $selected = -1;

    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
        $this->setupComboboxEvents();
    }

    protected function createHandle(): CData {
        return Combobox::create();
    }

    private function setupComboboxEvents(): void {
        Combobox::onSelected($this->handle, function() {
            $this->selected = Combobox::selected($this->handle);
            $this->emit('combobox.selected.' . $this->getId(), $this->selected);
        });
    }

    // 便捷方法
    public function onSelected(callable $callback, int $priority = 0): string {
        return $this->on('combobox.selected', $callback, $priority);
    }

    public function append(string $item): self {
        $this->items[] = $item;
        Combobox::append($this->handle, $item);
        return $this;
    }

    public function setSelected(int $index): self {
        if ($index >= 0 && $index < count($this->items)) {
            $this->selected = $index;
            Combobox::setSelected($this->handle, $index);
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
