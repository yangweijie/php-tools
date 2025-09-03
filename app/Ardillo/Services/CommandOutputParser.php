<?php

namespace App\Ardillo\Services;

use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Exceptions\SystemCommandException;

/**
 * Service for parsing system command output into structured data
 */
class CommandOutputParser
{
    private string $operatingSystem;

    public function __construct(string $operatingSystem)
    {
        $this->operatingSystem = $operatingSystem;
    }

    /**
     * Parse port command output into PortInfo objects
     */
    public function parsePortOutput(array $outputLines): array
    {
        switch ($this->operatingSystem) {
            case 'windows':
                return $this->parseWindowsNetstatOutput($outputLines);
            case 'macos':
                return $this->parseMacOSLsofOutput($outputLines);
            case 'linux':
                return $this->parseLinuxSsOutput($outputLines);
            default:
                throw new SystemCommandException("Unsupported operating system: {$this->operatingSystem}");
        }
    }

    /**
     * Parse process command output into ProcessInfo objects
     */
    public function parseProcessOutput(array $outputLines): array
    {
        switch ($this->operatingSystem) {
            case 'windows':
                return $this->parseWindowsTasklistOutput($outputLines);
            case 'macos':
            case 'linux':
                return $this->parseUnixPsOutput($outputLines);
            default:
                throw new SystemCommandException("Unsupported operating system: {$this->operatingSystem}");
        }
    }

    /**
     * Parse Windows netstat output
     * Format: Proto  Local Address          Foreign Address        State           PID
     */
    private function parseWindowsNetstatOutput(array $lines): array
    {
        $ports = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'Proto') === 0) {
                continue;
            }
            
            // Parse netstat line: TCP    0.0.0.0:80             0.0.0.0:0              LISTENING       1234
            // UDP lines don't have state: UDP    0.0.0.0:53             *:*                                    9012
            if (preg_match('/^\s*(\w+)\s+([^\s]+)\s+([^\s]+)(?:\s+(\w+))?\s+(\d+)/', $line, $matches)) {
                $localAddress = $matches[2];
                $portMatch = [];
                
                if (preg_match('/:(\d+)$/', $localAddress, $portMatch)) {
                    $state = $matches[4] ?? '';
                    $pid = count($matches) > 5 ? $matches[5] : $matches[4];
                    
                    // For UDP, there's no state column, so PID is in position 4
                    if (strtolower($matches[1]) === 'udp' && !is_numeric($matches[4])) {
                        $state = '';
                        $pid = $matches[5] ?? '';
                    }
                    
                    $port = new PortInfo(
                        $portMatch[1],
                        $pid,
                        strtolower($matches[1]),
                        $matches[2],
                        $matches[3],
                        $state,
                        $this->getProcessNameByPid($pid)
                    );
                    
                    $ports[] = $port;
                }
            }
        }
        
        return $ports;
    }

    /**
     * Parse macOS lsof output
     * Format: COMMAND   PID USER   FD   TYPE DEVICE SIZE/OFF NODE NAME
     */
    private function parseMacOSLsofOutput(array $lines): array
    {
        $ports = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'COMMAND') === 0) {
                continue;
            }
            
            $parts = preg_split('/\s+/', $line);
            
            // Look for TCP connections in lsof output
            if (count($parts) >= 8 && strpos($line, 'TCP') !== false) {
                // The NAME field starts at index 7 (TCP) and continues
                $nameStartIndex = 7; // TCP starts at index 7
                $name = implode(' ', array_slice($parts, $nameStartIndex));
                
                // Parse address like TCP *:80 (LISTEN) or TCP 127.0.0.1:3000 (LISTEN)
                if (preg_match('/TCP\s+([^:]+):(\d+)\s*\(LISTEN\)/', $name, $matches)) {
                    $port = new PortInfo(
                        $matches[2], // Port number
                        $parts[1],   // PID
                        'tcp',       // Protocol
                        $matches[1] . ':' . $matches[2], // Local address
                        '',          // Remote address
                        'LISTEN',    // State
                        $parts[0]    // Process name
                    );
                    
                    $ports[] = $port;
                }
            }
        }
        
        return $ports;
    }

    /**
     * Parse Linux ss output
     * Format: State    Recv-Q Send-Q Local Address:Port  Peer Address:Port
     */
    private function parseLinuxSsOutput(array $lines): array
    {
        $ports = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'State') === 0) {
                continue;
            }
            
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 5) {
                $localAddress = $parts[3]; // Local Address:Port is in column 3
                
                if (preg_match('/:(\d+)$/', $localAddress, $matches)) {
                    $pid = '';
                    $processName = '';
                    
                    // Extract PID from process info if available (look in remaining parts)
                    $remainingParts = implode(' ', array_slice($parts, 5));
                    if (preg_match('/pid=(\d+)/', $remainingParts, $pidMatch)) {
                        $pid = $pidMatch[1];
                        // Extract process name from users:(("processname",pid=...))
                        if (preg_match('/\(\("([^"]+)"/', $remainingParts, $nameMatch)) {
                            $processName = $nameMatch[1];
                        }
                    }
                    
                    $port = new PortInfo(
                        $matches[1],        // Port number
                        $pid,               // PID
                        'tcp',              // Protocol (assume TCP for ss output)
                        $localAddress,      // Local address
                        $parts[4] ?? '',    // Peer address
                        $parts[0],          // State
                        $processName        // Process name
                    );
                    
                    $ports[] = $port;
                }
            }
        }
        
        return $ports;
    }    /*
*
     * Parse Windows tasklist output (CSV format)
     */
    private function parseWindowsTasklistOutput(array $lines): array
    {
        $processes = [];
        $headers = [];
        
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Parse CSV line
            $fields = str_getcsv($line);
            
            if ($index === 0) {
                $headers = $fields;
                continue;
            }
            
            if (count($fields) >= 5) {
                $process = new ProcessInfo(
                    $fields[1] ?? '', // PID
                    $fields[0] ?? '', // Name (Image Name)
                    '', // User (not available in tasklist)
                    '', // CPU usage not available in tasklist
                    $fields[4] ?? '', // Memory usage (Mem Usage)
                    $fields[0] ?? '', // Command line (use Image Name)
                    'Running' // Status
                );
                
                $processes[] = $process;
            }
        }
        
        return $processes;
    }

    /**
     * Parse Unix ps output
     * Format: USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND
     */
    private function parseUnixPsOutput(array $lines): array
    {
        $processes = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'USER') === 0 || strpos($line, 'PID') !== false) {
                continue;
            }
            
            $parts = preg_split('/\s+/', $line, 11);
            if (count($parts) >= 11) {
                // Extract process name from command line
                $commandParts = explode(' ', $parts[10]);
                $processName = basename($commandParts[0]);
                
                $process = new ProcessInfo(
                    $parts[1], // PID
                    $processName, // Name
                    $parts[0], // User
                    $parts[2] . '%', // CPU usage
                    $parts[3] . '%', // Memory usage
                    $parts[10], // Command line
                    $parts[7] ?? 'Running' // Status
                );
                
                $processes[] = $process;
            }
        }
        
        return $processes;
    }

    /**
     * Get process name by PID (helper method)
     */
    private function getProcessNameByPid(string $pid): string
    {
        try {
            $command = $this->operatingSystem === 'windows' 
                ? "tasklist /fi \"pid eq {$pid}\" /fo csv /nh"
                : "ps -p {$pid} -o comm=";
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                if ($this->operatingSystem === 'windows') {
                    $fields = str_getcsv($output[0]);
                    return $fields[0] ?? 'Unknown';
                } else {
                    return trim($output[0]);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors and return unknown
        }
        
        return 'Unknown';
    }

    /**
     * Validate parsed data integrity
     */
    public function validatePortData(array $ports): array
    {
        return array_filter($ports, function ($port) {
            return $port instanceof PortInfo 
                && !empty($port->port) 
                && is_numeric($port->port)
                && $port->port > 0 
                && $port->port <= 65535;
        });
    }

    /**
     * Validate parsed process data integrity
     */
    public function validateProcessData(array $processes): array
    {
        return array_filter($processes, function ($process) {
            return $process instanceof ProcessInfo 
                && !empty($process->pid) 
                && is_numeric($process->pid)
                && $process->pid > 0;
        });
    }
}