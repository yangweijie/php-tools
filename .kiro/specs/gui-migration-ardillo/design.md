# Design Document

## Overview

This design document outlines the migration of the existing PHP GUI application from the `kingbes/libui` library to the `ardillo-php/ext` library. The application provides port and process management functionality through a tabbed interface, with enhanced table-based result display featuring checkbox selection capabilities.

The migration will maintain the existing functionality while improving the user interface by replacing the current checkbox-based list display with proper table components that provide better data organization and user interaction.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Main Application                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   Port Manager  │  │ Process Manager │  │ GUI Manager │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                 Ardillo PHP Extension                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │   Window    │  │    Table    │  │   Event Handlers    │ │
│  │ Management  │  │ Components  │  │                     │ │
│  └─────────────┘  └─────────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                 System Commands                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │   netstat   │  │   lsof      │  │   tasklist/ps       │ │
│  │   (ports)   │  │  (ports)    │  │   (processes)       │ │
│  └─────────────┘  └─────────────┘  └─────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Component Architecture

The application will be restructured into the following main components:

1. **Application Core** (`App\Core\Application`)
   - Main application initialization and lifecycle management
   - Window creation and management
   - Tab container management

2. **GUI Components** (`App\Gui\Components`)
   - Table component with checkbox support
   - Input controls and buttons
   - Layout containers

3. **Managers** (`App\Managers`)
   - `PortManager`: Handles port querying and killing operations
   - `ProcessManager`: Handles process querying and killing operations
   - `TableManager`: Manages table data and selection state

4. **Services** (`App\Services`)
   - `SystemCommandService`: Executes system commands for port/process operations
   - `DataFormatterService`: Formats raw command output into structured data

## Components and Interfaces

### Core Application Interface

```php
interface ApplicationInterface
{
    public function initialize(): void;
    public function addTab(string $name, ComponentInterface $component): void;
    public function run(): void;
    public function shutdown(): void;
}
```

### Table Component Interface

```php
interface TableInterface
{
    public function setColumns(array $columns): void;
    public function setData(array $data): void;
    public function getSelectedRows(): array;
    public function selectAll(): void;
    public function clearSelection(): void;
    public function refresh(): void;
}
```

### Manager Interface

```php
interface ManagerInterface
{
    public function query(string $input): array;
    public function killSelected(array $selectedIds): array;
    public function getTableColumns(): array;
}
```

### Component Structure

#### 1. Main Application Component
- **Purpose**: Initialize ardillo-php/ext, create main window, manage tabs
- **Key Methods**:
  - `initialize()`: Set up the GUI framework
  - `createMainWindow()`: Create the main application window
  - `addTab()`: Add new tabs to the interface
  - `run()`: Start the main event loop

#### 2. Table Component
- **Purpose**: Display data in tabular format with checkbox selection
- **Features**:
  - First column contains checkboxes for row selection
  - Sortable columns (if supported by ardillo-php/ext)
  - Row highlighting for selected items
  - Context menu support (right-click operations)
- **Key Methods**:
  - `setColumns()`: Define table column headers
  - `addRow()`: Add data rows to the table
  - `getSelectedRows()`: Return selected row data
  - `clearTable()`: Remove all rows from table

#### 3. Port Manager Component
- **Purpose**: Handle port-related operations
- **Features**:
  - Query ports by port number
  - Display port information in table format
  - Kill selected port processes
- **Table Columns**:
  - Checkbox (selection)
  - Port Number
  - PID
  - Protocol
  - Local Address
  - Remote Address
  - State
  - Process Name

#### 4. Process Manager Component
- **Purpose**: Handle process-related operations
- **Features**:
  - Query processes by name or PID
  - Display process information in table format
  - Kill selected processes
- **Table Columns**:
  - Checkbox (selection)
  - PID
  - Process Name
  - User/Owner
  - CPU Usage
  - Memory Usage
  - Command Line

## Data Models

### Port Data Model

```php
class PortInfo
{
    public string $port;
    public string $pid;
    public string $protocol;
    public string $localAddress;
    public string $remoteAddress;
    public string $state;
    public string $processName;
    public string $commandLine;
}
```

### Process Data Model

```php
class ProcessInfo
{
    public string $pid;
    public string $name;
    public string $user;
    public string $cpuUsage;
    public string $memoryUsage;
    public string $commandLine;
    public string $status;
}
```

### Table Row Model

```php
class TableRow
{
    public bool $selected;
    public array $data;
    public string $id; // PID for both ports and processes
}
```

## Error Handling

### Exception Hierarchy

```php
abstract class GuiException extends Exception {}

class ArdilloInitializationException extends GuiException {}
class SystemCommandException extends GuiException {}
class TableOperationException extends GuiException {}
class ProcessKillException extends GuiException {}
```

### Error Handling Strategy

1. **GUI Framework Errors**:
   - Catch ardillo-php/ext initialization failures
   - Display user-friendly error dialogs
   - Graceful degradation when possible

2. **System Command Errors**:
   - Handle command execution failures
   - Parse error output from system commands
   - Display specific error messages in the GUI

3. **Data Processing Errors**:
   - Validate command output before processing
   - Handle malformed data gracefully
   - Provide fallback display options

4. **User Input Errors**:
   - Validate port numbers and process identifiers
   - Show input validation messages
   - Prevent invalid operations

### Error Display

- Use message boxes for critical errors
- Show status messages in the application status bar
- Highlight problematic table rows with different colors
- Provide detailed error information in tooltips

## Testing Strategy

### Unit Testing

1. **Manager Classes**:
   - Test port and process querying logic
   - Mock system command execution
   - Verify data parsing and formatting

2. **Service Classes**:
   - Test system command construction
   - Verify output parsing for different OS platforms
   - Test error handling scenarios

3. **Data Models**:
   - Test data validation and serialization
   - Verify model property access and modification

### Integration Testing

1. **GUI Component Integration**:
   - Test table component with real data
   - Verify event handling between components
   - Test tab switching and state management

2. **System Command Integration**:
   - Test actual command execution on target platforms
   - Verify cross-platform compatibility
   - Test permission and security scenarios

### Platform Testing

1. **Windows Testing**:
   - Test with `netstat` and `tasklist` commands
   - Verify PowerShell command execution
   - Test process killing with `taskkill`

2. **macOS/Linux Testing**:
   - Test with `lsof` and `ps` commands
   - Verify process killing with `kill` command
   - Test different shell environments

### GUI Testing

1. **User Interaction Testing**:
   - Test checkbox selection and deselection
   - Verify table sorting and filtering
   - Test button click handlers and menu actions

2. **Layout Testing**:
   - Test window resizing behavior
   - Verify table column width adjustment
   - Test tab switching and content display

## Implementation Notes

### Ardillo PHP Extension Research

Based on the GitHub repository `https://github.com/ardillo-php/ext`, the ardillo-php/ext library provides:

- Native PHP extension for GUI development
- Cross-platform window and widget support
- Event-driven programming model
- Table/grid components with selection support
- Modern widget styling and theming

### Migration Considerations

1. **API Differences**:
   - Replace `kingbes/libui` API calls with ardillo-php/ext equivalents
   - Adapt event handling patterns to new framework
   - Update widget creation and management code

2. **Table Implementation**:
   - Replace custom checkbox layout with native table component
   - Implement proper column definitions and data binding
   - Add selection state management

3. **Cross-Platform Compatibility**:
   - Ensure ardillo-php/ext works on target platforms
   - Test native library dependencies
   - Verify system command integration remains functional

4. **Performance Considerations**:
   - Optimize table rendering for large datasets
   - Implement efficient data refresh mechanisms
   - Consider pagination for large result sets

### Development Phases

1. **Phase 1**: Core migration
   - Replace basic GUI framework
   - Migrate window and tab management
   - Implement basic table structure

2. **Phase 2**: Enhanced table functionality
   - Add checkbox selection support
   - Implement data binding and refresh
   - Add sorting and filtering capabilities

3. **Phase 3**: Advanced features
   - Add context menus and keyboard shortcuts
   - Implement batch operations
   - Add status reporting and progress indicators

4. **Phase 4**: Polish and optimization
   - Improve error handling and user feedback
   - Optimize performance for large datasets
   - Add configuration and customization options