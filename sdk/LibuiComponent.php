<?php
namespace Kingbes\Libui\SDK;
use FFI\CData;
use Psr\Log\LoggerInterface;

/**
 * 组件基类 - 实现树形结构和句柄管理
 */
abstract class LibuiComponent
{
    protected string $id;
    protected ?CData $handle = null;
    protected ?LibuiComponent $parent = null;
    protected array $children = [];
    protected EventManager $eventManager;
    protected LoggerInterface $logger;
    protected array $eventListeners = [];

    public function __construct() {
        $this->id = uniqid(static::class . '_');
        $this->eventManager = LibuiApplication::getInstance()->getEventManager();
        $this->logger = LibuiApplication::getInstance()->getLogger();

        $this->logger->debug("Component created", [
            'class' => static::class,
            'id' => $this->id
        ]);
    }

    public function getId(): string {
        return $this->id;
    }

    public function getHandle(): ?CData {
        return $this->handle;
    }

    public function getParent(): ?LibuiComponent {
        return $this->parent;
    }

    public function getChildren(): array {
        return $this->children;
    }

    public function addChild(LibuiComponent $child): self {
        if ($child->parent !== null) {
            $child->parent->removeChild($child);
        }

        $this->children[$child->getId()] = $child;
        $child->parent = $this;

        $this->onChildAdded($child);
        $this->eventManager->emit('child.added', $this, $child);

        $this->logger->debug("Child added", [
            'parent' => $this->getId(),
            'child' => $child->getId()
        ]);

        return $this;
    }

    public function removeChild(LibuiComponent $child): self {
        if (isset($this->children[$child->getId()])) {
            unset($this->children[$child->getId()]);
            $child->parent = null;

            $this->onChildRemoved($child);
            $this->eventManager->emit('child.removed', $this, $child);

            $this->logger->debug("Child removed", [
                'parent' => $this->getId(),
                'child' => $child->getId()
            ]);
        }

        return $this;
    }

    public function on(string $event, callable $callback, int $priority = 0): string {
        // 为事件添加组件特定的标识符
        $specificEvent = $event . '.' . $this->getId();
        $listenerId = $this->eventManager->on($specificEvent, $callback, $priority);
        $this->eventListeners[] = [$specificEvent, $listenerId];
        return $listenerId;
    }

    // 获取组件类型的方法
    public function getComponentType(): string {
        $class = get_class($this);
        $parts = explode('\\', $class);
        $className = end($parts);
        
        // 移除"Libui"前缀并转换为小写
        if (strpos($className, 'Libui') === 0) {
            $className = substr($className, 5);
        }
        
        // 转换为小写
        return strtolower($className);
    }

    // 通用的便捷事件绑定方法
    public function onEvent(string $eventType, callable $callback, int $priority = 0): string {
        $componentType = $this->getComponentType();
        // 将组件类型转换为事件前缀格式
        $eventPrefix = $this->convertToEventPrefix($componentType);
        return $this->on($eventPrefix . '.' . $eventType, $callback, $priority);
    }

    // 转换组件类型为事件前缀
    private function convertToEventPrefix(string $componentType): string {
        // 处理特殊命名情况
        $prefixMap = [
            'button' => 'button',
            'entry' => 'entry',
            'combobox' => 'combobox',
            'checkbox' => 'checkbox',
            'multilineentry' => 'multilineentry',
            'datetimepicker' => 'datetimepicker',
            'spinbox' => 'spinbox',
            'slider' => 'slider',
            'tab' => 'tab',
            'table' => 'table'
        ];
        
        return $prefixMap[$componentType] ?? $componentType;
    }

    public function emit(string $event, mixed $data = null): void {
        $this->eventManager->emit($event, $this, $data);
    }

    // 组件生命周期钩子
    protected function onChildAdded(LibuiComponent $child): void {}
    protected function onChildRemoved(LibuiComponent $child): void {}

    // 子类必须实现句柄创建
    abstract protected function createHandle(): CData;

    // 清理资源
    public function destroy(): void {
        // 清理事件监听
        foreach ($this->eventListeners as [$event, $listenerId]) {
            $this->eventManager->off($event, $listenerId);
        }

        // 递归销毁子组件
        foreach ($this->children as $child) {
            $child->destroy();
        }

        $this->logger->debug("Component destroyed", ['id' => $this->id]);
    }
}
