<?php

use App\Ardillo\Managers\PortManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Exceptions\SystemCommandException;

describe('PortManager', function () {
    beforeEach(function () {
        // Create mock services
        $this->mockSystemCommandService = Mockery::mock(SystemCommandService::class);
        $this->mockDataFormatterService = Mockery::mock(DataFormatterService::class);
        
        // Create PortManager instance with mocked dependencies
        $this->portManager = new PortManager(
            $this->mockSystemCommandService,
            $this->mockDataFormatterService
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('initialization', function () {
        it('can be instantiated with dependencies', function () {
            expect($this->portManager)->toBeInstanceOf(PortManager::class);
        });

        it('has correct display name', function () {
            expect($this->portManager->getDisplayName())->toBe('Port Manager');
        });

        it('has table columns defined', function () {
            $columns = $this->portManager->getTableColumns();
            expect($columns)->toBeArray();
            expect($columns)->not->toBeEmpty();
            
            // Check that checkbox column is first
            expect($columns[0]['key'])->toBe('checkbox');
            expect($columns[0]['type'])->toBe('checkbox');
            
            // Check for required columns
            $columnKeys = array_column($columns, 'key');
            expect($columnKeys)->toContain('port');
            expect($columnKeys)->toContain('pid');
            expect($columnKeys)->toContain('protocol');
            expect($columnKeys)->toContain('localAddress');
            expect($columnKeys)->toContain('remoteAddress');
            expect($columnKeys)->toContain('state');
            expect($columnKeys)->toContain('processName');
        });
    });

    describe('input validation', function () {
        it('validates empty input as valid (query all ports)', function () {
            expect($this->portManager->validateInput(''))->toBeTrue();
            expect($this->portManager->validateInput('   '))->toBeTrue();
        });

        it('validates numeric port numbers', function () {
            expect($this->portManager->validateInput('80'))->toBeTrue();
            expect($this->portManager->validateInput('8080'))->toBeTrue();
            expect($this->portManager->validateInput('65535'))->toBeTrue();
        });

        it('rejects invalid port numbers', function () {
            expect($this->portManager->validateInput('abc'))->toBeFalse();
            expect($this->portManager->validateInput('0'))->toBeFalse();
            expect($this->portManager->validateInput('65536'))->toBeFalse();
            expect($this->portManager->validateInput('-1'))->toBeFalse();
        });
    });

    describe('port number validation', function () {
        it('validates port number ranges correctly', function () {
            expect($this->portManager->validatePortNumber('1'))->toBe('1');
            expect($this->portManager->validatePortNumber('80'))->toBe('80');
            expect($this->portManager->validatePortNumber('8080'))->toBe('8080');
            expect($this->portManager->validatePortNumber('65535'))->toBe('65535');
        });

        it('returns null for empty input', function () {
            expect($this->portManager->validatePortNumber(''))->toBeNull();
            expect($this->portManager->validatePortNumber('   '))->toBeNull();
        });

        it('throws exception for invalid port numbers', function () {
            expect(fn() => $this->portManager->validatePortNumber('0'))
                ->toThrow(InvalidArgumentException::class);
            expect(fn() => $this->portManager->validatePortNumber('65536'))
                ->toThrow(InvalidArgumentException::class);
            expect(fn() => $this->portManager->validatePortNumber('abc'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('PID validation', function () {
        it('validates numeric PIDs', function () {
            expect($this->portManager->validatePid('1'))->toBeTrue();
            expect($this->portManager->validatePid('1234'))->toBeTrue();
            expect($this->portManager->validatePid('99999'))->toBeTrue();
        });

        it('rejects invalid PIDs', function () {
            expect($this->portManager->validatePid('0'))->toBeFalse();
            expect($this->portManager->validatePid('-1'))->toBeFalse();
            expect($this->portManager->validatePid('abc'))->toBeFalse();
            expect($this->portManager->validatePid(''))->toBeFalse();
        });
    });

    describe('port querying', function () {
        it('queries all ports when no input provided', function () {
            $mockCommandResult = [
                'command' => 'netstat -ano',
                'output' => ['line1', 'line2'],
                'return_code' => 0,
                'raw_output' => "line1\nline2"
            ];

            $mockPortInfo = new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx');
            $mockFormattedPorts = [$mockPortInfo];

            $this->mockSystemCommandService
                ->shouldReceive('queryPorts')
                ->with(null)
                ->once()
                ->andReturn($mockCommandResult);

            $this->mockDataFormatterService
                ->shouldReceive('formatPortData')
                ->with($mockCommandResult['output'])
                ->once()
                ->andReturn($mockFormattedPorts);

            $result = $this->portManager->query('');
            
            expect($result)->toBe($mockFormattedPorts);
        });

        it('queries specific port when port number provided', function () {
            $mockCommandResult = [
                'command' => 'netstat -ano | findstr ":8080"',
                'output' => ['line1'],
                'return_code' => 0,
                'raw_output' => "line1"
            ];

            $mockPortInfo = new PortInfo('8080', '5678', 'TCP', '0.0.0.0:8080', '', 'LISTEN', 'apache');
            $mockFormattedPorts = [$mockPortInfo];

            $this->mockSystemCommandService
                ->shouldReceive('queryPorts')
                ->with('8080')
                ->once()
                ->andReturn($mockCommandResult);

            $this->mockDataFormatterService
                ->shouldReceive('formatPortData')
                ->with($mockCommandResult['output'])
                ->once()
                ->andReturn($mockFormattedPorts);

            $result = $this->portManager->query('8080');
            
            expect($result)->toBe($mockFormattedPorts);
        });

        it('throws exception when system command fails', function () {
            $this->mockSystemCommandService
                ->shouldReceive('queryPorts')
                ->andThrow(new SystemCommandException('Command failed'));

            expect(fn() => $this->portManager->query('80'))
                ->toThrow(SystemCommandException::class);
        });

        it('throws exception for invalid input', function () {
            expect(fn() => $this->portManager->query('invalid'))
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

            $result = $this->portManager->killSelected($selectedPids);
            
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

            $result = $this->portManager->killSelected($selectedPids);
            
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

            $result = $this->portManager->killSelected($selectedPids);
            
            expect($result['summary']['total'])->toBe(2);
            expect($result['summary']['success'])->toBe(1);
            expect($result['summary']['failed'])->toBe(1);
            expect($result['results'][0]['success'])->toBeFalse();
            expect($result['results'][0]['message'])->toBe('Invalid PID format');
        });

        it('returns failure message when no processes selected', function () {
            $result = $this->portManager->killSelected([]);
            
            expect($result['success'])->toBeFalse();
            expect($result['message'])->toBe('No processes selected for killing');
            expect($result['results'])->toBe([]);
        });

        it('handles all failures correctly', function () {
            $selectedPids = ['1234', '5678'];
            
            $this->mockSystemCommandService
                ->shouldReceive('killProcess')
                ->andThrow(new SystemCommandException('Permission denied'));

            $result = $this->portManager->killSelected($selectedPids);
            
            expect($result['success'])->toBeFalse();
            expect($result['summary']['success'])->toBe(0);
            expect($result['summary']['failed'])->toBe(2);
            expect($result['message'])->toContain('Failed to kill 2 process(es)');
        });
    });

    describe('table data formatting', function () {
        it('formats port data for table display', function () {
            $portInfo = new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '192.168.1.1:12345', 'ESTAB', 'nginx');
            $ports = [$portInfo];
            
            $result = $this->portManager->getFormattedTableData($ports);
            
            expect($result)->toHaveCount(1);
            expect($result[0])->toHaveKeys([
                'id', 'port', 'pid', 'protocol', 'localAddress', 
                'remoteAddress', 'state', 'processName'
            ]);
            expect($result[0]['id'])->toBe('1234');
            expect($result[0]['port'])->toBe('80');
            expect($result[0]['pid'])->toBe('1234');
            expect($result[0]['protocol'])->toBe('TCP');
        });

        it('handles missing optional data with defaults', function () {
            $portInfo = new PortInfo('80', '1234', 'TCP', '0.0.0.0:80');
            $ports = [$portInfo];
            
            $result = $this->portManager->getFormattedTableData($ports);
            
            expect($result[0]['remoteAddress'])->toBe('-');
            expect($result[0]['state'])->toBe('-');
            expect($result[0]['processName'])->toBe('Unknown');
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

            expect($this->portManager->isReady())->toBeTrue();
        });

        it('reports not ready when system command service is unavailable', function () {
            $this->mockSystemCommandService
                ->shouldReceive('isAvailable')
                ->andReturn(false);
                
            $this->mockDataFormatterService
                ->shouldReceive('isAvailable')
                ->andReturn(true);

            expect($this->portManager->isReady())->toBeFalse();
        });

        it('reports not ready when data formatter service is unavailable', function () {
            $this->mockSystemCommandService
                ->shouldReceive('isAvailable')
                ->andReturn(true);
                
            $this->mockDataFormatterService
                ->shouldReceive('isAvailable')
                ->andReturn(false);

            expect($this->portManager->isReady())->toBeFalse();
        });
    });

    describe('system information', function () {
        it('provides system information for debugging', function () {
            $this->mockSystemCommandService
                ->shouldReceive('getOperatingSystem')
                ->andReturn('linux');
                
            $this->mockSystemCommandService
                ->shouldReceive('getCommandBuilders')
                ->andReturn(['port_query' => 'ss -tulpn']);
                
            $this->mockSystemCommandService
                ->shouldReceive('isAvailable')
                ->andReturn(true);
                
            $this->mockDataFormatterService
                ->shouldReceive('isAvailable')
                ->andReturn(true);

            $info = $this->portManager->getSystemInfo();
            
            expect($info)->toHaveKeys(['operating_system', 'available_commands', 'service_ready']);
            expect($info['operating_system'])->toBe('linux');
            expect($info['service_ready'])->toBeTrue();
        });
    });

    describe('query options', function () {
        it('provides available query options', function () {
            $options = $this->portManager->getQueryOptions();
            
            expect($options)->toBeArray();
            expect($options)->toHaveKeys([
                'all_ports', 'specific_port', 'listening_only', 'established_only'
            ]);
            expect($options['all_ports'])->toBeString();
            expect($options['specific_port'])->toBeString();
        });
    });
});