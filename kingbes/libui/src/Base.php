<?php

// 严格模式
declare(strict_types=1);

namespace Kingbes\Libui;

abstract class Base
{
    // private \FFI $ffi;
    private static \FFI $ffi;

    /**
     * 获取 FFI 实例
     *
     * @return \FFI
     * @throws RuntimeException Missing libui dependencies.
     */
    public static function ffi(): \FFI
    {
        if (!isset(self::$ffi)) {
            $headerPath = __DIR__ . '/Libui.h';
            $dllPath = self::getLibFilePath();
            $libHeader = file_get_contents($headerPath);
            self::$ffi = \FFI::cdef($libHeader, $dllPath);
        }
        return self::$ffi;
    }

    /**
     * 获取 libui 库文件的路径
     *
     * 此方法根据当前操作系统的类型返回相应的 libui 库文件路径。
     * 支持 Windows 和 Linux 操作系统，若使用其他操作系统将抛出异常。
     *
     * @return string 包含 libui 库文件的完整路径
     * @throws \RuntimeException 如果当前操作系统不被支持
     */
    protected static function getLibFilePath(): string
    {
        // 检查是否在 PHAR 环境中运行
        $inPhar = defined('PATH_SEPARATOR') && strpos(__DIR__, 'phar://') === 0;

        if ($inPhar) {
            // 在 PHAR 环境中，尝试从系统路径加载库文件
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows 系统
                return dirname(__DIR__) . '/lib/windows/libui.dll';
            } else if (PHP_OS_FAMILY === 'Linux') {
                // Linux 系统
                return dirname(__DIR__) . '/lib/linux/libui.so';
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                // macOS 系统
                // 检查系统架构
                $arch = trim(shell_exec('uname -m'));
                $isARM = $arch === 'arm64';

                // 优先尝试从系统临时目录加载
                $tempDir = sys_get_temp_dir();
                $tempLibPath = $tempDir . '/libui.dylib';

                // 如果临时目录中有正确的库文件，使用它
                if (file_exists($tempLibPath)) {
                    $expectedMd5 = '46722841c0b859c10745df15e647be1f';
                    $currentMd5 = md5_file($tempLibPath);
                    if ($currentMd5 === $expectedMd5) {
                        return $tempLibPath;
                    }
                }

                // 否则返回默认路径
                return dirname(__DIR__) . '/lib/macos/libui.dylib';
            }
        } else {
            // 不在 PHAR 环境中，使用原来的逻辑
            if (PHP_OS_FAMILY === 'Windows') {
                // 返回 Windows 系统下的 libui 动态链接库文件路径
                return dirname(__DIR__) . '/lib/windows/libui.dll';
            } else if (PHP_OS_FAMILY === 'Linux') {
                // 返回 Linux 系统下的 libui 共享库文件路径
                return dirname(__DIR__) . '/lib/linux/libui.so';
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                // 返回 macOS 系统下的 libui 共享库文件路径
                return dirname(__DIR__) . '/lib/macos/libui.dylib';
            } else {
                // 若当前操作系统不被支持，抛出异常
                throw new \RuntimeException("Unsupported operating system: " . PHP_OS_FAMILY . ": " . PHP_OS . "");
            }
        }
    }
}
