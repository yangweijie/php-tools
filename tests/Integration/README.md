# Integration Tests for GUI Migration Project

This directory contains comprehensive integration tests for the Ardillo GUI migration project. These tests verify the complete integration between GUI components, managers, services, and the underlying system commands.

## Test Categories

### 1. GUI Component Interaction Tests (`GuiComponentInteractionTest.php`)
Tests the complete integration between GUI components, managers, and services:
- Application initialization flow
- Component hierarchy and relationships
- Tab switching and state management
- Cross-panel communication
- Event propagation and handling
- Memory management during operations
- Application shutdown and cleanup

### 2. Table Component Real Data Tests (`TableComponentRealDataTest.php`)
Tests table behavior with various data types, sizes, and edge cases:
- Large datasets (100+ ports, 200+ processes)
- Mixed data types and special characters
- Unicode and internationalization support
- Data refresh scenarios
- Selection persistence across operations
- Performance with frequent updates
- Memory usage optimization
- Error handling with invalid data

### 3. Cross-Platform System Command Tests (`CrossPlatformSystemCommandTest.php`)
Tests system command functionality across different operating systems:
- Operating system detection
- Platform-specific command execution (Windows, Linux, macOS)
- Data formatting across platforms
- Kill command validation
- Command timeout handling
- Permission and error handling
- Manager integration with real system commands

### 4. Complete User Workflow Tests (`CompleteUserWorkflowTest.php`)
Tests end-to-end user scenarios:
- Complete port management workflow (query → select → kill)
- Complete process management workflow
- Tab switching with state preservation
- Query all ports/processes workflows
- Error recovery scenarios
- Kill operation failure handling
- Data refresh workflows
- Cross-panel workflows (port → process correlation)

### 5. Error Scenarios and Edge Cases Tests (`ErrorScenariosAndEdgeCasesTest.php`)
Tests application behavior under various failure conditions:
- Invalid dependencies and initialization failures
- Input validation edge cases
- System command failures
- Data formatting failures
- Permission errors and system process protection
- Memory exhaustion scenarios
- Concurrent operation conflicts
- Malformed data handling
- Unicode and special character edge cases
- Network timeouts and privilege issues
- Resource cleanup on errors
- Graceful degradation scenarios

## Running the Tests

### Run All Integration Tests
```bash
./vendor/bin/phpunit tests/Integration/
```

### Run Specific Test Categories
```bash
# GUI Component Integration
./vendor/bin/phpunit tests/Integration/GuiComponentInteractionTest.php

# Table Component Real Data
./vendor/bin/phpunit tests/Integration/TableComponentRealDataTest.php

# Cross-Platform System Commands
./vendor/bin/phpunit tests/Integration/CrossPlatformSystemCommandTest.php

# Complete User Workflows
./vendor/bin/phpunit tests/Integration/CompleteUserWorkflowTest.php

# Error Scenarios and Edge Cases
./vendor/bin/phpunit tests/Integration/ErrorScenariosAndEdgeCasesTest.php
```

### Run Individual Tests
```bash
./vendor/bin/phpunit --filter test_complete_application_initialization_flow tests/Integration/GuiComponentInteractionTest.php
```

## Test Requirements Coverage

These integration tests fulfill the requirements specified in task 14:

### ✅ GUI Component Interactions
- **MainGuiApplication** integration with panels and managers
- **TabPanel** switching and state management
- **TableComponent** selection and data handling
- Event propagation between components
- Cross-panel communication and updates

### ✅ Table Component with Real Data Scenarios
- Large datasets (100-200+ items)
- Mixed data types and formats
- Unicode and special characters
- Performance testing with frequent updates
- Memory usage optimization
- Selection persistence and state management

### ✅ Cross-Platform System Command Execution
- Windows (`netstat`, `tasklist`, `taskkill`)
- Linux (`ss`, `ps`, `kill`, `lsof`)
- macOS (`lsof`, `ps`, `kill`)
- Command timeout and error handling
- Permission validation
- Data formatting across platforms

### ✅ Complete User Workflows
- **Query → Select → Kill** workflows for both ports and processes
- Tab switching with state preservation
- Error recovery and user feedback
- Batch operations and progress indication
- Cross-panel data correlation

### ✅ Error Scenarios and Edge Cases
- Invalid input validation
- System command failures
- Permission denied scenarios
- Memory and resource management
- Concurrent operation handling
- Malformed data processing
- Network timeouts and system errors

## Test Environment Setup

### Mock Services
Most tests use mocked services to ensure consistent, predictable behavior:
- `SystemCommandService` - Mocked for reliable command simulation
- `DataFormatterService` - Mocked for consistent data formatting
- `LoggingService` - Real service for actual logging

### Real Integration Points
Some tests use real services where appropriate:
- Component initialization and lifecycle
- GUI widget creation (in test mode)
- Memory management and performance
- Event handling and propagation

### Platform Considerations
- Tests detect the current operating system
- Platform-specific tests are skipped when commands are unavailable
- Cross-platform compatibility is verified where possible
- Test mode prevents actual GUI widget creation to avoid segfaults

## Performance Benchmarks

The integration tests include performance benchmarks:
- **Large Dataset Handling**: 100-200 items should load within 1-2 seconds
- **Selection Operations**: Select all/clear should complete within 1 second
- **Memory Usage**: Should scale reasonably with data size (< 10KB per row)
- **Frequent Updates**: 20 rapid updates should complete within 5 seconds

## Error Handling Verification

Tests verify proper error handling for:
- **Component Initialization Failures**
- **System Command Execution Errors**
- **Data Validation and Formatting Errors**
- **Permission and Security Restrictions**
- **Resource Exhaustion Scenarios**
- **Concurrent Operation Conflicts**

## Maintenance Notes

### Adding New Tests
1. Follow the existing test structure and naming conventions
2. Use appropriate mocking for external dependencies
3. Include both success and failure scenarios
4. Add performance considerations for large datasets
5. Document any platform-specific requirements

### Updating Tests
1. Update tests when component interfaces change
2. Maintain backward compatibility where possible
3. Update performance benchmarks as needed
4. Keep error scenarios comprehensive and realistic

### Debugging Test Failures
1. Check mock configurations for correct return values
2. Verify component initialization order
3. Ensure proper cleanup in tearDown methods
4. Check for platform-specific issues
5. Review memory usage and resource cleanup