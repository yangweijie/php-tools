# PHP Tools Collection

A cross-platform PHP tools collection with GUI interface built using the kingbes/libui library. This toolkit includes system management utilities and HTTP load testing tools.

## Features

- **Port Killer**: Identify and terminate processes occupying specific ports
  - Scan ports by number to find associated processes
  - Display process details including PID, user, and command
  - Selectively terminate processes with checkboxes
  - Cross-platform support (Windows, macOS, Linux)

- **Process Killer**: Find and terminate running processes by name or PID
  - Search processes by name or PID
  - Display detailed process information
  - Selective termination with bulk operations
  - Cross-platform support (Windows, macOS, Linux)

- **Cross-platform GUI**: Native desktop application with tabbed interface
  - Built with kingbes/libui PHP-FFI bindings
  - Native look and feel on all platforms
  - Intuitive tabbed interface for different tools
  - Responsive design with proper layout management

## System Requirements

- PHP 8.2+ with FFI extension
- Windows, macOS, or Linux (x86_64 architecture only)
- For GUI functionality: libui library dependencies

## Installation

### From Source

```bash
# Clone the repository
git clone https://github.com/yangweijie/php-tools.git
cd php-tools

# Install dependencies
composer install
```

### Download Pre-built Binaries

Pre-built binaries are available for download from the GitHub Releases page for:
- Windows (x86_64)
- macOS (x86_64)
- Linux (x86_64)

## Usage

### Running the GUI Application

#### From Source
```bash
# When running from source
php cli.php gui
```

#### From PHAR Package
```bash
# When using the PHAR package
php builds/tools.phar gui
```

### Command Line Options

```bash
# Show help
./tools --help

# Show version
./tools --version
```

## Building from Source

### Requirements for Building
- PHP 8.2+ with FFI extension
- Composer

### Build Process

```bash
# Install dependencies
composer install

# Build PHAR executable
php cli.php build

# Or use the build script
./build.sh

# The built executable will be available in the builds/ directory
```

## Development

### Project Structure
```
app/                 # Application source code
├── App.php         # Main GUI application class
├── PortKiller.php  # Port killing utility
├── ProcessKiller.php # Process killing utility
├── ProcessRow.php  # Process row component
├── ExampleTab.php  # Example tab component
├── Commands/       # CLI commands
└── Providers/      # Service providers
bootstrap/          # Application bootstrap files
config/             # Configuration files
tests/              # Test files
builds/             # Build output directory
```

### Running Tests
```bash
php run_tests.php
```

### Development Commands
```bash
# Install dependencies
composer install

# Run tests
composer test

# Code formatting
composer format

# Build PHAR executable
php cli.php build
```

### Cross-platform Builds

The GitHub Actions workflow automatically builds binaries for all supported platforms:
- Linux (x86_64)
- Windows (x86_64)
- macOS (x86_64)

## GitHub Actions

This repository includes GitHub Actions for automated building and releasing:

- **Build and Release**: Automatically builds binaries for all platforms when a new tag is pushed
- **Continuous Integration**: Runs tests on every push and pull request

### Creating a New Release

To create a new release with pre-built binaries:

1. Create and push a new tag:
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```

2. The GitHub Action will automatically:
   - Build binaries for Linux, Windows, and macOS
   - Create a new release on GitHub
   - Attach all platform binaries to the release

### Manual Release Trigger

You can also manually trigger the build workflow:
1. Go to the "Actions" tab in your GitHub repository
2. Select "Build and Release" workflow
3. Click "Run workflow" and confirm

### Downloading Binaries

Pre-built binaries are available from the GitHub Releases page:
- **Windows**: `tools-windows.exe`
- **macOS**: `tools-macos`
- **Linux**: `tools-linux`

Download the appropriate binary for your platform and run it directly without any installation.

## Dependencies

- [kingbes/libui](https://github.com/kingbes/php-libui) - PHP-FFI bindings for libui
- [humbug/box](https://github.com/humbug/box) - PHAR builder
- [pestphp/pest](https://github.com/pestphp/pest) - Testing framework

## License

This project is open-source software licensed under the MIT license.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.