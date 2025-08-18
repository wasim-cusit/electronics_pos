<?php
/**
 * Test Settings System
 * This script tests if the settings are being saved and retrieved properly
 */

require_once 'includes/config.php';
require_once 'includes/settings.php';

echo "<h2>Settings System Test</h2>";

try {
    // Test 1: Check if settings table exists
    echo "<h3>1. Checking Settings Table</h3>";
    $debug = debug_settings_table();
    
    if ($debug['table_exists']) {
        echo "<p style='color: green;'>✅ Settings table exists</p>";
        echo "<p><strong>Settings count:</strong> {$debug['settings_count']}</p>";
        
        if (!empty($debug['sample_settings'])) {
            echo "<p><strong>Sample settings:</strong></p>";
            echo "<ul>";
            foreach ($debug['sample_settings'] as $setting) {
                echo "<li>{$setting['setting_key']}: {$setting['setting_value']}</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ Settings table does not exist</p>";
    }
    
    // Test 2: Test setting and getting a value
    echo "<h3>2. Testing Set/Get Setting</h3>";
    $test_key = 'test_setting_' . time();
    $test_value = 'test_value_' . time();
    
    echo "<p><strong>Setting:</strong> {$test_key} = {$test_value}</p>";
    
    if (set_setting($test_key, $test_value, 'Test Setting')) {
        echo "<p style='color: green;'>✅ Successfully set setting</p>";
        
        // Try to retrieve it
        $retrieved = get_setting($test_key, 'NOT_FOUND');
        if ($retrieved === $test_value) {
            echo "<p style='color: green;'>✅ Successfully retrieved setting: {$retrieved}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to retrieve setting. Expected: {$test_value}, Got: {$retrieved}</p>";
        }
        
        // Clean up
        $pdo->exec("DELETE FROM settings WHERE setting_key = '{$test_key}'");
        echo "<p>🧹 Cleaned up test setting</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to set setting</p>";
    }
    
    // Test 3: Test existing settings
    echo "<h3>3. Testing Existing Settings</h3>";
    $company_name = get_setting('company_name', 'NOT_FOUND');
    echo "<p><strong>Company Name:</strong> {$company_name}</p>";
    
    $enable_notifications = get_setting('enable_notifications', 'NOT_FOUND');
    echo "<p><strong>Enable Notifications:</strong> {$enable_notifications}</p>";
    
    // Test 4: Test boolean setting
    echo "<h3>4. Testing Boolean Setting</h3>";
    $is_enabled = is_setting_enabled('enable_notifications');
    echo "<p><strong>Notifications Enabled:</strong> " . ($is_enabled ? 'Yes' : 'No') . "</p>";
    
    // Test 5: Test company info
    echo "<h3>5. Testing Company Info</h3>";
    $company_info = get_company_info();
    echo "<p><strong>Company Info:</strong></p>";
    echo "<ul>";
    foreach ($company_info as $key => $value) {
        echo "<li>{$key}: {$value}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='settings.php'>← Back to Settings</a></p>";
echo "<p><a href='create_settings_table.php'>🔧 Create Settings Table (if needed)</a></p>";
echo "<p><strong>Note:</strong> If you see any errors above, the settings table may need to be created or there may be a database connection issue.</p>";
?>
