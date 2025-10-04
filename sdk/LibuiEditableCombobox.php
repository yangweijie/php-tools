<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\EditableCombobox;

/**
 * 可编辑下拉框组件
 */
class LibuiEditableCombobox extends LibuiComponent
{
    private array $items = [];
    private string $text = '';

    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
        $this->setupEditableComboboxEvents();
    }

    protected function createHandle(): CData {
        return EditableCombobox::create();
    }

    private function setupEditableComboboxEvents(): void {
        EditableCombobox::onChanged($this->handle, function() {
            $this->text = EditableCombobox::text($this->handle);
            $this->emit('editablecombobox.changed', $this->text);
        });
    }

    public function append(string $item): self {
        $this->items[] = $item;
        EditableCombobox::append($this->handle, $item);
        return $this;
    }

    public function setText(string $text): self {
        $this->text = $text;
        EditableCombobox::setText($this->handle, $text);
        return $this;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getItems(): array {
        return $this->items;
    }
}