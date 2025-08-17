-- Create comprehensive settings table for dynamic configuration
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text,
  `setting_type` enum('text','number','select','textarea','boolean') DEFAULT 'text',
  `setting_group` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_description`, `setting_type`, `setting_group`) VALUES
-- Company Information
('company_name', 'TAILOR SHOP', 'Company/Business Name', 'text', 'company'),
('company_tagline', 'Professional Tailoring Services', 'Company Tagline or Description', 'text', 'company'),
('company_phone', '+92-300-1234567', 'Company Phone Number', 'text', 'company'),
('company_email', 'info@tailorshop.com', 'Company Email Address', 'email', 'company'),
('company_address', 'Shop #123, Main Street, Lahore, Pakistan', 'Company Address', 'textarea', 'company'),
('company_website', 'www.tailorshop.com', 'Company Website', 'text', 'company'),
('company_logo', '', 'Company Logo URL (optional)', 'text', 'company'),

-- Currency Settings
('currency_symbol', 'PKR', 'Currency Symbol', 'text', 'currency'),
('currency_name', 'Pakistani Rupee', 'Currency Name', 'text', 'currency'),

-- Invoice Settings
('invoice_prefix', 'INV', 'Invoice Number Prefix', 'text', 'invoice'),
('purchase_prefix', 'PUR', 'Purchase Invoice Prefix', 'text', 'invoice'),
('sale_prefix', 'SALE', 'Sale Invoice Prefix', 'text', 'invoice'),

-- Business Settings
('footer_text', 'Thank you for your business!', 'Footer Text for Invoices', 'textarea', 'business'),
('print_header', 'Computer Generated Invoice', 'Print Header Text', 'textarea', 'business'),
('low_stock_threshold', '10', 'Low Stock Alert Threshold', 'number', 'business'),
('business_hours', '9:00 AM - 6:00 PM', 'Business Hours', 'text', 'business'),
('business_days', 'Monday - Saturday', 'Business Days', 'text', 'business'),

-- Date/Time Settings
('date_format', 'd/m/Y', 'Date Format', 'select', 'datetime'),
('time_format', 'H:i:s', 'Time Format', 'select', 'datetime'),

-- System Settings
('system_name', 'Tailor Management System', 'System Name', 'text', 'system'),
('system_version', '1.0.0', 'System Version', 'text', 'system'),
('maintenance_mode', '0', 'Maintenance Mode (0=No, 1=Yes)', 'boolean', 'system'),
('default_language', 'en', 'Default Language', 'select', 'system'),
('timezone', 'Asia/Karachi', 'System Timezone', 'text', 'system'),

-- Notification Settings
('email_notifications', '1', 'Enable Email Notifications', 'boolean', 'notifications'),
('sms_notifications', '0', 'Enable SMS Notifications', 'boolean', 'notifications'),
('low_stock_alerts', '1', 'Enable Low Stock Alerts', 'boolean', 'notifications'),
('payment_reminders', '1', 'Enable Payment Reminders', 'boolean', 'notifications'),

-- Backup Settings
('auto_backup', '1', 'Enable Auto Backup', 'boolean', 'backup'),
('backup_frequency', 'daily', 'Backup Frequency', 'select', 'backup'),
('backup_retention', '30', 'Backup Retention Days', 'number', 'backup');

-- Update existing system_settings table to use new settings
UPDATE `system_settings` SET 
  `company_name` = (SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'company_name'),
  `company_address` = (SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'company_address'),
  `company_phone` = (SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'company_phone'),
  `company_email` = (SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'company_email'),
  `company_logo` = (SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'company_logo'),
  `currency` = (SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'currency_symbol');
