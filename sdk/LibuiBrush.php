<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Draw;
use Kingbes\Libui\DrawBrushType;

/**
 * 绘图画刷封装
 */
class LibuiBrush
{
    private CData $handle;

    public function __construct(float $r, float $g, float $b, float $a = 1.0) {
        $this->handle = Draw::createBrush(DrawBrushType::Solid);
        $this->handle->R = $r;
        $this->handle->G = $g;
        $this->handle->B = $b;
        $this->handle->A = $a;
    }

    public function getHandle(): CData {
        return $this->handle;
    }
}
