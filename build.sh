#!/bin/bash

# Build script for creating PHAR files for different platforms

# Create builds directory if it doesn't exist
mkdir -p builds

echo "Building PHAR file for current platform..."

# Run the build command
php cli.php build

# Check if build was successful
if [ -f "builds/tools.phar" ]; then
    echo "Build successful! PHAR file created at builds/tools.phar"
    
    # Test the PHAR file help command
    echo "Testing the PHAR file help command..."
    php builds/tools.phar help
    
    # Make a copy with platform-specific names
    if [[ "$OSTYPE" == "darwin"* ]]; then
        cp builds/tools.phar builds/tools-macos.phar
        echo "Created macOS specific build: builds/tools-macos.phar"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        cp builds/tools.phar builds/tools-linux.phar
        echo "Created Linux specific build: builds/tools-linux.phar"
    elif [[ "$OSTYPE" == "msys"* ]] || [[ "$OSTYPE" == "win32"* ]]; then
        cp builds/tools.phar builds/tools-windows.phar
        echo "Created Windows specific build: builds/tools-windows.phar"
    fi
    
    echo "All builds completed successfully!"
else
    echo "Build failed!"
    exit 1
fi