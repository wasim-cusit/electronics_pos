-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2025 at 02:03 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tailor_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_cash`
--

CREATE TABLE `account_cash` (
  `id` int(11) NOT NULL,
  `type` int(10) NOT NULL COMMENT '1 for cash, 2 for bank',
  `identifier` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `credit` decimal(15,2) NOT NULL,
  `debit` decimal(15,2) NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup`
--

CREATE TABLE `backup` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `backup_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_history`
--

CREATE TABLE `cash_history` (
  `id` int(11) NOT NULL,
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
  `cust_INOUT_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_in_bank`
--

CREATE TABLE `cash_in_bank` (
  `id` int(11) NOT NULL,
  `account_tittle` varchar(255) NOT NULL,
  `account_no` varchar(255) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `iban` varchar(255) NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_in_bank_history`
--

CREATE TABLE `cash_in_bank_history` (
  `id` int(11) NOT NULL,
  `cash_in_bank_id` int(11) NOT NULL,
  `bank_date` date NOT NULL,
  `detail` varchar(255) NOT NULL,
  `credit` decimal(15,2) NOT NULL,
  `debit` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_in_hand`
--

CREATE TABLE `cash_in_hand` (
  `id` int(11) NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL,
  `cash` decimal(15,2) NOT NULL,
  `opening_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_in_hand`
--

INSERT INTO `cash_in_hand` (`id`, `opening_balance`, `cash`, `opening_date`) VALUES
(1, 0.00, 0.00, '2025-08-11');

-- --------------------------------------------------------

--
-- Table structure for table `cash_transactions`
--

CREATE TABLE `cash_transactions` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category`, `description`, `status`, `created_at`) VALUES
(1, 'Air Conditioners', 'AC units, split AC, window AC, portable AC', 1, '2025-08-18 15:00:00'),
(2, 'Fragrances & Perfumes', 'Men and women perfumes, body sprays, deodorants', 1, '2025-08-18 15:00:00'),
(3, 'Mobile Phones', 'Smartphones, feature phones, accessories', 1, '2025-08-18 15:00:00'),
(4, 'Laptops & Computers', 'Laptops, desktops, tablets, accessories', 1, '2025-08-18 15:00:00'),
(5, 'Home Appliances', 'Refrigerators, washing machines, microwaves', 1, '2025-08-18 15:00:00'),
(6, 'TV & Audio', 'Televisions, speakers, headphones, sound systems', 1, '2025-08-18 15:00:00'),
(7, 'Kitchen Appliances', 'Blenders, mixers, toasters, coffee makers', 1, '2025-08-18 15:00:00'),
(8, 'Gaming & Entertainment', 'Gaming consoles, controllers, games', 1, '2025-08-18 15:00:00'),
(9, 'Security & Surveillance', 'CCTV cameras, security systems, locks', 1, '2025-08-18 15:00:00'),
(10, 'Lighting & Electrical', 'LED lights, switches, wiring, electrical items', 1, '2025-08-18 15:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `cloths_orders`
--

CREATE TABLE `cloths_orders` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cloths_orders`
--

INSERT INTO `cloths_orders` (`id`, `order_no`, `customer_id`, `order_date`, `delivery_date`, `sub_total`, `discount`, `total_amount`, `paid_amount`, `remaining_amount`, `details`, `status`, `created_by`, `created_at`) VALUES
(4, NULL, 1, '2025-08-13', '0000-00-00', 500.00, 0.00, 500.00, 500.00, 0.00, '', 'Pending', 3, '2025-08-13 09:25:00'),
(5, NULL, 1, '2025-08-13', '0000-00-00', 250.00, 0.00, 250.00, 200.00, 50.00, '', 'Completed', 3, '2025-08-13 09:36:24'),
(7, NULL, 3, '2025-08-13', '0000-00-00', 2500.00, 0.00, 2500.00, 2000.00, 500.00, '', 'Pending', 3, '2025-08-13 12:28:27'),
(8, NULL, 3, '2025-08-13', '0000-00-00', 2500.00, 0.00, 2500.00, 2400.00, 100.00, '', 'Pending', 3, '2025-08-13 13:18:34'),
(9, NULL, 6, '2025-08-13', '0000-00-00', 7500.00, 0.00, 7500.00, 7300.00, 200.00, '', 'Pending', 3, '2025-08-13 13:20:48');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `mobile` varchar(30) NOT NULL,
  `cnic` varchar(15) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `name`, `mobile`, `cnic`, `email`, `address`, `opening_balance`, `status`, `created_at`) VALUES
(1, 'Ahmed Khan', '0300-1111111', '35202-1234567-1', 'ahmed@email.com', 'House #45, Street 7, F-8/1, Islamabad', 0.00, 1, '2025-08-18 15:00:00'),
(2, 'Fatima Ali', '0300-2222222', '35202-2345678-2', 'fatima@email.com', 'Apartment 12, Block C, Blue Area, Islamabad', 0.00, 1, '2025-08-18 15:00:00'),
(3, 'Usman Hassan', '0300-3333333', '35202-3456789-3', 'usman@email.com', 'Shop #23, Jinnah Super Market, Islamabad', 0.00, 1, '2025-08-18 15:00:00'),
(4, 'Ayesha Malik', '0300-4444444', '35202-4567890-4', 'ayesha@email.com', 'House #67, Street 12, G-9/1, Islamabad', 0.00, 1, '2025-08-18 15:00:00'),
(5, 'Bilal Ahmed', '0300-5555555', '35202-5678901-5', 'bilal@email.com', 'Office #8, 3rd Floor, Centaurus Mall, Islamabad', 0.00, 1, '2025-08-18 15:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `customer_in_out`
--

CREATE TABLE `customer_in_out` (
  `id` int(11) NOT NULL,
  `customer` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_ledger`
--

CREATE TABLE `customer_ledger` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL DEFAULT 0,
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `Ldate` date NOT NULL,
  `details` varchar(255) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_payment`
--

CREATE TABLE `customer_payment` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL DEFAULT 0,
  `customer_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `paid` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_payment`
--

INSERT INTO `customer_payment` (`id`, `sale_id`, `customer_id`, `payment_method_id`, `paid`, `payment_date`, `details`, `receipt`, `created_at`) VALUES
(1, 0, 4, 1, 12.00, '2025-08-15', '', '', '2025-08-15 08:04:09');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT 1,
  `cat_id` int(11) NOT NULL,
  `expense_person` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `details` varchar(255) NOT NULL,
  `exp_date` date NOT NULL,
  `receipt` varchar(255) NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses_category`
--

CREATE TABLE `expenses_category` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT 1,
  `expense_cat` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses_category`
--

INSERT INTO `expenses_category` (`id`, `company_id`, `expense_cat`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rent', 'Shop rent and utilities', 1, '2025-08-11 11:31:37', NULL),
(2, 1, 'Salaries', 'Employee salaries', 1, '2025-08-11 11:31:37', NULL),
(3, 1, 'Materials', 'Raw materials and supplies', 1, '2025-08-11 11:31:37', NULL),
(4, 1, 'Equipment', 'Equipment maintenance and repair', 1, '2025-08-11 11:31:37', NULL),
(5, 1, 'Marketing', 'Advertising and promotion', 1, '2025-08-11 11:31:37', NULL),
(6, 1, 'Other', 'Miscellaneous expenses', 1, '2025-08-11 11:31:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, '', 'Low stock alert: qasd stock is 12 (threshold: 23)', '', 1, '2025-08-12 10:47:43'),
(2, 1, '', 'Low stock alert: alkaram cotan stock is 12 (threshold: 12)', '', 1, '2025-08-13 07:04:32'),
(3, 1, '', 'Low stock alert: João Souza Silva stock is 2 (threshold: 2)', '', 1, '2025-08-13 07:09:35'),
(4, 1, '', 'Low stock alert: João Souza Silva stock is 0 (threshold: 2)', '', 1, '2025-08-13 09:32:21'),
(5, 1, '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', 1, '2025-08-13 09:51:03'),
(6, 1, '', 'Low stock alert: alkaram cotan stock is 8 (threshold: 12)', '', 1, '2025-08-13 09:56:45'),
(7, 1, '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', 1, '2025-08-13 10:09:43'),
(8, 1, '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', 1, '2025-08-13 10:11:13'),
(9, 1, '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', 1, '2025-08-13 10:15:41'),
(10, 1, '', 'Low stock alert: alkaram cotan stock is 10 (threshold: 12)', '', 1, '2025-08-13 11:00:30'),
(11, 1, '', 'Low stock alert: alkaram cotan stock is 8 (threshold: 12)', '', 1, '2025-08-13 11:20:58'),
(12, 1, '', 'Low stock alert: Royal Tag stock is 23 (threshold: 23)', '', 1, '2025-08-14 10:37:59'),
(13, 1, '', 'Low stock alert: Royal Tag stock is 2 (threshold: 23)', '', 1, '2025-08-14 13:15:25'),
(14, 1, '', 'Low stock alert: Royal Tag stock is 0 (threshold: 23)', '', 1, '2025-08-17 21:36:48');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `customer_id`, `order_date`, `delivery_date`, `sub_total`, `discount`, `total_amount`, `paid_amount`, `remaining_amount`, `details`, `status`, `created_by`, `created_at`) VALUES
(4, NULL, 1, '2025-08-13', '0000-00-00', 500.00, 0.00, 500.00, 500.00, 0.00, '', 'Pending', 3, '2025-08-13 09:25:00'),
(5, NULL, 1, '2025-08-13', '0000-00-00', 250.00, 0.00, 250.00, 200.00, 50.00, '', 'Pending', 3, '2025-08-13 09:36:24'),
(7, NULL, 3, '2025-08-13', '0000-00-00', 2500.00, 0.00, 2500.00, 2000.00, 500.00, '', 'Pending', 3, '2025-08-13 12:28:27'),
(8, NULL, 3, '2025-08-13', '0000-00-00', 2500.00, 0.00, 2500.00, 2400.00, 100.00, '', 'Pending', 3, '2025-08-13 13:18:34'),
(9, NULL, 6, '2025-08-13', '0000-00-00', 7500.00, 0.00, 7500.00, 7300.00, 200.00, '', 'Pending', 3, '2025-08-13 13:20:48');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `description`, `quantity`, `unit_price`, `total_price`) VALUES
(4, 4, NULL, 'denim', 2, 250.00, 500.00),
(5, 5, NULL, 'denim', 1, 250.00, 250.00),
(7, 7, NULL, 'alkaram', 1, 2500.00, 2500.00),
(8, 8, NULL, 'alkaram', 1, 2500.00, 2500.00),
(9, 9, NULL, 'alkaram', 2, 2500.00, 5000.00),
(10, 9, NULL, 'alkaram', 1, 2500.00, 2500.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `id` int(11) NOT NULL,
  `method` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`id`, `method`, `status`, `created_at`) VALUES
(1, 'Cash', 1, '2025-08-11 11:31:37'),
(2, 'Online', 1, '2025-08-11 11:31:37'),
(3, 'Credit Card', 1, '2025-08-11 11:31:37'),
(4, 'Debit Card', 1, '2025-08-11 11:31:37'),
(5, 'Bank Transfer', 1, '2025-08-11 11:31:37'),
(6, 'Mobile Payment', 1, '2025-08-11 11:31:37'),
(7, 'Check', 1, '2025-08-11 11:31:37');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `brand`, `product_unit`, `size`, `color`, `product_code`, `category_id`, `alert_quantity`, `description`, `product_image`, `status`, `created_at`) VALUES
(1, 'Split AC 1.5 Ton', 'Haier', 'piece', NULL, NULL, 'AC-001', 1, 1, 'High efficiency split air conditioner', NULL, 1, '2025-08-18 15:00:00'),
(2, 'Window AC 1 Ton', 'Gree', 'piece', NULL, NULL, 'AC-002', 1, 1, 'Energy efficient window air conditioner', NULL, 1, '2025-08-18 15:00:00'),
(3, 'Portable AC', 'LG', 'piece', NULL, NULL, 'AC-003', 1, 1, 'Mobile air conditioning unit', NULL, 1, '2025-08-18 15:00:00'),
(4, 'Men\'s Cologne', 'Versace', 'piece', NULL, NULL, 'FRG-001', 2, 5, 'Luxury men\'s fragrance', NULL, 1, '2025-08-18 15:00:00'),
(5, 'Women\'s Perfume', 'Chanel', 'piece', NULL, NULL, 'FRG-002', 2, 5, 'Elegant women\'s perfume', NULL, 1, '2025-08-18 15:00:00'),
(6, 'Body Spray', 'Axe', 'piece', NULL, NULL, 'FRG-003', 2, 10, 'Refreshing body spray', NULL, 1, '2025-08-18 15:00:00'),
(7, 'Deodorant Roll-on', 'Nivea', 'piece', NULL, NULL, 'FRG-004', 2, 15, 'Long-lasting deodorant', NULL, 1, '2025-08-18 15:00:00'),
(8, 'iPhone 15 Pro', 'Apple', 'piece', NULL, NULL, 'MBL-001', 3, 2, 'Latest iPhone with advanced features', NULL, 1, '2025-08-18 15:00:00'),
(9, 'Samsung Galaxy S24', 'Samsung', 'piece', NULL, NULL, 'MBL-002', 3, 3, 'Premium Android smartphone', NULL, 1, '2025-08-18 15:00:00'),
(10, 'Mobile Charger', 'Anker', 'piece', NULL, NULL, 'MBL-003', 3, 20, 'Fast charging USB-C charger', NULL, 1, '2025-08-18 15:00:00'),
(11, 'Phone Case', 'Spigen', 'piece', NULL, NULL, 'MBL-004', 3, 25, 'Protective phone case', NULL, 1, '2025-08-18 15:00:00'),
(12, 'MacBook Air M2', 'Apple', 'piece', NULL, NULL, 'LAP-001', 4, 1, 'Lightweight laptop with M2 chip', NULL, 1, '2025-08-18 15:00:00'),
(13, 'Dell Inspiron 15', 'Dell', 'piece', NULL, NULL, 'LAP-002', 4, 2, 'Affordable business laptop', NULL, 1, '2025-08-18 15:00:00'),
(14, 'Wireless Mouse', 'Logitech', 'piece', NULL, NULL, 'LAP-003', 4, 15, 'Ergonomic wireless mouse', NULL, 1, '2025-08-18 15:00:00'),
(15, 'USB Keyboard', 'Microsoft', 'piece', NULL, NULL, 'LAP-004', 4, 12, 'Comfortable USB keyboard', NULL, 1, '2025-08-18 15:00:00'),
(16, 'Refrigerator 500L', 'Whirlpool', 'piece', NULL, NULL, 'HAP-001', 5, 1, 'Large capacity refrigerator', NULL, 1, '2025-08-18 15:00:00'),
(17, 'Washing Machine 8kg', 'Samsung', 'piece', NULL, NULL, 'HAP-002', 5, 2, 'Front load washing machine', NULL, 1, '2025-08-18 15:00:00'),
(18, 'Microwave Oven', 'Panasonic', 'piece', NULL, NULL, 'HAP-003', 5, 3, '20L microwave with grill', NULL, 1, '2025-08-18 15:00:00'),
(19, 'Smart TV 55"', 'LG', 'piece', NULL, NULL, 'TVA-001', 6, 2, '4K Smart LED TV', NULL, 1, '2025-08-18 15:00:00'),
(20, 'Bluetooth Speaker', 'JBL', 'piece', NULL, NULL, 'TVA-002', 6, 8, 'Portable wireless speaker', NULL, 1, '2025-08-18 15:00:00'),
(21, 'Gaming Headset', 'Razer', 'piece', NULL, NULL, 'TVA-003', 6, 10, 'Professional gaming headset', NULL, 1, '2025-08-18 15:00:00'),
(22, 'Blender 1000W', 'Philips', 'piece', NULL, NULL, 'KIT-001', 7, 5, 'High power blender', NULL, 1, '2025-08-18 15:00:00'),
(23, 'Coffee Maker', 'Bialetti', 'piece', NULL, NULL, 'KIT-002', 7, 4, 'Italian coffee maker', NULL, 1, '2025-08-18 15:00:00'),
(24, 'Toaster 4-Slice', 'Breville', 'piece', NULL, NULL, 'KIT-003', 7, 6, '4-slice toaster with timer', NULL, 1, '2025-08-18 15:00:00'),
(25, 'PlayStation 5', 'Sony', 'piece', NULL, NULL, 'GAM-001', 8, 1, 'Next-gen gaming console', NULL, 1, '2025-08-18 15:00:00'),
(26, 'Xbox Controller', 'Microsoft', 'piece', NULL, NULL, 'GAM-002', 8, 8, 'Wireless Xbox controller', NULL, 1, '2025-08-18 15:00:00'),
(27, 'Gaming Mouse', 'SteelSeries', 'piece', NULL, NULL, 'GAM-003', 8, 12, 'High DPI gaming mouse', NULL, 1, '2025-08-18 15:00:00'),
(28, 'CCTV Camera', 'Hikvision', 'piece', NULL, NULL, 'SEC-001', 9, 10, 'HD security camera', NULL, 1, '2025-08-18 15:00:00'),
(29, 'Smart Lock', 'Yale', 'piece', NULL, NULL, 'SEC-002', 9, 5, 'WiFi enabled smart lock', NULL, 1, '2025-08-18 15:00:00'),
(30, 'Motion Sensor', 'Ring', 'piece', NULL, NULL, 'SEC-003', 9, 15, 'Wireless motion detector', NULL, 1, '2025-08-18 15:00:00'),
(31, 'LED Bulb 10W', 'Philips', 'piece', NULL, NULL, 'LIT-001', 10, 50, 'Energy efficient LED bulb', NULL, 1, '2025-08-18 15:00:00'),
(32, 'Smart Switch', 'TP-Link', 'piece', NULL, NULL, 'LIT-002', 10, 20, 'WiFi controlled light switch', NULL, 1, '2025-08-18 15:00:00'),
(33, 'LED Strip 5M', 'Govee', 'piece', NULL, NULL, 'LIT-003', 10, 25, 'RGB LED strip with remote', NULL, 1, '2025-08-18 15:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`id`, `supplier_id`, `after_discount_purchase`, `purchase_date`, `purchase_no`, `subtotal`, `discount`, `total_amount`, `paid_amount`, `due_amount`, `payment_method_id`, `notes`, `status`, `created_by`, `created_at`) VALUES
(12, 2, 0.00, '2025-08-14', 'INV-001', 529.00, 0.00, 529.00, 520.00, 9.00, 1, '', 'completed', 1, '2025-08-14 10:37:59'),
(14, 2, 144.00, '2025-08-15', 'INV-013', 144.00, 0.00, 144.00, 0.00, 144.00, 0, '', 'completed', 1, '2025-08-15 07:51:04'),
(16, 3, 0.00, '2025-08-18', 'INV-015', 529.00, 0.00, 529.00, 2343.00, -1814.00, 0, '', 'completed', 1, '2025-08-18 01:33:29');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_code` varchar(255) NOT NULL,
  `color` varchar(7) DEFAULT '#000000',
  `purchase_price` decimal(15,2) NOT NULL,
  `sale_price` decimal(15,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `purchase_total` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `product_code`, `color`, `purchase_price`, `sale_price`, `quantity`, `purchase_total`) VALUES
(12, 12, 21, '232', '#000000', 23.00, 23.00, 23, 529.00),
(14, 14, 21, '', '#000000', 12.00, 122.00, 12, 144.00),
(16, 16, 21, '232', 'Blue', 23.00, 23.00, 23, 529.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return`
--

CREATE TABLE `purchase_return` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_qty` int(11) NOT NULL,
  `return_price` decimal(15,2) NOT NULL,
  `return_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_percale`
--

CREATE TABLE `return_percale` (
  `id` int(11) NOT NULL,
  `return_no` varchar(50) NOT NULL,
  `return_type` enum('customer_return','supplier_return') NOT NULL DEFAULT 'customer_return',
  `customer_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `fabric_name` varchar(255) NOT NULL,
  `fabric_type` varchar(100) DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_percale`
--

INSERT INTO `return_percale` (`id`, `return_no`, `return_type`, `customer_id`, `supplier_id`, `order_id`, `purchase_id`, `product_id`, `fabric_name`, `fabric_type`, `quantity`, `unit`, `original_price`, `return_price`, `return_reason`, `return_date`, `status`, `approved_by`, `approved_date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'RET-001', 'customer_return', 4, NULL, NULL, NULL, NULL, 'Premium Cotton Percale', 'Cotton', 5.00, 'meters', 150.00, 120.00, 'Fabric quality not as expected', '2025-08-17', 'pending', NULL, NULL, 'Customer complaint about fabric texture', 1, '2025-08-17 22:32:48', '2025-08-17 23:03:27'),
(2, 'RET-002', 'customer_return', 4, NULL, NULL, NULL, NULL, 'Silk Blend Percale', 'Silk', 3.50, 'meters', 200.00, 180.00, 'Color mismatch with order', '2025-08-17', 'rejected', 1, '2025-08-18', 'Supplier accepted return due to color variation', 1, '2025-08-17 22:32:48', '2025-08-17 23:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `permissions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `permissions`, `created_at`) VALUES
(1, 'Admin', 'all', '2025-08-11 11:31:37'),
(2, 'Manager', 'sales,purchases,customers,suppliers,reports', '2025-08-11 11:31:37'),
(3, 'Tailor', 'sales,customers', '2025-08-11 11:31:37'),
(4, 'Cashier', 'sales,customers', '2025-08-11 11:31:37');

-- --------------------------------------------------------

--
-- Table structure for table `sale`
--

CREATE TABLE `sale` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `walk_in_cust_name` varchar(255) NOT NULL DEFAULT '0',
  `customer_cnic` varchar(15) DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale`
--

INSERT INTO `sale` (`id`, `customer_id`, `walk_in_cust_name`, `discount`, `after_discount`, `sale_date`, `expiry_date`, `sale_no`, `subtotal`, `total_amount`, `paid_amount`, `due_amount`, `payment_method_id`, `notes`, `status`, `delivery_date`, `created_by`, `created_at`) VALUES
(13, NULL, 'ahmad', 0.00, 529.00, '2025-08-14', NULL, 'SALE-001', 529.00, 529.00, 500.00, 29.00, 1, 'next time they will be pay the charges', 'pending', NULL, 1, '2025-08-14 13:15:25'),
(14, 4, '', 0.00, 275.54, '2025-08-17', NULL, 'SALE-014', 275.54, 275.54, 0.00, 275.54, 7, '', 'pending', NULL, 1, '2025-08-17 21:36:48');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL DEFAULT 0,
  `product_code` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `stock_qty` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `warehouse_id`, `product_code`, `price`, `stock_qty`, `quantity`, `total_price`, `category_name`, `notes`) VALUES
(11, 13, 21, 0, '232', 23.00, 23, 23, 529.00, 'Shirts', NULL),
(12, 14, 21, 0, '', 23.00, 12, 12, 275.54, 'Shirts', 'Color: Yellow');

-- --------------------------------------------------------

--
-- Table structure for table `sale_return`
--

CREATE TABLE `sale_return` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `return_price` decimal(15,2) NOT NULL,
  `return_date` date NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text DEFAULT NULL,
  `setting_type` enum('text','number','select','textarea','boolean') DEFAULT 'text',
  `setting_group` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_description`, `setting_type`, `setting_group`, `is_public`, `created_at`, `updated_at`) VALUES
(85, 'company_name', 'ELECTRONICS STORE', 'Company/Business Name', 'text', 'general', 1, '2025-08-18 15:00:00', '2025-08-18 15:00:00'),
(86, 'company_tagline', 'Your One-Stop Electronics Shop', 'Company Tagline or Description', 'text', 'general', 1, '2025-08-18 15:00:00', '2025-08-18 15:00:00'),
(87, 'company_phone', '+92-300-1234567', 'Company Phone Number', 'text', 'general', 1, '2025-08-18 15:00:00', '2025-08-18 15:00:00'),
(88, 'company_email', 'info@electronicsstore.pk', 'Company Email Address', 'text', 'general', 1, '2025-08-18 15:00:00', '2025-08-18 15:00:00'),
(89, 'company_address', 'Address shop #1 hameed plaza main university road Pakistan', 'Company Address', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(90, 'company_website', 'www.electronicsstore.pk', 'Company Website', 'text', 'general', 1, '2025-08-18 15:00:00', '2025-08-18 15:00:00'),
(91, 'company_logo', '', 'Company Logo URL (optional)', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(92, 'currency_symbol', 'PKR', 'Currency Symbol', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(93, 'currency_name', 'Pakistani Rupee', 'Currency Name', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(94, 'invoice_prefix', 'INV', 'Invoice Number Prefix', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(95, 'purchase_prefix', 'PUR', 'Purchase Invoice Prefix', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(96, 'sale_prefix', 'SALE', 'Sale Invoice Prefix', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(97, 'footer_text', 'Thank you for your business!', 'Footer Text for Invoices', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(98, 'print_header', 'Computer Generated Invoice', 'Print Header Text', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(99, 'low_stock_threshold', '10', 'Low Stock Alert Threshold', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(100, 'business_hours', '9:00 AM - 6:00 PM', 'Business Hours', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(101, 'business_days', 'Monday - Saturday', 'Business Days', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(102, 'date_format', 'd/m/Y', 'Date Format', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(103, 'time_format', 'H:i:s', 'Time Format', 'text', 'general', 1, '2025-08-18 00:27:12', '2025-08-18 00:27:12'),
(104, 'auto_backup', '1', 'Enable Auto Backup', 'text', 'general', 1, '2025-08-18 00:28:18', '2025-08-18 00:28:18'),
(105, 'backup_frequency', 'weekly', 'Backup Frequency', 'text', 'general', 1, '2025-08-18 00:28:18', '2025-08-18 00:28:18'),
(106, 'backup_retention', '60', 'Backup Retention Days', 'text', 'general', 1, '2025-08-18 00:28:18', '2025-08-18 00:28:18'),
(107, 'backup_location', 'backups/', 'Backup location', 'text', 'general', 1, '2025-08-18 00:35:59', '2025-08-18 00:35:59'),
(108, 'backup_type', 'full', 'Backup type', 'text', 'general', 1, '2025-08-18 00:35:59', '2025-08-18 00:35:59'),
(109, 'last_backup_date', '2025-08-18 02:46:28', 'Last Backup Date', 'text', 'general', 1, '2025-08-18 00:37:04', '2025-08-18 00:46:28');

-- --------------------------------------------------------

--
-- Table structure for table `stock_items`
--

CREATE TABLE `stock_items` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `purchase_item_id` int(11) NOT NULL DEFAULT 0,
  `product_code` varchar(255) NOT NULL,
  `color` varchar(7) DEFAULT '#000000',
  `quantity` int(11) NOT NULL,
  `purchase_price` decimal(15,2) NOT NULL,
  `sale_price` decimal(15,2) NOT NULL,
  `stock_date` date NOT NULL,
  `status` enum('available','reserved','sold') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_items`
--

INSERT INTO `stock_items` (`id`, `product_id`, `purchase_item_id`, `product_code`, `color`, `quantity`, `purchase_price`, `sale_price`, `stock_date`, `status`) VALUES
(13, 21, 12, '232', '#000000', 0, 23.00, 23.00, '2025-08-14', 'available'),
(15, 21, 14, '', '#000000', 0, 12.00, 122.00, '2025-08-15', 'sold'),
(17, 21, 0, '232-20250818-010', '#000000', 12, 33.00, 233.00, '2025-08-17', 'available'),
(18, 21, 0, '232-20250818-208', '#000000', 23, 32.00, 222.00, '2025-08-17', 'available'),
(19, 21, 16, '232', 'Blue', 23, 23.00, 23.00, '2025-08-18', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `supplier_contact` text NOT NULL,
  `supplier_open_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `supplier_address` varchar(255) NOT NULL,
  `supplier_email` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `opening_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id`, `supplier_name`, `supplier_contact`, `supplier_open_balance`, `supplier_address`, `supplier_email`, `status`, `created_at`, `opening_balance`) VALUES
(1, 'Tech Solutions Ltd', '0300-6666666', 0.00, 'Office #15, 5th Floor, Blue Area, Islamabad', 'info@techsolutions.pk', 1, '2025-08-18 15:00:00', 0.00),
(2, 'Electronics Wholesale', '0300-7777777', 0.00, 'Warehouse #3, Industrial Area, Islamabad', 'sales@ewholesale.pk', 1, '2025-08-18 15:00:00', 0.00),
(3, 'Digital Imports', '0300-8888888', 0.00, 'Shop #45, Electronics Market, Rawalpindi', 'contact@digitalimports.pk', 1, '2025-08-18 15:00:00', 0.00),
(4, 'Smart Gadgets Co', '0300-9999999', 0.00, 'Showroom #12, Centaurus Mall, Islamabad', 'info@smartgadgets.pk', 1, '2025-08-18 15:00:00', 0.00),
(5, 'Home Electronics', '0300-0000000', 0.00, 'Shop #78, Main Market, Islamabad', 'sales@homeelectronics.pk', 1, '2025-08-18 15:00:00', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_ledger`
--

CREATE TABLE `supplier_ledger` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL DEFAULT 0,
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `Ldate` date NOT NULL,
  `details` varchar(255) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_ledger`
--

INSERT INTO `supplier_ledger` (`id`, `supplier_id`, `purchase_id`, `payment_id`, `debit`, `credit`, `Ldate`, `details`, `balance`, `created_at`) VALUES
(1, 2, 14, 0, 144.00, 0.00, '2025-08-15', 'Purchase: Royal Tag', 144.00, '2025-08-15 07:51:04'),
(2, 2, 15, 0, 144.00, 0.00, '2025-08-15', 'Purchase: Royal Tag', 144.00, '2025-08-15 07:52:21');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payment`
--

CREATE TABLE `supplier_payment` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL DEFAULT 0,
  `supplier_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `paid` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `payment_amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_payments`
--

INSERT INTO `supplier_payments` (`id`, `supplier_id`, `payment_amount`, `payment_date`, `payment_method`, `reference_no`, `notes`, `created_at`) VALUES
(2, 2, 12.00, '2025-08-14', 'Cash', '', '', '2025-08-14 12:47:33');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL DEFAULT 'Tailor Shop',
  `company_address` text NOT NULL,
  `company_phone` varchar(50) NOT NULL,
  `company_email` varchar(100) NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_users`
--

CREATE TABLE `system_users` (
  `id` int(11) NOT NULL,
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
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_users`
--

INSERT INTO `system_users` (`id`, `role_id`, `status`, `name`, `username`, `email`, `password`, `contact`, `image`, `address`, `signupdate`, `last_login`) VALUES
(1, 1, 1, 'Administrator', 'admin', 'admin@tailorshop.com', '$2y$10$YiIrWv6lyeh6E9YTqyoZ3.6FBYR/yRzpWNy8zKmC46XUCxIR6ZRPG', '+1234567890', NULL, '123 Main Street, City, Country', '2025-08-11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `short_name` varchar(10) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `unit`, `short_name`, `status`, `created_at`) VALUES
(1, 'Piece', 'pc', 1, '2025-08-11 11:31:38'),
(2, 'Meter', 'm', 1, '2025-08-11 11:31:38'),
(3, 'Yard', 'yd', 1, '2025-08-11 11:31:38'),
(4, 'Centimeter', 'cm', 1, '2025-08-11 11:31:38');

-- --------------------------------------------------------

--
-- Table structure for table `unit_prices`
--

CREATE TABLE `unit_prices` (
  `id` int(11) NOT NULL,
  `unit_name` varchar(100) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit_prices`
--

INSERT INTO `unit_prices` (`id`, `unit_name`, `unit_price`, `created_at`) VALUES
(2, 'denim', 250.00, '2025-08-13 08:05:02'),
(3, 'alkaram', 2500.00, '2025-08-13 11:52:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_cash`
--
ALTER TABLE `account_cash`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `backup`
--
ALTER TABLE `backup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_history`
--
ALTER TABLE `cash_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_in_bank`
--
ALTER TABLE `cash_in_bank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_in_bank_history`
--
ALTER TABLE `cash_in_bank_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_in_hand`
--
ALTER TABLE `cash_in_hand`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cloths_orders`
--
ALTER TABLE `cloths_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_customer_id` (`customer_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_in_out`
--
ALTER TABLE `customer_in_out`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_payment`
--
ALTER TABLE `customer_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses_category`
--
ALTER TABLE `expenses_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_return`
--
ALTER TABLE `purchase_return`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `return_percale`
--
ALTER TABLE `return_percale`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_no` (`return_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `status` (`status`),
  ADD KEY `return_date` (`return_date`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale`
--
ALTER TABLE `sale`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sale_return`
--
ALTER TABLE `sale_return`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_items`
--
ALTER TABLE `stock_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_payment`
--
ALTER TABLE `supplier_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_users`
--
ALTER TABLE `system_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `unit_prices`
--
ALTER TABLE `unit_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unit_name` (`unit_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_cash`
--
ALTER TABLE `account_cash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backup`
--
ALTER TABLE `backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_history`
--
ALTER TABLE `cash_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_in_bank`
--
ALTER TABLE `cash_in_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_in_bank_history`
--
ALTER TABLE `cash_in_bank_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_in_hand`
--
ALTER TABLE `cash_in_hand`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cloths_orders`
--
ALTER TABLE `cloths_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_in_out`
--
ALTER TABLE `customer_in_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_payment`
--
ALTER TABLE `customer_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expenses_category`
--
ALTER TABLE `expenses_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `purchase_return`
--
ALTER TABLE `purchase_return`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_percale`
--
ALTER TABLE `return_percale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sale`
--
ALTER TABLE `sale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sale_return`
--
ALTER TABLE `sale_return`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `stock_items`
--
ALTER TABLE `stock_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_payment`
--
ALTER TABLE `supplier_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_users`
--
ALTER TABLE `system_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `unit_prices`
--
ALTER TABLE `unit_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD CONSTRAINT `fk_supplier_payments_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`) ON DELETE CASCADE;

--
-- CNIC fields are already present in customer and sale tables
-- No additional ALTER statements needed
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
