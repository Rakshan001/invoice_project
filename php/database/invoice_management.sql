-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2025 at 08:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `invoice_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `email`, `created_at`) VALUES
(2, 'admin', '$2y$10$tKV/Izk9zZfXuBCSJDoVP.yQG6flbaNbxrDunru.OBucEvzvlj/hW', 'admin@example.com', '2025-04-05 08:22:21');

-- --------------------------------------------------------

--
-- Table structure for table `client_master`
--

CREATE TABLE `client_master` (
  `client_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `gst` varchar(50) DEFAULT NULL,
  `state` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_master`
--

INSERT INTO `client_master` (`client_id`, `company_id`, `name`, `address`, `gst`, `state`, `email`, `created_at`, `updated_at`) VALUES
(1, 2, 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 'ksdc1234@gmail.com', '2025-04-05 17:28:56', '2025-04-05 17:28:56'),
(2, 2, 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 'ksahc1234@gmail.com', '2025-04-05 17:29:24', '2025-04-05 17:29:24'),
(3, 2, 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 'rakshanshetty2003@gmail.com', '2025-04-08 05:05:44', '2025-04-10 06:24:15'),
(4, 2, 'kmc', 'jyoti mangaluru 575001', 'SDSDS745S41D51D2C2C', 'Karnataka', 'kmc@gmail.com', '2025-04-09 06:27:33', '2025-04-09 06:27:33'),
(5, 2, 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 'srisha2373@gmail.com', '2025-04-09 09:03:56', '2025-04-09 09:03:56'),
(6, 1, 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 'rakshanshetty2003@gmail.com', '2025-04-14 06:24:26', '2025-04-14 06:24:26'),
(7, 1, 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 'srisha2373@gmail.com', '2025-04-14 06:46:12', '2025-04-14 06:46:12'),
(8, 2, 'anoop sadanaandaa', 'Kulai manglore\r\nHonnakatte', '3DFD51VVD51V5DDV', 'Karnataka', 'anoopsprabhu12@gmail.com', '2025-04-14 09:41:22', '2025-04-14 09:41:22'),
(9, 1, 'vinayak d', 'Kulai manglore\r\nHonnakatte', '', '', 'vinayak123@gmail.com', '2025-04-19 09:52:59', '2025-04-19 09:52:59'),
(10, 1, 'anoop s prabhu', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 'anoopsprabhu12@gmail.com', '2025-04-19 10:00:02', '2025-04-19 10:00:02'),
(11, 1, 'Harish', '', '', 'Karnataka', 'harish@gmail.com', '2025-04-22 10:08:52', '2025-04-22 10:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `company_bank`
--

CREATE TABLE `company_bank` (
  `company_bank_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc` varchar(20) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_bank`
--

INSERT INTO `company_bank` (`company_bank_id`, `company_id`, `account_name`, `account_number`, `ifsc`, `bank_name`, `branch_name`, `created_at`, `updated_at`) VALUES
(1, 2, 'Accolade tech solutions pvt ltd', '8512562145245251455', 'DFERS2453d', 'canara bank', 'kadri managluru', '2025-04-05 17:28:14', '2025-04-05 17:28:14'),
(2, 1, 'Accolade tech solutions pvt ltd', '8512562145245251455', 'DFERS2453d', 'canara bank', 'kadri managluru', '2025-04-07 04:58:17', '2025-04-07 04:58:17');

-- --------------------------------------------------------

--
-- Table structure for table `company_master`
--

CREATE TABLE `company_master` (
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cin` varchar(50) DEFAULT NULL,
  `gstin` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `state` varchar(100) NOT NULL,
  `seal` varchar(255) DEFAULT NULL,
  `sign` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_master`
--

INSERT INTO `company_master` (`company_id`, `user_id`, `name`, `address`, `phone`, `email`, `cin`, `gstin`, `logo`, `state`, `seal`, `sign`, `created_at`, `updated_at`, `email_password`) VALUES
(1, 1, 'Accolade Tech Solutions Private Limited', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', '09341679055', 'shrishamayyav23@gmail.com', 'D8S4D8D4D55D2D5D25DD5', '8D5D5D5D4D5D1D4D1', 'uploads/logo_1.jpg', 'Karnataka', 'uploads/seal_1.jpg', 'uploads/sign_1.png', '2025-04-05 08:08:02', '2025-04-14 06:26:15', 'naon tpii qitg rban'),
(2, 2, 'canara engineering college', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', '09341679055', 'shrishamayyav23@gmail.com', 'D8S4D8D4D55D2D5D25DD5', 'DD5C4C4C14X4S54DF4FF1SXS', 'uploads/logo_2.jpg', 'Karnataka', 'uploads/seal_2.jpg', 'uploads/sign_2.png', '2025-04-05 17:27:50', '2025-04-11 10:43:33', 'naontpiiqitgrban');

-- --------------------------------------------------------

--
-- Table structure for table `company_themes`
--

CREATE TABLE `company_themes` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `theme_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_master`
--

CREATE TABLE `email_master` (
  `email_master_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_master`
--

INSERT INTO `email_master` (`email_master_id`, `company_id`, `description`) VALUES
(1, 2, 'Dear {client_name},\r\n\r\nPlease find attached the invoice #{invoice_number} dated {invoice_date} for amount Rs. {amount}.\r\n\r\nThank you for your business.\r\n\r\nBest regards,\r\n{company_name}');

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `template_type` varchar(50) NOT NULL,
  `template_content` mediumtext NOT NULL,
  `design` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `company_id`, `template_type`, `template_content`, `design`, `created_at`, `updated_at`) VALUES
(1, 1, 'invoice', '{\"subject\":\"Invoice #{{INVOICE_NUMBER}} from {{COMPANY_NAME}}\",\"greeting\":\"Dear {{CLIENT_NAME}},\",\"main_content\":\"Please find attached the invoice #{{INVOICE_NUMBER}} dated {{INVOICE_DATE}} for amount {{INVOICE_AMOUNT}}.\\r\\n\\r\\nIf you have any questions, please don\'t hesitate to contact us.\",\"signature\":\"Best Regards,\\r\\n{{COMPANY_NAME}}\\r\\nPhone: {{COMPANY_PHONE}}\\r\\nEmail: {{COMPANY_EMAIL}}\"}', '<!DOCTYPE html>\n<html>\n<head>\n    <style>\n        body { font-family: Arial, sans-serif; }\n        .header { background: #6366f1; color: white; padding: 20px; text-align: center; }\n        .content { padding: 20px; background: #f8fafc; }\n        .invoice-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #6366f1; }\n        .footer { background: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; }\n        .company-info { color: #4f46e5; margin-top: 20px; }\n    </style>\n</head>\n<body>\n    <div class=\"header\">\n        <h2>Invoice #{{INVOICE_NUMBER}}</h2>\n    </div>\n    <div class=\"content\">\n        {{GREETING}}\n        <p>{{MAIN_CONTENT}}</p>\n        <div class=\"invoice-details\">\n            <p><strong>Invoice Number:</strong> {{INVOICE_NUMBER}}</p>\n            <p><strong>Date:</strong> {{INVOICE_DATE}}</p>\n            <p><strong>Amount:</strong> Rs. {{INVOICE_AMOUNT}}</p>\n        </div>\n        <div class=\"company-info\">\n            <p>For any queries regarding this invoice, please contact us:</p>\n            <p><strong>{{COMPANY_NAME}}</strong></p>\n            <p>Phone: {{COMPANY_PHONE}}</p>\n            <p>Email: {{COMPANY_EMAIL}}</p>\n        </div>\n    </div>\n    <div class=\"footer\">\n        &copy; {{CURRENT_YEAR}} {{COMPANY_NAME}}. All rights reserved.\n    </div>\n</body>\n</html>', '2025-04-15 15:30:59', '2025-04-17 12:42:15');

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `invoice_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `bank_id` int(11) NOT NULL,
  `invoice_number` varchar(20) NOT NULL,
  `invoice_date` date NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `client_address` text NOT NULL,
  `client_gstin` varchar(20) DEFAULT NULL,
  `client_state` varchar(50) NOT NULL,
  `total_amount` decimal(20,2) DEFAULT NULL,
  `taxable_value` decimal(20,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 18.00,
  `cgst` decimal(20,2) DEFAULT NULL,
  `sgst` decimal(20,2) DEFAULT NULL,
  `total_tax_amount` decimal(20,2) DEFAULT NULL,
  `net_total` decimal(20,2) DEFAULT NULL,
  `rupees_in_words` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pdf_content` mediumblob DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`invoice_id`, `company_id`, `client_id`, `bank_id`, `invoice_number`, `invoice_date`, `client_name`, `client_address`, `client_gstin`, `client_state`, `total_amount`, `taxable_value`, `tax_rate`, `cgst`, `sgst`, `total_tax_amount`, `net_total`, `rupees_in_words`, `created_at`, `updated_at`, `pdf_content`, `status`) VALUES
(3, 2, 1, 1, '500234', '2025-04-07', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 500.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'Five Hundred and Ninety Rupees Only', '2025-04-07 06:05:13', '2025-04-07 06:05:13', NULL, 'pending'),
(4, 2, 2, 1, '500235', '2025-04-07', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 1000.00, 1000.00, 18.00, 90.00, 90.00, 180.00, 1180.00, 'One Hundred and Eighteen Rupees Only', '2025-04-07 06:11:14', '2025-04-07 06:11:14', NULL, 'pending'),
(5, 2, 1, 1, '500236', '2025-04-07', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 1500.00, 1500.00, 18.00, 135.00, 135.00, 270.00, 1770.00, 'One Hundred and Seventy Seven Rupees Only', '2025-04-07 06:15:52', '2025-04-07 06:15:52', NULL, 'pending'),
(9, 2, 2, 1, '500237', '2025-04-07', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 950.00, 950.00, 18.00, 85.50, 85.50, 171.00, 1121.00, 'Six Hundred and Forty Three Rupees and Ten Paise Only', '2025-04-07 06:25:45', '2025-04-07 06:25:45', NULL, 'pending'),
(10, 2, 2, 1, '500238', '2025-04-07', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 8000.00, 8000.00, 18.00, 720.00, 720.00, 1440.00, 9440.00, 'One Hundred and Eighteen Rupees Only', '2025-04-07 06:29:03', '2025-04-07 06:29:03', NULL, 'pending'),
(11, 2, 1, 1, '500239', '2025-04-07', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 1010.00, 1010.00, 18.00, 90.90, 90.90, 181.80, 1191.80, 'Seven Hundred and Thirteen Rupees and Ninety Paise Only', '2025-04-07 06:33:17', '2025-04-07 06:33:17', NULL, 'pending'),
(12, 2, 3, 1, '500240', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 500.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'Five Hundred and Ninety Rupees Only', '2025-04-08 05:16:45', '2025-04-08 05:16:45', NULL, 'pending'),
(13, 2, 3, 1, '500241', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 900.00, 162.00, 18.00, 81.00, 81.00, 162.00, 1062.00, 'Six Hundred and Thirty Seven Rupees and Twenty Paise Only', '2025-04-08 05:44:26', '2025-04-08 05:44:26', NULL, 'pending'),
(16, 2, 1, 1, '500242', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 26675.00, 4801.50, 18.00, 2400.75, 2400.75, 4801.50, 31476.50, 'One Hundred and Five Rupees and Two Paise Only', '2025-04-08 05:57:39', '2025-04-08 05:57:39', NULL, 'pending'),
(17, 2, 1, 1, '500243', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'Five Hundred and Ninety Rupees Only', '2025-04-08 05:58:23', '2025-04-08 05:58:23', NULL, 'pending'),
(18, 2, 1, 1, '500244', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'Five Hundred and Ninety Rupees Only', '2025-04-08 06:18:19', '2025-04-08 06:18:19', NULL, 'pending'),
(19, 2, 3, 1, '500245', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 800.00, 144.00, 18.00, 72.00, 72.00, 144.00, 944.00, 'Nine Hundred and Forty Four Rupees Only', '2025-04-08 06:19:39', '2025-04-08 06:19:39', NULL, 'pending'),
(20, 2, 3, 1, '500246', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 800.00, 144.00, 18.00, 72.00, 72.00, 144.00, 944.00, 'Nine Hundred and Forty Four Rupees Only', '2025-04-08 06:23:37', '2025-04-08 06:23:37', NULL, 'pending'),
(21, 2, 2, 1, '500247', '2025-04-08', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 4521.00, 813.78, 18.00, 406.89, 406.89, 813.78, 5334.78, 'Five Hundred and Thirty Three Rupees and Thirty Six Paise Only', '2025-04-08 06:29:08', '2025-04-08 06:29:08', NULL, 'pending'),
(22, 2, 1, 1, '500248', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 99999999.99, 99999999.99, 18.00, 99999999.99, 99999999.99, 99999999.99, 99999999.99, 'Five Hundred and Thirty Eight Rupees and Eight Paise Only', '2025-04-08 06:30:56', '2025-04-08 06:30:56', NULL, 'pending'),
(24, 2, 1, 1, '500249', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 99999999.99, 99999999.99, 18.00, 99999999.99, 99999999.99, 99999999.99, 99999999.99, 'One Hundred Rupees and Thirty Paise Only', '2025-04-08 06:43:46', '2025-04-08 06:43:46', NULL, 'pending'),
(25, 2, 3, 1, '500250', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 99999999.99, 99999999.99, 18.00, 99999999.99, 99999999.99, 99999999.99, 99999999.99, 'One Hundred and Five Rupees and Two Paise Only', '2025-04-08 06:58:23', '2025-04-08 06:58:23', NULL, 'pending'),
(26, 2, 2, 1, '500251', '2025-04-08', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 99999999.99, 99999999.99, 18.00, 99999999.99, 99999999.99, 99999999.99, 99999999.99, 'One Hundred Rupees and Thirty Paise Only', '2025-04-08 07:01:14', '2025-04-08 07:01:14', NULL, 'pending'),
(27, 2, 1, 1, '500252', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 99999999.99, 99999999.99, 18.00, 99999999.99, 99999999.99, 99999999.99, 99999999.99, 'One Hundred Rupees and Thirty Paise Only', '2025-04-08 07:24:28', '2025-04-08 07:24:28', NULL, 'pending'),
(28, 2, 3, 1, '500253', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 99999999.99, 99999999.99, 18.00, 99999999.99, 99999999.99, 99999999.99, 99999999.99, 'One Hundred Rupees and Thirty Paise Only', '2025-04-08 10:25:19', '2025-04-08 10:25:19', NULL, 'pending'),
(29, 2, 1, 1, '500254', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 5200.00, 936.00, 18.00, 468.00, 468.00, 936.00, 6136.00, 'Six Hundred and Thirteen Rupees and Sixty Paise Only', '2025-04-08 10:53:54', '2025-04-08 10:53:54', NULL, 'pending'),
(30, 2, 3, 1, '500255', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 4522.00, 813.96, 18.00, 406.98, 406.98, 813.96, 5335.96, 'Five Hundred and Thirty Three Rupees and Thirty Six Paise Only', '2025-04-08 10:58:51', '2025-04-08 10:58:51', NULL, 'pending'),
(31, 2, 1, 1, '500256', '2025-04-08', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 5241.00, 943.38, 18.00, 471.69, 471.69, 943.38, 6184.38, 'Six Hundred and Eighteen Rupees and Thirty Two Paise Only', '2025-04-08 11:00:53', '2025-04-08 11:00:53', NULL, 'pending'),
(32, 2, 3, 1, '500257', '2025-04-08', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 78541.00, 14137.38, 18.00, 7068.69, 7068.69, 14137.38, 92678.38, 'Nine Hundred and Twenty Six Rupees and Thirty Paise Only', '2025-04-08 11:07:45', '2025-04-08 11:07:45', NULL, 'pending'),
(33, 2, 3, 1, '500258', '2025-04-09', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 60712.00, 10928.16, 18.00, 5464.08, 5464.08, 10928.16, 71640.16, 'Six Hundred and Sixty Three Rupees and Sixteen Paise Only', '2025-04-09 05:03:25', '2025-04-09 05:03:25', NULL, 'pending'),
(34, 2, 3, 1, '500259', '2025-04-09', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 456.00, 82.08, 18.00, 41.04, 41.04, 82.08, 538.08, 'Five Hundred and Thirty Eight Rupees and Eight Paise Only', '2025-04-09 06:12:56', '2025-04-09 06:12:56', NULL, 'pending'),
(35, 2, 1, 1, '500260', '2025-04-09', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 85.00, 15.30, 18.00, 7.65, 7.65, 15.30, 100.30, 'One Hundred Rupees and Thirty Paise Only', '2025-04-09 06:18:20', '2025-04-09 06:18:20', NULL, 'pending'),
(36, 2, 2, 1, '500261', '2025-04-09', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 458.00, 82.44, 18.00, 41.22, 41.22, 82.44, 540.44, 'Five Hundred and Forty Rupees and Forty Four Paise Only', '2025-04-09 06:20:19', '2025-04-09 06:20:19', NULL, 'pending'),
(37, 2, 2, 1, '500262', '2025-04-09', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 85.00, 15.30, 18.00, 7.65, 7.65, 15.30, 100.30, 'One Hundred Rupees and Thirty Paise Only', '2025-04-09 06:21:26', '2025-04-09 06:21:26', NULL, 'pending'),
(38, 2, 1, 1, '500263', '2025-04-09', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 85.00, 15.30, 18.00, 7.65, 7.65, 15.30, 100.30, 'One Hundred Rupees and Thirty Paise Only', '2025-04-09 06:25:55', '2025-04-09 06:25:55', NULL, 'pending'),
(39, 2, 4, 1, '500264', '2025-04-09', 'kmc', 'jyoti mangaluru 575001', 'SDSDS745S41D51D2C2C', 'Karnataka', 785.00, 141.30, 18.00, 70.65, 70.65, 141.30, 926.30, 'Nine Hundred and Twenty Six Rupees and Thirty Paise Only', '2025-04-09 06:28:35', '2025-04-09 06:28:35', NULL, 'pending'),
(40, 2, 1, 1, '500265', '2025-04-09', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 85.00, 15.30, 18.00, 7.65, 7.65, 15.30, 100.30, 'One Hundred Rupees and Thirty Paise Only', '2025-04-09 06:30:48', '2025-04-09 06:30:48', NULL, 'pending'),
(41, 2, 1, 1, '500266', '2025-04-09', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 17136.00, 3084.48, 18.00, 1542.24, 1542.24, 3084.48, 20220.48, '', '2025-04-09 06:53:58', '2025-04-09 06:53:58', NULL, 'pending'),
(42, 2, 3, 1, '500267', '2025-04-09', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 866800.00, 156024.00, 18.00, 78012.00, 78012.00, 156024.00, 1022824.00, '', '2025-04-09 06:59:55', '2025-04-09 06:59:55', NULL, 'pending'),
(43, 2, 1, 1, '500268', '2025-04-09', 'ksdc', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 1500.00, 270.00, 18.00, 135.00, 135.00, 270.00, 1770.00, '', '2025-04-09 07:02:31', '2025-04-09 07:02:31', NULL, 'pending'),
(44, 2, 2, 1, '500269', '2025-04-09', 'ksahc', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 1337300.00, 240714.00, 18.00, 120357.00, 120357.00, 240714.00, 1578014.00, '', '2025-04-09 07:04:13', '2025-04-09 07:04:13', NULL, 'pending'),
(45, 2, 3, 1, '500270', '2025-04-09', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 18214294.00, 3278572.92, 18.00, 1639286.46, 1639286.46, 3278572.92, 21492866.92, 'Five Hundred and Ninety Rupees Only', '2025-04-09 07:16:56', '2025-04-09 07:16:56', NULL, 'pending'),
(46, 2, 5, 1, '500271', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 09:04:27', '2025-04-09 09:04:27', NULL, 'pending'),
(47, 2, 5, 1, '500272', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 600.00, 108.00, 18.00, 54.00, 54.00, 108.00, 708.00, 'seven hundred and eight rupees only', '2025-04-09 09:05:29', '2025-04-09 09:05:29', NULL, 'pending'),
(48, 2, 5, 1, '500273', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 526.00, 94.68, 18.00, 47.34, 47.34, 94.68, 620.68, 'six twenty rupees and sixtyeight paise only', '2025-04-09 09:50:48', '2025-04-09 09:50:48', NULL, 'pending'),
(49, 2, 5, 1, '500274', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 09:54:31', '2025-04-09 09:54:31', NULL, 'pending'),
(50, 2, 5, 1, '500275', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5600.00, 1008.00, 18.00, 504.00, 504.00, 1008.00, 6608.00, 'six thousand six hundres and eight rupees only', '2025-04-09 09:59:16', '2025-04-09 09:59:16', NULL, 'pending'),
(51, 2, 5, 1, '500276', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 8000.00, 1440.00, 18.00, 720.00, 720.00, 1440.00, 9440.00, 'nine thousand four fourty rupees only', '2025-04-09 10:01:18', '2025-04-09 10:01:18', NULL, 'pending'),
(52, 2, 5, 1, '500277', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:04:11', '2025-04-09 10:04:11', NULL, 'pending'),
(53, 2, 5, 1, '500278', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:06:48', '2025-04-09 10:06:48', NULL, 'pending'),
(54, 2, 5, 1, '500279', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:08:30', '2025-04-09 10:08:30', NULL, 'pending'),
(55, 2, 5, 1, '500280', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:09:44', '2025-04-09 10:09:44', NULL, 'pending'),
(56, 2, 5, 1, '500281', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:12:12', '2025-04-09 10:12:12', NULL, 'pending'),
(57, 2, 5, 1, '500282', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:14:53', '2025-04-09 10:14:53', NULL, 'pending'),
(58, 2, 5, 1, '500283', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:19:16', '2025-04-09 10:19:16', NULL, 'pending'),
(59, 2, 5, 1, '500284', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:23:10', '2025-04-09 10:23:10', NULL, 'pending'),
(60, 2, 5, 1, '500285', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:26:38', '2025-04-09 10:26:38', NULL, 'pending'),
(61, 2, 5, 1, '500286', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:27:21', '2025-04-09 10:27:21', NULL, 'pending'),
(62, 2, 5, 1, '500287', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:30:31', '2025-04-09 10:30:31', NULL, 'pending'),
(63, 2, 5, 1, '500288', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-09 10:32:27', '2025-04-09 10:32:27', NULL, 'pending'),
(64, 2, 5, 1, '500289', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-09 10:40:53', '2025-04-09 10:40:53', NULL, 'pending'),
(65, 2, 5, 1, '500290', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-09 10:42:35', '2025-04-09 10:42:35', NULL, 'pending'),
(66, 2, 5, 1, '500291', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-09 10:43:45', '2025-04-09 10:43:45', NULL, 'pending'),
(67, 2, 5, 1, '500292', '2025-04-09', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 800.00, 144.00, 18.00, 72.00, 72.00, 144.00, 944.00, '', '2025-04-09 10:46:44', '2025-04-09 10:46:44', NULL, 'pending'),
(68, 2, 5, 1, '500293', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:03:07', '2025-04-10 05:03:07', NULL, 'pending'),
(69, 2, 5, 1, '500294', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:08:22', '2025-04-10 05:08:22', NULL, 'pending'),
(70, 2, 5, 1, '500295', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5000.00, 900.00, 18.00, 450.00, 450.00, 900.00, 5900.00, '', '2025-04-10 05:10:29', '2025-04-10 05:10:29', NULL, 'pending'),
(71, 2, 5, 1, '500296', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:14:22', '2025-04-10 05:14:22', NULL, 'pending'),
(72, 2, 5, 1, '500297', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:23:13', '2025-04-10 05:23:13', NULL, 'pending'),
(73, 2, 5, 1, '500298', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:30:27', '2025-04-10 05:30:27', NULL, 'pending'),
(74, 2, 5, 1, '500299', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:33:56', '2025-04-10 05:33:56', NULL, 'pending'),
(75, 2, 5, 1, '500300', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:36:50', '2025-04-10 05:36:50', NULL, 'pending'),
(76, 2, 5, 1, '500301', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, '', '2025-04-10 05:41:35', '2025-04-10 05:41:35', NULL, 'pending'),
(77, 2, 5, 1, '500302', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:19:56', '2025-04-10 06:19:56', NULL, 'pending'),
(78, 2, 3, 1, '500303', '2025-04-10', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:24:41', '2025-04-10 06:24:41', NULL, 'pending'),
(79, 2, 3, 1, '500304', '2025-04-10', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:27:20', '2025-04-10 06:27:20', NULL, 'pending'),
(80, 2, 3, 1, '500305', '2025-04-10', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:29:13', '2025-04-10 06:29:13', NULL, 'pending'),
(81, 2, 3, 1, '500306', '2025-04-10', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:33:22', '2025-04-10 06:33:22', NULL, 'pending'),
(82, 2, 5, 1, '500307', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:35:11', '2025-04-10 06:35:11', NULL, 'pending'),
(83, 2, 5, 1, '500308', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:36:36', '2025-04-10 06:36:36', NULL, 'pending'),
(84, 2, 5, 1, '500309', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:40:41', '2025-04-10 06:40:41', NULL, 'pending'),
(85, 2, 5, 1, '500310', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:57:30', '2025-04-10 06:57:30', NULL, 'pending'),
(95, 2, 5, 1, '500311', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 06:59:34', '2025-04-10 06:59:34', NULL, 'pending'),
(96, 2, 5, 1, '500312', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 07:01:27', '2025-04-10 07:01:27', NULL, 'pending'),
(97, 2, 5, 1, '500313', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 07:07:09', '2025-04-10 07:07:09', NULL, 'pending'),
(98, 2, 5, 1, '500314', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 07:10:54', '2025-04-10 07:10:54', NULL, 'pending'),
(99, 2, 5, 1, '500315', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 07:14:56', '2025-04-10 07:14:56', NULL, 'pending'),
(100, 2, 5, 1, '500316', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 09:36:58', '2025-04-10 09:36:58', NULL, 'pending'),
(101, 2, 5, 1, '500317', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 09:42:06', '2025-04-10 09:42:06', NULL, 'pending'),
(102, 2, 5, 1, '500318', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 11:36:59', '2025-04-10 11:36:59', NULL, 'pending'),
(103, 2, 5, 1, '500319', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 12:24:29', '2025-04-10 12:24:29', NULL, 'pending'),
(104, 2, 5, 1, '500320', '2025-04-10', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-10 12:26:36', '2025-04-10 12:26:36', NULL, 'pending'),
(105, 2, 5, 1, '500321', '2025-04-11', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 09:01:49', '2025-04-11 09:01:49', NULL, 'pending'),
(106, 2, 5, 1, '500322', '2025-04-11', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 09:03:29', '2025-04-11 09:03:29', NULL, 'pending'),
(107, 2, 3, 1, '500323', '2025-04-11', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 09:25:00', '2025-04-11 09:25:00', NULL, 'pending'),
(108, 2, 5, 1, '500324', '2025-04-11', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 09:28:23', '2025-04-11 09:28:23', NULL, 'pending'),
(109, 2, 5, 1, '500325', '2025-04-11', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 09:32:48', '2025-04-11 09:32:48', NULL, 'pending'),
(110, 2, 5, 1, '500326', '2025-04-11', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 10:25:45', '2025-04-11 10:25:45', NULL, 'pending'),
(111, 2, 3, 1, '500327', '2025-04-11', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 10:35:31', '2025-04-11 10:35:31', NULL, 'pending'),
(112, 2, 5, 1, '500328', '2025-04-11', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 10:41:57', '2025-04-11 10:41:57', NULL, 'pending'),
(113, 2, 5, 1, '500329', '2025-04-11', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-11 10:43:56', '2025-04-11 10:43:56', NULL, 'pending'),
(114, 2, 5, 1, '500330', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 05:09:08', '2025-04-14 05:09:08', NULL, 'pending'),
(115, 2, 5, 1, '500331', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 05:19:21', '2025-04-14 05:19:21', NULL, 'pending'),
(116, 2, 5, 1, '500332', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 05:24:26', '2025-04-14 05:24:26', NULL, 'pending'),
(117, 2, 5, 1, '500333', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 05:38:40', '2025-04-14 05:38:40', NULL, 'pending'),
(118, 2, 5, 1, '500334', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 05:46:18', '2025-04-14 05:46:18', NULL, 'pending'),
(119, 2, 5, 1, '500335', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 05:55:01', '2025-04-14 05:55:01', NULL, 'pending'),
(120, 2, 5, 1, '500336', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 06:09:49', '2025-04-14 06:09:49', NULL, 'pending'),
(121, 2, 3, 1, '500337', '2025-04-14', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 06:21:27', '2025-04-14 06:21:27', NULL, 'pending'),
(122, 1, 6, 2, '632500', '2025-04-14', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 06:25:33', '2025-04-15 05:52:48', NULL, 'cancelled'),
(123, 1, 6, 2, '632501', '2025-04-14', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 06:26:45', '2025-04-15 05:51:47', NULL, 'paid'),
(124, 1, 6, 2, '632502', '2025-04-14', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 06:35:52', '2025-04-14 06:35:52', NULL, 'pending'),
(125, 1, 7, 2, '632503', '2025-04-14', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-14 06:46:23', '2025-04-14 06:46:23', NULL, 'pending'),
(126, 2, 5, 1, '500338', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 60.00, 18.00, 30.00, 30.00, 60.00, 560.00, 'five ninty rupees only', '2025-04-14 08:58:00', '2025-04-14 08:58:00', NULL, 'pending'),
(127, 2, 5, 1, '500339', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5110.00, 613.20, 18.00, 306.60, 306.60, 613.20, 5723.20, 'Five Hundred and Ninety Rupees Only', '2025-04-14 09:13:18', '2025-04-14 09:13:18', NULL, 'pending'),
(128, 2, 5, 1, '500340', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5005.00, 600.60, 18.00, 300.30, 300.30, 600.60, 5605.60, 'Five Hundred and Ninety Rupees Only', '2025-04-14 09:18:01', '2025-04-14 09:18:01', NULL, 'pending'),
(129, 2, 5, 1, '500341', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5000.00, 600.00, 18.00, 300.00, 300.00, 600.00, 5600.00, 'five ninty rupees only', '2025-04-14 09:34:39', '2025-04-14 09:34:39', NULL, 'pending'),
(130, 2, 5, 1, '500342', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 13147.00, 1577.64, 18.00, 788.82, 788.82, 1577.64, 14724.64, 'Five Hundred and Ninety Rupees Only', '2025-04-14 09:35:45', '2025-04-14 09:35:45', NULL, 'pending'),
(131, 2, 5, 1, '500343', '2025-04-14', 'srisha', '11-25/12A near marigudi temple marigudi Surathkal mangalore\r\nNear biogreen nursery', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 13452.00, 1614.24, 18.00, 807.12, 807.12, 1614.24, 15066.24, 'five ninty rupees only', '2025-04-14 09:37:23', '2025-04-14 09:37:23', NULL, 'pending'),
(132, 2, 8, 1, '500344', '2025-04-14', 'anoop sadanaandaa', 'Kulai manglore\r\nHonnakatte', '3DFD51VVD51V5DDV', 'Karnataka', 500.00, 60.00, 18.00, 30.00, 30.00, 60.00, 560.00, 'five ninty rupees only', '2025-04-14 09:41:37', '2025-04-14 09:41:37', NULL, 'pending'),
(133, 2, 3, 1, '500345', '2025-04-14', 'manipal', 'near marigudi temple\r\nmarigudi surathkal', '3DFD51VVD51V5DDV', 'Karnataka', 5621.00, 674.52, 18.00, 337.26, 337.26, 674.52, 6295.52, 'five ninty rupees only', '2025-04-14 09:45:05', '2025-04-14 09:45:05', NULL, 'pending'),
(134, 1, 7, 2, '632504', '2025-04-15', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-15 09:01:31', '2025-04-15 09:01:31', NULL, 'pending'),
(135, 1, 7, 2, '632505', '2025-04-15', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 590.00, 106.20, 18.00, 53.10, 53.10, 106.20, 696.20, 'Five Hundred and Ninety Rupees Only', '2025-04-15 10:03:16', '2025-04-15 10:03:16', NULL, 'pending'),
(136, 1, 7, 2, '632506', '2025-04-15', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5000.00, 900.00, 18.00, 450.00, 450.00, 900.00, 5900.00, 'Five Hundred and Ninety Rupees Only', '2025-04-15 10:05:46', '2025-04-16 06:33:04', NULL, 'pending'),
(137, 1, 7, 2, '632507', '2025-03-11', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 700.00, 126.00, 18.00, 63.00, 63.00, 126.00, 826.00, 'five ninty rupees only', '2025-04-15 11:25:07', '2025-04-16 06:33:12', NULL, 'paid'),
(138, 1, 6, 2, '632508', '2025-04-15', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 600.00, 108.00, 18.00, 54.00, 54.00, 108.00, 708.00, 'five ninty rupees only', '2025-04-15 11:25:44', '2025-04-15 11:25:44', NULL, 'pending'),
(139, 1, 6, 2, '632509', '2025-03-12', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 7500.00, 1350.00, 18.00, 675.00, 675.00, 1350.00, 8850.00, 'One Hundred and Eighteen Rupees Only', '2025-04-15 11:26:29', '2025-04-15 11:26:29', NULL, 'pending'),
(140, 1, 7, 2, '632510', '2025-02-03', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 4510.00, 811.80, 18.00, 405.90, 405.90, 811.80, 5321.80, 'One Hundred and Eighteen Rupees Only', '2025-04-15 11:26:47', '2025-04-15 11:26:47', NULL, 'pending'),
(141, 1, 7, 2, '632511', '2025-05-15', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 8564.00, 1541.52, 18.00, 770.76, 770.76, 1541.52, 10105.52, 'Five Hundred and Ninety Rupees Only', '2025-04-15 11:32:29', '2025-04-15 11:32:29', NULL, 'pending'),
(142, 1, 6, 2, '632512', '2026-01-15', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 8652.00, 1557.36, 18.00, 778.68, 778.68, 1557.36, 10209.36, 'Six Hundred and Thirteen Rupees and Sixty Paise Only', '2025-04-15 11:37:12', '2025-04-15 11:37:12', NULL, 'pending'),
(143, 1, 7, 2, '632513', '2025-04-17', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-17 05:25:14', '2025-04-17 05:25:14', NULL, 'pending'),
(144, 1, 7, 2, '632514', '2025-04-17', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5000.00, 900.00, 18.00, 450.00, 450.00, 900.00, 5900.00, 'five thousand nine hundred rupees only', '2025-04-17 05:26:07', '2025-04-17 05:26:07', NULL, 'pending'),
(145, 1, 6, 2, '632515', '2025-04-17', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 10000.00, 1800.00, 18.00, 900.00, 900.00, 1800.00, 11800.00, 'eleven thousand eight hundred rupees only', '2025-04-17 05:31:04', '2025-04-17 05:31:04', NULL, 'pending'),
(146, 1, 7, 2, '632516', '2025-04-17', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-17 06:26:57', '2025-04-17 06:26:57', NULL, 'pending'),
(147, 1, 7, 2, '632517', '2025-04-17', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5000.00, 900.00, 18.00, 450.00, 450.00, 900.00, 5900.00, 'five thousand nine hundred rupees only', '2025-04-17 06:44:39', '2025-04-17 06:44:39', NULL, 'pending'),
(148, 1, 7, 2, '632518', '2025-05-21', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 5000.00, 900.00, 18.00, 450.00, 450.00, 900.00, 5900.00, 'five thousand nine hundred rupees only', '2025-04-17 06:59:55', '2025-04-17 06:59:55', NULL, 'pending'),
(149, 1, 7, 2, '632519', '2025-04-17', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 500.00, 90.00, 18.00, 45.00, 45.00, 90.00, 590.00, 'five ninty rupees only', '2025-04-17 07:04:36', '2025-04-17 07:04:36', NULL, 'pending'),
(150, 1, 7, 2, '632520', '2025-04-17', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 10000.00, 1800.00, 18.00, 900.00, 900.00, 1800.00, 11800.00, 'eleven thousand eight hundred rupees only', '2025-04-17 07:16:04', '2025-04-17 07:16:04', NULL, 'pending'),
(151, 1, 7, 2, '632521', '2025-05-19', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 8500.00, 1530.00, 18.00, 765.00, 765.00, 1530.00, 10030.00, 'Five Hundred and Ninety Rupees Only', '2025-04-17 07:19:00', '2025-04-17 07:19:00', NULL, 'pending'),
(152, 1, 6, 2, '632522', '2025-05-30', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 7500.00, 1350.00, 18.00, 675.00, 675.00, 1350.00, 8850.00, 'five ninty rupees only', '2025-04-17 07:19:57', '2025-04-17 07:19:57', NULL, 'pending'),
(153, 1, 6, 2, '632523', '2025-05-08', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 7854.00, 1413.72, 18.00, 706.86, 706.86, 1413.72, 9267.72, 'Five Hundred and Ninety Rupees Only', '2025-04-17 07:20:18', '2025-04-17 07:20:18', NULL, 'pending'),
(154, 1, 6, 2, '632524', '2025-06-19', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 12423.00, 2236.14, 18.00, 1118.07, 1118.07, 2236.14, 14659.14, 'One Hundred and Eighteen Rupees Only', '2025-04-17 07:20:52', '2025-04-17 07:20:52', NULL, 'pending'),
(155, 1, 7, 2, '632525', '2025-06-01', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 7854.00, 1413.72, 18.00, 706.86, 706.86, 1413.72, 9267.72, 'One Hundred Rupees and Thirty Paise Only', '2025-04-17 07:21:08', '2025-04-17 07:21:08', NULL, 'pending'),
(156, 1, 7, 2, '632526', '2025-06-26', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 7854.00, 1413.72, 18.00, 706.86, 706.86, 1413.72, 9267.72, 'One Hundred and Eighteen Rupees Only', '2025-04-17 07:21:24', '2025-04-17 07:21:24', NULL, 'pending'),
(157, 1, 6, 2, '632527', '2025-06-12', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 784.00, 141.12, 18.00, 70.56, 70.56, 141.12, 925.12, 'Five Hundred and Ninety Rupees Only', '2025-04-17 07:21:42', '2025-04-17 07:21:42', NULL, 'pending'),
(158, 1, 7, 2, '632528', '2025-07-01', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 450.00, 81.00, 18.00, 40.50, 40.50, 81.00, 531.00, 'five ninty rupees only', '2025-04-17 07:23:05', '2025-04-17 07:23:05', NULL, 'pending'),
(159, 1, 6, 2, '632529', '2025-07-05', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 745.00, 134.10, 18.00, 67.05, 67.05, 134.10, 879.10, 'Six Hundred and Thirteen Rupees and Sixty Paise Only', '2025-04-17 07:23:23', '2025-04-17 07:23:23', NULL, 'pending'),
(160, 1, 6, 2, '632530', '2025-07-10', 'rakshan shetty', 'near managudda koila bc road mangaluru', '54c2s5d42sx51sd5x2', 'Karnataka', 741.00, 133.38, 18.00, 66.69, 66.69, 133.38, 874.38, 'Six Hundred and Thirteen Rupees and Sixty Paise Only', '2025-04-17 07:23:42', '2025-04-17 07:23:42', NULL, 'pending'),
(161, 1, 7, 2, '632531', '2025-07-15', 'srisha', '11-25/12A near marigudi temple\r\nMarigudi surathkal mangaluru', 'CD5FDFD2C1SSDS4DS5D', 'Karnataka', 7845.00, 1412.10, 18.00, 706.05, 706.05, 1412.10, 9257.10, 'Six Hundred and Thirteen Rupees and Sixty Paise Only', '2025-04-17 07:24:07', '2025-04-17 07:24:07', NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_description`
--

CREATE TABLE `invoice_description` (
  `invoice_desc_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `s_no` int(11) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(20,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL,
  `tax_value` decimal(20,2) DEFAULT NULL,
  `total` decimal(20,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_description`
--

INSERT INTO `invoice_description` (`invoice_desc_id`, `invoice_id`, `s_no`, `description`, `amount`, `tax_rate`, `tax_value`, `total`, `created_at`, `updated_at`) VALUES
(3, 3, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-07 06:05:13', '2025-04-07 06:05:13'),
(4, 4, 1, 'item2', 1000.00, 18.00, 180.00, 1180.00, '2025-04-07 06:11:14', '2025-04-07 06:11:14'),
(5, 5, 1, 'item3', 1500.00, 18.00, 270.00, 1770.00, '2025-04-07 06:15:52', '2025-04-07 06:15:52'),
(9, 9, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-07 06:25:45', '2025-04-07 06:25:45'),
(10, 9, 2, 'item5', 450.00, 18.00, 81.00, 531.00, '2025-04-07 06:25:45', '2025-04-07 06:25:45'),
(11, 10, 1, 'item2', 1000.00, 18.00, 180.00, 1180.00, '2025-04-07 06:29:03', '2025-04-07 06:29:03'),
(12, 10, 2, 'item5', 2500.00, 18.00, 450.00, 2950.00, '2025-04-07 06:29:03', '2025-04-07 06:29:03'),
(13, 10, 3, 'item3', 4500.00, 18.00, 810.00, 5310.00, '2025-04-07 06:29:03', '2025-04-07 06:29:03'),
(14, 11, 1, 'item2', 560.00, 18.00, 100.80, 660.80, '2025-04-07 06:33:17', '2025-04-07 06:33:17'),
(15, 11, 2, 'item5', 450.00, 18.00, 81.00, 531.00, '2025-04-07 06:33:17', '2025-04-07 06:33:17'),
(16, 12, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-08 05:16:45', '2025-04-08 05:16:45'),
(17, 13, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-08 05:44:26', '2025-04-08 05:44:26'),
(18, 13, 2, 'item5', 400.00, 18.00, 72.00, 472.00, '2025-04-08 05:44:26', '2025-04-08 05:44:26'),
(19, 16, 1, 'item1', 8956.00, 18.00, 1612.08, 10568.08, '2025-04-08 05:57:39', '2025-04-08 05:57:39'),
(20, 16, 2, 'item5', 7854.00, 18.00, 1413.72, 9267.72, '2025-04-08 05:57:39', '2025-04-08 05:57:39'),
(21, 16, 3, 'item3', 9865.00, 18.00, 1775.70, 11640.70, '2025-04-08 05:57:39', '2025-04-08 05:57:39'),
(22, 17, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-08 05:58:23', '2025-04-08 05:58:23'),
(23, 18, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-08 06:18:19', '2025-04-08 06:18:19'),
(24, 19, 1, 'item1', 800.00, 18.00, 144.00, 944.00, '2025-04-08 06:19:39', '2025-04-08 06:19:39'),
(25, 20, 1, 'item1', 800.00, 18.00, 144.00, 944.00, '2025-04-08 06:23:37', '2025-04-08 06:23:37'),
(26, 21, 1, 'item1', 4521.00, 18.00, 813.78, 5334.78, '2025-04-08 06:29:08', '2025-04-08 06:29:08'),
(27, 22, 1, 'item1', 45698578.00, 18.00, 8225744.04, 53924322.04, '2025-04-08 06:30:56', '2025-04-08 06:30:56'),
(28, 22, 2, 'item5', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:30:56', '2025-04-08 06:30:56'),
(29, 22, 3, 'item3', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:30:56', '2025-04-08 06:30:56'),
(30, 22, 4, 'item2', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:30:56', '2025-04-08 06:30:56'),
(35, 24, 1, 'item1', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:43:46', '2025-04-08 06:43:46'),
(36, 24, 2, 'item5', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:43:46', '2025-04-08 06:43:46'),
(37, 24, 3, 'item3', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:43:46', '2025-04-08 06:43:46'),
(38, 24, 4, 'item2', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:43:46', '2025-04-08 06:43:46'),
(39, 25, 1, 'item1', 89584855.00, 18.00, 16125273.90, 99999999.99, '2025-04-08 06:58:23', '2025-04-08 06:58:23'),
(40, 25, 2, 'item5', 7848585.00, 18.00, 1412745.30, 9261330.30, '2025-04-08 06:58:23', '2025-04-08 06:58:23'),
(41, 25, 3, 'item3', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 06:58:23', '2025-04-08 06:58:23'),
(42, 25, 4, 'item2', 8569478.00, 18.00, 1542506.04, 10111984.04, '2025-04-08 06:58:23', '2025-04-08 06:58:23'),
(43, 26, 1, 'item1', 8596475.00, 18.00, 1547365.50, 10143840.50, '2025-04-08 07:01:14', '2025-04-08 07:01:14'),
(44, 26, 2, 'item5', 7845965.00, 18.00, 1412273.70, 9258238.70, '2025-04-08 07:01:14', '2025-04-08 07:01:14'),
(45, 26, 3, 'item3', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 07:01:14', '2025-04-08 07:01:14'),
(46, 26, 4, 'item2', 99999999.99, 18.00, 82252725.30, 99999999.99, '2025-04-08 07:01:14', '2025-04-08 07:01:14'),
(47, 27, 1, 'item1', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 07:24:28', '2025-04-08 07:24:28'),
(48, 27, 2, 'item5', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 07:24:28', '2025-04-08 07:24:28'),
(49, 27, 3, 'item3', 87549455.00, 18.00, 15758901.90, 99999999.99, '2025-04-08 07:24:28', '2025-04-08 07:24:28'),
(50, 27, 4, 'item2', 8579454.00, 18.00, 1544301.72, 10123755.72, '2025-04-08 07:24:28', '2025-04-08 07:24:28'),
(51, 28, 1, 'item2', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 10:25:19', '2025-04-08 10:25:19'),
(52, 28, 2, 'item5', 78515522.00, 18.00, 14132793.96, 92648315.96, '2025-04-08 10:25:19', '2025-04-08 10:25:19'),
(53, 28, 3, 'item3', 78451525.00, 18.00, 14121274.50, 92572799.50, '2025-04-08 10:25:19', '2025-04-08 10:25:19'),
(54, 28, 4, 'item2', 99999999.99, 18.00, 99999999.99, 99999999.99, '2025-04-08 10:25:19', '2025-04-08 10:25:19'),
(55, 29, 1, 'item1', 5200.00, 18.00, 936.00, 6136.00, '2025-04-08 10:53:54', '2025-04-08 10:53:54'),
(56, 30, 1, 'item1', 4522.00, 18.00, 813.96, 5335.96, '2025-04-08 10:58:51', '2025-04-08 10:58:51'),
(57, 31, 1, 'item2', 5241.00, 18.00, 943.38, 6184.38, '2025-04-08 11:00:53', '2025-04-08 11:00:53'),
(58, 32, 1, 'item2', 78541.00, 18.00, 14137.38, 92678.38, '2025-04-08 11:07:45', '2025-04-08 11:07:45'),
(59, 33, 1, 'item1', 56200.00, 18.00, 10116.00, 66316.00, '2025-04-09 05:03:25', '2025-04-09 05:03:25'),
(60, 33, 2, 'item5', 4512.00, 18.00, 812.16, 5324.16, '2025-04-09 05:03:25', '2025-04-09 05:03:25'),
(61, 34, 1, 'item2', 4561.00, 18.00, 820.98, 5381.98, '2025-04-09 06:12:56', '2025-04-09 06:12:56'),
(62, 35, 1, 'item1', 8562.00, 18.00, 1541.16, 10103.16, '2025-04-09 06:18:20', '2025-04-09 06:18:20'),
(63, 36, 1, 'item1', 4582.00, 18.00, 824.76, 5406.76, '2025-04-09 06:20:19', '2025-04-09 06:20:19'),
(64, 36, 2, 'item5', 47.00, 18.00, 8.46, 55.46, '2025-04-09 06:20:19', '2025-04-09 06:20:19'),
(65, 37, 1, 'item1', 8541.00, 18.00, 1537.38, 10078.38, '2025-04-09 06:21:26', '2025-04-09 06:21:26'),
(66, 37, 2, 'item5', 78541.00, 18.00, 14137.38, 92678.38, '2025-04-09 06:21:26', '2025-04-09 06:21:26'),
(67, 38, 1, 'item1', 8520.00, 18.00, 1533.60, 10053.60, '2025-04-09 06:25:55', '2025-04-09 06:25:55'),
(68, 39, 1, 'item1', 7851415252.00, 18.00, 1413254745.36, 9264669997.36, '2025-04-09 06:28:35', '2025-04-09 06:28:35'),
(69, 39, 2, 'item5', 969584125.00, 18.00, 174525142.50, 1144109267.50, '2025-04-09 06:28:35', '2025-04-09 06:28:35'),
(70, 39, 3, 'item3', 745125258.00, 18.00, 134122546.44, 879247804.44, '2025-04-09 06:28:35', '2025-04-09 06:28:35'),
(71, 39, 4, 'item2', 857452502.00, 18.00, 154341450.36, 1011793952.36, '2025-04-09 06:28:35', '2025-04-09 06:28:35'),
(72, 40, 1, 'item1', 856.00, 18.00, 154.08, 1010.08, '2025-04-09 06:30:48', '2025-04-09 06:30:48'),
(73, 41, 1, 'item1', 8562.00, 18.00, 1541.16, 10103.16, '2025-04-09 06:53:58', '2025-04-09 06:53:58'),
(74, 41, 2, 'item5', 8574.00, 18.00, 1543.32, 10117.32, '2025-04-09 06:53:58', '2025-04-09 06:53:58'),
(75, 42, 1, 'item2', 7548.00, 18.00, 1358.64, 8906.64, '2025-04-09 06:59:55', '2025-04-09 06:59:55'),
(76, 42, 2, 'item5', 859252.00, 18.00, 154665.36, 1013917.36, '2025-04-09 06:59:55', '2025-04-09 06:59:55'),
(77, 43, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 07:02:31', '2025-04-09 07:02:31'),
(78, 43, 2, 'item5', 1000.00, 18.00, 180.00, 1180.00, '2025-04-09 07:02:31', '2025-04-09 07:02:31'),
(79, 44, 1, 'item1', 952300.00, 18.00, 171414.00, 1123714.00, '2025-04-09 07:04:13', '2025-04-09 07:04:13'),
(80, 44, 2, 'item5', 250000.00, 18.00, 45000.00, 295000.00, '2025-04-09 07:04:13', '2025-04-09 07:04:13'),
(81, 44, 3, 'item3', 135000.00, 18.00, 24300.00, 159300.00, '2025-04-09 07:04:13', '2025-04-09 07:04:13'),
(82, 45, 1, 'item1', 8562147.00, 18.00, 1541186.46, 10103333.46, '2025-04-09 07:16:56', '2025-04-09 07:16:56'),
(83, 45, 2, 'item5', 9652147.00, 18.00, 1737386.46, 11389533.46, '2025-04-09 07:16:56', '2025-04-09 07:16:56'),
(84, 46, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 09:04:27', '2025-04-09 09:04:27'),
(85, 47, 1, 'item1', 600.00, 18.00, 108.00, 708.00, '2025-04-09 09:05:29', '2025-04-09 09:05:29'),
(86, 48, 1, 'item2', 526.00, 18.00, 94.68, 620.68, '2025-04-09 09:50:48', '2025-04-09 09:50:48'),
(87, 49, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-09 09:54:31', '2025-04-09 09:54:31'),
(88, 50, 1, 'item2', 5600.00, 18.00, 1008.00, 6608.00, '2025-04-09 09:59:16', '2025-04-09 09:59:16'),
(89, 51, 1, 'item1', 8000.00, 18.00, 1440.00, 9440.00, '2025-04-09 10:01:18', '2025-04-09 10:01:18'),
(90, 52, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:04:11', '2025-04-09 10:04:11'),
(91, 53, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:06:48', '2025-04-09 10:06:48'),
(92, 54, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:08:30', '2025-04-09 10:08:30'),
(93, 55, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:09:44', '2025-04-09 10:09:44'),
(94, 56, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:12:12', '2025-04-09 10:12:12'),
(95, 57, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:14:53', '2025-04-09 10:14:53'),
(96, 58, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:19:16', '2025-04-09 10:19:16'),
(97, 59, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:23:10', '2025-04-09 10:23:10'),
(98, 60, 1, 'item3', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:26:38', '2025-04-09 10:26:38'),
(99, 61, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:27:21', '2025-04-09 10:27:21'),
(100, 62, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:30:31', '2025-04-09 10:30:31'),
(101, 63, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:32:27', '2025-04-09 10:32:27'),
(102, 64, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:40:53', '2025-04-09 10:40:53'),
(103, 65, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:42:35', '2025-04-09 10:42:35'),
(104, 66, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-09 10:43:45', '2025-04-09 10:43:45'),
(105, 67, 1, 'item2', 800.00, 18.00, 144.00, 944.00, '2025-04-09 10:46:44', '2025-04-09 10:46:44'),
(106, 68, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:03:07', '2025-04-10 05:03:07'),
(107, 69, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:08:22', '2025-04-10 05:08:22'),
(108, 70, 1, 'item2', 5000.00, 18.00, 900.00, 5900.00, '2025-04-10 05:10:29', '2025-04-10 05:10:29'),
(109, 71, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:14:22', '2025-04-10 05:14:22'),
(110, 72, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:23:13', '2025-04-10 05:23:13'),
(111, 73, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:30:27', '2025-04-10 05:30:27'),
(112, 74, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:33:56', '2025-04-10 05:33:56'),
(113, 75, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:36:50', '2025-04-10 05:36:50'),
(114, 76, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 05:41:35', '2025-04-10 05:41:35'),
(115, 77, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:19:56', '2025-04-10 06:19:56'),
(116, 78, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:24:41', '2025-04-10 06:24:41'),
(117, 79, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:27:20', '2025-04-10 06:27:20'),
(118, 80, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:29:13', '2025-04-10 06:29:13'),
(119, 81, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:33:22', '2025-04-10 06:33:22'),
(120, 82, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:35:11', '2025-04-10 06:35:11'),
(121, 83, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:36:36', '2025-04-10 06:36:36'),
(122, 84, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:40:41', '2025-04-10 06:40:41'),
(123, 85, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:57:30', '2025-04-10 06:57:30'),
(124, 95, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 06:59:34', '2025-04-10 06:59:34'),
(125, 96, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 07:01:27', '2025-04-10 07:01:27'),
(126, 97, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 07:07:09', '2025-04-10 07:07:09'),
(127, 98, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 07:10:54', '2025-04-10 07:10:54'),
(128, 99, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 07:14:56', '2025-04-10 07:14:56'),
(129, 100, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 09:36:58', '2025-04-10 09:36:58'),
(130, 101, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 09:42:06', '2025-04-10 09:42:06'),
(131, 102, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 11:36:59', '2025-04-10 11:36:59'),
(132, 103, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 12:24:29', '2025-04-10 12:24:29'),
(133, 104, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-10 12:26:36', '2025-04-10 12:26:36'),
(134, 105, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-11 09:01:49', '2025-04-11 09:01:49'),
(135, 106, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-11 09:03:29', '2025-04-11 09:03:29'),
(136, 107, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-11 09:25:00', '2025-04-11 09:25:00'),
(137, 108, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-11 09:28:23', '2025-04-11 09:28:23'),
(138, 109, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-11 09:32:48', '2025-04-11 09:32:48'),
(139, 110, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-11 10:25:45', '2025-04-11 10:25:45'),
(140, 111, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-11 10:35:31', '2025-04-11 10:35:31'),
(141, 112, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-11 10:41:57', '2025-04-11 10:41:57'),
(142, 113, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-11 10:43:56', '2025-04-11 10:43:56'),
(143, 114, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 05:09:08', '2025-04-14 05:09:08'),
(144, 115, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 05:19:21', '2025-04-14 05:19:21'),
(145, 116, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 05:24:26', '2025-04-14 05:24:26'),
(146, 117, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 05:38:40', '2025-04-14 05:38:40'),
(147, 118, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 05:46:18', '2025-04-14 05:46:18'),
(148, 119, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 05:55:01', '2025-04-14 05:55:01'),
(149, 120, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 06:09:49', '2025-04-14 06:09:49'),
(150, 121, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 06:21:27', '2025-04-14 06:21:27'),
(151, 122, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 06:25:33', '2025-04-14 06:25:33'),
(152, 123, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 06:26:45', '2025-04-14 06:26:45'),
(153, 124, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-14 06:35:52', '2025-04-14 06:35:52'),
(154, 125, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-14 06:46:23', '2025-04-14 06:46:23'),
(155, 126, 1, 'item1', 500.00, 12.00, 60.00, 560.00, '2025-04-14 08:58:00', '2025-04-14 08:58:00'),
(156, 127, 1, 'item3', 5110.00, 12.00, 613.20, 5723.20, '2025-04-14 09:13:18', '2025-04-14 09:13:18'),
(157, 128, 1, 'item3', 5005.00, 12.00, 600.60, 5605.60, '2025-04-14 09:18:01', '2025-04-14 09:18:01'),
(158, 129, 1, 'item2', 5000.00, 12.00, 600.00, 5600.00, '2025-04-14 09:34:39', '2025-04-14 09:34:39'),
(159, 130, 1, 'item2', 5689.00, 12.00, 682.68, 6371.68, '2025-04-14 09:35:45', '2025-04-14 09:35:45'),
(160, 130, 2, 'item5', 7458.00, 12.00, 894.96, 8352.96, '2025-04-14 09:35:45', '2025-04-14 09:35:45'),
(161, 131, 1, 'item1', 5600.00, 12.00, 672.00, 6272.00, '2025-04-14 09:37:23', '2025-04-14 09:37:23'),
(162, 131, 2, 'item5', 7852.00, 12.00, 942.24, 8794.24, '2025-04-14 09:37:23', '2025-04-14 09:37:23'),
(163, 132, 1, 'item1', 500.00, 12.00, 60.00, 560.00, '2025-04-14 09:41:37', '2025-04-14 09:41:37'),
(164, 133, 1, 'item1', 5621.00, 12.00, 674.52, 6295.52, '2025-04-14 09:45:05', '2025-04-14 09:45:05'),
(165, 134, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-15 09:01:31', '2025-04-15 09:01:31'),
(166, 135, 1, 'item1', 590.00, 18.00, 106.20, 696.20, '2025-04-15 10:03:16', '2025-04-15 10:03:16'),
(167, 136, 1, 'item1', 5000.00, 18.00, 900.00, 5900.00, '2025-04-15 10:05:46', '2025-04-15 10:05:46'),
(168, 137, 1, 'item1', 700.00, 18.00, 126.00, 826.00, '2025-04-15 11:25:07', '2025-04-15 11:25:07'),
(169, 138, 1, 'item1', 600.00, 18.00, 108.00, 708.00, '2025-04-15 11:25:44', '2025-04-15 11:25:44'),
(170, 139, 1, 'item2', 7500.00, 18.00, 1350.00, 8850.00, '2025-04-15 11:26:29', '2025-04-15 11:26:29'),
(171, 140, 1, 'item2', 4510.00, 18.00, 811.80, 5321.80, '2025-04-15 11:26:47', '2025-04-15 11:26:47'),
(172, 141, 1, 'item2', 8564.00, 18.00, 1541.52, 10105.52, '2025-04-15 11:32:29', '2025-04-15 11:32:29'),
(173, 142, 1, 'item3', 8652.00, 18.00, 1557.36, 10209.36, '2025-04-15 11:37:12', '2025-04-15 11:37:12'),
(174, 143, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-17 05:25:14', '2025-04-17 05:25:14'),
(175, 144, 1, 'item3', 5000.00, 18.00, 900.00, 5900.00, '2025-04-17 05:26:07', '2025-04-17 05:26:07'),
(176, 145, 1, 'item2', 5000.00, 18.00, 900.00, 5900.00, '2025-04-17 05:31:04', '2025-04-17 05:31:04'),
(177, 145, 2, 'item5', 5000.00, 18.00, 900.00, 5900.00, '2025-04-17 05:31:04', '2025-04-17 05:31:04'),
(178, 146, 1, 'item1', 500.00, 18.00, 90.00, 590.00, '2025-04-17 06:26:57', '2025-04-17 06:26:57'),
(179, 147, 1, 'item2', 5000.00, 18.00, 900.00, 5900.00, '2025-04-17 06:44:39', '2025-04-17 06:44:39'),
(180, 148, 1, 'item3', 5000.00, 18.00, 900.00, 5900.00, '2025-04-17 06:59:55', '2025-04-17 06:59:55'),
(181, 149, 1, 'item2', 500.00, 18.00, 90.00, 590.00, '2025-04-17 07:04:36', '2025-04-17 07:04:36'),
(182, 150, 1, 'item1', 5000.00, 18.00, 900.00, 5900.00, '2025-04-17 07:16:04', '2025-04-17 07:16:04'),
(183, 150, 2, 'item5', 5000.00, 18.00, 900.00, 5900.00, '2025-04-17 07:16:04', '2025-04-17 07:16:04'),
(184, 151, 1, 'item1', 8500.00, 18.00, 1530.00, 10030.00, '2025-04-17 07:19:00', '2025-04-17 07:19:00'),
(185, 152, 1, 'item1', 7500.00, 18.00, 1350.00, 8850.00, '2025-04-17 07:19:57', '2025-04-17 07:19:57'),
(186, 153, 1, 'item3', 7854.00, 18.00, 1413.72, 9267.72, '2025-04-17 07:20:18', '2025-04-17 07:20:18'),
(187, 154, 1, 'item2', 4572.00, 18.00, 822.96, 5394.96, '2025-04-17 07:20:52', '2025-04-17 07:20:52'),
(188, 154, 2, 'item5', 7851.00, 18.00, 1413.18, 9264.18, '2025-04-17 07:20:52', '2025-04-17 07:20:52'),
(189, 155, 1, 'item2', 7854.00, 18.00, 1413.72, 9267.72, '2025-04-17 07:21:08', '2025-04-17 07:21:08'),
(190, 156, 1, 'item2', 7854.00, 18.00, 1413.72, 9267.72, '2025-04-17 07:21:24', '2025-04-17 07:21:24'),
(191, 157, 1, 'item2', 784.00, 18.00, 141.12, 925.12, '2025-04-17 07:21:42', '2025-04-17 07:21:42'),
(192, 158, 1, 'item1', 450.00, 18.00, 81.00, 531.00, '2025-04-17 07:23:05', '2025-04-17 07:23:05'),
(193, 159, 1, 'item3', 745.00, 18.00, 134.10, 879.10, '2025-04-17 07:23:23', '2025-04-17 07:23:23'),
(194, 160, 1, 'item3', 741.00, 18.00, 133.38, 874.38, '2025-04-17 07:23:42', '2025-04-17 07:23:42'),
(195, 161, 1, 'item3', 7845.00, 18.00, 1412.10, 9257.10, '2025-04-17 07:24:07', '2025-04-17 07:24:07');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_master`
--

CREATE TABLE `invoice_master` (
  `invoice_master_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `invoice_number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_master`
--

INSERT INTO `invoice_master` (`invoice_master_id`, `company_id`, `invoice_number`, `created_at`, `updated_at`) VALUES
(1, 2, 500346, '2025-04-05 17:28:22', '2025-04-14 09:45:05'),
(2, 1, 632532, '2025-04-14 06:24:49', '2025-04-17 07:24:07');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_templates`
--

CREATE TABLE `invoice_templates` (
  `template_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `template_type` varchar(50) NOT NULL DEFAULT 'tcpdf',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_templates`
--

INSERT INTO `invoice_templates` (`template_id`, `name`, `description`, `preview_image`, `is_default`, `template_type`, `created_at`) VALUES
(1, 'Modern Clean', 'A clean, minimalist template without tables', NULL, 1, 'tcpdf', '2025-04-17 06:17:58'),
(2, 'Professional Table', 'Clean and organized table-based invoice layout', NULL, 0, 'tcpdf', '2025-04-17 06:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verification`
--

INSERT INTO `otp_verification` (`id`, `email`, `otp`, `created_at`) VALUES
(5, 'test@example.com', '123456', '2025-04-16 11:45:38');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `quotation_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `quotation_number` varchar(50) NOT NULL,
  `quotation_date` date NOT NULL,
  `validity_days` int(11) DEFAULT 30,
  `total_amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `terms_conditions` text DEFAULT NULL,
  `status` enum('draft','sent','accepted','rejected','expired') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`quotation_id`, `company_id`, `client_id`, `quotation_number`, `quotation_date`, `validity_days`, `total_amount`, `tax_amount`, `discount_amount`, `grand_total`, `terms_conditions`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'QT8956001', '2025-04-22', 17, 90000.00, 10800.00, 2700.00, 98100.00, '', 'draft', '2025-04-22 09:23:30', '2025-04-22 09:23:30'),
(2, 1, 9, 'QT8956002', '2025-04-22', 17, 70000.00, 8400.00, 2100.00, 76300.00, '', 'draft', '2025-04-22 09:35:34', '2025-04-22 09:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_history`
--

CREATE TABLE `quotation_history` (
  `history_id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `status` enum('draft','sent','accepted','rejected','expired') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `item_id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`item_id`, `quotation_id`, `description`, `quantity`, `unit_price`, `tax_rate`, `tax_amount`, `total_amount`, `created_at`, `updated_at`) VALUES
(1, 1, 'Mobile App Development', 1.00, 40000.00, 12.00, 4800.00, 44800.00, '2025-04-22 09:23:30', '2025-04-22 09:23:30'),
(2, 1, 'UI/UX Design', 1.00, 15000.00, 12.00, 1800.00, 16800.00, '2025-04-22 09:23:30', '2025-04-22 09:23:30'),
(3, 1, 'API Integration', 1.00, 20000.00, 12.00, 2400.00, 22400.00, '2025-04-22 09:23:30', '2025-04-22 09:23:30'),
(4, 1, 'Push Notification System', 1.00, 10000.00, 12.00, 1200.00, 11200.00, '2025-04-22 09:23:30', '2025-04-22 09:23:30'),
(5, 1, 'App Store Deployment', 1.00, 5000.00, 12.00, 600.00, 5600.00, '2025-04-22 09:23:30', '2025-04-22 09:23:30'),
(6, 2, 'Website Design & Development', 1.00, 25000.00, 12.00, 3000.00, 28000.00, '2025-04-22 09:35:34', '2025-04-22 09:35:34'),
(7, 2, 'Responsive Design Implementation', 1.00, 10000.00, 12.00, 1200.00, 11200.00, '2025-04-22 09:35:34', '2025-04-22 09:35:34'),
(8, 2, 'Content Management System', 1.00, 15000.00, 12.00, 1800.00, 16800.00, '2025-04-22 09:35:34', '2025-04-22 09:35:34'),
(9, 2, 'SEO Integration', 1.00, 8000.00, 12.00, 960.00, 8960.00, '2025-04-22 09:35:34', '2025-04-22 09:35:34'),
(10, 2, 'Security Implementation & SSL', 1.00, 12000.00, 12.00, 1440.00, 13440.00, '2025-04-22 09:35:34', '2025-04-22 09:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_settings`
--

CREATE TABLE `quotation_settings` (
  `setting_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `default_tax_rate` decimal(5,2) DEFAULT 0.00,
  `default_discount_rate` decimal(5,2) DEFAULT 0.00,
  `default_terms` text DEFAULT NULL,
  `quotation_prefix` varchar(10) DEFAULT 'QT',
  `next_number` int(11) DEFAULT 1,
  `validity_days` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotation_settings`
--

INSERT INTO `quotation_settings` (`setting_id`, `company_id`, `default_tax_rate`, `default_discount_rate`, `default_terms`, `quotation_prefix`, `next_number`, `validity_days`) VALUES
(1, 1, 12.00, 3.00, '', 'QT', 8956003, 17);

-- --------------------------------------------------------

--
-- Table structure for table `tax_master`
--

CREATE TABLE `tax_master` (
  `tax_master_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tax_master`
--

INSERT INTO `tax_master` (`tax_master_id`, `company_id`, `tax`, `description`, `is_default`, `created_at`) VALUES
(1, 1, 18.00, '', 1, '2025-04-14 08:37:29'),
(2, 2, 12.00, '', 1, '2025-04-14 08:43:39');

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

CREATE TABLE `tax_rates` (
  `tax_rate_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `tax_name` varchar(100) NOT NULL,
  `tax_rate` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tax_rates`
--

INSERT INTO `tax_rates` (`tax_rate_id`, `company_id`, `tax_name`, `tax_rate`, `description`, `is_default`, `created_at`) VALUES
(2, 1, 'quotation', 12.00, '', 1, '2025-04-22 09:03:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remember_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `created_at`, `updated_at`, `remember_token`, `reset_token`, `reset_expires`) VALUES
(1, 'srisha2373', '$2y$10$1awoQi8syEV5Hp11Htqb1u3Dcxasgw4QI/BO4mx6RgIyPyCAppJIS', 'srisha2373@gmail.com', 'Srisha Mayya', '2025-04-05 08:05:23', '2025-04-16 06:51:19', NULL, NULL, NULL),
(2, 'Shrisha23', '$2y$10$ZjxG6NsJCfwQYsa2JvWad.ZuC85mmMYi5htFIDUE5ybS8P9gxnTmK', 'shrishamayyav23@gmail.com', 'P S Shreesha', '2025-04-05 08:24:48', '2025-04-05 08:24:48', NULL, NULL, NULL),
(3, 'psshreesha2373130', '$2y$10$fhhoRhnedya0.THegVxwmunURPLsqQzk389l5m2COyDnEudxuP8J2', 'psshreesha2373@gmail.com', 'P S Shreesha', '2025-04-16 06:24:52', '2025-04-16 06:24:52', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`preference_id`, `user_id`, `preference_key`, `preference_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'invoice_template', '1', '2025-04-17 06:19:26', '2025-04-17 09:26:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `client_master`
--
ALTER TABLE `client_master`
  ADD PRIMARY KEY (`client_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `company_bank`
--
ALTER TABLE `company_bank`
  ADD PRIMARY KEY (`company_bank_id`),
  ADD UNIQUE KEY `unique_company_bank` (`company_id`);

--
-- Indexes for table `company_master`
--
ALTER TABLE `company_master`
  ADD PRIMARY KEY (`company_id`),
  ADD UNIQUE KEY `unique_user_company` (`user_id`);

--
-- Indexes for table `company_themes`
--
ALTER TABLE `company_themes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_master`
--
ALTER TABLE `email_master`
  ADD PRIMARY KEY (`email_master_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_template` (`company_id`,`template_type`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `unique_invoice_number` (`company_id`,`invoice_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `bank_id` (`bank_id`);

--
-- Indexes for table `invoice_description`
--
ALTER TABLE `invoice_description`
  ADD PRIMARY KEY (`invoice_desc_id`),
  ADD UNIQUE KEY `unique_invoice_item` (`invoice_id`,`s_no`);

--
-- Indexes for table `invoice_master`
--
ALTER TABLE `invoice_master`
  ADD PRIMARY KEY (`invoice_master_id`),
  ADD UNIQUE KEY `unique_company_invoice` (`company_id`);

--
-- Indexes for table `invoice_templates`
--
ALTER TABLE `invoice_templates`
  ADD PRIMARY KEY (`template_id`);

--
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`quotation_id`),
  ADD UNIQUE KEY `unique_company_quotation` (`company_id`,`quotation_number`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `quotation_history`
--
ALTER TABLE `quotation_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `quotation_settings`
--
ALTER TABLE `quotation_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `company_id` (`company_id`);

--
-- Indexes for table `tax_master`
--
ALTER TABLE `tax_master`
  ADD PRIMARY KEY (`tax_master_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD PRIMARY KEY (`tax_rate_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `user_preference` (`user_id`,`preference_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `client_master`
--
ALTER TABLE `client_master`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `company_bank`
--
ALTER TABLE `company_bank`
  MODIFY `company_bank_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_master`
--
ALTER TABLE `company_master`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_themes`
--
ALTER TABLE `company_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_master`
--
ALTER TABLE `email_master`
  MODIFY `email_master_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `invoice_description`
--
ALTER TABLE `invoice_description`
  MODIFY `invoice_desc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT for table `invoice_master`
--
ALTER TABLE `invoice_master`
  MODIFY `invoice_master_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoice_templates`
--
ALTER TABLE `invoice_templates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `quotation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotation_history`
--
ALTER TABLE `quotation_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `quotation_settings`
--
ALTER TABLE `quotation_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tax_master`
--
ALTER TABLE `tax_master`
  MODIFY `tax_master_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tax_rates`
--
ALTER TABLE `tax_rates`
  MODIFY `tax_rate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `client_master`
--
ALTER TABLE `client_master`
  ADD CONSTRAINT `client_master_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`);

--
-- Constraints for table `company_bank`
--
ALTER TABLE `company_bank`
  ADD CONSTRAINT `company_bank_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`);

--
-- Constraints for table `company_master`
--
ALTER TABLE `company_master`
  ADD CONSTRAINT `company_master_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `email_master`
--
ALTER TABLE `email_master`
  ADD CONSTRAINT `email_master_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`);

--
-- Constraints for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD CONSTRAINT `email_templates_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`),
  ADD CONSTRAINT `invoice_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client_master` (`client_id`),
  ADD CONSTRAINT `invoice_ibfk_3` FOREIGN KEY (`bank_id`) REFERENCES `company_bank` (`company_bank_id`);

--
-- Constraints for table `invoice_description`
--
ALTER TABLE `invoice_description`
  ADD CONSTRAINT `invoice_description_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoice` (`invoice_id`);

--
-- Constraints for table `invoice_master`
--
ALTER TABLE `invoice_master`
  ADD CONSTRAINT `invoice_master_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`);

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`),
  ADD CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client_master` (`client_id`);

--
-- Constraints for table `quotation_history`
--
ALTER TABLE `quotation_history`
  ADD CONSTRAINT `quotation_history_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`quotation_id`) ON DELETE CASCADE;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`quotation_id`) ON DELETE CASCADE;

--
-- Constraints for table `quotation_settings`
--
ALTER TABLE `quotation_settings`
  ADD CONSTRAINT `quotation_settings_fk` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `tax_master`
--
ALTER TABLE `tax_master`
  ADD CONSTRAINT `tax_master_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD CONSTRAINT `tax_rates_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company_master` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
