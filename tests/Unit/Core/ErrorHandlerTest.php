<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Ardillo\Core\ErrorHandler;
use App\Ardillo\Exceptions\GuiException;
use App\Ardillo\Exceptions\ArdilloInitializationException;
use App\Ardillo\Exceptions\ComponentInitializationException;
use App\Ardillo\Exceptions\DataValidationException;
use App\Ardillo\Exceptions\PermissionException;
use App\Ardillo\Services\LoggingService;

class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $errorHandler;
    private LoggingService $logger;
    private string $tempLogFile;

    protected function setUp(): void
    {
        $this->tempLogFile = sys_get_temp_dir() . '/test_error_handler.log';
        $this->logger = new LoggingService($this->tempLogFile, 'debug');
        $this->errorHandler = new ErrorHandler($this->logger);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLogFile)) {
            unlink($this->tempLogFile);
        }
    }

    public function testHandleGuiException(): void
    {
        $exception = new ComponentInitializationException(
            'Test component failed',
            0,
            null,
            ['component' => 'TestComponent']
        );

        // Capture stderr output
        $this->expectOutputString('');
        
        $this->errorHandler->handleException($exception);

        // Check that the exception was logged
        $this->assertFileExists($this->tempLogFile);
        $logContent = file_get_contents($this->tempLogFile);
        $this->assertStringContainsString('Test component failed', $logContent);
        $this->assertStringContainsString('ComponentInitializationException', $logContent);
    }

    public function testHandleNonGuiException(): void
    {
        $exception = new \RuntimeException('Generic runtime error');

        $this->errorHandler->handleException($exception);

        // Check that the exception was logged
        $this->assertFileExists($this->tempLogFile);
        $logContent = file_get_contents($this->tempLogFile);
        $this->assertStringContainsString('Generic runtime error', $logContent);
        $this->assertStringContainsString('RuntimeException', $logContent);
    }

    public function testErrorCallbacks(): void
    {
        $callbackExecuted = false;
        $receivedException = null;

        $this->errorHandler->addErrorCallback(function ($exception) use (&$callbackExecuted, &$receivedException) {
            $callbackExecuted = true;
            $receivedException = $exception;
        });

        $testException = new DataValidationException('Invalid data');
        $this->errorHandler->handleException($testException);

        $this->assertTrue($callbackExecuted);
        $this->assertSame($testException, $receivedException);
    }

    public function testCreateErrorReport(): void
    {
        $exception = new PermissionException(
            'Access denied',
            403,
            null,
            ['operation' => 'kill_process', 'pid' => '1234']
        );

        $report = $this->errorHandler->createErrorReport($exception);

        $this->assertStringContainsString('Error Report', $report);
        $this->assertStringContainsString('PermissionException', $report);
        $this->assertStringContainsString('Access denied', $report);
        $this->assertStringContainsString('Severity: error', $report);
        $this->assertStringContainsString('Recoverable: Yes', $report);
        $this->assertStringContainsString('Run as administrator', $report);
        $this->assertStringContainsString('"operation": "kill_process"', $report);
    }

    public function testGracefulDegradationRecoverable(): void
    {
        $exception = new ComponentInitializationException('Component failed');
        
        $result = $this->errorHandler->handleGracefulDegradation($exception);
        
        $this->assertTrue($result);
        
        // Check that recovery was logged
        $logContent = file_get_contents($this->tempLogFile);
        $this->assertStringContainsString('Attempting graceful degradation', $logContent);
    }

    public function testGracefulDegradationNonRecoverable(): void
    {
        $exception = new ArdilloInitializationException('Framework failed');
        
        $result = $this->errorHandler->handleGracefulDegradation($exception);
        
        $this->assertFalse($result);
        
        // Check that non-recoverable error was logged
        $logContent = file_get_contents($this->tempLogFile);
        $this->assertStringContainsString('Non-recoverable error occurred', $logContent);
    }

    public function testGracefulDegradationNonGuiException(): void
    {
        $exception = new \RuntimeException('Generic error');
        
        $result = $this->errorHandler->handleGracefulDegradation($exception);
        
        $this->assertFalse($result);
    }

    public function testErrorHandlerWithFailingCallback(): void
    {
        // Add a callback that throws an exception
        $this->errorHandler->addErrorCallback(function ($exception) {
            throw new \RuntimeException('Callback failed');
        });

        $testException = new DataValidationException('Test error');
        
        // This should not throw an exception even though the callback fails
        $this->errorHandler->handleException($testException);

        // Check that both the original error and callback failure were logged
        $logContent = file_get_contents($this->tempLogFile);
        $this->assertStringContainsString('Test error', $logContent);
        $this->assertStringContainsString('Error callback failed', $logContent);
    }

    public function testPhpErrorHandling(): void
    {
        // Trigger a PHP error (this will be converted to an ErrorException)
        // We need to ensure error_reporting is enabled for this test
        $oldErrorReporting = error_reporting(E_ALL);
        
        try {
            $result = $this->errorHandler->handleError(E_WARNING, 'Test warning', __FILE__, __LINE__);
            $this->assertTrue($result);

            // Check that the error was logged
            $logContent = file_get_contents($this->tempLogFile);
            $this->assertStringContainsString('Test warning', $logContent);
            $this->assertStringContainsString('ErrorException', $logContent);
        } finally {
            // Restore original error reporting
            error_reporting($oldErrorReporting);
        }
    }

    public function testSuppressedErrorHandling(): void
    {
        // Test that suppressed errors (with @) are not handled
        // Temporarily disable error reporting to simulate suppressed errors
        $oldErrorReporting = error_reporting(0);
        
        try {
            $result = $this->errorHandler->handleError(E_WARNING, 'Suppressed warning', __FILE__, __LINE__);
            // When error_reporting() returns 0 (suppressed), handleError should return false
            $this->assertFalse($result);
        } finally {
            // Restore original error reporting
            error_reporting($oldErrorReporting);
        }
    }

    public function testExceptionContext(): void
    {
        $context = [
            'user_id' => 123,
            'operation' => 'test_operation',
            'timestamp' => time()
        ];

        $exception = new DataValidationException(
            'Validation failed',
            0,
            null,
            $context
        );

        $this->assertEquals($context, $exception->getContext());
        $this->assertTrue($exception->isRecoverable());
        $this->assertEquals('warning', $exception->getSeverity());

        $recoveryActions = $exception->getRecoveryActions();
        $this->assertContains('Check input format', $recoveryActions);
    }

    public function testExceptionUserMessage(): void
    {
        // Use DataValidationException which doesn't override getUserMessage
        $exception = new DataValidationException('Technical error message');
        
        // Test default user message
        $userMessage = $exception->getUserMessage();
        $this->assertStringContainsString('Invalid input data', $userMessage);
        
        // Test custom user message
        $exception->setUserMessage('Custom user message');
        $this->assertEquals('Custom user message', $exception->getUserMessage());
    }

    public function testExceptionRecoverability(): void
    {
        $recoverableException = new ComponentInitializationException('Component failed');
        $this->assertTrue($recoverableException->isRecoverable());

        $nonRecoverableException = new ArdilloInitializationException('Framework failed');
        $this->assertFalse($nonRecoverableException->isRecoverable());

        // Test setting recoverability
        $recoverableException->setRecoverable(false);
        $this->assertFalse($recoverableException->isRecoverable());
    }

    public function testTechnicalDetails(): void
    {
        $context = ['test' => 'value'];
        $exception = new DataValidationException(
            'Test message',
            123,
            null,
            $context
        );

        $details = $exception->getTechnicalDetails();

        $this->assertEquals('Test message', $details['message']);
        $this->assertEquals(123, $details['code']);
        $this->assertEquals($context, $details['context']);
        $this->assertTrue($details['recoverable']);
        $this->assertArrayHasKey('file', $details);
        $this->assertArrayHasKey('line', $details);
        $this->assertArrayHasKey('trace', $details);
    }
}