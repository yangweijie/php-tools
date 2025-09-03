# Enhanced Features Documentation

This document describes the enhanced features added to the Ardillo GUI application as part of the final polish and optimization phase.

## Table of Contents

1. [Table Sorting and Filtering](#table-sorting-and-filtering)
2. [Keyboard Shortcuts](#keyboard-shortcuts)
3. [Performance Optimizations](#performance-optimizations)
4. [Configuration System](#configuration-system)
5. [Usage Examples](#usage-examples)
6. [API Reference](#api-reference)

## Table Sorting and Filtering

### Sorting Features

The enhanced TableComponent now supports column-based sorting with the following capabilities:

- **Multi-type sorting**: Automatic detection of numeric vs. text data
- **Ascending/Descending**: Toggle between sort directions
- **Persistent sorting**: Sort preferences are maintained during data updates
- **API-based sorting**: Programmatic control over sorting behavior

#### Usage Example

```php
// Sort by port number in ascending order
$table->sortByColumn('port', 'asc');

// Sort by process name in descending order
$table->sortByColumn('process_name', 'desc');

// Get current sort information
$sortInfo = $table->getSortInfo();
// Returns: ['column' => 'port', 'direction' => 'asc']
```

### Filtering Features

Advanced filtering system with multiple operators and real-time application:

- **Multiple filter operators**: contains, equals, starts_with, ends_with, greater_than, less_than, not_empty, empty
- **Multi-column filtering**: Apply filters to multiple columns simultaneously
- **Real-time filtering**: Filters are applied immediately as data changes
- **Filter persistence**: Filters remain active during data refreshes

#### Usage Example

```php
// Filter ports containing "80"
$table->addFilter('port', '80', 'contains');

// Filter processes by specific user
$table->addFilter('user', 'admin', 'equals');

// Filter by CPU usage greater than 50%
$table->addFilter('cpu_usage', '50', 'greater_than');

// Remove specific filter
$table->removeFilter('port');

// Clear all filters
$table->clearFilters();

// Get current filters
$filters = $table->getFilters();
```

### Filter Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `contains` | Cell value contains filter value | "localhost" contains "local" |
| `equals` | Exact match (case-insensitive) | "admin" equals "admin" |
| `starts_with` | Cell value starts with filter value | "192.168.1.1" starts with "192" |
| `ends_with` | Cell value ends with filter value | "process.exe" ends with ".exe" |
| `greater_than` | Numeric comparison (>) | CPU usage > 50 |
| `less_than` | Numeric comparison (<) | Memory < 100MB |
| `not_empty` | Cell has non-empty value | Any non-blank cell |
| `empty` | Cell is empty or whitespace only | Blank cells |

## Keyboard Shortcuts

### Default Shortcuts

The application includes comprehensive keyboard shortcuts for efficient operation:

| Shortcut | Action | Description |
|----------|--------|-------------|
| `Ctrl+R` | Refresh Current Tab | Refresh data in the active tab |
| `Ctrl+A` | Select All | Select all items in current table |
| `Ctrl+D` | Clear Selection | Clear all selections |
| `Ctrl+K` | Kill Selected | Kill selected ports/processes |
| `Ctrl+F` | Focus Search | Focus the search input field |
| `Ctrl+1` | Switch to Port Tab | Switch to port management |
| `Ctrl+2` | Switch to Process Tab | Switch to process management |
| `F5` | Refresh Current Tab | Alternative refresh shortcut |
| `Delete` | Kill Selected | Alternative kill shortcut |
| `Escape` | Clear Selection/Filters | Clear selection or active filters |
| `Ctrl+Shift+A` | Select All Filtered | Select all currently filtered items |

### Custom Shortcuts

You can add custom keyboard shortcuts programmatically:

```php
// Add custom shortcut
$mainApp->addKeyboardShortcut('Ctrl+Shift+R', 'refresh_all_tabs');

// Remove shortcut
$mainApp->removeKeyboardShortcut('Ctrl+K');

// Get all shortcuts
$shortcuts = $mainApp->getKeyboardShortcuts();
```

### Shortcut Context

Shortcuts are context-aware and behave differently based on the active tab:

- **Port Manager Tab**: Shortcuts operate on port data and operations
- **Process Manager Tab**: Shortcuts operate on process data and operations
- **Global Shortcuts**: Tab switching and application-level operations work from any context

## Performance Optimizations

### Virtual Scrolling

For large datasets, the table automatically enables virtual scrolling to maintain smooth performance:

- **Automatic activation**: Enabled when row count exceeds threshold (default: 500 rows)
- **Configurable thresholds**: Customize when virtual scrolling activates
- **Visible row limiting**: Only renders visible rows plus buffer
- **Smooth scrolling**: Maintains scroll position and selection state

#### Configuration

```php
// Enable virtual scrolling
$table->enableVirtualScrolling(true);

// Set threshold for activation
$table->setVirtualScrollThreshold(1000);

// Set maximum visible rows
$table->setMaxVisibleRows(500);

// Scroll to specific row
$table->scrollToRow(250);
```

### Performance Monitoring

The system includes built-in performance monitoring:

```php
// Get performance statistics
$stats = $table->getPerformanceStats();

/*
Returns:
[
    'total_rows' => 1500,
    'filtered_rows' => 750,
    'selected_rows' => 5,
    'virtual_scroll_enabled' => true,
    'virtual_scroll_threshold' => 500,
    'max_visible_rows' => 1000,
    'current_visible_rows' => 50,
    'scroll_offset' => 100,
    'filters_active' => true,
    'sort_active' => true
]
*/
```

### Memory Optimization

- **Lazy loading**: Data is loaded only when needed
- **Efficient filtering**: Filters are applied without duplicating data
- **Selection tracking**: Minimal memory overhead for selection state
- **Garbage collection**: Automatic cleanup of unused resources

## Configuration System

### Configuration Categories

The configuration system organizes settings into logical categories:

1. **UI Preferences**: Window size, theme, notifications
2. **Table Preferences**: Display options, performance settings
3. **Keyboard Preferences**: Shortcut customization
4. **Performance Preferences**: Optimization settings
5. **Port Manager Preferences**: Port-specific settings
6. **Process Manager Preferences**: Process-specific settings
7. **Logging Preferences**: Debug and logging options

### Configuration File

Settings are automatically saved to `~/.ardillo_config.json`:

```json
{
  "ui": {
    "theme": "default",
    "window_width": 1200,
    "window_height": 800,
    "remember_window_size": true,
    "auto_refresh_interval": 0,
    "show_confirmation_dialogs": true
  },
  "table": {
    "max_visible_rows": 1000,
    "virtual_scroll_threshold": 500,
    "enable_virtual_scrolling": true,
    "remember_sort_preferences": true,
    "row_height": "normal",
    "show_grid_lines": true
  },
  "keyboard": {
    "enable_shortcuts": true,
    "custom_shortcuts": {},
    "disable_default_shortcuts": []
  }
}
```

### Configuration API

```php
// Get configuration service
$config = new ConfigurationService($logger);

// Get specific values
$windowWidth = $config->get('ui.window_width', 1200);
$enableShortcuts = $config->get('keyboard.enable_shortcuts', true);

// Set values
$config->set('ui.theme', 'dark');
$config->set('table.max_visible_rows', 2000);

// Save configuration
$config->saveConfiguration();

// Reset to defaults
$config->resetToDefaults();
$config->resetSectionToDefaults('ui');

// Validate configuration
$errors = $config->validateConfiguration();
```

### Preference Categories

#### UI Preferences

```php
$uiPrefs = $config->getUiPreferences();
$config->updateUiPreferences([
    'theme' => 'dark',
    'window_width' => 1400,
    'show_confirmation_dialogs' => false
]);
```

#### Table Preferences

```php
$tablePrefs = $config->getTablePreferences();
$config->updateTablePreferences([
    'max_visible_rows' => 2000,
    'enable_virtual_scrolling' => true,
    'row_height' => 'compact'
]);
```

## Usage Examples

### Complete Workflow Example

```php
// Initialize with configuration
$config = new ConfigurationService($logger);
$mainApp = new MainGuiApplication($portManager, $processManager, $logger, $config);

// Apply user preferences
$tablePrefs = $config->getTablePreferences();
$portTable = $mainApp->getPortPanel()->getPortTable();
$portTable->setMaxVisibleRows($tablePrefs['max_visible_rows']);
$portTable->enableVirtualScrolling($tablePrefs['enable_virtual_scrolling']);

// Set up custom keyboard shortcuts
if ($config->get('keyboard.enable_shortcuts', true)) {
    $customShortcuts = $config->get('keyboard.custom_shortcuts', []);
    foreach ($customShortcuts as $shortcut => $action) {
        $mainApp->addKeyboardShortcut($shortcut, $action);
    }
}

// Start the application
$mainApp->start();
```

### Advanced Filtering Example

```php
// Complex filtering scenario
$processTable = $mainApp->getProcessPanel()->getProcessTable();

// Filter for high CPU usage processes
$processTable->addFilter('cpu_usage', '50', 'greater_than');

// Filter for specific user processes
$processTable->addFilter('user', 'admin', 'equals');

// Filter for processes with names containing "service"
$processTable->addFilter('name', 'service', 'contains');

// Sort by memory usage (descending)
$processTable->sortByColumn('memory_usage', 'desc');

// Select all filtered results
$filteredRows = $processTable->getFilteredRows();
foreach ($filteredRows as $row) {
    $processTable->setRowSelected($row->getId(), true);
}
```

### Performance Monitoring Example

```php
// Monitor table performance
$stats = $table->getPerformanceStats();

if ($stats['total_rows'] > 1000) {
    $logger->info('Large dataset detected', [
        'total_rows' => $stats['total_rows'],
        'virtual_scroll_enabled' => $stats['virtual_scroll_enabled']
    ]);
    
    if (!$stats['virtual_scroll_enabled']) {
        $table->enableVirtualScrolling(true);
        $logger->info('Virtual scrolling enabled for performance');
    }
}

// Log filter usage
if ($stats['filters_active']) {
    $filters = $table->getFilters();
    $logger->debug('Active filters', [
        'filter_count' => count($filters),
        'filtered_rows' => $stats['filtered_rows'],
        'total_rows' => $stats['total_rows']
    ]);
}
```

## API Reference

### TableComponent Enhanced Methods

#### Sorting Methods

- `sortByColumn(string $columnKey, string $direction = 'asc'): void`
- `getSortInfo(): array`

#### Filtering Methods

- `addFilter(string $columnKey, string $value, string $operator = 'contains'): void`
- `removeFilter(string $columnKey): void`
- `clearFilters(): void`
- `getFilters(): array`
- `getFilteredRows(): array`

#### Performance Methods

- `enableVirtualScrolling(bool $enabled = true): void`
- `setMaxVisibleRows(int $maxRows): void`
- `setVirtualScrollThreshold(int $threshold): void`
- `scrollToRow(int $rowIndex): void`
- `getPerformanceStats(): array`

### MainGuiApplication Enhanced Methods

#### Keyboard Shortcut Methods

- `handleKeyboardShortcut(string $shortcut): bool`
- `getKeyboardShortcuts(): array`
- `addKeyboardShortcut(string $shortcut, string $action): void`
- `removeKeyboardShortcut(string $shortcut): void`

### ConfigurationService Methods

#### Core Methods

- `get(string $key, $default = null): mixed`
- `set(string $key, $value): void`
- `has(string $key): bool`
- `remove(string $key): void`
- `saveConfiguration(): bool`

#### Preference Methods

- `getUiPreferences(): array`
- `getTablePreferences(): array`
- `getKeyboardPreferences(): array`
- `updateUiPreferences(array $preferences): void`
- `updateTablePreferences(array $preferences): void`
- `updateKeyboardPreferences(array $preferences): void`

#### Utility Methods

- `resetToDefaults(): void`
- `validateConfiguration(): array`
- `exportConfiguration(): array`
- `importConfiguration(array $configData): bool`

## Best Practices

### Performance

1. **Enable virtual scrolling** for datasets > 500 rows
2. **Use appropriate filters** to reduce visible data
3. **Monitor performance stats** regularly
4. **Configure reasonable thresholds** based on system capabilities

### User Experience

1. **Provide keyboard shortcuts** for common operations
2. **Remember user preferences** across sessions
3. **Show confirmation dialogs** for destructive operations
4. **Provide visual feedback** for long-running operations

### Configuration

1. **Validate configuration** on startup
2. **Provide sensible defaults** for all settings
3. **Allow configuration reset** when needed
4. **Document configuration options** clearly

### Error Handling

1. **Log performance issues** for debugging
2. **Gracefully handle** configuration errors
3. **Provide user feedback** for invalid operations
4. **Maintain application stability** during errors