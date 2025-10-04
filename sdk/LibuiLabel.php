<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Label;

/**
 * 标签组件
 */
class LibuiLabel extends LibuiComponent
{
    private string $text;

    public function __construct(string $text) {
        parent::__construct();
        $this->text = $text;
        $this->handle = $this->createHandle();
    }

    protected function createHandle(): CData {
        return Label::create($this->text);
    }

    public function setText(string $text): self {
        $this->text = $text;
        Label::setText($this->handle, $text);
        $this->emit('label.text_changed', $text);
        return $this;
    }

    public function getText(): string {
        return $this->text;
    }
}
