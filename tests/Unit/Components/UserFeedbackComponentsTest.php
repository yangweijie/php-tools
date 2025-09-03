<?php

namespace Tests\Unit\Components;

use Tests\TestCase;
use App\Ardillo\Components\DialogComponent;
use App\Ardillo\Components\ProgressIndicatorComponent;
use App\Ardillo\Components\StatusMessageComponent;
use App\Ardillo\Components\BatchResultsDialogComponent;

class UserFeedbackComponentsTest extends TestCase
{
    public function test_dialog_component_creation()
    {
        // Test confirmation dialog
        $confirmDialog = DialogComponent::createConfirmation('Test Title', 'Test Message');
        $this->assertInstanceOf(DialogComponent::class, $confirmDialog);
        
        // Test info dialog
        $infoDialog = DialogComponent::createInfo('Info Title', 'Info Message');
        $this->assertInstanceOf(DialogComponent::class, $infoDialog);
        
        // Test warning dialog
        $warningDialog = DialogComponent::createWarning('Warning Title', 'Warning Message');
        $this->assertInstanceOf(DialogComponent::class, $warningDialog);
        
        // Test error dialog
        $errorDialog = DialogComponent::createError('Error Title', 'Error Message');
        $this->assertInstanceOf(DialogComponent::class, $errorDialog);
    }

    public function test_dialog_component_initialization()
    {
        $dialog = DialogComponent::createConfirmation('Test', 'Are you sure?');
        $dialog->initialize();
        
        $this->assertTrue($dialog->isInitialized());
    }

    public function test_dialog_component_show_returns_result()
    {
        $dialog = DialogComponent::createConfirmation('Test', 'Are you sure?');
        $result = $dialog->show();
        
        // In test mode, should return 'Yes' for confirmation dialogs
        $this->assertEquals('Yes', $result);
        $this->assertTrue($dialog->isConfirmed());
        $this->assertFalse($dialog->isCancelled());
    }

    public function test_progress_indicator_creation()
    {
        // Test indeterminate progress
        $indeterminateProgress = ProgressIndicatorComponent::createIndeterminate('Loading', 'Please wait...');
        $this->assertInstanceOf(ProgressIndicatorComponent::class, $indeterminateProgress);
        $this->assertTrue($indeterminateProgress->isIndeterminate());
        
        // Test determinate progress
        $determinateProgress = ProgressIndicatorComponent::createDeterminate('Processing', 'Processing items...');
        $this->assertInstanceOf(ProgressIndicatorComponent::class, $determinateProgress);
        $this->assertFalse($determinateProgress->isIndeterminate());
    }

    public function test_progress_indicator_progress_updates()
    {
        $progress = ProgressIndicatorComponent::createDeterminate('Test', 'Testing...');
        
        // Test progress setting
        $progress->setProgress(0.5);
        $this->assertEquals(0.5, $progress->getProgress());
        $this->assertEquals(50.0, $progress->getProgressPercentage());
        
        // Test progress bounds
        $progress->setProgress(1.5); // Should be clamped to 1.0
        $this->assertEquals(1.0, $progress->getProgress());
        
        $progress->setProgress(-0.5); // Should be clamped to 0.0
        $this->assertEquals(0.0, $progress->getProgress());
        
        // Test increment
        $progress->setProgress(0.3);
        $progress->incrementProgress(0.2);
        $this->assertEquals(0.5, $progress->getProgress());
        
        // Test percentage setting
        $progress->setProgressPercentage(75);
        $this->assertEquals(0.75, $progress->getProgress());
    }

    public function test_progress_indicator_visibility()
    {
        $progress = ProgressIndicatorComponent::createIndeterminate('Test', 'Testing...');
        
        $this->assertFalse($progress->isVisible());
        
        $progress->show();
        $this->assertTrue($progress->isVisible());
        
        $progress->hide();
        $this->assertFalse($progress->isVisible());
    }

    public function test_status_message_creation()
    {
        // Test different message types
        $infoMessage = StatusMessageComponent::createInfo('Info message');
        $this->assertInstanceOf(StatusMessageComponent::class, $infoMessage);
        $this->assertEquals('info', $infoMessage->getMessageType());
        
        $successMessage = StatusMessageComponent::createSuccess('Success message');
        $this->assertEquals('success', $successMessage->getMessageType());
        
        $warningMessage = StatusMessageComponent::createWarning('Warning message');
        $this->assertEquals('warning', $warningMessage->getMessageType());
        
        $errorMessage = StatusMessageComponent::createError('Error message');
        $this->assertEquals('error', $errorMessage->getMessageType());
    }

    public function test_status_message_auto_hide()
    {
        $message = StatusMessageComponent::createInfo('Test message');
        
        // Test auto-hide configuration
        $this->assertFalse($message->isAutoHideEnabled());
        
        $message->setAutoHide(true, 2000);
        $this->assertTrue($message->isAutoHideEnabled());
        $this->assertEquals(2000, $message->getAutoHideDelay());
    }

    public function test_status_message_temporary_and_persistent()
    {
        $message = new StatusMessageComponent();
        
        // Test temporary message
        $message->showTemporary('Temporary message', 'warning', 1000);
        $this->assertEquals('Temporary message', $message->getMessage());
        $this->assertEquals('warning', $message->getMessageType());
        $this->assertTrue($message->isAutoHideEnabled());
        $this->assertEquals(1000, $message->getAutoHideDelay());
        
        // Test persistent message
        $message->showPersistent('Persistent message', 'error');
        $this->assertEquals('Persistent message', $message->getMessage());
        $this->assertEquals('error', $message->getMessageType());
        $this->assertFalse($message->isAutoHideEnabled());
    }

    public function test_batch_results_dialog_creation()
    {
        $results = [
            ['pid' => '1234', 'success' => true, 'message' => 'Killed successfully'],
            ['pid' => '5678', 'success' => false, 'message' => 'Permission denied']
        ];
        
        $summary = [
            'total' => 2,
            'success' => 1,
            'failed' => 1
        ];
        
        // Test generic results dialog
        $dialog = BatchResultsDialogComponent::create('Test Results', $results, $summary);
        $this->assertInstanceOf(BatchResultsDialogComponent::class, $dialog);
        $this->assertEquals($results, $dialog->getResults());
        $this->assertEquals($summary, $dialog->getSummary());
        
        // Test kill results dialog
        $killResults = [
            'results' => $results,
            'summary' => $summary,
            'success' => true,
            'message' => 'Batch operation completed'
        ];
        
        $killDialog = BatchResultsDialogComponent::createKillResults($killResults);
        $this->assertInstanceOf(BatchResultsDialogComponent::class, $killDialog);
        $this->assertEquals($results, $killDialog->getResults());
    }

    public function test_batch_results_dialog_analysis()
    {
        $results = [
            ['pid' => '1234', 'success' => true, 'message' => 'Killed successfully'],
            ['pid' => '5678', 'success' => false, 'message' => 'Permission denied'],
            ['pid' => '9012', 'success' => true, 'message' => 'Killed successfully']
        ];
        
        $dialog = BatchResultsDialogComponent::create('Test', $results);
        
        // Test success/failure counts
        $this->assertEquals(2, $dialog->getSuccessCount());
        $this->assertEquals(1, $dialog->getFailureCount());
        
        // Test success analysis
        $this->assertFalse($dialog->isAllSuccessful());
        $this->assertTrue($dialog->hasAnySuccess());
        
        // Test summary text
        $summaryText = $dialog->getSummaryText();
        $this->assertStringContainsString('2 of 3 operations completed successfully', $summaryText);
    }

    public function test_batch_results_dialog_all_successful()
    {
        $results = [
            ['pid' => '1234', 'success' => true, 'message' => 'Killed successfully'],
            ['pid' => '5678', 'success' => true, 'message' => 'Killed successfully']
        ];
        
        $dialog = BatchResultsDialogComponent::create('Test', $results);
        
        $this->assertTrue($dialog->isAllSuccessful());
        $this->assertTrue($dialog->hasAnySuccess());
        $this->assertEquals(2, $dialog->getSuccessCount());
        $this->assertEquals(0, $dialog->getFailureCount());
        
        $summaryText = $dialog->getSummaryText();
        $this->assertStringContainsString('All 2 operations completed successfully', $summaryText);
    }

    public function test_batch_results_dialog_all_failed()
    {
        $results = [
            ['pid' => '1234', 'success' => false, 'message' => 'Permission denied'],
            ['pid' => '5678', 'success' => false, 'message' => 'Process not found']
        ];
        
        $dialog = BatchResultsDialogComponent::create('Test', $results);
        
        $this->assertFalse($dialog->isAllSuccessful());
        $this->assertFalse($dialog->hasAnySuccess());
        $this->assertEquals(0, $dialog->getSuccessCount());
        $this->assertEquals(2, $dialog->getFailureCount());
        
        $summaryText = $dialog->getSummaryText();
        $this->assertStringContainsString('All 2 operations failed', $summaryText);
    }

    public function test_batch_results_dialog_empty_results()
    {
        $dialog = BatchResultsDialogComponent::create('Test', []);
        
        $this->assertFalse($dialog->isAllSuccessful());
        $this->assertFalse($dialog->hasAnySuccess());
        $this->assertEquals(0, $dialog->getSuccessCount());
        $this->assertEquals(0, $dialog->getFailureCount());
        
        $summaryText = $dialog->getSummaryText();
        $this->assertEquals('No operations performed', $summaryText);
    }
}