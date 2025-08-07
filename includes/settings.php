<?php
/**
 * Settings Helper Functions
 * Provides easy access to application settings
 */

/**
 * Get a setting value by key
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
function get_setting($key, $default = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Set a setting value
 * @param string $key Setting key
 * @param string $value Setting value
 * @param string $description Setting description (optional)
 * @return bool Success status
 */
function set_setting($key, $value, $description = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            setting_description = VALUES(setting_description),
            updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$key, $value, $description]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all settings as an associative array
 * @return array All settings
 */
function get_all_settings() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value, setting_description FROM settings ORDER BY setting_key");
        $settings = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['setting_description']
            ];
        }
        
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get company information as an array
 * @return array Company information
 */
function get_company_info() {
    return [
        'name' => get_setting('company_name', 'TAILOR SHOP'),
        'tagline' => get_setting('company_tagline', 'Professional Tailoring Services'),
        'phone' => get_setting('company_phone', '+92-300-1234567'),
        'email' => get_setting('company_email', 'info@tailorshop.com'),
        'address' => get_setting('company_address', 'Shop #123, Main Street, Lahore, Pakistan'),
        'website' => get_setting('company_website', 'www.tailorshop.com'),
        'logo' => get_setting('company_logo', ''),
        'currency_symbol' => get_setting('currency_symbol', 'PKR'),
        'currency_name' => get_setting('currency_name', 'Pakistani Rupee'),
        'business_hours' => get_setting('business_hours', '9:00 AM - 6:00 PM'),
        'business_days' => get_setting('business_days', 'Monday - Saturday')
    ];
}

/**
 * Format currency with proper symbol
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function format_currency($amount) {
    $symbol = get_setting('currency_symbol', 'PKR');
    return $symbol . ' ' . number_format($amount, 2);
}

/**
 * Format date according to settings
 * @param string $date Date string
 * @return string Formatted date
 */
function format_date($date) {
    $format = get_setting('date_format', 'd/m/Y');
    return date($format, strtotime($date));
}

/**
 * Generate invoice number with prefix
 * @param string $type Type of invoice (purchase, sale, etc.)
 * @return string Generated invoice number
 */
function generate_invoice_number($type = 'invoice') {
    $prefix = get_setting($type . '_prefix', 'INV');
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid()), 0, 4));
    return $prefix . '-' . $date . '-' . $random;
}
?>
