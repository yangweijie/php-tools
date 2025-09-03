<?php

use App\Ardillo\Services\CommandOutputParser;
use App\Ardillo\Models\PortInfo;
use App\Ardillo\Models\ProcessInfo;
use App\Ardillo\Exceptions\SystemCommandException;

describe('CommandOutputParser', function () {
    describe('Windows parsing', function () {
        beforeEach(function () {
            $this->parser = new CommandOutputParser('windows');
        });

        it('parses Windows netstat output correctly', function () {
            $output = [
                'Active Connections',
                '',
                '  Proto  Local Address          Foreign Address        State           PID',
                '  TCP    0.0.0.0:80             0.0.0.0:0              LISTENING       1234',
                '  TCP    127.0.0.1:3000         0.0.0.0:0              LISTENING       5678',
                '  UDP    0.0.0.0:53             *:*                                    9012'
            ];

            $ports = $this->parser->parsePortOutput($output);
            
            expect($ports)->toBeArray();
            expect(count($ports))->toBe(3);
            
            $firstPort = $ports[0];
            expect($firstPort)->toBeInstanceOf(PortInfo::class);
            expect($firstPort->getPort())->toBe('80');
            expect($firstPort->getPid())->toBe('1234');
            expect($firstPort->getProtocol())->toBe('tcp');
            expect($firstPort->getState())->toBe('LISTENING');
        });

        it('parses Windows tasklist CSV output correctly', function () {
            $output = [
                '"Image Name","PID","Session Name","Session#","Mem Usage"',
                '"System Idle Process","0","Services","0","24 K"',
                '"System","4","Services","0","1,024 K"',
                '"smss.exe","364","Services","0","1,048 K"'
            ];

            $processes = $this->parser->parseProcessOutput($output);
            
            expect($processes)->toBeArray();
            expect(count($processes))->toBe(3);
            
            $firstProcess = $processes[0];
            expect($firstProcess)->toBeInstanceOf(ProcessInfo::class);
            expect($firstProcess->getName())->toBe('System Idle Process');
            expect($firstProcess->getPid())->toBe('0');
            expect($firstProcess->getMemoryUsage())->toBe('24 K');
        });
    });

    describe('macOS parsing', function () {
        beforeEach(function () {
            $this->parser = new CommandOutputParser('macos');
        });

        it('parses macOS lsof output correctly', function () {
            $output = [
                'COMMAND   PID USER   FD   TYPE DEVICE SIZE/OFF NODE NAME',
                'launchd     1 root  txt    REG    1,4   162432    2 /sbin/launchd',
                'nginx    1234 www   6u  IPv4 0x1234      0t0  TCP *:80 (LISTEN)',
                'node     5678 user  12u IPv4 0x5678      0t0  TCP 127.0.0.1:3000 (LISTEN)'
            ];

            $ports = $this->parser->parsePortOutput($output);
            
            expect($ports)->toBeArray();
            expect(count($ports))->toBe(2);
            
            $firstPort = $ports[0];
            expect($firstPort)->toBeInstanceOf(PortInfo::class);
            expect($firstPort->getPort())->toBe('80');
            expect($firstPort->getPid())->toBe('1234');
            expect($firstPort->getProcessName())->toBe('nginx');
        });

        it('parses Unix ps output correctly', function () {
            $output = [
                'USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND',
                'root         1  0.0  0.1   4624  1024 ?        Ss   Jan01   0:01 /sbin/init',
                'www       1234  0.5  2.3  12345  5678 ?        S    10:30   0:05 nginx: worker process',
                'user      5678  1.2  3.4  23456  7890 ?        S    11:00   0:10 node server.js'
            ];

            $processes = $this->parser->parseProcessOutput($output);
            
            expect($processes)->toBeArray();
            expect(count($processes))->toBe(3);
            
            $firstProcess = $processes[0];
            expect($firstProcess)->toBeInstanceOf(ProcessInfo::class);
            expect($firstProcess->getPid())->toBe('1');
            expect($firstProcess->getUser())->toBe('root');
            expect($firstProcess->getCpuUsage())->toBe('0.0%');
            expect($firstProcess->getMemoryUsage())->toBe('0.1%');
        });
    });

    describe('Linux parsing', function () {
        beforeEach(function () {
            $this->parser = new CommandOutputParser('linux');
        });

        it('parses Linux ss output correctly', function () {
            $output = [
                'State    Recv-Q Send-Q Local Address:Port  Peer Address:Port',
                'LISTEN   0      128    0.0.0.0:22          0.0.0.0:*     users:(("sshd",pid=1234,fd=3))',
                'LISTEN   0      128    0.0.0.0:80          0.0.0.0:*     users:(("nginx",pid=5678,fd=6))',
                'ESTAB    0      0      192.168.1.100:22    192.168.1.1:54321'
            ];

            $ports = $this->parser->parsePortOutput($output);
            
            expect($ports)->toBeArray();
            expect(count($ports))->toBe(3);
            
            $firstPort = $ports[0];
            expect($firstPort)->toBeInstanceOf(PortInfo::class);
            expect($firstPort->getPort())->toBe('22');
            expect($firstPort->getState())->toBe('LISTEN');
        });
    });  
  describe('data validation', function () {
        beforeEach(function () {
            $this->parser = new CommandOutputParser('linux');
        });

        it('validates port data correctly', function () {
            $validPort = new PortInfo('8080', '1234', 'tcp', '127.0.0.1:8080');
            $invalidPort = new PortInfo('invalid', '5678', 'tcp', '127.0.0.1:5678');
            
            $ports = [$validPort, $invalidPort];
            $validatedPorts = $this->parser->validatePortData($ports);
            
            expect(count($validatedPorts))->toBe(1);
            expect($validatedPorts[0]->getPort())->toBe('8080');
        });

        it('validates process data correctly', function () {
            $validProcess = new ProcessInfo('1234', 'test');
            $invalidProcess = new ProcessInfo('invalid', 'test2');
            
            $processes = [$validProcess, $invalidProcess];
            $validatedProcesses = $this->parser->validateProcessData($processes);
            
            expect(count($validatedProcesses))->toBe(1);
            expect($validatedProcesses[0]->getPid())->toBe('1234');
        });

        it('filters out ports with invalid port numbers', function () {
            $ports = [];
            
            // Valid port
            $port1 = new PortInfo('8080', '1234', 'tcp', '127.0.0.1:8080');
            $ports[] = $port1;
            
            // Invalid port - too high
            $port2 = new PortInfo('99999', '5678', 'tcp', '127.0.0.1:99999');
            $ports[] = $port2;
            
            // Invalid port - negative
            $port3 = new PortInfo('-1', '9012', 'tcp', '127.0.0.1:-1');
            $ports[] = $port3;
            
            // Invalid port - non-numeric
            $port4 = new PortInfo('abc', '3456', 'tcp', '127.0.0.1:abc');
            $ports[] = $port4;
            
            $validatedPorts = $this->parser->validatePortData($ports);
            expect(count($validatedPorts))->toBe(1);
        });

        it('filters out processes with invalid PIDs', function () {
            $processes = [];
            
            // Valid process
            $process1 = new ProcessInfo('1234', 'test');
            $processes[] = $process1;
            
            // Invalid process - non-numeric PID
            $process2 = new ProcessInfo('abc', 'test2');
            $processes[] = $process2;
            
            // Invalid process - negative PID
            $process3 = new ProcessInfo('-1', 'test3');
            $processes[] = $process3;
            
            $validatedProcesses = $this->parser->validateProcessData($processes);
            expect(count($validatedProcesses))->toBe(1);
        });
    });

    describe('error handling', function () {
        it('throws exception for unsupported operating system in port parsing', function () {
            $parser = new CommandOutputParser('unsupported_os');
            
            expect(fn() => $parser->parsePortOutput([]))
                ->toThrow(SystemCommandException::class);
        });

        it('throws exception for unsupported operating system in process parsing', function () {
            $parser = new CommandOutputParser('unsupported_os');
            
            expect(fn() => $parser->parseProcessOutput([]))
                ->toThrow(SystemCommandException::class);
        });

        it('handles empty output gracefully', function () {
            $parser = new CommandOutputParser('linux');
            
            $ports = $parser->parsePortOutput([]);
            expect($ports)->toBeArray();
            expect($ports)->toBeEmpty();
            
            $processes = $parser->parseProcessOutput([]);
            expect($processes)->toBeArray();
            expect($processes)->toBeEmpty();
        });

        it('handles malformed output gracefully', function () {
            $parser = new CommandOutputParser('linux');
            
            $malformedOutput = [
                'This is not valid output',
                'Random text here',
                'No structured data'
            ];
            
            $ports = $parser->parsePortOutput($malformedOutput);
            expect($ports)->toBeArray();
            expect($ports)->toBeEmpty();
            
            $processes = $parser->parseProcessOutput($malformedOutput);
            expect($processes)->toBeArray();
            expect($processes)->toBeEmpty();
        });
    });

    describe('cross-platform compatibility', function () {
        it('handles different line endings', function () {
            $parser = new CommandOutputParser('windows');
            
            $outputWithCRLF = [
                "  TCP    0.0.0.0:80             0.0.0.0:0              LISTENING       1234\r\n",
                "  TCP    127.0.0.1:3000         0.0.0.0:0              LISTENING       5678\r\n"
            ];
            
            $ports = $parser->parsePortOutput($outputWithCRLF);
            expect($ports)->toBeArray();
            expect(count($ports))->toBe(2);
        });

        it('handles different whitespace patterns', function () {
            $parser = new CommandOutputParser('linux');
            
            $outputWithVariableSpacing = [
                'LISTEN   0      128         0.0.0.0:22          0.0.0.0:*',
                'LISTEN 0    128   0.0.0.0:80    0.0.0.0:*'
            ];
            
            $ports = $parser->parsePortOutput($outputWithVariableSpacing);
            expect($ports)->toBeArray();
            expect(count($ports))->toBe(2);
        });
    });
});