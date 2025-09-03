<?php

namespace App\Ardillo\Managers;

use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Exceptions\SystemCommandException;
use App\Ardillo\Exceptions\ProcessKillException;
use App\Ardillo\Exceptions\DataValidationException;
use App\Ardillo\Exceptions\PermissionException;

/**
 * Manager for port-related operations including querying and killing port processes
 */
class PortManager extends BaseManager
{
    private SystemCommandService $systemCommandService;
    private DataFormatterService $dataFormatterService;

    public function __construct(
        SystemCommandService $systemCommandService,
        DataFormatterService $dataFormatterService
    ) {
        $this->systemCommandService = $systemCommandService;
        $this->dataFormatterService = $dataFormatterService;
        $this->displayName = 'Port Manager';
        $this->initializeTableColumns();
    }

    /**
     * Query for ports based on input criteria
     */
    public function query(string $input): array
    {
        if (!$this->validateInput($input)) {
            throw new DataValidationException(
                "Invalid port input: {$input}",
                0,
                null,
                ['input' => $input, 'manager' => 'PortManager']
            );
        }

        try {
            // Validate port number if provided
            $portNumber = $this->validatePortNumber($input);
            
            // Execute port query command
            $commandResult = $this->systemCommandService->queryPorts($portNumber);
            
            // Format the raw output into structured data
            $formattedPorts = $this->dataFormatterService->formatPortData($commandResult['output']);
            
            return $formattedPorts;
        } catch (SystemCommandException $e) {
            throw new SystemCommandException(
                "Failed to query ports: " . $e->getMessage(),
                $e->getCode(),
                $e,
                ['input' => $input, 'port_number' => $portNumber ?? null]
            );
        } catch (\InvalidArgumentException $e) {
            throw new DataValidationException(
                "Invalid port number format: " . $e->getMessage(),
                0,
                $e,
                ['input' => $input]
            );
        } catch (\Exception $e) {
            throw new SystemCommandException(
                "Unexpected error during port query: " . $e->getMessage(),
                0,
                $e,
                ['input' => $input, 'operation' => 'query_ports']
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

                // Execute kill command
                $killResult = $this->systemCommandService->killProcess($pid);
                
                $results[] = [
                    'pid' => $pid,
                    'success' => true,
                    'message' => 'Process killed successfully'
                ];
                $successCount++;
                
            } catch (SystemCommandException $e) {
                // Check if it's a permission error
                if (strpos(strtolower($e->getMessage()), 'permission') !== false ||
                    strpos(strtolower($e->getMessage()), 'access') !== false) {
                    $results[] = [
                        'pid' => $pid,
                        'success' => false,
                        'message' => 'Permission denied - run as administrator'
                    ];
                } else {
                    $results[] = [
                        'pid' => $pid,
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
                $failureCount++;
            } catch (ProcessKillException $e) {
                $results[] = [
                    'pid' => $pid,
                    'success' => false,
                    'message' => $e->getUserMessage()
                ];
                $failureCount++;
            } catch (\Exception $e) {
                $results[] = [
                    'pid' => $pid,
                    'success' => false,
                    'message' => 'Unexpected error: ' . $e->getMessage()
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
        
        // Allow empty input for querying all ports
        if ($input === '') {
            return true;
        }
        
        // Validate port number format
        try {
            $this->validatePortNumber($input);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Validate port number format and range
     */
    public function validatePortNumber(string $input): ?string
    {
        $input = trim($input);
        
        // Allow empty input for querying all ports
        if ($input === '') {
            return null;
        }
        
        // Check if input is numeric
        if (!is_numeric($input)) {
            throw new \InvalidArgumentException("Port number must be numeric: {$input}");
        }
        
        $portNumber = (int) $input;
        
        // Validate port range (1-65535)
        if ($portNumber < 1 || $portNumber > 65535) {
            throw new \InvalidArgumentException("Port number must be between 1 and 65535: {$portNumber}");
        }
        
        return (string) $portNumber;
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
     * Initialize table column definitions for port data display
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
                'key' => 'port',
                'label' => 'Port',
                'width' => 80,
                'type' => 'text',
                'sortable' => true,
                'align' => 'right'
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
                'key' => 'protocol',
                'label' => 'Protocol',
                'width' => 80,
                'type' => 'text',
                'sortable' => true,
                'align' => 'center'
            ],
            [
                'key' => 'localAddress',
                'label' => 'Local Address',
                'width' => 200,
                'type' => 'text',
                'sortable' => true,
                'align' => 'left'
            ],
            [
                'key' => 'remoteAddress',
                'label' => 'Remote Address',
                'width' => 200,
                'type' => 'text',
                'sortable' => true,
                'align' => 'left'
            ],
            [
                'key' => 'state',
                'label' => 'State',
                'width' => 120,
                'type' => 'text',
                'sortable' => true,
                'align' => 'center'
            ],
            [
                'key' => 'processName',
                'label' => 'Process Name',
                'width' => 150,
                'type' => 'text',
                'sortable' => true,
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
        return $this->dataFormatterService->formatPortData($outputLines);
    }

    /**
     * Execute system command and return output
     */
    protected function executeCommand(string $command): string
    {
        $result = $this->systemCommandService->queryPorts();
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
     * Get port data formatted for table display
     */
    public function getFormattedTableData(array $ports): array
    {
        return array_map(function (PortInfo $port) {
            return [
                'id' => $port->getPid(), // Use PID as unique identifier
                'port' => $port->getPort(),
                'pid' => $port->getPid(),
                'protocol' => $port->getProtocol(),
                'localAddress' => $port->getLocalAddress(),
                'remoteAddress' => $port->getRemoteAddress() ?: '-',
                'state' => $port->getState() ?: '-',
                'processName' => $port->getProcessName() ?: 'Unknown'
            ];
        }, $ports);
    }

    /**
     * Get available port query options
     */
    public function getQueryOptions(): array
    {
        return [
            'all_ports' => 'Query all active ports',
            'specific_port' => 'Query specific port number',
            'listening_only' => 'Query listening ports only',
            'established_only' => 'Query established connections only'
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
}