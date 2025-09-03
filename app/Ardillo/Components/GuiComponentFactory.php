<?php

namespace App\Ardillo\Components;

use App\Ardillo\Managers\PortManager;
use App\Ardillo\Managers\ProcessManager;
use App\Ardillo\Services\SystemCommandService;
use App\Ardillo\Services\DataFormatterService;
use App\Ardillo\Services\LoggingService;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating and configuring GUI components
 */
class GuiComponentFactory
{
    private LoggerInterface $logger;
    private SystemCommandService $systemCommandService;
    private DataFormatterService $dataFormatterService;

    public function __construct(
        LoggerInterface $logger,
        SystemCommandService $systemCommandService,
        DataFormatterService $dataFormatterService
    ) {
        $this->logger = $logger;
        $this->systemCommandService = $systemCommandService;
        $this->dataFormatterService = $dataFormatterService;
    }

    /**
     * Create a complete main GUI application
     */
    public function createMainApplication(): MainGuiApplication
    {
        // Create managers
        $portManager = $this->createPortManager();
        $processManager = $this->createProcessManager();

        // Create main application
        $mainApp = new MainGuiApplication(
            $portManager,
            $processManager,
            $this->logger
        );

        return $mainApp;
    }

    /**
     * Create a port manager
     */
    public function createPortManager(): PortManager
    {
        return new PortManager(
            $this->systemCommandService,
            $this->dataFormatterService
        );
    }

    /**
     * Create a process manager
     */
    public function createProcessManager(): ProcessManager
    {
        return new ProcessManager(
            $this->systemCommandService,
            $this->dataFormatterService
        );
    }

    /**
     * Create a port management panel
     */
    public function createPortManagementPanel(?PortManager $portManager = null): PortManagementPanel
    {
        $portManager = $portManager ?? $this->createPortManager();
        return new PortManagementPanel($portManager);
    }

    /**
     * Create a process management panel
     */
    public function createProcessManagementPanel(?ProcessManager $processManager = null): ProcessManagementPanel
    {
        $processManager = $processManager ?? $this->createProcessManager();
        return new ProcessManagementPanel($processManager);
    }

    /**
     * Create a table component with default configuration
     */
    public function createTableComponent(): TableComponent
    {
        $table = new TableComponent();
        
        // Set default columns (can be overridden later)
        $defaultColumns = [
            [
                'key' => 'id',
                'title' => 'ID',
                'type' => 'text',
                'width' => 80
            ],
            [
                'key' => 'name',
                'title' => 'Name',
                'type' => 'text',
                'width' => 200
            ]
        ];
        
        $table->setColumns($defaultColumns);
        
        return $table;
    }

    /**
     * Create an input component with default configuration
     */
    public function createInputComponent(string $placeholder = ''): InputComponent
    {
        $input = new InputComponent();
        
        if (!empty($placeholder)) {
            $input->setPlaceholder($placeholder);
        }
        
        return $input;
    }

    /**
     * Create a button component
     */
    public function createButton(string $text, string $type = 'default'): ButtonComponent
    {
        $button = new ButtonComponent();
        $button->setText($text);
        $button->setType($type);
        
        return $button;
    }

    /**
     * Create a layout container
     */
    public function createLayoutContainer(string $type = 'vertical', bool $padded = true): LayoutContainer
    {
        $container = new LayoutContainer();
        $container->setLayoutType($type);
        $container->setPadded($padded);
        
        return $container;
    }

    /**
     * Create a tab panel
     */
    public function createTabPanel(): TabPanel
    {
        return new TabPanel();
    }

    /**
     * Create a complete GUI setup with all dependencies
     */
    public function createCompleteGuiSetup(): array
    {
        // Create services
        $portManager = $this->createPortManager();
        $processManager = $this->createProcessManager();

        // Create panels
        $portPanel = $this->createPortManagementPanel($portManager);
        $processPanel = $this->createProcessManagementPanel($processManager);

        // Create main application
        $mainApp = $this->createMainApplication();

        return [
            'main_application' => $mainApp,
            'port_manager' => $portManager,
            'process_manager' => $processManager,
            'port_panel' => $portPanel,
            'process_panel' => $processPanel
        ];
    }

    /**
     * Create services factory method
     */
    public static function createWithServices(): self
    {
        // Create logging service (it implements LoggerInterface directly)
        $logger = new LoggingService();

        // Create system command service
        $systemCommandService = new SystemCommandService($logger);

        // Create data formatter service (it doesn't need a logger, just OS detection)
        $dataFormatterService = new DataFormatterService();

        return new self($logger, $systemCommandService, $dataFormatterService);
    }

    /**
     * Get the logger
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get the system command service
     */
    public function getSystemCommandService(): SystemCommandService
    {
        return $this->systemCommandService;
    }

    /**
     * Get the data formatter service
     */
    public function getDataFormatterService(): DataFormatterService
    {
        return $this->dataFormatterService;
    }
}