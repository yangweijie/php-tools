<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Form;

/**
 * 表单容器
 */
class LibuiForm extends Container
{
    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
    }

    protected function createHandle(): CData {
        return Form::create();
    }

    protected function applyPadding(): void {
        Form::setPadded($this->handle, $this->padded);
    }

    public function append(string $label, LibuiComponent $child, bool $stretchy = false): self {
        Form::append($this->handle, $label, $child->getHandle(), $stretchy);
        $this->addChild($child);
        return $this;
    }
}
