<?php

use App\Ardillo\Components\InputComponent;
use App\Ardillo\Components\ButtonComponent;
use App\Ardillo\Components\LayoutContainer;
use App\Ardillo\Components\TabPanel;
use App\Ardillo\Components\GuiComponentFactory;

describe('GUI Components', function () {
    describe('InputComponent', function () {
        it('should create input component with default values', function () {
            $input = new InputComponent();
            
            expect($input->getValue())->toBe('');
            expect($input->getPlaceholder())->toBe('');
            expect($input->isReadonly())->toBeFalse();
        });

        it('should set and get input value', function () {
            $input = new InputComponent();
            $input->setValue('test value');
            
            expect($input->getValue())->toBe('test value');
        });

        it('should set and get placeholder', function () {
            $input = new InputComponent();
            $input->setPlaceholder('Enter text here');
            
            expect($input->getPlaceholder())->toBe('Enter text here');
        });

        it('should set and get readonly state', function () {
            $input = new InputComponent();
            $input->setReadonly(true);
            
            expect($input->isReadonly())->toBeTrue();
        });

        it('should clear input value', function () {
            $input = new InputComponent();
            $input->setValue('test');
            $input->clear();
            
            expect($input->getValue())->toBe('');
        });

        it('should validate input', function () {
            $input = new InputComponent();
            
            // Empty input should be invalid
            expect($input->validate())->toBeFalse();
            
            // Non-empty input should be valid
            $input->setValue('test');
            expect($input->validate())->toBeTrue();
        });
    });

    describe('ButtonComponent', function () {
        it('should create button component with default values', function () {
            $button = new ButtonComponent();
            
            expect($button->getText())->toBe('');
            expect($button->getType())->toBe('default');
            expect($button->isEnabled())->toBeTrue();
        });

        it('should set and get button text', function () {
            $button = new ButtonComponent();
            $button->setText('Click Me');
            
            expect($button->getText())->toBe('Click Me');
        });

        it('should set and get button type', function () {
            $button = new ButtonComponent();
            $button->setType('primary');
            
            expect($button->getType())->toBe('primary');
        });

        it('should set and get enabled state', function () {
            $button = new ButtonComponent();
            $button->setEnabled(false);
            
            expect($button->isEnabled())->toBeFalse();
        });

        it('should create primary button', function () {
            $button = ButtonComponent::createPrimary('Primary Button');
            
            expect($button->getText())->toBe('Primary Button');
            expect($button->getType())->toBe('primary');
        });

        it('should create danger button', function () {
            $button = ButtonComponent::createDanger('Delete');
            
            expect($button->getText())->toBe('Delete');
            expect($button->getType())->toBe('danger');
        });

        it('should throw exception for invalid button type', function () {
            $button = new ButtonComponent();
            
            expect(fn() => $button->setType('invalid'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('LayoutContainer', function () {
        it('should create layout container with default values', function () {
            $layout = new LayoutContainer();
            
            expect($layout->getLayoutType())->toBe('vertical');
            expect($layout->isPadded())->toBeTrue();
            expect($layout->getSpacing())->toBe(5);
        });

        it('should set and get layout type', function () {
            $layout = new LayoutContainer();
            $layout->setLayoutType('horizontal');
            
            expect($layout->getLayoutType())->toBe('horizontal');
        });

        it('should set and get padding', function () {
            $layout = new LayoutContainer();
            $layout->setPadded(false);
            
            expect($layout->isPadded())->toBeFalse();
        });

        it('should set and get spacing', function () {
            $layout = new LayoutContainer();
            $layout->setSpacing(10);
            
            expect($layout->getSpacing())->toBe(10);
        });

        it('should create horizontal layout', function () {
            $layout = LayoutContainer::createHorizontal(false);
            
            expect($layout->getLayoutType())->toBe('horizontal');
            expect($layout->isPadded())->toBeFalse();
        });

        it('should create vertical layout', function () {
            $layout = LayoutContainer::createVertical(true);
            
            expect($layout->getLayoutType())->toBe('vertical');
            expect($layout->isPadded())->toBeTrue();
        });

        it('should create grid layout', function () {
            $layout = LayoutContainer::createGrid(true);
            
            expect($layout->getLayoutType())->toBe('grid');
            expect($layout->isPadded())->toBeTrue();
        });

        it('should throw exception for invalid layout type', function () {
            $layout = new LayoutContainer();
            
            expect(fn() => $layout->setLayoutType('invalid'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('should manage children', function () {
            $layout = new LayoutContainer();
            $child = new InputComponent();
            
            $layout->addChild($child);
            
            expect($layout->getChildren())->toHaveCount(1);
            expect($layout->getChildren()[0])->toBe($child);
        });

        it('should clear children', function () {
            $layout = new LayoutContainer();
            $child1 = new InputComponent();
            $child2 = new ButtonComponent();
            
            $layout->addChild($child1);
            $layout->addChild($child2);
            $layout->clearChildren();
            
            expect($layout->getChildren())->toHaveCount(0);
        });
    });

    describe('TabPanel', function () {
        it('should create tab panel with default values', function () {
            $tabPanel = new TabPanel();
            
            expect($tabPanel->getTabCount())->toBe(0);
            expect($tabPanel->getActiveTabIndex())->toBe(0);
        });

        it('should add tabs', function () {
            $tabPanel = new TabPanel();
            $content = new InputComponent();
            
            $tabPanel->addTab('Test Tab', $content);
            
            expect($tabPanel->getTabCount())->toBe(1);
            expect($tabPanel->getTabTitle(0))->toBe('Test Tab');
        });

        it('should set and get active tab', function () {
            $tabPanel = new TabPanel();
            $content1 = new InputComponent();
            $content2 = new ButtonComponent();
            
            $tabPanel->addTab('Tab 1', $content1);
            $tabPanel->addTab('Tab 2', $content2);
            $tabPanel->setActiveTab(1);
            
            expect($tabPanel->getActiveTabIndex())->toBe(1);
        });

        it('should find tab by title', function () {
            $tabPanel = new TabPanel();
            $content1 = new InputComponent();
            $content2 = new ButtonComponent();
            
            $tabPanel->addTab('First Tab', $content1);
            $tabPanel->addTab('Second Tab', $content2);
            
            expect($tabPanel->findTabByTitle('Second Tab'))->toBe(1);
            expect($tabPanel->findTabByTitle('Nonexistent'))->toBeNull();
        });

        it('should enable and disable tabs', function () {
            $tabPanel = new TabPanel();
            $content = new InputComponent();
            
            $tabPanel->addTab('Test Tab', $content);
            $tabPanel->setTabEnabled(0, false);
            
            expect($tabPanel->isTabEnabled(0))->toBeFalse();
        });

        it('should remove tabs', function () {
            $tabPanel = new TabPanel();
            $content1 = new InputComponent();
            $content2 = new ButtonComponent();
            
            $tabPanel->addTab('Tab 1', $content1);
            $tabPanel->addTab('Tab 2', $content2);
            $tabPanel->removeTab(0);
            
            expect($tabPanel->getTabCount())->toBe(1);
        });

        it('should navigate between tabs', function () {
            $tabPanel = new TabPanel();
            $content1 = new InputComponent();
            $content2 = new ButtonComponent();
            $content3 = new LayoutContainer();
            
            $tabPanel->addTab('Tab 1', $content1);
            $tabPanel->addTab('Tab 2', $content2);
            $tabPanel->addTab('Tab 3', $content3);
            
            // Test next tab
            $tabPanel->nextTab();
            expect($tabPanel->getActiveTabIndex())->toBe(1);
            
            // Test previous tab
            $tabPanel->previousTab();
            expect($tabPanel->getActiveTabIndex())->toBe(0);
        });
    });

    describe('GuiComponentFactory', function () {
        it('should create factory with services', function () {
            $factory = GuiComponentFactory::createWithServices();
            
            expect($factory)->toBeInstanceOf(GuiComponentFactory::class);
            expect($factory->getLogger())->not->toBeNull();
            expect($factory->getSystemCommandService())->not->toBeNull();
            expect($factory->getDataFormatterService())->not->toBeNull();
        });

        it('should create input component', function () {
            $factory = GuiComponentFactory::createWithServices();
            $input = $factory->createInputComponent('Test placeholder');
            
            expect($input)->toBeInstanceOf(InputComponent::class);
            expect($input->getPlaceholder())->toBe('Test placeholder');
        });

        it('should create button component', function () {
            $factory = GuiComponentFactory::createWithServices();
            $button = $factory->createButton('Test Button', 'primary');
            
            expect($button)->toBeInstanceOf(ButtonComponent::class);
            expect($button->getText())->toBe('Test Button');
            expect($button->getType())->toBe('primary');
        });

        it('should create layout container', function () {
            $factory = GuiComponentFactory::createWithServices();
            $layout = $factory->createLayoutContainer('horizontal', false);
            
            expect($layout)->toBeInstanceOf(LayoutContainer::class);
            expect($layout->getLayoutType())->toBe('horizontal');
            expect($layout->isPadded())->toBeFalse();
        });

        it('should create tab panel', function () {
            $factory = GuiComponentFactory::createWithServices();
            $tabPanel = $factory->createTabPanel();
            
            expect($tabPanel)->toBeInstanceOf(TabPanel::class);
            expect($tabPanel->getTabCount())->toBe(0);
        });

        it('should create managers', function () {
            $factory = GuiComponentFactory::createWithServices();
            
            $portManager = $factory->createPortManager();
            $processManager = $factory->createProcessManager();
            
            expect($portManager)->toBeInstanceOf(\App\Ardillo\Managers\PortManager::class);
            expect($processManager)->toBeInstanceOf(\App\Ardillo\Managers\ProcessManager::class);
        });

        it('should create complete GUI setup', function () {
            $factory = GuiComponentFactory::createWithServices();
            $setup = $factory->createCompleteGuiSetup();
            
            expect($setup)->toHaveKey('main_application');
            expect($setup)->toHaveKey('port_manager');
            expect($setup)->toHaveKey('process_manager');
            expect($setup)->toHaveKey('port_panel');
            expect($setup)->toHaveKey('process_panel');
        });
    });
});