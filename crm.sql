-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 02:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$GXx.2oNdKFlnSeYNBWtcGO61oGf/zewmHzeZv5UBlAFLgomtEkI2a');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `enrolled_by` varchar(100) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `plan` varchar(100) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `recruiter` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `name`, `enrolled_by`, `enrollment_date`, `plan`, `amount_paid`, `recruiter`, `status`, `created_at`) VALUES
(1, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-14 09:18:45'),
(2, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-14 09:19:09'),
(3, 'janvi', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-14 09:20:17'),
(6, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-15 09:02:18'),
(8, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-15 09:04:38'),
(9, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-15 09:05:22'),
(10, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-15 09:07:09'),
(11, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-15 09:19:23'),
(12, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-15 09:21:32'),
(13, 'pary', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'active', '2025-10-15 09:38:49'),
(14, 'janvi', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'active', '2025-10-15 09:44:26'),
(15, 'asd', 'user', '2025-10-14', 'pro path', 123454.00, 'john', 'inactive', '2025-10-16 05:30:04'),
(16, 'PATEL JANVI BHAVESHKUMAR', 'Janvi Patel', '0000-00-00', 'essential', 874512.00, '84561', 'active', '2025-11-04 07:11:23');

-- --------------------------------------------------------

--
-- Table structure for table `daily_report`
--

CREATE TABLE `daily_report` (
  `id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `qualified` int(11) DEFAULT 0,
  `connected` int(11) DEFAULT 0,
  `meetings` int(11) DEFAULT 0,
  `hot` int(11) DEFAULT 0,
  `cold` int(11) DEFAULT 0,
  `interview` int(11) DEFAULT 0,
  `applications` int(11) DEFAULT 0,
  `offer` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_report`
--

INSERT INTO `daily_report` (`id`, `report_date`, `department`, `qualified`, `connected`, `meetings`, `hot`, `cold`, `interview`, `applications`, `offer`, `notes`, `user_id`, `created_at`) VALUES
(1, '2025-10-30', 'Recruiter', 0, 0, 0, 0, 0, 0, 0, 0, 'trfygubhiyvgbuhbh', 1, '2025-10-30 05:04:34'),
(2, '2025-10-30', 'Recruiter', 0, 0, 0, 0, 0, 0, 0, 0, 'fsdghjewertyrthujifjgnkdfmnsdkcnsdkcmd,vx,vc', 1, '2025-10-30 10:16:08'),
(3, '2025-10-30', 'Recruiter', 0, 0, 0, 0, 0, 0, 0, 0, 'xdcfghnnkjnhbgvfcdxsdcfgvhjkml,;kjhgvfcdxsdfghjkl;,kmjnhbgvfcdxesxdfghyujikol,kmijnubgrfderftgyhujikolpo,minubgtfrghujikolkimunybv', 1, '2025-10-30 10:16:25'),
(4, '2025-11-04', 'Recruiter', 0, 0, 0, 0, 0, 0, 0, 0, 'wertyuiol;kjhgfdsjaifghvndiofshjoigtojdsfghjhgtyuiiytrewrsgfhfdsfghfdsewrthyjgtrtghjkhgtretyuiytrewewrthjgfdsfghjgfdghjtewtryukiyutrewetuikuyuterwqrtyujytyrewrtyhjgfdfhgjhgfghjggfghjmgfdsfsdgyhjkertyuewrtyuirety', 1, '2025-11-04 06:56:20');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_employees`
--

CREATE TABLE `deleted_employees` (
  `id` int(11) NOT NULL,
  `join_date` date DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `deleted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_employees`
--

INSERT INTO `deleted_employees` (`id`, `join_date`, `name`, `email`, `mobile`, `address`, `department`, `designation`, `salary`, `deleted_at`) VALUES
(1, '2025-10-14', 'pary', 'janvi@gmail.com', '8765487654', 'qawsedrftgyu', 'Marketing', 'Team Lead', 213456.00, '2025-11-03 13:01:14'),
(2, '2025-10-12', 'Janvi Patelllllllllll', 'janvipb467@gmail.com', '09265496772', 'redctfvjk', 'Sales', 'Manager', 20000.00, '2025-11-03 13:01:23');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `emp_id` varchar(50) NOT NULL,
  `join_date` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `department` varchar(50) NOT NULL,
  `designation` varchar(50) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `bank_account` varchar(50) NOT NULL,
  `ifsc` varchar(20) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `payment_mode` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `emp_id`, `join_date`, `email`, `mobile`, `address`, `department`, `designation`, `salary`, `bank_account`, `ifsc`, `bank_name`, `payment_mode`, `created_at`, `role`) VALUES
(14, 'Pary', '', '0000-00-00', 'pari467@gmail.com', '', '', '', '', 0.00, '', '', '', '', '2025-10-28 06:27:28', 'Staff'),
(20, 'Janvi Patelllllllllll', 'sdfgb', '2025-10-12', 'janvipb467@gmail.com', '09265496772', 'redctfvjk', 'Sales', 'Developer', 20000.00, '45416123489', 'dewrt565', 'SAESRDTHNBVC', 'Cheque', '2025-11-03 09:14:20', 'staff');

-- --------------------------------------------------------

--
-- Table structure for table `employee_notes`
--

CREATE TABLE `employee_notes` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `employee_email` varchar(255) DEFAULT NULL,
  `employee_phone` varchar(50) DEFAULT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_notes`
--

INSERT INTO `employee_notes` (`id`, `employee_id`, `user_id`, `employee_name`, `employee_email`, `employee_phone`, `note`, `created_at`) VALUES
(1, 6, 1, 'janvi', 'janvi@gmail.com', '1234567890', 'sadfghsdfg', '2025-10-27 06:00:03'),
(2, 6, 1, 'janvi', 'janvi@gmail.com', '1234567890', 'vgbnhjumvygbhunjghhjn', '2025-10-27 06:05:42'),
(3, 3, 1, 'pary', 'janvi@gmail.com', '8765487654', 'cfgvcfgvghbjbhj', '2025-10-27 06:06:32'),
(4, 3, 1, 'pary', 'janvi@gmail.com', '8765487654', 'dfcghj kml,', '2025-10-27 06:07:44'),
(5, 12, 1, 'Janvi Patel', 'janvipb467@gmail.com', '09265496772', 'esrdftgyhjkml,', '2025-10-27 08:36:20'),
(6, 20, 1, 'Janvi Patelllllllllll', 'janvipb467@gmail.com', '09265496772', 'rctvgybhunjmik', '2025-11-03 09:22:25'),
(7, 20, 1, 'Janvi Patelllllllllll', 'janvipb467@gmail.com', '09265496772', 'exdxrcftvgybhnj', '2025-11-04 06:54:51');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `visa` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `generatedBy` varchar(100) DEFAULT NULL,
  `leadType` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `email`, `phone`, `visa`, `status`, `generatedBy`, `leadType`, `created_at`) VALUES
(1, 'Janvi Patel', 'janvipb467@gmail.com', '09265496772', 'H1B', 'Qualified', 'Janvi', 'Hot', '2025-11-04 04:57:55'),
(2, 'Pari Patel', 'pari123@gmail.com', '8758728438', 'edr5ftgyhu', 'edrftgy', 'swedrftgybhun', 'dctfvgybhunjj', '2025-11-04 04:58:44'),
(3, 'Janvi Patel', 'janvip67@gmail.com', '0926549655', 'edr5ftgyhu', 'edrftgy', 'swedrftgybhun', 'Hot', '2025-11-04 05:05:24'),
(4, 'rftgy', 'rdftgy@gmail.com', '8452357494', 'H1B', 'Qualified', 'Janvi', 'dctfvgybhunjj', '2025-11-04 05:05:49'),
(5, 'dcrfvgtbhyfvtgy', 'cftvgb@gmail.com', '78984565462', 'edr5ftgyhu', 'Qualified', 'Janvi', 'dctfvgybhunjj', '2025-11-04 05:06:19'),
(6, 'PATEL JANVI BHAVESHKUMAR', 'janvipb467@gmail.com', '09265496772', 'H1B', 'Qualified', 'Janvi', 'Hot', '2025-11-04 05:06:37'),
(7, 'drftgyb', 'cftvgb@gmail.com', '78984565462', 'edr5ftgyhu', 'edrftgy', 'Janvi', 'dctfvgybhunjj', '2025-11-04 05:07:01');

-- --------------------------------------------------------

--
-- Table structure for table `lead_status`
--

CREATE TABLE `lead_status` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_status`
--

INSERT INTO `lead_status` (`id`, `name`) VALUES
(19, 'edrftgy'),
(20, 'Qualified'),
(21, 'connected');

-- --------------------------------------------------------

--
-- Table structure for table `lead_type`
--

CREATE TABLE `lead_type` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_type`
--

INSERT INTO `lead_type` (`id`, `name`) VALUES
(5, 'dctfvgybhunjj'),
(6, 'Hot'),
(7, 'Cold');

-- --------------------------------------------------------

--
-- Table structure for table `lead_visa`
--

CREATE TABLE `lead_visa` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_visa`
--

INSERT INTO `lead_visa` (`id`, `name`) VALUES
(10, 'edr5ftgyhu'),
(11, 'H1B'),
(12, 'DNR1');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `user`, `action`, `details`, `created_at`) VALUES
(1, 'Janvi Patel', 'create_employee', 'Added Pary', '2025-10-28 06:27:28'),
(2, 'Janvi Patel', 'create_role', 'Created role #0 (manager)', '2025-10-28 06:38:01'),
(3, 'Janvi Patel', 'create_target', 'Assigned target #1 to employee #14 amount 180000 (January 2025)', '2025-10-28 06:38:24'),
(4, 'Janvi Patel', 'update_role', 'Updated role #1', '2025-10-29 06:39:19'),
(5, 'Janvi Patel', 'create_target', 'Assigned target #2 to employee #9 amount 2000000 (April 2025)', '2025-10-29 06:39:51'),
(6, 'Janvi Patel', 'create_target', 'Assigned target #3 to employee #8 amount 232333 (January 2025)', '2025-10-29 06:49:52'),
(7, 'Janvi Patel', 'create_target', 'Assigned target #4 to employee #6 amount 2312 (January 2025)', '2025-10-29 07:33:07'),
(8, 'Janvi Patel', 'delete_target', 'Deleted target #3', '2025-10-29 07:52:31'),
(9, 'Janvi Patel', 'delete_target', 'Deleted target #1', '2025-10-29 07:52:36'),
(10, 'Janvi Patel', 'delete_target', 'Deleted target #2', '2025-10-29 07:52:41'),
(11, 'Janvi Patel', 'delete_target', 'Deleted target #4', '2025-10-29 07:52:47'),
(12, 'Janvi Patel', 'create_target', 'Assigned target #5 to employee #8 amount 789689 (January 2025)', '2025-10-29 07:54:23'),
(13, 'Janvi Patel', 'create_target', 'Assigned target #7 to employee #8 amount 898966 (January 2025) category: Lead generation', '2025-10-29 08:36:51'),
(14, 'Janvi Patel', 'create_target', 'Assigned target #8 to employee #8 amount 6236 (September 2025) category: Recruiter', '2025-10-29 08:47:25'),
(15, 'Janvi Patel', 'create_target', 'Assigned target #9 to employee #3 amount 33203 (April 2025) category: Lead generation', '2025-10-29 08:50:26'),
(16, 'Janvi Patel', 'create_target', 'Assigned target #10 to employee #15 amount 498496 (April 2025) category: Sales', '2025-10-29 08:57:08'),
(17, 'Janvi Patel', 'delete_target', 'Deleted target #5', '2025-10-29 08:57:25'),
(18, 'Janvi Patel', 'create_target', 'Assigned target #11 to employee #15 amount 21112363 (November 2025) category: Sales', '2025-10-29 10:10:08'),
(19, 'Janvi Patel', 'create_target', 'Assigned target #12 to employee #6 amount 323233 (August 2025) category: Recruiter', '2025-10-29 10:11:07'),
(20, 'Janvi Patel', 'create_target', 'Assigned target #13 to employee #6 amount 5632156 (April 2025) category: Lead generation', '2025-10-29 10:23:54'),
(21, 'Janvi Patel', 'create_target', 'Assigned target #14 to employee #6 amount 265163 (January 2025) category: Sales', '2025-10-30 09:03:21'),
(22, 'Janvi Patel', 'create_lead_status', 'Added status: vgbyhunji', '2025-10-30 09:55:19'),
(23, 'Janvi Patel', 'create_lead_type', 'Added type: drftgyh', '2025-10-30 09:55:23'),
(24, 'Janvi Patel', 'create_visa_type', 'Added visa: drtfgyhu', '2025-10-30 09:55:26'),
(25, 'Janvi Patel', 'delete_employee', 'Deleted employee #18', '2025-10-30 09:59:39'),
(26, 'Janvi Patel', 'delete_employee', 'Deleted employee #17', '2025-10-30 09:59:43'),
(27, 'Janvi Patel', 'delete_employee', 'Deleted employee #15', '2025-10-30 09:59:46'),
(28, 'Janvi Patel', 'delete_employee', 'Deleted employee #16', '2025-10-30 09:59:49'),
(29, 'Janvi Patel', 'delete_employee', 'Deleted employee #6', '2025-10-30 09:59:53'),
(30, 'Janvi Patel', 'delete_employee', 'Deleted employee #8', '2025-10-30 09:59:57'),
(31, 'Janvi Patel', 'delete_employee', 'Deleted employee #9', '2025-10-30 10:00:00'),
(32, 'Janvi Patel', 'delete_employee', 'Deleted employee #11', '2025-10-30 10:00:03'),
(33, 'Janvi Patel', 'delete_employee', 'Deleted employee #12', '2025-10-30 10:00:05'),
(34, 'Janvi Patel', 'create_target', 'Assigned target #15 to employee #14 amount 1213456 (January 2025) category: Recruiter', '2025-10-30 10:13:53'),
(35, 'Janvi Patel', 'create_target', 'Assigned target #16 to employee #14 amount 784562 (January 2025) category: Recruiter', '2025-10-31 04:36:54'),
(36, 'Janvi Patel', 'create_role', 'Created role #2 (staff)', '2025-11-03 09:30:56'),
(37, 'Janvi Patel', 'update_employee', 'Updated employee #', '2025-11-03 13:46:24');

-- --------------------------------------------------------

--
-- Table structure for table `month_target`
--

CREATE TABLE `month_target` (
  `id` int(11) NOT NULL,
  `employee` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `closure_sales` int(11) DEFAULT 0,
  `closure_lead` int(11) DEFAULT 0,
  `revenue` decimal(15,2) DEFAULT 0.00,
  `cold_leads` int(11) DEFAULT 0,
  `hot_leads` int(11) DEFAULT 0,
  `interviews` int(11) DEFAULT 0,
  `placements` int(11) DEFAULT 0,
  `applications` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `month_target`
--

INSERT INTO `month_target` (`id`, `employee`, `category`, `month`, `year`, `closure_sales`, `closure_lead`, `revenue`, `cold_leads`, `hot_leads`, `interviews`, `placements`, `applications`, `created_at`) VALUES
(1, 'gbhnj', 'Sales', 'March', 2025, 8949, 0, 778456.00, 0, 0, 0, 0, 0, '2025-11-04 06:45:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `total_payment` decimal(10,2) NOT NULL,
  `installments` varchar(50) NOT NULL,
  `status` enum('paid','pending','overdue') NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `enrolled_by` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `customer_name`, `total_payment`, `installments`, `status`, `payment_date`, `payment_method`, `enrolled_by`, `notes`, `created_at`) VALUES
(1, 'pary', 234567.00, '3', 'paid', '2025-10-14', 'Cash', 'Manager', '21234res', '2025-10-14 09:00:42'),
(2, 'pary', 234567.00, '3', 'paid', '2025-10-14', 'Cash', 'Manager', '21234res', '2025-10-14 09:01:52'),
(3, 'pary', 234567.00, '3', 'paid', '2025-10-14', 'Cash', 'Manager', '21234res', '2025-10-14 09:03:56'),
(4, 'janvi', 14332.00, '5', 'pending', '2025-10-14', 'Cash', 'Manager', '`1qw2e3r4t5y6ui', '2025-10-14 09:06:37'),
(5, 'janvi', 14332.00, '5', 'overdue', '2025-10-14', 'Other', 'Team Lead', 'wsedeqq', '2025-10-14 09:07:46'),
(6, 'janvi', 14332.00, '5', 'pending', '2025-10-14', 'Bank Transfer', 'Manager', 'qwertyui', '2025-10-15 05:03:00'),
(7, 'janvippppppppppppppppppp', 14332.00, '5', 'pending', '2025-10-14', 'Cash', 'Admin', 'qw34r56y78', '2025-10-15 05:12:35'),
(8, 'qwe', 8749562.00, '5', 'pending', '2025-10-27', 'Bank Transfer', 'Team Lead', 'ygubhynjimkltvgybhu', '2025-10-27 06:10:40'),
(9, 'qwe', 8749562.00, '5', 'paid', '2025-10-27', 'Bank Transfer', 'Manager', 'asdfvgb', '2025-10-27 07:12:30'),
(10, 'Janvi Patel', 23456.00, '4', 'paid', '5567-04-13', 'Cash', 'Admin', 'aserdvgbhnjmk,', '2025-10-27 08:37:14'),
(11, 'Janvi Patel', 78989.00, '5', 'pending', '2025-10-30', 'Credit Card', 'HR', 'zwasexlpxsdcrfvtgbymj', '2025-10-30 06:27:19');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_key` varchar(50) DEFAULT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_key`, `role_name`, `permissions`, `created_at`) VALUES
(1, 'man', 'staff', '[\"manage_leads\",\"manage_targets\",\"manage_users\",\"view_reports\"]', '2025-10-28 05:50:19'),
(2, NULL, 'staff', '[\"manage_leads\",\"manage_targets\",\"manage_users\",\"view_reports\"]', '2025-11-03 09:30:56');

-- --------------------------------------------------------

--
-- Table structure for table `target`
--

CREATE TABLE `target` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `category` enum('Lead Generation','Recruiters','Sales') NOT NULL,
  `cold_leads` int(11) DEFAULT 0,
  `hot_leads` int(11) DEFAULT 0,
  `total_interviews` int(11) DEFAULT 0,
  `placements` int(11) DEFAULT 0,
  `applications` int(11) DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `year` varchar(20) DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closure_sales` int(11) DEFAULT 0,
  `closure_leads` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `target`
--

INSERT INTO `target` (`id`, `employee_id`, `employee_name`, `category`, `cold_leads`, `hot_leads`, `total_interviews`, `placements`, `applications`, `revenue`, `year`, `month`, `created_at`, `closure_sales`, `closure_leads`) VALUES
(1, 14, '', 'Sales', 0, 0, 0, 0, 0, 32456.00, NULL, NULL, '2025-11-03 05:35:30', 0, 0),
(2, 14, '', 'Recruiters', 0, 0, 34567, 1234, 334567, 0.00, NULL, NULL, '2025-11-03 05:36:32', 0, 0),
(3, 19, 'wert', 'Lead Generation', 3245, 3234, 0, 0, 0, 0.00, NULL, NULL, '2025-11-03 05:37:09', 0, 0),
(4, 19, '', 'Lead Generation', 5634, 1563, 0, 0, 0, 0.00, NULL, NULL, '2025-11-03 06:07:36', 0, 0),
(5, 19, '', 'Lead Generation', 5634, 1563, 0, 0, 0, 0.00, NULL, NULL, '2025-11-03 06:29:29', 0, 0),
(6, 19, 'Janvi Patelllllllllll', 'Lead Generation', 5634, 1563, 0, 0, 0, 0.00, '2025', 'November', '2025-11-03 06:37:50', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `targets`
--

CREATE TABLE `targets` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT '',
  `target_value` decimal(14,2) NOT NULL,
  `achieved_value` decimal(14,2) DEFAULT 0.00,
  `month` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_name` varchar(50) DEFAULT NULL,
  `task` varchar(255) DEFAULT NULL,
  `task_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_name`, `task`, `task_date`) VALUES
(1, 'pary patel', '2waertfyhuik', '2025-10-16'),
(5, 'Janvi Patel', 'tvfgybhunj', '2025-11-03');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`) VALUES
(13, 'swedrftgybhun'),
(14, 'Janvi'),
(15, 'Pary'),
(16, 'Pary'),
(17, 'Janvi'),
(18, 'Janvi Patel'),
(19, 'Patel');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('user','admin') NOT NULL,
  `job_role` enum('sales_person','lead_generator','recruiter') DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `role`, `job_role`, `password`, `created_at`) VALUES
(1, 'Janvi Patel', 'janvipb467@gmail.com', '09265496772', 'admin', '', '$2y$10$z/79SIeDNisC9AvAAS4Ihej1HpEQ.faNWIK1fb8N1ggeqYw8PRRGe', '2025-10-16 16:11:36'),
(2, 'Pary Patel', 'pary123@gmail.com', '01324567890', 'user', 'lead_generator', '$2y$10$mu1erK6cd7hlleU0.qHRbunH3jMyRrejYZ0Hfenh5EnCPu1MhSga6', '2025-10-17 04:35:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `daily_report`
--
ALTER TABLE `daily_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_employees`
--
ALTER TABLE `deleted_employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_notes`
--
ALTER TABLE `employee_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_status`
--
ALTER TABLE `lead_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_type`
--
ALTER TABLE `lead_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_visa`
--
ALTER TABLE `lead_visa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `month_target`
--
ALTER TABLE `month_target`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_key` (`role_key`);

--
-- Indexes for table `target`
--
ALTER TABLE `target`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `targets`
--
ALTER TABLE `targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `daily_report`
--
ALTER TABLE `daily_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deleted_employees`
--
ALTER TABLE `deleted_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `employee_notes`
--
ALTER TABLE `employee_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `lead_status`
--
ALTER TABLE `lead_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `lead_type`
--
ALTER TABLE `lead_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lead_visa`
--
ALTER TABLE `lead_visa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `month_target`
--
ALTER TABLE `month_target`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `target`
--
ALTER TABLE `target`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `targets`
--
ALTER TABLE `targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `targets`
--
ALTER TABLE `targets`
  ADD CONSTRAINT `targets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
