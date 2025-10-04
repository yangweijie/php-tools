<?php

namespace Kingbes\Libui\SDK;

use FFI\CData;
use Kingbes\Libui\Tab;
use Kingbes\Libui\Control;

/**
 * 标签页组件
 */
class LibuiTab extends LibuiComponent
{
    private array $tabs = []; // 保存标签页引用

    public function __construct() {
        parent::__construct();
        $this->handle = $this->createHandle();
    }

    protected function createHandle(): CData {
        return Tab::create();
    }

    public function append(string $name, $control): self {
        $index = Tab::numPages($this->handle);
        
        // 检查control是否为LibuiComponent实例
        if ($control instanceof LibuiComponent) {
            Tab::append($this->handle, $name, $control->getHandle());
        } else {
            // 假设control是FFI\CData对象
            Tab::append($this->handle, $name, $control);
        }
        
        Tab::setMargined($this->handle, $index, true);
        
        // 如果是LibuiComponent实例，添加到子组件中
        if ($control instanceof LibuiComponent) {
            $this->addChild($control);
        }
        
        return $this;
    }

    /**
     * 添加带有回调函数的标签页
     */
    public function appendWithCallback(string $name, $control, callable $callback = null): self {
        $index = Tab::numPages($this->handle);
        
        // 检查control是否为LibuiComponent实例
        if ($control instanceof LibuiComponent) {
            Tab::append($this->handle, $name, $control->getHandle());
        } else {
            // 假设control是FFI\CData对象
            Tab::append($this->handle, $name, $control);
        }
        
        Tab::setMargined($this->handle, $index, true);
        
        // 如果是LibuiComponent实例，添加到子组件中
        if ($control instanceof LibuiComponent) {
            $this->addChild($control);
        }
        
        // 保存标签页回调函数
        if ($callback) {
            $this->tabs[$index] = $callback;
        }
        
        return $this;
    }
}