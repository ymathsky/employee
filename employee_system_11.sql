-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 03:58 AM
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
-- Database: `employee_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by_id` int(11) DEFAULT NULL COMMENT 'Employee ID of the author',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `created_by_id`, `created_at`) VALUES
(1, 'Annoucement', 'Sample Annoucement', 7, '2025-11-03 09:17:15');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `log_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `log_date` date NOT NULL,
  `scheduled_start_time` datetime DEFAULT NULL COMMENT 'The expected start time of the shift on this day (standard or exception).'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`log_id`, `employee_id`, `time_in`, `time_out`, `remarks`, `log_date`, `scheduled_start_time`) VALUES
(1, 2, '2025-09-29 08:58:00', '2025-09-29 17:05:00', NULL, '2025-09-29', '2025-09-29 09:00:00'),
(2, 2, '2025-09-30 09:01:00', '2025-09-30 17:00:00', NULL, '2025-09-30', '2025-09-30 09:00:00'),
(3, 2, '2025-10-01 09:05:00', '2025-10-01 16:55:00', NULL, '2025-10-01', '2025-10-01 09:00:00'),
(4, 2, '2025-10-02 08:55:00', '2025-10-02 17:00:00', NULL, '2025-10-02', '2025-10-02 09:00:00'),
(5, 2, '2025-10-03 09:00:00', '2025-10-03 17:00:00', NULL, '2025-10-03', '2025-10-03 09:00:00'),
(6, 7, '2025-09-29 09:00:00', '2025-09-29 16:58:00', NULL, '2025-09-29', '2025-09-29 09:00:00'),
(7, 7, '2025-09-30 08:59:00', '2025-09-30 17:02:00', NULL, '2025-09-30', '2025-09-30 09:00:00'),
(8, 7, '2025-10-01 09:00:00', '2025-10-01 17:00:00', NULL, '2025-10-01', '2025-10-01 09:00:00'),
(9, 7, '2025-10-02 09:03:00', '2025-10-02 16:59:00', NULL, '2025-10-02', '2025-10-02 09:00:00'),
(10, 7, '2025-10-03 08:57:00', '2025-10-03 17:05:00', NULL, '2025-10-03', '2025-10-03 09:00:00'),
(11, 2, '2025-10-06 09:00:00', '2025-10-06 17:00:00', NULL, '2025-10-06', '2025-10-06 09:00:00'),
(12, 2, '2025-10-07 09:00:00', '2025-10-07 17:00:00', NULL, '2025-10-07', '2025-10-07 09:00:00'),
(13, 2, '2025-10-08 09:00:00', '2025-10-08 17:00:00', NULL, '2025-10-08', '2025-10-08 09:00:00'),
(14, 2, '2025-10-09 09:00:00', '2025-10-09 17:00:00', NULL, '2025-10-09', '2025-10-09 09:00:00'),
(15, 2, '2025-10-10 09:00:00', '2025-10-10 17:00:00', NULL, '2025-10-10', '2025-10-10 09:00:00'),
(16, 7, '2025-10-06 09:00:00', '2025-10-06 17:00:00', NULL, '2025-10-06', '2025-10-06 09:00:00'),
(17, 7, '2025-10-07 09:00:00', '2025-10-07 17:00:00', NULL, '2025-10-07', '2025-10-07 09:00:00'),
(18, 7, '2025-10-08 09:00:00', '2025-10-08 17:00:00', NULL, '2025-10-08', '2025-10-08 09:00:00'),
(19, 7, '2025-10-09 09:00:00', '2025-10-09 17:00:00', NULL, '2025-10-09', '2025-10-09 09:00:00'),
(20, 7, '2025-10-10 09:00:00', '2025-10-10 17:00:00', NULL, '2025-10-10', '2025-10-10 09:00:00'),
(21, 2, '2025-10-13 09:00:00', '2025-10-13 17:00:00', NULL, '2025-10-13', '2025-10-13 09:00:00'),
(22, 2, '2025-10-14 09:00:00', '2025-10-14 17:00:00', NULL, '2025-10-14', '2025-10-14 09:00:00'),
(23, 2, '2025-10-15 09:00:00', '2025-10-15 17:00:00', NULL, '2025-10-15', '2025-10-15 09:00:00'),
(24, 2, '2025-10-16 09:00:00', '2025-10-16 17:00:00', NULL, '2025-10-16', '2025-10-16 09:00:00'),
(25, 2, '2025-10-17 09:00:00', '2025-10-17 17:00:00', NULL, '2025-10-17', '2025-10-17 09:00:00'),
(26, 7, '2025-10-13 09:00:00', '2025-10-13 17:00:00', NULL, '2025-10-13', '2025-10-13 09:00:00'),
(27, 7, '2025-10-14 09:00:00', '2025-10-14 17:00:00', NULL, '2025-10-14', '2025-10-14 09:00:00'),
(28, 7, '2025-10-15 09:00:00', '2025-10-15 17:00:00', NULL, '2025-10-15', '2025-10-15 09:00:00'),
(29, 7, '2025-10-16 09:00:00', '2025-10-16 17:00:00', NULL, '2025-10-16', '2025-10-16 09:00:00'),
(30, 7, '2025-10-17 09:00:00', '2025-10-17 17:00:00', NULL, '2025-10-17', '2025-10-17 09:00:00'),
(31, 2, '2025-10-20 09:00:00', '2025-10-20 17:00:00', NULL, '2025-10-20', '2025-10-20 09:00:00'),
(32, 2, '2025-10-21 09:00:00', '2025-10-21 17:00:00', NULL, '2025-10-21', '2025-10-21 09:00:00'),
(33, 2, '2025-10-22 09:00:00', '2025-10-22 17:00:00', NULL, '2025-10-22', '2025-10-22 09:00:00'),
(34, 2, '2025-10-23 09:00:00', '2025-10-23 17:00:00', NULL, '2025-10-23', '2025-10-23 09:00:00'),
(35, 2, '2025-10-24 13:16:00', '2025-10-24 17:00:00', 'Late, Half Day', '2025-10-24', '2025-10-24 09:00:00'),
(36, 7, '2025-10-20 09:00:00', '2025-10-20 17:00:00', NULL, '2025-10-20', '2025-10-20 09:00:00'),
(37, 7, '2025-10-21 09:00:00', '2025-10-21 17:00:00', NULL, '2025-10-21', '2025-10-21 09:00:00'),
(38, 7, '2025-10-22 09:00:00', '2025-10-22 17:00:00', NULL, '2025-10-22', '2025-10-22 09:00:00'),
(39, 7, '2025-10-23 09:00:00', '2025-10-23 17:00:00', NULL, '2025-10-23', '2025-10-23 09:00:00'),
(43, 2, '2025-12-03 13:02:00', '2025-12-03 17:00:00', 'Half Day', '2025-12-03', NULL),
(44, 1, '2025-11-17 08:07:37', '2025-11-17 17:29:16', 'Present', '2025-11-17', NULL),
(45, 9, '2025-11-17 08:01:14', '2025-11-17 17:43:15', 'Present', '2025-11-17', NULL),
(46, 2, '2025-11-17 08:14:29', '2025-11-17 17:16:35', 'Present', '2025-11-17', NULL),
(47, 3, '2025-11-17 07:59:55', '2025-11-17 17:43:57', 'Present', '2025-11-17', NULL),
(48, 4, '2025-11-17 07:52:19', '2025-11-17 17:20:08', 'Present', '2025-11-17', NULL),
(49, 7, '2025-11-17 07:46:31', '2025-11-17 17:13:44', 'Present', '2025-11-17', NULL),
(50, 5, '2025-11-17 07:49:43', '2025-11-17 17:47:52', 'Present', '2025-11-17', NULL),
(51, 1, '2025-11-18 08:12:00', '2025-11-18 17:48:35', 'Present', '2025-11-18', NULL),
(52, 9, '2025-11-18 07:46:57', '2025-11-18 17:56:11', 'Present', '2025-11-18', NULL),
(53, 2, '2025-11-18 08:09:54', '2025-11-18 17:02:12', 'Present', '2025-11-18', NULL),
(54, 3, '2025-11-18 07:57:22', '2025-11-18 17:43:51', 'Present', '2025-11-18', NULL),
(55, 4, '2025-11-18 08:11:44', '2025-11-18 17:45:11', 'Present', '2025-11-18', NULL),
(56, 7, '2025-11-18 08:01:53', '2025-11-18 17:48:08', 'Present', '2025-11-18', NULL),
(57, 5, '2025-11-18 08:15:33', '2025-11-18 17:35:07', 'Present', '2025-11-18', NULL),
(58, 1, '2025-11-19 08:10:37', '2025-11-19 17:03:10', 'Present', '2025-11-19', NULL),
(59, 9, '2025-11-19 07:53:27', '2025-11-19 17:29:00', 'Present', '2025-11-19', NULL),
(60, 2, '2025-11-19 07:49:00', '2025-11-19 17:53:46', 'Present', '2025-11-19', NULL),
(61, 3, '2025-11-19 08:11:11', '2025-11-19 17:12:57', 'Present', '2025-11-19', NULL),
(62, 4, '2025-11-19 08:10:13', '2025-11-19 17:13:43', 'Present', '2025-11-19', NULL),
(63, 7, '2025-11-19 07:49:21', '2025-11-19 17:52:02', 'Present', '2025-11-19', NULL),
(64, 5, '2025-11-19 08:13:49', '2025-11-19 17:40:35', 'Present', '2025-11-19', NULL),
(65, 1, '2025-11-20 08:13:55', '2025-11-20 17:30:49', 'Present', '2025-11-20', NULL),
(66, 9, '2025-11-20 08:12:57', '2025-11-20 17:07:36', 'Present', '2025-11-20', NULL),
(67, 2, '2025-11-20 08:00:24', '2025-11-20 17:38:43', 'Present', '2025-11-20', NULL),
(68, 3, '2025-11-20 07:45:50', '2025-11-20 17:15:28', 'Present', '2025-11-20', NULL),
(69, 4, '2025-11-20 07:58:49', '2025-11-20 17:37:24', 'Present', '2025-11-20', NULL),
(70, 7, '2025-11-20 07:52:30', '2025-11-20 17:58:43', 'Present', '2025-11-20', NULL),
(71, 5, '2025-11-20 07:49:33', '2025-11-20 17:47:01', 'Present', '2025-11-20', NULL),
(72, 1, '2025-11-21 08:01:17', '2025-11-21 17:03:53', 'Present', '2025-11-21', NULL),
(73, 9, '2025-11-21 07:46:06', '2025-11-21 17:59:01', 'Present', '2025-11-21', NULL),
(74, 2, '2025-11-21 08:07:37', '2025-11-21 17:32:10', 'Present', '2025-11-21', NULL),
(75, 3, '2025-11-21 07:51:18', '2025-11-21 17:43:21', 'Present', '2025-11-21', NULL),
(76, 4, '2025-11-21 08:13:21', '2025-11-21 17:08:18', 'Present', '2025-11-21', NULL),
(77, 7, '2025-11-21 08:02:19', '2025-11-21 17:26:45', 'Present', '2025-11-21', NULL),
(78, 5, '2025-11-21 08:09:48', '2025-11-21 17:13:34', 'Present', '2025-11-21', NULL),
(79, 1, '2025-11-24 07:57:34', '2025-11-24 17:20:51', 'Present', '2025-11-24', NULL),
(80, 9, '2025-11-24 07:57:47', '2025-11-24 17:41:16', 'Present', '2025-11-24', NULL),
(81, 2, '2025-11-24 07:51:33', '2025-11-24 17:27:29', 'Present', '2025-11-24', NULL),
(82, 3, '2025-11-24 07:45:55', '2025-11-24 17:31:43', 'Present', '2025-11-24', NULL),
(83, 4, '2025-11-24 07:47:02', '2025-11-24 17:14:43', 'Present', '2025-11-24', NULL),
(84, 7, '2025-11-24 08:03:36', '2025-11-24 17:21:37', 'Present', '2025-11-24', NULL),
(85, 5, '2025-11-24 07:54:12', '2025-11-24 17:45:04', 'Present', '2025-11-24', NULL),
(86, 1, '2025-11-25 08:06:11', '2025-11-25 17:15:10', 'Present', '2025-11-25', NULL),
(87, 9, '2025-11-25 08:05:00', '2025-11-25 17:43:35', 'Present', '2025-11-25', NULL),
(88, 2, '2025-11-25 08:01:03', '2025-11-25 17:15:18', 'Present', '2025-11-25', NULL),
(89, 3, '2025-11-25 08:13:43', '2025-11-25 17:17:05', 'Present', '2025-11-25', NULL),
(90, 4, '2025-11-25 08:02:53', '2025-11-25 17:18:35', 'Present', '2025-11-25', NULL),
(91, 7, '2025-11-25 07:51:34', '2025-11-25 17:10:34', 'Present', '2025-11-25', NULL),
(92, 5, '2025-11-25 07:52:32', '2025-11-25 17:47:27', 'Present', '2025-11-25', NULL),
(93, 1, '2025-11-26 07:53:59', '2025-11-26 17:08:20', 'Present', '2025-11-26', NULL),
(94, 9, '2025-11-26 07:52:34', '2025-11-26 17:14:05', 'Present', '2025-11-26', NULL),
(95, 2, '2025-11-26 08:05:19', '2025-11-26 17:35:29', 'Present', '2025-11-26', NULL),
(96, 3, '2025-11-26 08:09:27', '2025-11-26 17:27:59', 'Present', '2025-11-26', NULL),
(97, 4, '2025-11-26 08:05:22', '2025-11-26 17:38:54', 'Present', '2025-11-26', NULL),
(98, 7, '2025-11-26 08:14:19', '2025-11-26 17:08:05', 'Present', '2025-11-26', NULL),
(99, 5, '2025-11-26 08:04:26', '2025-11-26 17:34:23', 'Present', '2025-11-26', NULL),
(100, 1, '2025-11-27 08:13:29', '2025-11-27 17:07:37', 'Present', '2025-11-27', NULL),
(101, 9, '2025-11-27 08:12:50', '2025-11-27 17:28:37', 'Present', '2025-11-27', NULL),
(102, 2, '2025-11-27 07:48:33', '2025-11-27 17:51:43', 'Present', '2025-11-27', NULL),
(103, 3, '2025-11-27 08:10:24', '2025-11-27 17:46:10', 'Present', '2025-11-27', NULL),
(104, 4, '2025-11-27 08:12:19', '2025-11-27 17:25:12', 'Present', '2025-11-27', NULL),
(105, 7, '2025-11-27 08:12:15', '2025-11-27 17:20:40', 'Present', '2025-11-27', NULL),
(106, 5, '2025-11-27 08:09:31', '2025-11-27 17:12:06', 'Present', '2025-11-27', NULL),
(107, 1, '2025-11-28 08:09:55', '2025-11-28 17:36:16', 'Present', '2025-11-28', NULL),
(108, 9, '2025-11-28 08:03:13', '2025-11-28 17:52:30', 'Present', '2025-11-28', NULL),
(109, 2, '2025-11-28 07:54:39', '2025-11-28 17:54:54', 'Present', '2025-11-28', NULL),
(110, 3, '2025-11-28 08:05:18', '2025-11-28 17:02:45', 'Present', '2025-11-28', NULL),
(111, 4, '2025-11-28 08:08:25', '2025-11-28 17:07:44', 'Present', '2025-11-28', NULL),
(112, 7, '2025-11-28 08:11:43', '2025-11-28 17:05:37', 'Present', '2025-11-28', NULL),
(113, 5, '2025-11-28 07:54:11', '2025-11-28 17:05:24', 'Present', '2025-11-28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) UNSIGNED NOT NULL,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `log_timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `employee_id`, `action`, `description`, `log_timestamp`) VALUES
(1, 1, 'LOGIN_SUCCESS', 'User superadmin (Super Admin) successfully logged in.', '2025-10-25 23:13:15'),
(2, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-25 23:13:16'),
(3, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-25 23:15:56'),
(4, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-25 23:18:58'),
(5, 1, 'LOGIN_SUCCESS', 'User superadmin (Super Admin) successfully logged in.', '2025-10-28 23:45:09'),
(6, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-28 23:45:12'),
(7, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-28 23:45:46'),
(8, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-28 23:46:09'),
(9, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-28 23:46:20'),
(10, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-28 23:46:34'),
(11, 1, 'DEPT_DELETED', 'Deleted department \'Engineering\' (ID 101). Unassigned 3 employees.', '2025-10-28 23:47:09'),
(12, 1, 'DEPT_ADDED', 'Added new department: \'Managerial\'. Manager EID: None.', '2025-10-28 23:47:39'),
(13, 1, 'EMPLOYEE_CREATE_FAILED', 'Password mismatch during creation of Raymart Ricamata.', '2025-10-28 23:48:39'),
(14, 1, 'EMPLOYEE_CREATE_FAILED', 'Password mismatch during creation of Raymart Ricamata.', '2025-10-28 23:49:03'),
(15, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 00:06:33'),
(16, 1, 'EMPLOYEE_CREATE_FAILED', 'Password mismatch during creation of Raymart Ricamata.', '2025-10-29 00:09:55'),
(17, 1, 'EMPLOYEE_CREATE_FAILED', 'Password mismatch during creation of Raymart Ricamata.', '2025-10-29 00:10:05'),
(18, 1, 'EMPLOYEE_CREATED', 'New employee Raymart Ricamata (EID: 7, Role: HR Admin) created by Admin EID 1.', '2025-10-29 00:11:06'),
(19, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 00:27:24'),
(20, 1, 'PAY_RATE_DELETED', 'Deleted pay rate (History ID 13) for EID 7. Rate: 25000.00, Effective: 2025-10-28.', '2025-10-29 00:41:30'),
(21, 1, 'PAY_RATE_UPDATED', 'Added new pay rate for EID 7: Daily @ 961.53 effective 2025-10-28.', '2025-10-29 00:41:46'),
(22, 1, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 15) for EID 7: Daily @ 961.53 effective 2025-10-28.', '2025-10-29 00:42:06'),
(23, 1, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 15) for EID 7: Daily @ 961.53 effective 2025-10-28.', '2025-10-29 00:43:35'),
(24, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 00:44:09'),
(25, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 00:44:22'),
(26, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 00:45:02'),
(27, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 00:46:58'),
(28, 1, 'CA_ADDED', 'Added CA/VALE of 100 for EID 7 on 2025-10-28.', '2025-10-29 00:48:50'),
(29, 1, 'PAYROLL_SKIP', 'Skipped Jane Doe (EID 2): Zero gross pay.', '2025-10-29 00:49:39'),
(30, 1, 'PAYROLL_SKIP', 'Skipped Peter Parker (EID 4): Zero gross pay.', '2025-10-29 00:49:39'),
(31, 1, 'PAYROLL_SKIP', 'Skipped Tony Stark (EID 5): Zero gross pay.', '2025-10-29 00:49:39'),
(32, 1, 'PAYROLL_SKIP', 'Skipped Bruce Banner (EID 6): Zero gross pay.', '2025-10-29 00:49:39'),
(33, 1, 'CA_DEDUCTED', 'Deducted 100 CA/VALE for EID 7 in Payroll ID 3. Details: Full deduction of 100 for T-ID 1.', '2025-10-29 00:49:39'),
(34, 1, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-26 to 2025-11-10. Processed 3 payslips.', '2025-10-29 00:49:39'),
(35, 1, 'PAYSLIP_VIEW_SUCCESS', 'User EID 1 viewed Payslip ID 1 for EID 1.', '2025-10-29 00:50:09'),
(36, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 01:13:50'),
(37, 1, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 01:30:47'),
(38, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-09-01 to 2025-09-30, Dept=all.', '2025-10-29 01:30:58'),
(39, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-10 to 2025-10-30, Dept=all.', '2025-10-29 01:31:13'),
(40, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-10 to 2025-10-30, Dept=all.', '2025-10-29 01:31:14'),
(41, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-10 to 2025-10-30, Dept=all.', '2025-10-29 01:31:15'),
(42, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-10 to 2025-10-30, Dept=all.', '2025-10-29 01:31:15'),
(43, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-10 to 2025-10-30, Dept=all.', '2025-10-29 01:31:15'),
(44, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-10 to 2025-10-30, Dept=all.', '2025-10-29 01:31:16'),
(45, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-10 to 2025-10-30, Dept=all.', '2025-10-29 01:31:16'),
(46, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-12 to 2025-10-16, Dept=all.', '2025-10-29 01:31:41'),
(47, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-12 to 2025-10-16, Dept=all.', '2025-10-29 01:31:43'),
(48, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-12 to 2025-10-16, Dept=all.', '2025-10-29 01:31:43'),
(49, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-12 to 2025-10-16, Dept=all.', '2025-10-29 01:31:43'),
(50, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-09-01 to 2025-09-30, Dept=all.', '2025-10-29 01:31:56'),
(51, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-29 to 2025-11-10, Dept=all.', '2025-10-29 01:34:12'),
(52, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-10-29 to 2025-11-10, Dept=all.', '2025-10-29 01:34:17'),
(53, 1, 'PAYROLL_SKIP', 'Skipped Super Admin (EID 1): Pay rate is zero.', '2025-10-29 01:39:22'),
(54, 1, 'PAYROLL_SKIP', 'Skipped Jane Doe (EID 2): Zero gross pay after calculations.', '2025-10-29 01:39:22'),
(55, 1, 'PAYROLL_SKIP', 'Skipped Peter Parker (EID 4): Zero gross pay after calculations.', '2025-10-29 01:39:22'),
(56, 1, 'PAYROLL_SKIP', 'Skipped Tony Stark (EID 5): Zero gross pay after calculations.', '2025-10-29 01:39:22'),
(57, 1, 'PAYROLL_SKIP', 'Skipped Bruce Banner (EID 6): Zero gross pay after calculations.', '2025-10-29 01:39:22'),
(58, 1, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-29 to 2025-11-08. Processed 2 payslips.', '2025-10-29 01:39:22'),
(59, 1, 'PAYSLIP_VIEW_SUCCESS', 'User EID 1 viewed Payslip ID 5 for EID 7.', '2025-10-29 01:42:08'),
(60, 1, 'PAYROLL_SKIP', 'Skipped Super Admin (EID 1): Pay rate is zero.', '2025-10-29 01:44:33'),
(61, 1, 'PAYROLL_SKIP', 'Skipped Jane Doe (EID 2): Zero gross pay after calculations.', '2025-10-29 01:44:33'),
(62, 1, 'PAYROLL_SKIP', 'Skipped Peter Parker (EID 4): Zero gross pay after calculations.', '2025-10-29 01:44:33'),
(63, 1, 'PAYROLL_SKIP', 'Skipped Tony Stark (EID 5): Zero gross pay after calculations.', '2025-10-29 01:44:33'),
(64, 1, 'PAYROLL_SKIP', 'Skipped Bruce Banner (EID 6): Zero gross pay after calculations.', '2025-10-29 01:44:33'),
(65, 1, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-29 to 2025-11-08. Processed 2 payslips.', '2025-10-29 01:44:33'),
(66, 1, 'PAYSLIP_VIEW_SUCCESS', 'User EID 1 viewed Payslip ID 1 for EID 1.', '2025-10-29 01:45:24'),
(67, 1, 'REPORT_GENERATED', 'Generated Payroll Summary Report. Filters: Date=2025-08-01 to 2025-09-30, Dept=all.', '2025-10-29 01:46:43'),
(68, 1, 'PAYSLIP_VIEW_SUCCESS', 'User EID 1 viewed Payslip ID 2 for EID 3.', '2025-10-29 01:48:13'),
(69, 1, 'PAYROLL_SKIP', 'Skipped Super Admin (EID 1): Pay rate is zero.', '2025-10-29 01:50:55'),
(70, 1, 'PAYROLL_SKIP', 'Skipped Peter Parker (EID 4): Zero gross pay after calculations.', '2025-10-29 01:50:55'),
(71, 1, 'PAYROLL_SKIP', 'Skipped Tony Stark (EID 5): Zero gross pay after calculations.', '2025-10-29 01:50:55'),
(72, 1, 'PAYROLL_SKIP', 'Skipped Bruce Banner (EID 6): Zero gross pay after calculations.', '2025-10-29 01:50:55'),
(73, 1, 'CA_DEDUCTED', 'Collected 100 CA/VALE for EID 7 in Payroll ID 10. Details: Full deduction of 100.00 for T-ID 1.', '2025-10-29 01:50:55'),
(74, 1, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-26 to 2025-11-10. Processed 3 payslips.', '2025-10-29 01:50:55'),
(75, 1, 'PAYSLIP_VIEW_SUCCESS', 'User EID 1 viewed Payslip ID 10 for EID 7.', '2025-10-29 01:51:23'),
(76, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-10-29 04:18:44'),
(77, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 04:18:46'),
(78, 7, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 16) for EID 1: Hourly @ 0.00 effective 2025-10-28.', '2025-10-29 04:38:25'),
(79, 7, 'PAY_RATE_DELETED', 'Deleted pay rate (History ID 6) for EID 1. Rate: 150000.00, Effective: 2025-01-01.', '2025-10-29 04:42:54'),
(80, 7, 'PAYSLIP_VIEW_SUCCESS', 'User EID 7 viewed Payslip ID 8 for EID 2.', '2025-10-29 04:43:20'),
(81, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 04:43:22'),
(82, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 04:43:27'),
(83, 7, 'EMPLOYEE_DELETED', 'Employee Bruce Banner successfully deleted.', '2025-10-29 04:43:36'),
(84, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 04:44:01'),
(85, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 04:45:26'),
(86, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Sep 28, 2025 - Oct 28, 2025). Filters: Type=attendance_summary, Start=2025-09-28, End=2025-10-28, Dept=all', '2025-10-29 04:45:35'),
(87, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Sep 28, 2025 - Oct 28, 2025). Filters: Type=payroll_summary, Start=2025-09-28, End=2025-10-28, Dept=all', '2025-10-29 04:45:41'),
(88, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Oct 28, 2025 - Nov 10, 2025). Filters: Type=payroll_summary, Start=2025-10-28, End=2025-11-10, Dept=all', '2025-10-29 04:45:56'),
(89, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Oct 28, 2025 - Nov 10, 2025). Filters: Type=payroll_summary, Start=2025-10-28, End=2025-11-10, Dept=all', '2025-10-29 04:46:40'),
(90, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Oct 28, 2025 - Nov 10, 2025). Filters: Type=attendance_summary, Start=2025-10-28, End=2025-11-10, Dept=all', '2025-10-29 04:46:44'),
(91, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Oct 28, 2025 - Nov 10, 2025). Filters: Type=payroll_summary, Start=2025-10-28, End=2025-11-10, Dept=all', '2025-10-29 04:46:51'),
(92, 7, 'REPORT_GENERATED', 'Generated Leave Balance Report (Current). Filters: Type=leave_balance, Start=2025-09-28, End=2025-10-28, Dept=all', '2025-10-29 04:49:29'),
(93, 7, 'PAYROLL_SKIP', 'Skipped Super Admin (EID 1): Pay rate is zero.', '2025-10-29 04:56:44'),
(94, 7, 'PAYROLL_SKIP', 'Skipped Mark Ruffalo (EID 3): Zero gross pay after calculations.', '2025-10-29 04:56:44'),
(95, 7, 'PAYROLL_SKIP', 'Skipped Peter Parker (EID 4): Zero gross pay after calculations.', '2025-10-29 04:56:44'),
(96, 7, 'PAYROLL_SKIP', 'Skipped Tony Stark (EID 5): Zero gross pay after calculations.', '2025-10-29 04:56:44'),
(97, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-29 to 2025-11-10. Processed 2 payslips.', '2025-10-29 04:56:44'),
(98, 7, 'PAYROLL_SKIP', 'Skipped Super Admin (EID 1): Pay rate is zero.', '2025-10-29 04:57:29'),
(99, 7, 'PAYROLL_SKIP', 'Skipped Mark Ruffalo (EID 3): Zero gross pay after calculations.', '2025-10-29 04:57:29'),
(100, 7, 'PAYROLL_SKIP', 'Skipped Peter Parker (EID 4): Zero gross pay after calculations.', '2025-10-29 04:57:29'),
(101, 7, 'PAYROLL_SKIP', 'Skipped Tony Stark (EID 5): Zero gross pay after calculations.', '2025-10-29 04:57:29'),
(102, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-29 to 2025-11-10. Processed 2 payslips.', '2025-10-29 04:57:29'),
(103, 7, 'PAYSLIP_VIEW_SUCCESS', 'User EID 7 viewed Payslip ID 14 for EID 7.', '2025-10-29 04:58:42'),
(104, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 04:59:02'),
(105, 7, 'PAYSLIP_VIEW_SUCCESS', 'User EID 7 viewed Payslip ID 12 for EID 7.', '2025-10-29 05:08:03'),
(106, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 4 on 2025-10-28.', '2025-10-29 05:12:24'),
(107, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 4 on 2025-10-31.', '2025-10-29 05:12:42'),
(108, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-10-29 to 2025-11-10. Removed 4 payslip records. Reset 0 CA/VALE transaction statuses.', '2025-10-29 05:12:51'),
(109, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 7 on 2025-10-30.', '2025-10-29 05:13:11'),
(110, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-10-26 to 2025-11-10. Removed 3 payslip records. Reset 1 CA/VALE transaction statuses.', '2025-10-29 05:13:19'),
(111, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-10-29 to 2025-11-08. Removed 4 payslip records. Reset 0 CA/VALE transaction statuses.', '2025-10-29 05:13:41'),
(112, 7, 'PAYROLL_SKIP', 'Skipped Super Admin (EID 1): Pay rate is zero.', '2025-10-29 05:14:02'),
(113, 7, 'PAYROLL_SKIP', 'Skipped Mark Ruffalo (EID 3): Zero gross pay after calculations.', '2025-10-29 05:14:02'),
(114, 7, 'PAYROLL_SKIP', 'Skipped Peter Parker (EID 4): Zero gross pay after calculations.', '2025-10-29 05:14:02'),
(115, 7, 'PAYROLL_SKIP', 'Skipped Tony Stark (EID 5): Zero gross pay after calculations.', '2025-10-29 05:14:02'),
(116, 7, 'CA_DEDUCTED', 'Collected 1000 CA/VALE for EID 7 in Payroll ID 16. Details: Full deduction of 0 for T-ID 1.; Full deduction of 1000 for T-ID 4.', '2025-10-29 05:14:02'),
(117, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-28 to 2025-11-10. Processed 2 payslips.', '2025-10-29 05:14:02'),
(118, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-10-28 to 2025-11-10. Removed 2 payslip records. Reset 2 CA/VALE transaction statuses.', '2025-10-29 05:14:43'),
(119, 7, 'PAYROLL_RUN_DELETE_FAILED', 'No payroll records found for period 2025-10-28 to 2025-11-10.', '2025-10-29 05:14:44'),
(120, 7, 'CA_DELETED', 'Deleted pending CA transaction ID 4.', '2025-10-29 05:16:29'),
(121, 7, 'CA_DELETED', 'Deleted pending CA transaction ID 1.', '2025-10-29 05:16:31'),
(122, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 7 on 2025-10-30.', '2025-10-29 05:19:57'),
(123, 7, 'PAYROLL_SKIP', 'Skipped Super Admin (EID 1): Pay rate is zero or not set.', '2025-10-29 05:20:18'),
(124, 7, 'CA_DEDUCTED', 'Collected 1000 CA/VALE for EID 7 in Payroll ID 18. Details: Full deduction of 1000 for T-ID 5.', '2025-10-29 05:20:18'),
(125, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-28 to 2025-11-12. Processed 2 payslips.', '2025-10-29 05:20:18'),
(126, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-10-28 to 2025-11-12. Removed 2 payslip records. Reset 1 CA/VALE transaction statuses.', '2025-10-29 05:20:48'),
(127, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 05:40:27'),
(128, 7, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 16) for EID 1: Hourly @ 1 effective 2025-10-28.', '2025-10-29 06:04:28'),
(129, 7, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 7) for EID 2: Hourly @ 50 effective 2025-01-01.', '2025-10-29 06:04:43'),
(130, 7, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 7) for EID 2: Hourly @ 30 effective 2025-01-01.', '2025-10-29 06:05:07'),
(131, 7, 'PAY_RATE_ACTION_FAILED', 'Failed to add pay rate: Duplicate entry. Action: add, EID: 1, HID: . Error: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1-2025-10-28\' for key \'unique_employee_date\'', '2025-10-29 06:15:09'),
(132, 7, 'PAY_RATE_UPDATED', 'Added new pay rate for EID 1: Fix Rate @ 25000 effective 2025-10-31.', '2025-10-29 06:15:16'),
(133, 7, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 8) for EID 3: Fix Rate @ 7500.00 effective 2025-01-01.', '2025-10-29 06:15:34'),
(134, 7, 'PAY_RATE_UPDATED', 'Updated pay rate (History ID 7) for EID 2: Hourly @ 50 effective 2025-01-01.', '2025-10-29 06:22:44'),
(135, 7, 'EMPLOYEE_CREATE_FAILED', 'Password mismatch during creation of Richard Ariola.', '2025-10-29 06:24:20'),
(136, 7, 'EMPLOYEE_CREATE_FAILED', 'Password mismatch during creation of Richard Ariola.', '2025-10-29 06:25:25'),
(137, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:26:23'),
(138, 7, 'EMPLOYEE_CREATE_FAILED', 'Attempted creation with missing field: confirmPassword.', '2025-10-29 06:27:09'),
(139, 7, 'EMPLOYEE_CREATE_FAILED', 'Attempted creation with missing field: confirmPassword.', '2025-10-29 06:27:17'),
(140, 7, 'EMPLOYEE_CREATE_FAILED', 'Attempted creation with missing field: confirmPassword.', '2025-10-29 06:27:32'),
(141, 7, 'EMPLOYEE_CREATE_FAILED', 'Attempted creation with missing field: confirmPassword.', '2025-10-29 06:29:08'),
(142, 7, 'EMPLOYEE_CREATE_FAILED', 'Attempted creation with missing field: confirmPassword.', '2025-10-29 06:29:52'),
(143, 7, 'EMPLOYEE_CREATE_FAILED', 'Attempted creation with missing field: confirmPassword.', '2025-10-29 06:30:02'),
(144, 7, 'EMPLOYEE_CREATE_FAILED', 'Duplicate: Error: A duplicate entry already exists.', '2025-10-29 06:30:46'),
(145, 7, 'EMPLOYEE_CREATED', 'New employee Richard Ariola (EID: 9, Role: Employee) created by Admin EID 7.', '2025-10-29 06:30:52'),
(146, 9, 'LOGIN_SUCCESS', 'User rariola (Employee) successfully logged in.', '2025-10-29 06:31:10'),
(147, 9, 'PROFILE_PICTURE_UPLOADED', 'Employee uploaded new picture: uploads/profiles/9_1761690728.png', '2025-10-29 06:32:08'),
(148, 9, 'EMPLOYEE_PROFILE_UPDATED', 'Employee updated their own profile details.', '2025-10-29 06:32:08'),
(149, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-10-29 06:38:01'),
(150, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:38:03'),
(151, 9, 'LOGIN_SUCCESS', 'User rariola (Employee) successfully logged in.', '2025-10-29 06:42:05'),
(152, 9, 'EMPLOYEE_PROFILE_UPDATED', 'Employee updated their own profile details.', '2025-10-29 06:43:04'),
(153, 9, 'LOGIN_SUCCESS', 'User rariola (Employee) successfully logged in.', '2025-10-29 06:52:49'),
(154, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:55:17'),
(155, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:55:46'),
(156, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:18'),
(157, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:22'),
(158, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:23'),
(159, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:23'),
(160, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:24'),
(161, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:24'),
(162, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:24'),
(163, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:34'),
(164, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 06:59:59'),
(165, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 07:01:00'),
(166, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 07:02:27'),
(167, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 11:33:23'),
(168, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 11:33:37'),
(169, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-29 to 2025-11-08. Processed 6 payslips.', '2025-10-29 11:33:37'),
(170, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 24 for EID 7.', '2025-10-29 11:33:57'),
(171, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 24 for EID 7.', '2025-10-29 11:34:01'),
(172, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 19 for EID 1.', '2025-10-29 11:35:17'),
(173, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 20 for EID 2.', '2025-10-29 11:35:42'),
(174, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 23 for EID 5.', '2025-10-29 11:35:51'),
(175, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 23 for EID 5.', '2025-10-29 11:35:59'),
(176, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 24 for EID 7.', '2025-10-29 11:36:42'),
(177, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 24 for EID 7.', '2025-10-29 11:36:43'),
(178, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 24 for EID 7.', '2025-10-29 11:36:44'),
(179, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 24 for EID 7.', '2025-10-29 11:37:08'),
(180, 7, 'CA_ADDED', 'Added CA/VALE of 100 for EID 7 on 2025-10-31.', '2025-10-29 11:38:31'),
(181, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 24 for EID 7.', '2025-10-29 11:38:39'),
(182, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 24 for EID 7.', '2025-10-29 11:39:06'),
(183, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 21 for EID 3.', '2025-10-29 11:39:50'),
(184, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 24 for EID 7.', '2025-10-29 11:53:12'),
(185, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 24 for EID 7.', '2025-10-29 11:53:15'),
(186, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 20 for EID 2.', '2025-10-29 11:53:48'),
(187, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 12:04:25'),
(188, 7, 'PAYROLL_SKIP', 'Skipped EID 2: No active/valid pay rate.', '2025-10-29 12:04:25'),
(189, 7, 'PAYROLL_SKIP', 'Skipped EID 3: No active/valid pay rate.', '2025-10-29 12:04:25'),
(190, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 12:04:25'),
(191, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 12:04:25'),
(192, 7, 'PAYROLL_SKIP', 'Skipped EID 7: No active/valid pay rate.', '2025-10-29 12:04:25'),
(193, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 12:04:25'),
(194, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-09-26 to 2025-10-10. Processed 0 payslips.', '2025-10-29 12:04:25'),
(195, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 12:05:59'),
(196, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 12:05:59'),
(197, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 12:05:59'),
(198, 7, 'CA_DEDUCTED', 'Ded. 1000 CA for EID 7 (PID 3). Details: Full ded. 1000 (TID 2)', '2025-10-29 12:05:59'),
(199, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 12:05:59'),
(200, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-09-26 to 2025-10-10. Processed 3 payslips.', '2025-10-29 12:05:59'),
(201, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 2 for EID 3.', '2025-10-29 12:06:09'),
(202, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 2 for EID 3.', '2025-10-29 12:06:38'),
(203, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 3 for EID 7.', '2025-10-29 12:07:56'),
(204, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 1 for EID 2.', '2025-10-29 12:08:06'),
(205, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 1 for EID 2.', '2025-10-29 12:12:19'),
(206, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 12:12:33'),
(207, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 12:12:50'),
(208, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 12:12:50'),
(209, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 12:12:50'),
(210, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 12:12:50'),
(211, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-09-26 to 2025-10-10. Processed 3 payslips.', '2025-10-29 12:12:50'),
(212, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 3 for EID 7.', '2025-10-29 12:13:19'),
(213, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 3 for EID 7.', '2025-10-29 12:13:47'),
(214, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-09-26 to 2025-10-10. Removed 6 payslip records. Reset 1 CA/VALE transaction statuses.', '2025-10-29 12:17:31'),
(215, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 7 on 2025-10-29.', '2025-10-29 12:20:50'),
(216, 7, 'CA_ADDED', 'Added CA/VALE of 500 for EID 7 on 2025-10-16.', '2025-10-29 12:21:29'),
(217, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 12:21:52'),
(218, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 12:21:52'),
(219, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 12:21:52'),
(220, 7, 'CA_DEDUCTED', 'Ded. 1500 CA for EID 7 (PID 9). Details: Full ded. 0 (TID 2); Full ded. 1000 (TID 3); Full ded. 500 (TID 4)', '2025-10-29 12:21:52'),
(221, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 12:21:52'),
(222, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-10-11 to 2025-10-26. Processed 3 payslips.', '2025-10-29 12:21:52'),
(223, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-10-11 to 2025-10-26. Removed 3 payslip records. Reset 3 CA/VALE transaction statuses.', '2025-10-29 12:22:30'),
(224, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 7 on 2025-10-16.', '2025-10-29 12:28:56'),
(229, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 12:34:50'),
(230, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 12:34:50'),
(231, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 12:34:50'),
(232, 7, 'CA_DEDUCTED', 'Ded. 1000 CA for EID 7 (PID 12). Details: Full ded. 1000 (TID 5). Remaining: 0.00', '2025-10-29 12:34:50'),
(233, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 12:34:50'),
(234, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-09-26 to 2025-10-10. Processed 3 payslips.', '2025-10-29 12:34:50'),
(235, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 12 for EID 7.', '2025-10-29 12:35:12'),
(236, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-09-26 to 2025-10-10. Removed 3 payslip records. Reset 1 CA/VALE transaction statuses.', '2025-10-29 12:37:16'),
(237, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 12:40:28'),
(238, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 12:40:28'),
(239, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 12:40:28'),
(240, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 12:40:28'),
(241, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-09-26 to 2025-11-10. Processed 3 payslips.', '2025-10-29 12:40:28'),
(242, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-09-26 to 2025-11-10. Removed 3 payslip records. Reset 0 CA/VALE transaction statuses.', '2025-10-29 12:40:57'),
(243, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 7 on 2025-10-16.', '2025-10-29 12:41:13'),
(244, 7, 'CA_DELETED', 'Soft deleted pending CA transaction ID 6.', '2025-10-29 12:41:47'),
(245, 7, 'CA_ADDED', 'Added CA/VALE of 1000 for EID 7 on 2025-10-03.', '2025-10-29 12:41:57'),
(246, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 12:42:15'),
(247, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 12:42:15'),
(248, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 12:42:15'),
(249, 7, 'CA_DEDUCTED', 'Ded. 1000 CA for EID 7 (PID 18). Details: Full ded. 1000 (TID 7). Remaining: 0.00', '2025-10-29 12:42:15'),
(250, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 12:42:15'),
(251, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-09-26 to 2025-10-10. Processed 3 payslips.', '2025-10-29 12:42:15'),
(252, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 18 for EID 7.', '2025-10-29 12:42:25'),
(253, 7, 'PAYROLL_RUN_DELETED', 'Deleted payroll run for period 2025-09-26 to 2025-10-10. Removed 3 payslip records. Reset 1 CA/VALE transaction statuses.', '2025-10-29 12:42:43'),
(254, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 12:43:33'),
(255, 7, 'LOG_ADJUST_ERROR', 'DB error during adjustment for EID 9: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'is_manual_adjustment\' in \'field list\'', '2025-10-29 13:22:54'),
(256, 7, 'LOG_ADJUST_ERROR', 'DB error during adjustment for EID 9: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'is_manual_adjustment\' in \'field list\'', '2025-10-29 13:22:59'),
(265, 7, 'ATTENDANCE_UPDATED', 'Super Admin adjusted existing log ID 41 for EID 2.', '2025-10-29 13:33:50'),
(266, 7, 'EMPLOYEE_CREATED', 'New employee Team Manager (EID: 10, Role: Manager) created by Admin EID 7.', '2025-10-29 13:37:05'),
(267, 7, 'ATTENDANCE_DELETED', 'Super Admin deleted log ID 41 for EID 2 on 2025-10-26.', '2025-10-29 13:39:49'),
(268, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 13:40:38'),
(269, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 13:53:21'),
(270, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:30:42'),
(271, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:33:12'),
(272, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:37:41'),
(273, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:37:41'),
(274, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:37:42'),
(275, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:37:42'),
(276, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:26'),
(277, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:39'),
(278, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:40'),
(279, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:40'),
(280, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:40'),
(281, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:49'),
(282, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:50'),
(283, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:49:55'),
(284, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:56:26'),
(285, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:59:25'),
(286, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 15:59:50'),
(287, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:08:44'),
(288, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:08:45'),
(289, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:09:11'),
(290, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:09:11'),
(291, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:09:23'),
(292, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:10:00'),
(293, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:10:01'),
(294, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:10:02'),
(295, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:12:50'),
(296, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:14:10'),
(297, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:15:45'),
(298, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:16:27'),
(299, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:16:29'),
(300, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:16:35'),
(301, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:16:37'),
(302, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:16:43'),
(303, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:18:19'),
(304, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 16:51:26'),
(305, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Sep 29, 2025 - Oct 29, 2025). Filters: Type=payroll_summary, Start=2025-09-29, End=2025-10-29, Dept=all', '2025-10-29 17:15:42'),
(306, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-10-29 17:15:52'),
(307, 7, 'PAYROLL_SKIP', 'Skipped EID 1: No active/valid pay rate.', '2025-10-29 17:16:18'),
(308, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-10-29 17:16:18'),
(309, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-10-29 17:16:18'),
(310, 7, 'CA_DEDUCTED', 'Ded. 1000 CA for EID 7 (PID 21). Details: Full ded. 1000 (TID 7). Remaining: 0.00', '2025-10-29 17:16:18'),
(311, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-10-29 17:16:18'),
(312, 7, 'PAYROLL_SKIP', 'Skipped EID 10: No active/valid pay rate.', '2025-10-29 17:16:18'),
(313, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-09-26 to 2025-10-10. Processed 3 payslips.', '2025-10-29 17:16:18'),
(314, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Sep 29, 2025 - Oct 29, 2025). Filters: Type=payroll_summary, Start=2025-09-29, End=2025-10-29, Dept=all', '2025-10-29 17:16:42'),
(315, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Sep 29, 2025 - Oct 29, 2025) - Dept: Human Resources. Filters: Type=payroll_summary, Start=2025-09-29, End=2025-10-29, Dept=Human Resources', '2025-10-29 17:17:00'),
(316, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-01 09:23:40'),
(317, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-01 09:23:41'),
(318, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 19 for EID 2.', '2025-11-01 09:27:02'),
(319, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 19 for EID 2.', '2025-11-01 09:27:03'),
(320, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 21 for EID 7.', '2025-11-01 09:27:27'),
(321, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 21 for EID 7.', '2025-11-01 09:27:31'),
(322, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-01 09:27:55'),
(323, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-01 09:29:07'),
(324, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 20 for EID 3.', '2025-11-01 09:29:51'),
(325, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Oct 2, 2025 - Nov 1, 2025). Filters: Type=attendance_summary, Start=2025-10-02, End=2025-11-01, Dept=all', '2025-11-01 09:46:56'),
(326, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Oct 2, 2025 - Nov 1, 2025). Filters: Type=attendance_summary, Start=2025-10-02, End=2025-11-01, Dept=all', '2025-11-01 09:49:14'),
(327, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-01 10:35:06'),
(328, 7, 'JOURNAL_ENTRY_DELETED', 'Deleted journal entry with ID 1.', '2025-11-01 10:35:29'),
(329, 7, 'PROFILE_PICTURE_UPLOADED', 'Employee uploaded new picture: uploads/profiles/7_1761964602.jpg', '2025-11-01 10:36:42'),
(330, 7, 'EMPLOYEE_PROFILE_UPDATED', 'Employee updated their own profile details.', '2025-11-01 10:36:42'),
(331, 7, 'PROFILE_PICTURE_UPLOADED', 'Employee uploaded new picture: uploads/profiles/7_1761964613.png', '2025-11-01 10:36:53'),
(332, 7, 'EMPLOYEE_PROFILE_UPDATED', 'Employee updated their own profile details.', '2025-11-01 10:36:53'),
(333, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-01 10:41:28'),
(334, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-01 11:43:36'),
(335, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 11:30:08'),
(336, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 11:30:09'),
(337, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 14:22:13'),
(338, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 14:22:23'),
(339, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 14:40:39'),
(340, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 14:40:41'),
(341, 7, 'EMPLOYEE_CREATE_FAILED', 'Duplicate: Error: A duplicate entry already exists.', '2025-11-03 14:55:21'),
(342, 7, 'EMPLOYEE_CREATE_FAILED', 'Duplicate: Error: A duplicate entry already exists.', '2025-11-03 14:55:26'),
(343, 7, 'EMPLOYEE_CREATE_FAILED', 'Duplicate: Error: A duplicate entry already exists.', '2025-11-03 14:55:27'),
(344, 7, 'EMPLOYEE_CREATE_FAILED', 'Duplicate: Error: A duplicate entry already exists.', '2025-11-03 14:55:29'),
(345, 7, 'EMPLOYEE_CREATE_FAILED', 'Duplicate: Error: A duplicate entry already exists.', '2025-11-03 14:55:32'),
(346, 7, 'EMPLOYEE_CREATE_FAILED', 'Duplicate: Error: A duplicate entry already exists.', '2025-11-03 14:55:38'),
(347, 7, 'EMPLOYEE_CREATE_ERROR', 'DB error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'leave_type\' in \'field list\'', '2025-11-03 14:56:59'),
(348, 7, 'EMPLOYEE_CREATE_ERROR', 'DB error: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'leave_type\' in \'field list\'', '2025-11-03 14:57:22'),
(349, 7, 'EMPLOYEE_CREATED', 'New employee Romar Cabrera (EID: 19, Role: Employee) created by Admin EID 7.', '2025-11-03 15:01:56'),
(350, 7, 'EMPLOYEE_UPDATED', 'Employee details updated for EID 19 by Admin EID 7.', '2025-11-03 15:03:27'),
(351, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:03:44'),
(352, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:12:54'),
(353, 7, 'EMPLOYEE_DELETED', 'Employee Romar Cabrera successfully deleted.', '2025-11-03 15:12:57'),
(354, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:13:56'),
(355, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:15:14'),
(356, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:18:32'),
(357, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:18:34'),
(358, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 15:20:08'),
(359, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:20:09'),
(360, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:20:15'),
(361, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 15:20:16'),
(362, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:02:19'),
(363, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:03:48'),
(364, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:44'),
(365, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:45'),
(366, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:49'),
(367, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:55'),
(368, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:56'),
(369, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:56'),
(370, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:56'),
(371, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:57'),
(372, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:57'),
(373, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:58'),
(374, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:58'),
(375, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:58'),
(376, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:08:59'),
(377, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:09:00'),
(378, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:11:31'),
(379, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 16:11:42'),
(380, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:11:43'),
(381, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 16:11:51'),
(382, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 17:15:36'),
(383, 7, 'ANNOUNCEMENT_CREATED', 'New announcement created: dadasd', '2025-11-03 17:17:15'),
(384, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 17:17:17'),
(385, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 17:18:27'),
(386, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 19:28:26'),
(387, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 19:28:32'),
(388, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 19:29:33'),
(389, 7, 'PASSWORD_RESET', 'Admin EID 7 successfully reset password for EID 10.', '2025-11-03 19:29:48'),
(390, 0, 'LOGIN_SUCCESS', 'User tmanager (Manager) successfully logged in.', '2025-11-03 19:29:59'),
(391, 0, 'MANAGER_ANALYTICS_VIEWED', 'Manager viewed analytics for department: Human Resources', '2025-11-03 19:30:00'),
(392, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 19:30:16'),
(393, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 19:30:17'),
(394, 7, 'PASSWORD_RESET', 'Admin EID 7 successfully reset password for EID 9.', '2025-11-03 19:30:34'),
(395, 9, 'LOGIN_SUCCESS', 'User rariola (Employee) successfully logged in.', '2025-11-03 19:30:45'),
(396, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-03 20:34:59'),
(397, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-03 20:35:00'),
(398, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-04 01:11:53'),
(399, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:11:55'),
(400, 7, 'ANNOUNCEMENT_UPDATED', 'Announcement ID 1 updated.', '2025-11-04 01:12:26'),
(401, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:12:40'),
(402, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:13:07'),
(403, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:17:52'),
(404, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:19:05'),
(405, 7, 'GLOBAL_SETTINGS_UPDATED', 'Global settings updated. Changes: company_name: \'EMC\', timezone: \'Asia/Manila\', currency_symbol: \'$\', system_ca_deduction_name: \'Cash Advance\'', '2025-11-04 01:19:37'),
(406, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:19:39'),
(407, 7, 'GLOBAL_SETTINGS_UPDATED', 'Global settings updated. Changes: company_name: \'EMC\', timezone: \'Asia/Manila\', currency_symbol: \'₱\', system_ca_deduction_name: \'Cash Advance\'', '2025-11-04 01:19:58'),
(408, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:20:00'),
(409, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 20 for EID 3.', '2025-11-04 01:22:37'),
(410, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 20 for EID 3.', '2025-11-04 01:22:38'),
(411, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:22:56'),
(412, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:34:27'),
(413, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:34:35'),
(414, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:44:01'),
(415, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:46:39'),
(416, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:47:11'),
(417, 7, 'DEPT_UPDATED', 'Updated department ID 103 to \'Beyond Wound Care\'. New Manager EID: None.', '2025-11-04 01:47:44'),
(418, 7, 'DEPT_DELETED', 'Deleted department \'Marketing\' (ID 102). Unassigned 2 employees.', '2025-11-04 01:47:47'),
(419, 7, 'DEPT_UPDATED', 'Updated department ID 104 to \'MDOS\'. New Manager EID: None.', '2025-11-04 01:47:57'),
(420, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Oct 4, 2025 - Nov 3, 2025) - Dept: Beyond Wound Care. Filters: Type=attendance_summary, Start=2025-10-04, End=2025-11-03, Dept=Beyond Wound Care', '2025-11-04 01:48:21'),
(421, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Oct 4, 2025 - Nov 3, 2025) - Dept: MDOS. Filters: Type=attendance_summary, Start=2025-10-04, End=2025-11-03, Dept=MDOS', '2025-11-04 01:48:24'),
(422, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:48:51'),
(423, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 19 for EID 2.', '2025-11-04 01:49:10'),
(424, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:49:12'),
(425, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-04 01:49:22'),
(426, 7, 'PAY_RATE_UPDATED', 'Set new rate for EID 1: Hourly @ 20 effective 2025-11-03.', '2025-11-04 01:53:04'),
(427, 7, 'EMPLOYEE_UPDATED', 'Employee details updated for EID 10 by Admin EID 7.', '2025-11-04 01:53:54'),
(428, 7, 'EMPLOYEE_UPDATED', 'Employee details updated for EID 9 by Admin EID 7.', '2025-11-04 01:54:09'),
(429, 7, 'EMPLOYEE_UPDATED', 'Employee details updated for EID 1 by Admin EID 7.', '2025-11-04 01:54:42'),
(430, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Sep 4, 2025 - Nov 3, 2025). Filters: Type=attendance_summary, Start=2025-09-04, End=2025-11-03, Dept=all', '2025-11-04 01:55:04'),
(431, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Sep 4, 2025 - Nov 3, 2025). Filters: Type=attendance_summary, Start=2025-09-04, End=2025-11-03, Dept=all', '2025-11-04 01:59:42'),
(432, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-22 08:33:06'),
(433, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-22 08:33:08'),
(434, 7, 'GLOBAL_SETTINGS_UPDATED', 'Global settings updated. Changes: company_name: \'EMC\', timezone: \'Asia/Manila\', currency_symbol: \'₱\', system_ca_deduction_name: \'Cash Advance\', allow_manual_attendance_edit: \'0\'', '2025-11-22 08:34:27'),
(435, 7, 'EMPLOYEE_CREATED', 'New employee aa aa (EID: 20, Role: HR Admin) created by Admin EID 7.', '2025-11-22 08:35:25'),
(436, 0, 'LOGIN_FAILED', 'Failed login attempt for email: hr@admin.com from IP: ::1', '2025-11-22 08:35:43'),
(437, 20, 'LOGIN_SUCCESS', 'User aaa (HR Admin) successfully logged in.', '2025-11-22 08:35:50'),
(438, 20, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-22 08:35:52'),
(439, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-11-22 08:36:41'),
(440, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-22 08:36:42'),
(441, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-22 08:52:22'),
(442, 20, 'LOGIN_SUCCESS', 'User aaa (HR Admin) successfully logged in.', '2025-11-22 08:52:31'),
(443, 20, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-22 08:52:32'),
(444, 20, 'SCHEDULE_UPDATE_FAILED', 'Admin attempted to update standard schedule for EID 7: End time before start time for mon.', '2025-11-22 08:59:22'),
(445, 20, 'SCHEDULE_UPDATE_FAILED', 'Admin attempted to update standard schedule with missing EID.', '2025-11-22 09:07:26'),
(446, 20, 'LOGIN_SUCCESS', 'User aaa (HR Admin) successfully logged in.', '2025-11-26 06:29:07'),
(447, 20, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-26 06:29:09');
INSERT INTO `audit_logs` (`log_id`, `employee_id`, `action`, `description`, `log_timestamp`) VALUES
(448, 20, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-26 06:29:36'),
(449, 20, 'LOGIN_SUCCESS', 'User aaa (HR Admin) successfully logged in.', '2025-11-28 14:38:24'),
(450, 20, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-28 14:38:26'),
(451, 20, 'SCHEDULE_UPDATE', 'Added dedicated off day (Sun) for EID 7 effective 2025-11-28', '2025-11-28 14:39:07'),
(452, 20, 'SCHEDULE_UPDATE', 'Added dedicated off day (Mon) for EID 7 effective 2025-11-29', '2025-11-29 12:57:32'),
(453, 20, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-11-29 13:01:21'),
(454, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-12-04 02:51:08'),
(455, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 02:51:10'),
(456, 7, 'EMPLOYEE_DELETED', 'Employee aa aa successfully deleted.', '2025-12-04 02:51:19'),
(457, 7, 'EMPLOYEE_DELETED', 'Employee Team Manager successfully deleted.', '2025-12-04 02:52:10'),
(458, 7, 'SCHEDULE_UPDATE_FAILED', 'Admin attempted to update standard schedule with missing EID.', '2025-12-04 03:00:33'),
(459, 7, 'SCHEDULE_UPDATE_FAILED', 'Admin attempted to update standard schedule with missing EID.', '2025-12-04 03:00:39'),
(460, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 1.', '2025-12-04 03:04:26'),
(461, 7, 'ATTENDANCE_UPDATED', 'Super Admin adjusted existing log ID 35 for EID 2.', '2025-12-04 03:35:11'),
(462, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 2.', '2025-12-04 03:36:34'),
(463, 7, 'ATTENDANCE_UPDATED', 'Super Admin adjusted existing log ID 35 for EID 2.', '2025-12-04 03:36:44'),
(464, 7, 'ATTENDANCE_UPDATED', 'Super Admin adjusted existing log ID 35 for EID 2.', '2025-12-04 03:41:07'),
(465, 7, 'ATTENDANCE_DELETED', 'Super Admin deleted log ID 40 for EID 7 on 2025-10-24.', '2025-12-04 03:42:31'),
(466, 7, 'ATTENDANCE_UPDATED', 'Super Admin adjusted existing log ID 35 for EID 2.', '2025-12-04 03:49:33'),
(467, 7, 'ATTENDANCE_ADDED', 'Super Admin manually added new log ID 42 for EID 2 on 2025-11-28.', '2025-12-04 03:50:08'),
(468, 7, 'ATTENDANCE_DELETED', 'Super Admin deleted log ID 42 for EID 2 on 2025-11-28.', '2025-12-04 03:50:22'),
(469, 7, 'ATTENDANCE_ADDED', 'Super Admin manually added new log ID 43 for EID 2 on 2025-12-03.', '2025-12-04 03:50:41'),
(470, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 1.', '2025-12-04 03:58:22'),
(471, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 1.', '2025-12-04 04:01:36'),
(472, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 1.', '2025-12-04 04:01:41'),
(473, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 1.', '2025-12-04 04:02:45'),
(474, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 1.', '2025-12-04 04:03:06'),
(475, 7, 'SCHEDULE_EXCEPTION_DELETED', 'Deleted schedule exception (ID 1) for EID 1 on 2025-12-06.', '2025-12-04 04:04:02'),
(476, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 9.', '2025-12-04 04:04:13'),
(477, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 9.', '2025-12-04 04:04:43'),
(478, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 9.', '2025-12-04 04:04:47'),
(479, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 9.', '2025-12-04 04:05:09'),
(480, 7, 'SCHEDULE_STANDARD_UPDATED', 'Updated standard schedule for EID 9.', '2025-12-04 04:11:05'),
(481, 7, 'GLOBAL_SETTINGS_UPDATED', 'Global settings updated. Changes: company_name: \'EMC\', timezone: \'America/Chicago\', currency_symbol: \'₱\', system_ca_deduction_name: \'Cash Advance\', allow_manual_attendance_edit: \'0\'', '2025-12-04 04:13:23'),
(482, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-12-04 04:27:57'),
(483, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 04:27:59'),
(484, 7, 'JOURNAL_ENTRY_LOGGED', 'Logged a \'Positive\' journal entry for EID 9.', '2025-12-04 04:31:13'),
(485, 7, 'JOURNAL_ENTRY_DELETED', 'Deleted journal entry with ID 2.', '2025-12-04 04:31:19'),
(486, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 04:31:22'),
(487, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 04:32:01'),
(488, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 04:32:08'),
(489, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 21 for EID 7.', '2025-12-04 04:39:34'),
(490, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 21 for EID 7.', '2025-12-04 04:39:47'),
(491, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 19 for EID 2.', '2025-12-04 04:40:17'),
(492, 7, 'PAYROLL_SKIP', 'Skipped EID 4: No active/valid pay rate.', '2025-12-04 04:42:37'),
(493, 7, 'PAYROLL_SKIP', 'Skipped EID 5: No active/valid pay rate.', '2025-12-04 04:42:37'),
(494, 7, 'PAYROLL_SKIP', 'Skipped EID 9: No active/valid pay rate.', '2025-12-04 04:42:37'),
(495, 7, 'PAYROLL_GENERATED', 'Payroll run completed for period 2025-11-15 to 2025-12-03. Processed 4 payslips.', '2025-12-04 04:42:37'),
(496, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 22 for EID 1.', '2025-12-04 04:43:02'),
(497, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 22 for EID 1.', '2025-12-04 04:44:24'),
(498, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 22 for EID 1.', '2025-12-04 04:44:39'),
(499, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 22 for EID 1.', '2025-12-04 04:45:23'),
(500, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 22 for EID 1.', '2025-12-04 04:45:56'),
(501, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 22 for EID 1.', '2025-12-04 04:48:27'),
(502, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 22 for EID 1.', '2025-12-04 04:49:55'),
(503, 7, 'EMPLOYEE_UPDATED', 'Employee details updated for EID 7 by Admin EID 7.', '2025-12-04 04:51:07'),
(504, 7, 'LOGIN_SUCCESS', 'User rricamata (Employee) successfully logged in.', '2025-12-04 04:51:13'),
(505, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 25 for EID 7.', '2025-12-04 04:51:20'),
(506, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-12-04 04:52:35'),
(507, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 04:52:37'),
(508, 7, 'EMPLOYEE_UPDATED', 'Employee details updated for EID 2 by Admin EID 7.', '2025-12-04 04:53:25'),
(509, 7, 'PASSWORD_RESET', 'Admin EID 7 successfully reset password for EID 2.', '2025-12-04 04:53:47'),
(510, 2, 'LOGIN_SUCCESS', 'User janedoe (Employee) successfully logged in.', '2025-12-04 04:54:01'),
(511, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-12-04 04:54:25'),
(512, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 04:54:27'),
(513, 7, 'CA_ADDED', 'Added CA/VALE of 500 for EID 2 on 2025-12-03.', '2025-12-04 04:54:40'),
(514, 2, 'LOGIN_SUCCESS', 'User janedoe (Employee) successfully logged in.', '2025-12-04 04:54:49'),
(515, 2, 'PAYSLIP_VIEW_SUCCESS', 'EID 2 viewed PID 23 for EID 2.', '2025-12-04 04:55:52'),
(516, 2, 'PROFILE_PICTURE_UPLOADED', 'Employee uploaded new picture: uploads/profiles/2_1764795360.jpg', '2025-12-04 04:56:00'),
(517, 2, 'EMPLOYEE_PROFILE_UPDATED', 'Employee updated their own profile details.', '2025-12-04 04:56:00'),
(518, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-12-04 04:57:09'),
(519, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 04:57:10'),
(520, 7, 'DEDUCTION_ADD_FAILED', 'Failed to add deduction \'sss\': Name already exists.', '2025-12-04 05:02:24'),
(521, 7, 'DEDUCTION_ADDED', 'Added new deduction \'raymart SSS\' for Employee ID 7. Type: Fixed, Value: 250.', '2025-12-04 05:02:56'),
(522, 7, 'DEDUCTION_DELETED', 'Deleted deduction \'raymart SSS\' (ID 5).', '2025-12-04 05:03:35'),
(523, 7, 'DEDUCTION_ADDED', 'Added new deduction \'SSS\' for Employee ID 7. Type: Percentage, Value: 1.', '2025-12-04 05:03:42'),
(524, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 19 for EID 2.', '2025-12-04 05:04:33'),
(525, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 19 for EID 2.', '2025-12-04 05:04:41'),
(526, 7, 'PAYSLIP_VIEW_SUCCESS', 'EID 7 viewed PID 22 for EID 1.', '2025-12-04 05:04:51'),
(527, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 22 for EID 1.', '2025-12-04 05:04:52'),
(528, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 22 for EID 1.', '2025-12-04 05:05:05'),
(529, 7, 'PAYSLIP_DOWNLOAD_SUCCESS', 'EID 7 downloaded/viewed PID 22 for EID 1.', '2025-12-04 05:08:10'),
(530, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=attendance_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:09:44'),
(531, 7, 'REPORT_GENERATED', 'Generated Payroll Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=payroll_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:11:15'),
(532, 7, 'REPORT_GENERATED', 'Generated Leave Balance Report (Current). Filters: Type=leave_balance, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:11:32'),
(533, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=attendance_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:11:36'),
(534, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=attendance_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:12:02'),
(535, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=attendance_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:25:54'),
(536, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=attendance_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:27:20'),
(537, 2, 'LOGIN_SUCCESS', 'User janedoe (Employee) successfully logged in.', '2025-12-04 05:33:16'),
(538, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=attendance_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:35:20'),
(539, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 15, 2025 - Nov 30, 2025). Filters: Type=attendance_summary, Start=2025-11-15, End=2025-11-30, Dept=all', '2025-12-04 05:36:07'),
(540, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 15, 2025 - Nov 30, 2025). Filters: Type=attendance_summary, Start=2025-11-15, End=2025-11-30, Dept=all', '2025-12-04 05:36:24'),
(541, 7, 'REPORT_GENERATED', 'Generated Attendance Summary (Nov 3, 2025 - Dec 3, 2025). Filters: Type=attendance_summary, Start=2025-11-03, End=2025-12-03, Dept=all', '2025-12-04 05:42:34'),
(542, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-12-04 06:37:27'),
(543, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 06:37:28'),
(544, 7, 'LOGIN_SUCCESS', 'User rricamata (Super Admin) successfully logged in.', '2025-12-04 06:41:15'),
(545, 7, 'ADMIN_ANALYTICS_VIEWED', 'Admin loaded company-wide analytics dashboard.', '2025-12-04 06:41:16');

-- --------------------------------------------------------

--
-- Table structure for table `ca_transactions`
--

CREATE TABLE `ca_transactions` (
  `transaction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `original_amount` decimal(10,2) NOT NULL COMMENT 'The original advance amount.',
  `deducted_in_payroll` tinyint(1) DEFAULT 0 COMMENT 'Whether this amount has been deducted in a payroll run',
  `payroll_id` int(11) DEFAULT NULL COMMENT 'ID of the payroll run where deduction occurred',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `pending_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'The remaining amount to be deducted.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ca_transactions`
--

INSERT INTO `ca_transactions` (`transaction_id`, `employee_id`, `transaction_date`, `original_amount`, `deducted_in_payroll`, `payroll_id`, `created_at`, `deleted_at`, `pending_amount`) VALUES
(1, 4, '2025-10-01', 500.00, 0, NULL, '2025-10-29 04:02:53', NULL, 500.00),
(6, 7, '2025-10-16', 1000.00, 0, NULL, '2025-10-29 04:41:13', '2025-10-29 04:41:47', 1000.00),
(7, 7, '2025-10-03', 1000.00, 1, 21, '2025-10-29 04:41:57', NULL, 0.00),
(8, 2, '2025-12-03', 500.00, 0, NULL, '2025-12-03 20:54:40', NULL, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `default_timezone` varchar(100) NOT NULL DEFAULT 'UTC',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`company_id`, `company_name`, `address`, `contact_email`, `default_timezone`, `created_at`) VALUES
(1, 'Default Company', '123 Main St', 'admin@default.com', 'Asia/Manila', '2025-11-03 03:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `dedicated_off_days`
--

CREATE TABLE `dedicated_off_days` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `day_of_week` enum('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
  `effective_date` date NOT NULL COMMENT 'The date this off-day rule starts applying',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dedicated_off_days`
--

INSERT INTO `dedicated_off_days` (`id`, `employee_id`, `day_of_week`, `effective_date`, `reason`, `created_at`) VALUES
(1, 7, 'Sun', '2025-11-28', '', '2025-11-28 06:39:07'),
(2, 7, 'Mon', '2025-11-29', '', '2025-11-29 04:57:32'),
(5, 1, 'Sat', '2025-12-04', 'Standard Schedule Default', '2025-12-03 20:03:06'),
(6, 1, 'Sun', '2025-12-04', 'Standard Schedule Default', '2025-12-03 20:03:06'),
(15, 9, 'Sat', '2025-12-04', 'Standard Schedule Default', '2025-12-03 20:11:05'),
(16, 9, 'Sun', '2025-12-04', 'Standard Schedule Default', '2025-12-03 20:11:05');

-- --------------------------------------------------------

--
-- Table structure for table `deduction_types`
--

CREATE TABLE `deduction_types` (
  `deduction_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Fixed','Percentage') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deduction_types`
--

INSERT INTO `deduction_types` (`deduction_id`, `employee_id`, `name`, `type`, `value`, `is_active`, `created_at`) VALUES
(1, NULL, 'SSS', 'Percentage', 5.00, 1, '2025-10-29 04:02:53'),
(2, NULL, 'Medical Insurance', 'Fixed', 100.00, 1, '2025-10-29 04:02:53'),
(3, NULL, 'Taxes', 'Percentage', 15.00, 1, '2025-10-29 04:02:53'),
(6, 7, 'SSS', 'Percentage', 1.00, 1, '2025-12-03 21:03:42');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `manager_id`, `created_at`) VALUES
(103, 'Beyond Wound Care', NULL, '2025-10-25 10:17:18'),
(104, 'MDOS', NULL, '2025-10-28 15:47:39');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pay_rate` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `first_name`, `last_name`, `email`, `phone`, `profile_picture_url`, `job_title`, `department`, `created_at`, `pay_rate`) VALUES
(1, 'Super', 'Admin', 'admin@company.com', NULL, NULL, 'CEO', 'Beyond Wound Care', '2025-10-25 10:17:18', 539.00),
(2, 'Jane', 'Doe', 'jane.doe@company.com', '09123456789', 'uploads/profiles/2_1764795360.jpg', 'Software Engineer', 'MDOS', '2025-10-25 10:17:18', 1015.00),
(3, 'Mark', 'Ruffalo', 'mark.r@company.com', NULL, NULL, 'HR Manager', 'Human Resources', '2025-10-25 10:17:18', 1112.00),
(4, 'Peter', 'Parker', 'peter.p@company.com', NULL, NULL, 'Marketing Specialist', NULL, '2025-10-25 10:17:18', 883.00),
(5, 'Tony', 'Stark', 'tony.s@company.com', NULL, NULL, 'Senior Developer', NULL, '2025-10-25 10:17:18', 1053.00),
(7, 'Raymart', 'Ricamata', 'raymartcabreraricamata@gmail.com', '', 'uploads/profiles/7_1761964613.png', 'Operation Manager', 'Beyond Wound Care, MDOS', '2025-10-28 16:11:06', 729.00),
(9, 'Richard', 'Ariola', 'infiniteradio@gmail.com', '09123456789', 'uploads/profiles/9_1761690728.png', 'Station Manager', 'Beyond Wound Care', '2025-10-28 22:30:52', 850.00);

-- --------------------------------------------------------

--
-- Table structure for table `employee_journal`
--

CREATE TABLE `employee_journal` (
  `journal_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL COMMENT 'The employee this entry is about',
  `logged_by_id` int(11) NOT NULL COMMENT 'The manager/admin who logged the entry',
  `entry_type` enum('Positive','Coaching','Warning') NOT NULL,
  `entry_date` date NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_pay_history`
--

CREATE TABLE `employee_pay_history` (
  `history_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_type` enum('Hourly','Daily','Fix Rate') NOT NULL,
  `pay_rate` decimal(10,2) NOT NULL,
  `effective_start_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_pay_history`
--

INSERT INTO `employee_pay_history` (`history_id`, `employee_id`, `pay_type`, `pay_rate`, `effective_start_date`, `created_at`) VALUES
(1, 2, 'Hourly', 50.00, '2025-01-01', '2025-10-29 04:05:32'),
(2, 3, 'Fix Rate', 7500.00, '2025-01-01', '2025-10-29 04:05:32'),
(3, 7, 'Daily', 961.53, '2025-01-01', '2025-10-29 04:05:32'),
(4, 1, 'Hourly', 20.00, '2025-11-03', '2025-11-03 17:53:04');

-- --------------------------------------------------------

--
-- Table structure for table `global_settings`
--

CREATE TABLE `global_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `global_settings`
--

INSERT INTO `global_settings` (`setting_key`, `setting_value`) VALUES
('allow_manual_attendance_edit', '0'),
('company_name', 'EMC'),
('currency_symbol', '₱'),
('system_ca_deduction_name', 'Cash Advance'),
('timezone', 'America/Chicago');

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `employee_id` int(11) NOT NULL,
  `vacation_days_accrued` decimal(5,2) DEFAULT 0.00 COMMENT 'Annual policy in days',
  `sick_days_accrued` decimal(5,2) DEFAULT 0.00 COMMENT 'Annual policy in days',
  `personal_days_accrued` decimal(5,2) DEFAULT 0.00 COMMENT 'Annual policy in days',
  `vacation_days_used` decimal(5,2) DEFAULT 0.00,
  `sick_days_used` decimal(5,2) DEFAULT 0.00,
  `personal_days_used` decimal(5,2) DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `request_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `payroll_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `attendance_deductions` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processed','Paid') DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `pay_type_used` varchar(20) DEFAULT NULL,
  `pay_rate_used` decimal(10,2) DEFAULT 0.00,
  `total_payable_hours` decimal(10,2) DEFAULT 0.00,
  `total_paid_leave_days` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`payroll_id`, `employee_id`, `pay_period_start`, `pay_period_end`, `gross_pay`, `attendance_deductions`, `deductions`, `net_pay`, `status`, `created_at`, `pay_type_used`, `pay_rate_used`, `total_payable_hours`, `total_paid_leave_days`) VALUES
(19, 2, '2025-09-26', '2025-10-10', 3990.50, 0.00, 898.11, 3092.39, 'Processed', '2025-10-29 17:16:18', 'Hourly', 50.00, 79.81, 0),
(20, 3, '2025-09-26', '2025-10-10', 7500.00, 0.00, 1600.00, 5900.00, 'Processed', '2025-10-29 17:16:18', 'Fix Rate', 7500.00, 0.00, 0),
(21, 7, '2025-09-26', '2025-10-10', 9603.28, 0.00, 3020.65, 6582.63, 'Processed', '2025-10-29 17:16:18', 'Daily', 961.53, 79.90, 0),
(22, 1, '2025-11-15', '2025-12-03', 1600.00, 480.00, 420.00, 1180.00, 'Processed', '2025-12-04 04:42:37', 'Hourly', 20.00, 80.00, 0),
(23, 2, '2025-11-15', '2025-12-03', 800.00, 0.00, 260.00, 540.00, 'Processed', '2025-12-04 04:42:37', 'Hourly', 50.00, 16.00, 0),
(24, 3, '2025-11-15', '2025-12-03', 7500.00, 0.00, 1600.00, 5900.00, 'Processed', '2025-12-04 04:42:37', 'Fix Rate', 7500.00, 0.00, 0),
(25, 7, '2025-11-15', '2025-12-03', 9615.30, 2884.59, 2023.07, 7592.23, 'Processed', '2025-12-04 04:42:37', 'Daily', 961.53, 80.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `shift_start` time NOT NULL,
  `shift_end` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `standard_schedules`
--

CREATE TABLE `standard_schedules` (
  `standard_schedule_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `mon_start` time DEFAULT NULL,
  `mon_end` time DEFAULT NULL,
  `tue_start` time DEFAULT NULL,
  `tue_end` time DEFAULT NULL,
  `wed_start` time DEFAULT NULL,
  `wed_end` time DEFAULT NULL,
  `thu_start` time DEFAULT NULL,
  `thu_end` time DEFAULT NULL,
  `fri_start` time DEFAULT NULL,
  `fri_end` time DEFAULT NULL,
  `sat_start` time DEFAULT NULL,
  `sat_end` time DEFAULT NULL,
  `sun_start` time DEFAULT NULL,
  `sun_end` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `standard_schedules`
--

INSERT INTO `standard_schedules` (`standard_schedule_id`, `employee_id`, `mon_start`, `mon_end`, `tue_start`, `tue_end`, `wed_start`, `wed_end`, `thu_start`, `thu_end`, `fri_start`, `fri_end`, `sat_start`, `sat_end`, `sun_start`, `sun_end`) VALUES
(1, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '09:00:00', '17:00:00', NULL, NULL, NULL, NULL),
(2, 7, '09:00:00', '17:00:00', '09:00:00', '17:00:00', '09:00:00', '17:00:00', '09:00:00', '17:00:00', '09:00:00', '17:00:00', NULL, NULL, NULL, NULL),
(3, 1, '08:00:00', '16:00:00', '08:00:00', '16:00:00', '08:00:00', '16:00:00', '08:00:00', '16:00:00', '08:00:00', '16:00:00', NULL, NULL, NULL, NULL),
(10, 9, '09:00:00', '18:00:00', '09:00:00', '18:00:00', '09:00:00', '18:00:00', '09:00:00', '18:00:00', '09:00:00', '18:00:00', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `time_attendance`
--

CREATE TABLE `time_attendance` (
  `log_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Employee','Manager','HR Admin','Super Admin') NOT NULL,
  `employee_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role`, `employee_id`, `company_id`) VALUES
(6, 'superadmin', '$2y$10$0wFBB1e9jUrNBgzklc3HG.ZFTx3nCjNPL8rV9upw7YMIfxNbRnI8e', 'Super Admin', 1, 1),
(7, 'janedoe', '$2y$10$UcfpXzhVWIEuCH32ASSIM.WX2Dcn4Efd/ZGop.r9JeyLjxk4l1ktG', 'Employee', 2, 1),
(8, 'markruffalo', '$2y$10$0wFBB1e9jUrNBgzklc3HG.ZFTx3nCjNPL8rV9upw7YMIfxNbRnI8e', 'HR Admin', 3, 1),
(9, 'peterparker', '$2y$10$0wFBB1e9jUrNBgzklc3HG.ZFTx3nCjNPL8rV9upw7YMIfxNbRnI8e', 'Manager', 4, 1),
(10, 'tonystark', '$2y$10$0wFBB1e9jUrNBgzklc3HG.ZFTx3nCjNPL8rV9upw7YMIfxNbRnI8e', 'Employee', 5, 1),
(12, 'rricamata', '$2y$10$Bw1.fRAbiJ2Pi03vGnWHa.8Z2F075fnMN6wYSDYiv/fk8Iq3bgnAi', 'Super Admin', 7, 1),
(13, 'rariola', '$2y$10$FwdaIAy3zeIXil4VKWlYbe68hUYfMSNRWxt2/KZzMcJN888RMag/q', 'Employee', 9, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by_id` (`created_by_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_log_employee_id` (`employee_id`),
  ADD KEY `idx_log_action` (`action`),
  ADD KEY `idx_log_timestamp` (`log_timestamp`);

--
-- Indexes for table `ca_transactions`
--
ALTER TABLE `ca_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `dedicated_off_days`
--
ALTER TABLE `dedicated_off_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `deduction_types`
--
ALTER TABLE `deduction_types`
  ADD PRIMARY KEY (`deduction_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `employee_journal`
--
ALTER TABLE `employee_journal`
  ADD PRIMARY KEY (`journal_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `logged_by_id` (`logged_by_id`);

--
-- Indexes for table `employee_pay_history`
--
ALTER TABLE `employee_pay_history`
  ADD PRIMARY KEY (`history_id`),
  ADD UNIQUE KEY `unique_employee_date` (`employee_id`,`effective_start_date`);

--
-- Indexes for table `global_settings`
--
ALTER TABLE `global_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `manager_id` (`manager_id`),
  ADD KEY `idx_leave_status` (`status`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`payroll_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `unique_employee_day` (`employee_id`,`work_date`);

--
-- Indexes for table `standard_schedules`
--
ALTER TABLE `standard_schedules`
  ADD PRIMARY KEY (`standard_schedule_id`),
  ADD UNIQUE KEY `unique_employee` (`employee_id`);

--
-- Indexes for table `time_attendance`
--
ALTER TABLE `time_attendance`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_users_company` (`company_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=546;

--
-- AUTO_INCREMENT for table `ca_transactions`
--
ALTER TABLE `ca_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dedicated_off_days`
--
ALTER TABLE `dedicated_off_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `deduction_types`
--
ALTER TABLE `deduction_types`
  MODIFY `deduction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `employee_journal`
--
ALTER TABLE `employee_journal`
  MODIFY `journal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_pay_history`
--
ALTER TABLE `employee_pay_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `payroll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `standard_schedules`
--
ALTER TABLE `standard_schedules`
  MODIFY `standard_schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `time_attendance`
--
ALTER TABLE `time_attendance`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcement_author` FOREIGN KEY (`created_by_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `ca_transactions`
--
ALTER TABLE `ca_transactions`
  ADD CONSTRAINT `ca_transactions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `dedicated_off_days`
--
ALTER TABLE `dedicated_off_days`
  ADD CONSTRAINT `fk_off_day_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_journal`
--
ALTER TABLE `employee_journal`
  ADD CONSTRAINT `employee_journal_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_journal_ibfk_2` FOREIGN KEY (`logged_by_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `employee_pay_history`
--
ALTER TABLE `employee_pay_history`
  ADD CONSTRAINT `employee_pay_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `standard_schedules`
--
ALTER TABLE `standard_schedules`
  ADD CONSTRAINT `fk_employee_schedule` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `time_attendance`
--
ALTER TABLE `time_attendance`
  ADD CONSTRAINT `time_attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
