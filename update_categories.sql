-- Update categories table to add description field
ALTER TABLE categories ADD COLUMN description TEXT AFTER name;
ALTER TABLE categories ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER description;

-- Insert some sample categories for a tailor shop
INSERT INTO categories (name, description) VALUES 
('Cotton Fabric', 'Natural cotton fabrics for everyday wear'),
('Silk Fabric', 'Premium silk fabrics for special occasions'),
('Denim Fabric', 'Durable denim for jeans and jackets'),
('Linen Fabric', 'Lightweight linen for summer clothing'),
('Wool Fabric', 'Warm wool fabrics for winter wear'),
('Synthetic Fabric', 'Man-made fabrics like polyester, nylon'),
('Embroidered Fabric', 'Fabric with decorative embroidery'),
('Printed Fabric', 'Fabric with printed patterns and designs'),
('Plain Fabric', 'Solid color fabrics without patterns'),
('Accessories', 'Zippers, buttons, threads, and other accessories');

-- Update existing categories if any exist
UPDATE categories SET description = 'General fabric category' WHERE description IS NULL;
