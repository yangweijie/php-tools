<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\MultilineEntry;

/**
 * 多行文本框组件
 */
class LibuiMultilineEntry extends LibuiComponent
{
    private string $text = '';
    private bool $readOnly = false;

    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
        $this->setupMultilineEntryEvents();
    }

    protected function createHandle(): CData {
        return MultilineEntry::create();
    }

    private function setupMultilineEntryEvents(): void {
        MultilineEntry::onChanged($this->handle, function() {
            $this->text = MultilineEntry::text($this->handle);
            $this->emit('multilineentry.changed.' . $this->getId(), $this->text);
        });
    }

    // 便捷方法
    public function onChange(callable $callback, int $priority = 0): string {
        return $this->on('multilineentry.changed.'.$this->getId(), $callback, $priority);
    }

    public function setText(string $text): self {
        $this->text = $text;
        MultilineEntry::setText($this->handle, $text);
        return $this;
    }

    public function getText(): string {
        return $this->text;
    }

    public function setReadOnly(bool $readOnly): self {
        $this->readOnly = $readOnly;
        MultilineEntry::setReadOnly($this->handle, $readOnly);
        return $this;
    }

    public function isReadOnly(): bool {
        return $this->readOnly;
    }
}
