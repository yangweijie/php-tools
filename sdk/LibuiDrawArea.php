<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Area;

/**
 * 绘图区域组件
 */
class LibuiDrawArea extends LibuiComponent
{
    private $drawHandler = null;
    private $mouseHandler = null;
    private $keyHandler = null;
    private int $width = 400;
    private int $height = 400;

    public function __construct(int $width = 400, int $height = 400) {
        parent::__construct();
        $this->width = $width;
        $this->height = $height;
        $this->handle = $this->createHandle();
    }

    protected function createHandle(): CData {
        $handler = Area::handler(
            $this->getDrawCallback(),
            $this->getKeyCallback(),
            $this->getMouseCallback()
        );

        return Area::create($handler);
    }

    public function onDraw(callable $callback): self {
        $this->drawHandler = $callback;
        return $this;
    }

    public function onMouse(callable $callback): self {
        $this->mouseHandler = $callback;
        return $this;
    }

    public function onKey(callable $callback): self {
        $this->keyHandler = $callback;
        return $this;
    }

    public function setSize(int $width, int $height): self {
        $this->width = $width;
        $this->height = $height;
        Area::setSize($this->handle, $width, $height);
        return $this;
    }

    public function redraw(): self {
        Area::queueRedraw($this->handle);
        return $this;
    }

    private function getDrawCallback(): callable {
        return function($handler, $area, $params) {
            if ($this->drawHandler) {
                $drawContext = new LibuiDrawContext($params);
                ($this->drawHandler)($drawContext);
            }
        };
    }

    private function getMouseCallback(): callable {
        return function($handler, $area, $mouseEvent) {
            if ($this->mouseHandler) {
                ($this->mouseHandler)($mouseEvent);
            }
        };
    }

    private function getKeyCallback(): callable {
        return function($handler, $area, $keyEvent) {
            if ($this->keyHandler) {
                return ($this->keyHandler)($keyEvent) ? 1 : 0;
            }
            return 0;
        };
    }
}
