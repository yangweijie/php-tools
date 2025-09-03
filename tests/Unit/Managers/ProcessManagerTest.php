<?php

use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Exceptions\SystemCommandException;

describe('ProcessManager', function () {
    beforeEach(function () {
        // Create mock services
        $this->mockSystemCommandService = Mockery::mock(SystemCommandService::class);
        $this->mockDataFormatterService = Mockery::mock(DataFormatterService::class);
        
        // Create ProcessManager instance with mocked dependencies
        $this->processManager = new ProcessManager(
            $this->mockSystemCommandService,
            $this->mockDataFormatterService
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('initialization', function () {
        it('can be instantiated with dependencies', function () {
            expect($this->processManager)->toBeInstanceOf(ProcessManager::class);
        });

        it('has correct display name', function () {
            expect($this->processManager->getDisplayName())->toBe('Process Manager');
        });

        it('has table columns defined', function () {
            $columns = $this->processManager->getTableColumns();
            expect($columns)->toBeArray();
            expect($columns)->not->toBeEmpty();
            
            // Check that checkbox column is first
            expect($columns[0]['key'])->toBe('checkbox');
            expect($columns[0]['type'])->toBe('checkbox');
            
            // Check for required columns
            $columnKeys = array_column($columns, 'key');
            expect($columnKeys)->toContain('pid');
            expect($columnKeys)->toContain('name');
            expect($columnKeys)->toContain('user');
            expect($columnKeys)->toContain('cpuUsage');
            expect($columnKeys)->toContain('memoryUsage');
            expect($columnKeys)->toContain('status');
            expect($columnKeys)->toContain('commandLine');
        });
    });

    describe('input validation', function () {
        it('validates empty input as valid (query all processes)', function () {
            expect($this->processManager->validateInput(''))->toBeTrue();
            expect($this->processManager->validateInput('   '))->toBeTrue();
        });

        it('validates numeric PIDs', function () {
            expect($this->processManager->validateInput('1234'))->toBeTrue();
            expect($this->processManager->validateInput('1'))->toBeTrue();
            expect($this->processManager->validateInput('99999'))->toBeTrue();
        });

        it('validates process names', function () {
            expect($this->processManager->validateInput('nginx'))->toBeTrue();
            expect($this->processManager->validateInput('apache2'))->toBeTrue();
            expect($this->processManager->validateInput('my-process'))->toBeTrue();
        });

        it('rejects invalid input', function () {
            expect($this->processManager->validateInput('0'))->toBeFalse();
            expect($this->processManager->validateInput('-1'))->toBeFalse();
            expect($this->processManager->validateInput('process<name>'))->toBeFalse();
        });
    });

    describe('process identifier validation', function () {
        it('validates numeric PIDs correctly', function () {
            expect($this->processManager->validateProcessIdentifier('1'))->toBe('1');
            expect($this->processManager->validateProcessIdentifier('1234'))->toBe('1234');
            expect($this->processManager->validateProcessIdentifier('99999'))->toBe('99999');
        });

        it('validates process names correctly', function () {
            expect($this->processManager->validateProcessIdentifier('nginx'))->toBe('nginx');
            expect($this->processManager->validateProcessIdentifier('apache2'))->toBe('apache2');
            expect($this->processManager->validateProcessIdentifier('my-process'))->toBe('my-process');
        });

        it('returns null for empty input', function () {
            expect($this->processManager->validateProcessIdentifier(''))->toBeNull();
            expect($this->processManager->validateProcessIdentifier('   '))->toBeNull();
        });

        it('throws exception for invalid PIDs', function () {
            expect(fn() => $this->processManager->validateProcessIdentifier('0'))
                ->toThrow(InvalidArgumentException::class);
            expect(fn() => $this->processManager->validateProcessIdentifier('-1'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for invalid process names', function () {
            expect(fn() => $this->processManager->validateProcessIdentifier('process<name>'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('process name validation', function () {
        it('validates valid process names', function () {
            expect($this->processManager->validateProcessName('nginx'))->toBeTrue();
            expect($this->processManager->validateProcessName('apache2'))->toBeTrue();
            expect($this->processManager->validateProcessName('my-process'))->toBeTrue();
            expect($this->processManager->validateProcessName('process_name'))->toBeTrue();
        });

        it('rejects invalid process names', function () {
            expect($this->processManager->validateProcessName(''))->toBeFalse();
            expect($this->processManager->validateProcessName('process<name>'))->toBeFalse();
            expect($this->processManager->validateProcessName('process>name'))->toBeFalse();
            expect($this->processManager->validateProcessName('process|name'))->toBeFalse();
            expect($this->processManager->validateProcessName(str_repeat('a', 256)))->toBeFalse();
        });
    });

    describe('PID validation', function () {
        it('validates numeric PIDs', function () {
            expect($this->processManager->validatePid('1'))->toBeTrue();
            expect($this->processManager->validatePid('1234'))->toBeTrue();
            expect($this->processManager->validatePid('99999'))->toBeTrue();
        });

        it('rejects invalid PIDs', function () {
            expect($this->processManager->validatePid('0'))->toBeFalse();
            expect($this->processManager->validatePid('-1'))->toBeFalse();
            expect($this->processManager->validatePid('abc'))->toBeFalse();
            expect($this->processManager->validatePid(''))->toBeFalse();
        });
    });

    describe('system process detection', function () {
        it('identifies system processes correctly', function () {
            expect($this->processManager->isSystemProcess('0'))->toBeTrue();
            expect($this->processManager->isSystemProcess('1'))->toBeTrue();
            expect($this->processManager->isSystemProcess('10'))->toBeTrue();
        });

        it('identifies non-system processes correctly', function () {
            expect($this->processManager->isSystemProcess('11'))->toBeFalse();
            expect($this->processManager->isSystemProcess('1234'))->toBeFalse();
            expect($this->processManager->isSystemProcess('99999'))->toBeFalse();
        });
    });

    describe('process querying', function () {
        it('queries all processes when no input provided', function () {
            $mockCommandResult = [
                'command' => 'ps aux',
                'output' => ['line1', 'line2'],
                'return_code' => 0,
                'raw_output' => "line1\nline2"
            ];

            $mockProcessInfo = new ProcessInfo('1234', 'nginx', 'www-data', '1.5%', '50MB', '/usr/sbin/nginx', 'Running');
            $mockFormattedProcesses = [$mockProcessInfo];

            $this->mockSystemCommandService
                ->shouldReceive('queryProcesses')
                ->with(null)
                ->once()
                ->andReturn($mockCommandResult);

            $this->mockDataFormatterService
                ->shouldReceive('formatProcessData')
                ->with($mockCommandResult['output'])
                ->once()
                ->andReturn($mockFormattedProcesses);

            $result = $this->processManager->query('');
            
            expect($result)->toBe($mockFormattedProcesses);
        });

        it('queries specific process when process name provided', function () {
            $mockCommandResult = [
                'command' => 'ps aux | grep -i "nginx" | grep -v grep',
                'output' => ['line1'],
                'return_code' => 0,
                'raw_output' => "line1"
            ];

            $mockProcessInfo = new ProcessInfo('1234', 'nginx', 'www-data', '1.5%', '50MB', '/usr/sbin/nginx', 'Running');
            $mockFormattedProcesses = [$mockProcessInfo];

            $this->mockSystemCommandService
                ->shouldReceive('queryProcesses')
                ->with('nginx')
                ->once()
                ->andReturn($mockCommandResult);

            $this->mockDataFormatterService
                ->shouldReceive('formatProcessData')
                ->with($mockCommandResult['output'])
                ->once()
                ->andReturn($mockFormattedProcesses);

            $result = $this->processManager->query('nginx');
            
            expect($result)->toBe($mockFormattedProcesses);
        });

        it('queries specific process when PID provided', function () {
            $mockCommandResult = [
                'command' => 'ps aux',
                'output' => ['line1'],
                'return_code' => 0,
                'raw_output' => "line1"
            ];

            $mockProcessInfo = new ProcessInfo('1234', 'nginx', 'www-data', '1.5%', '50MB', '/usr/sbin/nginx', 'Running');
            $mockFormattedProcesses = [$mockProcessInfo];

            $this->mockSystemCommandService
                ->shouldReceive('queryProcesses')
                ->with('1234')
                ->once()
                ->andReturn($mockCommandResult);

            $this->mockDataFormatterService
                ->shouldReceive('formatProcessData')
                ->with($mockCommandResult['output'])
                ->once()
                ->andReturn($mockFormattedProcesses);

            $result = $this->processManager->query('1234');
            
            expect($result)->toBe($mockFormattedProcesses);
        });

        it('throws exception when system command fails', function () {
            $this->mockSystemCommandService
                ->shouldReceive('queryProcesses')
                ->andThrow(new SystemCommandException('Command failed'));

            expect(fn() => $this->processManager->query('nginx'))
                ->toThrow(SystemCommandException::class);
        });

        it('throws exception for invalid input', function () {
            expect(fn() => $this->processManager->query('invalid<name>'))
                ->toThrow(\App\Ardillo\Exceptions\DataValidationException::class);
        });
    });

    describe('process killing', function () {
        it('kills selected processes successfully', function () {
            $selectedPids = ['1234', '5678'];
            
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->with('1234')
                ->once()
                ->andReturn(['success' => true]);
                
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->with('5678')
                ->once()
                ->andReturn(['success' => true]);

            $result = $this->processManager->killSelected($selectedPids);
            
            expect($result['success'])->toBeTrue();
            expect($result['summary']['total'])->toBe(2);
            expect($result['summary']['success'])->toBe(2);
            expect($result['summary']['failed'])->toBe(0);
            expect($result['results'])->toHaveCount(2);
        });

        it('handles mixed success and failure results', function () {
            $selectedPids = ['1234', '5678'];
            
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->with('1234')
                ->once()
                ->andReturn(['success' => true]);
                
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->with('5678')
                ->once()
                ->andThrow(new SystemCommandException('Process not found'));

            $result = $this->processManager->killSelected($selectedPids);
            
            expect($result['success'])->toBeTrue(); // At least one success
            expect($result['summary']['total'])->toBe(2);
            expect($result['summary']['success'])->toBe(1);
            expect($result['summary']['failed'])->toBe(1);
            expect($result['results'])->toHaveCount(2);
            expect($result['results'][0]['success'])->toBeTrue();
            expect($result['results'][1]['success'])->toBeFalse();
        });

        it('handles invalid PID format', function () {
            $selectedPids = ['invalid', '1234'];
            
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->with('1234')
                ->once()
                ->andReturn(['success' => true]);

            $result = $this->processManager->killSelected($selectedPids);
            
            expect($result['summary']['total'])->toBe(2);
            expect($result['summary']['success'])->toBe(1);
            expect($result['summary']['failed'])->toBe(1);
            expect($result['results'][0]['success'])->toBeFalse();
            expect($result['results'][0]['message'])->toBe('Invalid PID format');
        });

        it('prevents killing system processes', function () {
            $selectedPids = ['1', '1234']; // PID 1 is typically init process
            
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->with('1234')
                ->once()
                ->andReturn(['success' => true]);

            $result = $this->processManager->killSelected($selectedPids);
            
            expect($result['summary']['total'])->toBe(2);
            expect($result['summary']['success'])->toBe(1);
            expect($result['summary']['failed'])->toBe(1);
            expect($result['results'][0]['success'])->toBeFalse();
            expect($result['results'][0]['message'])->toBe('Cannot kill system process');
        });

        it('returns failure message when no processes selected', function () {
            $result = $this->processManager->killSelected([]);
            
            expect($result['success'])->toBeFalse();
            expect($result['message'])->toBe('No processes selected for killing');
            expect($result['results'])->toBe([]);
        });

        it('handles all failures correctly', function () {
            $selectedPids = ['1234', '5678'];
            
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->andThrow(new SystemCommandException('Permission denied'));

            $result = $this->processManager->killSelected($selectedPids);
            
            expect($result['success'])->toBeFalse();
            expect($result['summary']['success'])->toBe(0);
            expect($result['summary']['failed'])->toBe(2);
            expect($result['message'])->toContain('Failed to kill 2 process(es)');
        });
    });

    describe('table data formatting', function () {
        it('formats process data for table display', function () {
            $processInfo = new ProcessInfo('1234', 'nginx', 'www-data', '1.5%', '50MB', '/usr/sbin/nginx -g daemon off;', 'Running');
            $processes = [$processInfo];
            
            $result = $this->processManager->getFormattedTableData($processes);
            
            expect($result)->toHaveCount(1);
            expect($result[0])->toHaveKeys([
                'id', 'pid', 'name', 'user', 'cpuUsage', 
                'memoryUsage', 'status', 'commandLine'
            ]);
            expect($result[0]['id'])->toBe('1234');
            expect($result[0]['pid'])->toBe('1234');
            expect($result[0]['name'])->toBe('nginx');
            expect($result[0]['user'])->toBe('www-data');
            expect($result[0]['cpuUsage'])->toBe('1.5%');
            expect($result[0]['memoryUsage'])->toBe('50MB');
            expect($result[0]['status'])->toBe('Running');
        });

        it('handles missing optional data with defaults', function () {
            $processInfo = new ProcessInfo('1234', 'nginx');
            $processes = [$processInfo];
            
            $result = $this->processManager->getFormattedTableData($processes);
            
            expect($result[0]['user'])->toBe('-');
            expect($result[0]['cpuUsage'])->toBe('-');
            expect($result[0]['memoryUsage'])->toBe('-');
            expect($result[0]['status'])->toBe('Unknown');
        });

        it('truncates long command lines', function () {
            $longCommand = str_repeat('a', 100);
            $processInfo = new ProcessInfo('1234', 'nginx', 'www-data', '1.5%', '50MB', $longCommand, 'Running');
            $processes = [$processInfo];
            
            $result = $this->processManager->getFormattedTableData($processes);
            
            expect(strlen($result[0]['commandLine']))->toBeLessThanOrEqual(50);
            expect($result[0]['commandLine'])->toEndWith('...');
        });
    });

    describe('service readiness', function () {
        it('reports ready when both services are available', function () {
            $this->mockSystemCommandService
                ->shouldReceive('isAvailable')
                ->andReturn(true);
                
            $this->mockDataFormatterService
                ->shouldReceive('isAvailable')
                ->andReturn(true);

            expect($this->processManager->isReady())->toBeTrue();
        });

        it('reports not ready when system command service is unavailable', function () {
            $this->mockSystemCommandService
                ->shouldReceive('isAvailable')
                ->andReturn(false);
                
            $this->mockDataFormatterService
                ->shouldReceive('isAvailable')
                ->andReturn(true);

            expect($this->processManager->isReady())->toBeFalse();
        });

        it('reports not ready when data formatter service is unavailable', function () {
            $this->mockSystemCommandService
                ->shouldReceive('isAvailable')
                ->andReturn(true);
                
            $this->mockDataFormatterService
                ->shouldReceive('isAvailable')
                ->andReturn(false);

            expect($this->processManager->isReady())->toBeFalse();
        });
    });

    describe('system information', function () {
        it('provides system information for debugging', function () {
            $this->mockSystemCommandService
                ->shouldReceive('getOperatingSystem')
                ->andReturn('linux');
                
            $this->mockSystemCommandService
                ->shouldReceive('getCommandBuilders')
                ->andReturn(['process_query' => 'ps aux']);
                
            $this->mockSystemCommandService
                ->shouldReceive('isAvailable')
                ->andReturn(true);
                
            $this->mockDataFormatterService
                ->shouldReceive('isAvailable')
                ->andReturn(true);

            $info = $this->processManager->getSystemInfo();
            
            expect($info)->toHaveKeys(['operating_system', 'available_commands', 'service_ready']);
            expect($info['operating_system'])->toBe('linux');
            expect($info['service_ready'])->toBeTrue();
        });
    });

    describe('query options', function () {
        it('provides available query options', function () {
            $options = $this->processManager->getQueryOptions();
            
            expect($options)->toBeArray();
            expect($options)->toHaveKeys([
                'all_processes', 'specific_process', 'specific_pid', 'user_processes'
            ]);
            expect($options['all_processes'])->toBeString();
            expect($options['specific_process'])->toBeString();
            expect($options['specific_pid'])->toBeString();
            expect($options['user_processes'])->toBeString();
        });
    });

    describe('filtered queries', function () {
        it('gets processes by user', function () {
            $mockProcesses = [
                new ProcessInfo('1234', 'nginx', 'www-data', '1.5%', '50MB', '/usr/sbin/nginx', 'Running'),
                new ProcessInfo('5678', 'apache', 'www-data', '2.0%', '75MB', '/usr/sbin/apache2', 'Running'),
                new ProcessInfo('9999', 'mysql', 'mysql', '3.0%', '200MB', '/usr/sbin/mysqld', 'Running')
            ];

            $this->mockSystemCommandService
                ->shouldReceive('queryProcesses')
                ->with('')
                ->once()
                ->andReturn(['output' => []]);

            $this->mockDataFormatterService
                ->shouldReceive('formatProcessData')
                ->once()
                ->andReturn($mockProcesses);

            $result = $this->processManager->getProcessesByUser('www-data');
            
            expect($result)->toHaveCount(2);
            expect($result[0]->getUser())->toBe('www-data');
            expect($result[1]->getUser())->toBe('www-data');
        });

        it('gets high CPU processes', function () {
            $mockProcesses = [
                new ProcessInfo('1234', 'nginx', 'www-data', '5.0%', '50MB', '/usr/sbin/nginx', 'Running'),
                new ProcessInfo('5678', 'apache', 'www-data', '15.0%', '75MB', '/usr/sbin/apache2', 'Running'),
                new ProcessInfo('9999', 'mysql', 'mysql', '25.0%', '200MB', '/usr/sbin/mysqld', 'Running')
            ];

            $this->mockSystemCommandService
                ->shouldReceive('queryProcesses')
                ->with('')
                ->once()
                ->andReturn(['output' => []]);

            $this->mockDataFormatterService
                ->shouldReceive('formatProcessData')
                ->once()
                ->andReturn($mockProcesses);

            $result = $this->processManager->getHighCpuProcesses(10.0);
            
            expect($result)->toHaveCount(2);
            expect($result[0]->getName())->toBe('apache');
            expect($result[1]->getName())->toBe('mysql');
        });

        it('gets high memory processes', function () {
            $mockProcesses = [
                new ProcessInfo('1234', 'nginx', 'www-data', '5.0%', '50MB', '/usr/sbin/nginx', 'Running'),
                new ProcessInfo('5678', 'apache', 'www-data', '15.0%', '75MB', '/usr/sbin/apache2', 'Running'),
                new ProcessInfo('9999', 'mysql', 'mysql', '25.0%', '1.5GB', '/usr/sbin/mysqld', 'Running')
            ];

            $this->mockSystemCommandService
                ->shouldReceive('queryProcesses')
                ->with('')
                ->once()
                ->andReturn(['output' => []]);

            $this->mockDataFormatterService
                ->shouldReceive('formatProcessData')
                ->once()
                ->andReturn($mockProcesses);

            $result = $this->processManager->getHighMemoryProcesses('100MB');
            
            expect($result)->toHaveCount(1);
            expect($result[0]->getName())->toBe('mysql');
        });
    });
});