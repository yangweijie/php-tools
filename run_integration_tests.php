<?php

/**
 * Integration Test Runner for GUI Migration Project
 * 
 * This script runs the comprehensive integration tests for the Ardillo GUI migration project.
 * It provides detailed output and handles test failures gracefully.
 */

require_once __DIR__ . '/vendor/autoload.php';

class IntegrationTestRunner
{
    private array $testSuites = [
        'Simple Integration' => 'tests/Integration/SimpleIntegrationTest.php',
        'GUI Component Interaction' => 'tests/Integration/GuiComponentInteractionTest.php',
        'Table Component Real Data' => 'tests/Integration/TableComponentRealDataTest.php',
        'Cross-Platform System Commands' => 'tests/Integration/CrossPlatformSystemCommandTest.php',
        'Complete User Workflows' => 'tests/Integration/CompleteUserWorkflowTest.php',
        'Error Scenarios and Edge Cases' => 'tests/Integration/ErrorScenariosAndEdgeCasesTest.php',
    ];

    private array $results = [];
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;

    public function run(): void
    {
        $this->printHeader();
        
        foreach ($this->testSuites as $suiteName => $testFile) {
            $this->runTestSuite($suiteName, $testFile);
        }
        
        $this->printSummary();
    }

    private function printHeader(): void
    {
        echo "\n";
        echo "================================================================================\n";
        echo "                    GUI Migration Integration Test Runner\n";
        echo "================================================================================\n";
        echo "Running comprehensive integration tests for the Ardillo GUI migration project.\n";
        echo "These tests verify component integration, real data scenarios, cross-platform\n";
        echo "compatibility, user workflows, and error handling.\n";
        echo "\n";
    }

    private function runTestSuite(string $suiteName, string $testFile): void
    {
        echo "Running: {$suiteName}\n";
        echo str_repeat("-", 80) . "\n";
        
        if (!file_exists($testFile)) {
            echo "❌ Test file not found: {$testFile}\n\n";
            $this->results[$suiteName] = ['status' => 'missing', 'output' => 'Test file not found'];
            return;
        }

        $startTime = microtime(true);
        
        // Run PHPUnit for this test file
        $command = "./vendor/bin/phpunit {$testFile} 2>&1";
        $output = shell_exec($command);
        $exitCode = 0;
        
        // Parse the last line to get exit code
        exec($command, $outputLines, $exitCode);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        // Parse test results
        $this->parseTestResults($suiteName, $output, $exitCode, $duration);
        
        echo "\n";
    }

    private function parseTestResults(string $suiteName, string $output, int $exitCode, float $duration): void
    {
        $lines = explode("\n", $output);
        
        // Look for test summary line
        $testCount = 0;
        $assertionCount = 0;
        $failureCount = 0;
        $errorCount = 0;
        
        foreach ($lines as $line) {
            if (preg_match('/Tests: (\d+), Assertions: (\d+)/', $line, $matches)) {
                $testCount = (int)$matches[1];
                $assertionCount = (int)$matches[2];
            }
            
            if (preg_match('/Failures: (\d+)/', $line, $matches)) {
                $failureCount = (int)$matches[1];
            }
            
            if (preg_match('/Errors: (\d+)/', $line, $matches)) {
                $errorCount = (int)$matches[1];
            }
        }
        
        $this->totalTests += $testCount;
        
        if ($exitCode === 0) {
            echo "✅ PASSED - {$testCount} tests, {$assertionCount} assertions ({$duration}s)\n";
            $this->passedTests += $testCount;
            $this->results[$suiteName] = [
                'status' => 'passed',
                'tests' => $testCount,
                'assertions' => $assertionCount,
                'duration' => $duration
            ];
        } else {
            echo "❌ FAILED - {$failureCount} failures, {$errorCount} errors ({$duration}s)\n";
            $this->failedTests += $testCount;
            $this->results[$suiteName] = [
                'status' => 'failed',
                'tests' => $testCount,
                'failures' => $failureCount,
                'errors' => $errorCount,
                'duration' => $duration,
                'output' => $output
            ];
            
            // Show first few lines of error output
            $errorLines = array_slice($lines, 0, 10);
            foreach ($errorLines as $line) {
                if (trim($line) && !preg_match('/^PHPUnit|^Runtime|^Configuration/', $line)) {
                    echo "   {$line}\n";
                }
            }
        }
    }

    private function printSummary(): void
    {
        echo "================================================================================\n";
        echo "                              TEST SUMMARY\n";
        echo "================================================================================\n";
        
        $totalDuration = 0;
        foreach ($this->results as $suiteName => $result) {
            $status = $result['status'];
            $duration = $result['duration'] ?? 0;
            $totalDuration += $duration;
            
            $statusIcon = match($status) {
                'passed' => '✅',
                'failed' => '❌',
                'missing' => '⚠️',
                default => '❓'
            };
            
            echo sprintf("%-50s %s (%0.2fs)\n", $suiteName, $statusIcon, $duration);
            
            if ($status === 'passed') {
                echo sprintf("   Tests: %d, Assertions: %d\n", 
                    $result['tests'], $result['assertions']);
            } elseif ($status === 'failed') {
                echo sprintf("   Tests: %d, Failures: %d, Errors: %d\n", 
                    $result['tests'], $result['failures'] ?? 0, $result['errors'] ?? 0);
            }
            echo "\n";
        }
        
        echo str_repeat("-", 80) . "\n";
        echo sprintf("Total Tests: %d | Passed: %d | Failed: %d | Duration: %0.2fs\n", 
            $this->totalTests, $this->passedTests, $this->failedTests, $totalDuration);
        
        if ($this->failedTests > 0) {
            echo "\n❌ Some tests failed. Check the output above for details.\n";
            echo "To run individual test suites:\n";
            foreach ($this->testSuites as $suiteName => $testFile) {
                if ($this->results[$suiteName]['status'] === 'failed') {
                    echo "   ./vendor/bin/phpunit {$testFile}\n";
                }
            }
        } else {
            echo "\n✅ All integration tests passed successfully!\n";
        }
        
        echo "\nIntegration test coverage includes:\n";
        echo "• GUI component interactions and event handling\n";
        echo "• Table component with large datasets and real data scenarios\n";
        echo "• Cross-platform system command execution and validation\n";
        echo "• Complete user workflows (query → select → kill operations)\n";
        echo "• Error scenarios, edge cases, and graceful degradation\n";
        echo "• Memory management and performance optimization\n";
        echo "• Unicode support and special character handling\n";
        echo "\n";
    }
}

// Run the integration tests
$runner = new IntegrationTestRunner();
$runner->run();