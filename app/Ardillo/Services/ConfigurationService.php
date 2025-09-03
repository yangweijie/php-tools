<?php

namespace App\Ardillo\Services;

use App\Ardillo\Exceptions\GuiException;
use App\Ardillo\Exceptions\ComponentInitializationException;
use Psr\Log\LoggerInterface;

/**
 * Configuration service for managing user preferences and application settings
 * 
 * This service provides comprehensive configuration management for the Ardillo GUI application,
 * including user preferences, performance settings, and application behavior customization.
 * 
 * Features:
 * - Hierarchical configuration structure with dot notation access
 * - Automatic file-based persistence to user's home directory
 * - Default configuration values with validation
 * - Category-based preference management (UI, Table, Keyboard, etc.)
 * - Configuration import/export functionality
 * - Validation and error handling
 * 
 * Configuration Categories:
 * - ui: Window size, theme, notifications, auto-refresh settings
 * - table: Display options, performance thresholds, virtual scrolling
 * - keyboard: Shortcut customization and enablement
 * - performance: Optimization settings and monitoring
 * - ports: Port manager specific preferences
 * - processes: Process manager specific preferences
 * - logging: Debug and logging configuration
 * 
 * Usage Example:
 * ```php
 * $config = new ConfigurationService($logger);
 * 
 * // Get values with defaults
 * $windowWidth = $config->get('ui.window_width', 1200);
 * 
 * // Set values
 * $config->set('table.max_visible_rows', 2000);
 * 
 * // Update category preferences
 * $config->updateTablePreferences([
 *     'enable_virtual_scrolling' => true,
 *     'virtual_scroll_threshold' => 1000
 * ]);
 * 
 * // Save to file
 * $config->saveConfiguration();
 * ```
 * 
 * Configuration File Location:
 * - Default: ~/.ardillo_config.json
 * - Format: JSON with pretty printing
 * - Automatic backup and validation
 * 
 * @package App\Ardillo\Services
 * @since 1.0.0
 * @author Ardillo Development Team
 */
class ConfigurationService implements ServiceInterface
{
    private LoggerInterface $logger;
    private string $configPath;
    private array $config = [];
    private array $defaultConfig = [];

    public function __construct(LoggerInterface $logger, string $configPath = null)
    {
        $this->logger = $logger;
        $this->configPath = $configPath ?? $this->getDefaultConfigPath();
        $this->initializeDefaultConfig();
        $this->loadConfiguration();
    }

    /**
     * Get default configuration path
     */
    private function getDefaultConfigPath(): string
    {
        $homeDir = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? sys_get_temp_dir();
        return $homeDir . DIRECTORY_SEPARATOR . '.ardillo_config.json';
    }

    /**
     * Initialize default configuration values
     */
    private function initializeDefaultConfig(): void
    {
        $this->defaultConfig = [
            'ui' => [
                'theme' => 'default',
                'window_width' => 1200,
                'window_height' => 800,
                'remember_window_size' => true,
                'remember_window_position' => true,
                'auto_refresh_interval' => 0, // 0 = disabled
                'show_confirmation_dialogs' => true,
                'enable_sound_notifications' => false
            ],
            'table' => [
                'max_visible_rows' => 1000,
                'virtual_scroll_threshold' => 500,
                'enable_virtual_scrolling' => true,
                'default_sort_column' => null,
                'default_sort_direction' => 'asc',
                'remember_column_widths' => true,
                'remember_sort_preferences' => true,
                'remember_filters' => false,
                'row_height' => 'normal', // compact, normal, large
                'show_grid_lines' => true,
                'alternate_row_colors' => true
            ],
            'keyboard' => [
                'enable_shortcuts' => true,
                'custom_shortcuts' => [],
                'disable_default_shortcuts' => []
            ],
            'performance' => [
                'enable_performance_monitoring' => false,
                'log_slow_operations' => true,
                'slow_operation_threshold_ms' => 1000,
                'cache_system_commands' => true,
                'cache_duration_seconds' => 30
            ],
            'ports' => [
                'default_columns' => ['port', 'pid', 'protocol', 'state', 'process_name'],
                'column_widths' => [],
                'auto_refresh_on_tab_switch' => false,
                'show_local_ports_only' => false,
                'exclude_system_ports' => false
            ],
            'processes' => [
                'default_columns' => ['pid', 'name', 'user', 'cpu_usage', 'memory_usage'],
                'column_widths' => [],
                'auto_refresh_on_tab_switch' => false,
                'show_current_user_only' => false,
                'exclude_system_processes' => false,
                'memory_unit' => 'MB' // KB, MB, GB
            ],
            'logging' => [
                'log_level' => 'info',
                'log_to_file' => true,
                'log_file_path' => null,
                'max_log_file_size_mb' => 10,
                'keep_log_files' => 5
            ]
        ];
    }

    /**
     * Load configuration from file
     */
    private function loadConfiguration(): void
    {
        try {
            if (file_exists($this->configPath)) {
                $configData = file_get_contents($this->configPath);
                $loadedConfig = json_decode($configData, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($loadedConfig)) {
                    $this->config = array_replace_recursive($this->defaultConfig, $loadedConfig);
                    $this->logger->info('Configuration loaded successfully', [
                        'config_path' => $this->configPath,
                        'config_keys' => array_keys($this->config)
                    ]);
                } else {
                    $this->logger->warning('Invalid configuration file, using defaults', [
                        'config_path' => $this->configPath,
                        'json_error' => json_last_error_msg()
                    ]);
                    $this->config = $this->defaultConfig;
                }
            } else {
                $this->logger->info('Configuration file not found, using defaults', [
                    'config_path' => $this->configPath
                ]);
                $this->config = $this->defaultConfig;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error loading configuration', [
                'config_path' => $this->configPath,
                'error' => $e->getMessage()
            ]);
            $this->config = $this->defaultConfig;
        }
    }

    /**
     * Save configuration to file
     */
    public function saveConfiguration(): bool
    {
        try {
            $configData = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            if ($configData === false) {
                throw new ComponentInitializationException('Failed to encode configuration data');
            }
            
            $result = file_put_contents($this->configPath, $configData);
            
            if ($result === false) {
                throw new ComponentInitializationException('Failed to write configuration file');
            }
            
            $this->logger->info('Configuration saved successfully', [
                'config_path' => $this->configPath,
                'bytes_written' => $result
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Error saving configuration', [
                'config_path' => $this->configPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get configuration value
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Set configuration value
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }
        
        $this->logger->debug('Configuration value set', [
            'key' => $key,
            'value' => $value
        ]);
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return false;
            }
            $value = $value[$k];
        }
        
        return true;
    }

    /**
     * Remove configuration key
     */
    public function remove(string $key): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!isset($config[$keys[$i]]) || !is_array($config[$keys[$i]])) {
                return;
            }
            $config = &$config[$keys[$i]];
        }
        
        unset($config[end($keys)]);
        
        $this->logger->debug('Configuration key removed', [
            'key' => $key
        ]);
    }

    /**
     * Get all configuration
     */
    public function getAll(): array
    {
        return $this->config;
    }

    /**
     * Reset configuration to defaults
     */
    public function resetToDefaults(): void
    {
        $this->config = $this->defaultConfig;
        $this->logger->info('Configuration reset to defaults');
    }

    /**
     * Reset specific section to defaults
     */
    public function resetSectionToDefaults(string $section): void
    {
        if (isset($this->defaultConfig[$section])) {
            $this->config[$section] = $this->defaultConfig[$section];
            $this->logger->info('Configuration section reset to defaults', [
                'section' => $section
            ]);
        }
    }

    /**
     * Get UI preferences
     */
    public function getUiPreferences(): array
    {
        return $this->get('ui', []);
    }

    /**
     * Get table preferences
     */
    public function getTablePreferences(): array
    {
        return $this->get('table', []);
    }

    /**
     * Get keyboard preferences
     */
    public function getKeyboardPreferences(): array
    {
        return $this->get('keyboard', []);
    }

    /**
     * Get performance preferences
     */
    public function getPerformancePreferences(): array
    {
        return $this->get('performance', []);
    }

    /**
     * Get port manager preferences
     */
    public function getPortPreferences(): array
    {
        return $this->get('ports', []);
    }

    /**
     * Get process manager preferences
     */
    public function getProcessPreferences(): array
    {
        return $this->get('processes', []);
    }

    /**
     * Update UI preferences
     */
    public function updateUiPreferences(array $preferences): void
    {
        $current = $this->getUiPreferences();
        $this->set('ui', array_merge($current, $preferences));
    }

    /**
     * Update table preferences
     */
    public function updateTablePreferences(array $preferences): void
    {
        $current = $this->getTablePreferences();
        $this->set('table', array_merge($current, $preferences));
    }

    /**
     * Update keyboard preferences
     */
    public function updateKeyboardPreferences(array $preferences): void
    {
        $current = $this->getKeyboardPreferences();
        $this->set('keyboard', array_merge($current, $preferences));
    }

    /**
     * Update performance preferences
     */
    public function updatePerformancePreferences(array $preferences): void
    {
        $current = $this->getPerformancePreferences();
        $this->set('performance', array_merge($current, $preferences));
    }

    /**
     * Update port manager preferences
     */
    public function updatePortPreferences(array $preferences): void
    {
        $current = $this->getPortPreferences();
        $this->set('ports', array_merge($current, $preferences));
    }

    /**
     * Update process manager preferences
     */
    public function updateProcessPreferences(array $preferences): void
    {
        $current = $this->getProcessPreferences();
        $this->set('processes', array_merge($current, $preferences));
    }

    /**
     * Export configuration to array
     */
    public function exportConfiguration(): array
    {
        return [
            'version' => '1.0',
            'exported_at' => date('c'),
            'config' => $this->config
        ];
    }

    /**
     * Import configuration from array
     */
    public function importConfiguration(array $configData): bool
    {
        try {
            if (!isset($configData['config']) || !is_array($configData['config'])) {
                throw new ComponentInitializationException('Invalid configuration data format');
            }
            
            $this->config = array_replace_recursive($this->defaultConfig, $configData['config']);
            
            $this->logger->info('Configuration imported successfully', [
                'version' => $configData['version'] ?? 'unknown',
                'exported_at' => $configData['exported_at'] ?? 'unknown'
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Error importing configuration', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate configuration structure
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        
        // Validate UI preferences
        $ui = $this->get('ui', []);
        if (isset($ui['window_width']) && (!is_int($ui['window_width']) || $ui['window_width'] < 400)) {
            $errors[] = 'Invalid window width: must be integer >= 400';
        }
        if (isset($ui['window_height']) && (!is_int($ui['window_height']) || $ui['window_height'] < 300)) {
            $errors[] = 'Invalid window height: must be integer >= 300';
        }
        
        // Validate table preferences
        $table = $this->get('table', []);
        if (isset($table['max_visible_rows']) && (!is_int($table['max_visible_rows']) || $table['max_visible_rows'] < 10)) {
            $errors[] = 'Invalid max_visible_rows: must be integer >= 10';
        }
        if (isset($table['virtual_scroll_threshold']) && (!is_int($table['virtual_scroll_threshold']) || $table['virtual_scroll_threshold'] < 100)) {
            $errors[] = 'Invalid virtual_scroll_threshold: must be integer >= 100';
        }
        
        // Validate performance preferences
        $performance = $this->get('performance', []);
        if (isset($performance['slow_operation_threshold_ms']) && (!is_int($performance['slow_operation_threshold_ms']) || $performance['slow_operation_threshold_ms'] < 100)) {
            $errors[] = 'Invalid slow_operation_threshold_ms: must be integer >= 100';
        }
        
        return $errors;
    }

    /**
     * Get configuration file path
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * Check if configuration file exists
     */
    public function configFileExists(): bool
    {
        return file_exists($this->configPath);
    }

    /**
     * Get configuration file size
     */
    public function getConfigFileSize(): int
    {
        return file_exists($this->configPath) ? filesize($this->configPath) : 0;
    }

    /**
     * Get configuration file modification time
     */
    public function getConfigFileModTime(): int
    {
        return file_exists($this->configPath) ? filemtime($this->configPath) : 0;
    }

    /**
     * Initialize the configuration service
     */
    public function initialize(): void
    {
        // Configuration is already loaded in constructor
        // This method is here to satisfy the ServiceInterface
        $this->logger->debug('Configuration service initialized', [
            'config_path' => $this->configPath,
            'config_exists' => $this->configFileExists()
        ]);
    }

    /**
     * Check if the configuration service is available
     */
    public function isAvailable(): bool
    {
        // Configuration service is always available
        // It will use defaults if the config file is not accessible
        return true;
    }
}