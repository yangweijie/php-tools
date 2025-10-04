<?php

namespace Kingbes\Libui\SDK;

use Kingbes\Libui\App;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;

/**
 * 核心应用程序类 - 单例模式管理整个应用生命周期
 */
class LibuiApplication
{
    private static ?self $instance = null;
    private EventManager $eventManager;
    private LoggerInterface $logger;
    private array $windows = [];
    private bool $initialized = false;
    private array $screenInfo = [];

    private function __construct() {
        $this->eventManager = new EventManager();
        $this->logger = new NullLogger();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(LoggerInterface $logger = null): self {
        if ($this->initialized) {
            throw new RuntimeException("Application already initialized");
        }

        if ($logger) {
            $this->logger = $logger;
        }

        App::init();
        $this->initialized = true;
        $this->logger->info("LibUI Application initialized");

        return $this;
    }

    public function createWindow(string $title, int $width = 640, int $height = 480): LibuiWindow {
        $window = new LibuiWindow($title, $width, $height);
        $this->windows[$window->getId()] = $window;

        $this->logger->info("Window created", [
            'id' => $window->getId(),
            'title' => $title,
            'size' => "{$width}x{$height}"
        ]);

        return $window;
    }

    public function run(): void {
        $this->logger->info("Starting main loop");
        // 注册应用程序退出事件
        App::onShouldQuit(function () {
            App::quit();
            return true;
        });
        App::main();
    }

    public function quit(): void {
        $this->logger->info("Application quit requested");
        App::quit();
    }

    /**
     * 在主循环队列中执行回调函数
     *
     * @param callable $callable 要执行的回调函数
     * @return void
     */
    public function queueMain(callable $callable): void {
        App::queueMain($callable);
    }

    public function getEventManager(): EventManager {
        return $this->eventManager;
    }

    public function getLogger(): LoggerInterface {
        return $this->logger;
    }

    /**
     * 获取屏幕信息
     */
    public function getScreenInfo(): array {
        if (empty($this->screenInfo)) {
            $this->screenInfo = $this->detectScreenInfo();
        }
        return $this->screenInfo;
    }

    /**
     * 获取屏幕宽度
     */
    public function getScreenWidth(): int {
        return $this->getScreenInfo()['width'];
    }

    /**
     * 获取屏幕高度
     */
    public function getScreenHeight(): int {
        return $this->getScreenInfo()['height'];
    }

    /**
     * 获取系统类型详细信息
     */
    public function getSystemInfo(): array {
        return [
            'os_family' => PHP_OS_FAMILY,
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'architecture' => php_uname('m'),
            'hostname' => php_uname('n'),
            'sapi' => PHP_SAPI,
            'is_windows' => PHP_OS_FAMILY === 'Windows',
            'is_linux' => PHP_OS_FAMILY === 'Linux',
            'is_macos' => PHP_OS_FAMILY === 'Darwin',
        ];
    }

    /**
     * 复制到剪切板
     */
    public function copyToClipboard(string $text): bool {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $this->executeCommand(['clip'], $text);
            } elseif (PHP_OS_FAMILY === 'Linux') {
                // 尝试xclip或xsel
                if ($this->commandExists('xclip')) {
                    $this->executeCommand(['xclip', '-selection', 'clipboard'], $text);
                } elseif ($this->commandExists('xsel')) {
                    $this->executeCommand(['xsel', '--clipboard', '--input'], $text);
                } else {
                    throw new RuntimeException('No clipboard utility found (xclip or xsel required)');
                }
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                $this->executeCommand(['pbcopy'], $text);
            } else {
                throw new RuntimeException('Unsupported operating system for clipboard operations');
            }

            $this->logger->debug("Text copied to clipboard", ['length' => strlen($text)]);
            return true;
        } catch (Throwable $e) {
            $this->logger->error("Failed to copy to clipboard", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 从剪切板获取内容
     */
    public function getFromClipboard(): ?string {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                return $this->executeCommand(['powershell', '-command', 'Get-Clipboard']);
            } elseif (PHP_OS_FAMILY === 'Linux') {
                if ($this->commandExists('xclip')) {
                    return $this->executeCommand(['xclip', '-selection', 'clipboard', '-out']);
                } elseif ($this->commandExists('xsel')) {
                    return $this->executeCommand(['xsel', '--clipboard', '--output']);
                }
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                return $this->executeCommand(['pbpaste']);
            }

            throw new RuntimeException('Unsupported operating system for clipboard operations');
        } catch (Throwable $e) {
            $this->logger->error("Failed to read from clipboard", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 清空剪切板
     */
    public function clearClipboard(): bool {
        return $this->copyToClipboard('');
    }

    /**
     * 创建确认对话框窗口
     */
    public function createConfirmWindow(string $title, string $message, callable $onConfirm = null, callable $onCancel = null): ConfirmWindow {
        $window = new ConfirmWindow($title, $message, $onConfirm, $onCancel);
        $this->windows[$window->getId()] = $window;
        return $window;
    }

    /**
     * 创建浏览器窗口
     */
    public function createBrowserWindow(string $title, string $url = 'about:blank'): BrowserWindow {
        $window = new BrowserWindow($title, $url);
        $this->windows[$window->getId()] = $window;
        return $window;
    }

    /**
     * 创建保存文件对话框窗口
     */
    public function createSaveWindow(string $title, array $filters = []): SaveWindow {
        $window = new SaveWindow($title, $filters);
        $this->windows[$window->getId()] = $window;
        return $window;
    }

    // 私有辅助方法
    private function detectScreenInfo(): array {
        // 默认值
        $default = ['width' => 1920, 'height' => 1080];

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = $this->executeCommand(['wmic', 'path', 'Win32_VideoController', 'get', 'CurrentHorizontalResolution,CurrentVerticalResolution', '/format:value']);
                if (preg_match('/CurrentHorizontalResolution=(\d+)/', $output, $matches)) {
                    $default['width'] = (int)$matches[1];
                }
                if (preg_match('/CurrentVerticalResolution=(\d+)/', $output, $matches)) {
                    $default['height'] = (int)$matches[1];
                }
            } elseif (PHP_OS_FAMILY === 'Linux') {
                if ($this->commandExists('xrandr')) {
                    $output = $this->executeCommand(['xrandr']);
                    if (preg_match('/(\d+)x(\d+).*\*/', $output, $matches)) {
                        $default['width'] = (int)$matches[1];
                        $default['height'] = (int)$matches[2];
                    }
                }
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                $output = $this->executeCommand(['system_profiler', 'SPDisplaysDataType']);
                if (preg_match('/Resolution: (\d+) x (\d+)/', $output, $matches)) {
                    $default['width'] = (int)$matches[1];
                    $default['height'] = (int)$matches[2];
                }
            }
        } catch (Throwable $e) {
            $this->logger->warning("Failed to detect screen resolution", ['error' => $e->getMessage()]);
        }

        return $default;
    }

    private function executeCommand(array $command, string $input = null): string {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ],
            $pipes
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to create process');
        }

        if ($input !== null) {
            fwrite($pipes[0], $input);
        }
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException("Command failed with exit code $exitCode: $error");
        }

        return trim($output);
    }

    private function commandExists(string $command): bool {
        $testCommand = PHP_OS_FAMILY === 'Windows' ? "where $command" : "which $command";
        return shell_exec($testCommand) !== null;
    }
}




