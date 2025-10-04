<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Group;

/**
 * 分组容器
 */
class LibuiGroup extends Container
{
    private string $title;

    public function __construct(string $title) {
        parent::__construct();
        $this->title = $title;
        $this->handle = $this->createHandle();
    }

    protected function createHandle(): CData {
        return Group::create($this->title);
    }

    protected function applyPadding(): void {
        Group::setMargined($this->handle, $this->padded);
    }

    public function append(LibuiComponent $child, bool $stretchy = false): self {
        Group::setChild($this->handle, $child->getHandle());
        $this->addChild($child);
        return $this;
    }

    public function setTitle(string $title): self {
        $this->title = $title;
        Group::setTitle($this->handle, $title);
        return $this;
    }
}
