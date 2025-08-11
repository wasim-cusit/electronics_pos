-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 09:50 AM
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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Cotton Fabric', 'Natural cotton fabrics for everyday wear', '2025-08-07 05:41:36'),
(2, 'Silk Fabric', 'Premium silk fabrics for special occasions', '2025-08-07 05:41:36'),
(3, 'Denim Fabric', 'Durable denim for jeans and jackets', '2025-08-07 05:41:36'),
(4, 'Linen Fabric', 'Lightweight linen for summer clothing', '2025-08-07 05:41:36'),
(5, 'Wool Fabric', 'Warm wool fabrics for winter wear', '2025-08-07 05:41:36'),
(6, 'Synthetic Fabric', 'Man-made fabrics like polyester, nylon', '2025-08-07 05:41:36'),
(7, 'Embroidered Fabric', 'Fabric with decorative embroidery', '2025-08-07 05:41:36'),
(8, 'Printed Fabric', 'Fabric with printed patterns and designs', '2025-08-07 05:41:36'),
(9, 'Plain Fabric', 'Solid color fabrics without patterns', '2025-08-07 05:41:36'),
(10, 'Accessories', 'Zippers, buttons, threads, and other accessories', '2025-08-07 05:41:36'),
(11, 'Cotton Fabric', 'Natural cotton fabrics for everyday wear', '2025-08-07 05:57:00'),
(12, 'Silk Fabric', 'Premium silk fabrics for special occasions', '2025-08-07 05:57:00'),
(13, 'Denim Fabric', 'Durable denim for jeans and jackets', '2025-08-07 05:57:00'),
(14, 'Linen Fabric', 'Lightweight linen for summer clothing', '2025-08-07 05:57:00'),
(15, 'Wool Fabric', 'Warm wool fabrics for winter wear', '2025-08-07 05:57:00'),
(16, 'Synthetic Fabric', 'Man-made fabrics like polyester, nylon', '2025-08-07 05:57:00'),
(17, 'Embroidered Fabric', 'Fabric with decorative embroidery', '2025-08-07 05:57:00'),
(18, 'Printed Fabric', 'Fabric with printed patterns and designs', '2025-08-07 05:57:00'),
(19, 'Plain Fabric', 'Solid color fabrics without patterns', '2025-08-07 05:57:00'),
(20, 'Accessories', 'Zippers, buttons, threads, and other accessories', '2025-08-07 05:57:00');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `contact`, `address`, `email`, `created_at`) VALUES
(1, 'MUHAMMAD WASIM', '', 'Doctor Guest House, Street 6, Phase 4, HMC, Hayatabad', 'z.r@gmail.com', '2025-08-06 13:22:20'),
(2, 'MUHAMMAD WASIM', '03342372772', 'Doctor Guest House, Street 6, Phase 4, HMC, Hayatabad', 'z.r@gmail.com', '2025-08-07 05:31:32'),
(3, 'Ahmed Khan', '+92-310-1111111', 'House #123, Street 5, Model Town, Lahore', 'ahmed.khan@email.com', '2025-08-07 05:57:00'),
(4, 'Fatima Ali', '+92-311-2222222', 'Apartment 45, Block B, Gulberg, Lahore', 'fatima.ali@email.com', '2025-08-07 05:57:00'),
(5, 'Muhammad Hassan', '+92-312-3333333', 'House #67, Street 12, Johar Town, Lahore', 'hassan@email.com', '2025-08-07 05:57:00'),
(6, 'Ayesha Malik', '+92-313-4444444', 'Shop #8, Main Bazaar, Multan', 'ayesha.malik@email.com', '2025-08-07 05:57:00'),
(7, 'Usman Ahmed', '+92-314-5555555', 'House #90, Street 3, Faisalabad', 'usman.ahmed@email.com', '2025-08-07 05:57:00'),
(8, 'Sara Khan', '+92-315-6666666', 'Apartment 23, Block C, Karachi', 'sara.khan@email.com', '2025-08-07 05:57:00'),
(9, 'Bilal Hassan', '+92-316-7777777', 'House #45, Street 8, Islamabad', 'bilal.hassan@email.com', '2025-08-07 05:57:00'),
(10, 'Nadia Ali', '+92-317-8888888', 'Shop #12, Market Street, Peshawar', 'nadia.ali@email.com', '2025-08-07 05:57:00');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 'Low Stock', 'Low stock alert: Natural Linen Beige stock is -213.00 (threshold: 0.00)', 1, '2025-08-07 10:27:14'),
(2, 3, 'Low Stock', 'Low stock alert: Light Denim Grey stock is -424.50 (threshold: 0.00)', 1, '2025-08-07 10:28:09'),
(3, 3, 'Low Stock', 'Low stock alert: Light Denim Grey stock is -412.50 (threshold: 0.00)', 1, '2025-08-07 11:43:20'),
(4, 3, 'Low Stock', 'Low stock alert: Polyester Black stock is -5.00 (threshold: 0.00)', 1, '2025-08-07 12:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit` enum('meter','piece','set') NOT NULL,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` decimal(10,2) DEFAULT 0.00,
  `low_stock_threshold` decimal(10,2) DEFAULT 0.00,
  `barcode` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `unit`, `size`, `color`, `brand`, `cost_price`, `sale_price`, `stock_quantity`, `low_stock_threshold`, `barcode`, `created_at`) VALUES
(1, 'Premium Cotton White', 1, 'meter', 'Standard', 'White', 'CottonCo', 150.00, 200.00, 59.50, 0.00, 'CTN001', '2025-08-07 05:57:00'),
(2, 'Cotton Blue Striped', 1, 'meter', 'Standard', 'Blue', 'CottonCo', 180.00, 240.00, 35.00, 0.00, 'CTN002', '2025-08-07 05:57:00'),
(3, 'Cotton Pink Plain', 1, 'meter', 'Standard', 'Pink', 'CottonCo', 160.00, 220.00, 42.50, 0.00, 'CTN003', '2025-08-07 05:57:00'),
(4, 'Cotton Green Checkered', 1, 'meter', 'Standard', 'Green', 'CottonCo', 170.00, 230.00, 28.00, 0.00, 'CTN004', '2025-08-07 05:57:00'),
(5, 'Pure Silk Red', 2, 'meter', 'Standard', 'Red', 'SilkPro', 800.00, 1200.00, 15.50, 0.00, 'SLK001', '2025-08-07 05:57:00'),
(6, 'Silk Blue Embroidered', 2, 'meter', 'Standard', 'Blue', 'SilkPro', 900.00, 1400.00, 12.00, 0.00, 'SLK002', '2025-08-07 05:57:00'),
(7, 'Silk Gold Plain', 2, 'meter', 'Standard', 'Gold', 'SilkPro', 750.00, 1100.00, 18.50, 0.00, 'SLK003', '2025-08-07 05:57:00'),
(8, 'Heavy Denim Blue', 3, 'meter', 'Standard', 'Blue', 'DenimMax', 300.00, 450.00, 25.00, 0.00, 'DNM001', '2025-08-07 05:57:00'),
(9, 'Light Denim Grey', 3, 'meter', 'Standard', 'Grey', 'DenimMax', 250.00, 380.00, -412.50, 0.00, 'DNM002', '2025-08-07 05:57:00'),
(10, 'Natural Linen Beige', 4, 'meter', 'Standard', 'Beige', 'LinenPure', 400.00, 600.00, -213.00, 0.00, 'LNN001', '2025-08-07 05:57:00'),
(11, 'Linen White', 4, 'meter', 'Standard', 'White', 'LinenPure', 380.00, 570.00, 22.50, 0.00, 'LNN002', '2025-08-07 05:57:00'),
(12, 'Wool Black', 5, 'meter', 'Standard', 'Black', 'WoolWarm', 500.00, 750.00, 15.00, 0.00, 'WOL001', '2025-08-07 05:57:00'),
(13, 'Wool Brown', 5, 'meter', 'Standard', 'Brown', 'WoolWarm', 480.00, 720.00, 18.50, 19.00, NULL, '2025-08-07 05:57:00'),
(14, 'Polyester Black', 6, 'meter', 'Standard', 'Black', 'SynthFab', 120.00, 180.00, -5.00, 0.00, 'SYN001', '2025-08-07 05:57:00'),
(15, 'Nylon White', 6, 'meter', 'Standard', 'White', 'SynthFab', 100.00, 150.00, 35.50, 0.00, 'SYN002', '2025-08-07 05:57:00'),
(16, 'Embroidered Red Silk', 7, 'meter', 'Standard', 'Red', 'EmbroideryPro', 1200.00, 1800.00, 8.50, 0.00, 'EMB001', '2025-08-07 05:57:00'),
(17, 'Embroidered Green Cotton', 7, 'meter', 'Standard', 'Green', 'EmbroideryPro', 400.00, 600.00, 12.00, 0.00, 'EMB002', '2025-08-07 05:57:00'),
(18, 'Printed Floral Cotton', 8, 'meter', 'Standard', 'Multi', 'PrintFab', 200.00, 300.00, 25.50, 0.00, 'PRT001', '2025-08-07 05:57:00'),
(19, 'Printed Geometric Silk', 8, 'meter', 'Standard', 'Multi', 'PrintFab', 600.00, 900.00, 10.00, 0.00, 'PRT002', '2025-08-07 05:57:00'),
(20, 'Plain White Cotton', 9, 'meter', 'Standard', 'White', 'PlainFab', 140.00, 200.00, 45.00, 0.00, 'PLN001', '2025-08-07 05:57:00'),
(21, 'Plain Black Cotton', 9, 'meter', 'Standard', 'Black', 'PlainFab', 150.00, 220.00, 37.50, 0.00, 'PLN002', '2025-08-07 05:57:00'),
(22, 'Zipper 12 inch', 10, 'piece', '12 inch', 'Silver', 'ZipCo', 25.00, 40.00, 100.00, 0.00, 'ACC001', '2025-08-07 05:57:00'),
(23, 'Buttons Pack (10 pcs)', 10, 'piece', 'Standard', 'White', 'ButtonCo', 15.00, 25.00, 38.00, 0.00, 'ACC002', '2025-08-07 05:57:00'),
(24, 'Thread Spool', 10, 'piece', 'Standard', 'Black', 'ThreadCo', 30.00, 45.00, 70.00, 75.00, NULL, '2025-08-07 05:57:00'),
(25, 'Thread Spool	', 20, 'meter', 'standard ', 'red', 'ThreadCo', 1050.00, 1200.00, 4.00, 0.00, NULL, '2025-08-07 06:35:59');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `supplier_id`, `invoice_no`, `purchase_date`, `total_amount`, `created_by`, `created_at`) VALUES
(11, 7, 'INV-001', '2025-08-07', 144.00, 3, '2025-08-07 07:42:01'),
(12, 7, 'INV-012', '2025-08-07', 144.00, 3, '2025-08-07 11:43:20'),
(13, 7, 'INV-013', '2025-08-08', 144.00, 3, '2025-08-08 07:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(6, 11, 1, 12.00, 12.00, 144.00),
(7, 12, 9, 12.00, 12.00, 144.00),
(8, 13, 15, 12.00, 12.00, 144.00);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Manager'),
(3, 'Cashier'),
(4, 'Admin'),
(5, 'Manager'),
(6, 'Cashier'),
(7, 'Admin'),
(8, 'Manager'),
(9, 'Cashier');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `invoice_no`, `sale_date`, `delivery_date`, `total_amount`, `created_by`, `created_at`) VALUES
(2, 1, 'SALE-001', '2025-08-07', '0000-00-00', 300.00, 3, '2025-08-07 07:57:35'),
(3, 5, 'SALE-003', '2025-08-07', '2025-08-09', 820.00, 3, '2025-08-07 09:23:48'),
(4, 6, 'SALE-004', '2025-08-07', '0000-00-00', 14400.00, 3, '2025-08-07 10:10:41'),
(5, 9, 'SALE-005', '2025-08-07', '0000-00-00', 139800.00, 3, '2025-08-07 10:27:14'),
(6, 4, 'SALE-006', '2025-08-07', '0000-00-00', 172900.00, 3, '2025-08-07 10:28:09'),
(7, 3, 'SALE-007', '2025-08-07', '0000-00-00', 1800.00, 3, '2025-08-07 11:43:50'),
(8, 3, 'SALE-008', '2025-08-07', '0000-00-00', 8100.00, 3, '2025-08-07 12:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(2, 2, 23, 12.00, 25.00, 300.00),
(3, 3, 21, 1.00, 220.00, 220.00),
(4, 3, 1, 3.00, 200.00, 600.00),
(5, 4, 25, 12.00, 1200.00, 14400.00),
(6, 5, 10, 233.00, 600.00, 139800.00),
(7, 6, 9, 455.00, 380.00, 172900.00),
(8, 7, 15, 12.00, 150.00, 1800.00),
(9, 8, 14, 45.00, 180.00, 8100.00);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_description`, `created_at`, `updated_at`) VALUES
(1, 'company_name', 'TAILOR SHOP', 'Company/Business Name', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(2, 'company_tagline', 'Professional Tailoring Services', 'Company Tagline or Description', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(3, 'company_phone', '+92-300-1234567', 'Company Phone Number', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(4, 'company_email', 'info@tailorshop.com', 'Company Email Address', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(5, 'company_address', 'Shop #123, Main Street, Lahore, Pakistan', 'Company Address', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(6, 'company_website', 'www.tailorshop.com', 'Company Website', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(7, 'company_logo', '', 'Company Logo URL (optional)', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(8, 'currency_symbol', 'PKR', 'Currency Symbol', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(9, 'currency_name', 'Pakistani Rupee', 'Currency Name', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(10, 'invoice_prefix', 'INV', 'Invoice Number Prefix', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(11, 'purchase_prefix', 'PUR', 'Purchase Invoice Prefix', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(12, 'sale_prefix', 'SALE', 'Sale Invoice Prefix', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(13, 'tax_rate', '0', 'Default Tax Rate (%)', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(14, 'footer_text', 'Thank you for your business!', 'Footer Text for Invoices', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(15, 'print_header', 'Computer Generated Invoice', 'Print Header Text', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(16, 'low_stock_threshold', '10', 'Low Stock Alert Threshold', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(17, 'date_format', 'd/m/Y', 'Date Format', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(18, 'time_format', 'h:i A', 'Time Format', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(19, 'business_hours', '9:00 AM - 6:00 PM', 'Business Hours', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(20, 'business_days', 'Monday - Saturday', 'Business Days', '2025-08-07 06:10:48', '2025-08-07 06:13:22'),
(21, 'created_at', '2025-08-07 11:10:48', 'Settings Created Date', '2025-08-07 06:10:48', '2025-08-07 06:10:48'),
(22, 'updated_at', '2025-08-07 11:10:48', 'Settings Last Updated Date', '2025-08-07 06:10:48', '2025-08-07 06:10:48');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `movement_type` enum('adjustment','return','purchase','sale') DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `movement_type`, `quantity`, `note`, `created_by`, `created_at`) VALUES
(1, 6, 'purchase', 2.00, 'Purchase from supplier', 4, '2025-08-07 06:05:39'),
(2, 10, 'purchase', 112.00, 'Purchase from supplier', 3, '2025-08-07 07:40:23'),
(3, 23, 'purchase', 12.00, 'Purchase from supplier', 3, '2025-08-07 07:40:34'),
(4, 3, 'purchase', 12.00, 'Purchase from supplier', 3, '2025-08-07 07:41:37'),
(5, 17, 'purchase', 12.00, 'Purchase from supplier', 3, '2025-08-07 07:41:37'),
(6, 1, 'purchase', 12.00, 'Purchase from supplier', 3, '2025-08-07 07:42:01'),
(7, 20, 'sale', 12.00, 'Sale to customer', 3, '2025-08-07 07:56:29'),
(8, 23, 'sale', 12.00, 'Sale to customer', 3, '2025-08-07 07:57:35'),
(9, 21, 'sale', 1.00, 'Sale to customer', 3, '2025-08-07 09:23:48'),
(10, 1, 'sale', 3.00, 'Sale to customer', 3, '2025-08-07 09:23:48'),
(11, 25, 'sale', 12.00, 'Sale to customer', 3, '2025-08-07 10:10:41'),
(12, 10, 'sale', 233.00, 'Sale to customer', 3, '2025-08-07 10:27:14'),
(13, 9, 'sale', 455.00, 'Sale to customer', 3, '2025-08-07 10:28:09'),
(14, 9, 'purchase', 12.00, 'Purchase from supplier', 3, '2025-08-07 11:43:20'),
(15, 15, 'sale', 12.00, 'Sale to customer', 3, '2025-08-07 11:43:50'),
(16, 14, 'sale', 45.00, 'Sale to customer', 3, '2025-08-07 12:32:11'),
(17, 15, 'purchase', 12.00, 'Purchase from supplier', 3, '2025-08-08 07:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact`, `address`, `email`, `created_at`) VALUES
(1, 'MUHAMMAD WASIM', '', 'Doctor Guest House, Street 6, Phase 4, HMC, Hayatabad', 'z.r@gmail.com', '2025-08-06 13:16:03'),
(2, 'ABC Fabrics Ltd', '+92-300-1234567', 'Shop #15, Main Market, Lahore', 'info@abcfabrics.com', '2025-08-07 05:57:00'),
(3, 'Premium Textiles', '+92-301-2345678', 'Floor 2, Plaza Building, Karachi', 'sales@premiumtextiles.com', '2025-08-07 05:57:00'),
(4, 'Quality Cloth House', '+92-302-3456789', 'Street 5, Industrial Area, Faisalabad', 'contact@qualitycloth.com', '2025-08-07 05:57:00'),
(5, 'Royal Silk Traders', '+92-303-4567890', 'Shop #8, Silk Market, Multan', 'royal@silktraders.com', '2025-08-07 05:57:00'),
(6, 'Denim World', '+92-304-5678901', 'Unit 12, Textile Complex, Sialkot', 'denim@world.com', '2025-08-07 05:57:00'),
(7, 'Cotton Paradise', '+92-305-6789012', 'Shop #25, Cotton Market, Rahim Yar Khan', 'cotton@paradise.com', '2025-08-07 05:57:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role_id`, `created_at`) VALUES
(3, 'admin', '$2y$10$m0w2f54uNZhRCuzf5D3MaO4xDEhFTeQ0xQTkuaS6qAlzDunbs.0QS', NULL, NULL, 1, '2025-08-06 11:13:19'),
(4, 'wasim', '$2y$10$mw5kjfuvCGpSX4AiQw.0p.UebDxiMKQ5t6Otz2HgVftTay29WJSE2', NULL, NULL, 1, '2025-08-07 05:17:56'),
(10, 'cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier One', 'cashier1@tailorshop.com', 3, '2025-08-07 05:57:00'),
(11, 'cashier2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier Two', 'cashier2@tailorshop.com', 3, '2025-08-07 05:57:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
