-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 10:45 AM
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
-- Database: `coop_ims`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `table_name` varchar(60) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `logged_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `logged_at`) VALUES
(51, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-07 15:47:22'),
(52, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-07 15:47:38'),
(53, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-07 15:48:04'),
(54, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-07 15:53:10'),
(55, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-07 15:53:30'),
(56, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-08 21:51:39'),
(57, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-08 21:53:40'),
(58, 5, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-08 21:53:50'),
(59, 5, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-08 21:54:12'),
(60, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-08 21:54:21'),
(61, 5, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 00:53:11'),
(62, 5, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 00:53:14'),
(63, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 00:53:21'),
(64, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 00:54:18'),
(65, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 01:03:55'),
(66, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 01:27:14'),
(67, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 01:29:27'),
(68, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 01:29:48'),
(69, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 01:38:47'),
(70, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 01:38:54'),
(71, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 01:46:38'),
(72, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 01:48:29'),
(73, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:01:12'),
(74, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:02:23'),
(75, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:08:46'),
(76, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:09:25'),
(77, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:23:15'),
(78, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:52:49'),
(79, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:52:56'),
(80, 16, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:54:29'),
(81, 5, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:54:38'),
(82, 5, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:54:50'),
(83, 9, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:54:58'),
(84, 9, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:55:47'),
(85, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:56:10'),
(86, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:56:26'),
(87, 10, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:56:38'),
(88, 10, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 02:56:56'),
(89, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 02:57:06'),
(90, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 03:07:17'),
(91, 16, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 03:07:35'),
(92, 10, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 03:07:59'),
(93, 10, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 03:08:05'),
(94, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 03:11:40'),
(95, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 21:31:23'),
(96, 5, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 21:31:38'),
(97, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 21:33:37'),
(98, 5, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 21:33:39'),
(99, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 21:45:23'),
(100, 5, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 21:45:32'),
(101, 5, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 21:48:44'),
(102, 10, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-10 21:48:53'),
(103, 10, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-10 21:49:09'),
(104, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-11 11:07:33'),
(105, 4, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-11 11:08:16'),
(106, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-11 11:08:39'),
(107, 19, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-11 11:09:09'),
(108, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-11 11:11:46'),
(109, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-13 00:26:44'),
(110, 10, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-13 00:27:06'),
(111, 19, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-13 00:27:15'),
(112, 19, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-13 00:28:05'),
(113, 19, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-13 00:29:59'),
(114, 16, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-13 00:30:29'),
(115, 5, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-13 00:36:13'),
(116, 9, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-13 00:36:46'),
(117, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-16 20:47:07'),
(118, 4, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-17 14:23:45'),
(119, 10, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-17 14:36:36'),
(120, 19, 'LOGIN', NULL, NULL, 'User logged in', '::1', '2026-04-17 14:36:47'),
(121, 19, 'LOGOUT', NULL, NULL, 'User logged out', '::1', '2026-04-17 16:05:39');

-- --------------------------------------------------------

--
-- Table structure for table `capital_shares`
--

CREATE TABLE `capital_shares` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `capital_shares`
--

INSERT INTO `capital_shares` (`id`, `member_id`, `amount`, `updated_at`, `updated_by`) VALUES
(4, 15, 10000.00, '2026-04-10 02:07:51', 4),
(5, 16, 79800.00, '2026-04-10 02:08:21', 4),
(6, 17, 5000.00, '2026-04-16 20:47:41', 4);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `doc_type` varchar(80) DEFAULT NULL,
  `filename` varchar(200) DEFAULT NULL,
  `filepath` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `uploaded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `member_id`, `doc_type`, `filename`, `filepath`, `uploaded_at`, `uploaded_by`) VALUES
(1, 16, 'Membership Form', 'doc_1775760393_69d7f409337f7.png', '../uploads/docs/doc_1775760393_69d7f409337f7.png', '2026-04-10 02:46:33', 4),
(2, 16, 'Valid ID', 'doc_1775761556_69d7f894c8470.jpg', '../uploads/docs/doc_1775761556_69d7f894c8470.jpg', '2026-04-10 03:05:56', 4);

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `principal` decimal(12,2) DEFAULT NULL,
  `balance` decimal(12,2) DEFAULT NULL,
  `accrued_penalty` decimal(12,2) DEFAULT 0.00,
  `monthly_due` decimal(12,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `disbursed_at` datetime DEFAULT current_timestamp(),
  `status` enum('active','settled','defaulted') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `application_id`, `member_id`, `principal`, `balance`, `accrued_penalty`, `monthly_due`, `due_date`, `disbursed_at`, `status`) VALUES
(3, 3, 16, 10000.00, 10000.00, 0.00, 1133.33, '2026-05-09', '2026-04-10 02:55:20', 'active'),
(4, 4, 15, 1212.00, 1212.00, 0.00, 278.76, '2026-05-12', '2026-04-13 00:32:01', 'active'),
(5, 5, 17, 1000.00, 1000.00, 0.00, 196.67, '2026-05-17', '2026-04-17 14:49:55', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `loan_type_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `term_months` int(11) NOT NULL,
  `purpose` text DEFAULT NULL,
  `details_json` longtext DEFAULT NULL,
  `status` enum('pending','for_officer_evaluation','for_gm_evaluation','approved','rejected','disbursed') DEFAULT 'pending',
  `applied_at` datetime DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `member_id`, `loan_type_id`, `amount`, `term_months`, `purpose`, `details_json`, `status`, `applied_at`, `approved_at`, `approved_by`, `remarks`) VALUES
(3, 16, 2, 10000.00, 12, '?', NULL, 'disbursed', '2026-04-10 02:54:15', '2026-04-10 02:55:20', 9, ''),
(4, 15, 2, 1212.00, 5, 'g', NULL, 'disbursed', '2026-04-13 00:30:57', '2026-04-13 00:31:47', 4, ''),
(5, 17, 2, 1000.00, 6, 'qq', NULL, 'disbursed', '2026-04-17 14:36:17', '2026-04-17 14:49:40', 4, '');

-- --------------------------------------------------------

--
-- Table structure for table `loan_payments`
--

CREATE TABLE `loan_payments` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime DEFAULT current_timestamp(),
  `payment_method` enum('gcash','cash','bank') DEFAULT 'gcash',
  `reference_no` varchar(60) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_types`
--

CREATE TABLE `loan_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(80) NOT NULL,
  `max_amount` decimal(12,2) DEFAULT NULL,
  `interest` decimal(5,2) DEFAULT NULL,
  `penalty_rate` decimal(5,2) DEFAULT 2.00,
  `max_months` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_types`
--

INSERT INTO `loan_types` (`id`, `type_name`, `max_amount`, `interest`, `penalty_rate`, `max_months`) VALUES
(2, 'Regular Loan', 50000.00, 3.00, 2.00, 12),
(4, 'Spring Board Loan', 30000.00, 1.50, 3.00, 4),
(6, 'Special Loan', 75000.00, 2.00, 3.00, 12);

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `capital_share` decimal(12,2) DEFAULT 0.00,
  `date_joined` date DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_id`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `street`, `barangay`, `city`, `province`, `capital_share`, `date_joined`, `status`, `created_at`) VALUES
(15, 'MEM-001', 'Andi', 'G', 'Sumigin', 'egiplant1029@gmail.com', '09275814545', 'asdasd', 'dasdas', 'asdas', 'dfgyh', 0.00, '2026-04-09', 'active', '2026-04-10 02:07:51'),
(16, 'MEM-002', 'Eric Jason', 'G', 'Requina', 'ericrequina1029@gmail.com', '09111111111111', 'asdasd', 'assdasd', 'asdas', 'asdas', 0.00, '2026-04-09', 'active', '2026-04-10 02:08:20'),
(17, 'MEM-003', 'Eric', '', 'Jason', 'ericrequina1029@gmail.com', '09275814545', 'bosco ra', '', '', '', 0.00, '2026-04-16', 'active', '2026-04-16 20:47:41');

-- --------------------------------------------------------

--
-- Table structure for table `pre_applications`
--

CREATE TABLE `pre_applications` (
  `id` int(11) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `verified_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `initial_capital` decimal(12,2) NOT NULL DEFAULT 5000.00,
  `details_json` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pre_applications`
--

INSERT INTO `pre_applications` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `street`, `barangay`, `city`, `province`, `submitted_at`, `status`, `verified_at`, `admin_notes`, `initial_capital`, `details_json`) VALUES
(4, 'Eric Jason', 'G', 'Requina', 'ericrequina1029@gmail.com', '09111111111111', 'asdasd', 'assdasd', 'asdas', 'asdas', '2026-04-10 01:47:54', 'approved', '2026-04-10 02:08:21', '', 79800.00, NULL),
(5, 'Andi', 'G', 'Sumigin', 'egiplant1029@gmail.com', '09275814545', 'asdasd', 'dasdas', 'asdas', 'dfgyh', '2026-04-10 02:02:06', 'approved', '2026-04-10 02:07:51', '', 10000.00, NULL),
(6, 'Andi', 'ambot', 'Requina', 'ericrequina1029@gmail.com', '09275814545', 'asdasd', 'dasdas', 'xcgv', 'dfgyh', '2026-04-10 02:09:13', 'rejected', '2026-04-10 02:09:29', '', 5000.00, NULL),
(7, 'Kurt', 'G', 'macalaos', 'egiplant1029@gmail.com', '09275814545', 'asd', 'dasdas', 'xcgv', 'gdsasd', '2026-04-10 03:11:26', 'rejected', '2026-04-16 21:38:36', '', 5000.00, NULL),
(8, 'Eric', '', 'Jason', 'ericrequina1029@gmail.com', '09275814545', 'bosco ra', '', '', '', '2026-04-16 20:46:32', 'approved', '2026-04-16 20:47:41', 'go', 5000.00, '{\"dob\":\"2001-02-16\",\"age\":\"30\",\"sex\":\"Female\",\"civil_status\":\"Single\",\"res_cert\":\"123412\",\"occupation\":\"Farmer\",\"residence_types\":[\"owned\"],\"spouse\":{\"name\":\"Junsoy\",\"dob\":\"2009-06-13\",\"job\":\"Alaw\"},\"business\":{\"name\":\"Secret\",\"facebook\":\"qweqwe\"},\"beneficiary\":{\"name\":\"Egi\",\"dob\":\"2026-03-31\",\"sex\":\"Male\",\"relationship\":\"angkol\"},\"loan_details\":{\"types\":[\"regular\",\"salary\"],\"others\":\"alaw\",\"rate\":\"3\",\"term\":\"4\",\"mode\":\"COD\"},\"income\":{\"gross\":\"123123123\",\"expenses\":\"2321\",\"net\":\"11221\"},\"dependents\":[{\"name\":\"qweqwsad\",\"dob\":\"2026-04-09\",\"age\":\"214\",\"rel\":\"mama\"},{\"name\":\"asd\",\"dob\":\"2026-04-01\",\"age\":\"4\",\"rel\":\"amam\"}],\"signature\":\"qweqwe\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pre_application_documents`
--

CREATE TABLE `pre_application_documents` (
  `id` int(11) NOT NULL,
  `pre_application_id` int(11) NOT NULL,
  `doc_type` varchar(80) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pre_application_documents`
--

INSERT INTO `pre_application_documents` (`id`, `pre_application_id`, `doc_type`, `filename`, `filepath`, `uploaded_at`) VALUES
(7, 4, 'Valid ID', 'preapp_1775756874_69d7e64a31ae5.jpg', 'uploads/pre_applications/preapp_1775756874_69d7e64a31ae5.jpg', '2026-04-10 01:47:54'),
(8, 4, 'Additional Document', 'preapp_1775756874_69d7e64a328ee.jpg', 'uploads/pre_applications/preapp_1775756874_69d7e64a328ee.jpg', '2026-04-10 01:47:54'),
(9, 5, 'Valid ID', 'preapp_1775757726_69d7e99e0fe14.png', 'uploads/pre_applications/preapp_1775757726_69d7e99e0fe14.png', '2026-04-10 02:02:06'),
(10, 5, 'Additional Document', 'preapp_1775757726_69d7e99e11c79.png', 'uploads/pre_applications/preapp_1775757726_69d7e99e11c79.png', '2026-04-10 02:02:06'),
(11, 6, 'Valid ID', 'preapp_1775758153_69d7eb494eb72.png', 'uploads/pre_applications/preapp_1775758153_69d7eb494eb72.png', '2026-04-10 02:09:13'),
(12, 6, 'Additional Document', 'preapp_1775758153_69d7eb494fec9.png', 'uploads/pre_applications/preapp_1775758153_69d7eb494fec9.png', '2026-04-10 02:09:13'),
(13, 7, 'Valid ID', 'preapp_1775761886_69d7f9de3d85f.png', '../uploads/pre_applications/preapp_1775761886_69d7f9de3d85f.png', '2026-04-10 03:11:26'),
(14, 7, 'Additional Document', 'preapp_1775761886_69d7f9de3ef09.png', '../uploads/pre_applications/preapp_1775761886_69d7f9de3ef09.png', '2026-04-10 03:11:26'),
(15, 8, 'Valid ID', 'preapp_1776343592_69e0da28e0839.png', '../uploads/pre_applications/preapp_1776343592_69e0da28e0839.png', '2026-04-16 20:46:32'),
(16, 8, 'Additional Document', 'preapp_1776343592_69e0da28e244a.png', '../uploads/pre_applications/preapp_1776343592_69e0da28e244a.png', '2026-04-16 20:46:32');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `category` enum('grocery','rice','other') DEFAULT 'grocery',
  `unit` varchar(20) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `reorder_pt` int(11) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `category`, `unit`, `price`, `stock`, `reorder_pt`, `is_active`, `updated_at`) VALUES
(1, 'RIC-001', 'Premium White Rice', 'rice', 'kg', 55.00, 498, 50, 1, '2026-03-29 13:28:47'),
(2, 'RIC-002', 'Brown Rice', 'rice', 'kg', 65.00, 200, 30, 0, '2026-03-29 13:22:10'),
(3, 'GRC-001', 'Cooking Oil 1L', 'grocery', 'bottle', 85.00, 120, 20, 1, '2026-03-26 16:20:27'),
(4, 'GRC-002', 'Sugar 1kg', 'grocery', 'pack', 70.00, 78, 15, 1, '2026-03-29 13:29:20'),
(5, 'GRC-003', 'Salt 500g', 'grocery', 'pack', 18.00, 138, 25, 1, '2026-03-27 12:32:05'),
(7, 'GRC-006', 'Toyo', 'grocery', 'pack', 10.00, 5, 10, 1, '2026-03-29 13:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `total` decimal(12,2) DEFAULT 0.00,
  `payment_type` enum('cash','credit') DEFAULT 'cash',
  `status` enum('completed','voided') DEFAULT 'completed',
  `recorded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` enum('stock_in','stock_out','adjustment') NOT NULL,
  `qty` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `moved_at` datetime DEFAULT current_timestamp(),
  `moved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `role` enum('loan_officer','cashier','book_keeper','collector','general_manager','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `middle_name`, `last_name`, `email`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'kurt', '$2y$10$OOWr3nb8Ku7XxoZw3V0t4uV6TsiwRdHFn0QK/JUQZ3D.mv58vZqQ.', 'k', '', 'Pagayanan', 'kurt@gmail.com', 'general_manager', 1, '2026-03-26 16:31:27', '2026-03-26 16:31:27'),
(5, 'mj', '$2y$10$tDMS8N1YZth8MT7ayQ/tpuAHbVs0nuJBbK8AkqihJdvulyo.Enywq', 'mj', '', 'macalaos', 'egi@gmail.com', 'book_keeper', 1, '2026-03-26 16:31:59', '2026-03-26 16:31:59'),
(9, 'andi', '$2y$10$9q2ImcArE.3VIJ448n.k6uS1k.uc3EYh9T.QqOzwz8h/Dyk18WWzu', 'Andi', 'ambot', 'Sumigin', 'admin@gmail.com', 'collector', 1, '2026-03-29 18:52:28', '2026-03-29 18:52:28'),
(10, 'egi', '$2y$10$mLPq1zyYb5SZ8fWFwUIENOzkLZbkxfGwzdki6NcDm90Fz2w8R29x2', 'Eric Jason', 'G', 'Requina', 'egi@gmail.com', 'loan_officer', 1, '2026-03-29 18:54:54', '2026-03-29 18:54:54'),
(19, 'test', '$2y$10$3QUlA1MkdhbGLiKtIy02YeobUcLVH91sPVCLa4qIplGmKMBBYnxbi', 'Eric Jason', 'ambot', 'paglinawan', 'e@gmail.ccom', 'cashier', 1, '2026-04-11 11:08:07', '2026-04-11 11:08:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `capital_shares`
--
ALTER TABLE `capital_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `loan_type_id` (`loan_type_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `loan_payments`
--
ALTER TABLE `loan_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `loan_types`
--
ALTER TABLE `loan_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_id` (`member_id`);

--
-- Indexes for table `pre_applications`
--
ALTER TABLE `pre_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pre_application_documents`
--
ALTER TABLE `pre_application_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pre_application_id` (`pre_application_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `moved_by` (`moved_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `capital_shares`
--
ALTER TABLE `capital_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `loan_payments`
--
ALTER TABLE `loan_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loan_types`
--
ALTER TABLE `loan_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `pre_applications`
--
ALTER TABLE `pre_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pre_application_documents`
--
ALTER TABLE `pre_application_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `capital_shares`
--
ALTER TABLE `capital_shares`
  ADD CONSTRAINT `capital_shares_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `capital_shares_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `loan_applications` (`id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD CONSTRAINT `loan_applications_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `loan_applications_ibfk_2` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`),
  ADD CONSTRAINT `loan_applications_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `loan_payments`
--
ALTER TABLE `loan_payments`
  ADD CONSTRAINT `loan_payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  ADD CONSTRAINT `loan_payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `pre_application_documents`
--
ALTER TABLE `pre_application_documents`
  ADD CONSTRAINT `pre_application_documents_ibfk_1` FOREIGN KEY (`pre_application_id`) REFERENCES `pre_applications` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

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
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`moved_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
