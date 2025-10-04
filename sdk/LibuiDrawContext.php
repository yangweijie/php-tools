<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;

/**
 * 绘图上下文封装
 */
class LibuiDrawContext
{
    private CData $params;
    private CData $context;

    public function __construct(CData $params) {
        $this->params = $params;
        $this->context = $params->Context;
    }

    public function getWidth(): float {
        return $this->params->AreaWidth;
    }

    public function getHeight(): float {
        return $this->params->AreaHeight;
    }

    public function createPath(): LibuiPath {
        return new LibuiPath();
    }

    public function stroke(LibuiPath $path, LibuiBrush $brush, LibuiStrokeParams $stroke): self {
        \Kingbes\Libui\Draw::stroke($this->context, $path->getHandle(), $brush->getHandle(), $stroke->getHandle());
        return $this;
    }

    public function fill(LibuiPath $path, LibuiBrush $brush): self {
        \Kingbes\Libui\Draw::fill($this->context, $path->getHandle(), $brush->getHandle());
        return $this;
    }
}
