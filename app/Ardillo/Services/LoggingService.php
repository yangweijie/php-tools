<?php

namespace App\Ardillo\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Simple logging service for the application
 */
class LoggingService implements LoggerInterface
{
    private string $logFile;
    private string $minLevel;
    private array $levelPriority = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];

    public function __construct(string $logFile = null, string $minLevel = LogLevel::INFO)
    {
        $this->logFile = $logFile ?? sys_get_temp_dir() . '/ardillo_app.log';
        $this->minLevel = $minLevel;
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * System is unusable.
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Check if we should log this level
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelStr = strtoupper($level);
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        
        $logEntry = "[{$timestamp}] {$levelStr}: {$message}{$contextStr}" . PHP_EOL;
        
        // Write to file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to stderr for errors and above
        if ($this->levelPriority[$level] >= $this->levelPriority[LogLevel::ERROR]) {
            fwrite(STDERR, $logEntry);
        }
    }

    /**
     * Check if we should log this level
     */
    private function shouldLog(string $level): bool
    {
        return $this->levelPriority[$level] >= $this->levelPriority[$this->minLevel];
    }

    /**
     * Get the log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * Clear the log file
     */
    public function clearLog(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }
}