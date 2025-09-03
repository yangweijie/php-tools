<?php

namespace App\Ardillo\Services;

use App\Ardillo\Exceptions\SystemCommandException;

/**
 * Service for executing system commands across different operating systems
 */
class SystemCommandService implements ServiceInterface
{
    protected string $operatingSystem;
    protected array $commandBuilders;

    public function __construct()
    {
        $this->operatingSystem = $this->detectOperatingSystem();
        $this->initializeCommandBuilders();
    }

    /**
     * Initialize the service
     */
    public function initialize(): void
    {
        // Verify that required commands are available on the system
        $this->verifySystemCommands();
    }

    /**
     * Check if the service is available and ready
     */
    public function isAvailable(): bool
    {
        try {
            $this->verifySystemCommands();
            return true;
        } catch (SystemCommandException $e) {
            return false;
        }
    }

    /**
     * Execute a port query command
     */
    public function queryPorts(?string $portNumber = null): array
    {
        $command = $this->buildPortCommand($portNumber);
        return $this->executeCommand($command);
    }

    /**
     * Execute a process query command
     */
    public function queryProcesses(?string $processName = null): array
    {
        $command = $this->buildProcessCommand($processName);
        return $this->executeCommand($command);
    }

    /**
     * Kill a process by PID
     */
    public function killProcess(string $pid): array
    {
        $command = $this->buildKillCommand($pid);
        return $this->executeCommand($command);
    }

    /**
     * Detect the current operating system
     */
    private function detectOperatingSystem(): string
    {
        $os = strtolower(PHP_OS_FAMILY);
        
        switch ($os) {
            case 'windows':
                return 'windows';
            case 'darwin':
                return 'macos';
            case 'linux':
                return 'linux';
            default:
                return 'linux'; // Default fallback
        }
    }

    /**
     * Initialize command builders for different operating systems
     */
    protected function initializeCommandBuilders(): void
    {
        $this->commandBuilders = [
            'windows' => [
                'port_query' => 'netstat -ano',
                'port_query_specific' => 'netstat -ano | findstr ":%s"',
                'process_query' => 'tasklist /fo csv',
                'process_query_specific' => 'tasklist /fo csv /fi "imagename eq %s*"',
                'kill_process' => 'taskkill /f /pid %s',
            ],
            'macos' => [
                'port_query' => 'lsof -i -P -n',
                'port_query_specific' => 'lsof -i :%s -P -n',
                'process_query' => 'ps aux',
                'process_query_specific' => 'ps aux | grep -i "%s" | grep -v grep',
                'kill_process' => 'kill -9 %s',
            ],
            'linux' => [
                'port_query' => 'ss -tulpn',
                'port_query_specific' => 'ss -tulpn | grep ":%s"',
                'process_query' => 'ps aux',
                'process_query_specific' => 'ps aux | grep -i "%s" | grep -v grep',
                'kill_process' => 'kill -9 %s',
            ],
        ];
    } 
   /**
     * Build port query command based on operating system
     */
    private function buildPortCommand(?string $portNumber = null): string
    {
        $commands = $this->commandBuilders[$this->operatingSystem];
        
        if ($portNumber !== null) {
            return sprintf($commands['port_query_specific'], $portNumber);
        }
        
        return $commands['port_query'];
    }

    /**
     * Build process query command based on operating system
     */
    private function buildProcessCommand(?string $processName = null): string
    {
        $commands = $this->commandBuilders[$this->operatingSystem];
        
        if ($processName !== null) {
            return sprintf($commands['process_query_specific'], $processName);
        }
        
        return $commands['process_query'];
    }

    /**
     * Build kill command based on operating system
     */
    private function buildKillCommand(string $pid): string
    {
        $commands = $this->commandBuilders[$this->operatingSystem];
        return sprintf($commands['kill_process'], $pid);
    }

    /**
     * Execute a system command and return parsed output
     */
    private function executeCommand(string $command): array
    {
        $output = [];
        $returnCode = 0;
        
        // Execute the command
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new SystemCommandException(
                "Command execution failed: {$command}. Return code: {$returnCode}. Output: " . implode("\n", $output)
            );
        }
        
        return [
            'command' => $command,
            'output' => $output,
            'return_code' => $returnCode,
            'raw_output' => implode("\n", $output),
        ];
    }

    /**
     * Verify that required system commands are available
     */
    private function verifySystemCommands(): void
    {
        $requiredCommands = $this->getRequiredCommands();
        
        foreach ($requiredCommands as $command) {
            if (!$this->isCommandAvailable($command)) {
                throw new SystemCommandException(
                    "Required command '{$command}' is not available on this system"
                );
            }
        }
    }

    /**
     * Get list of required commands for current operating system
     */
    private function getRequiredCommands(): array
    {
        switch ($this->operatingSystem) {
            case 'windows':
                return ['netstat', 'tasklist', 'taskkill'];
            case 'macos':
                return ['lsof', 'ps', 'kill'];
            case 'linux':
                return ['ss', 'ps', 'kill'];
            default:
                return ['ps', 'kill'];
        }
    }

    /**
     * Check if a command is available on the system
     */
    private function isCommandAvailable(string $command): bool
    {
        $checkCommand = $this->operatingSystem === 'windows' 
            ? "where {$command} >nul 2>&1"
            : "which {$command} >/dev/null 2>&1";
        
        $returnCode = 0;
        exec($checkCommand, $output, $returnCode);
        
        return $returnCode === 0;
    }

    /**
     * Get the current operating system
     */
    public function getOperatingSystem(): string
    {
        return $this->operatingSystem;
    }

    /**
     * Get available command builders for current OS
     */
    public function getCommandBuilders(): array
    {
        return $this->commandBuilders[$this->operatingSystem] ?? [];
    }
}