# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP tools collection built with the kingbes/libui GUI library. It includes system management tools (port killer, process killer) and HTTP load testing tools with cross-platform support.

## Code Architecture

### Main Components

1. **Core Application**: 
   - `app/App.php` - Main GUI application class that manages the window and tab structure
   - `app/Commands/Gui.php` - Main entry point that initializes the application and adds tabs

2. **System Tools**:
   - `app/PortKiller.php` - Port killing utility with GUI interface
   - `app/ProcessKiller.php` - Process killing utility with GUI interface
   - `app/ExampleTab.php` - Example tab implementation

3. **GUI Framework**:
   - Built on kingbes/libui PHP-FFI bindings for libui
   - Cross-platform desktop GUI with native look and feel
   - Supports Windows, macOS, and Linux (x86_64 architecture only)

### Key Features

- Graphical interface based on libui native desktop application
- System tools: Port killing, process killing
- Example tab for reference implementation

## Common Development Commands

### Installation
```bash
composer install
```

### Running the Application
```bash
# Start the GUI toolkit application
php toolkit gui
```

### Running Tests
```bash
# Run all tests
php run_tests.php

# Or run with Pest directly
./vendor/bin/pest
```

### Code Formatting
```bash
# Format code with Laravel Pint
./vendor/bin/pint
```

## Development Notes

### GUI Development
- Uses kingbes/libui PHP-FFI bindings for native GUI components
- Main window with tabbed interface for different tools
- Components include: Labels, Buttons, Entry fields, Checkboxes, Boxes (layout containers)
- Event-driven programming model with callback functions

### System Compatibility
- GUI version requires PHP 8.2+ with FFI extension
- GUI version only supports x86_64 architecture (no ARM64/Apple Silicon support)
- Command-line tools have broader compatibility (PHP 8.0+)
- Cross-platform support for Windows, macOS, and Linux

### Code Structure
- PSR-4 autoloading with `App\` namespace mapped to `app/` directory
- Object-oriented design with classes for each major component
- System command execution for process and port management
- Platform-specific code paths for Windows vs. Unix-like systems

### Building Executables
```bash
# Build PHAR executable with Box
php toolkit app:build tools
```

## Dependencies

- kingbes/libui: GUI framework (PHP-FFI bindings for libui)
- laravel-zero/framework: CLI framework
- guzzlehttp/guzzle: HTTP client for load testing
- ext-json, ext-curl: Required PHP extensions
- pestphp/pest: Testing framework
- laravel/pint: Code formatting