<?php

use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Exceptions\SystemCommandException;

describe('DataFormatterService', function () {
    describe('initialization', function () {
        it('initializes successfully with supported operating system', function () {
            $service = new DataFormatterService('linux');
            $service->initialize();
            
            expect($service->isAvailable())->toBeTrue();
            expect($service->getOperatingSystem())->toBe('linux');
        });

        it('throws exception for unsupported operating system', function () {
            $service = new DataFormatterService('unsupported_os');
            
            expect(fn() => $service->initialize())
                ->toThrow(SystemCommandException::class);
        });

        it('auto-detects operating system when none provided', function () {
            $service = new DataFormatterService();
            $service->initialize();
            
            expect($service->getOperatingSystem())->toBeIn(['windows', 'macos', 'linux']);
            expect($service->isAvailable())->toBeTrue();
        });

        it('returns supported operating systems', function () {
            $service = new DataFormatterService('linux');
            $supported = $service->getSupportedOperatingSystems();
            
            expect($supported)->toBe(['windows', 'macos', 'linux']);
        });
    });

    describe('port data formatting', function () {
        beforeEach(function () {
            $this->service = new DataFormatterService('linux');
            $this->service->initialize();
        });

        it('formats Linux ss output correctly', function () {
            $rawOutput = [
                'State    Recv-Q Send-Q Local Address:Port  Peer Address:Port',
                'LISTEN   0      128    0.0.0.0:22          0.0.0.0:*     users:(("sshd",pid=1234,fd=3))',
                'LISTEN   0      128    0.0.0.0:80          0.0.0.0:*     users:(("nginx",pid=5678,fd=6))',
                'ESTAB    0      0      192.168.1.100:443   192.168.1.1:54321'
            ];

            $formattedPorts = $this->service->formatPortData($rawOutput);
            
            expect($formattedPorts)->toBeArray();
            expect(count($formattedPorts))->toBe(3);
            
            // Check sorting by port number
            expect($formattedPorts[0]->getPort())->toBe('22');
            expect($formattedPorts[1]->getPort())->toBe('80');
            expect($formattedPorts[2]->getPort())->toBe('443');
            
            // Check normalization
            expect($formattedPorts[0]->getProtocol())->toBe('TCP');
            expect($formattedPorts[0]->getState())->toBe('LISTEN');
        });

        it('normalizes port data across different formats', function () {
            // Create test ports with different formats
            $testPorts = [
                new PortInfo('8080', '1234', 'tcp', '127.0.0.1:8080', '0.0.0.0:0', 'listening', 'test.exe'),
                new PortInfo('443', '5678', 'TCP', '*:443', '*:*', 'LISTEN', '/usr/bin/nginx'),
            ];

            // Use reflection to test private normalization methods
            $reflection = new ReflectionClass($this->service);
            $normalizeMethod = $reflection->getMethod('normalizePortData');
            $normalizeMethod->setAccessible(true);

            $normalized1 = $normalizeMethod->invoke($this->service, $testPorts[0]);
            $normalized2 = $normalizeMethod->invoke($this->service, $testPorts[1]);

            expect($normalized1->getProtocol())->toBe('TCP');
            expect($normalized1->getState())->toBe('LISTEN');
            expect($normalized1->getProcessName())->toBe('test.exe'); // .exe kept on Linux

            expect($normalized2->getProcessName())->toBe('nginx'); // path basename extracted
        });

        it('validates and filters invalid port data', function () {
            $rawOutput = [
                'LISTEN   0      128    0.0.0.0:22          0.0.0.0:*',
                'LISTEN   0      128    0.0.0.0:99999       0.0.0.0:*', // Invalid port
                'LISTEN   0      128    0.0.0.0:abc         0.0.0.0:*', // Invalid port
            ];

            $formattedPorts = $this->service->formatPortData($rawOutput);
            
            // Only valid port should remain
            expect(count($formattedPorts))->toBe(1);
            expect($formattedPorts[0]->getPort())->toBe('22');
        });

        it('throws exception when service not available', function () {
            $service = new DataFormatterService('linux');
            // Don't initialize
            
            expect(fn() => $service->formatPortData([]))
                ->toThrow(SystemCommandException::class);
        });
    });

    describe('process data formatting', function () {
        beforeEach(function () {
            $this->service = new DataFormatterService('linux');
            $this->service->initialize();
        });

        it('formats Unix ps output correctly', function () {
            $rawOutput = [
                'USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND',
                'root         1  0.0  0.1   4624  1024 ?        Ss   Jan01   0:01 /sbin/init',
                'www       5678  0.5  2.3  12345  5678 ?        S    10:30   0:05 nginx: worker process',
                'user      1234  1.2  3.4  23456  7890 ?        R+   11:00   0:10 node server.js'
            ];

            $formattedProcesses = $this->service->formatProcessData($rawOutput);
            
            expect($formattedProcesses)->toBeArray();
            expect(count($formattedProcesses))->toBe(3);
            
            // Check sorting by PID
            expect($formattedProcesses[0]->getPid())->toBe('1');
            expect($formattedProcesses[1]->getPid())->toBe('1234');
            expect($formattedProcesses[2]->getPid())->toBe('5678');
            
            // Check normalization
            expect($formattedProcesses[0]->getCpuUsage())->toBe('0.0%');
            expect($formattedProcesses[0]->getMemoryUsage())->toBe('0.1%');
            expect($formattedProcesses[0]->getStatus())->toBe('Sleeping (session leader)'); // Ss normalized
        });

        it('normalizes process data across different formats', function () {
            // Create test processes with different formats
            $testProcesses = [
                new ProcessInfo('1234', 'test.exe', 'user', '1.5', '2.3', '/path/to/test.exe --arg', 'R'),
                new ProcessInfo('5678', '/usr/bin/nginx', 'www', '0.5%', '1,024 K', 'nginx: worker', 'S+'),
            ];

            // Use reflection to test private normalization methods
            $reflection = new ReflectionClass($this->service);
            $normalizeMethod = $reflection->getMethod('normalizeProcessData');
            $normalizeMethod->setAccessible(true);

            $normalized1 = $normalizeMethod->invoke($this->service, $testProcesses[0]);
            $normalized2 = $normalizeMethod->invoke($this->service, $testProcesses[1]);

            expect($normalized1->getName())->toBe('test.exe'); // Keep as-is on Linux
            expect($normalized1->getCpuUsage())->toBe('1.5%'); // Add percentage
            expect($normalized1->getStatus())->toBe('Running'); // Normalize status

            expect($normalized2->getName())->toBe('nginx'); // Extract basename
            expect($normalized2->getMemoryUsage())->toBe('1024 KB'); // Normalize memory format
            expect($normalized2->getStatus())->toBe('Sleeping (foreground)'); // Normalize status
        });

        it('validates and filters invalid process data', function () {
            $rawOutput = [
                'root         1  0.0  0.1   4624  1024 ?        Ss   Jan01   0:01 /sbin/init',
                'user       abc  1.2  3.4  23456  7890 ?        R+   11:00   0:10 invalid', // Invalid PID
                'www        -1   0.5  2.3  12345  5678 ?        S    10:30   0:05 negative', // Invalid PID
            ];

            $formattedProcesses = $this->service->formatProcessData($rawOutput);
            
            // Only valid process should remain
            expect(count($formattedProcesses))->toBe(1);
            expect($formattedProcesses[0]->getPid())->toBe('1');
        });
    });

    describe('Windows-specific formatting', function () {
        beforeEach(function () {
            $this->service = new DataFormatterService('windows');
            $this->service->initialize();
        });

        it('formats Windows netstat output correctly', function () {
            $rawOutput = [
                'Active Connections',
                '',
                '  Proto  Local Address          Foreign Address        State           PID',
                '  TCP    0.0.0.0:80             0.0.0.0:0              LISTENING       1234',
                '  TCP    127.0.0.1:3000         0.0.0.0:0              LISTENING       5678',
                '  UDP    0.0.0.0:53             *:*                                    9012'
            ];

            $formattedPorts = $this->service->formatPortData($rawOutput);
            
            expect($formattedPorts)->toBeArray();
            expect(count($formattedPorts))->toBe(3);
            
            // Check sorting and normalization
            expect($formattedPorts[0]->getPort())->toBe('53');
            expect($formattedPorts[1]->getPort())->toBe('80');
            expect($formattedPorts[2]->getPort())->toBe('3000');
            
            expect($formattedPorts[1]->getState())->toBe('LISTEN'); // Normalized from LISTENING
        });

        it('formats Windows tasklist output correctly', function () {
            $rawOutput = [
                '"Image Name","PID","Session Name","Session#","Mem Usage"',
                '"System","4","Services","0","1,024 K"',
                '"notepad.exe","1234","Console","1","2,048 K"'
            ];

            $formattedProcesses = $this->service->formatProcessData($rawOutput);
            
            expect($formattedProcesses)->toBeArray();
            expect(count($formattedProcesses))->toBe(2);
            
            // Check Windows-specific normalization
            $notepadProcesses = array_filter($formattedProcesses, fn($p) => $p->getPid() === '1234');
            expect(count($notepadProcesses))->toBe(1);
            $notepadProcess = array_values($notepadProcesses)[0];
            expect($notepadProcess->getName())->toBe('notepad'); // .exe removed
            expect($notepadProcess->getMemoryUsage())->toBe('2048 KB'); // Normalized format
        });
    });

    describe('macOS-specific formatting', function () {
        beforeEach(function () {
            $this->service = new DataFormatterService('macos');
            $this->service->initialize();
        });

        it('formats macOS lsof output correctly', function () {
            $rawOutput = [
                'COMMAND   PID USER   FD   TYPE DEVICE SIZE/OFF NODE NAME',
                'launchd     1 root  txt    REG    1,4   162432    2 /sbin/launchd',
                'nginx    1234 www   6u  IPv4 0x1234      0t0  TCP *:80 (LISTEN)',
                'node     5678 user  12u IPv4 0x5678      0t0  TCP 127.0.0.1:3000 (LISTEN)'
            ];

            $formattedPorts = $this->service->formatPortData($rawOutput);
            
            expect($formattedPorts)->toBeArray();
            expect(count($formattedPorts))->toBe(2);
            
            expect($formattedPorts[0]->getPort())->toBe('80');
            expect($formattedPorts[1]->getPort())->toBe('3000');
            expect($formattedPorts[0]->getProcessName())->toBe('nginx');
        });
    });

    describe('data normalization methods', function () {
        beforeEach(function () {
            $this->service = new DataFormatterService('linux');
            $this->service->initialize();
            $this->reflection = new ReflectionClass($this->service);
        });

        it('normalizes port numbers correctly', function () {
            $method = $this->reflection->getMethod('normalizePort');
            $method->setAccessible(true);

            expect($method->invoke($this->service, '8080'))->toBe('8080');
            expect($method->invoke($this->service, '  80  '))->toBe('80');
            expect($method->invoke($this->service, '99999'))->toBe('99999'); // Invalid but preserved
            expect($method->invoke($this->service, 'abc'))->toBe('abc'); // Non-numeric preserved
        });

        it('normalizes protocols correctly', function () {
            $method = $this->reflection->getMethod('normalizeProtocol');
            $method->setAccessible(true);

            expect($method->invoke($this->service, 'tcp'))->toBe('TCP');
            expect($method->invoke($this->service, 'UDP'))->toBe('UDP');
            expect($method->invoke($this->service, '  tcp  '))->toBe('TCP');
        });

        it('normalizes addresses correctly', function () {
            $method = $this->reflection->getMethod('normalizeAddress');
            $method->setAccessible(true);

            expect($method->invoke($this->service, '0.0.0.0:0'))->toBe('*');
            expect($method->invoke($this->service, '*:*'))->toBe('*');
            expect($method->invoke($this->service, '127.0.0.1:8080'))->toBe('127.0.0.1:8080');
        });

        it('normalizes connection states correctly', function () {
            $method = $this->reflection->getMethod('normalizeState');
            $method->setAccessible(true);

            expect($method->invoke($this->service, 'LISTENING'))->toBe('LISTEN');
            expect($method->invoke($this->service, 'ESTABLISHED'))->toBe('ESTAB');
            expect($method->invoke($this->service, 'TIME_WAIT'))->toBe('TIME-WAIT');
            expect($method->invoke($this->service, 'custom_state'))->toBe('CUSTOM_STATE');
        });

        it('normalizes process names correctly', function () {
            $method = $this->reflection->getMethod('normalizeProcessName');
            $method->setAccessible(true);

            // Test Windows .exe removal
            $windowsService = new DataFormatterService('windows');
            $windowsService->initialize();
            $windowsReflection = new ReflectionClass($windowsService);
            $windowsMethod = $windowsReflection->getMethod('normalizeProcessName');
            $windowsMethod->setAccessible(true);

            expect($windowsMethod->invoke($windowsService, 'notepad.exe'))->toBe('notepad');
            expect($method->invoke($this->service, 'notepad.exe'))->toBe('notepad.exe'); // Linux keeps .exe

            // Test path extraction
            expect($method->invoke($this->service, '/usr/bin/nginx'))->toBe('nginx');
            expect($method->invoke($this->service, 'C:\\Program Files\\app.exe'))->toBe('app.exe');
        });

        it('normalizes CPU usage correctly', function () {
            $method = $this->reflection->getMethod('normalizeCpuUsage');
            $method->setAccessible(true);

            expect($method->invoke($this->service, '1.5'))->toBe('1.5%');
            expect($method->invoke($this->service, '2.3%'))->toBe('2.3%');
            expect($method->invoke($this->service, '  0.5 %  '))->toBe('0.5%');
        });

        it('normalizes memory usage correctly', function () {
            $method = $this->reflection->getMethod('normalizeMemoryUsage');
            $method->setAccessible(true);

            expect($method->invoke($this->service, '1,024 K'))->toBe('1024 KB');
            expect($method->invoke($this->service, '2.5'))->toBe('2.5%');
            expect($method->invoke($this->service, '1.5%'))->toBe('1.5%');
            expect($method->invoke($this->service, '  3.2 %  '))->toBe('3.2%');
        });

        it('normalizes process status correctly', function () {
            $method = $this->reflection->getMethod('normalizeProcessStatus');
            $method->setAccessible(true);

            expect($method->invoke($this->service, 'R'))->toBe('Running');
            expect($method->invoke($this->service, 'S'))->toBe('Sleeping');
            expect($method->invoke($this->service, 'Ss'))->toBe('Sleeping (session leader)');
            expect($method->invoke($this->service, 'R+'))->toBe('Running (foreground)');
            expect($method->invoke($this->service, 'CUSTOM'))->toBe('CUSTOM');
        });

        it('normalizes command lines correctly', function () {
            $method = $this->reflection->getMethod('normalizeCommandLine');
            $method->setAccessible(true);

            $shortCmd = 'nginx -g daemon off;';
            expect($method->invoke($this->service, $shortCmd))->toBe($shortCmd);

            $longCmd = str_repeat('a', 150);
            $result = $method->invoke($this->service, $longCmd);
            expect(strlen($result))->toBe(100);
            expect(str_ends_with($result, '...'))->toBeTrue();
        });
    });

    describe('table display formatting', function () {
        beforeEach(function () {
            $this->service = new DataFormatterService('linux');
            $this->service->initialize();
        });

        it('formats port data for table display', function () {
            $ports = [
                new PortInfo('80', '1234', 'TCP', '0.0.0.0:80', '', 'LISTEN', 'nginx'),
                new PortInfo('443', '5678', 'TCP', '0.0.0.0:443', '', 'LISTEN', 'nginx'),
            ];

            $tableData = $this->service->formatForTableDisplay($ports, 'port');
            
            expect($tableData)->toBeArray();
            expect(count($tableData))->toBe(2);
            
            $firstRow = $tableData[0];
            expect($firstRow['port'])->toBe('    80'); // Right-padded
            expect($firstRow['protocol'])->toBe('TCP     '); // Left-padded
            expect($firstRow['processName'])->toBe('nginx');
        });

        it('formats process data for table display', function () {
            $processes = [
                new ProcessInfo('1234', 'nginx', 'www', '0.5%', '2.3%', 'nginx: worker', 'Running'),
                new ProcessInfo('5678', 'node', 'user', '1.2%', '3.4%', 'node server.js', 'Running'),
            ];

            $tableData = $this->service->formatForTableDisplay($processes, 'process');
            
            expect($tableData)->toBeArray();
            expect(count($tableData))->toBe(2);
            
            $firstRow = $tableData[0];
            expect($firstRow['pid'])->toBe('    1234'); // Right-padded
            expect($firstRow['name'])->toBe('nginx               '); // Left-padded
            expect($firstRow['status'])->toBe('Running');
        });
    });

    describe('error handling', function () {
        it('handles empty output gracefully', function () {
            $service = new DataFormatterService('linux');
            $service->initialize();
            
            $ports = $service->formatPortData([]);
            expect($ports)->toBeArray();
            expect($ports)->toBeEmpty();
            
            $processes = $service->formatProcessData([]);
            expect($processes)->toBeArray();
            expect($processes)->toBeEmpty();
        });

        it('handles malformed output gracefully', function () {
            $service = new DataFormatterService('linux');
            $service->initialize();
            
            $malformedOutput = [
                'This is not valid output',
                'Random text here',
                'No structured data'
            ];
            
            $ports = $service->formatPortData($malformedOutput);
            expect($ports)->toBeArray();
            expect($ports)->toBeEmpty();
            
            $processes = $service->formatProcessData($malformedOutput);
            expect($processes)->toBeArray();
            expect($processes)->toBeEmpty();
        });
    });
});