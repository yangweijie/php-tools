<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use InvalidArgumentException;
use Kingbes\Libui\Control;
use Kingbes\Libui\SDK\Enums\WindowPosition;
use Kingbes\Libui\Window;

/**
 * 窗口组件
 */
class LibuiWindow extends LibuiComponent
{
    private string $title;
    private int $width;
    private int $height;
    private bool $hasMenubar;

    public function __construct(string $title, int $width = 640, int $height = 480, bool $hasMenubar = false) {
        parent::__construct();
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
        $this->hasMenubar = $hasMenubar;
        $this->handle = $this->createHandle();

        $this->setupWindowEvents();
    }

    protected function createHandle(): CData {
        return Window::create($this->title, $this->width, $this->height, $this->hasMenubar ? 1 : 0);
    }

    private function setupWindowEvents(): void {
        Window::onClosing($this->handle, function() {
            $this->emit('window.closing');
            return 1; // 允许关闭
        });
    }

    public function setTitle(string $title): self {
        $this->title = $title;
        Window::setTitle($this->handle, $title);
        $this->emit('window.title_changed', $title);
        return $this;
    }

    public function show(): self {
        Control::show($this->handle);
        $this->emit('window.shown');
        return $this;
    }

    public function setChild(LibuiComponent $child): self {
        Window::setChild($this->handle, $child->getHandle());
        $this->addChild($child);
        return $this;
    }

    protected function onChildAdded(LibuiComponent $child): void {
        // 窗口只能有一个直接子元素
        if (count($this->children) > 1) {
            $this->logger->warning("Window can only have one direct child", [
                'window' => $this->getId(),
                'children_count' => count($this->children)
            ]);
        }
    }

    /**
     * 设置窗口位置到屏幕的特定位置
     */
    public function setScreenPosition(WindowPosition $position): self {
        $app = LibuiApplication::getInstance();
        $screenWidth = $app->getScreenWidth();
        $screenHeight = $app->getScreenHeight();

        [$x, $y] = $this->calculatePosition($position, $screenWidth, $screenHeight, $this->width, $this->height);

        Window::setPosition($this->handle, $x, $y);
        $this->emit('window.position_changed', ['x' => $x, 'y' => $y, 'relative_to' => 'screen']);

        return $this;
    }

    /**
     * 设置窗口位置到父组件的特定位置
     */
    public function setRelativePosition(WindowPosition $position, LibuiComponent $parent = null): self {
        $parentComponent = $parent ?? $this->parentComponent;

        if (!$parentComponent instanceof LibuiWindow) {
            throw new InvalidArgumentException('Parent must be a window component for relative positioning');
        }

        $parentPos = Window::position($parentComponent->getHandle());
        $parentSize = Window::contentSize($parentComponent->getHandle());

        [$relativeX, $relativeY] = $this->calculatePosition(
            $position,
            $parentSize[0],
            $parentSize[1],
            $this->width,
            $this->height
        );

        $x = $parentPos[0] + $relativeX;
        $y = $parentPos[1] + $relativeY;

        Window::setPosition($this->handle, $x, $y);
        $this->emit('window.position_changed', [
            'x' => $x,
            'y' => $y,
            'relative_to' => $parentComponent->getId()
        ]);

        return $this;
    }

    /**
     * 快捷方法：居中显示
     */
    public function center(): self {
        return $this->setScreenPosition(WindowPosition::CENTER);
    }

    /**
     * 快捷方法：左上角
     */
    public function topLeft(): self {
        return $this->setScreenPosition(WindowPosition::TOP_LEFT);
    }

    /**
     * 快捷方法：右上角
     */
    public function topRight(): self {
        return $this->setScreenPosition(WindowPosition::TOP_RIGHT);
    }

    /**
     * 快捷方法：左下角
     */
    public function bottomLeft(): self {
        return $this->setScreenPosition(WindowPosition::BOTTOM_LEFT);
    }

    /**
     * 快捷方法：右下角
     */
    public function bottomRight(): self {
        return $this->setScreenPosition(WindowPosition::BOTTOM_RIGHT);
    }

    /**
     * 设置父组件（用于相对定位）
     */
    public function setParentComponent(LibuiComponent $parent): self {
        $this->parentComponent = $parent;
        return $this;
    }

    // 私有辅助方法
    private function calculatePosition(WindowPosition $position, int $containerWidth, int $containerHeight, int $windowWidth, int $windowHeight): array {
        return match($position) {
            WindowPosition::TOP_LEFT => [0, 0],
            WindowPosition::TOP_CENTER => [($containerWidth - $windowWidth) / 2, 0],
            WindowPosition::TOP_RIGHT => [$containerWidth - $windowWidth, 0],
            WindowPosition::CENTER_LEFT => [0, ($containerHeight - $windowHeight) / 2],
            WindowPosition::CENTER => [($containerWidth - $windowWidth) / 2, ($containerHeight - $windowHeight) / 2],
            WindowPosition::CENTER_RIGHT => [$containerWidth - $windowWidth, ($containerHeight - $windowHeight) / 2],
            WindowPosition::BOTTOM_LEFT => [0, $containerHeight - $windowHeight],
            WindowPosition::BOTTOM_CENTER => [($containerWidth - $windowWidth) / 2, $containerHeight - $windowHeight],
            WindowPosition::BOTTOM_RIGHT => [$containerWidth - $windowWidth, $containerHeight - $windowHeight],
        };
    }
}
