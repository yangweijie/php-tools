<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Ardillo\Services\ConfigurationService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Test configuration service functionality (Task 15)
 * 
 * Tests the configuration management system added as part of the final polish phase.
 */
class ConfigurationServiceTest extends TestCase
{
    private ConfigurationService $config;
    private string $testConfigPath;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = new NullLogger();
        $this->testConfigPath = sys_get_temp_dir() . '/test_ardillo_config_' . uniqid() . '.json';
        $this->config = new ConfigurationService($this->logger, $this->testConfigPath);
    }

    protected function tearDown(): void
    {
        // Clean up test config file
        if (file_exists($this->testConfigPath)) {
            unlink($this->testConfigPath);
        }
        
        parent::tearDown();
    }

    /**
     * Test basic get/set functionality
     */
    public function test_basic_get_set(): void
    {
        // Test setting and getting simple values
        $this->config->set('test.value', 'hello');
        $this->assertEquals('hello', $this->config->get('test.value'));
        
        // Test default values
        $this->assertEquals('default', $this->config->get('nonexistent.key', 'default'));
        $this->assertNull($this->config->get('nonexistent.key'));
    }

    /**
     * Test nested configuration paths
     */
    public function test_nested_paths(): void
    {
        $this->config->set('ui.window.width', 1200);
        $this->config->set('ui.window.height', 800);
        $this->config->set('ui.theme', 'dark');
        
        $this->assertEquals(1200, $this->config->get('ui.window.width'));
        $this->assertEquals(800, $this->config->get('ui.window.height'));
        $this->assertEquals('dark', $this->config->get('ui.theme'));
    }

    /**
     * Test has() method
     */
    public function test_has_method(): void
    {
        $this->config->set('existing.key', 'value');
        
        $this->assertTrue($this->config->has('existing.key'));
        $this->assertFalse($this->config->has('nonexistent.key'));
        $this->assertFalse($this->config->has('existing.nonexistent'));
    }

    /**
     * Test remove() method
     */
    public function test_remove_method(): void
    {
        $this->config->set('removable.key', 'value');
        $this->assertTrue($this->config->has('removable.key'));
        
        $this->config->remove('removable.key');
        $this->assertFalse($this->config->has('removable.key'));
    }

    /**
     * Test default configuration values
     */
    public function test_default_configuration(): void
    {
        // Test that default values are loaded
        $this->assertEquals('default', $this->config->get('ui.theme'));
        $this->assertEquals(1200, $this->config->get('ui.window_width'));
        $this->assertEquals(800, $this->config->get('ui.window_height'));
        $this->assertEquals(1000, $this->config->get('table.max_visible_rows'));
        $this->assertEquals(500, $this->config->get('table.virtual_scroll_threshold'));
        $this->assertTrue($this->config->get('keyboard.enable_shortcuts'));
    }

    /**
     * Test preference category methods
     */
    public function test_preference_categories(): void
    {
        // Test UI preferences
        $uiPrefs = $this->config->getUiPreferences();
        $this->assertIsArray($uiPrefs);
        $this->assertArrayHasKey('theme', $uiPrefs);
        $this->assertArrayHasKey('window_width', $uiPrefs);
        
        // Test updating UI preferences
        $this->config->updateUiPreferences(['theme' => 'dark', 'window_width' => 1400]);
        $this->assertEquals('dark', $this->config->get('ui.theme'));
        $this->assertEquals(1400, $this->config->get('ui.window_width'));
        
        // Test table preferences
        $tablePrefs = $this->config->getTablePreferences();
        $this->assertIsArray($tablePrefs);
        $this->assertArrayHasKey('max_visible_rows', $tablePrefs);
        
        // Test updating table preferences
        $this->config->updateTablePreferences(['max_visible_rows' => 2000]);
        $this->assertEquals(2000, $this->config->get('table.max_visible_rows'));
    }

    /**
     * Test keyboard preferences
     */
    public function test_keyboard_preferences(): void
    {
        $keyboardPrefs = $this->config->getKeyboardPreferences();
        $this->assertIsArray($keyboardPrefs);
        $this->assertTrue($keyboardPrefs['enable_shortcuts']);
        
        // Test custom shortcuts
        $customShortcuts = ['Ctrl+X' => 'custom_action'];
        $this->config->updateKeyboardPreferences(['custom_shortcuts' => $customShortcuts]);
        
        $updatedPrefs = $this->config->getKeyboardPreferences();
        $this->assertEquals($customShortcuts, $updatedPrefs['custom_shortcuts']);
    }

    /**
     * Test performance preferences
     */
    public function test_performance_preferences(): void
    {
        $perfPrefs = $this->config->getPerformancePreferences();
        $this->assertIsArray($perfPrefs);
        $this->assertArrayHasKey('enable_performance_monitoring', $perfPrefs);
        $this->assertArrayHasKey('slow_operation_threshold_ms', $perfPrefs);
        
        $this->config->updatePerformancePreferences([
            'enable_performance_monitoring' => true,
            'slow_operation_threshold_ms' => 2000
        ]);
        
        $this->assertTrue($this->config->get('performance.enable_performance_monitoring'));
        $this->assertEquals(2000, $this->config->get('performance.slow_operation_threshold_ms'));
    }

    /**
     * Test port and process preferences
     */
    public function test_port_and_process_preferences(): void
    {
        // Test port preferences
        $portPrefs = $this->config->getPortPreferences();
        $this->assertIsArray($portPrefs);
        $this->assertArrayHasKey('default_columns', $portPrefs);
        
        $this->config->updatePortPreferences(['show_local_ports_only' => true]);
        $this->assertTrue($this->config->get('ports.show_local_ports_only'));
        
        // Test process preferences
        $processPrefs = $this->config->getProcessPreferences();
        $this->assertIsArray($processPrefs);
        $this->assertArrayHasKey('default_columns', $processPrefs);
        
        $this->config->updateProcessPreferences(['memory_unit' => 'GB']);
        $this->assertEquals('GB', $this->config->get('processes.memory_unit'));
    }

    /**
     * Test configuration validation
     */
    public function test_configuration_validation(): void
    {
        // Test with valid configuration
        $errors = $this->config->validateConfiguration();
        $this->assertEmpty($errors);
        
        // Test with invalid values
        $this->config->set('ui.window_width', 100); // Too small
        $this->config->set('ui.window_height', 'invalid'); // Not numeric
        $this->config->set('table.max_visible_rows', -5); // Negative
        
        $errors = $this->config->validateConfiguration();
        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid window width: must be integer >= 400', $errors);
    }

    /**
     * Test reset to defaults
     */
    public function test_reset_to_defaults(): void
    {
        // Modify some values
        $this->config->set('ui.theme', 'custom');
        $this->config->set('table.max_visible_rows', 5000);
        
        $this->assertEquals('custom', $this->config->get('ui.theme'));
        $this->assertEquals(5000, $this->config->get('table.max_visible_rows'));
        
        // Reset to defaults
        $this->config->resetToDefaults();
        
        $this->assertEquals('default', $this->config->get('ui.theme'));
        $this->assertEquals(1000, $this->config->get('table.max_visible_rows'));
    }

    /**
     * Test reset section to defaults
     */
    public function test_reset_section_to_defaults(): void
    {
        // Modify UI values
        $this->config->set('ui.theme', 'custom');
        $this->config->set('ui.window_width', 2000);
        $this->config->set('table.max_visible_rows', 5000);
        
        // Reset only UI section
        $this->config->resetSectionToDefaults('ui');
        
        $this->assertEquals('default', $this->config->get('ui.theme'));
        $this->assertEquals(1200, $this->config->get('ui.window_width'));
        $this->assertEquals(5000, $this->config->get('table.max_visible_rows')); // Unchanged
    }

    /**
     * Test configuration export/import
     */
    public function test_export_import(): void
    {
        // Set some custom values
        $this->config->set('ui.theme', 'dark');
        $this->config->set('table.max_visible_rows', 2000);
        
        // Export configuration
        $exported = $this->config->exportConfiguration();
        
        $this->assertIsArray($exported);
        $this->assertArrayHasKey('version', $exported);
        $this->assertArrayHasKey('exported_at', $exported);
        $this->assertArrayHasKey('config', $exported);
        
        // Reset and import
        $this->config->resetToDefaults();
        $this->assertEquals('default', $this->config->get('ui.theme'));
        
        $result = $this->config->importConfiguration($exported);
        $this->assertTrue($result);
        
        $this->assertEquals('dark', $this->config->get('ui.theme'));
        $this->assertEquals(2000, $this->config->get('table.max_visible_rows'));
    }

    /**
     * Test invalid import data
     */
    public function test_invalid_import(): void
    {
        $invalidData = ['invalid' => 'structure'];
        $result = $this->config->importConfiguration($invalidData);
        $this->assertFalse($result);
    }

    /**
     * Test file persistence
     */
    public function test_file_persistence(): void
    {
        // Set some values
        $this->config->set('ui.theme', 'dark');
        $this->config->set('table.max_visible_rows', 2000);
        
        // Save to file
        $result = $this->config->saveConfiguration();
        $this->assertTrue($result);
        $this->assertTrue($this->config->configFileExists());
        $this->assertGreaterThan(0, $this->config->getConfigFileSize());
        
        // Create new instance and verify values are loaded
        $newConfig = new ConfigurationService($this->logger, $this->testConfigPath);
        $this->assertEquals('dark', $newConfig->get('ui.theme'));
        $this->assertEquals(2000, $newConfig->get('table.max_visible_rows'));
    }

    /**
     * Test configuration file info methods
     */
    public function test_config_file_info(): void
    {
        $this->assertFalse($this->config->configFileExists());
        $this->assertEquals(0, $this->config->getConfigFileSize());
        $this->assertEquals(0, $this->config->getConfigFileModTime());
        
        $this->config->saveConfiguration();
        
        $this->assertTrue($this->config->configFileExists());
        $this->assertGreaterThan(0, $this->config->getConfigFileSize());
        $this->assertGreaterThan(0, $this->config->getConfigFileModTime());
        
        $this->assertEquals($this->testConfigPath, $this->config->getConfigPath());
    }

    /**
     * Test get all configuration
     */
    public function test_get_all(): void
    {
        $allConfig = $this->config->getAll();
        
        $this->assertIsArray($allConfig);
        $this->assertArrayHasKey('ui', $allConfig);
        $this->assertArrayHasKey('table', $allConfig);
        $this->assertArrayHasKey('keyboard', $allConfig);
        $this->assertArrayHasKey('performance', $allConfig);
        $this->assertArrayHasKey('ports', $allConfig);
        $this->assertArrayHasKey('processes', $allConfig);
        $this->assertArrayHasKey('logging', $allConfig);
    }

    /**
     * Test complex nested operations
     */
    public function test_complex_nested_operations(): void
    {
        // Set complex nested structure
        $this->config->set('custom.deeply.nested.value', 'test');
        $this->config->set('custom.array.0', 'first');
        $this->config->set('custom.array.1', 'second');
        
        $this->assertEquals('test', $this->config->get('custom.deeply.nested.value'));
        $this->assertEquals('first', $this->config->get('custom.array.0'));
        $this->assertEquals('second', $this->config->get('custom.array.1'));
        
        // Test removing nested keys
        $this->config->remove('custom.deeply.nested.value');
        $this->assertFalse($this->config->has('custom.deeply.nested.value'));
        $this->assertTrue($this->config->has('custom.array.0'));
    }

    /**
     * Test configuration with special characters and types
     */
    public function test_special_values(): void
    {
        // Test various data types
        $this->config->set('test.string', 'hello world');
        $this->config->set('test.integer', 42);
        $this->config->set('test.float', 3.14);
        $this->config->set('test.boolean_true', true);
        $this->config->set('test.boolean_false', false);
        $this->config->set('test.null', null);
        $this->config->set('test.array', ['a', 'b', 'c']);
        
        $this->assertEquals('hello world', $this->config->get('test.string'));
        $this->assertEquals(42, $this->config->get('test.integer'));
        $this->assertEquals(3.14, $this->config->get('test.float'));
        $this->assertTrue($this->config->get('test.boolean_true'));
        $this->assertFalse($this->config->get('test.boolean_false'));
        $this->assertNull($this->config->get('test.null'));
        $this->assertEquals(['a', 'b', 'c'], $this->config->get('test.array'));
    }
}