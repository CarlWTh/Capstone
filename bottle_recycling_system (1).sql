-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2025 at 03:56 AM
-- Server version: 11.6.2-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bottle_recycling_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `email`, `password_hash`, `is_admin`, `reset_token`, `reset_token_expires`, `created_at`) VALUES
(1, 'admin', 'justinejohnorosco.basc@gmail.com', '$2y$10$F1ozJFJDgqN/QDinZJEzse5cTz0gyN0xIh5xifarpyHrIh75ovsl.', 1, NULL, NULL, '2025-08-15 01:55:05');

-- --------------------------------------------------------

--
-- Table structure for table `bandwidth_usage`
--

CREATE TABLE `bandwidth_usage` (
  `user_id` int(11) NOT NULL,
  `Device_MAC_Address` varchar(17) NOT NULL,
  `Download` bigint(20) DEFAULT 0,
  `Upload` bigint(20) DEFAULT 0,
  `Total` bigint(20) DEFAULT 0,
  `Duration` int(11) DEFAULT 0,
  `last_updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bandwidth_usage`
--

INSERT INTO `bandwidth_usage` (`user_id`, `Device_MAC_Address`, `Download`, `Upload`, `Total`, `Duration`, `last_updated_at`) VALUES
(1, '00:1A:2B:3C:4D:5E', 125000000, 25000000, 150000000, 7200, '2025-07-24 01:19:55'),
(2, 'AA:BB:CC:DD:EE:FF', 75000000, 25000000, 100000000, 5400, '2025-07-24 01:19:55'),
(19, '11:22:33:44:55:66', 50000000, 10000000, 60000000, 3600, '2025-07-24 01:19:55'),
(20, '99:88:77:66:55:44', 200000000, 50000000, 250000000, 14400, '2025-07-24 01:19:55');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `settings_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `minutes_per_bottle` decimal(5,2) NOT NULL,
  `bandwidth_limit_kbps` int(11) NOT NULL,
  `bin_full_threshold` int(11) NOT NULL,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `auto_reboot_schedule` varchar(255) DEFAULT NULL,
  `setting_key` varchar(255) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `systemlog`
--

CREATE TABLE `systemlog` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `systemlog`
--

INSERT INTO `systemlog` (`log_id`, `admin_id`, `action`, `details`, `timestamp`) VALUES
(1, 1, 'Dashboard Access', 'Accessed admin dashboard', '2025-08-15 01:55:27'),
(2, 1, 'Bottle Deposits Access', 'Viewed bottle deposits list', '2025-08-15 01:55:32'),
(3, 1, 'Settings Access', 'Accessed settings page', '2025-08-15 01:55:34');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bottle_count` int(11) NOT NULL,
  `time_credits_earned` decimal(10,0) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `user_id`, `bottle_count`, `time_credits_earned`, `created_at`) VALUES
(1, 1, 10, 5, '2025-07-20 08:11:22'),
(2, 2, 20, 10, '2025-07-20 08:11:22'),
(3, 1, 5, 2, '2025-07-20 08:11:22'),
(5, 1, 2, 12, '2025-07-22 05:49:10'),
(6, 1, 2, 12, '2025-07-23 05:04:31'),
(7, 1, 1, 6, '2025-07-23 05:06:27'),
(8, 1, 2, 12, '2025-07-23 05:06:37'),
(9, 1, 1, 6, '2025-07-23 05:31:14'),
(10, 1, 2, 12, '2025-07-23 05:31:33'),
(11, 1, 3, 18, '2025-07-23 05:34:27'),
(19, 1, 2, 12, '2025-07-23 05:51:45'),
(20, 1, 5, 30, '2025-07-23 05:52:03'),
(21, 1, 1, 6, '2025-07-23 08:08:10'),
(22, 1, 1, 6, '2025-07-23 08:12:04'),
(23, 1, 1, 6, '2025-07-23 08:14:26'),
(24, 1, 1, 6, '2025-07-23 08:15:29'),
(25, 1, 1, 6, '2025-07-23 08:16:21'),
(26, 1, 1, 2, '2025-07-23 08:19:10');

-- --------------------------------------------------------

--
-- Table structure for table `trashbin`
--

CREATE TABLE `trashbin` (
  `trashbin_id` int(11) NOT NULL,
  `janitor_id` int(11) NOT NULL,
  `status` enum('empty','partial','full') NOT NULL,
  `fill_level_percent` decimal(10,2) NOT NULL,
  `capacity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_emptied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trashbin`
--

INSERT INTO `trashbin` (`trashbin_id`, `janitor_id`, `status`, `fill_level_percent`, `capacity`, `last_emptied_at`) VALUES
(8, 2, 'empty', 0.00, 10.00, '2025-07-24 07:24:44'),
(9, 2, 'empty', 0.00, 10.00, '2025-07-24 07:23:58'),
(10, 3, 'empty', 0.00, 10.00, '2025-07-24 07:24:12'),
(11, 1, 'empty', 0.00, 10.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `mac_address` varchar(17) NOT NULL,
  `time_credits` decimal(10,2) DEFAULT 0.00,
  `last_active` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `mac_address`, `time_credits`, `last_active`, `created_at`) VALUES
(1, '00:1A:2B:3C:4D:5E', 15.50, '2025-07-20 09:11:22', '2025-07-20 09:11:22'),
(2, 'AA:BB:CC:DD:EE:FF', 20.00, '2025-07-20 09:11:22', '2025-07-20 09:11:22'),
(19, '11:22:33:44:55:66', 0.00, '2025-07-23 12:15:05', '2025-06-01 04:00:00'),
(20, '99:88:77:66:55:44', 90.00, '2025-07-21 03:20:30', '2024-09-10 08:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `usersessions`
--

CREATE TABLE `usersessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `start_time` timestamp NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `duration_minutes` decimal(10,2) DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usersessions`
--

INSERT INTO `usersessions` (`session_id`, `user_id`, `ip_address`, `start_time`, `end_time`, `duration_minutes`, `voucher_id`) VALUES
(1, 1, '192.168.1.100', '2025-07-20 02:00:00', '2025-07-20 02:30:00', 30.00, NULL),
(2, 2, '10.0.0.5', '2025-07-20 03:15:00', NULL, NULL, NULL),
(3, 1, '192.168.1.101', '2025-07-20 04:00:00', '2025-07-20 04:45:00', 45.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

CREATE TABLE `voucher` (
  `voucher_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `voucher_code` varchar(255) NOT NULL,
  `expiration` timestamp NOT NULL,
  `status` enum('unused','used','expired') NOT NULL DEFAULT 'unused',
  `time_credits_value` decimal(10,0) NOT NULL,
  `redeemed_by` int(11) DEFAULT NULL,
  `redeemed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`voucher_id`, `transaction_id`, `voucher_code`, `expiration`, `status`, `time_credits_value`, `redeemed_by`, `redeemed_at`) VALUES
(1, 1, 'VOUCHER123', '0000-00-00 00:00:00', 'used', 0, 1, '2025-07-20 09:11:22'),
(2, 2, 'CODE456', '2000-01-19 16:00:00', 'unused', 0, NULL, NULL),
(3, 1, 'TEST789', '0000-00-00 00:00:00', 'expired', 0, NULL, '2025-07-15 07:00:00'),
(4, 5, '9d569d1a19', '0000-00-00 00:00:00', 'unused', 0, NULL, NULL),
(5, 5, 'a4e07155ef', '0000-00-00 00:00:00', 'unused', 0, NULL, NULL),
(6, 6, 'f701d471d1', '0000-00-00 00:00:00', 'unused', 0, NULL, NULL),
(7, 6, 'a754002023', '0000-00-00 00:00:00', 'unused', 0, NULL, NULL),
(8, 7, 'ca5c309de0', '0000-00-00 00:00:00', 'unused', 0, NULL, NULL),
(9, 8, 'af19be9f29', '0000-00-00 00:00:00', 'unused', 0, NULL, NULL),
(10, 8, 'e15ad80acd', '0000-00-00 00:00:00', 'unused', 0, NULL, NULL),
(11, 9, '04907dd187', '2025-07-23 05:37:14', 'unused', 0, NULL, NULL),
(12, 10, '03f85bdfc4', '2025-07-23 05:37:33', 'unused', 0, NULL, NULL),
(13, 10, '270f66a504', '2025-07-23 05:37:33', 'unused', 0, NULL, NULL),
(14, 11, 'ebd5e642d0', '2025-07-23 05:40:27', 'unused', 0, NULL, NULL),
(15, 11, '3c4a4a7b4d', '2025-07-23 05:40:27', 'unused', 0, NULL, NULL),
(16, 11, '66c91949f5', '2025-07-23 05:40:27', 'unused', 0, NULL, NULL),
(17, 19, '7c068c0630', '2025-07-23 06:03:45', 'unused', 12, NULL, NULL),
(18, 20, '5712923130', '2025-07-23 06:22:03', 'unused', 30, NULL, NULL),
(19, 21, '081d8d8692', '2025-07-23 08:14:10', 'unused', 6, NULL, NULL),
(20, 22, 'f1befb3878', '2025-07-23 08:18:04', 'unused', 6, NULL, NULL),
(21, 23, '897716d69f', '2025-07-23 08:20:26', 'unused', 6, NULL, NULL),
(22, 24, 'c47c75afd4', '2025-07-23 08:21:29', 'unused', 6, NULL, NULL),
(23, 25, 'c75b5d8311', '2025-07-23 08:22:21', 'unused', 6, NULL, NULL),
(24, 26, '2d8e12e770', '2025-07-23 08:21:10', 'unused', 2, NULL, NULL);

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
-- Indexes for table `bandwidth_usage`
--
ALTER TABLE `bandwidth_usage`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `Device_MAC_Address` (`Device_MAC_Address`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`settings_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `systemlog`
--
ALTER TABLE `systemlog`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trashbin`
--
ALTER TABLE `trashbin`
  ADD PRIMARY KEY (`trashbin_id`),
  ADD KEY `janitor_id` (`janitor_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `mac_address` (`mac_address`);

--
-- Indexes for table `usersessions`
--
ALTER TABLE `usersessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_user_sessions_voucher` (`voucher_id`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`voucher_id`),
  ADD UNIQUE KEY `voucher_code` (`voucher_code`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `redeemed_by` (`redeemed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `settings_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `systemlog`
--
ALTER TABLE `systemlog`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `trashbin`
--
ALTER TABLE `trashbin`
  MODIFY `trashbin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `usersessions`
--
ALTER TABLE `usersessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `voucher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bandwidth_usage`
--
ALTER TABLE `bandwidth_usage`
  ADD CONSTRAINT `bandwidth_usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `bandwidth_usage_ibfk_2` FOREIGN KEY (`Device_MAC_Address`) REFERENCES `user` (`mac_address`);

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `systemlog`
--
ALTER TABLE `systemlog`
  ADD CONSTRAINT `systemlog_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `trashbin`
--
ALTER TABLE `trashbin`
  ADD CONSTRAINT `trashbin_ibfk_1` FOREIGN KEY (`janitor_id`) REFERENCES `janitor` (`janitor_id`);

--
-- Constraints for table `usersessions`
--
ALTER TABLE `usersessions`
  ADD CONSTRAINT `fk_user_sessions_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`voucher_id`),
  ADD CONSTRAINT `usersessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `voucher`
--
ALTER TABLE `voucher`
  ADD CONSTRAINT `voucher_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`),
  ADD CONSTRAINT `voucher_ibfk_2` FOREIGN KEY (`redeemed_by`) REFERENCES `user` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
