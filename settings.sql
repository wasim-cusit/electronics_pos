-- =====================================================
-- SETTINGS TABLE FOR TAILOR SHOP CONFIGURATION
-- =====================================================

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_description) VALUES 
('company_name', 'TAILOR SHOP', 'Company/Business Name'),
('company_tagline', 'Professional Tailoring Services', 'Company Tagline or Description'),
('company_phone', '+92-300-1234567', 'Company Phone Number'),
('company_email', 'info@tailorshop.com', 'Company Email Address'),
('company_address', 'Shop #123, Main Street, Lahore, Pakistan', 'Company Address'),
('company_website', 'www.tailorshop.com', 'Company Website'),
('company_logo', '', 'Company Logo URL (optional)'),
('currency_symbol', 'PKR', 'Currency Symbol'),
('currency_name', 'Pakistani Rupee', 'Currency Name'),
('invoice_prefix', 'INV', 'Invoice Number Prefix'),
('purchase_prefix', 'PUR', 'Purchase Invoice Prefix'),
('sale_prefix', 'SALE', 'Sale Invoice Prefix'),
('tax_rate', '0', 'Default Tax Rate (%)'),
('footer_text', 'Thank you for your business!', 'Footer Text for Invoices'),
('print_header', 'Computer Generated Invoice', 'Print Header Text'),
('low_stock_threshold', '10', 'Low Stock Alert Threshold'),
('date_format', 'd/m/Y', 'Date Format'),
('time_format', 'H:i:s', 'Time Format'),
('business_hours', '9:00 AM - 6:00 PM', 'Business Hours'),
('business_days', 'Monday - Saturday', 'Business Days'),
('created_at', NOW(), 'Settings Created Date'),
('updated_at', NOW(), 'Settings Last Updated Date');
