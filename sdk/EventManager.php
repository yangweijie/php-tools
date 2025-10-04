<?php

namespace Kingbes\Libui\SDK;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 统一事件管理器
 */
class EventManager
{
    private array $listeners = [];
    private ?LoggerInterface $logger = null;

    public function __construct() {
        // 延迟初始化 Logger，避免循环依赖
    }

    private function getLogger(): LoggerInterface {
        if ($this->logger === null) {
            // 尝试获取应用程序的 Logger，如果失败则使用空 Logger
            try {
                $this->logger = LibuiApplication::getInstance()->getLogger();
            } catch (\Throwable $e) {
                $this->logger = new NullLogger();
            }
        }
        return $this->logger;
    }

    public function on(string $event, callable $callback, int $priority = 0): string {
        $id = uniqid('listener_');
        $this->listeners[$event][$priority][$id] = $callback;
        krsort($this->listeners[$event]); // 高优先级先执行

        $this->getLogger()->debug("Event listener registered", [
            'event' => $event,
            'listener_id' => $id,
            'priority' => $priority
        ]);

        return $id;
    }

    public function off(string $event, string $listenerId): bool {
        foreach ($this->listeners[$event] ?? [] as $priority => &$callbacks) {
            if (isset($callbacks[$listenerId])) {
                unset($callbacks[$listenerId]);
                $this->getLogger()->debug("Event listener removed", [
                    'event' => $event,
                    'listener_id' => $listenerId
                ]);
                return true;
            }
        }
        return false;
    }

    public function emit(string $event, LibuiComponent $source, mixed $data = null): void {
        $this->getLogger()->debug("Event emitted", [
            'event' => $event,
            'source' => get_class($source),
            'source_id' => $source->getId()
        ]);

        foreach ($this->listeners[$event] ?? [] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                try {
                    $callback($source, $data);
                } catch (\Throwable $e) {
                    $this->getLogger()->error("Event listener error", [
                        'event' => $event,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }
    }
}
