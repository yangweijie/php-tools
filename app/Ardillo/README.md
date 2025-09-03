# Ardillo GUI Framework Structure

This directory contains the new ardillo-php/ext based GUI implementation for the port and process management tool.

## Directory Structure

```
app/Ardillo/
├── Core/                   # Core application classes
│   ├── Application.php     # Abstract base application class
│   └── ApplicationInterface.php
├── Components/             # GUI component classes
│   ├── BaseComponent.php   # Abstract base component
│   ├── ComponentInterface.php
│   └── TableInterface.php  # Table-specific interface
├── Managers/              # Business logic managers
│   ├── BaseManager.php    # Abstract base manager
│   └── ManagerInterface.php
├── Services/              # Service layer classes
│   └── ServiceInterface.php
├── Models/                # Data model classes
│   ├── BaseModel.php      # Abstract base model
│   ├── ModelInterface.php
│   └── TableRow.php       # Table row model with selection
├── Exceptions/            # Custom exception classes
│   ├── GuiException.php   # Base GUI exception
│   ├── ArdilloInitializationException.php
│   ├── SystemCommandException.php
│   ├── TableOperationException.php
│   └── ProcessKillException.php
└── README.md             # This file
```

## Architecture Overview

The new implementation follows a clean architecture pattern with clear separation of concerns:

- **Core**: Application lifecycle and framework initialization
- **Components**: GUI widgets and user interface elements
- **Managers**: Business logic for port and process operations
- **Services**: System integration and external dependencies
- **Models**: Data structures and validation
- **Exceptions**: Error handling and user feedback

## Key Interfaces

### ApplicationInterface
Defines the main application lifecycle methods for initialization, tab management, and event loop control.

### ComponentInterface
Base interface for all GUI components with initialization, widget access, and cleanup methods.

### TableInterface
Specialized interface for table components with selection support, data binding, and refresh capabilities.

### ManagerInterface
Interface for business logic managers that handle querying, killing operations, and table column definitions.

### ModelInterface
Interface for data models with validation, serialization, and identification methods.

## Usage

This structure provides the foundation for implementing the ardillo-based GUI. Concrete implementations will extend the abstract base classes and implement the interfaces according to the specific requirements of each component.

The design ensures:
- Type safety through interfaces
- Consistent error handling through custom exceptions
- Separation of GUI logic from business logic
- Testable components through dependency injection
- Cross-platform compatibility through abstraction