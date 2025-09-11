<?php

namespace App;

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
        $os = \App\App::getOperatingSystem();
        
        try {
            if ($os === 'WIN') {
                // Windows系统
                $result = self::copyWindows($text);
                if (!$result) {
                    self::$lastError = "无法复制到剪贴板，请确保系统支持clip命令";
                }
                return $result;
            } elseif ($os === 'DAR') {
                // macOS系统
                $result = self::copyMacOS($text);
                if (!$result) {
                    // 提供安装提示
                    if (!self::commandExists('xclip') && !self::commandExists('pbcopy')) {
                        self::$lastError = "无法复制到剪贴板。请安装xclip工具:\n brew install xclip\n\n或者确保系统支持pbcopy命令";
                    } else {
                        self::$lastError = "无法复制到剪贴板，请检查xclip或pbcopy命令是否正常工作";
                    }
                }
                return $result;
            } elseif ($os === 'LIN') {
                // Linux系统
                $result = self::copyLinux($text);
                if (!$result) {
                    // 提供安装提示
                    if (!self::commandExists('xclip') && !self::commandExists('xsel')) {
                        self::$lastError = "无法复制到剪贴板。请安装xclip或xsel工具:\n Ubuntu/Debian: sudo apt-get install xclip 或 sudo apt-get install xsel\n CentOS/RHEL: sudo yum install xclip 或 sudo yum install xsel\n Fedora: sudo dnf install xclip 或 sudo dnf install xsel";
                    } else {
                        self::$lastError = "无法复制到剪贴板，请检查xclip或xsel命令是否正常工作";
                    }
                }
                return $result;
            } else {
                // 其他Unix-like系统尝试Linux方式
                $result = self::copyLinux($text);
                if (!$result) {
                    self::$lastError = "无法复制到剪贴板，请安装xclip或xsel工具";
                }
                return $result;
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            self::$lastError = "复制失败: " . $e->getMessage();
            // 如果任何方法失败，返回false
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
    
    // 存储最后的错误信息
    private static $lastError = "";
    
    /**
     * Windows系统复制到剪贴板
     *
     * @param string $text 要复制的文本
     * @return bool 是否成功复制
     */
    private static function copyWindows($text)
    {
        // 使用Windows的clip命令
        $process = proc_open(
            'clip',
            [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ],
            $pipes
        );
        
        if (!is_resource($process)) {
            return false;
        }
        
        // 写入文本到stdin
        fwrite($pipes[0], $text);
        fclose($pipes[0]);
        
        // 等待进程完成并获取返回码
        $exitCode = proc_close($process);
        
        // 如果进程正常退出，返回true
        return $exitCode === 0;
    }
    
    /**
     * macOS系统复制到剪贴板
     *
     * @param string $text 要复制的文本
     * @return bool 是否成功复制
     */
    private static function copyMacOS($text)
    {
        // 优先使用xclip（如果已安装）
        if (self::commandExists('xclip')) {
            $process = proc_open(
                'xclip -selection clipboard',
                [
                    0 => ['pipe', 'r'],  // stdin
                    1 => ['pipe', 'w'],  // stdout
                    2 => ['pipe', 'w']   // stderr
                ],
                $pipes
            );
            
            if (!is_resource($process)) {
                return false;
            }
            
            // 写入文本到stdin
            fwrite($pipes[0], $text);
            fclose($pipes[0]);
            
            // 等待进程完成并获取返回码
            $exitCode = proc_close($process);
            
            // 如果进程正常退出，返回true
            return $exitCode === 0;
        }
        
        // 使用macOS的pbcopy命令
        $process = proc_open(
            'pbcopy',
            [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w']   // stderr
            ],
            $pipes
        );
        
        if (!is_resource($process)) {
            return false;
        }
        
        // 写入文本到stdin
        fwrite($pipes[0], $text);
        fclose($pipes[0]);
        
        // 等待进程完成并获取返回码
        $exitCode = proc_close($process);
        
        // 如果进程正常退出，返回true
        return $exitCode === 0;
    }
    
    /**
     * Linux系统复制到剪贴板
     *
     * @param string $text 要复制的文本
     * @return bool 是否成功复制
     */
    private static function copyLinux($text)
    {
        // 检查是否有xclip工具
        if (self::commandExists('xclip')) {
            $process = proc_open(
                'xclip -selection clipboard',
                [
                    0 => ['pipe', 'r'],  // stdin
                    1 => ['pipe', 'w'],  // stdout
                    2 => ['pipe', 'w']   // stderr
                ],
                $pipes
            );
            
            if (!is_resource($process)) {
                return false;
            }
            
            // 写入文本到stdin
            fwrite($pipes[0], $text);
            fclose($pipes[0]);
            
            // 等待进程完成并获取返回码
            $exitCode = proc_close($process);
            
            // 如果进程正常退出，返回true
            return $exitCode === 0;
        }
        
        // 检查是否有xsel工具
        if (self::commandExists('xsel')) {
            $process = proc_open(
                'xsel --clipboard --input',
                [
                    0 => ['pipe', 'r'],  // stdin
                    1 => ['pipe', 'w'],  // stdout
                    2 => ['pipe', 'w']   // stderr
                ],
                $pipes
            );
            
            if (!is_resource($process)) {
                return false;
            }
            
            // 写入文本到stdin
            fwrite($pipes[0], $text);
            fclose($pipes[0]);
            
            // 等待进程完成并获取返回码
            $exitCode = proc_close($process);
            
            // 如果进程正常退出，返回true
            return $exitCode === 0;
        }
        
        // 如果没有可用的工具，返回false
        return false;
    }
    
    /**
     * 检查命令是否存在
     *
     * @param string $command 命令名称
     * @return bool 命令是否存在
     */
    private static function commandExists($command)
    {
        $os = strtolower(PHP_OS);
        
        if (strpos($os, 'win') !== false) {
            // Windows系统
            $result = shell_exec("where $command 2>nul");
            return !empty($result);
        } else {
            // Unix-like系统
            $result = shell_exec("which $command 2>/dev/null");
            return !empty($result);
        }
    }
}