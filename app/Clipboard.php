<?php

namespace App;

use Exception;
use Kingbes\Libui\SDK\LibuiApplication;

/**
 * 跨平台剪贴板操作类
 */
class Clipboard
{
    /**
     * 将文本复制到剪贴板
     *
     * @param string $text 要复制的文本
     * @return bool 是否成功复制
     */
    public static function copy($text)
    {
        try {
            // 使用SDK中的LibuiApplication来处理剪贴板操作
            $app = LibuiApplication::getInstance();
            $result = $app->copyToClipboard($text);

            if (!$result) {
                self::$lastError = "无法复制到剪贴板，请确保系统支持相应的剪贴板命令";
            }

            return $result;
        } catch (Exception $e) {
            self::$lastError = "复制失败: " . $e->getMessage();
            return false;
        }
    }

    /**
     * 获取最后的错误信息
     *
     * @return string 错误信息
     */
    public static function getLastError()
    {
        return isset(self::$lastError) ? self::$lastError : "";
    }

    /**
     * 从剪贴板获取文本
     *
     * @return string|null 剪贴板中的文本，如果失败则返回null
     */
    public static function getText()
    {
        try {
            // 使用SDK中的LibuiApplication来处理剪贴板操作
            $app = LibuiApplication::getInstance();
            return $app->getFromClipboard();
        } catch (Exception $e) {
            self::$lastError = "读取剪贴板失败: " . $e->getMessage();
            return null;
        }
    }

    /**
     * 清空剪贴板
     *
     * @return bool 是否成功清空
     */
    public static function clear()
    {
        try {
            // 使用SDK中的LibuiApplication来处理剪贴板操作
            $app = LibuiApplication::getInstance();
            return $app->clearClipboard();
        } catch (Exception $e) {
            self::$lastError = "清空剪贴板失败: " . $e->getMessage();
            return false;
        }
    }

    // 存储最后的错误信息
    private static $lastError = "";
}
