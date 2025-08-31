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
        // 检查是否在PHAR中运行
        if (strpos(__DIR__, 'phar://') === 0) {
            // 在PHAR中运行，需要提取动态库到临时目录
            return self::extractLibFileFromPhar();
        }
        
        // 判断当前系统是windows还是linux
        if (PHP_OS_FAMILY === 'Windows') {
            // 返回 Windows 系统下的 libui 动态链接库文件路径
            return dirname(__DIR__) . '/lib/windows/libui.dll';
        } else if (PHP_OS_FAMILY === 'Linux') {
            // 返回 Linux 系统下的 libui 共享库文件路径
            return dirname(__DIR__) . '/lib/linux/libui.so';
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // 根据架构选择正确的libui.dylib文件
            if (php_uname('m') === 'x86_64') {
                // Intel架构使用x64版本
                return dirname(__DIR__) . '/lib/macos/libui.dylib(x64)';
            } else {
                // ARM架构使用默认版本
                return dirname(__DIR__) . '/lib/macos/libui.dylib';
            }
        } else {
            // 若当前操作系统不被支持，抛出异常
            throw new \RuntimeException("Unsupported operating系统: " . PHP_OS_FAMILY . ": " . PHP_OS . "");
        }
    }
    
    /**
     * 从PHAR中提取动态库文件到临时目录
     *
     * @return string 提取后的动态库文件路径
     */
    protected static function extractLibFileFromPhar(): string
    {
        // 创建临时目录
        $tempDir = sys_get_temp_dir() . '/libui_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // 根据操作系统确定库文件路径和名称
        if (PHP_OS_FAMILY === 'Windows') {
            $libFile = 'lib/windows/libui.dll';
            $extractedFile = $tempDir . '/libui.dll';
        } else if (PHP_OS_FAMILY === 'Linux') {
            $libFile = 'lib/linux/libui.so';
            $extractedFile = $tempDir . '/libui.so';
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // 根据架构选择正确的libui.dylib文件
            if (php_uname('m') === 'x86_64') {
                // Intel架构使用x64版本
                $libFile = 'lib/macos/libui.dylib(x64)';
                $extractedFile = $tempDir . '/libui.dylib';
            } else {
                // ARM架构使用默认版本
                $libFile = 'lib/macos/libui.dylib';
                $extractedFile = $tempDir . '/libui.dylib';
            }
        } else {
            throw new \RuntimeException("Unsupported operating system: " . PHP_OS_FAMILY . ": " . PHP_OS . "");
        }
        
        // 尝试从PHAR中提取库文件
        // 使用更简单的路径构造方法
        $pharLibPath = str_replace('src', $libFile, __DIR__);
        if (file_exists($pharLibPath)) {
            copy($pharLibPath, $extractedFile);
            return $extractedFile;
        }
        
        // 如果在PHAR中找不到，尝试其他可能的路径
        $alternativePaths = [
            dirname(dirname(__DIR__)) . '/' . $libFile,  // PHAR根目录
            dirname(dirname(dirname(__DIR__))) . '/vendor/kingbes/libui/' . $libFile,  // 原始路径
        ];
        
        foreach ($alternativePaths as $path) {
            if (file_exists($path)) {
                copy($path, $extractedFile);
                return $extractedFile;
            }
        }
        
        // 调试信息
        throw new \RuntimeException("Cannot find libui library file. Looking for: " . $libFile . ", Current path: " . __DIR__ . ", PHAR lib path: " . $pharLibPath);
    }
}
