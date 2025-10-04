<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Base;
use Kingbes\Libui\DrawLineCap;
use Kingbes\Libui\DrawLineJoin;

/**
 * 绘图描边参数封装
 */
class LibuiStrokeParams
{
    private CData $handle;

    public function __construct(float $thickness = 1.0) {
        $this->handle = Base::ffi()->new("uiDrawStrokeParams");
        $this->handle->Cap = DrawLineCap::Flat->value;
        $this->handle->Join = DrawLineJoin::Miter->value;
        $this->handle->Thickness = $thickness;
        $this->handle->MiterLimit = 10.0;
    }

    public function getHandle(): CData {
        return $this->handle;
    }

    public function setThickness(float $thickness): self {
        $this->handle->Thickness = $thickness;
        return $this;
    }
}
