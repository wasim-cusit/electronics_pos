-- =====================================================
-- DUMMY DATA FOR TAILOR SHOP MANAGEMENT SYSTEM
-- =====================================================

-- Clear existing data (optional - uncomment if needed)
-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE notifications;
-- TRUNCATE TABLE expenses;
-- TRUNCATE TABLE stock_movements;
-- TRUNCATE TABLE sale_items;
-- TRUNCATE TABLE sales;
-- TRUNCATE TABLE purchase_items;
-- TRUNCATE TABLE purchases;
-- TRUNCATE TABLE products;
-- TRUNCATE TABLE categories;
-- TRUNCATE TABLE customers;
-- TRUNCATE TABLE suppliers;
-- TRUNCATE TABLE users;
-- TRUNCATE TABLE roles;
-- SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. ROLES DATA
-- =====================================================
INSERT INTO roles (name) VALUES 
('Admin'),
('Manager'),
('Cashier');

-- =====================================================
-- 2. USERS DATA
-- =====================================================
INSERT INTO users (username, password, full_name, email, role_id) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@tailorshop.com', 1),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Shop Manager', 'manager@tailorshop.com', 2),
('cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier One', 'cashier1@tailorshop.com', 3),
('cashier2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier Two', 'cashier2@tailorshop.com', 3);

-- =====================================================
-- 3. CATEGORIES DATA
-- =====================================================
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

-- =====================================================
-- 4. SUPPLIERS DATA
-- =====================================================
INSERT INTO suppliers (name, contact, address, email) VALUES 
('ABC Fabrics Ltd', '+92-300-1234567', 'Shop #15, Main Market, Lahore', 'info@abcfabrics.com'),
('Premium Textiles', '+92-301-2345678', 'Floor 2, Plaza Building, Karachi', 'sales@premiumtextiles.com'),
('Quality Cloth House', '+92-302-3456789', 'Street 5, Industrial Area, Faisalabad', 'contact@qualitycloth.com'),
('Royal Silk Traders', '+92-303-4567890', 'Shop #8, Silk Market, Multan', 'royal@silktraders.com'),
('Denim World', '+92-304-5678901', 'Unit 12, Textile Complex, Sialkot', 'denim@world.com'),
('Cotton Paradise', '+92-305-6789012', 'Shop #25, Cotton Market, Rahim Yar Khan', 'cotton@paradise.com');

-- =====================================================
-- 5. CUSTOMERS DATA
-- =====================================================
INSERT INTO customers (name, contact, address, email) VALUES 
('Ahmed Khan', '+92-310-1111111', 'House #123, Street 5, Model Town, Lahore', 'ahmed.khan@email.com'),
('Fatima Ali', '+92-311-2222222', 'Apartment 45, Block B, Gulberg, Lahore', 'fatima.ali@email.com'),
('Muhammad Hassan', '+92-312-3333333', 'House #67, Street 12, Johar Town, Lahore', 'hassan@email.com'),
('Ayesha Malik', '+92-313-4444444', 'Shop #8, Main Bazaar, Multan', 'ayesha.malik@email.com'),
('Usman Ahmed', '+92-314-5555555', 'House #90, Street 3, Faisalabad', 'usman.ahmed@email.com'),
('Sara Khan', '+92-315-6666666', 'Apartment 23, Block C, Karachi', 'sara.khan@email.com'),
('Bilal Hassan', '+92-316-7777777', 'House #45, Street 8, Islamabad', 'bilal.hassan@email.com'),
('Nadia Ali', '+92-317-8888888', 'Shop #12, Market Street, Peshawar', 'nadia.ali@email.com');

-- =====================================================
-- 6. PRODUCTS DATA
-- =====================================================
INSERT INTO products (name, category_id, unit, size, color, brand, cost_price, sale_price, stock_quantity, barcode) VALUES 
-- Cotton Fabrics
('Premium Cotton White', 1, 'meter', 'Standard', 'White', 'CottonCo', 150.00, 200.00, 50.5, 'CTN001'),
('Cotton Blue Striped', 1, 'meter', 'Standard', 'Blue', 'CottonCo', 180.00, 240.00, 35.0, 'CTN002'),
('Cotton Pink Plain', 1, 'meter', 'Standard', 'Pink', 'CottonCo', 160.00, 220.00, 42.5, 'CTN003'),
('Cotton Green Checkered', 1, 'meter', 'Standard', 'Green', 'CottonCo', 170.00, 230.00, 28.0, 'CTN004'),

-- Silk Fabrics
('Pure Silk Red', 2, 'meter', 'Standard', 'Red', 'SilkPro', 800.00, 1200.00, 15.5, 'SLK001'),
('Silk Blue Embroidered', 2, 'meter', 'Standard', 'Blue', 'SilkPro', 900.00, 1400.00, 12.0, 'SLK002'),
('Silk Gold Plain', 2, 'meter', 'Standard', 'Gold', 'SilkPro', 750.00, 1100.00, 18.5, 'SLK003'),

-- Denim Fabrics
('Heavy Denim Blue', 3, 'meter', 'Standard', 'Blue', 'DenimMax', 300.00, 450.00, 25.0, 'DNM001'),
('Light Denim Grey', 3, 'meter', 'Standard', 'Grey', 'DenimMax', 250.00, 380.00, 30.5, 'DNM002'),

-- Linen Fabrics
('Natural Linen Beige', 4, 'meter', 'Standard', 'Beige', 'LinenPure', 400.00, 600.00, 20.0, 'LNN001'),
('Linen White', 4, 'meter', 'Standard', 'White', 'LinenPure', 380.00, 570.00, 22.5, 'LNN002'),

-- Wool Fabrics
('Wool Black', 5, 'meter', 'Standard', 'Black', 'WoolWarm', 500.00, 750.00, 15.0, 'WOL001'),
('Wool Brown', 5, 'meter', 'Standard', 'Brown', 'WoolWarm', 480.00, 720.00, 18.5, 'WOL002'),

-- Synthetic Fabrics
('Polyester Black', 6, 'meter', 'Standard', 'Black', 'SynthFab', 120.00, 180.00, 40.0, 'SYN001'),
('Nylon White', 6, 'meter', 'Standard', 'White', 'SynthFab', 100.00, 150.00, 35.5, 'SYN002'),

-- Embroidered Fabrics
('Embroidered Red Silk', 7, 'meter', 'Standard', 'Red', 'EmbroideryPro', 1200.00, 1800.00, 8.5, 'EMB001'),
('Embroidered Green Cotton', 7, 'meter', 'Standard', 'Green', 'EmbroideryPro', 400.00, 600.00, 12.0, 'EMB002'),

-- Printed Fabrics
('Printed Floral Cotton', 8, 'meter', 'Standard', 'Multi', 'PrintFab', 200.00, 300.00, 25.5, 'PRT001'),
('Printed Geometric Silk', 8, 'meter', 'Standard', 'Multi', 'PrintFab', 600.00, 900.00, 10.0, 'PRT002'),

-- Plain Fabrics
('Plain White Cotton', 9, 'meter', 'Standard', 'White', 'PlainFab', 140.00, 200.00, 45.0, 'PLN001'),
('Plain Black Cotton', 9, 'meter', 'Standard', 'Black', 'PlainFab', 150.00, 220.00, 38.5, 'PLN002'),

-- Accessories
('Zipper 12 inch', 10, 'piece', '12 inch', 'Silver', 'ZipCo', 25.00, 40.00, 100, 'ACC001'),
('Buttons Pack (10 pcs)', 10, 'piece', 'Standard', 'White', 'ButtonCo', 15.00, 25.00, 50, 'ACC002'),
('Thread Spool', 10, 'piece', 'Standard', 'Black', 'ThreadCo', 30.00, 45.00, 75, 'ACC003');

-- =====================================================
-- 7. PURCHASES DATA
-- =====================================================
INSERT INTO purchases (supplier_id, invoice_no, purchase_date, total_amount, created_by) VALUES 
(1, 'INV-001', '2024-01-15', 15000.00, 1),
(2, 'INV-002', '2024-01-20', 25000.00, 2),
(3, 'INV-003', '2024-02-01', 18000.00, 1),
(4, 'INV-004', '2024-02-10', 12000.00, 2),
(5, 'INV-005', '2024-02-15', 22000.00, 1),
(6, 'INV-006', '2024-03-01', 16000.00, 2);

-- =====================================================
-- 8. PURCHASE ITEMS DATA
-- =====================================================
INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_price, total_price) VALUES 
-- Purchase 1 (INV-001)
(1, 1, 50.0, 150.00, 7500.00),
(1, 2, 25.0, 180.00, 4500.00),
(1, 3, 30.0, 160.00, 4800.00),

-- Purchase 2 (INV-002)
(2, 5, 15.0, 800.00, 12000.00),
(2, 6, 10.0, 900.00, 9000.00),
(2, 7, 8.0, 750.00, 6000.00),

-- Purchase 3 (INV-003)
(3, 8, 30.0, 300.00, 9000.00),
(3, 9, 25.0, 250.00, 6250.00),
(3, 10, 20.0, 400.00, 8000.00),

-- Purchase 4 (INV-004)
(4, 11, 15.0, 500.00, 7500.00),
(4, 12, 12.0, 480.00, 5760.00),

-- Purchase 5 (INV-005)
(5, 13, 40.0, 120.00, 4800.00),
(5, 14, 35.0, 100.00, 3500.00),
(5, 15, 8.0, 1200.00, 9600.00),
(5, 16, 12.0, 400.00, 4800.00),

-- Purchase 6 (INV-006)
(6, 17, 25.0, 200.00, 5000.00),
(6, 18, 10.0, 600.00, 6000.00),
(6, 19, 45.0, 140.00, 6300.00);

-- =====================================================
-- 9. SALES DATA
-- =====================================================
INSERT INTO sales (customer_id, invoice_no, sale_date, delivery_date, total_amount, created_by) VALUES 
(1, 'SALE-001', '2024-01-16', '2024-01-18', 1200.00, 3),
(2, 'SALE-002', '2024-01-22', '2024-01-25', 2800.00, 4),
(3, 'SALE-003', '2024-02-02', '2024-02-05', 900.00, 3),
(4, 'SALE-004', '2024-02-12', '2024-02-15', 1800.00, 4),
(5, 'SALE-005', '2024-02-16', '2024-02-20', 1500.00, 3),
(6, 'SALE-006', '2024-03-02', '2024-03-05', 2200.00, 4),
(7, 'SALE-007', '2024-03-10', '2024-03-12', 800.00, 3),
(8, 'SALE-008', '2024-03-15', '2024-03-18', 1600.00, 4);

-- =====================================================
-- 10. SALE ITEMS DATA
-- =====================================================
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES 
-- Sale 1 (SALE-001)
(1, 1, 4.0, 200.00, 800.00),
(1, 23, 2.0, 40.00, 80.00),
(1, 24, 1.0, 25.00, 25.00),
(1, 25, 2.0, 45.00, 90.00),

-- Sale 2 (SALE-002)
(2, 5, 2.0, 1200.00, 2400.00),
(2, 23, 1.0, 40.00, 40.00),
(2, 24, 2.0, 25.00, 50.00),
(2, 25, 1.0, 45.00, 45.00),

-- Sale 3 (SALE-003)
(3, 2, 3.0, 240.00, 720.00),
(3, 23, 1.0, 40.00, 40.00),
(3, 25, 1.0, 45.00, 45.00),

-- Sale 4 (SALE-004)
(4, 6, 1.0, 1400.00, 1400.00),
(4, 23, 2.0, 40.00, 80.00),
(4, 24, 1.0, 25.00, 25.00),
(4, 25, 1.0, 45.00, 45.00),

-- Sale 5 (SALE-005)
(5, 8, 2.0, 450.00, 900.00),
(5, 23, 1.0, 40.00, 40.00),
(5, 24, 1.0, 25.00, 25.00),
(5, 25, 1.0, 45.00, 45.00),

-- Sale 6 (SALE-006)
(6, 7, 1.0, 1100.00, 1100.00),
(6, 10, 1.0, 600.00, 600.00),
(6, 23, 1.0, 40.00, 40.00),
(6, 24, 1.0, 25.00, 25.00),
(6, 25, 1.0, 45.00, 45.00),

-- Sale 7 (SALE-007)
(7, 13, 4.0, 180.00, 720.00),
(7, 23, 1.0, 40.00, 40.00),
(7, 25, 1.0, 45.00, 45.00),

-- Sale 8 (SALE-008)
(8, 11, 1.0, 750.00, 750.00),
(8, 12, 1.0, 720.00, 720.00),
(8, 23, 1.0, 40.00, 40.00),
(8, 24, 1.0, 25.00, 25.00),
(8, 25, 1.0, 45.00, 45.00);

-- =====================================================
-- 11. STOCK MOVEMENTS DATA
-- =====================================================
INSERT INTO stock_movements (product_id, movement_type, quantity, note, created_by) VALUES 
-- Purchase movements
(1, 'purchase', 50.0, 'Purchase from supplier', 1),
(2, 'purchase', 25.0, 'Purchase from supplier', 1),
(3, 'purchase', 30.0, 'Purchase from supplier', 1),
(5, 'purchase', 15.0, 'Purchase from supplier', 2),
(6, 'purchase', 10.0, 'Purchase from supplier', 2),
(7, 'purchase', 8.0, 'Purchase from supplier', 2),
(8, 'purchase', 30.0, 'Purchase from supplier', 1),
(9, 'purchase', 25.0, 'Purchase from supplier', 1),
(10, 'purchase', 20.0, 'Purchase from supplier', 1),
(11, 'purchase', 15.0, 'Purchase from supplier', 2),
(12, 'purchase', 12.0, 'Purchase from supplier', 2),
(13, 'purchase', 40.0, 'Purchase from supplier', 1),
(14, 'purchase', 35.0, 'Purchase from supplier', 1),
(15, 'purchase', 8.0, 'Purchase from supplier', 1),
(16, 'purchase', 12.0, 'Purchase from supplier', 1),
(17, 'purchase', 25.0, 'Purchase from supplier', 2),
(18, 'purchase', 10.0, 'Purchase from supplier', 2),
(19, 'purchase', 45.0, 'Purchase from supplier', 2),

-- Sale movements
(1, 'sale', 4.0, 'Sale to customer', 3),
(5, 'sale', 2.0, 'Sale to customer', 4),
(2, 'sale', 3.0, 'Sale to customer', 3),
(6, 'sale', 1.0, 'Sale to customer', 4),
(8, 'sale', 2.0, 'Sale to customer', 3),
(7, 'sale', 1.0, 'Sale to customer', 4),
(10, 'sale', 1.0, 'Sale to customer', 4),
(13, 'sale', 4.0, 'Sale to customer', 3),
(11, 'sale', 1.0, 'Sale to customer', 4),
(12, 'sale', 1.0, 'Sale to customer', 4);

-- =====================================================
-- 12. EXPENSES DATA
-- =====================================================
INSERT INTO expenses (date, category, amount, description, created_by) VALUES 
('2024-01-01', 'Rent', 15000.00, 'Monthly shop rent', 1),
('2024-01-05', 'Electricity Bill', 2500.00, 'December electricity bill', 1),
('2024-01-10', 'Staff Salary', 8000.00, 'Cashier salary for December', 1),
('2024-01-15', 'Internet Bill', 1500.00, 'Monthly internet connection', 1),
('2024-01-20', 'Maintenance', 2000.00, 'Sewing machine repair', 1),
('2024-02-01', 'Rent', 15000.00, 'Monthly shop rent', 2),
('2024-02-05', 'Electricity Bill', 2800.00, 'January electricity bill', 2),
('2024-02-10', 'Staff Salary', 8000.00, 'Cashier salary for January', 2),
('2024-02-15', 'Packaging', 500.00, 'Plastic bags and packaging material', 2),
('2024-02-20', 'Transport', 800.00, 'Delivery expenses', 2),
('2024-03-01', 'Rent', 15000.00, 'Monthly shop rent', 1),
('2024-03-05', 'Electricity Bill', 2200.00, 'February electricity bill', 1),
('2024-03-10', 'Staff Salary', 8000.00, 'Cashier salary for February', 1),
('2024-03-15', 'Miscellaneous', 1000.00, 'Office supplies and stationery', 1);

-- =====================================================
-- 13. NOTIFICATIONS DATA
-- =====================================================
INSERT INTO notifications (user_id, type, message, is_read) VALUES 
(1, 'low_stock', 'Product "Premium Cotton White" is running low (5.5 meters remaining)', 0),
(2, 'low_stock', 'Product "Pure Silk Red" is running low (3.5 meters remaining)', 0),
(1, 'new_sale', 'New sale completed: SALE-008 for PKR 1,600', 0),
(2, 'new_purchase', 'New purchase recorded: INV-006 for PKR 16,000', 0),
(3, 'reminder', 'Customer Ahmed Khan has pending delivery for SALE-001', 0),
(4, 'reminder', 'Customer Fatima Ali has pending delivery for SALE-002', 0);

-- =====================================================
-- DATA SUMMARY
-- =====================================================
-- Roles: 3
-- Users: 4 (admin, manager, 2 cashiers)
-- Categories: 10 (fabric types + accessories)
-- Products: 25 (various fabrics and accessories)
-- Suppliers: 6
-- Customers: 8
-- Purchases: 6 (with items)
-- Sales: 8 (with items)
-- Expenses: 14
-- Notifications: 6

-- Total Investment: PKR 108,000 (from purchases)
-- Total Sales: PKR 12,000 (from sales)
-- Total Expenses: PKR 82,300
