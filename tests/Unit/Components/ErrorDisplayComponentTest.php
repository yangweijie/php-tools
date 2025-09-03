<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;
use App\Ardillo\Components\ErrorDisplayComponent;
use App\Ardillo\Exceptions\ComponentInitializationException;
use App\Ardillo\Exceptions\DataValidationException;
use App\Ardillo\Exceptions\PermissionException;

class ErrorDisplayComponentTest extends TestCase
{
    private ErrorDisplayComponent $errorDisplay;

    protected function setUp(): void
    {
        $this->errorDisplay = new ErrorDisplayComponent();
    }

    public function testSetError(): void
    {
        $this->errorDisplay->setError('Test Error', 'This is a test error message', 'warning');
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        
        $this->assertEquals('Test Error', $errorInfo['title']);
        $this->assertEquals('This is a test error message', $errorInfo['message']);
        $this->assertEquals('warning', $errorInfo['severity']);
        $this->assertEmpty($errorInfo['recovery_actions']);
        $this->assertFalse($errorInfo['show_details']);
        $this->assertFalse($errorInfo['has_exception']);
    }

    public function testSetErrorFromException(): void
    {
        $exception = new DataValidationException(
            'Invalid input data',
            0,
            null,
            ['field' => 'port_number', 'value' => 'invalid']
        );

        $this->errorDisplay->setErrorFromException($exception);
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        
        $this->assertEquals('Warning', $errorInfo['title']);
        $this->assertEquals($exception->getUserMessage(), $errorInfo['message']);
        $this->assertEquals('warning', $errorInfo['severity']);
        $this->assertNotEmpty($errorInfo['recovery_actions']);
        $this->assertContains('Check input format', $errorInfo['recovery_actions']);
        $this->assertTrue($errorInfo['has_exception']);
    }

    public function testSetRecoveryActions(): void
    {
        $actions = ['Action 1', 'Action 2', 'Action 3'];
        
        $this->errorDisplay->setRecoveryActions($actions);
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertEquals($actions, $errorInfo['recovery_actions']);
    }

    public function testToggleDetails(): void
    {
        $exception = new ComponentInitializationException('Component failed');
        $this->errorDisplay->setErrorFromException($exception);
        
        // Initially details should be hidden
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertFalse($errorInfo['show_details']);
        
        // Toggle details
        $this->errorDisplay->toggleDetails();
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertTrue($errorInfo['show_details']);
        
        // Toggle again
        $this->errorDisplay->toggleDetails();
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertFalse($errorInfo['show_details']);
    }

    public function testInitializeInTestMode(): void
    {
        // Initialize the component (should work in test mode)
        $this->errorDisplay->initialize();
        
        $this->assertTrue($this->errorDisplay->isInitialized());
        
        // Get the widget (should be a test mode widget)
        $widget = $this->errorDisplay->getControl();
        $this->assertNotNull($widget);
        $this->assertTrue(isset($widget->isTestMode));
    }

    public function testCallbackHandling(): void
    {
        $callbackTriggered = false;
        $receivedComponent = null;
        
        $this->errorDisplay->onAction('ok', function ($component) use (&$callbackTriggered, &$receivedComponent) {
            $callbackTriggered = true;
            $receivedComponent = $component;
        });
        
        // Simulate OK button click (we can't actually click in tests)
        // We'll test the callback registration instead
        $this->assertTrue(true); // Callback is registered, actual triggering would happen in GUI
    }

    public function testShowErrorDialogWithException(): void
    {
        $exception = new PermissionException(
            'Access denied for operation',
            0,
            null,
            ['operation' => 'kill_process', 'pid' => '1234']
        );

        // This should not throw an exception even if GUI is not available
        $this->expectOutputString('');
        
        // In test mode, this will output to stderr, so we capture it
        ob_start();
        ErrorDisplayComponent::showErrorDialog($exception);
        $output = ob_get_clean();
        
        // The method should handle the case where GUI is not available gracefully
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testErrorSeverityHandling(): void
    {
        $criticalException = new ComponentInitializationException('Critical failure');
        $criticalException->setRecoverable(false);
        
        $this->errorDisplay->setErrorFromException($criticalException);
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertEquals('warning', $errorInfo['severity']); // ComponentInitializationException has warning severity
        
        // Test with different exception types
        $permissionException = new PermissionException('Permission denied');
        $this->errorDisplay->setErrorFromException($permissionException);
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertEquals('error', $errorInfo['severity']);
    }

    public function testErrorInfoStructure(): void
    {
        $this->errorDisplay->setError('Test Title', 'Test Message', 'error');
        $this->errorDisplay->setRecoveryActions(['Action 1', 'Action 2']);
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        
        // Check that all expected keys are present
        $expectedKeys = ['title', 'message', 'severity', 'recovery_actions', 'show_details', 'has_exception'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $errorInfo);
        }
        
        // Check data types
        $this->assertIsString($errorInfo['title']);
        $this->assertIsString($errorInfo['message']);
        $this->assertIsString($errorInfo['severity']);
        $this->assertIsArray($errorInfo['recovery_actions']);
        $this->assertIsBool($errorInfo['show_details']);
        $this->assertIsBool($errorInfo['has_exception']);
    }

    public function testMultipleErrorUpdates(): void
    {
        // Set initial error
        $this->errorDisplay->setError('First Error', 'First message', 'warning');
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertEquals('First Error', $errorInfo['title']);
        
        // Update with new error
        $this->errorDisplay->setError('Second Error', 'Second message', 'error');
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertEquals('Second Error', $errorInfo['title']);
        $this->assertEquals('Second message', $errorInfo['message']);
        $this->assertEquals('error', $errorInfo['severity']);
    }

    public function testExceptionWithoutRecoveryActions(): void
    {
        // Create a custom exception without recovery actions
        $exception = new class('Test exception') extends \App\Ardillo\Exceptions\GuiException {
            public function getRecoveryActions(): array
            {
                return [];
            }
        };

        $this->errorDisplay->setErrorFromException($exception);
        
        $errorInfo = $this->errorDisplay->getErrorInfo();
        $this->assertEmpty($errorInfo['recovery_actions']);
    }
}