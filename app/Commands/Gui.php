<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\ArdilloApp;
use App\Ardillo\Components\SimplePortPanel;
use App\Ardillo\Components\SimpleProcessPanel;
use App\Ardillo\Components\DashboardPanel;
use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\LoggingService;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Exceptions\GuiException;
use App\Ardillo\Exceptions\ArdilloInitializationException;
use App\App;
use App\PortKiller;
use App\ProcessKiller;
use App\ExampleTab;

class Gui extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gui {--legacy : Use legacy kingbes/libui implementation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Launch GUI toolkit for port and process management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if legacy mode is requested
        if ($this->option('legacy')) {
            return $this->handleLegacyMode();
        }

        try {
            // Use new ardillo-based implementation
            return $this->handleArdilloMode();
        } catch (ArdilloInitializationException $e) {
            $this->error('Failed to initialize Ardillo framework: ' . $e->getMessage());
            $this->warn('Falling back to legacy implementation...');
            return $this->handleLegacyMode();
        } catch (GuiException $e) {
            $this->error('GUI error: ' . $e->getMessage());
            if ($e->isRecoverable()) {
                $this->warn('Attempting fallback to legacy implementation...');
                return $this->handleLegacyMode();
            }
            return 1;
        } catch (\Exception $e) {
            $this->error('Unexpected error: ' . $e->getMessage());
            $this->warn('Falling back to legacy implementation...');
            return $this->handleLegacyMode();
        }
    }

    /**
     * Handle the new ardillo-based GUI mode
     */
    private function handleArdilloMode(): int
    {
        $this->info('Starting Ardillo-based GUI application...');

        try {
            // Create ArdilloApp instance
            $app = ArdilloApp::createForDevelopment();

            // Create logging service
            $logger = new LoggingService();

            // Create managers with dependency injection
            $portManager = $this->createPortManager($logger);
            $processManager = $this->createProcessManager($logger);

            // Create exit callback
            $exitCallback = function() use ($app) {
                $app->requestShutdown();
            };

            // Create and add tabs
            $portPanel = new SimplePortPanel($portManager, $exitCallback);
            $processPanel = new SimpleProcessPanel($processManager, $exitCallback);

            // Add dashboard tab first
            $app->addTab('Dashboard', new DashboardPanel());
            $app->addTab("端口查杀", $portPanel);
            $app->addTab("进程查杀", $processPanel);

            // Add startup callback for initialization
            $app->onStartup(function() use ($logger) {
                $logger->info('GUI application started successfully');
            });

            // Add shutdown callback for cleanup
            $app->onShutdown(function() use ($logger) {
                $logger->info('GUI application shutting down');
            });

            // Run the application
            $app->run();

            $this->info('GUI application closed successfully.');
            return 0;

        } catch (GuiException $e) {
            $this->error('GUI initialization failed: ' . $e->getMessage());
            
            // Log detailed error information
            if ($e->getContext()) {
                $this->line('Error context: ' . json_encode($e->getContext(), JSON_PRETTY_PRINT));
            }
            
            throw $e;
        } catch (\Exception $e) {
            $this->error('Unexpected error during GUI startup: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle the legacy kingbes/libui mode for backward compatibility
     */
    private function handleLegacyMode(): int
    {
        $this->warn('Using legacy kingbes/libui implementation');
        
        try {
            // Check if legacy classes are available
            if (!class_exists(App::class)) {
                $this->error('Legacy App class not found. Please ensure kingbes/libui is properly installed.');
                return 1;
            }

            // 创建应用 (Create application)
            $application = new App();

            // 创建端口查杀工具 (Create port killer tool)
            $portKiller = new PortKiller();
            $application->addTab("端口查杀", $portKiller->getControl());

            // 创建进程查杀工具 (Create process killer tool)
            $processKiller = new ProcessKiller();
            $application->addTab("进程查杀", $processKiller->getControl());

            // 创建示例tab (Create example tab)
            $exampleTab = new ExampleTab();
            $application->addTab("示例", $exampleTab->getControl());

            // 运行应用 (Run application)
            $application->run();

            $this->info('Legacy GUI application closed successfully.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Legacy GUI failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Create port manager with proper dependency injection
     */
    private function createPortManager(LoggingService $logger): PortManager
    {
        try {
            // Create required services
            $systemCommandService = $this->createSystemCommandService();
            $dataFormatterService = $this->createDataFormatterService();
            
            return new PortManager($systemCommandService, $dataFormatterService);
        } catch (\Exception $e) {
            throw new GuiException(
                'Failed to create port manager: ' . $e->getMessage(),
                0,
                $e,
                ['component' => 'PortManager']
            );
        }
    }

    /**
     * Create process manager with proper dependency injection
     */
    private function createProcessManager(LoggingService $logger): ProcessManager
    {
        try {
            // Create required services
            $systemCommandService = $this->createSystemCommandService();
            $dataFormatterService = $this->createDataFormatterService();
            
            return new ProcessManager($systemCommandService, $dataFormatterService);
        } catch (\Exception $e) {
            throw new GuiException(
                'Failed to create process manager: ' . $e->getMessage(),
                0,
                $e,
                ['component' => 'ProcessManager']
            );
        }
    }

    /**
     * Create system command service
     */
    private function createSystemCommandService(): \App\Ardillo\Services\SystemCommandService
    {
        $service = new \App\Ardillo\Services\SystemCommandService();
        $service->initialize();
        return $service;
    }

    /**
     * Create data formatter service
     */
    private function createDataFormatterService(): \App\Ardillo\Services\DataFormatterService
    {
        $service = new \App\Ardillo\Services\DataFormatterService();
        $service->initialize();
        return $service;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
