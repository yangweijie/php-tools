<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Grid;

/**
 * 网格容器
 */
class LibuiGrid extends Container
{
    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
    }

    protected function createHandle(): CData {
        return Grid::create();
    }

    protected function applyPadding(): void {
        Grid::setPadded($this->handle, $this->padded);
    }

    public function append(LibuiComponent $child, int $left, int $top, int $xspan = 1, int $yspan = 1, int $hexpand = 0, int $halign = 0, int $vexpand = 0, int $valign = 0): self {
        Grid::append($this->handle, $child->getHandle(), $left, $top, $xspan, $yspan, $hexpand, $halign, $vexpand, $valign);
        $this->addChild($child);
        return $this;
    }
}
