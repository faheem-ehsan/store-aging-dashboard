-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 26, 2026 at 03:50 PM
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
-- Database: `store_aging`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(120) DEFAULT NULL,
  `actor_email` varchar(190) DEFAULT NULL,
  `actor_role` varchar(40) DEFAULT NULL,
  `division_id` int(10) UNSIGNED DEFAULT NULL,
  `division_code` varchar(20) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(80) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_json`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_user_id`, `actor_name`, `actor_email`, `actor_role`, `division_id`, `division_code`, `action`, `entity_type`, `entity_id`, `description`, `details_json`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:22:53'),
(2, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:23:49'),
(3, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:24:17'),
(4, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:24:27'),
(5, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:40:22'),
(6, 3, 'Bilal', 'bilal.tariq@qarshi.com', 'division_admin', 1, 'JSD', 'LOGIN_SUCCESS', 'auth', '3', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:40:32'),
(7, 3, 'Bilal', 'bilal.tariq@qarshi.com', 'division_admin', 1, 'JSD', 'LOGOUT', 'auth', '3', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:43:33'),
(8, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:43:38'),
(9, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'DIVISION_CREATE', 'division', '4', 'Created division FPD — Farm Product Division.', '{\"code\":\"FPD\",\"name\":\"Farm Product Division\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 09:54:27'),
(10, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 1, 'JSD', 'BATCH_UPDATE', 'stock_batch', '4', 'Updated batch JS220726A for material 100396.', '{\"before\":{\"division\":\"JSD\",\"material_no\":\"100396\",\"material_description\":\"JAM-E-SHIRIN 3000ML\",\"batch_no\":\"JS220726A\",\"current_qty\":1958,\"stock_value\":0,\"manufacture_date\":\"2026-07-22\",\"expiry_date\":\"2030-07-21\",\"receiving_store\":null},\"after\":{\"division\":\"JSD\",\"material_no\":\"100396\",\"material_description\":\"JAM-E-SHIRIN 3000ML\",\"batch_no\":\"JS220726A\",\"current_qty\":1958,\"stock_value\":125000,\"manufacture_date\":\"2026-07-22\",\"expiry_date\":\"2030-07-21\",\"receiving_store\":\"Main\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 10:24:16'),
(11, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 5, 'NMD', 'DIVISION_CREATE', 'division', '5', 'Created division NMD — Natural Medicine Division.', '{\"code\":\"NMD\",\"name\":\"Natural Medicine Division\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 12:30:45'),
(12, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'GRADE_CRITERIA_UPDATE', 'grading_criteria', NULL, 'Updated division day-based grading criteria.', '{\"before\":[{\"division\":\"FPD\",\"a_plus_days\":30,\"a_days\":60,\"b_days\":90,\"c_days\":120},{\"division\":\"JSD\",\"a_plus_days\":30,\"a_days\":60,\"b_days\":90,\"c_days\":120},{\"division\":\"NMD\",\"a_plus_days\":30,\"a_days\":60,\"b_days\":90,\"c_days\":120},{\"division\":\"RTD\",\"a_plus_days\":30,\"a_days\":60,\"b_days\":90,\"c_days\":120},{\"division\":\"SNW\",\"a_plus_days\":30,\"a_days\":60,\"b_days\":90,\"c_days\":120}],\"after\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 13:06:59'),
(13, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'GRADE_CRITERIA_UPDATE', 'grading_criteria', NULL, 'Updated division day-based grading criteria.', '{\"before\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}],\"after\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 13:09:03');

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`id`, `code`, `name`, `is_active`, `created_at`) VALUES
(1, 'JSD', 'Jam-e-Shirin', 1, '2026-09-25 15:55:31'),
(2, 'SNW', 'Springly Natural Water', 1, '2026-09-25 15:55:31'),
(3, 'RTD', 'Ready To Drink', 1, '2026-09-25 15:55:31'),
(4, 'FPD', 'Farm Product Division', 1, '2026-09-26 09:54:27'),
(5, 'NMD', 'Natural Medicine Division', 1, '2026-09-26 12:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `division_grade_settings`
--

CREATE TABLE `division_grade_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED NOT NULL,
  `a_plus_months` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `a_months` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `b_months` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `a_plus_days` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `a_days` int(10) UNSIGNED NOT NULL DEFAULT 60,
  `b_days` int(10) UNSIGNED NOT NULL DEFAULT 90,
  `c_days` int(10) UNSIGNED NOT NULL DEFAULT 120,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `division_grade_settings`
--

INSERT INTO `division_grade_settings` (`id`, `division_id`, `a_plus_months`, `a_months`, `b_months`, `a_plus_days`, `a_days`, `b_days`, `c_days`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 2, 3, 1, 15, 30, 45, '2026-09-26 11:08:07', '2026-09-26 13:06:59'),
(2, 1, 1, 2, 3, 1, 15, 30, 45, '2026-09-26 11:08:07', '2026-09-26 13:06:59'),
(3, 3, 1, 2, 3, 1, 15, 30, 45, '2026-09-26 11:08:07', '2026-09-26 13:06:59'),
(4, 2, 1, 2, 3, 1, 15, 30, 45, '2026-09-26 11:08:07', '2026-09-26 13:06:59'),
(9, 5, 1, 2, 3, 1, 15, 30, 45, '2026-09-26 13:04:50', '2026-09-26 13:06:59');

-- --------------------------------------------------------

--
-- Table structure for table `grade_rules`
--

CREATE TABLE `grade_rules` (
  `id` int(10) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED DEFAULT NULL,
  `grade_code` varchar(10) NOT NULL,
  `grade_name` varchar(50) NOT NULL,
  `min_days` int(11) DEFAULT NULL,
  `max_days` int(11) DEFAULT NULL,
  `color_class` varchar(20) NOT NULL DEFAULT 'gray',
  `sort_order` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grade_rules`
--

INSERT INTO `grade_rules` (`id`, `division_id`, `grade_code`, `grade_name`, `min_days`, `max_days`, `color_class`, `sort_order`) VALUES
(1, NULL, 'EX', 'Expired', NULL, -1, 'black', 5),
(2, NULL, 'D', 'Critical', 0, 30, 'red', 2),
(3, NULL, 'C', 'Priority', 31, 90, 'amber', 3),
(4, NULL, 'B', 'Watch', 91, 180, 'yellow', 3),
(5, NULL, 'A', 'Safe', 181, NULL, 'green', 2);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED NOT NULL,
  `material_code` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `division_id`, `material_code`, `description`, `is_active`, `created_at`) VALUES
(1, 1, '100394', 'JAM-E-SHIRIN 800ML', 1, '2026-09-25 15:55:31'),
(2, 1, '100396', 'JAM-E-SHIRIN 3000ML', 1, '2026-09-25 15:55:31'),
(3, 1, '100397', 'JAM-E-SHIRIN 500ML', 1, '2026-09-25 15:55:31'),
(4, 3, '120000200', '10000200', 1, '2026-09-25 18:21:20'),
(5, 2, '100409', 'SPRINGLEY 500 ML (1*12)', 1, '2026-09-26 06:26:39'),
(6, 2, '100411', 'SPRINGLEY 1500 ML (1*6)', 1, '2026-09-26 06:26:39'),
(7, 2, '100684', 'SPRINGLEY BOTTLE 19LTR', 1, '2026-09-26 06:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `stock_batches`
--

CREATE TABLE `stock_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED NOT NULL,
  `material_id` bigint(20) UNSIGNED NOT NULL,
  `batch_no` varchar(80) NOT NULL,
  `manufacture_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `last_receipt_date` date DEFAULT NULL,
  `current_qty` decimal(18,3) NOT NULL DEFAULT 0.000,
  `stock_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `uom` varchar(20) NOT NULL DEFAULT 'PCS',
  `receiving_store` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_batches`
--

INSERT INTO `stock_batches` (`id`, `division_id`, `material_id`, `batch_no`, `manufacture_date`, `expiry_date`, `last_receipt_date`, `current_qty`, `stock_value`, `uom`, `receiving_store`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'JS020926A', '2026-09-02', '2030-09-01', '2026-09-11', 2150.000, 0.00, 'PCS', NULL, '2026-09-25 15:55:31', '2026-09-25 15:55:31'),
(2, 1, 1, 'JS020926B', '2026-09-02', '2030-09-01', '2026-09-12', 2732.000, 0.00, 'PCS', NULL, '2026-09-25 15:55:31', '2026-09-25 15:55:31'),
(3, 1, 2, 'JS210826B', '2026-08-21', '2030-08-20', '2026-08-29', 1849.000, 0.00, 'PCS', NULL, '2026-09-25 15:55:31', '2026-09-25 15:55:31'),
(4, 1, 2, 'JS220726A', '2026-07-22', '2030-07-21', NULL, 1958.000, 125000.00, 'PCS', 'Main', '2026-09-25 15:55:31', '2026-09-26 10:24:16'),
(5, 1, 3, 'JS200726B', '2025-06-11', '2026-09-24', '2026-08-12', 2380.000, 0.00, 'PCS', NULL, '2026-09-25 15:55:31', '2026-09-26 06:08:13'),
(6, 1, 3, 'JS220726B', '2026-07-22', '2030-07-21', '2026-08-19', 962.000, 0.00, 'PCS', NULL, '2026-09-25 15:55:31', '2026-09-25 15:55:31'),
(7, 3, 4, '4560021', '2026-09-01', '2026-09-21', '2026-09-10', 50.000, 0.00, 'PCS', NULL, '2026-09-25 18:21:20', '2026-09-25 18:21:20'),
(8, 2, 5, 'SP180926', '2026-09-18', '2027-09-18', NULL, 2085.000, 5000.00, 'PCS', 'FW02', '2026-09-26 06:26:39', '2026-09-26 06:28:18'),
(9, 2, 6, 'SP090926', '2026-09-09', '2027-09-09', NULL, 3230.000, 50000.00, 'PCS', 'FW02', '2026-09-26 06:26:39', '2026-09-26 06:28:18'),
(10, 2, 7, 'SB220926', '2026-09-22', '2027-09-22', NULL, 220.000, 50000.00, 'PCS', 'FW02', '2026-09-26 06:26:39', '2026-09-26 06:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED NOT NULL,
  `movement_type` enum('receipt','issue','adjustment') NOT NULL,
  `qty` decimal(18,3) NOT NULL,
  `movement_date` datetime NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','division_admin','user') NOT NULL DEFAULT 'user',
  `avatar_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `division_id`, `name`, `email`, `password_hash`, `role`, `avatar_path`, `is_active`, `created_at`) VALUES
(1, NULL, 'Faheem Ehsan', 'admin@example.com', '$2y$10$1dljE91ZXHocVZFS.r1DV.53YHNGME.9So0CMti6lQVLwyFJh8muC', 'super_admin', '/uploads/users/172a96848f82119727da29ba32f62996.png', 1, '2026-09-25 15:55:31'),
(2, 3, 'Kinza', 'kinza.waheed@qarshi.com', '$2y$10$S.aojmVhQxugkLVgrjXNZ.OtRMIkTu.PX9yRg3en4r5mI5ZcSQkIW', 'division_admin', NULL, 1, '2026-09-25 17:57:44'),
(3, 1, 'Bilal', 'bilal.tariq@qarshi.com', '$2y$10$40hHd1mKKFkn1h5zL87spOjggoSEu48cOQ6aG13VvfndsSQEg7Lzu', 'division_admin', NULL, 1, '2026-09-26 05:21:19'),
(4, 2, 'Zubair', 'zubair@qarshi.com', '$2y$10$iPoaU.PZ4ELmFx0KiI0CgO5/rolOGxyUzsUShBybylXDF8SpUs.ZO', 'division_admin', NULL, 1, '2026-09-26 05:57:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_created` (`created_at`),
  ADD KEY `idx_audit_action` (`action`,`created_at`),
  ADD KEY `idx_audit_actor` (`actor_user_id`,`created_at`),
  ADD KEY `idx_audit_division` (`division_id`,`created_at`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `division_grade_settings`
--
ALTER TABLE `division_grade_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grade_settings_division` (`division_id`);

--
-- Indexes for table `grade_rules`
--
ALTER TABLE `grade_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_grade_division` (`division_id`,`sort_order`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_material_division_code` (`division_id`,`material_code`),
  ADD KEY `idx_material_description` (`description`);

--
-- Indexes for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_batch_per_material` (`division_id`,`material_id`,`batch_no`),
  ADD KEY `fk_batch_material` (`material_id`),
  ADD KEY `idx_batch_expiry` (`division_id`,`expiry_date`),
  ADD KEY `idx_batch_number` (`batch_no`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_move_user` (`created_by`),
  ADD KEY `idx_move_batch_date` (`batch_id`,`movement_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_division` (`division_id`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `division_grade_settings`
--
ALTER TABLE `division_grade_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `grade_rules`
--
ALTER TABLE `grade_rules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stock_batches`
--
ALTER TABLE `stock_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `division_grade_settings`
--
ALTER TABLE `division_grade_settings`
  ADD CONSTRAINT `fk_grade_settings_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grade_rules`
--
ALTER TABLE `grade_rules`
  ADD CONSTRAINT `fk_grade_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `fk_material_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`);

--
-- Constraints for table `stock_batches`
--
ALTER TABLE `stock_batches`
  ADD CONSTRAINT `fk_batch_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  ADD CONSTRAINT `fk_batch_material` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_move_batch` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_move_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
