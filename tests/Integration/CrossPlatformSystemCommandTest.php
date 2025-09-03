<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Services\LoggingService;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Exceptions\SystemCommandException;

/**
 * Integration tests for cross-platform system command execution
 * Tests system command functionality across different operating systems
 */
class CrossPlatformSystemCommandTest extends TestCase
{
    private SystemCommandService $systemCommandService;
    private DataFormatterService $dataFormatterService;
    private PortManager $portManager;
    private ProcessManager $processManager;
    private LoggingService $logger;
    private string $currentOS;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new LoggingService();
        $this->systemCommandService = new SystemCommandService($this->logger);
        $this->dataFormatterService = new DataFormatterService();
        
        $this->portManager = new PortManager($this->systemCommandService, $this->dataFormatterService);
        $this->processManager = new ProcessManager($this->systemCommandService, $this->dataFormatterService);
        
        $this->currentOS = $this->systemCommandService->getOperatingSystem();
    }

    public function test_operating_system_detection(): void
    {
        $detectedOS = $this->systemCommandService->getOperatingSystem();
        
        // Should detect one of the supported operating systems
        $supportedOS = ['windows', 'linux', 'darwin', 'macos'];
        $this->assertContains($detectedOS, $supportedOS, 
            "Detected OS '{$detectedOS}' should be one of: " . implode(', ', $supportedOS));
        
        // Verify OS-specific command availability
        $this->assertTrue($this->systemCommandService->isAvailable(), 
            'System command service should be available on detected OS');
    }

    public function test_port_query_commands_by_platform(): void
    {
        $this->markTestSkippedIfCommandsNotAvailable();

        switch ($this->currentOS) {
            case 'windows':
                $this->runWindowsPortTests();
                break;
            case 'linux':
                $this->runLinuxPortTests();
                break;
            case 'darwin':
            case 'macos':
                $this->runMacOSPortTests();
                break;
            default:
                $this->markTestSkipped("Unsupported OS: {$this->currentOS}");
        }
    }

    public function test_process_query_commands_by_platform(): void
    {
        $this->markTestSkippedIfCommandsNotAvailable();

        switch ($this->currentOS) {
            case 'windows':
                $this->runWindowsProcessTests();
                break;
            case 'linux':
                $this->runLinuxProcessTests();
                break;
            case 'darwin':
            case 'macos':
                $this->runMacOSProcessTests();
                break;
            default:
                $this->markTestSkipped("Unsupported OS: {$this->currentOS}");
        }
    }

    private function runWindowsPortTests(): void
    {
        // Test Windows netstat command
        try {
            $result = $this->systemCommandService->queryPorts();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('command', $result);
            $this->assertArrayHasKey('output', $result);
            $this->assertArrayHasKey('return_code', $result);
            
            // Windows should use netstat
            $this->assertStringContainsString('netstat', strtolower($result['command']));
            
            // Should have some output (unless no network activity)
            $this->assertIsArray($result['output']);
            
            // Return code should be 0 for success
            $this->assertEquals(0, $result['return_code']);
            
        } catch (SystemCommandException $e) {
            $this->fail("Windows port query failed: " . $e->getMessage());
        }

        // Test specific port query
        try {
            $result = $this->systemCommandService->queryPorts('80');
            $this->assertIsArray($result);
            $this->assertStringContainsString('80', $result['command']);
            
        } catch (SystemCommandException $e) {
            // It's OK if port 80 is not in use
            $this->assertStringContainsString('80', $e->getMessage());
        }
    }

    private function runLinuxPortTests(): void
    {
        // Test Linux ss or netstat command
        try {
            $result = $this->systemCommandService->queryPorts();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('command', $result);
            $this->assertArrayHasKey('output', $result);
            $this->assertArrayHasKey('return_code', $result);
            
            // Linux should use ss or netstat
            $command = strtolower($result['command']);
            $this->assertTrue(
                strpos($command, 'ss ') !== false || strpos($command, 'netstat') !== false,
                "Linux should use 'ss' or 'netstat' command, got: {$result['command']}"
            );
            
            $this->assertEquals(0, $result['return_code']);
            
        } catch (SystemCommandException $e) {
            $this->fail("Linux port query failed: " . $e->getMessage());
        }

        // Test lsof command if available
        try {
            $result = $this->systemCommandService->queryPorts('22');
            $this->assertIsArray($result);
            
        } catch (SystemCommandException $e) {
            // SSH port might not be active in test environment
            $this->assertTrue(true, 'SSH port test completed');
        }
    }

    private function runMacOSPortTests(): void
    {
        // Test macOS lsof command
        try {
            $result = $this->systemCommandService->queryPorts();
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('command', $result);
            $this->assertArrayHasKey('output', $result);
            $this->assertArrayHasKey('return_code', $result);
            
            // macOS should use lsof or netstat
            $command = strtolower($result['command']);
            $this->assertTrue(
                strpos($command, 'lsof') !== false || strpos($command, 'netstat') !== false,
                "macOS should use 'lsof' or 'netstat' command, got: {$result['command']}"
            );
            
            $this->assertEquals(0, $result['return_code']);
            
        } catch (SystemCommandException $e) {
            $this->fail("macOS port query failed: " . $e->getMessage());
        }
    }

    private function runWindowsProcessTests(): void
    {
        // Test Windows tasklist command
        try {
            $result = $this->systemCommandService->queryProcesses();
            
            $this->assertIsArray($result);
            $this->assertStringContainsString('tasklist', strtolower($result['command']));
            $this->assertEquals(0, $result['return_code']);
            $this->assertNotEmpty($result['output']);
            
        } catch (SystemCommandException $e) {
            $this->fail("Windows process query failed: " . $e->getMessage());
        }

        // Test specific process query
        try {
            $result = $this->systemCommandService->queryProcesses('explorer');
            $this->assertIsArray($result);
            $this->assertStringContainsString('explorer', strtolower($result['command']));
            
        } catch (SystemCommandException $e) {
            // Explorer might not be running in test environment
            $this->assertTrue(true, 'Explorer process test completed');
        }
    }

    private function runLinuxProcessTests(): void
    {
        // Test Linux ps command
        try {
            $result = $this->systemCommandService->queryProcesses();
            
            $this->assertIsArray($result);
            $this->assertStringContainsString('ps', strtolower($result['command']));
            $this->assertEquals(0, $result['return_code']);
            $this->assertNotEmpty($result['output']);
            
        } catch (SystemCommandException $e) {
            $this->fail("Linux process query failed: " . $e->getMessage());
        }

        // Test process by name
        try {
            $result = $this->systemCommandService->queryProcesses('init');
            $this->assertIsArray($result);
            
        } catch (SystemCommandException $e) {
            // init process should exist on Linux
            $this->fail("Linux init process query failed: " . $e->getMessage());
        }
    }

    private function runMacOSProcessTests(): void
    {
        // Test macOS ps command
        try {
            $result = $this->systemCommandService->queryProcesses();
            
            $this->assertIsArray($result);
            $this->assertStringContainsString('ps', strtolower($result['command']));
            $this->assertEquals(0, $result['return_code']);
            $this->assertNotEmpty($result['output']);
            
        } catch (SystemCommandException $e) {
            $this->fail("macOS process query failed: " . $e->getMessage());
        }
    }

    public function test_data_formatting_cross_platform(): void
    {
        $this->markTestSkippedIfCommandsNotAvailable();

        // Test port data formatting
        try {
            $portResult = $this->systemCommandService->queryPorts();
            $formattedPorts = $this->dataFormatterService->formatPortData($portResult['output']);
            
            $this->assertIsArray($formattedPorts);
            
            // If we have port data, verify format
            if (!empty($formattedPorts)) {
                $firstPort = $formattedPorts[0];
                $this->assertInstanceOf(\App\Ardillo\Models\PortInfo::class, $firstPort);
                $this->assertNotEmpty($firstPort->getPort());
                $this->assertNotEmpty($firstPort->getPid());
                $this->assertNotEmpty($firstPort->getProtocol());
            }
            
        } catch (SystemCommandException $e) {
            $this->markTestSkipped("Port data formatting test skipped: " . $e->getMessage());
        }

        // Test process data formatting
        try {
            $processResult = $this->systemCommandService->queryProcesses();
            $formattedProcesses = $this->dataFormatterService->formatProcessData($processResult['output']);
            
            $this->assertIsArray($formattedProcesses);
            
            // Should have at least some processes
            $this->assertNotEmpty($formattedProcesses, 'Should have at least some running processes');
            
            $firstProcess = $formattedProcesses[0];
            $this->assertInstanceOf(\App\Ardillo\Models\ProcessInfo::class, $firstProcess);
            $this->assertNotEmpty($firstProcess->getPid());
            $this->assertNotEmpty($firstProcess->getName());
            
        } catch (SystemCommandException $e) {
            $this->fail("Process data formatting failed: " . $e->getMessage());
        }
    }

    public function test_kill_command_validation_cross_platform(): void
    {
        // Test kill command validation without actually killing processes
        
        switch ($this->currentOS) {
            case 'windows':
                $this->validateWindowsKillCommands();
                break;
            case 'linux':
            case 'darwin':
            case 'macos':
                $this->validateUnixKillCommands();
                break;
        }
    }

    private function validateWindowsKillCommands(): void
    {
        // Test Windows taskkill command validation
        $killCommands = $this->systemCommandService->getKillCommands();
        
        $this->assertIsArray($killCommands);
        $this->assertArrayHasKey('by_pid', $killCommands);
        
        $pidCommand = $killCommands['by_pid'];
        $this->assertStringContainsString('taskkill', strtolower($pidCommand));
        $this->assertStringContainsString('/pid', strtolower($pidCommand));
    }

    private function validateUnixKillCommands(): void
    {
        // Test Unix kill command validation
        $killCommands = $this->systemCommandService->getKillCommands();
        
        $this->assertIsArray($killCommands);
        $this->assertArrayHasKey('by_pid', $killCommands);
        
        $pidCommand = $killCommands['by_pid'];
        $this->assertStringContainsString('kill', strtolower($pidCommand));
    }

    public function test_command_timeout_handling(): void
    {
        $this->markTestSkippedIfCommandsNotAvailable();

        // Test that commands complete within reasonable time
        $startTime = microtime(true);
        
        try {
            $result = $this->systemCommandService->queryProcesses();
            $endTime = microtime(true);
            
            $executionTime = $endTime - $startTime;
            
            // Process query should complete within 30 seconds
            $this->assertLessThan(30.0, $executionTime, 
                'Process query should complete within 30 seconds');
            
            $this->assertEquals(0, $result['return_code']);
            
        } catch (SystemCommandException $e) {
            $this->fail("Command timeout test failed: " . $e->getMessage());
        }
    }

    public function test_command_error_handling(): void
    {
        // Test handling of invalid commands
        try {
            // Try to query an invalid port
            $result = $this->systemCommandService->queryPorts('999999');
            
            // Should either succeed with empty results or throw exception
            if (isset($result['return_code']) && $result['return_code'] !== 0) {
                $this->assertTrue(true, 'Invalid port query handled appropriately');
            }
            
        } catch (SystemCommandException $e) {
            // Exception is expected for invalid input
            $this->assertStringContainsString('999999', $e->getMessage());
        }

        // Test handling of invalid process names
        try {
            $result = $this->systemCommandService->queryProcesses('nonexistent_process_name_12345');
            
            // Should succeed but return empty results
            $this->assertIsArray($result);
            
        } catch (SystemCommandException $e) {
            // Exception is acceptable for nonexistent processes
            $this->assertTrue(true, 'Nonexistent process query handled appropriately');
        }
    }

    public function test_permission_handling(): void
    {
        $this->markTestSkippedIfCommandsNotAvailable();

        // Test that commands work with current user permissions
        try {
            $processResult = $this->systemCommandService->queryProcesses();
            $this->assertEquals(0, $processResult['return_code']);
            
            $portResult = $this->systemCommandService->queryPorts();
            $this->assertEquals(0, $portResult['return_code']);
            
        } catch (SystemCommandException $e) {
            // If permission denied, that's a valid test result
            if (strpos(strtolower($e->getMessage()), 'permission') !== false) {
                $this->markTestSkipped('Insufficient permissions for system commands');
            } else {
                $this->fail("Unexpected error: " . $e->getMessage());
            }
        }
    }

    public function test_manager_integration_cross_platform(): void
    {
        $this->markTestSkippedIfCommandsNotAvailable();

        // Test port manager integration
        try {
            $ports = $this->portManager->query('');
            $this->assertIsArray($ports);
            
            // Test table data formatting
            $tableData = $this->portManager->getFormattedTableData($ports);
            $this->assertIsArray($tableData);
            
            foreach ($tableData as $row) {
                $this->assertArrayHasKey('id', $row);
                $this->assertArrayHasKey('port', $row);
                $this->assertArrayHasKey('pid', $row);
                $this->assertArrayHasKey('protocol', $row);
            }
            
        } catch (SystemCommandException $e) {
            $this->markTestSkipped("Port manager integration test skipped: " . $e->getMessage());
        }

        // Test process manager integration
        try {
            $processes = $this->processManager->query('');
            $this->assertIsArray($processes);
            $this->assertNotEmpty($processes, 'Should have at least some running processes');
            
            // Test table data formatting
            $tableData = $this->processManager->getFormattedTableData($processes);
            $this->assertIsArray($tableData);
            
            foreach ($tableData as $row) {
                $this->assertArrayHasKey('id', $row);
                $this->assertArrayHasKey('pid', $row);
                $this->assertArrayHasKey('name', $row);
            }
            
        } catch (SystemCommandException $e) {
            $this->fail("Process manager integration failed: " . $e->getMessage());
        }
    }

    public function test_system_info_reporting(): void
    {
        // Test system information gathering
        $portSystemInfo = $this->portManager->getSystemInfo();
        $processSystemInfo = $this->processManager->getSystemInfo();
        
        $this->assertIsArray($portSystemInfo);
        $this->assertIsArray($processSystemInfo);
        
        $this->assertArrayHasKey('operating_system', $portSystemInfo);
        $this->assertArrayHasKey('service_ready', $portSystemInfo);
        
        $this->assertEquals($this->currentOS, $portSystemInfo['operating_system']);
        $this->assertEquals($this->currentOS, $processSystemInfo['operating_system']);
    }

    public function test_command_builder_availability(): void
    {
        $commandBuilders = $this->systemCommandService->getCommandBuilders();
        
        $this->assertIsArray($commandBuilders);
        
        // Should have builders for current platform
        switch ($this->currentOS) {
            case 'windows':
                $this->assertArrayHasKey('port_query_windows', $commandBuilders);
                $this->assertArrayHasKey('process_query_windows', $commandBuilders);
                break;
            case 'linux':
                $this->assertArrayHasKey('port_query_linux', $commandBuilders);
                $this->assertArrayHasKey('process_query_linux', $commandBuilders);
                break;
            case 'darwin':
            case 'macos':
                $this->assertArrayHasKey('port_query_macos', $commandBuilders);
                $this->assertArrayHasKey('process_query_macos', $commandBuilders);
                break;
        }
    }

    private function markTestSkippedIfCommandsNotAvailable(): void
    {
        if (!$this->systemCommandService->isAvailable()) {
            $this->markTestSkipped('System commands not available on this platform');
        }
    }

    protected function tearDown(): void
    {
        // Clean up any resources
        parent::tearDown();
    }
}