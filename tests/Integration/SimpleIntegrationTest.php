<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Ardillo\Components\MainGuiApplication;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Services\LoggingService;

/**
 * Simple integration test to verify basic functionality
 */
class SimpleIntegrationTest extends TestCase
{
    public function test_basic_integration_setup(): void
    {
        $logger = new LoggingService();
        $systemCommandService = $this->createMock(SystemCommandService::class);
        $dataFormatterService = $this->createMock(DataFormatterService::class);

        // Configure mocks
        $systemCommandService->method('isAvailable')->willReturn(true);
        $systemCommandService->method('getOperatingSystem')->willReturn('linux');
        $dataFormatterService->method('isAvailable')->willReturn(true);

        $portManager = new PortManager($systemCommandService, $dataFormatterService);
        $processManager = new ProcessManager($systemCommandService, $dataFormatterService);

        $mainApp = new MainGuiApplication($portManager, $processManager, $logger);

        // Test basic instantiation
        $this->assertInstanceOf(MainGuiApplication::class, $mainApp);
        
        // Test initialization
        $mainApp->initialize();
        
        // Test status
        $status = $mainApp->getStatus();
        $this->assertIsArray($status);
        $this->assertArrayHasKey('initialized', $status);
        $this->assertTrue($status['initialized']);
    }
}