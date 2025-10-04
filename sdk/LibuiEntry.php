<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Entry;

/**
 * 输入框组件
 */
class LibuiEntry extends LibuiComponent
{
    private bool $readOnly = false;
    private string $type = 'normal'; // normal, password, search

    public function __construct(string $type = 'normal') {
        parent::__construct();
        $this->type = $type;
        $this->handle = $this->createHandle();
        $this->setupEntryEvents();
    }

    protected function createHandle(): CData {
        return match($this->type) {
            'password' => Entry::createPwd(),
            'search' => Entry::createSearch(),
            default => Entry::create(),
        };
    }

    private function setupEntryEvents(): void {
        Entry::onChanged($this->handle, function() {
            $this->emit('entry.changed.' . $this->getId(), $this->getText());
        });
    }

    // 便捷方法
    public function onChange(callable $callback, int $priority = 0): string {
        return $this->on('entry.changed', $callback, $priority);
    }

    public function setText(string $text): self {
        Entry::setText($this->handle, $text);
        return $this;
    }

    public function getText(): string {
        return Entry::text($this->handle);
    }

    public function setReadOnly(bool $readOnly): self {
        $this->readOnly = $readOnly;
        Entry::setReadOnly($this->handle, $readOnly);
        return $this;
    }

    public function isReadOnly(): bool {
        return $this->readOnly;
    }
    
    public function getType(): string {
        return $this->type;
    }
}
