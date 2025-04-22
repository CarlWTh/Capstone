-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 22, 2025 at 04:18 AM
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
-- Table structure for table `credit_rates`
--

CREATE TABLE `credit_rates` (
  `id` int(11) NOT NULL,
  `bottle_type` varchar(50) NOT NULL,
  `credits_per_unit` int(11) NOT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credit_rates`
--

INSERT INTO `credit_rates` (`id`, `bottle_type`, `credits_per_unit`, `last_updated`) VALUES
(1, 'standard', 5, '2025-04-22 02:14:04');

-- --------------------------------------------------------

--
-- Table structure for table `device_status`
--

CREATE TABLE `device_status` (
  `id` int(11) NOT NULL,
  `device_name` varchar(50) NOT NULL,
  `status` enum('OPERATIONAL','DEGRADED','OFFLINE','MAINTENANCE') NOT NULL,
  `last_checked` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device_status`
--

INSERT INTO `device_status` (`id`, `device_name`, `status`, `last_checked`, `details`) VALUES
(1, 'Orange Pi', 'OPERATIONAL', '2025-04-22 01:37:27', NULL),
(2, 'GSM Module', 'OPERATIONAL', '2025-04-22 01:37:27', NULL),
(3, 'Wi-Fi Router', 'OPERATIONAL', '2025-04-22 01:37:27', NULL),
(4, 'Main Server', 'OPERATIONAL', '2025-04-22 01:37:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `period` varchar(50) NOT NULL,
  `generated_on` timestamp NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_alerts`
--

CREATE TABLE `sms_alerts` (
  `id` int(11) NOT NULL,
  `admin_phone` varchar(20) NOT NULL,
  `bin_full_alerts` tinyint(1) DEFAULT 1,
  `system_error_alerts` tinyint(1) DEFAULT 1,
  `daily_summary` tinyint(1) DEFAULT 1,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_alerts`
--

INSERT INTO `sms_alerts` (`id`, `admin_phone`, `bin_full_alerts`, `system_error_alerts`, `daily_summary`, `last_updated`) VALUES
(1, '+639098043045', 1, 1, 1, '2025-04-22 02:15:33');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `event` varchar(255) NOT NULL,
  `severity` enum('INFO','WARNING','ERROR','CRITICAL') NOT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `timestamp`, `event`, `severity`, `details`) VALUES
(1, '2025-04-22 01:37:27', 'System Startup', 'INFO', 'System booted successfully'),
(2, '2025-04-22 01:37:27', 'Database Backup Completed', 'INFO', 'Daily backup completed without errors'),
(3, '2025-04-22 01:37:27', 'Routine Health Check', 'INFO', 'All systems operational'),
(4, '2025-04-22 01:37:27', 'GSM Module Reconnected', 'WARNING', 'Temporary connection loss detected and resolved');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_name`, `setting_value`, `last_updated`) VALUES
(1, 'backup_frequency', 'daily', '2025-04-22 01:37:27'),
(2, 'last_backup', '2024-03-27 10:15:00', '2025-04-22 01:37:27'),
(3, 'auto_backup', '1', '2025-04-22 01:37:27');

-- --------------------------------------------------------

--
-- Table structure for table `system_status`
--

CREATE TABLE `system_status` (
  `id` int(11) NOT NULL,
  `device_name` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_status`
--

INSERT INTO `system_status` (`id`, `device_name`, `status`, `last_updated`) VALUES
(1, 'Orange Pi', 'Operational', '2025-04-22 01:19:52'),
(2, 'GSM Module', 'Connected', '2025-04-22 01:19:52'),
(3, 'Wi-Fi Router', 'Active', '2025-04-22 01:19:52');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bottle_count` int(11) NOT NULL,
  `credits_earned` int(11) NOT NULL,
  `transaction_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password_hash`, `created_at`, `is_admin`) VALUES
(1, 'SuperAdmin', 'admin@gmail.com', '09098043045', '$2y$10$hkU5l70yNu.OCBjrcaE8bebJWcANCSVHEUdchl110GU5GUj.Pd4la', '2025-04-22 01:25:17', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `credit_rates`
--
ALTER TABLE `credit_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `device_status`
--
ALTER TABLE `device_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_alerts`
--
ALTER TABLE `sms_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `system_status`
--
ALTER TABLE `system_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `credit_rates`
--
ALTER TABLE `credit_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `device_status`
--
ALTER TABLE `device_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_alerts`
--
ALTER TABLE `sms_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_status`
--
ALTER TABLE `system_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
