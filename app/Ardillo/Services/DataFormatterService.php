<?php

namespace App\Ardillo\Services;

use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Exceptions\SystemCommandException;

/**
 * Service for formatting and normalizing raw command output into structured data
 * Provides cross-platform data normalization and validation
 */
class DataFormatterService implements ServiceInterface
{
    private CommandOutputParser $parser;
    private string $operatingSystem;
    private bool $initialized = false;

    public function __construct(string $operatingSystem = null)
    {
        $this->operatingSystem = $operatingSystem ?? $this->detectOperatingSystem();
        $this->parser = new CommandOutputParser($this->operatingSystem);
    }

    /**
     * Initialize the service
     */
    public function initialize(): void
    {
        if (!$this->isOperatingSystemSupported($this->operatingSystem)) {
            throw new SystemCommandException("Unsupported operating system: {$this->operatingSystem}");
        }
        $this->initialized = true;
    }

    /**
     * Check if the service is available and ready
     */
    public function isAvailable(): bool
    {
        return $this->initialized && $this->isOperatingSystemSupported($this->operatingSystem);
    }

    /**
     * Format raw port command output into normalized PortInfo objects
     */
    public function formatPortData(array $rawOutput): array
    {
        if (!$this->isAvailable()) {
            throw new SystemCommandException('DataFormatterService is not available');
        }

        // Parse the raw output using the command output parser
        $parsedPorts = $this->parser->parsePortOutput($rawOutput);
        
        // Normalize and format the data
        $formattedPorts = array_map([$this, 'normalizePortData'], $parsedPorts);
        
        // Validate data integrity
        $validatedPorts = $this->parser->validatePortData($formattedPorts);
        
        // Sort by port number for consistent display
        usort($validatedPorts, function (PortInfo $a, PortInfo $b) {
            return (int)$a->getPort() <=> (int)$b->getPort();
        });
        
        return $validatedPorts;
    }

    /**
     * Format raw process command output into normalized ProcessInfo objects
     */
    public function formatProcessData(array $rawOutput): array
    {
        if (!$this->isAvailable()) {
            throw new SystemCommandException('DataFormatterService is not available');
        }

        // Parse the raw output using the command output parser
        $parsedProcesses = $this->parser->parseProcessOutput($rawOutput);
        
        // Normalize and format the data
        $formattedProcesses = array_map([$this, 'normalizeProcessData'], $parsedProcesses);
        
        // Validate data integrity
        $validatedProcesses = $this->parser->validateProcessData($formattedProcesses);
        
        // Sort by PID for consistent display
        usort($validatedProcesses, function (ProcessInfo $a, ProcessInfo $b) {
            return (int)$a->getPid() <=> (int)$b->getPid();
        });
        
        return $validatedProcesses;
    }

    /**
     * Normalize port data across different operating systems
     */
    private function normalizePortData(PortInfo $port): PortInfo
    {
        return new PortInfo(
            $this->normalizePort($port->getPort()),
            $this->normalizePid($port->getPid()),
            $this->normalizeProtocol($port->getProtocol()),
            $this->normalizeAddress($port->getLocalAddress()),
            $this->normalizeAddress($port->getRemoteAddress()),
            $this->normalizeState($port->getState()),
            $this->normalizeProcessName($port->getProcessName()),
            $this->normalizeCommandLine($port->getCommandLine())
        );
    }

    /**
     * Normalize process data across different operating systems
     */
    private function normalizeProcessData(ProcessInfo $process): ProcessInfo
    {
        return new ProcessInfo(
            $this->normalizePid($process->getPid()),
            $this->normalizeProcessName($process->getName()),
            $this->normalizeUser($process->getUser()),
            $this->normalizeCpuUsage($process->getCpuUsage()),
            $this->normalizeMemoryUsage($process->getMemoryUsage()),
            $this->normalizeCommandLine($process->getCommandLine()),
            $this->normalizeProcessStatus($process->getStatus())
        );
    }

    /**
     * Normalize port number format
     */
    private function normalizePort(string $port): string
    {
        $port = trim($port);
        if (is_numeric($port)) {
            $portNum = (int)$port;
            return $portNum >= 1 && $portNum <= 65535 ? (string)$portNum : $port;
        }
        return $port;
    }

    /**
     * Normalize PID format
     */
    private function normalizePid(string $pid): string
    {
        $pid = trim($pid);
        return is_numeric($pid) ? (string)(int)$pid : $pid;
    }

    /**
     * Normalize protocol format (uppercase)
     */
    private function normalizeProtocol(string $protocol): string
    {
        return strtoupper(trim($protocol));
    }

    /**
     * Normalize address format
     */
    private function normalizeAddress(string $address): string
    {
        $address = trim($address);
        
        // Handle different representations of "any" address
        if (in_array($address, ['0.0.0.0:0', '*:*', '0.0.0.0', '*'], true)) {
            return '*';
        }
        
        // Handle IPv6 addresses
        if (strpos($address, '::') !== false) {
            return $address; // Keep IPv6 as-is for now
        }
        
        return $address;
    }

    /**
     * Normalize connection state
     */
    private function normalizeState(string $state): string
    {
        $state = strtoupper(trim($state));
        
        // Normalize common state variations
        $stateMap = [
            'LISTENING' => 'LISTEN',
            'ESTABLISHED' => 'ESTAB',
            'TIME_WAIT' => 'TIME-WAIT',
            'CLOSE_WAIT' => 'CLOSE-WAIT',
            'FIN_WAIT1' => 'FIN-WAIT-1',
            'FIN_WAIT2' => 'FIN-WAIT-2',
            'SYN_SENT' => 'SYN-SENT',
            'SYN_RECV' => 'SYN-RECV',
        ];
        
        return $stateMap[$state] ?? $state;
    }

    /**
     * Normalize process name
     */
    private function normalizeProcessName(string $processName): string
    {
        $processName = trim($processName);
        
        // Extract basename from full paths first
        if (strpos($processName, '/') !== false || strpos($processName, '\\') !== false) {
            // Handle both Unix and Windows path separators
            $processName = preg_replace('/.*[\/\\\\]/', '', $processName);
        }
        
        // Remove file extensions on Windows
        if ($this->operatingSystem === 'windows' && str_ends_with($processName, '.exe')) {
            $processName = substr($processName, 0, -4);
        }
        
        return $processName;
    }

    /**
     * Normalize user/owner format
     */
    private function normalizeUser(string $user): string
    {
        return trim($user);
    }

    /**
     * Normalize CPU usage format
     */
    private function normalizeCpuUsage(string $cpuUsage): string
    {
        $cpuUsage = trim($cpuUsage);
        
        // Ensure percentage sign is present
        if (is_numeric($cpuUsage)) {
            return $cpuUsage . '%';
        }
        
        // Remove extra spaces around percentage
        if (str_contains($cpuUsage, '%')) {
            return preg_replace('/\s*%\s*/', '%', $cpuUsage);
        }
        
        return $cpuUsage;
    }

    /**
     * Normalize memory usage format
     */
    private function normalizeMemoryUsage(string $memoryUsage): string
    {
        $memoryUsage = trim($memoryUsage);
        
        // Handle Windows format (e.g., "1,024 K")
        if (preg_match('/^([\d,]+)\s*([KMGT])\s*$/', $memoryUsage, $matches)) {
            $number = str_replace(',', '', $matches[1]);
            $unit = $matches[2];
            return $number . ' ' . $unit . 'B';
        }
        
        // Handle percentage format
        if (is_numeric($memoryUsage)) {
            return $memoryUsage . '%';
        }
        
        // Ensure percentage sign is present for percentage values
        if (str_contains($memoryUsage, '%')) {
            return preg_replace('/\s*%\s*/', '%', $memoryUsage);
        }
        
        return $memoryUsage;
    }

    /**
     * Normalize command line format
     */
    private function normalizeCommandLine(string $commandLine): string
    {
        $commandLine = trim($commandLine);
        
        // Truncate very long command lines for display
        if (strlen($commandLine) > 100) {
            $commandLine = substr($commandLine, 0, 97) . '...';
        }
        
        return $commandLine;
    }

    /**
     * Normalize process status
     */
    private function normalizeProcessStatus(string $status): string
    {
        $status = trim($status);
        
        // Normalize common status variations
        $statusMap = [
            'R' => 'Running',
            'S' => 'Sleeping',
            'D' => 'Waiting',
            'Z' => 'Zombie',
            'T' => 'Stopped',
            'I' => 'Idle',
            'Ss' => 'Sleeping (session leader)',
            'S+' => 'Sleeping (foreground)',
            'R+' => 'Running (foreground)',
        ];
        
        return $statusMap[$status] ?? $status;
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
                return 'linux'; // Default to Linux for Unix-like systems
        }
    }

    /**
     * Check if the operating system is supported
     */
    private function isOperatingSystemSupported(string $os): bool
    {
        return in_array($os, ['windows', 'macos', 'linux'], true);
    }

    /**
     * Get the current operating system
     */
    public function getOperatingSystem(): string
    {
        return $this->operatingSystem;
    }

    /**
     * Get supported operating systems
     */
    public function getSupportedOperatingSystems(): array
    {
        return ['windows', 'macos', 'linux'];
    }

    /**
     * Format data for table display with consistent column widths
     */
    public function formatForTableDisplay(array $data, string $type = 'port'): array
    {
        if ($type === 'port') {
            return $this->formatPortsForTable($data);
        } elseif ($type === 'process') {
            return $this->formatProcessesForTable($data);
        }
        
        return $data;
    }

    /**
     * Format port data for table display
     */
    private function formatPortsForTable(array $ports): array
    {
        return array_map(function (PortInfo $port) {
            return [
                'port' => str_pad($port->getPort(), 6, ' ', STR_PAD_LEFT),
                'pid' => str_pad($port->getPid(), 8, ' ', STR_PAD_LEFT),
                'protocol' => str_pad($port->getProtocol(), 8, ' ', STR_PAD_RIGHT),
                'localAddress' => str_pad($port->getLocalAddress(), 20, ' ', STR_PAD_RIGHT),
                'remoteAddress' => str_pad($port->getRemoteAddress() ?: '-', 20, ' ', STR_PAD_RIGHT),
                'state' => str_pad($port->getState() ?: '-', 12, ' ', STR_PAD_RIGHT),
                'processName' => $port->getProcessName() ?: 'Unknown',
            ];
        }, $ports);
    }

    /**
     * Format process data for table display
     */
    private function formatProcessesForTable(array $processes): array
    {
        return array_map(function (ProcessInfo $process) {
            return [
                'pid' => str_pad($process->getPid(), 8, ' ', STR_PAD_LEFT),
                'name' => str_pad($process->getName(), 20, ' ', STR_PAD_RIGHT),
                'user' => str_pad($process->getUser() ?: '-', 12, ' ', STR_PAD_RIGHT),
                'cpuUsage' => str_pad($process->getCpuUsage() ?: '-', 8, ' ', STR_PAD_LEFT),
                'memoryUsage' => str_pad($process->getMemoryUsage() ?: '-', 12, ' ', STR_PAD_LEFT),
                'status' => $process->getStatus() ?: 'Unknown',
            ];
        }, $processes);
    }
}