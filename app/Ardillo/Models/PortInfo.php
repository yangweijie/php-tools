<?php

namespace App\Ardillo\Models;

/**
 * Model for port information data
 */
class PortInfo extends BaseModel
{
    protected array $validationRules = [
        'port' => ['required', 'string'],
        'pid' => ['required', 'string'],
        'protocol' => ['required', 'string'],
        'localAddress' => ['required', 'string'],
        'remoteAddress' => ['string'],
        'state' => ['string'],
        'processName' => ['string'],
        'commandLine' => ['string'],
    ];

    public function __construct(
        string $port,
        string $pid,
        string $protocol,
        string $localAddress,
        string $remoteAddress = '',
        string $state = '',
        string $processName = '',
        string $commandLine = ''
    ) {
        $this->data = [
            'port' => $port,
            'pid' => $pid,
            'protocol' => $protocol,
            'localAddress' => $localAddress,
            'remoteAddress' => $remoteAddress,
            'state' => $state,
            'processName' => $processName,
            'commandLine' => $commandLine,
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
     * Get the port number
     */
    public function getPort(): string
    {
        return $this->data['port'];
    }

    /**
     * Get the process ID
     */
    public function getPid(): string
    {
        return $this->data['pid'];
    }

    /**
     * Get the protocol (TCP/UDP)
     */
    public function getProtocol(): string
    {
        return $this->data['protocol'];
    }

    /**
     * Get the local address
     */
    public function getLocalAddress(): string
    {
        return $this->data['localAddress'];
    }

    /**
     * Get the remote address
     */
    public function getRemoteAddress(): string
    {
        return $this->data['remoteAddress'];
    }

    /**
     * Get the connection state
     */
    public function getState(): string
    {
        return $this->data['state'];
    }

    /**
     * Get the process name
     */
    public function getProcessName(): string
    {
        return $this->data['processName'];
    }

    /**
     * Get the command line
     */
    public function getCommandLine(): string
    {
        return $this->data['commandLine'];
    }

    /**
     * Validate port number format
     */
    public function isValidPort(): bool
    {
        $port = $this->getPort();
        if (!is_numeric($port)) {
            return false;
        }
        $portNum = (int) $port;
        return $portNum >= 1 && $portNum <= 65535;
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
            $data['port'] ?? '',
            $data['pid'] ?? '',
            $data['protocol'] ?? '',
            $data['localAddress'] ?? '',
            $data['remoteAddress'] ?? '',
            $data['state'] ?? '',
            $data['processName'] ?? '',
            $data['commandLine'] ?? ''
        );
    }

    /**
     * Create PortInfo from parsed command output
     */
    public static function fromCommandOutput(array $parsedData): static
    {
        return new static(
            $parsedData['port'] ?? '',
            $parsedData['pid'] ?? '',
            $parsedData['protocol'] ?? '',
            $parsedData['localAddress'] ?? '',
            $parsedData['remoteAddress'] ?? '',
            $parsedData['state'] ?? '',
            $parsedData['processName'] ?? '',
            $parsedData['commandLine'] ?? ''
        );
    }
}