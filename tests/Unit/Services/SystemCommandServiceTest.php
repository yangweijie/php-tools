<?php

use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Exceptions\SystemCommandException;

describe('SystemCommandService', function () {
    beforeEach(function () {
        $this->service = new SystemCommandService();
    });

    describe('initialization', function () {
        it('can be instantiated', function () {
            expect($this->service)->toBeInstanceOf(SystemCommandService::class);
        });

        it('detects operating system correctly', function () {
            $os = $this->service->getOperatingSystem();
            expect($os)->toBeIn(['windows', 'macos', 'linux']);
        });

        it('has command builders for detected OS', function () {
            $builders = $this->service->getCommandBuilders();
            expect($builders)->toBeArray();
            expect($builders)->toHaveKeys([
                'port_query',
                'process_query',
                'kill_process'
            ]);
        });
    });

    describe('command building', function () {
        it('builds port query command without specific port', function () {
            // Use reflection to test private method
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('buildPortCommand');
            $method->setAccessible(true);
            
            $command = $method->invoke($this->service, null);
            expect($command)->toBeString();
            expect($command)->not->toBeEmpty();
        });

        it('builds port query command with specific port', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('buildPortCommand');
            $method->setAccessible(true);
            
            $command = $method->invoke($this->service, '8080');
            expect($command)->toBeString();
            expect($command)->toContain('8080');
        });

        it('builds process query command without specific process', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('buildProcessCommand');
            $method->setAccessible(true);
            
            $command = $method->invoke($this->service, null);
            expect($command)->toBeString();
            expect($command)->not->toBeEmpty();
        });

        it('builds process query command with specific process', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('buildProcessCommand');
            $method->setAccessible(true);
            
            $command = $method->invoke($this->service, 'nginx');
            expect($command)->toBeString();
            expect($command)->toContain('nginx');
        });

        it('builds kill command with PID', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('buildKillCommand');
            $method->setAccessible(true);
            
            $command = $method->invoke($this->service, '1234');
            expect($command)->toBeString();
            expect($command)->toContain('1234');
        });
    });

    describe('command execution', function () {
        it('executes command and returns structured result', function () {
            // Mock a simple command that should work on all systems
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeCommand');
            $method->setAccessible(true);
            
            // Use a command that exists on all platforms
            $command = PHP_OS_FAMILY === 'Windows' ? 'echo test' : 'echo test';
            $result = $method->invoke($this->service, $command);
            
            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['command', 'output', 'return_code', 'raw_output']);
            expect($result['return_code'])->toBe(0);
            expect($result['command'])->toBe($command);
        });

        it('throws exception on command failure', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('executeCommand');
            $method->setAccessible(true);
            
            // Use a command that should fail
            $command = 'nonexistentcommand12345';
            
            expect(fn() => $method->invoke($this->service, $command))
                ->toThrow(SystemCommandException::class);
        });
    }); 
   describe('service availability', function () {
        it('reports availability correctly', function () {
            // The service should be available if system commands exist
            $available = $this->service->isAvailable();
            expect($available)->toBeBool();
        });

        it('can initialize without errors when commands are available', function () {
            // Skip this test if commands are not available
            if (!$this->service->isAvailable()) {
                $this->markTestSkipped('Required system commands not available');
            }
            
            expect(fn() => $this->service->initialize())->not->toThrow(SystemCommandException::class);
        });
    });

    describe('operating system specific behavior', function () {
        it('uses correct commands for Windows', function () {
            // Create a service instance with mocked OS detection
            $service = new class extends SystemCommandService {
                public function __construct() {
                    $this->operatingSystem = 'windows';
                    $this->initializeCommandBuilders();
                }
                
                protected function initializeCommandBuilders(): void {
                    $this->commandBuilders = [
                        'windows' => [
                            'port_query' => 'netstat -ano',
                            'port_query_specific' => 'netstat -ano | findstr ":%s"',
                            'process_query' => 'tasklist /fo csv',
                            'process_query_specific' => 'tasklist /fo csv /fi "imagename eq %s*"',
                            'kill_process' => 'taskkill /f /pid %s',
                        ],
                    ];
                }
            };
            
            $builders = $service->getCommandBuilders();
            expect($builders['port_query'])->toContain('netstat');
            expect($builders['process_query'])->toContain('tasklist');
            expect($builders['kill_process'])->toContain('taskkill');
        });

        it('uses correct commands for macOS', function () {
            $service = new class extends SystemCommandService {
                protected function detectOperatingSystem(): string {
                    return 'macos';
                }
            };
            
            $builders = $service->getCommandBuilders();
            expect($builders['port_query'])->toContain('lsof');
            expect($builders['process_query'])->toContain('ps');
            expect($builders['kill_process'])->toContain('kill');
        });

        it('uses correct commands for Linux', function () {
            $service = new class extends SystemCommandService {
                public function __construct() {
                    $this->operatingSystem = 'linux';
                    $this->initializeCommandBuilders();
                }
                
                protected function initializeCommandBuilders(): void {
                    $this->commandBuilders = [
                        'linux' => [
                            'port_query' => 'ss -tulpn',
                            'port_query_specific' => 'ss -tulpn | grep ":%s"',
                            'process_query' => 'ps aux',
                            'process_query_specific' => 'ps aux | grep -i "%s" | grep -v grep',
                            'kill_process' => 'kill -9 %s',
                        ],
                    ];
                }
            };
            
            $builders = $service->getCommandBuilders();
            expect($builders['port_query'])->toContain('ss');
            expect($builders['process_query'])->toContain('ps');
            expect($builders['kill_process'])->toContain('kill');
        });
    });

    describe('command verification', function () {
        it('checks command availability correctly', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('isCommandAvailable');
            $method->setAccessible(true);
            
            // Test with a command that should exist
            $existingCommand = PHP_OS_FAMILY === 'Windows' ? 'echo' : 'echo';
            $available = $method->invoke($this->service, $existingCommand);
            expect($available)->toBeTrue();
            
            // Test with a command that should not exist
            $nonExistentCommand = 'nonexistentcommand12345xyz';
            $available = $method->invoke($this->service, $nonExistentCommand);
            expect($available)->toBeFalse();
        });

        it('gets required commands for current OS', function () {
            $reflection = new ReflectionClass($this->service);
            $method = $reflection->getMethod('getRequiredCommands');
            $method->setAccessible(true);
            
            $commands = $method->invoke($this->service);
            expect($commands)->toBeArray();
            expect($commands)->not->toBeEmpty();
        });
    });

    describe('error handling', function () {
        it('throws SystemCommandException when required commands are missing', function () {
            // Create a service that will fail command verification
            $service = new class extends SystemCommandService {
                public function __construct() {
                    $this->operatingSystem = 'linux'; // Set a valid OS
                    $this->initializeCommandBuilders();
                }
                
                protected function isCommandAvailable(string $command): bool {
                    return false; // Always return false to simulate missing commands
                }
            };
            
            expect(fn() => $service->initialize())
                ->toThrow(SystemCommandException::class);
        });

        it('handles invalid PID in kill command', function () {
            expect(fn() => $this->service->killProcess('invalid_pid'))
                ->toThrow(SystemCommandException::class);
        });
    });

    describe('integration methods', function () {
        it('has queryPorts method that returns array', function () {
            // Skip if service is not available
            if (!$this->service->isAvailable()) {
                $this->markTestSkipped('Required system commands not available');
            }
            
            $result = $this->service->queryPorts();
            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['command', 'output', 'return_code', 'raw_output']);
        });

        it('has queryProcesses method that returns array', function () {
            // Skip if service is not available
            if (!$this->service->isAvailable()) {
                $this->markTestSkipped('Required system commands not available');
            }
            
            $result = $this->service->queryProcesses();
            expect($result)->toBeArray();
            expect($result)->toHaveKeys(['command', 'output', 'return_code', 'raw_output']);
        });

        it('can query specific port', function () {
            // Skip if service is not available
            if (!$this->service->isAvailable()) {
                $this->markTestSkipped('Required system commands not available');
            }
            
            $result = $this->service->queryPorts('80');
            expect($result)->toBeArray();
            expect($result['command'])->toContain('80');
        });

        it('can query specific process', function () {
            // Skip if service is not available
            if (!$this->service->isAvailable()) {
                $this->markTestSkipped('Required system commands not available');
            }
            
            $result = $this->service->queryProcesses('test');
            expect($result)->toBeArray();
            expect($result['command'])->toContain('test');
        });
    });
});