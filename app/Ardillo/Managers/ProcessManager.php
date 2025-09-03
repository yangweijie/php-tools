<?php

namespace App\Ardillo\Managers;

use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Exceptions\SystemCommandException;
use App\Ardillo\Exceptions\ProcessKillException;
use App\Ardillo\Exceptions\DataValidationException;
use App\Ardillo\Exceptions\PermissionException;

/**
 * Manager for process-related operations including querying and killing processes
 */
class ProcessManager extends BaseManager
{
    private SystemCommandService $systemCommandService;
    private DataFormatterService $dataFormatterService;

    public function __construct(
        SystemCommandService $systemCommandService,
        DataFormatterService $dataFormatterService
    ) {
        $this->systemCommandService = $systemCommandService;
        $this->dataFormatterService = $dataFormatterService;
        $this->displayName = 'Process Manager';
        $this->initializeTableColumns();
    }

    /**
     * Query for processes based on input criteria
     */
    public function query(string $input): array
    {
        if (!$this->validateInput($input)) {
            throw new DataValidationException(
                "Invalid process input: {$input}",
                0,
                null,
                ['input' => $input, 'manager' => 'ProcessManager']
            );
        }

        try {
            // Validate process name or PID if provided
            $processIdentifier = $this->validateProcessIdentifier($input);
            
            // Execute process query command
            $commandResult = $this->systemCommandService->queryProcesses($processIdentifier);
            
            // Format the raw output into structured data
            $formattedProcesses = $this->dataFormatterService->formatProcessData($commandResult['output']);
            
            return $formattedProcesses;
        } catch (SystemCommandException $e) {
            throw new SystemCommandException(
                "Failed to query processes: " . $e->getMessage(),
                $e->getCode(),
                $e,
                ['input' => $input, 'process_identifier' => $processIdentifier ?? null]
            );
        } catch (\InvalidArgumentException $e) {
            throw new DataValidationException(
                "Invalid process identifier format: " . $e->getMessage(),
                0,
                $e,
                ['input' => $input]
            );
        } catch (\Exception $e) {
            throw new SystemCommandException(
                "Unexpected error during process query: " . $e->getMessage(),
                0,
                $e,
                ['input' => $input, 'operation' => 'query_processes']
            );
        }
    }

    /**
     * Kill selected processes by their PIDs
     */
    public function killSelected(array $selectedIds): array
    {
        if (empty($selectedIds)) {
            return [
                'success' => false,
                'message' => 'No processes selected for killing',
                'results' => []
            ];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($selectedIds as $pid) {
            try {
                // Validate PID format
                if (!$this->validatePid($pid)) {
                    $results[] = [
                        'pid' => $pid,
                        'success' => false,
                        'message' => 'Invalid PID format'
                    ];
                    $failureCount++;
                    continue;
                }

                // Check if it's a system process that shouldn't be killed
                if ($this->isSystemProcess($pid)) {
                    $results[] = [
                        'pid' => $pid,
                        'success' => false,
                        'message' => 'Cannot kill system process'
                    ];
                    $failureCount++;
                    continue;
                }

                // Execute kill command
                $killResult = $this->systemCommandService->killProcess($pid);
                
                $results[] = [
                    'pid' => $pid,
                    'success' => true,
                    'message' => 'Process killed successfully'
                ];
                $successCount++;
                
            } catch (SystemCommandException $e) {
                $results[] = [
                    'pid' => $pid,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        return [
            'success' => $successCount > 0,
            'message' => $this->formatKillResultMessage($successCount, $failureCount),
            'results' => $results,
            'summary' => [
                'total' => count($selectedIds),
                'success' => $successCount,
                'failed' => $failureCount
            ]
        ];
    }

    /**
     * Validate input before processing
     */
    public function validateInput(string $input): bool
    {
        $input = trim($input);
        
        // Allow empty input for querying all processes
        if ($input === '') {
            return true;
        }
        
        // Validate process identifier (name or PID)
        try {
            $this->validateProcessIdentifier($input);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Validate process identifier (name or PID)
     */
    public function validateProcessIdentifier(string $input): ?string
    {
        $input = trim($input);
        
        // Allow empty input for querying all processes
        if ($input === '') {
            return null;
        }
        
        // Check if input is numeric (PID)
        if (is_numeric($input)) {
            $pid = (int) $input;
            if ($pid <= 0) {
                throw new \InvalidArgumentException("PID must be a positive integer: {$pid}");
            }
            return (string) $pid;
        }
        
        // Validate process name format
        if (!$this->validateProcessName($input)) {
            throw new \InvalidArgumentException("Invalid process name format: {$input}");
        }
        
        return $input;
    }

    /**
     * Validate process name format
     */
    public function validateProcessName(string $processName): bool
    {
        $processName = trim($processName);
        
        // Process name should not be empty
        if ($processName === '') {
            return false;
        }
        
        // Process name should not contain invalid characters
        if (preg_match('/[<>:"|?*]/', $processName)) {
            return false;
        }
        
        // Process name should not be too long
        if (strlen($processName) > 255) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate PID format
     */
    public function validatePid(string $pid): bool
    {
        $pid = trim($pid);
        return is_numeric($pid) && (int) $pid > 0;
    }

    /**
     * Check if a PID belongs to a system process that shouldn't be killed
     */
    public function isSystemProcess(string $pid): bool
    {
        $pidInt = (int) $pid;
        
        // PIDs 0-10 are typically system processes
        if ($pidInt <= 10) {
            return true;
        }
        
        // Additional system process checks could be added here
        // For now, we'll be conservative and only protect very low PIDs
        
        return false;
    }

    /**
     * Initialize table column definitions for process data display
     */
    private function initializeTableColumns(): void
    {
        $this->tableColumns = [
            [
                'key' => 'checkbox',
                'label' => '',
                'width' => 40,
                'type' => 'checkbox',
                'sortable' => false
            ],
            [
                'key' => 'pid',
                'label' => 'PID',
                'width' => 80,
                'type' => 'text',
                'sortable' => true,
                'align' => 'right'
            ],
            [
                'key' => 'name',
                'label' => 'Process Name',
                'width' => 200,
                'type' => 'text',
                'sortable' => true,
                'align' => 'left'
            ],
            [
                'key' => 'user',
                'label' => 'User',
                'width' => 120,
                'type' => 'text',
                'sortable' => true,
                'align' => 'left'
            ],
            [
                'key' => 'cpuUsage',
                'label' => 'CPU %',
                'width' => 80,
                'type' => 'text',
                'sortable' => true,
                'align' => 'right'
            ],
            [
                'key' => 'memoryUsage',
                'label' => 'Memory',
                'width' => 120,
                'type' => 'text',
                'sortable' => true,
                'align' => 'right'
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'width' => 100,
                'type' => 'text',
                'sortable' => true,
                'align' => 'center'
            ],
            [
                'key' => 'commandLine',
                'label' => 'Command Line',
                'width' => 300,
                'type' => 'text',
                'sortable' => false,
                'align' => 'left'
            ]
        ];
    }

    /**
     * Format raw command output into structured data
     */
    protected function formatData(string $rawOutput): array
    {
        $outputLines = explode("\n", $rawOutput);
        return $this->dataFormatterService->formatProcessData($outputLines);
    }

    /**
     * Execute system command and return output
     */
    protected function executeCommand(string $command): string
    {
        $result = $this->systemCommandService->queryProcesses();
        return $result['raw_output'];
    }

    /**
     * Format kill operation result message
     */
    private function formatKillResultMessage(int $successCount, int $failureCount): string
    {
        if ($successCount === 0 && $failureCount > 0) {
            return "Failed to kill {$failureCount} process(es)";
        }
        
        if ($successCount > 0 && $failureCount === 0) {
            return "Successfully killed {$successCount} process(es)";
        }
        
        if ($successCount > 0 && $failureCount > 0) {
            return "Killed {$successCount} process(es), failed to kill {$failureCount} process(es)";
        }
        
        return "No processes were processed";
    }

    /**
     * Get process data formatted for table display
     */
    public function getFormattedTableData(array $processes): array
    {
        return array_map(function (ProcessInfo $process) {
            return [
                'id' => $process->getPid(), // Use PID as unique identifier
                'pid' => $process->getPid(),
                'name' => $process->getName(),
                'user' => $process->getUser() ?: '-',
                'cpuUsage' => $process->getCpuUsage() ?: '-',
                'memoryUsage' => $process->getMemoryUsage() ?: '-',
                'status' => $process->getStatus() ?: 'Unknown',
                'commandLine' => $this->truncateCommandLine($process->getCommandLine())
            ];
        }, $processes);
    }

    /**
     * Truncate command line for display
     */
    private function truncateCommandLine(string $commandLine): string
    {
        if (strlen($commandLine) > 50) {
            return substr($commandLine, 0, 47) . '...';
        }
        return $commandLine;
    }

    /**
     * Get available process query options
     */
    public function getQueryOptions(): array
    {
        return [
            'all_processes' => 'Query all running processes',
            'specific_process' => 'Query specific process by name',
            'specific_pid' => 'Query specific process by PID',
            'user_processes' => 'Query processes for current user only'
        ];
    }

    /**
     * Check if the manager is ready for operations
     */
    public function isReady(): bool
    {
        return $this->systemCommandService->isAvailable() && 
               $this->dataFormatterService->isAvailable();
    }

    /**
     * Get system information for debugging
     */
    public function getSystemInfo(): array
    {
        return [
            'operating_system' => $this->systemCommandService->getOperatingSystem(),
            'available_commands' => $this->systemCommandService->getCommandBuilders(),
            'service_ready' => $this->isReady()
        ];
    }

    /**
     * Get processes filtered by user
     */
    public function getProcessesByUser(string $username): array
    {
        try {
            $allProcesses = $this->query('');
            
            $filtered = array_filter($allProcesses, function (ProcessInfo $process) use ($username) {
                return $process->getUser() === $username;
            });
            
            return array_values($filtered); // Re-index array
        } catch (SystemCommandException $e) {
            throw new SystemCommandException(
                "Failed to query processes by user: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get processes with high CPU usage
     */
    public function getHighCpuProcesses(float $threshold = 10.0): array
    {
        try {
            $allProcesses = $this->query('');
            
            $filtered = array_filter($allProcesses, function (ProcessInfo $process) use ($threshold) {
                $cpuUsage = $process->getCpuUsage();
                if (empty($cpuUsage)) {
                    return false;
                }
                
                // Extract numeric value from CPU usage string
                $numericCpu = (float) preg_replace('/[^0-9.]/', '', $cpuUsage);
                return $numericCpu >= $threshold;
            });
            
            return array_values($filtered); // Re-index array
        } catch (SystemCommandException $e) {
            throw new SystemCommandException(
                "Failed to query high CPU processes: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get processes with high memory usage
     */
    public function getHighMemoryProcesses(string $threshold = '100MB'): array
    {
        try {
            $allProcesses = $this->query('');
            
            $filtered = array_filter($allProcesses, function (ProcessInfo $process) use ($threshold) {
                $memoryUsage = $process->getMemoryUsage();
                if (empty($memoryUsage)) {
                    return false;
                }
                
                // This is a simplified check - could be enhanced with proper memory parsing
                return strpos($memoryUsage, 'GB') !== false || 
                       (strpos($memoryUsage, 'MB') !== false && 
                        (float) preg_replace('/[^0-9.]/', '', $memoryUsage) >= 100);
            });
            
            return array_values($filtered); // Re-index array
        } catch (SystemCommandException $e) {
            throw new SystemCommandException(
                "Failed to query high memory processes: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}