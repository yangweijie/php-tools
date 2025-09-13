# Changes Log

## Migration from Laravel Zero to Native PHP CLI

### Overview
This release migrates the command-line interface from Laravel Zero framework to a native PHP implementation. This change reduces dependencies, simplifies the codebase, and improves performance.

### Key Changes

1. **Removed Laravel Zero Dependency**
   - Removed `laravel-zero/framework` from composer.json
   - Eliminated unnecessary framework overhead
   - Reduced package size and complexity

2. **Native PHP CLI Implementation**
   - Created `cli.php` as the new entry point
   - Implemented custom command routing
   - Added support for `gui` and `build` commands
   - Maintained backward compatibility for existing functionality

3. **PHAR Building with Box**
   - Integrated `humbug/box` for PHAR generation
   - Updated `box.json` configuration for proper file inclusion
   - Added platform-specific build support
   - Included all necessary dependencies (kingbes/libui) in PHAR

4. **Build Process Improvements**
   - Created `build.sh` script for automated building
   - Added platform detection for cross-platform builds
   - Improved error handling and validation
   - Added automated testing of generated PHAR files

### Migration Guide

#### For Developers
- Replace `php toolkit` with `php cli.php` for development
- Use `php cli.php gui` to start the GUI application
- Use `php cli.php build` to build PHAR files

#### For Users
- Use `php builds/tools.phar gui` to run the GUI application from PHAR
- Platform-specific binaries are available in the builds directory

### Benefits

1. **Reduced Dependencies**
   - Eliminated Laravel Zero framework overhead
   - Simplified dependency tree
   - Faster installation and updates

2. **Improved Performance**
   - Lower memory footprint
   - Faster startup times
   - Reduced complexity

3. **Better Cross-platform Support**
   - Proper inclusion of native libraries in PHAR
   - Platform-specific build scripts
   - Consistent behavior across platforms

4. **Simplified Maintenance**
   - Fewer dependencies to maintain
   - Easier to understand codebase
   - Reduced potential for conflicts