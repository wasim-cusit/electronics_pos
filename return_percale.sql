-- Return Percale Table Structure
-- This table manages fabric returns (percale) from customers or suppliers

CREATE TABLE `return_percale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_no` varchar(50) NOT NULL,
  `return_type` enum('customer_return','supplier_return') NOT NULL DEFAULT 'customer_return',
  `customer_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `fabric_name` varchar(255) NOT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT 'meters',
  `original_price` decimal(15,2) NOT NULL,
  `return_price` decimal(15,2) NOT NULL,
  `return_reason` text DEFAULT NULL,
  `return_date` date NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_no` (`return_no`),
  KEY `customer_id` (`customer_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `order_id` (`order_id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `product_id` (`product_id`),
  KEY `status` (`status`),
  KEY `return_date` (`return_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for testing
INSERT INTO `return_percale` (`return_no`, `return_type`, `customer_id`, `fabric_name`, `fabric_type`, `color`, `quantity`, `unit`, `original_price`, `return_price`, `return_reason`, `return_date`, `status`, `notes`, `created_by`) VALUES
('RET-001', 'customer_return', 1, 'Premium Cotton Percale', 'Cotton', 'White', 5.00, 'meters', 150.00, 120.00, 'Fabric quality not as expected', '2025-08-17', 'pending', 'Customer complaint about fabric texture', 1),
('RET-002', 'supplier_return', NULL, 'Silk Blend Percale', 'Silk', 'Blue', 3.50, 'meters', 200.00, 180.00, 'Color mismatch with order', '2025-08-17', 'approved', 'Supplier accepted return due to color variation', 1);
