-- Add low_stock_threshold column to products table
ALTER TABLE products ADD COLUMN low_stock_threshold DECIMAL(10,2) DEFAULT 0 AFTER stock_quantity;
