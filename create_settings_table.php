<?php
/**
 * Create Settings Table Script
 * This script checks if the settings table exists and creates it if needed
 */

require_once 'includes/config.php';

echo "<h2>Settings Table Check & Creation</h2>";

try {
    // Check if settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p style='color: green;'>✅ Settings table already exists!</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE settings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show current settings count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM settings");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p><strong>Current settings count:</strong> {$count}</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Settings table does not exist. Creating it now...</p>";
        
        // Create settings table
        $sql = "CREATE TABLE `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(255) NOT NULL,
            `setting_value` text,
            `setting_description` text,
            `setting_type` varchar(50) DEFAULT 'text',
            `setting_group` varchar(100) DEFAULT 'general',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`),
            KEY `setting_group` (`setting_group`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($pdo->exec($sql)) {
            echo "<p style='color: green;'>✅ Settings table created successfully!</p>";
            
            // Insert default settings
            $default_settings = [
                ['company_name', 'WASEM WEARS', 'Company/Business Name'],
                ['company_tagline', 'Professional Tailoring Services', 'Company Tagline or Description'],
                ['company_phone', '+92 323 9507813', 'Company Phone Number'],
                ['company_email', 'info@tailorshop.com', 'Company Email Address'],
                ['company_address', 'Address shop #1 hameed plaza main university road Pakistan', 'Company Address'],
                ['company_website', 'www.tailorshop.com', 'Company Website'],
                ['company_logo', '', 'Company Logo URL (optional)'],
                ['currency_symbol', 'PKR', 'Currency Symbol'],
                ['currency_name', 'Pakistani Rupee', 'Currency Name'],
                ['invoice_prefix', 'INV', 'Invoice Number Prefix'],
                ['purchase_prefix', 'PUR', 'Purchase Invoice Prefix'],
                ['sale_prefix', 'SALE', 'Sale Invoice Prefix'],
                ['footer_text', 'Thank you for your business!', 'Footer Text for Invoices'],
                ['print_header', 'Computer Generated Invoice', 'Print Header Text'],
                ['low_stock_threshold', '10', 'Low Stock Alert Threshold'],
                ['business_hours', '9:00 AM - 6:00 PM', 'Business Hours'],
                ['business_days', 'Monday - Saturday', 'Business Days'],
                ['date_format', 'd/m/Y', 'Date Format'],
                ['time_format', 'H:i:s', 'Time Format'],
                ['auto_backup', '1', 'Enable Auto Backup'],
                ['backup_frequency', 'weekly', 'Backup Frequency'],
                ['backup_retention', '30', 'Backup Retention Days'],
                ['backup_location', 'backups/', 'Backup Storage Location'],
                ['backup_type', 'full', 'Backup Type'],
                ['enable_notifications', '1', 'Enable Notifications System'],
                ['show_notification_badge', '1', 'Show Notification Badge'],
                ['email_notifications', '0', 'Enable Email Notifications'],
                ['smtp_server', 'smtp.gmail.com', 'SMTP Server'],
                ['smtp_port', '587', 'SMTP Port'],
                ['smtp_username', '', 'SMTP Username'],
                ['smtp_password', '', 'SMTP Password'],
                ['from_email', 'noreply@tailorshop.com', 'From Email Address'],
                ['sms_notifications', '0', 'Enable SMS Notifications'],
                ['sms_provider', 'twilio', 'SMS Provider'],
                ['sms_api_key', '', 'SMS API Key'],
                ['sms_api_secret', '', 'SMS API Secret'],
                ['low_stock_alerts', '1', 'Enable Low Stock Alerts'],
                ['payment_reminders', '1', 'Enable Payment Reminders'],
                ['order_status_updates', '1', 'Enable Order Status Updates'],
                ['delivery_reminders', '1', 'Enable Delivery Reminders'],
                ['notification_auto_hide', '5', 'Notification Auto-Hide Duration'],
                ['notification_position', 'top-right', 'Notification Position'],
                ['notification_sound', '0', 'Enable Notification Sounds'],
                ['notification_sound_type', 'default', 'Notification Sound Type']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_description) VALUES (?, ?, ?)");
            $inserted = 0;
            
            foreach ($default_settings as $setting) {
                if ($stmt->execute($setting)) {
                    $inserted++;
                }
            }
            
            echo "<p style='color: green;'>✅ Inserted {$inserted} default settings!</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to create settings table!</p>";
        }
    }
    
    // Test the get_setting function
    echo "<h3>Testing get_setting function:</h3>";
    
    // Include the settings helper
    require_once 'includes/settings.php';
    
    $test_key = 'company_name';
    $test_value = get_setting($test_key, 'NOT_FOUND');
    echo "<p><strong>Test:</strong> get_setting('{$test_key}') = '{$test_value}'</p>";
    
    // Test setting a value
    echo "<h3>Testing set_setting function:</h3>";
    $test_setting = 'test_setting_' . time();
    $test_value = 'test_value_' . time();
    
    if (set_setting($test_setting, $test_value, 'Test Setting')) {
        echo "<p style='color: green;'>✅ Successfully set test setting: {$test_setting} = {$test_value}</p>";
        
        // Test retrieving it
        $retrieved = get_setting($test_setting, 'NOT_FOUND');
        if ($retrieved === $test_value) {
            echo "<p style='color: green;'>✅ Successfully retrieved test setting: {$retrieved}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to retrieve test setting. Expected: {$test_value}, Got: {$retrieved}</p>";
        }
        
        // Clean up test setting
        $pdo->exec("DELETE FROM settings WHERE setting_key = '{$test_setting}'");
        echo "<p>🧹 Cleaned up test setting</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to set test setting!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='settings.php'>← Back to Settings</a></p>";
echo "<p><strong>Note:</strong> If you see any errors above, the settings table may not exist or there may be a database connection issue.</p>";
?>
