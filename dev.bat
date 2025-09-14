@echo off
REM Hot Reload Development Script for PHP Tools (Windows)
REM Usage: dev.bat

echo Starting PHP Tools with hot reload...
echo Any changes to PHP files in the app/ directory will automatically restart the application.
echo Press Ctrl+C to stop.

php scripts/watcher.php gui --watch