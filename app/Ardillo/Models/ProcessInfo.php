<?php

namespace App\Ardillo\Models;

/**
 * Model for process information data
 */
class ProcessInfo extends BaseModel
{
    protected array $validationRules = [
        'pid' => ['required', 'string'],
        'name' => ['required', 'string'],
        'user' => ['string'],
        'cpuUsage' => ['string'],
        'memoryUsage' => ['string'],
        'commandLine' => ['string'],
        'status' => ['string'],
    ];

    public function __construct(
        string $pid,
        string $name,
        string $user = '',
        string $cpuUsage = '',
        string $memoryUsage = '',
        string $commandLine = '',
        string $status = ''
    ) {
        $this->data = [
            'pid' => $pid,
            'name' => $name,
            'user' => $user,
            'cpuUsage' => $cpuUsage,
            'memoryUsage' => $memoryUsage,
            'commandLine' => $commandLine,
            'status' => $status,
        ];
    }

    /**
     * Get the unique identifier for this model (using PID as identifier)
     */
    public function getId(): string
    {
        return $this->data['pid'];
    }

    /**
     * Get the process ID
     */
    public function getPid(): string
    {
        return $this->data['pid'];
    }

    /**
     * Get the process name
     */
    public function getName(): string
    {
        return $this->data['name'];
    }

    /**
     * Get the user/owner
     */
    public function getUser(): string
    {
        return $this->data['user'];
    }

    /**
     * Get the CPU usage
     */
    public function getCpuUsage(): string
    {
        return $this->data['cpuUsage'];
    }

    /**
     * Get the memory usage
     */
    public function getMemoryUsage(): string
    {
        return $this->data['memoryUsage'];
    }

    /**
     * Get the command line
     */
    public function getCommandLine(): string
    {
        return $this->data['commandLine'];
    }

    /**
     * Get the process status
     */
    public function getStatus(): string
    {
        return $this->data['status'];
    }

    /**
     * Validate PID format
     */
    public function isValidPid(): bool
    {
        $pid = $this->getPid();
        return is_numeric($pid) && (int) $pid > 0;
    }

    /**
     * Create model from array data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            $data['pid'] ?? '',
            $data['name'] ?? '',
            $data['user'] ?? '',
            $data['cpuUsage'] ?? '',
            $data['memoryUsage'] ?? '',
            $data['commandLine'] ?? '',
            $data['status'] ?? ''
        );
    }

    /**
     * Create ProcessInfo from parsed command output
     */
    public static function fromCommandOutput(array $parsedData): static
    {
        return new static(
            $parsedData['pid'] ?? '',
            $parsedData['name'] ?? '',
            $parsedData['user'] ?? '',
            $parsedData['cpuUsage'] ?? '',
            $parsedData['memoryUsage'] ?? '',
            $parsedData['commandLine'] ?? '',
            $parsedData['status'] ?? ''
        );
    }

    /**
     * Check if this process is a system process
     */
    public function isSystemProcess(): bool
    {
        $systemProcesses = ['System', 'kernel_task', 'launchd', 'init'];
        return in_array($this->getName(), $systemProcesses, true);
    }

    /**
     * Get formatted memory usage
     */
    public function getFormattedMemoryUsage(): string
    {
        $memory = $this->getMemoryUsage();
        if (empty($memory) || !is_numeric($memory)) {
            return $memory;
        }

        $bytes = (int) $memory;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}