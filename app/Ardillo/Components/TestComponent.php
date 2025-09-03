<?php

namespace App\Ardillo\Components;

/**
 * Simple test component for validating the application framework
 */
class TestComponent extends BaseComponent
{
    private string $title;
    private string $content;

    public function __construct(string $title = "Test Component", string $content = "This is a test component")
    {
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * Create the native widget for this component
     */
    protected function createWidget(): void
    {
        try {
            // Create a simple box container
            $this->widget = \Ardillo\Box::createVertical();
            
            // Add a label with the title
            $titleLabel = \Ardillo\Label::create($this->title);
            $this->widget->append($titleLabel, false);
            
            // Add a label with the content
            $contentLabel = \Ardillo\Label::create($this->content);
            $this->widget->append($contentLabel, false);
            
            // Add a button for testing
            $testButton = \Ardillo\Button::create("Test Button");
            $this->widget->append($testButton, false);
            
        } catch (\Exception $e) {
            // Fallback: create a simple placeholder if ardillo classes don't exist
            $this->widget = new \stdClass();
            $this->widget->title = $this->title;
            $this->widget->content = $this->content;
        }
    }

    /**
     * Setup event handlers for the widget
     */
    protected function setupEventHandlers(): void
    {
        // This would set up button click handlers, etc.
        // For now, just a placeholder since we're testing the framework
    }

    /**
     * Set the component title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
        
        if ($this->initialized) {
            // Update the widget if already created
            $this->cleanup();
            $this->initialize();
        }
    }

    /**
     * Set the component content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
        
        if ($this->initialized) {
            // Update the widget if already created
            $this->cleanup();
            $this->initialize();
        }
    }
}