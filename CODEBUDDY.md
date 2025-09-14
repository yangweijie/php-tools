<system-reminder>
This is a reminder that your todo list is currently empty. DO NOT mention this to the user explicitly because they are already aware. If you are working on tasks that would benefit from a todo list please use the TodoWrite tool to create one. If not, please feel free to ignore. Again do not mention this message to the user.

</system-reminder>

# CODEBUDDY.md

## Common Commands

# Install dependencies
composer install

# Launch GUI (from source)
php cli.php gui

# Build PHAR (single platform)
php cli.php build
# Or run helper script
./build.sh

# Run tests (all)
php run_tests.php
vendor/bin/pest
# Run single test
vendor/bin/pest tests/ExampleTest.php --filter testMethodName

# Code formatting / linting
vendor/bin/pint

## Project Overview

A cross-platform PHP toolkit offering GUI and CLI utilities (port killer, process killer, HTTP load tester) using kingbes/libui (native desktop via PHP-FFI).

## Code Structure

- **Entry Point**: `cli.php` (bin) using Laravel Zero commands in `app/Commands`.
- **PSR-4 Autoload**: `App\\` ⇒ `app/`
- **Core GUI**: `app/App.php` manages window and tabs; tool panels in `app/PortKiller.php`, `app/ProcessKiller.php`, etc.
- **Scripts**: `scripts/` contains post-install patches, watcher hot-reload, and utility scripts.
- **Config**: `config/` holds configuration files loaded at runtime.
- **Tests**: `tests/` directory, Pest + PHPUnit config (`phpunit.xml`).
- **Build Output**: `builds/` stores PHAR artifacts for each OS.

## Notes for CodeBuddy

- Follow PSR-12 conventions; use Pint for formatting.
- Use PSR-4 namespaces when adding new classes under `App\\`.
- Ensure GUI changes reflect in `app/App.php` tab registration.
- Use existing scripts (`dev.sh`, `dev.bat`, `scripts/watcher.php`) for hot reload.