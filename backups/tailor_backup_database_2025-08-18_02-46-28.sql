-- Tailor Management System Database Backup
-- Generated on: 2025-08-18 02:46:28
-- Database: tailor_db

-- Table structure for table `account_cash`
CREATE TABLE `account_cash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(10) NOT NULL COMMENT '1 for cash, 2 for bank',
  `identifier` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `credit` decimal(15,2) NOT NULL,
  `debit` decimal(15,2) NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `backup`
CREATE TABLE `backup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `backup_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `cash_history`
CREATE TABLE `cash_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(15,2) DEFAULT NULL,
  `pay_status` varchar(100) DEFAULT NULL,
  `pay_by` varchar(100) NOT NULL DEFAULT 'Direct',
  `details` varchar(255) DEFAULT NULL,
  `pay_date` date NOT NULL,
  `pay_person` varchar(255) DEFAULT NULL,
  `contact` varchar(150) DEFAULT NULL,
  `pay_type_id` varchar(50) DEFAULT NULL,
  `slip_no` varchar(50) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `expense_id` int(11) DEFAULT NULL,
  `client_payment_id` int(11) DEFAULT NULL,
  `ticket_ptr_id` int(11) DEFAULT NULL,
  `supplier_payment_id` int(11) DEFAULT NULL,
  `ticket_payment_id` int(11) DEFAULT NULL,
  `cust_INOUT_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `cash_in_bank`
CREATE TABLE `cash_in_bank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_tittle` varchar(255) NOT NULL,
  `account_no` varchar(255) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `iban` varchar(255) NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `cash_in_bank_history`
CREATE TABLE `cash_in_bank_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cash_in_bank_id` int(11) NOT NULL,
  `bank_date` date NOT NULL,
  `detail` varchar(255) NOT NULL,
  `credit` decimal(15,2) NOT NULL,
  `debit` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `cash_in_hand`
CREATE TABLE `cash_in_hand` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `opening_balance` decimal(15,2) NOT NULL,
  `cash` decimal(15,2) NOT NULL,
  `opening_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `cash_in_hand`
INSERT INTO `cash_in_hand` VALUES
('1', '0.00', '0.00', '2025-08-11');

-- Table structure for table `cash_transactions`
CREATE TABLE `cash_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` enum('income','expense') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `categories`
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `categories`
INSERT INTO `categories` VALUES
('1', 'Shirts', 'All types of shirts', '1', '2025-08-11 16:31:37'),
('2', 'Pants', 'All types of pants', '1', '2025-08-11 16:31:37'),
('3', 'Suits', 'Formal suits and blazers', '1', '2025-08-11 16:31:37'),
('4', 'Dresses', 'All types of dresses', '1', '2025-08-11 16:31:37'),
('5', 'Alterations', 'Clothing alterations and repairs', '1', '2025-08-11 16:31:37');

-- Table structure for table `cloths_orders`
CREATE TABLE `cloths_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `details` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `cloths_orders`
INSERT INTO `cloths_orders` VALUES
('4', NULL, '1', '2025-08-13', '0000-00-00', '500.00', '0.00', '500.00', '500.00', '0.00', '', 'Pending', '3', '2025-08-13 14:25:00'),
('5', NULL, '1', '2025-08-13', '0000-00-00', '250.00', '0.00', '250.00', '200.00', '50.00', '', 'Completed', '3', '2025-08-13 14:36:24'),
('7', NULL, '3', '2025-08-13', '0000-00-00', '2500.00', '0.00', '2500.00', '2000.00', '500.00', '', 'Pending', '3', '2025-08-13 17:28:27'),
('8', NULL, '3', '2025-08-13', '0000-00-00', '2500.00', '0.00', '2500.00', '2400.00', '100.00', '', 'Pending', '3', '2025-08-13 18:18:34'),
('9', NULL, '6', '2025-08-13', '0000-00-00', '7500.00', '0.00', '7500.00', '7300.00', '200.00', '', 'Pending', '3', '2025-08-13 18:20:48');

-- Table structure for table `customer`
CREATE TABLE `customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `email` varchar(100) NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `customer`
INSERT INTO `customer` VALUES
('4', 'Zia Ur Rehman', 'Doctor Guest House, Street 6, Phase 4, HMC, Hayatabad', '03342332323', 'z.r@gmail.com', '0.00', '1', '2025-08-15 13:03:57');

-- Table structure for table `customer_in_out`
CREATE TABLE `customer_in_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `customer_ledger`
CREATE TABLE `customer_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL DEFAULT 0,
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `Ldate` date NOT NULL,
  `details` varchar(255) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `customer_payment`
CREATE TABLE `customer_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL DEFAULT 0,
  `customer_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `paid` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `customer_payment`
INSERT INTO `customer_payment` VALUES
('1', '0', '4', '1', '12.00', '2025-08-15', '', '', '2025-08-15 13:04:09');

-- Table structure for table `expenses`
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 1,
  `cat_id` int(11) NOT NULL,
  `expense_person` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `details` varchar(255) NOT NULL,
  `exp_date` date NOT NULL,
  `receipt` varchar(255) NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `expenses_category`
CREATE TABLE `expenses_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL DEFAULT 1,
  `expense_cat` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `expenses_category`
INSERT INTO `expenses_category` VALUES
('1', '1', 'Rent', 'Shop rent and utilities', '1', '2025-08-11 16:31:37', NULL),
('2', '1', 'Salaries', 'Employee salaries', '1', '2025-08-11 16:31:37', NULL),
('3', '1', 'Materials', 'Raw materials and supplies', '1', '2025-08-11 16:31:37', NULL),
('4', '1', 'Equipment', 'Equipment maintenance and repair', '1', '2025-08-11 16:31:37', NULL),
('5', '1', 'Marketing', 'Advertising and promotion', '1', '2025-08-11 16:31:37', NULL),
('6', '1', 'Other', 'Miscellaneous expenses', '1', '2025-08-11 16:31:37', NULL);

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `notifications`
INSERT INTO `notifications` VALUES
('1', '1', '', 'Low stock alert: qasd stock is 12 (threshold: 23)', '', '1', '2025-08-12 15:47:43'),
('2', '1', '', 'Low stock alert: alkaram cotan stock is 12 (threshold: 12)', '', '1', '2025-08-13 12:04:32'),
('3', '1', '', 'Low stock alert: João Souza Silva stock is 2 (threshold: 2)', '', '1', '2025-08-13 12:09:35'),
('4', '1', '', 'Low stock alert: João Souza Silva stock is 0 (threshold: 2)', '', '1', '2025-08-13 14:32:21'),
('5', '1', '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', '1', '2025-08-13 14:51:03'),
('6', '1', '', 'Low stock alert: alkaram cotan stock is 8 (threshold: 12)', '', '1', '2025-08-13 14:56:45'),
('7', '1', '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', '1', '2025-08-13 15:09:43'),
('8', '1', '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', '1', '2025-08-13 15:11:13'),
('9', '1', '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', '1', '2025-08-13 15:15:41'),
('10', '1', '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', '1', '2025-08-13 16:00:30'),
('11', '1', '', 'Low stock alert: alkaram cotan stock is 8 (threshold: 12)', '', '1', '2025-08-13 16:20:58'),
('12', '1', '', 'Low stock alert: Royal Tag stock is 23 (threshold: 23)', '', '1', '2025-08-14 15:37:59'),
('13', '1', '', 'Low stock alert: Royal Tag stock is 2 (threshold: 23)', '', '1', '2025-08-14 18:15:25'),
('14', '1', '', 'Low stock alert: Royal Tag stock is 0 (threshold: 23)', '', '1', '2025-08-18 02:36:48');

-- Table structure for table `order_items`
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `order_items`
INSERT INTO `order_items` VALUES
('4', '4', NULL, 'denim', '2', '250.00', '500.00'),
('5', '5', NULL, 'denim', '1', '250.00', '250.00'),
('7', '7', NULL, 'alkaram', '1', '2500.00', '2500.00'),
('8', '8', NULL, 'alkaram', '1', '2500.00', '2500.00'),
('9', '9', NULL, 'alkaram', '2', '2500.00', '5000.00'),
('10', '9', NULL, 'alkaram', '1', '2500.00', '2500.00');

-- Table structure for table `orders`
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `sub_total` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `details` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `orders`
INSERT INTO `orders` VALUES
('4', NULL, '1', '2025-08-13', '0000-00-00', '500.00', '0.00', '500.00', '500.00', '0.00', '', 'Pending', '3', '2025-08-13 14:25:00'),
('5', NULL, '1', '2025-08-13', '0000-00-00', '250.00', '0.00', '250.00', '200.00', '50.00', '', 'Pending', '3', '2025-08-13 14:36:24'),
('7', NULL, '3', '2025-08-13', '0000-00-00', '2500.00', '0.00', '2500.00', '2000.00', '500.00', '', 'Pending', '3', '2025-08-13 17:28:27'),
('8', NULL, '3', '2025-08-13', '0000-00-00', '2500.00', '0.00', '2500.00', '2400.00', '100.00', '', 'Pending', '3', '2025-08-13 18:18:34'),
('9', NULL, '6', '2025-08-13', '0000-00-00', '7500.00', '0.00', '7500.00', '7300.00', '200.00', '', 'Pending', '3', '2025-08-13 18:20:48');

-- Table structure for table `payment_method`
CREATE TABLE `payment_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `payment_method`
INSERT INTO `payment_method` VALUES
('1', 'Cash', '1', '2025-08-11 16:31:37'),
('2', 'Online', '1', '2025-08-11 16:31:37'),
('3', 'Credit Card', '1', '2025-08-11 16:31:37'),
('4', 'Debit Card', '1', '2025-08-11 16:31:37'),
('5', 'Bank Transfer', '1', '2025-08-11 16:31:37'),
('6', 'Mobile Payment', '1', '2025-08-11 16:31:37'),
('7', 'Check', '1', '2025-08-11 16:31:37');

-- Table structure for table `products`
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `brand` varchar(30) NOT NULL,
  `product_unit` varchar(100) NOT NULL,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `product_code` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `alert_quantity` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `products`
INSERT INTO `products` VALUES
('21', 'Royal Tag', 'Junaid Jamshed', 'meter', NULL, 'Green', '232', '1', '23', '', NULL, '1', '2025-08-14 15:37:04');

-- Table structure for table `purchase`
CREATE TABLE `purchase` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `after_discount_purchase` decimal(15,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `purchase_no` varchar(50) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `purchase`
INSERT INTO `purchase` VALUES
('12', '2', '0.00', '2025-08-14', 'INV-001', '529.00', '0.00', '529.00', '520.00', '9.00', '1', '', 'completed', '1', '2025-08-14 15:37:59'),
('14', '2', '144.00', '2025-08-15', 'INV-013', '144.00', '0.00', '144.00', '0.00', '144.00', '0', '', 'completed', '1', '2025-08-15 12:51:04');

-- Table structure for table `purchase_items`
CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_code` varchar(255) NOT NULL,
  `color` varchar(7) DEFAULT '#000000',
  `purchase_price` decimal(15,2) NOT NULL,
  `sale_price` decimal(15,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `purchase_total` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `purchase_items`
INSERT INTO `purchase_items` VALUES
('12', '12', '21', '232', '#000000', '23.00', '23.00', '23', '529.00'),
('14', '14', '21', '', '#000000', '12.00', '122.00', '12', '144.00');

-- Table structure for table `purchase_return`
CREATE TABLE `purchase_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_qty` int(11) NOT NULL,
  `return_price` decimal(15,2) NOT NULL,
  `return_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `return_percale`
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `return_percale`
INSERT INTO `return_percale` VALUES
('1', 'RET-001', 'customer_return', '4', NULL, NULL, NULL, NULL, 'Premium Cotton Percale', 'Cotton', 'White', '5.00', 'meters', '150.00', '120.00', 'Fabric quality not as expected', '2025-08-17', 'pending', NULL, NULL, 'Customer complaint about fabric texture', '1', '2025-08-18 03:32:48', '2025-08-18 04:03:27'),
('2', 'RET-002', 'customer_return', '4', NULL, NULL, NULL, NULL, 'Silk Blend Percale', 'Silk', 'Blue', '3.50', 'meters', '200.00', '180.00', 'Color mismatch with order', '2025-08-17', 'rejected', '1', '2025-08-18', 'Supplier accepted return due to color variation', '1', '2025-08-18 03:32:48', '2025-08-18 04:04:14');

-- Table structure for table `roles`
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `permissions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `roles`
INSERT INTO `roles` VALUES
('1', 'Admin', 'all', '2025-08-11 16:31:37'),
('2', 'Manager', 'sales,purchases,customers,suppliers,reports', '2025-08-11 16:31:37'),
('3', 'Tailor', 'sales,customers', '2025-08-11 16:31:37'),
('4', 'Cashier', 'sales,customers', '2025-08-11 16:31:37');

-- Table structure for table `sale`
CREATE TABLE `sale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `walk_in_cust_name` varchar(255) NOT NULL DEFAULT '0',
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `after_discount` decimal(15,2) NOT NULL,
  `sale_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `sale_no` varchar(50) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `delivery_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `sale`
INSERT INTO `sale` VALUES
('13', NULL, 'ahmad', '0.00', '529.00', '2025-08-14', NULL, 'SALE-001', '529.00', '529.00', '500.00', '29.00', '1', 'next time they will be pay the charges', 'pending', NULL, '1', '2025-08-14 18:15:25'),
('14', '4', '', '0.00', '275.54', '2025-08-17', NULL, 'SALE-014', '275.54', '275.54', '0.00', '275.54', '7', '', 'pending', NULL, '1', '2025-08-18 02:36:48');

-- Table structure for table `sale_items`
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL DEFAULT 0,
  `product_code` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `stock_qty` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `sale_items`
INSERT INTO `sale_items` VALUES
('11', '13', '21', '0', '232', '23.00', '23', '23', '529.00', 'Shirts', NULL),
('12', '14', '21', '0', '', '23.00', '12', '12', '275.54', 'Shirts', 'Color: Yellow');

-- Table structure for table `sale_return`
CREATE TABLE `sale_return` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `return_price` decimal(15,2) NOT NULL,
  `return_date` date NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `settings`
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text DEFAULT NULL,
  `setting_type` enum('text','number','select','textarea','boolean') DEFAULT 'text',
  `setting_group` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `settings`
INSERT INTO `settings` VALUES
('85', 'company_name', 'WASEM WEARS', 'Company/Business Name', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('86', 'company_tagline', 'Professional Tailoring Services', 'Company Tagline or Description', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('87', 'company_phone', '+92 323 9507813', 'Company Phone Number', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('88', 'company_email', 'info@tailorshop.com', 'Company Email Address', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('89', 'company_address', 'Address shop #1 hameed plaza main university road Pakistan', 'Company Address', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('90', 'company_website', 'www.tailorshop.com', 'Company Website', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('91', 'company_logo', '', 'Company Logo URL (optional)', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('92', 'currency_symbol', 'PKR', 'Currency Symbol', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('93', 'currency_name', 'Pakistani Rupee', 'Currency Name', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('94', 'invoice_prefix', 'INV', 'Invoice Number Prefix', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('95', 'purchase_prefix', 'PUR', 'Purchase Invoice Prefix', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('96', 'sale_prefix', 'SALE', 'Sale Invoice Prefix', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('97', 'footer_text', 'Thank you for your business!', 'Footer Text for Invoices', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('98', 'print_header', 'Computer Generated Invoice', 'Print Header Text', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('99', 'low_stock_threshold', '10', 'Low Stock Alert Threshold', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('100', 'business_hours', '9:00 AM - 6:00 PM', 'Business Hours', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('101', 'business_days', 'Monday - Saturday', 'Business Days', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('102', 'date_format', 'd/m/Y', 'Date Format', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('103', 'time_format', 'H:i:s', 'Time Format', 'text', 'general', '1', '2025-08-18 05:27:12', '2025-08-18 05:27:12'),
('104', 'auto_backup', '1', 'Enable Auto Backup', 'text', 'general', '1', '2025-08-18 05:28:18', '2025-08-18 05:28:18'),
('105', 'backup_frequency', 'weekly', 'Backup Frequency', 'text', 'general', '1', '2025-08-18 05:28:18', '2025-08-18 05:28:18'),
('106', 'backup_retention', '60', 'Backup Retention Days', 'text', 'general', '1', '2025-08-18 05:28:18', '2025-08-18 05:28:18'),
('107', 'backup_location', 'backups/', 'Backup location', 'text', 'general', '1', '2025-08-18 05:35:59', '2025-08-18 05:35:59'),
('108', 'backup_type', 'full', 'Backup type', 'text', 'general', '1', '2025-08-18 05:35:59', '2025-08-18 05:35:59'),
('109', 'last_backup_date', '2025-08-18 02:46:17', 'Last Backup Date', 'text', 'general', '1', '2025-08-18 05:37:04', '2025-08-18 05:46:17');

-- Table structure for table `stock_items`
CREATE TABLE `stock_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `purchase_item_id` int(11) NOT NULL DEFAULT 0,
  `product_code` varchar(255) NOT NULL,
  `color` varchar(7) DEFAULT '#000000',
  `quantity` int(11) NOT NULL,
  `purchase_price` decimal(15,2) NOT NULL,
  `sale_price` decimal(15,2) NOT NULL,
  `stock_date` date NOT NULL,
  `status` enum('available','reserved','sold') NOT NULL DEFAULT 'available',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `stock_items`
INSERT INTO `stock_items` VALUES
('13', '21', '12', '232', '#000000', '0', '23.00', '23.00', '2025-08-14', 'available'),
('15', '21', '14', '', '#000000', '0', '12.00', '122.00', '2025-08-15', 'sold'),
('17', '21', '0', '232-20250818-010', '#000000', '12', '33.00', '233.00', '2025-08-17', 'available'),
('18', '21', '0', '232-20250818-208', '#000000', '23', '32.00', '222.00', '2025-08-17', 'available');

-- Table structure for table `supplier`
CREATE TABLE `supplier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(255) NOT NULL,
  `supplier_contact` text NOT NULL,
  `supplier_open_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `supplier_address` varchar(255) NOT NULL,
  `supplier_email` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `supplier`
INSERT INTO `supplier` VALUES
('2', 'Zubair Khan', '03341234567', '0.00', 'hayatabad
Doctor Guest House', 'Zubair Khan', '1', '2025-08-14 15:34:58', '12.00');

-- Table structure for table `supplier_ledger`
CREATE TABLE `supplier_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL DEFAULT 0,
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `Ldate` date NOT NULL,
  `details` varchar(255) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `supplier_ledger`
INSERT INTO `supplier_ledger` VALUES
('1', '2', '14', '0', '144.00', '0.00', '2025-08-15', 'Purchase: Royal Tag', '144.00', '2025-08-15 12:51:04'),
('2', '2', '15', '0', '144.00', '0.00', '2025-08-15', 'Purchase: Royal Tag', '144.00', '2025-08-15 12:52:21');

-- Table structure for table `supplier_payment`
CREATE TABLE `supplier_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL DEFAULT 0,
  `supplier_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `paid` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `supplier_payments`
CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `payment_amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `fk_supplier_payments_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `supplier_payments`
INSERT INTO `supplier_payments` VALUES
('2', '2', '12.00', '2025-08-14', 'Cash', '', '', '2025-08-14 17:47:33');

-- Table structure for table `system_settings`
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL DEFAULT 'Tailor Shop',
  `company_address` text NOT NULL,
  `company_phone` varchar(50) NOT NULL,
  `company_email` varchar(100) NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `system_users`
CREATE TABLE `system_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `signupdate` date NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `system_users`
INSERT INTO `system_users` VALUES
('1', '1', '1', 'Administrator', 'admin', 'admin@tailorshop.com', '$2y$10$YiIrWv6lyeh6E9YTqyoZ3.6FBYR/yRzpWNy8zKmC46XUCxIR6ZRPG', '+1234567890', NULL, '123 Main Street, City, Country', '2025-08-11', NULL);

-- Table structure for table `unit_prices`
CREATE TABLE `unit_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(100) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_name` (`unit_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `unit_prices`
INSERT INTO `unit_prices` VALUES
('2', 'denim', '250.00', '2025-08-13 13:05:02'),
('3', 'alkaram', '2500.00', '2025-08-13 16:52:19');

-- Table structure for table `units`
CREATE TABLE `units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unit` varchar(255) NOT NULL,
  `short_name` varchar(10) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table `units`
INSERT INTO `units` VALUES
('1', 'Piece', 'pc', '1', '2025-08-11 16:31:38'),
('2', 'Meter', 'm', '1', '2025-08-11 16:31:38'),
('3', 'Yard', 'yd', '1', '2025-08-11 16:31:38'),
('4', 'Centimeter', 'cm', '1', '2025-08-11 16:31:38');

