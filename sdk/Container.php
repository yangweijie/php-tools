<?php

namespace Kingbes\Libui\SDK;

/**
 * 容器组件基类
 */
abstract class Container extends LibuiComponent
{
    protected bool $padded = false;

    public function setPadded(bool $padded): self {
        $this->padded = $padded;
        $this->applyPadding();
        return $this;
    }

    abstract protected function applyPadding(): void;
}
