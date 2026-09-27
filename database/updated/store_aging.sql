-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2026 at 08:22 PM
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
(13, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'GRADE_CRITERIA_UPDATE', 'grading_criteria', NULL, 'Updated division day-based grading criteria.', '{\"before\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}],\"after\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 13:09:03'),
(14, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 18:08:09'),
(15, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 18:08:19'),
(16, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 3, 'RTD', 'BATCH_DELETE', 'stock_batch', '7', 'Deleted batch 4560021 for material 120000200.', '{\"division\":\"RTD\",\"material_no\":\"120000200\",\"material_description\":\"10000200\",\"batch_no\":\"4560021\",\"current_qty\":50,\"stock_value\":0,\"manufacture_date\":\"2026-09-01\",\"expiry_date\":\"2026-09-21\",\"receiving_store\":null}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 18:13:02'),
(17, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 1, 'JSD', 'BATCH_DELETE', 'stock_batch', '5', 'Deleted batch JS200726B for material 100397.', '{\"division\":\"JSD\",\"material_no\":\"100397\",\"material_description\":\"JAM-E-SHIRIN 500ML\",\"batch_no\":\"JS200726B\",\"current_qty\":2380,\"stock_value\":0,\"manufacture_date\":\"2025-06-11\",\"expiry_date\":\"2026-09-24\",\"receiving_store\":null}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 18:13:05'),
(18, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 2, 'SNW', 'BATCH_UPDATE', 'stock_batch', '9', 'Updated batch SP090926 for material 100411.', '{\"before\":{\"division\":\"SNW\",\"material_no\":\"100411\",\"material_description\":\"SPRINGLEY 1500 ML (1*6)\",\"batch_no\":\"SP090926\",\"current_qty\":3230,\"stock_value\":50000,\"manufacture_date\":\"2026-09-09\",\"expiry_date\":\"2027-09-09\",\"receiving_store\":\"FW02\"},\"after\":{\"division\":\"SNW\",\"material_no\":\"100411\",\"material_description\":\"SPRINGLEY 1500 ML (1*6)\",\"batch_no\":\"SP090926\",\"current_qty\":3230,\"stock_value\":50000,\"manufacture_date\":\"2026-09-09\",\"expiry_date\":\"2027-09-09\",\"receiving_store\":\"FW02\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 18:14:16'),
(19, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'CSV_IMPORT', 'csv_import', NULL, 'CSV import completed: 0 inserted, 0 updated, 12 failed.', '{\"file_name\":\"qarshi_dispatch_stock_import_template.csv\",\"target_division\":\"From CSV rows\",\"inserted\":0,\"updated\":0,\"failed\":12,\"errors\":[\"Row 2: Stock Location is required.\",\"Row 3: Stock Location is required.\",\"Row 4: Stock Location is required.\",\"Row 5: Stock Location is required.\",\"Row 6: Stock Location is required.\",\"Row 7: Stock Location is required.\",\"Row 8: Stock Location is required.\",\"Row 9: Stock Location is required.\",\"Row 10: Stock Location is required.\",\"Row 11: Stock Location is required.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 18:52:52'),
(20, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'CSV_IMPORT', 'csv_import', NULL, 'CSV import completed: 12 inserted, 0 updated, 0 failed.', '{\"file_name\":\"qarshi_dispatch_stock_import_template.csv\",\"target_division\":\"From CSV rows\",\"inserted\":12,\"updated\":0,\"failed\":0,\"errors\":[]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 18:54:16'),
(21, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'GRADE_CRITERIA_UPDATE', 'grading_criteria', NULL, 'Updated division day-based grading criteria.', '{\"before\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}],\"after\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":40},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 19:04:04'),
(22, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'GRADE_CRITERIA_UPDATE', 'grading_criteria', NULL, 'Updated division day-based grading criteria.', '{\"before\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":40},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}],\"after\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":30,\"a_days\":45,\"b_days\":65,\"c_days\":120},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-26 19:04:42'),
(23, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_IMPORT', 'market_return_import', '1', 'Market Return import completed: 425 rows imported for FPD.', '{\"file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"file_type\":\"xlsx\",\"target_division\":\"FPD\",\"categories\":[\"cabinets\",\"export\",\"promotion\",\"rawalkot\",\"tablet_strips\",\"fresh_saleable\",\"utilizable\",\"expired\"],\"inserted\":425,\"deleted_previous\":0,\"replace_existing\":true,\"warnings\":[]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 05:57:23'),
(24, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 3, 'RTD', 'BATCH_UPDATE', 'stock_batch', '7', 'Updated batch 4560021 for material 120000200.', '{\"before\":{\"division\":\"RTD\",\"material_no\":\"120000200\",\"material_description\":\"10000200\",\"batch_no\":\"4560021\",\"current_qty\":50,\"stock_value\":0,\"manufacture_date\":\"2026-09-01\",\"expiry_date\":\"2026-09-21\",\"receiving_store\":null},\"after\":{\"division\":\"RTD\",\"material_no\":\"120000200\",\"material_description\":\"10000200\",\"batch_no\":\"4560021\",\"current_qty\":50,\"stock_value\":216000,\"manufacture_date\":\"2026-09-01\",\"expiry_date\":\"2026-09-21\",\"receiving_store\":\"RTD Store\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 06:11:23'),
(25, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:11:49'),
(26, 3, 'Bilal', 'bilal.tariq@qarshi.com', 'division_admin', 1, 'JSD', 'LOGIN_SUCCESS', 'auth', '3', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:12:05'),
(27, 3, 'Bilal', 'bilal.tariq@qarshi.com', 'division_admin', 1, 'JSD', 'LOGOUT', 'auth', '3', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:12:41'),
(28, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:13:18'),
(29, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '180', 'Deleted market-return item Sharbat Sadar.', '{\"id\":180,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"30\",\"product_name\":\"Sharbat Sadar\",\"quantity\":\"1.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Mrch.2028\",\"expiry_date\":null,\"retail_price\":\"220.00\",\"total_amount\":\"220.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:23'),
(30, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '181', 'Deleted market-return item SNMW 500ml.', '{\"id\":181,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"31\",\"product_name\":\"SNMW 500ml\",\"quantity\":\"26.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Nov 2026\",\"expiry_date\":\"0026-11-20\",\"retail_price\":\"50.00\",\"total_amount\":\"1300.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:27'),
(31, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '182', 'Deleted market-return item Genxing Capsules.', '{\"id\":182,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"32\",\"product_name\":\"Genxing Capsules\",\"quantity\":\"12.000\",\"unit\":\"Dabbi\",\"expiry_raw\":\"Feb 2029\",\"expiry_date\":\"0029-02-20\",\"retail_price\":\"900.00\",\"total_amount\":\"10800.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:31'),
(32, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '183', 'Deleted market-return item Shahi Capsules.', '{\"id\":183,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"33\",\"product_name\":\"Shahi Capsules\",\"quantity\":\"12.000\",\"unit\":\"Dabbi\",\"expiry_raw\":\"Jan 2029\",\"expiry_date\":\"0029-01-20\",\"retail_price\":\"480.00\",\"total_amount\":\"5760.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:40'),
(33, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '184', 'Deleted market-return item Arq-e-Gulab Spray 120ml.', '{\"id\":184,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"34\",\"product_name\":\"Arq-e-Gulab Spray 120ml\",\"quantity\":\"48.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Jan 2028\",\"expiry_date\":\"0028-01-20\",\"retail_price\":\"175.00\",\"total_amount\":\"8400.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:48'),
(34, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '185', 'Deleted market-return item Arq-e-Gulab Spray 120ml.', '{\"id\":185,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"35\",\"product_name\":\"Arq-e-Gulab Spray 120ml\",\"quantity\":\"48.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Dec 2027\",\"expiry_date\":\"0027-12-20\",\"retail_price\":\"175.00\",\"total_amount\":\"8400.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:53'),
(35, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '186', 'Deleted market-return item Arq-e-Gulab 120ml.', '{\"id\":186,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"36\",\"product_name\":\"Arq-e-Gulab 120ml\",\"quantity\":\"12.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Jul 2028\",\"expiry_date\":\"0028-07-20\",\"retail_price\":\"95.00\",\"total_amount\":\"1140.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:56'),
(36, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '187', 'Deleted market-return item Rogahn-e-Ojah 60ml.', '{\"id\":187,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"37\",\"product_name\":\"Rogahn-e-Ojah 60ml\",\"quantity\":\"40.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Mar 2029\",\"expiry_date\":\"0029-03-20\",\"retail_price\":\"250.00\",\"total_amount\":\"10000.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:36:58'),
(37, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '188', 'Deleted market-return item Arq-e-Gulab 800ml.', '{\"id\":188,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"38\",\"product_name\":\"Arq-e-Gulab 800ml\",\"quantity\":\"12.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Sep 2028\",\"expiry_date\":\"0028-09-20\",\"retail_price\":\"215.00\",\"total_amount\":\"2580.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:37:01'),
(38, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '189', 'Deleted market-return item Arq-e-Gulab 800ml.', '{\"id\":189,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"39\",\"product_name\":\"Arq-e-Gulab 800ml\",\"quantity\":\"144.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Feb 2028\",\"expiry_date\":\"0028-02-20\",\"retail_price\":\"215.00\",\"total_amount\":\"30960.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:37:04'),
(39, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '190', 'Deleted market-return item Arq-e-Gulab 800ml.', '{\"id\":190,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"40\",\"product_name\":\"Arq-e-Gulab 800ml\",\"quantity\":\"108.000\",\"unit\":\"Bottles\",\"expiry_raw\":\"Nov 2027\",\"expiry_date\":\"0027-11-20\",\"retail_price\":\"215.00\",\"total_amount\":\"23220.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:37:07'),
(40, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '191', 'Deleted market-return item Ispaghol Husk 95g.', '{\"id\":191,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"41\",\"product_name\":\"Ispaghol Husk 95g\",\"quantity\":\"54.000\",\"unit\":\"Jars\",\"expiry_raw\":\"Jan 2029\",\"expiry_date\":\"0029-01-20\",\"retail_price\":\"850.00\",\"total_amount\":\"45900.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:37:10'),
(41, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_DELETE', 'market_return_item', '192', 'Deleted market-return item Ispaghol Husk 95g.', '{\"id\":192,\"division_id\":4,\"category_code\":\"fresh_saleable\",\"source_sheet\":\"Fresh Saleable Stock final\",\"section_name\":\"Fresh Saleable Stock\",\"serial_no\":\"42\",\"product_name\":\"Ispaghol Husk 95g\",\"quantity\":\"1.000\",\"unit\":\"Jars\",\"expiry_raw\":\"Jan.2027\",\"expiry_date\":\"2027-01-31\",\"retail_price\":\"675.00\",\"total_amount\":\"675.00\",\"amount_text\":null,\"remarks\":null,\"aux_label\":null,\"aux_value\":null,\"report_date\":\"2026-09-25\",\"import_id\":1,\"created_by\":1,\"created_at\":\"2026-09-27 10:57:23\",\"updated_at\":\"2026-09-27 10:57:23\",\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"import_file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"imported_at\":\"2026-09-27 10:57:23\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:37:12'),
(42, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '2', 'Market Return import completed: 1 rows imported across 1 division(s).', '{\"file_name\":\"market_return_store_single_category_template.csv\",\"file_type\":\"csv\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":4,\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"inserted\":1,\"deleted_previous\":105,\"categories\":[\"utilizable\"]}],\"categories\":[\"utilizable\"],\"inserted\":1,\"deleted_previous\":105,\"replace_requested\":true,\"replace_effective\":true,\"unmatched_rows\":0,\"warnings\":[]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:37:36'),
(43, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '3', 'Market Return import completed: 1 rows imported across 1 division(s).', '{\"file_name\":\"market_return_store_single_category_template.csv\",\"file_type\":\"csv\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":4,\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"inserted\":1,\"deleted_previous\":1,\"categories\":[\"utilizable\"]}],\"categories\":[\"utilizable\"],\"inserted\":1,\"deleted_previous\":1,\"replace_requested\":true,\"replace_effective\":true,\"unmatched_rows\":0,\"warnings\":[]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:41:29'),
(44, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '1,2', 'Market Return import completed: 12 rows imported across 2 division(s).', '{\"file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"file_type\":\"xlsx\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":1,\"division_code\":\"JSD\",\"division_name\":\"Jam-e-Shirin\",\"inserted\":11,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\"]},{\"division_id\":2,\"division_code\":\"SNW\",\"division_name\":\"Springly Natural Water\",\"inserted\":1,\"deleted_previous\":0,\"categories\":[\"utilizable\"]}],\"categories\":[\"cabinets\",\"export\",\"promotion\",\"rawalkot\",\"tablet_strips\",\"fresh_saleable\",\"utilizable\",\"expired\"],\"inserted\":12,\"deleted_previous\":0,\"replace_requested\":true,\"replace_effective\":false,\"unmatched_rows\":413,\"warnings\":[\"Replace Existing was automatically disabled because some rows could not be assigned to a division. This prevents accidental deletion of existing Market Return data.\",\"413 row(s) were not imported because their division could not be identified safely.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:47:19'),
(45, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '3,4', 'Market Return import completed: 12 rows imported across 2 division(s).', '{\"file_name\":\"Market_Return_Store_Items_Template.xlsx\",\"file_type\":\"xlsx\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":1,\"division_code\":\"JSD\",\"division_name\":\"Jam-e-Shirin\",\"inserted\":11,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\"]},{\"division_id\":2,\"division_code\":\"SNW\",\"division_name\":\"Springly Natural Water\",\"inserted\":1,\"deleted_previous\":0,\"categories\":[\"utilizable\"]}],\"categories\":[\"cabinets\",\"export\",\"promotion\",\"rawalkot\",\"tablet_strips\",\"fresh_saleable\",\"utilizable\",\"expired\"],\"inserted\":12,\"deleted_previous\":0,\"replace_requested\":true,\"replace_effective\":false,\"unmatched_rows\":413,\"warnings\":[\"Replace Existing was automatically disabled because some rows could not be assigned to a division. This prevents accidental deletion of existing Market Return data.\",\"413 row(s) were not imported because their division could not be identified safely.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:47:35'),
(46, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'GRADE_CRITERIA_UPDATE', 'grading_criteria', NULL, 'Updated division day-based grading criteria.', '{\"before\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":30,\"a_days\":45,\"b_days\":65,\"c_days\":120},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}],\"after\":[{\"division\":\"FPD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"JSD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"NMD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"RTD\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45},{\"division\":\"SNW\",\"a_plus_days\":1,\"a_days\":15,\"b_days\":30,\"c_days\":45}]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 08:51:59'),
(47, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '5,6', 'Market Return import completed: 12 rows imported across 2 division(s).', '{\"file_name\":\"Market_Return_Store_Items.xlsx\",\"file_type\":\"xlsx\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":1,\"division_code\":\"JSD\",\"division_name\":\"Jam-e-Shirin\",\"inserted\":11,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\"]},{\"division_id\":2,\"division_code\":\"SNW\",\"division_name\":\"Springly Natural Water\",\"inserted\":1,\"deleted_previous\":0,\"categories\":[\"utilizable\"]}],\"categories\":[\"cabinets\",\"export\",\"promotion\",\"rawalkot\",\"tablet_strips\",\"fresh_saleable\",\"utilizable\",\"expired\"],\"inserted\":12,\"deleted_previous\":0,\"replace_requested\":true,\"replace_effective\":false,\"unmatched_rows\":413,\"warnings\":[\"Replace Existing was automatically disabled because some rows could not be assigned to a division. This prevents accidental deletion of existing Market Return data.\",\"413 row(s) were not imported because their division could not be identified safely.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 09:18:13'),
(48, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 4, 'FPD', 'MARKET_RETURN_MAPPING_CREATE', 'market_return_division_mapping', NULL, 'Added Market Return division mapping for FPD.', '{\"match_text\":\"FPD\",\"match_type\":\"contains\",\"division_id\":4,\"division_code\":\"FPD\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 10:41:20'),
(49, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 1, 'JSD', 'MARKET_RETURN_MAPPING_CREATE', 'market_return_division_mapping', NULL, 'Added Market Return division mapping for JSD.', '{\"match_text\":\"JSD\",\"match_type\":\"contains\",\"division_id\":1,\"division_code\":\"JSD\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 10:41:31'),
(50, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 5, 'NMD', 'MARKET_RETURN_MAPPING_CREATE', 'market_return_division_mapping', NULL, 'Added Market Return division mapping for NMD.', '{\"match_text\":\"NMD\",\"match_type\":\"contains\",\"division_id\":5,\"division_code\":\"NMD\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 10:41:41'),
(51, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 3, 'RTD', 'MARKET_RETURN_MAPPING_CREATE', 'market_return_division_mapping', NULL, 'Added Market Return division mapping for RTD.', '{\"match_text\":\"RTD\",\"match_type\":\"contains\",\"division_id\":3,\"division_code\":\"RTD\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 10:41:51'),
(52, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 2, 'SNW', 'MARKET_RETURN_MAPPING_CREATE', 'market_return_division_mapping', NULL, 'Added Market Return division mapping for SNW.', '{\"match_text\":\"SNW\",\"match_type\":\"exact\",\"division_id\":2,\"division_code\":\"SNW\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 10:42:00'),
(53, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 2, 'SNW', 'MARKET_RETURN_MAPPING_CREATE', 'market_return_division_mapping', NULL, 'Added Market Return division mapping for SNW.', '{\"match_text\":\"SNW\",\"match_type\":\"contains\",\"division_id\":2,\"division_code\":\"SNW\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 10:42:50'),
(54, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '7', 'Market Return import completed: 14 rows imported across 1 division(s).', '{\"file_name\":\"Market_Return_Store_Items JSD.xlsx\",\"file_type\":\"xlsx\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":1,\"division_code\":\"JSD\",\"division_name\":\"Jam-e-Shirin Division\",\"inserted\":14,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\",\"expired\"]}],\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\",\"expired\"],\"inserted\":14,\"deleted_previous\":0,\"replace_requested\":true,\"replace_effective\":false,\"unmatched_rows\":3,\"warnings\":[\"14 exact Market Return product/division mapping(s) were learned for future imports.\",\"Replace Existing was automatically disabled because some rows could not be assigned to a division. This prevents accidental deletion of existing Market Return data.\",\"3 row(s) were not imported because their division could not be identified safely.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:02:07'),
(55, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 3, 'RTD', 'BATCH_UPDATE', 'stock_batch', '7', 'Updated batch 4560021 for material 120000200.', '{\"before\":{\"division\":\"RTD\",\"material_no\":\"120000200\",\"material_description\":\"10000200\",\"batch_no\":\"4560021\",\"current_qty\":50,\"stock_value\":216000,\"manufacture_date\":\"2026-09-01\",\"expiry_date\":\"2026-09-21\",\"receiving_store\":\"RTD Store\"},\"after\":{\"division\":\"RTD\",\"material_no\":\"120000200\",\"material_description\":\"Applsh (Demo Only)\",\"batch_no\":\"4560021\",\"current_qty\":50,\"stock_value\":216000,\"manufacture_date\":\"2026-09-01\",\"expiry_date\":\"2026-09-21\",\"receiving_store\":\"RTD Store\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:37:33'),
(56, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 3, 'RTD', 'BATCH_UPDATE', 'stock_batch', '7', 'Updated batch 4560021 for material 500711.', '{\"before\":{\"division\":\"RTD\",\"material_no\":\"120000200\",\"material_description\":\"Applsh (Demo Only)\",\"batch_no\":\"4560021\",\"current_qty\":50,\"stock_value\":216000,\"manufacture_date\":\"2026-09-01\",\"expiry_date\":\"2026-09-21\",\"receiving_store\":\"RTD Store\"},\"after\":{\"division\":\"RTD\",\"material_no\":\"500711\",\"material_description\":\"Applsh (Demo Only)\",\"batch_no\":\"4560021\",\"current_qty\":50,\"stock_value\":216000,\"manufacture_date\":\"2026-09-01\",\"expiry_date\":\"2026-09-21\",\"receiving_store\":\"RTD Store\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:37:50'),
(57, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:39:29'),
(58, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:48:05'),
(59, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:49:38'),
(60, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:50:51'),
(61, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:52:49'),
(62, NULL, 'Login Attempt', 'admin@example.co', 'guest', NULL, NULL, 'LOGIN_FAILED', 'auth', NULL, 'Failed login attempt for admin@example.co.', '{\"selected_division_id\":null}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:53:09'),
(63, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 11:53:23'),
(64, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'SAP_TEST_FAILED', 'sap', NULL, 'SAP connection test failed.', '{\"error\":\"SAP connection is not configured. Set SAP_BASE_URL, SAP_USERNAME and SAP_PASSWORD.\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 12:25:49'),
(65, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'SAP_TEST_FAILED', 'sap', NULL, 'SAP connection test failed.', '{\"error\":\"SAP connection is not configured. Set SAP_BASE_URL, SAP_USERNAME and SAP_PASSWORD.\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 12:26:14'),
(66, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '8', 'Market Return import completed: 356 rows imported across 1 division(s).', '{\"file_name\":\"Market_Return_Store_Items_NMD.xlsx\",\"file_type\":\"xlsx\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":5,\"division_code\":\"NMD\",\"division_name\":\"Natural Medicine Division\",\"inserted\":356,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"rawalkot\",\"tablet_strips\",\"fresh_saleable\",\"utilizable\",\"expired\"]}],\"categories\":[\"cabinets\",\"export\",\"rawalkot\",\"tablet_strips\",\"fresh_saleable\",\"utilizable\",\"expired\"],\"inserted\":356,\"deleted_previous\":0,\"replace_requested\":true,\"replace_effective\":false,\"unmatched_rows\":14,\"warnings\":[\"356 exact Market Return product/division mapping(s) were learned for future imports.\",\"Replace Existing was automatically disabled because some rows could not be assigned to a division. This prevents accidental deletion of existing Market Return data.\",\"14 row(s) were not imported because their division could not be identified safely.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 12:48:39'),
(67, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 5, 'NMD', 'USER_CREATE', 'user', '5', 'Created user Faheem.', '{\"name\":\"Faheem\",\"email\":\"faheem.ehsan90@gmail.com\",\"role\":\"division_admin\",\"division\":\"NMD\",\"profile_picture\":\"not uploaded\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 12:49:21'),
(68, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 12:49:26'),
(69, 5, 'Faheem', 'faheem.ehsan90@gmail.com', 'division_admin', 5, 'NMD', 'LOGIN_SUCCESS', 'auth', '5', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 12:49:35'),
(70, 5, 'Faheem', 'faheem.ehsan90@gmail.com', 'division_admin', 5, 'NMD', 'LOGOUT', 'auth', '5', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 12:59:50'),
(71, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 13:00:13'),
(72, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '9', 'Market Return import completed: 13 rows imported across 1 division(s).', '{\"file_name\":\"Market_Return_Store_items_FPD.xlsx\",\"file_type\":\"xlsx\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":4,\"division_code\":\"FPD\",\"division_name\":\"Farm Product Division\",\"inserted\":13,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"rawalkot\",\"fresh_saleable\",\"expired\"]}],\"categories\":[\"cabinets\",\"export\",\"rawalkot\",\"fresh_saleable\",\"expired\"],\"inserted\":13,\"deleted_previous\":0,\"replace_requested\":true,\"replace_effective\":true,\"unmatched_rows\":0,\"warnings\":[\"13 exact Market Return product/division mapping(s) were learned for future imports.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 13:51:13'),
(73, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'MARKET_RETURN_IMPORT', 'market_return_import', '10', 'Market Return import completed: 2 rows imported across 1 division(s).', '{\"file_name\":\"Market_Return_Store_Items_SNW.xlsx\",\"file_type\":\"xlsx\",\"automatic_division_assignment\":true,\"division_results\":[{\"division_id\":2,\"division_code\":\"SNW\",\"division_name\":\"Springley Natural Water Division\",\"inserted\":2,\"deleted_previous\":0,\"categories\":[\"fresh_saleable\",\"utilizable\"]}],\"categories\":[\"fresh_saleable\",\"utilizable\"],\"inserted\":2,\"deleted_previous\":0,\"replace_requested\":true,\"replace_effective\":true,\"unmatched_rows\":0,\"warnings\":[\"2 exact Market Return product/division mapping(s) were learned for future imports.\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 14:54:19'),
(74, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', 1, 'JSD', 'BATCH_UPDATE', 'stock_batch', '21', 'Updated batch BN190526 for material 100401.', '{\"before\":{\"division\":\"JSD\",\"material_no\":\"100401\",\"material_description\":\"SHARBAT BAZOOREEN 800ML\",\"batch_no\":\"BN190526\",\"current_qty\":925,\"stock_value\":925,\"manufacture_date\":\"2026-05-19\",\"expiry_date\":\"2029-04-19\",\"receiving_store\":\"JSD Dispatch\"},\"after\":{\"division\":\"JSD\",\"material_no\":\"100401\",\"material_description\":\"SHARBAT BAZOOREEN 800ML\",\"batch_no\":\"BN190526\",\"current_qty\":925,\"stock_value\":1925,\"manufacture_date\":\"2026-05-19\",\"expiry_date\":\"2029-04-19\",\"receiving_store\":\"JSD Dispatch\"}}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 16:25:07'),
(75, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGOUT', 'auth', '1', 'User signed out.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 17:14:14'),
(76, 1, 'Faheem Ehsan', 'admin@example.com', 'super_admin', NULL, NULL, 'LOGIN_SUCCESS', 'auth', '1', 'User signed in successfully.', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-27 17:14:27');

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
(1, 'JSD', 'Jam-e-Shirin Division', 1, '2026-09-25 15:55:31'),
(2, 'SNW', 'Springley Natural Water Division', 1, '2026-09-25 15:55:31'),
(3, 'RTD', 'Ready To Drink Division', 1, '2026-09-25 15:55:31'),
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
(2, 1, 1, 2, 3, 1, 15, 30, 45, '2026-09-26 11:08:07', '2026-09-27 08:51:59'),
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
-- Table structure for table `market_return_division_mappings`
--

CREATE TABLE `market_return_division_mappings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `match_type` enum('exact','contains') NOT NULL DEFAULT 'exact',
  `match_text` varchar(255) NOT NULL,
  `match_key` varchar(255) NOT NULL,
  `division_id` int(10) UNSIGNED NOT NULL,
  `source_type` enum('manual','file','login') NOT NULL DEFAULT 'manual',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `market_return_division_mappings`
--

INSERT INTO `market_return_division_mappings` (`id`, `match_type`, `match_text`, `match_key`, `division_id`, `source_type`, `created_by`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'contains', 'FPD', 'fpd', 4, 'manual', 1, 1, '2026-09-27 10:41:20', '2026-09-27 10:41:20'),
(2, 'contains', 'JSD', 'jsd', 1, 'manual', 1, 1, '2026-09-27 10:41:31', '2026-09-27 10:41:31'),
(3, 'contains', 'NMD', 'nmd', 5, 'manual', 1, 1, '2026-09-27 10:41:41', '2026-09-27 10:41:41'),
(4, 'contains', 'RTD', 'rtd', 3, 'manual', 1, 1, '2026-09-27 10:41:51', '2026-09-27 10:41:51'),
(6, 'contains', 'SNW', 'snw', 2, 'manual', 1, 1, '2026-09-27 10:42:50', '2026-09-27 10:42:50'),
(7, 'exact', 'Sandalleen Syrup 800ml', 'sandalleen syrup 800ml', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(8, 'exact', 'Jam-e -Shirin 1500 ml', 'jam e shirin 1500 ml', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(9, 'exact', 'Jam-e -Shirin 3000 ml', 'jam e shirin 3000 ml', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(10, 'exact', 'Jam-e-Shirin Sugar Free', 'jam e shirin sugarfree', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(15, 'exact', 'Compost bags 2KG', 'compost bags 2kg', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(16, 'exact', 'Sandalleen 800ml', 'sandalleen 800ml', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(17, 'exact', 'Jam-e-Shirin 800ml', 'jam e shirin 800ml', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(18, 'exact', 'Jam-e-Shirin 3 Liters', 'jam e shirin 3 liters', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(20, 'exact', 'Illachi syrup 800ml', 'illachi syrup 800ml', 1, 'file', 1, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(21, 'exact', 'Anjabar syrup 240 ml', 'anjabar syrup 240 ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(22, 'exact', 'Safoof -e-Tabkhir 80 gm', 'safoof e tabkhir 80 gm', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(23, 'exact', 'Sharbat-e-Faulad 240ml', 'sharbat e faulad 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(24, 'exact', 'Banafsha 240ml', 'banafsha 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(25, 'exact', 'Arq Gao-zabab 800ml', 'arq gao zabab 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(26, 'exact', 'Arq Badian 800ml', 'arq badian 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(27, 'exact', 'Arq Mako 800 ml', 'arq mako 800 ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(28, 'exact', 'Choarqa 800ml', 'choarqa 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(30, 'exact', 'Gesto Syrup', 'gesto syrup', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(31, 'exact', 'Toot Sia 120ml', 'toot sia 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(32, 'exact', 'Toot Sia 240ml', 'toot sia 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(33, 'exact', 'Arq Gao-zabab 120ml', 'arq gao zabab 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(34, 'exact', 'Ahmareen syrup 120ml', 'ahmareen syrup 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(35, 'exact', 'Qarshi Guhtti 60ml', 'guhtti 60ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(36, 'exact', 'Corezcol syrup 60ml', 'corezcol syrup 60ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(37, 'exact', 'Arq Kasni 800ml', 'arq kasni 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(38, 'exact', 'Anti Fat tablets', 'anti fat tablets', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(39, 'exact', 'Zarjam capsule', 'zarjam capsule', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(40, 'exact', 'Arq Sherian 240ml', 'arq sherian 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(41, 'exact', 'Arq Gulab Spray 120ml', 'arq gulab spray 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(42, 'exact', 'Arq Gulab 120ml', 'arq gulab 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(43, 'exact', 'Surficol tablets', 'surficol tablets', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(45, 'exact', 'Foladi Syrup 120 ml', 'foladi syrup 120 ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(48, 'exact', 'Jawarish Jalinoos 100 gm', 'jawarish jalinoos 100 gm', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(50, 'exact', 'QJJ Regular', 'qjj regular', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(51, 'exact', 'QJJ Export, Honey ,choclate, sugar free zinc', 'qjj honey choclate sugarfree zinc', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(52, 'exact', 'QJJ Honey', 'qjj honey', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(53, 'exact', 'QJJ Sugar Free', 'qjj sugarfree', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(54, 'exact', 'Arq Makoh 800ml', 'arq makoh 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(62, 'exact', 'kasni water 800ml', 'kasni water 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(63, 'exact', 'Fennel Water 800ml', 'fennel water 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(64, 'exact', 'Borage water 800ml', 'borage water 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(65, 'exact', 'Choarqa water', 'choarqa water', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(66, 'exact', 'Roze water 800ml', 'roze water 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(67, 'exact', 'Compost bags 2KG', 'compost bags 2kg', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(68, 'exact', 'Laal Sharbat 240ml', 'laal sharbat 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(69, 'exact', 'Safoof E Tabkhir', 'safoof e tabkhir', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(70, 'exact', 'Akseer E Pechish', 'akseer e pechish', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(71, 'exact', 'Toot Siah 240ml', 'toot siah 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(72, 'exact', 'Allergies', 'allergies', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(73, 'exact', 'Akseer E Jigar', 'akseer e jigar', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(74, 'exact', 'Asthamina Syp.', 'asthamina syp', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(75, 'exact', 'Kardil Tab.', 'kardil tab', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:39'),
(76, 'exact', 'Goshina Drops', 'goshina drops', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(77, 'exact', 'Majoon Ard Khurma', 'majoon ard khurma', 5, 'file', 1, 1, '2026-09-27 12:48:38', '2026-09-27 12:48:38'),
(79, 'exact', 'Majoon E Philaspha', 'majoon e philaspha', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(80, 'exact', 'Habb E Muqil Khas', 'habb e muqil khas', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(81, 'exact', 'Mensofar Tab.', 'mensofar tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(82, 'exact', 'Musaffeen Tab.', 'musaffeen tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(83, 'exact', 'Musaffeen Syp.', 'musaffeen syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(84, 'exact', 'Urosinal Syp.', 'urosinal syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(85, 'exact', 'Sharbat E Faulad', 'sharbat e faulad', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(86, 'exact', 'Qarshi Ghutti', 'ghutti', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(87, 'exact', 'Zubex', 'zubex', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(88, 'exact', 'Piles X Oil', 'piles x oil', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(89, 'exact', 'Safoof E Mughallaj', 'safoof e mughallaj', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(90, 'exact', 'Habis Powder', 'habis powder', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(91, 'exact', 'Pileen Tab.', 'pileen tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(92, 'exact', 'Habb E Gurda', 'habb e gurda', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(93, 'exact', 'Lecodine Tab.', 'lecodine tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(94, 'exact', 'Shafai Tab.', 'shafai tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(96, 'exact', 'Ispaghol Husk', 'ispaghol husk', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(97, 'exact', 'Surficol Tab.', 'surficol tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(98, 'exact', 'Surficol Syp.', 'surficol syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(99, 'exact', 'Fever X Syp.', 'fever x syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(100, 'exact', 'Khatooni Syp.', 'khatooni syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(101, 'exact', 'Jawarish E Anareen', 'jawarish e anareen', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(102, 'exact', 'Asphatoon Tab.', 'asphatoon tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(103, 'exact', 'Khameera Gao Zaban', 'khameera gao zaban', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(104, 'exact', 'Jawarish E Zarooni', 'jawarish e zarooni', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(105, 'exact', 'Itrifal Kashneez', 'itrifal kashneez', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(106, 'exact', 'Jawarish E Shahi', 'jawarish e shahi', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(107, 'exact', 'Corezcol Syp.', 'corezcol syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(108, 'exact', 'Thandak Safoof', 'thandak safoof', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(109, 'exact', 'Habb E Tinkar Tab', 'habb e tinkar tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(110, 'exact', 'Jawarish E Amla', 'jawarish e amla', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(111, 'exact', 'Jawarish E Kamooni', 'jawarish e kamooni', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(112, 'exact', 'Typhex Syp.', 'typhex syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(113, 'exact', 'BM 81 Tab.', 'bm 81 tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(114, 'exact', 'Haiza Murgh Murakkab', 'haiza murgh murakkab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(116, 'exact', 'Majoon E Najar', 'majoon e najar', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(117, 'exact', 'Tablet Strips', 'tablet strips', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(118, 'exact', 'Surficol Strips', 'surficol strips', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(120, 'exact', 'Shahi + Genxing Strips', 'shahi genxing strips', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(130, 'exact', 'QJJ Zinc', 'qjj zinc', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(132, 'exact', 'QJJ Choc.', 'qjj chocolate', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(135, 'exact', 'Dimaghi 100g', 'dimaghi 100g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(142, 'exact', 'Toot Siah 120ml', 'toot siah 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(145, 'exact', 'Toot Siah 60ml', 'toot siah 60ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(148, 'exact', 'Sharbat Sadar', 'sharbat sadar', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(149, 'exact', 'Genxing Capsules', 'genxing capsules', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(150, 'exact', 'Shahi Capsules', 'shahi capsules', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(151, 'exact', 'Arq-e-Gulab Spray 120ml', 'arq e gulab spray 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(153, 'exact', 'Arq-e-Gulab 120ml', 'arq e gulab 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(154, 'exact', 'Rogahn-e-Ojah 60ml', 'rogahn e ojah 60ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(155, 'exact', 'Arq-e-Gulab 800ml', 'arq e gulab 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(158, 'exact', 'Ispaghol Husk 95g', 'ispaghol husk 95g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(162, 'exact', 'Ispaghol Husk 50g', 'ispaghol husk 50g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(164, 'exact', 'Ispaghol Husk A 140g', 'ispaghol husk a 140g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(166, 'exact', 'Laal Sharbat 120ml', 'laal sharbat 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(171, 'exact', 'Jawarish Kamoni 500gm', 'jawarish kamoni 500gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(177, 'exact', 'Jaryaneen Tab.', 'jaryaneen tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(179, 'exact', 'Fauladi Tonic 120ml', 'fauladi tonic 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(180, 'exact', 'Fouladi Tonic 45ml', 'fouladi tonic 45ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(187, 'exact', 'Ispaghol Husk 25gm', 'ispaghol husk 25gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(191, 'exact', 'Gestofil Syp. 120ml', 'gestofil syp 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(193, 'exact', 'Habb E Kabd Noshadri Tab.', 'habb e kabd noshadri tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(194, 'exact', 'Habb E Muqil Khas Tab.', 'habb e muqil khas tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(195, 'exact', 'Itrifal Kashneez Tab.', 'itrifal kashneez tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(197, 'exact', 'Bukharin Tab.', 'bukharin tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(198, 'exact', 'Zubex Tab,', 'zubex tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(199, 'exact', 'Laal Sharbat Syp.', 'laal sharbat syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(200, 'exact', 'Majoon E Dabeed Ul Ward', 'majoon e dabeed ul ward', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(202, 'exact', 'Allergex', 'allergex', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(203, 'exact', 'Majoon E Najah', 'majoon e najah', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(204, 'exact', 'Arq E Gulaab 800ml', 'arq e gulaab 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(213, 'exact', 'Ispagohle', 'ispagohle', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(214, 'exact', 'Arq-e-Badian', 'arq e badian', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(215, 'exact', 'Lal Sharbat 120ml', 'lal sharbat 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(217, 'exact', 'Lal Sharbat 240ml', 'lal sharbat 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(218, 'exact', 'Mumsik Sultani', 'mumsik sultani', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(219, 'exact', 'Sharbat E Fulad 240 ml', 'sharbat e fulad 240 ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(220, 'exact', 'Ispagohle 25 gm', 'ispagohle 25 gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(223, 'exact', 'Ispagohle 50 gm', 'ispagohle 50 gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(226, 'exact', 'Ispagohle 140 Gm', 'ispagohle 140 gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(227, 'exact', 'Arq e-Gulab 800 ml', 'arq e gulab 800 ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(228, 'exact', 'Arq e-Gulab 120 ml Spray', 'arq e gulab 120 ml spray', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(229, 'exact', 'Musaffeen 120ml', 'musaffeen 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(230, 'exact', 'Bukharin Tablet Jar', 'bukharin tablet jar', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(231, 'exact', 'Dimaghi 100gm', 'dimaghi 100gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(233, 'exact', 'Dimaghi 250gm', 'dimaghi 250gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(234, 'exact', 'Dimaghi 500gm', 'dimaghi 500gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(235, 'exact', 'Toot Sia 240 ml', 'toot sia 240 ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(238, 'exact', 'Toot Sia 60 ml', 'toot sia 60 ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(239, 'exact', 'Genxing Cap', 'genxing cap', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(240, 'exact', 'Garlicare tablets', 'garlicare tablets', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(241, 'exact', 'Gasto tablets', 'gasto tablets', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(242, 'exact', 'Gestofill syrup 120ml', 'gestofill syrup 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(245, 'exact', 'Gesto Tablets', 'gesto tablets', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(246, 'exact', 'Gesto Tables', 'gesto tables', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(249, 'exact', 'Gestofill syrup', 'gestofill syrup', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(250, 'exact', 'Pathri Tore', 'pathri tore', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(251, 'exact', 'Laboob-e Bard 500 gm', 'laboob e bard 500 gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(253, 'exact', 'Surficol syrup 120ml', 'surficol syrup 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(255, 'exact', 'Majoon Mughaliz Jawahar wali 100gm', 'majoon mughaliz jawahar wali 100gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(256, 'exact', 'Mansofar Tablet', 'mansofar tablet', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(257, 'exact', 'Rogahn E Badam 30ml', 'rogahn e badam 30ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(263, 'exact', 'Ispaghol Husk 25g', 'ispaghol husk 25g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(268, 'exact', 'Ispaghol Husk 140g', 'ispaghol husk 140g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(269, 'exact', 'Khatooni 120ml', 'khatooni 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(272, 'exact', 'CMZ Tab.', 'cmz tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(273, 'exact', 'Corez col 60 ml', 'corez col 60 ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(274, 'exact', 'Aksser-e Jiger 120ml', 'aksser e jiger 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(275, 'exact', 'Arq Mako 240ml', 'arq mako 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(276, 'exact', 'Asthamina 120ml', 'asthamina 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(277, 'exact', 'Goshina', 'goshina', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(278, 'exact', 'Sharbat Ambreen 240ml', 'sharbat ambreen 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(279, 'exact', 'Jawarish E Jalinoos', 'jawarish e jalinoos', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(282, 'exact', 'Gestofil Tab.', 'gestofil tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(284, 'exact', 'Rogahn E Badam', 'rogahn e badam', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(285, 'exact', 'Anti Fat Tab.', 'anti fat tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(287, 'exact', 'Tone Up Syp.', 'tone up syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(288, 'exact', 'Sadabahar Syp.', 'sadabahar syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(289, 'exact', 'Taryak E Nazla', 'taryak e nazla', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(290, 'exact', 'Supari Pak Safoof', 'supari pak safoof', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(292, 'exact', 'Laboob E Kabeer', 'laboob e kabeer', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(293, 'exact', 'Laboob E Khas', 'laboob e khas', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(295, 'exact', 'Rogahn E Arand Syp.', 'rogahn e arand syp', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(296, 'exact', 'Musaffeen Syp. 120ml', 'musaffeen syp 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(302, 'exact', 'Surficol Syp. 120ml Plus', 'surficol syp 120ml plus', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(303, 'exact', 'Surficol Syp. 120ML', 'surficol syp 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(304, 'exact', 'Corezcol Syp. 60ml', 'corezcol syp 60ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(305, 'exact', 'Majoon Khabs Ul Hadeed', 'majoon khabs ul hadeed', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(306, 'exact', 'Zarjam Tab.', 'zarjam tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(307, 'exact', 'Arq E Mako', 'arq e mako', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(308, 'exact', 'Choarqah 800ml', 'choarqah 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(315, 'exact', 'Almond oil', 'almond oil', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(316, 'exact', 'Rogan-e-Ujah', 'rogan e ujah', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(317, 'exact', 'Amrat Dhara', 'amrat dhara', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(322, 'exact', 'Araq-shirin', 'araq shirin', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(328, 'exact', 'Ispagohle 95 Gm', 'ispagohle 95 gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(332, 'exact', 'Garlicare', 'garlicare', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(334, 'exact', 'Mussafeen', 'mussafeen', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(335, 'exact', 'Qarshi Gutti', 'gutti', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(336, 'exact', 'Arq-e Badian 240ml', 'arq e badian 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(337, 'exact', 'Arq-e Badian 800ml', 'arq e badian 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(340, 'exact', 'Arq-e-Gulab 60ml', 'arq e gulab 60ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(344, 'exact', 'Aqseer-e Pachish', 'aqseer e pachish', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(346, 'exact', 'Reno Tablet', 'reno tablet', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(348, 'exact', 'Murakab Abraq', 'murakab abraq', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(349, 'exact', 'Majoon Azaraqi 500 gm', 'majoon azaraqi 500 gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(350, 'exact', 'Majoon e suranjan', 'majoon e suranjan', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(351, 'exact', 'Itrifal Zamani 500gm', 'itrifal zamani 500gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(353, 'exact', 'Majoon eo Dabibul ward', 'majoon eo dabibul ward', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(355, 'exact', 'Arq Makoh', 'arq makoh', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(356, 'exact', 'Khameera Gao Zaban 50gm', 'khameera gao zaban 50gm', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(359, 'exact', 'Shahi Capsule', 'shahi capsule', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(360, 'exact', 'Genxing Capsule', 'genxing capsule', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(361, 'exact', 'Arq Goauzbaz 800ml', 'arq goauzbaz 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(362, 'exact', 'Arq Kasni', 'arq kasni', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(364, 'exact', 'Toneup 120ml', 'toneup 120ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(365, 'exact', 'Hab-e Gurda 500g', 'hab e gurda 500g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(366, 'exact', 'Majoon Mughalaz 100g', 'majoon mughalaz 100g', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(367, 'exact', 'Majoon E Mughallaz', 'majoon e mughallaz', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(368, 'exact', 'Arq E Maqoo 800ml', 'arq e maqoo 800ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(372, 'exact', 'Arq E Mako 240ml', 'arq e mako 240ml', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(374, 'exact', 'Garli Care Tab.', 'garli care tab', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(376, 'exact', 'Piles X', 'piles x', 5, 'file', 1, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(377, 'exact', 'Honey 150 gm', 'honey 150 gm', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:12'),
(378, 'exact', 'Quince', 'quince', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:13'),
(379, 'exact', 'Qarshi Honey 150gm', 'honey 150gm', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:12'),
(380, 'exact', 'Qarshi Honey 450gm', 'honey 450gm', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:12'),
(383, 'exact', 'Carrot Preserve 430gm', 'carrot preserve 430gm', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:12'),
(384, 'exact', 'Orange Marmalad', 'orange marmalad', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:12'),
(385, 'exact', 'Murabba Harar', 'murabba harar', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:12'),
(386, 'exact', 'Murabba amla', 'murabba amla', 4, 'file', 1, 1, '2026-09-27 13:51:12', '2026-09-27 13:51:12'),
(387, 'exact', 'Murabba Carret', 'murabba carret', 4, 'file', 1, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(388, 'exact', 'Murabba Apple', 'murabba apple', 4, 'file', 1, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(390, 'exact', 'SNW 500ml', 'snw 500ml', 2, 'file', 1, 1, '2026-09-27 14:54:19', '2026-09-27 14:54:19'),
(391, 'exact', 'Springley 500ml', 'springley 500ml', 2, 'file', 1, 1, '2026-09-27 14:54:19', '2026-09-27 14:54:19');

-- --------------------------------------------------------

--
-- Table structure for table `market_return_imports`
--

CREATE TABLE `market_return_imports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(20) NOT NULL,
  `imported_by` int(10) UNSIGNED DEFAULT NULL,
  `row_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `category_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `replace_existing` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('processing','completed','failed') NOT NULL DEFAULT 'processing',
  `summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary_json`)),
  `imported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `market_return_imports`
--

INSERT INTO `market_return_imports` (`id`, `division_id`, `file_name`, `file_type`, `imported_by`, `row_count`, `category_count`, `replace_existing`, `status`, `summary_json`, `imported_at`) VALUES
(1, 1, 'Market_Return_Store_Items_Template.xlsx', 'xlsx', 1, 11, 4, 0, 'completed', '{\"inserted\":11,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\"]}', '2026-09-27 08:47:19'),
(2, 2, 'Market_Return_Store_Items_Template.xlsx', 'xlsx', 1, 1, 1, 0, 'completed', '{\"inserted\":1,\"deleted_previous\":0,\"categories\":[\"utilizable\"]}', '2026-09-27 08:47:19'),
(3, 1, 'Market_Return_Store_Items_Template.xlsx', 'xlsx', 1, 11, 4, 0, 'completed', '{\"inserted\":11,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\"]}', '2026-09-27 08:47:35'),
(4, 2, 'Market_Return_Store_Items_Template.xlsx', 'xlsx', 1, 1, 1, 0, 'completed', '{\"inserted\":1,\"deleted_previous\":0,\"categories\":[\"utilizable\"]}', '2026-09-27 08:47:35'),
(5, 1, 'Market_Return_Store_Items.xlsx', 'xlsx', 1, 11, 4, 0, 'completed', '{\"inserted\":11,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\"]}', '2026-09-27 09:18:13'),
(6, 2, 'Market_Return_Store_Items.xlsx', 'xlsx', 1, 1, 1, 0, 'completed', '{\"inserted\":1,\"deleted_previous\":0,\"categories\":[\"utilizable\"]}', '2026-09-27 09:18:13'),
(7, 1, 'Market_Return_Store_Items JSD.xlsx', 'xlsx', 1, 14, 5, 0, 'completed', '{\"inserted\":14,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"fresh_saleable\",\"utilizable\",\"expired\"]}', '2026-09-27 11:02:07'),
(8, 5, 'Market_Return_Store_Items_NMD.xlsx', 'xlsx', 1, 356, 7, 0, 'completed', '{\"inserted\":356,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"rawalkot\",\"tablet_strips\",\"fresh_saleable\",\"utilizable\",\"expired\"]}', '2026-09-27 12:48:39'),
(9, 4, 'Market_Return_Store_items_FPD.xlsx', 'xlsx', 1, 13, 5, 1, 'completed', '{\"inserted\":13,\"deleted_previous\":0,\"categories\":[\"cabinets\",\"export\",\"rawalkot\",\"fresh_saleable\",\"expired\"]}', '2026-09-27 13:51:13'),
(10, 2, 'Market_Return_Store_Items_SNW.xlsx', 'xlsx', 1, 2, 2, 1, 'completed', '{\"inserted\":2,\"deleted_previous\":0,\"categories\":[\"fresh_saleable\",\"utilizable\"]}', '2026-09-27 14:54:19');

-- --------------------------------------------------------

--
-- Table structure for table `market_return_items`
--

CREATE TABLE `market_return_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED NOT NULL,
  `category_code` varchar(40) NOT NULL,
  `source_sheet` varchar(120) NOT NULL,
  `section_name` varchar(255) DEFAULT NULL,
  `serial_no` varchar(40) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` decimal(18,3) NOT NULL DEFAULT 0.000,
  `unit` varchar(80) NOT NULL DEFAULT 'Nos',
  `expiry_raw` varchar(120) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `retail_price` decimal(18,2) DEFAULT NULL,
  `total_amount` decimal(18,2) DEFAULT NULL,
  `amount_text` varchar(120) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `aux_label` varchar(80) DEFAULT NULL,
  `aux_value` varchar(120) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `import_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `market_return_items`
--

INSERT INTO `market_return_items` (`id`, `division_id`, `category_code`, `source_sheet`, `section_name`, `serial_no`, `product_name`, `quantity`, `unit`, `expiry_raw`, `expiry_date`, `retail_price`, `total_amount`, `amount_text`, `remarks`, `aux_label`, `aux_value`, `report_date`, `import_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'cabinets', 'Cabinets', '(Utilizable Stock)', '31', 'Sandalleen Syrup 800ml', 15.000, 'Bottles', '01/07/2028', '2028-07-01', 500.00, 7500.00, NULL, '//', NULL, NULL, '2026-09-24', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(2, 1, 'cabinets', 'Cabinets', '(Utilizable Stock)', '32', 'Jam-e -Shirin 1500 ml', 27.000, 'Bottles', '01/12/2027', '2027-12-01', 790.00, 21330.00, NULL, '//', NULL, NULL, '2026-09-24', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(3, 1, 'cabinets', 'Cabinets', '(Utilizable Stock)', '33', 'Jam-e -Shirin 3000 ml', 2.000, 'Bottles', '01/01/2028', '2028-01-01', 1390.00, 2780.00, NULL, '//', NULL, NULL, '2026-09-24', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(4, 1, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '19', 'Jam-e-Shirin Sugar Free', 2.000, 'Bottles', '01/03/2027', '2027-03-01', NULL, NULL, NULL, 'UK Export', NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(5, 1, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '20', 'Jam-e-Shirin Sugar Free', 2.000, 'Bottles', '01/05/2027', '2027-05-01', NULL, NULL, NULL, 'UK Export', NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(6, 1, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '21', 'Jam-e-Shirin Sugar Free', 1.000, 'Bottles', '01/08/2027', '2027-08-01', NULL, NULL, NULL, 'UK Export', NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(7, 1, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '22', 'Jam-e-Shirin Sugar Free', 1.000, 'Bottles', '01/12/2026', '2026-12-01', NULL, NULL, NULL, 'UK Export', NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(8, 1, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '23', 'Jam-e-Shirin Sugar Free', 2.000, 'Bottles', 'Not Found', NULL, NULL, NULL, NULL, 'UK Export', NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(9, 1, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '24', 'Compost bags 2KG', 10.000, 'Bags', NULL, NULL, NULL, NULL, NULL, 'Export Quality', NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(10, 1, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '57', 'Sandalleen 800ml', 12.000, 'Bottles', '01/04/2028', '2028-04-01', 500.00, 6000.00, NULL, NULL, NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(11, 1, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '58', 'Jam-e-Shirin 800ml', 12.000, 'Bottles', '01/10/2029', '2029-10-01', 550.00, 6600.00, NULL, NULL, NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(12, 1, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '59', 'Jam-e-Shirin 3 Liters', 4.000, 'Bottles', '01/11/2029', '2029-11-01', 1600.00, 6400.00, NULL, NULL, NULL, NULL, '2026-09-25', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(13, 1, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '68', 'Jam-e-Shirin 3 Liters', 4.000, 'Bottles', '01/11/2029', '2029-11-01', 1600.00, 6400.00, NULL, NULL, NULL, NULL, '2026-09-24', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(14, 1, 'expired', 'Expired', 'Expired Stock - Market Return Store', '76', 'Illachi syrup 800ml', 20.000, 'Bottles', '02/01/2026', '2026-01-02', 550.00, 11000.00, NULL, NULL, NULL, NULL, '2026-09-23', 7, 1, '2026-09-27 11:02:07', '2026-09-27 11:02:07'),
(15, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '1', 'Anjabar syrup 240 ml', 30.000, 'Bottles', '01.05.2028', '2028-05-01', 250.00, 7500.00, NULL, 'Placed in Cabinet # 01', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(16, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '2', 'Safoof -e-Tabkhir 80 gm', 485.000, 'Bottles', '01.08.2028', '2028-08-01', 265.00, 128525.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(17, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '3', 'Sharbat-e-Faulad 240ml', 89.000, 'Bottles', '01.04.2028', '2028-04-01', 280.00, 24920.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(18, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '4', 'Banafsha 240ml', 6.000, 'Bottles', '01.05.2027', '2027-05-01', 250.00, 1500.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(19, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '5', 'Arq Gao-zabab 800ml', 10.000, 'Bottles', '01.12.2027', '2027-12-01', 260.00, 2600.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(20, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '6', 'Arq Badian 800ml', 18.000, 'Bottles', '01.02.2028', '2028-02-01', 260.00, 4680.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(21, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '7', 'Arq Mako 800 ml', 24.000, 'Bottles', '01.12.2027', '2027-12-01', 260.00, 6240.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(22, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '8', 'Choarqa 800ml', 30.000, 'Bottles', '01.12.2027', '2027-12-01', 260.00, 7800.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(23, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '9', 'Sharbat-e-Faulad 240ml', 150.000, 'Bottles', '01.05.2027', '2027-05-01', 250.00, 37500.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(24, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '10', 'Gesto Syrup', 170.000, 'Bottles', '01.04.2028', '2028-04-01', 160.00, 27200.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(25, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '11', 'Toot Sia 120ml', 140.000, 'Bottles', '01.06.2028', '2028-06-01', 160.00, 22400.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(26, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '12', 'Toot Sia 240ml', 20.000, 'Bottles', '01.07.2027', '2027-07-01', 205.00, 4100.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(27, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '13', 'Arq Gao-zabab 120ml', 30.000, 'Bottles', '01.12.2026', '2026-12-01', 90.00, 2700.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(28, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '14', 'Ahmareen syrup 120ml', 4.000, 'Bottles', '01.06.2027', '2027-06-01', 410.00, 1640.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(29, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '15', 'Qarshi Guhtti 60ml', 10.000, 'Bottles', '01.01.2028', '2028-01-01', 130.00, 1300.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(30, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '16', 'Corezcol syrup 60ml', 10.000, 'Bottles', '01.12.2026', '2026-12-01', 130.00, 1300.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(31, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '17', 'Arq Kasni 800ml', 25.000, 'Bottles', '01.02.2028', '2028-02-01', 260.00, 6500.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(32, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '18', 'Anti Fat tablets', 26.000, 'Dabbi', '01.03.2027', '2027-03-01', 420.00, 10920.00, NULL, 'Placed in Cabinet # 02', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(33, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '19', 'Zarjam capsule', 21.000, 'Jars', '01.08.2026', '2026-08-01', 3600.00, 75600.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(34, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '20', 'Arq Sherian 240ml', 89.000, 'Bottles', '01.06.2028', '2028-06-01', 140.00, 12460.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(35, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '21', 'Arq Gulab Spray 120ml', 12.000, 'Bottles', '01.12.2026', '2026-12-01', 175.00, 2100.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(36, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '22', 'Arq Gulab 120ml', 34.000, 'Bottles', '01.04.2027', '2027-04-01', 90.00, 3060.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(37, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '23', 'Surficol tablets', 55.000, 'Box', '01.09.2028', '2028-09-01', 400.00, 22000.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(38, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '24', 'Toot Sia 120ml', 127.000, 'Bottles', '01.12.2028', '2028-12-01', 170.00, 21590.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(39, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '25', 'Foladi Syrup 120 ml', 42.000, 'Bottles', '01.04.2027', '2027-04-01', 190.00, 7980.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(40, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '26', 'Anjabar syrup 240 ml', 8.000, 'Bottles', '01.05.2027', '2027-05-01', 200.00, 1600.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(41, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '27', 'Corezcol syrup 60ml', 2.000, 'Bottles', '01.06.2027', '2027-06-01', 130.00, 260.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(42, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '28', 'Jawarish Jalinoos 100 gm', 25.000, 'Dabbi', '01.10.2028', '2028-10-01', 500.00, 12500.00, NULL, '//', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(43, 5, 'cabinets', 'Cabinets', '(Utilizable Stock)', '29', 'Arq Gulab Spray 120ml', 84.000, 'Bottles', '01.04.2028', '2028-04-01', 180.00, 15120.00, NULL, 'Placed in Cabinet # 03 (Back side)', NULL, NULL, '2026-09-24', 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(44, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '1', 'QJJ Regular (Export)', 2.000, 'Boxes (1x #30)', '01.02.2027', '2027-02-01', 0.00, 0.00, NULL, 'Export Canada', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(45, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '2', 'QJJ Export, Honey ,choclate, sugar free zinc', 11.000, 'Boxes (1x #30)', '01.09.2027', '2027-09-01', 0.00, 0.00, NULL, 'Export UK', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(46, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '3', 'QJJ Honey', 4.000, 'Boxes (1x #30)', '01.02.2027', '2027-02-01', 0.00, 0.00, NULL, 'Export Canada', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(47, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '4', 'QJJ sugar free', 3.000, 'Boxes (1x #30)', '01.02.2027', '2027-02-01', 0.00, 0.00, NULL, 'Export Canada', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(48, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '5', 'Arq Makoh 800ml', 5.000, 'Bottles', '01.08.2026', '2026-08-01', 0.00, 0.00, NULL, 'Export UK', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(49, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '6', 'Sharbat-e-Faulad 240ml', 1.000, 'Bottle', '01.09.2026', '2026-09-01', 0.00, 0.00, NULL, 'Export UK', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(50, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '7', 'QJJ Regular (Export)', 14.000, 'Bottles', '01.03.2026', '2026-03-01', 0.00, 0.00, NULL, 'Export Canadna', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(51, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '8', 'QJJ Honey (Export)', 14.000, 'Bottles', '01.08.2026', '2026-08-01', 0.00, 0.00, NULL, 'Export Canadna', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(52, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '9', 'QJJ Honey', 1.000, 'Boxes', '01.06.2029', '2029-06-01', 0.00, 0.00, NULL, 'Export Quality', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(53, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '10', 'QJJ Sugar Free', 2.000, 'Boxes', '01.06.2029', '2029-06-01', 0.00, 0.00, NULL, 'Export Quality', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(54, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '11', 'QJJ Regular', 3.000, 'Boxes', '01.02.2029', '2029-02-01', 0.00, 0.00, NULL, 'Export Quality', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(55, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '12', 'QJJ Regular', 6.000, 'Boxes', '01.06.2029', '2029-06-01', 0.00, 0.00, NULL, 'Export Quality', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(56, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '13', 'kasni water 800ml', 3.000, 'Bottles', '01.06.2028', '2028-06-01', 0.00, 0.00, NULL, 'UK Export', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(57, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '14', 'Fennel Water 800ml', 4.000, 'Bottles', '01.02.2028', '2028-02-01', 0.00, 0.00, NULL, 'UK Export', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(58, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '15', 'Borage water 800ml', 2.000, 'Bottles', '01.02.2028', '2028-02-01', 0.00, 0.00, NULL, 'UK Export', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(59, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '16', 'Choarqa water', 1.000, 'Bottles', '01.03.2028', '2028-03-01', 0.00, 0.00, NULL, 'UK Export', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(60, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '17', 'Roze water 800ml', 6.000, 'Bottles', '01.06.2028', '2028-06-01', 0.00, 0.00, NULL, 'UK Export', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(61, 5, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '18', 'Compost bags 2KG', 10.000, 'Bags', 'Not Found', NULL, 0.00, 0.00, NULL, 'Export Quality', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(62, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '1', 'Laal Sharbat 240ml', 150.000, 'Bottles', '01.04.2029', '2029-04-01', 280.00, 42000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(63, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '2', 'Safoof E Tabkhir', 205.000, 'Dabbi', '01.05.2029', '2029-05-01', 260.00, 53300.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(64, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '3', 'Akseer E Pechish', 220.000, 'Dabbi', '01.06.2029', '2029-06-01', 140.00, 30800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(65, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '4', 'Toot Siah 240ml', 120.000, 'Bottles', '01.04.2029', '2029-04-01', 280.00, 33600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(66, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '5', 'Allergies', 10.000, 'Dabbi', '01.06.2029', '2029-06-01', 300.00, 3000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(67, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '6', 'Akseer E Jigar', 135.000, 'Bottles', '01.06.2029', '2029-06-01', 250.00, 33750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(68, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '7', 'Asthamina Syp.', 28.000, 'Bottles', '01.06.2029', '2029-06-01', 190.00, 5320.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(69, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '8', 'Kardil Tab.', 5.000, 'Jars', '01.06.2029', '2029-06-01', 3300.00, 16500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(70, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '9', 'Goshina Drops', 16.000, 'Bottles', '01.06.2029', '2029-06-01', 140.00, 2240.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(71, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '10', 'Majoon Ard Khurma', 35.000, 'Dabbi', '01.05.2029', '2029-05-01', 220.00, 7700.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(72, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '11', 'Majoon Ard Khurma', 14.000, 'Jars', '01.05.2029', '2029-05-01', 700.00, 9800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(73, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '12', 'Majoon E Philaspha', 17.000, 'Jars', '01.04.2029', '2029-04-01', 1050.00, 17850.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(74, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '13', 'Habb E Muqil Khas', 32.000, 'Jars', '01.05.2029', '2029-05-01', 2000.00, 64000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(75, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '14', 'Mensofar Tab.', 5.000, 'Jars', '01.05.2029', '2029-05-01', 3600.00, 18000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(76, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '15', 'Musaffeen Tab.', 13.000, 'Jars', '01.04.2029', '2029-04-01', 2100.00, 27300.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(77, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '16', 'Musaffeen Syp.', 205.000, 'Bottles', '01.06.2029', '2029-06-01', 150.00, 30750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(78, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '17', 'Urosinal Syp.', 155.000, 'Bottles', '01.06.2028', '2028-06-01', 190.00, 29450.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(79, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '18', 'Sharbat E Faulad', 60.000, 'Bottles', '01.06.2029', '2029-06-01', 280.00, 16800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(80, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '19', 'Qarshi Ghutti', 80.000, 'Bottles', '01.03.2029', '2029-03-01', 130.00, 10400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(81, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '20', 'Zubex', 45.000, 'Dabbi', '29.03.2029', '2029-03-29', 360.00, 16200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(82, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '21', 'Piles X Oil', 51.000, 'Bottles', '01.04.2029', '2029-04-01', 240.00, 12240.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(83, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '22', 'Safoof E Mughallaj', 35.000, 'Dabbi', '01.01.2029', '2029-01-01', 360.00, 12600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(84, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '23', 'Habis Powder', 35.000, 'Dabbi', '01.02.2029', '2029-02-01', 290.00, 10150.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(85, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '24', 'Pileen Tab.', 5.000, 'Jars', '01.05.2029', '2029-05-01', 2100.00, 10500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(86, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '25', 'Habb E Gurda', 2.000, 'Jars', '01.04.2029', '2029-04-01', 2400.00, 4800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(87, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '26', 'Lecodine Tab.', 4.000, 'Jars', '01.05.2029', '2029-05-01', 1750.00, 7000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(88, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '27', 'Shafai Tab.', 6.000, 'Jars', '01.04.2029', '2029-04-01', 4000.00, 24000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(89, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '28', 'QJJ Regular', 50.000, 'Boxes', '01.11.2028', '2028-11-01', 750.00, 37500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(90, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '29', 'Ispaghol Husk', 60.000, 'Jars', '01.05.2029', '2029-05-01', 450.00, 27000.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(91, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '30', 'Surficol Tab.', 6.000, 'Boxes', '01.01.2029', '2029-01-01', 450.00, 2700.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(92, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '31', 'Surficol Syp.', 205.000, 'Bottles', '01.06.2029', '2029-06-01', 170.00, 34850.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(93, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '32', 'Fever X Syp.', 125.000, 'Bottles', '01.01.2029', '2029-01-01', 200.00, 25000.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(94, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '33', 'Khatooni Syp.', 160.000, 'Bottles', '01.05.2029', '2029-05-01', 160.00, 25600.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(95, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '34', 'Jawarish E Anareen', 20.000, 'Dabbi', '01.04.2029', '2029-04-01', 620.00, 12400.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(96, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '35', 'Asphatoon Tab.', 18.000, 'Jars', '01.04.2029', '2029-04-01', 3300.00, 59400.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(97, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '36', 'Khameera Gao Zaban', 10.000, 'Jars', '01.05.2029', '2029-05-01', 840.00, 8400.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(98, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '37', 'Jawarish E Zarooni', 9.000, 'Jars', '01.05.2029', '2029-05-01', 850.00, 7650.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(99, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '38', 'Itrifal Kashneez', 10.000, 'Jars', '01.04.2029', '2029-04-01', 700.00, 7000.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(100, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '39', 'Jawarish E Shahi', 10.000, 'Jars', '01.05.2029', '2029-05-01', 750.00, 7500.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(101, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '40', 'Corezcol Syp.', 110.000, 'Bottles', '01.06.2029', '2029-06-01', 140.00, 15400.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(102, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '41', 'Thandak Safoof', 55.000, 'Dabbi', '01.07.2029', '2029-07-01', 280.00, 15400.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(103, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '42', 'Habb E Tinkar Tab', 3.000, 'Jars', '01.05.2029', '2029-05-01', 1700.00, 5100.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(104, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '43', 'Jawarish E Amla', 35.000, 'Dabbi', '01.06.2029', '2029-06-01', 140.00, 4900.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(105, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '44', 'Jawarish E Kamooni', 7.000, 'Jars', '01.04.2029', '2029-04-01', 1000.00, 7000.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(106, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '45', 'Typhex Syp.', 30.000, 'Bottles', '01.06.2029', '2029-06-01', 140.00, 4200.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(107, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '46', 'BM 81 Tab.', 2.000, 'Jars', '01.06.2029', '2029-06-01', 800.00, 1600.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(108, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '47', 'Haiza Murgh Murakkab', 40.000, 'Dabbi', '01.06.2029', '2029-06-01', 140.00, 5600.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(109, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '48', 'Mensofar Tab.', 3.000, 'Jars', '01.04.2029', '2029-04-01', 3600.00, 10800.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(110, 5, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '49', 'Majoon E Najar', 35.000, 'Dabbi', '01.06.2029', '2029-06-01', 220.00, 7700.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(111, 5, 'tablet_strips', 'Tablet  Strips', 'Loos Packing', '1', 'Tablet Strips', 20022.000, 'Strips', 'Expired', NULL, NULL, NULL, 'Not Known', 'Loos Packing', 'Cartons', '7', NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(112, 5, 'tablet_strips', 'Tablet  Strips', 'Loos Packing', '2', 'Surficol Strips', 3428.000, 'Strips', 'Expired', NULL, NULL, NULL, 'Not Known', 'Loos Packing', 'Cartons', '1', NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(113, 5, 'tablet_strips', 'Tablet  Strips', 'Loos Packing', '3', 'Surficol Strips', 4375.000, 'Strips', '01.12.2027', '2027-12-01', NULL, NULL, 'Not Known', 'Damaged', 'Cartons', 'Loos Strip', NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(114, 5, 'tablet_strips', 'Tablet  Strips', 'Loos Packing', '4', 'Shahi + Genxing Strips', 980.000, 'Strips', 'Expired', NULL, NULL, NULL, 'Not Known', 'Loos Packing', 'Cartons', '1', NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(115, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '1', 'QJJ Regular', 1407.000, 'Boxes (1x 30)', '01.11.2027', '2027-11-01', 750.00, 1055250.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(116, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '2', 'QJJ Regular', 50.000, 'Boxes (1x 30)', '01.10.2027', '2027-10-01', 750.00, 37500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(117, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '3', 'QJJ Regular', 210.000, 'Boxes (1x 30)', '01.07.2029', '2029-07-01', 750.00, 157500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(118, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '4', 'QJJ Regular', 150.000, 'Boxes (1x 30)', '01.08.2029', '2029-08-01', 750.00, 112500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(119, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '5', 'QJJ Regular', 250.000, 'Boxes (1x 30)', '01.11.2028', '2028-11-01', 750.00, 187500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(120, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '6', 'QJJ Honey', 30.000, 'Boxes (1x 30)', '01.10.2027', '2027-10-01', 750.00, 22500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(121, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '7', 'QJJ Honey', 70.000, 'Boxes (1x 30)', '01.03.2029', '2029-03-01', 750.00, 52500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(122, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '8', 'QJJ Sugar Free', 40.000, 'Boxes (1x 30)', '01.10.2027', '2027-10-01', 750.00, 30000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(123, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '9', 'QJJ Sugar Free', 30.000, 'Boxes (1x 30)', '01.09.2028', '2028-09-01', 750.00, 22500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(124, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '10', 'QJJ Zinc', 30.000, 'Boxes (1x 30)', '01.10.2027', '2027-10-01', 750.00, 22500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(125, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '11', 'QJJ Zinc', 30.000, 'Boxes (1x 30)', '01.11.2028', '2028-11-01', 750.00, 22500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(126, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '12', 'QJJ Choc.', 22.000, 'Boxes (1x 30)', '01.10.2027', '2027-10-01', 750.00, 16500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(127, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '13', 'QJJ Choc.', 29.000, 'Boxes (1x 30)', '01.11.2028', '2028-11-01', 750.00, 21750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(128, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '14', 'QJJ Choc.', 30.000, 'Boxes (1x 30)', '01.12.2027', '2027-12-01', 750.00, 22500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(129, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '15', 'Dimaghi 100g', 15.000, 'Dabbi', '01.04.2029', '2029-04-01', 290.00, 4350.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(130, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '16', 'Dimaghi 100g', 30.000, 'Dabbi', '01.06.2029', '2029-06-01', 290.00, 8700.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(131, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '17', 'Dimaghi 100g', 15.000, 'Dabbi', '01.12.2028', '2028-12-01', 290.00, 4350.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(132, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '18', 'Toot Siah 240ml', 30.000, 'Bottles', '01.10.2027', '2027-10-01', 250.00, 7500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(133, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '19', 'Toot Siah 240ml', 30.000, 'Bottles', '01.04.2029', '2029-04-01', 280.00, 8400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(134, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '20', 'Toot Siah 240ml', 10.000, 'Bottles', '01.01.2030', '2030-01-01', 280.00, 2800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(135, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '21', 'Toot Siah 240ml', 30.000, 'Bottles', '01.01.2030', '2030-01-01', 280.00, 8400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(136, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '22', 'Toot Siah 120ml', 75.000, 'Bottles', '01.06.2029', '2029-06-01', 170.00, 12750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(137, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '23', 'Toot Siah 120ml', 60.000, 'Bottles', '01.03.2029', '2029-03-01', 170.00, 10200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(138, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '24', 'Toot Siah 120ml', 15.000, 'Bottles', '01.12.2028', '2028-12-01', 160.00, 2400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(139, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '25', 'Toot Siah 60ml', 58.000, 'Bottles', '01.12.2028', '2028-12-01', 130.00, 7540.00, NULL, 'Damaged', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(140, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '26', 'Toot Siah 120ml', 122.000, 'Bottles', '01.06.2028', '2028-06-01', 160.00, 19520.00, NULL, 'Damaged', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(141, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '27', 'Toot Siah 240ml', 1.000, 'Bottles', '01.03.2028', '2028-03-01', 250.00, 250.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(142, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '28', 'Sharbat Sadar', 1.000, 'Bottles', '01.03.2028', '2028-03-01', 220.00, 220.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(143, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '29', 'Genxing Capsules', 12.000, 'Dabbi', '01.02.2029', '2029-02-01', 900.00, 10800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(144, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '30', 'Shahi Capsules', 12.000, 'Dabbi', '01.01.2029', '2029-01-01', 480.00, 5760.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(145, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '31', 'Arq-e-Gulab Spray 120ml', 48.000, 'Bottles', '01.01.2028', '2028-01-01', 175.00, 8400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(146, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '32', 'Arq-e-Gulab Spray 120ml', 48.000, 'Bottles', '01.12.2027', '2027-12-01', 175.00, 8400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(147, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '33', 'Arq-e-Gulab 120ml', 12.000, 'Bottles', '01.07.2028', '2028-07-01', 95.00, 1140.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(148, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '34', 'Rogahn-e-Ojah 60ml', 40.000, 'Bottles', '01.03.2029', '2029-03-01', 250.00, 10000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(149, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '35', 'Arq-e-Gulab 800ml', 12.000, 'Bottles', '01.09.2028', '2028-09-01', 215.00, 2580.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(150, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '36', 'Arq-e-Gulab 800ml', 144.000, 'Bottles', '01.02.2028', '2028-02-01', 215.00, 30960.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(151, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '37', 'Arq-e-Gulab 800ml', 108.000, 'Bottles', '01.11.2027', '2027-11-01', 215.00, 23220.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(152, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '38', 'Ispaghol Husk 95g', 54.000, 'Jars', '01.01.2029', '2029-01-01', 850.00, 45900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(153, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '39', 'Ispaghol Husk 95g', 1.000, 'Jars', '01.01.2027', '2027-01-01', 675.00, 675.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(154, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '40', 'Ispaghol Husk 95g', 5.000, 'Jars', '01.04.2028', '2028-04-01', 800.00, 4000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(155, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '41', 'Ispaghol Husk 95g', 3.000, 'Jars', '01.09.2027', '2027-09-01', 800.00, 2400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(156, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '42', 'Ispaghol Husk 50g', 45.000, 'Dabbi', '01.09.2028', '2028-09-01', 390.00, 17550.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(157, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '43', 'Ispaghol Husk 50g', 30.000, 'Dabbi', '01.10.2027', '2027-10-01', 390.00, 11700.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(158, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '44', 'Ispaghol Husk A 140g', 51.000, 'Jars', '01.07.2028', '2028-07-01', 1250.00, 63750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(159, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '45', 'Laal Sharbat 240ml', 10.000, 'Bottles', '01.01.2029', '2029-01-01', 290.00, 2900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(160, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '46', 'Laal Sharbat 120ml', 60.000, 'Bottles', '01.04.2028', '2028-04-01', 190.00, 11400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(161, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '47', 'Laal Sharbat 120ml', 52.000, 'Bottles', '01.01.2028', '2028-01-01', 190.00, 9880.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(162, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '48', 'Sharbat-e-Faulad 240ml', 10.000, 'Bottles', '01.01.2029', '2029-01-01', 250.00, 2500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(163, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '49', 'Sharbat-e-Faulad 240ml', 40.000, 'Bottles', '01.02.2028', '2028-02-01', 250.00, 10000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(164, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '50', 'Sharbat-e-Faulad 240ml', 20.000, 'Bottles', '01.06.2028', '2028-06-01', 280.00, 5600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(165, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '51', 'Jawarish Kamoni 500gm', 3.000, 'Jars', '01.11.2027', '2027-11-01', 860.00, 2580.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(166, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '52', 'Laal Sharbat 120ml', 1.000, 'Bottles', '01.11.2029', '2029-11-01', 130.00, 130.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(167, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '53', 'Toot Siah 120ml', 2.000, 'Bottles', '01.12.2028', '2028-12-01', 160.00, 320.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(168, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '54', 'Toot Siah 120ml', 4.000, 'Bottles', '01.05.2028', '2028-05-01', 160.00, 640.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(169, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '55', 'Toot Siah 240ml', 6.000, 'Bottles', '01.01.2029', '2029-01-01', 280.00, 1680.00, NULL, 'Damaged', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(170, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '56', 'Toot Siah 240ml', 2.000, 'Bottles', '01.05.2028', '2028-05-01', 250.00, 500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(171, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '57', 'Jaryaneen Tab.', 3.000, 'Dabbi', '01.11.2028', '2028-11-01', 300.00, 900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(172, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '58', 'Musaffeen Syp.', 3.000, 'Bottles', '01.03.2028', '2028-03-01', 140.00, 420.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(173, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '59', 'Fauladi Tonic 120ml', 5.000, 'Bottles', '01.01.2028', '2028-01-01', 190.00, 950.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(174, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '60', 'Fouladi Tonic 45ml', 4.000, 'Bottles', '01.04.2029', '2029-04-01', 200.00, 800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(175, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '61', 'Sharbat E Faulad 240ml', 2.000, 'Bottles', '01.11.2027', '2027-11-01', 250.00, 500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(176, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '62', 'Pileen Tab.', 30.000, 'Dabbi', '01.07.2028', '2028-07-01', 260.00, 7800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(177, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '63', 'Lecodine Tab.', 15.000, 'Dabbi', '01.04.2028', '2028-04-01', 230.00, 3450.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(178, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '64', 'QJJ Regular', 40.000, 'Boxes', '01.11.2028', '2028-11-01', 750.00, 30000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(179, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '65', 'QJJ Regular', 336.000, 'Boxes', '01.06.2028', '2028-06-01', 750.00, 252000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(180, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '66', 'QJJ Regular', 55.000, 'Boxes', '01.09.2028', '2028-09-01', 750.00, 41250.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(181, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '67', 'Ispaghol Husk 25gm', 133.000, 'Boxes', '01.03.2028', '2028-03-01', 230.00, 30590.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(182, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '68', 'Ispaghol Husk 25gm', 100.000, 'Boxes', '01.04.2029', '2029-04-01', 230.00, 23000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(183, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '69', 'Surficol Tab.', 1.000, 'Boxes', '01.10.2027', '2027-10-01', 400.00, 400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(184, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '70', 'Surficol Tab.', 22.000, 'Boxes', '01.09.2028', '2028-09-01', 400.00, 8800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(185, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '71', 'Gestofil Syp. 120ml', 35.000, 'Bottles', '01.11.2028', '2028-11-01', 160.00, 5600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39');
INSERT INTO `market_return_items` (`id`, `division_id`, `category_code`, `source_sheet`, `section_name`, `serial_no`, `product_name`, `quantity`, `unit`, `expiry_raw`, `expiry_date`, `retail_price`, `total_amount`, `amount_text`, `remarks`, `aux_label`, `aux_value`, `report_date`, `import_id`, `created_by`, `created_at`, `updated_at`) VALUES
(186, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '72', 'Khameera Gao Zaban', 1.000, 'Dabbi', '01.12.2027', '2027-12-01', 720.00, 720.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(187, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '73', 'Habb E Kabd Noshadri Tab.', 8.000, 'Jars', '01.11.2028', '2028-11-01', 720.00, 5760.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(188, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '74', 'Habb E Muqil Khas Tab', 18.000, 'Jars', '01.05.2028', '2028-05-01', 1800.00, 32400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(189, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '75', 'Itrifal Kashneez Tab.', 4.000, 'Jars', '01.12.2028', '2028-12-01', 620.00, 2480.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(190, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '76', 'Kardil Tab.', 2.000, 'Jars', '01.11.2028', '2028-11-01', 2900.00, 5800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(191, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '77', 'Bukharin Tab.', 3.000, 'Jars', '01.11.2028', '2028-11-01', 2500.00, 7500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(192, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '78', 'Zubex Tab,', 90.000, 'Dabbi', '01.11.2028', '2028-11-01', 360.00, 32400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(193, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '79', 'Laal Sharbat Syp.', 10.000, 'Bottles', '01.11.2028', '2028-11-01', 290.00, 2900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(194, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '80', 'Majoon E Dabeed Ul Ward', 30.000, 'Jars', '01.11.2028', '2028-11-01', 1000.00, 30000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(195, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '81', 'Musaffeen Tab.', 50.000, 'Dabbi', '01.11.2028', '2028-11-01', 210.00, 10500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(196, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '82', 'Allergex', 44.000, 'Dabbi', '01.10.2028', '2028-10-01', 260.00, 11440.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(197, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '83', 'Majoon E Najah', 75.000, 'Dabbi', '01.12.2028', '2028-12-01', 200.00, 15000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(198, 5, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '84', 'Arq E Gulaab 800ml', 6.000, 'Bottles', '01.11.2027', '2027-11-01', 215.00, 1290.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(199, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '1', 'QJJ Zink', 84.000, 'Boxes (1x 30)', '01.06.2027', '2027-06-01', 750.00, 63000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(200, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '2', 'QJJ Zink', 29.000, 'Boxes (1x 30)', '01.09.2027', '2027-09-01', 750.00, 21750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(201, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '3', 'QJJ sugar free', 1.000, 'Boxes (1x 30)', '01.08.2027', '2027-08-01', 750.00, 750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(202, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '4', 'QJJ Honey', 10.000, 'Boxes (1x 30)', '01.08.2027', '2027-08-01', 750.00, 7500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(203, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '5', 'QJJ Honey', 60.000, 'Boxes (1x 30)', '01.08.2027', '2027-08-01', 750.00, 45000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(204, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '6', 'QJJ Regular', 154.000, 'Boxes (1x 30)', '01.08.2027', '2027-08-01', 750.00, 115500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(205, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '7', 'QJJ Regular', 108.000, 'Boxes (1x 30)', '01.06.2027', '2027-06-01', 750.00, 81000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(206, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '8', 'QJJ Regular', 26.000, 'Boxes (1x 30)', '01.07.2027', '2027-07-01', 750.00, 19500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(207, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '9', 'Ispagohle', 2.000, 'Box', '01.10.2026', '2026-10-01', 325.00, 650.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(208, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '10', 'Arq-e-Badian', 1.000, 'Bottle', '01.12.2026', '2026-12-01', 130.00, 130.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(209, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '11', 'Lal Sharbat 120ml', 60.000, 'Bottle', '01.12.2026', '2026-12-01', 100.00, 6000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(210, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '12', 'Lal Sharbat 120ml', 9.000, 'Bottle', '01.06.2027', '2027-06-01', 190.00, 1710.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(211, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '13', 'Lal Sharbat 240ml', 10.000, 'Bottle', '01.07.2027', '2027-07-01', 290.00, 2900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(212, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '14', 'Mumsik Sultani', 28.000, 'Bottle', '01.03.2027', '2027-03-01', 3480.00, 97440.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(213, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '15', 'Sharbat E Fulad 240 ml', 18.000, 'Bottle', '01.05.2027', '2027-05-01', 250.00, 4500.00, NULL, 'Damaged', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(214, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '16', 'Ispagohle 25 gm', 210.000, 'Box', '01.07.2027', '2027-07-01', 180.00, 37800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(215, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '17', 'Ispagohle 25 gm', 270.000, 'Box', '01.08.2027', '2027-08-01', 220.00, 59400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(216, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '18', 'Ispagohle 25 gm', 15.000, 'Box', '01.02.2027', '2027-02-01', 180.00, 2700.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(217, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '19', 'Ispagohle 50 gm', 15.000, 'Box', '01.02.2027', '2027-02-01', 325.00, 4875.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(218, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '20', 'Ispagohle 50 gm', 15.000, 'Box', '01.08.2027', '2027-08-01', 390.00, 5850.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(219, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '21', 'Ispagohle 50 gm', 75.000, 'Box', '01.07.2027', '2027-07-01', 390.00, 29250.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(220, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '22', 'Ispagohle 140 gm', 130.000, 'Jar', '01.07.2027', '2027-07-01', 1250.00, 162500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(221, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '23', 'Arq e-Gulab 800 ml', 48.000, 'Bottle', '01.12.2026', '2026-12-01', 215.00, 10320.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(222, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '24', 'Arq e-Gulab 120 ml Spray', 9.000, 'Bottle', '01.06.2027', '2027-06-01', 175.00, 1575.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(223, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '25', 'Musaffeen 120ml', 40.000, 'Bottle', '01.06.2027', '2027-06-01', 140.00, 5600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(224, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '26', 'Bukharin Tablet Jar', 707.000, 'Dabbi', '27.01.2027', '2027-01-27', 180.00, 127260.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(225, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '27', 'Dimaghi 100gm', 88.000, 'Dabbi', '01.06.2027', '2027-06-01', 290.00, 25520.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(226, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '28', 'Dimaghi 100gm', 15.000, 'Dabbi', '01.09.2027', '2027-09-01', 290.00, 4350.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(227, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '29', 'Dimaghi 250gm', 18.000, 'Jar', '01.06.2027', '2027-06-01', 550.00, 9900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(228, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '30', 'Dimaghi 500gm', 20.000, 'Jar', '01.08.2027', '2027-08-01', 860.00, 17200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(229, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '31', 'Toot Sia 240 ml', 40.000, 'Bottles', '01.04.2027', '2027-04-01', 250.00, 10000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(230, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '32', 'Toot Sia 240 ml', 15.000, 'Bottles', '01.08.2027', '2027-08-01', 250.00, 3750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(231, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '33', 'Toot Sia 240 ml', 150.000, 'Bottles', '01.07.2027', '2027-07-01', 250.00, 37500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(232, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '34', 'Toot Sia 60 ml', 7.000, 'Bottles', '01.05.2027', '2027-05-01', 130.00, 910.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(233, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '35', 'Genxing Cap', 14.000, 'Dabbi', '01.07.2027', '2027-07-01', 900.00, 12600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(234, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '36', 'Garlicare tablets', 447.000, 'Dabbi', '01.11.2026', '2026-11-01', 720.00, 321840.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(235, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '37', 'Gasto tablets', 15.000, 'Tables', '01.06.2027', '2027-06-01', 150.00, 2250.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(236, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '38', 'Gestofill syrup 120ml', 15.000, 'Bottles', '01.02.2027', '2027-02-01', 160.00, 2400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(237, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '39', 'Gestofill syrup 120ml', 295.000, 'Bottles', '01.07.2027', '2027-07-01', 160.00, 47200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(238, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '40', 'Gestofill syrup 120ml', 15.000, 'Bottles', '01.08.2027', '2027-08-01', 160.00, 2400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(239, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '41', 'Gesto Tablets', 202.000, 'Tables', '01.06.2027', '2027-06-01', 150.00, 30300.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(240, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '42', 'Gesto Tables', 30.000, 'Tables', '01.09.2027', '2027-09-01', 150.00, 4500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(241, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '43', 'Gesto Tables', 33.000, 'Tables', '01.07.2027', '2027-07-01', 150.00, 4950.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(242, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '44', 'Gesto Tables', 5.000, 'Tables', '01.08.2027', '2027-08-01', 150.00, 750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(243, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '45', 'Gestofill syrup', 15.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 2400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(244, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '46', 'Pathri Tore', 8.000, 'Tables', '01.03.2027', '2027-03-01', 4200.00, 33600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(245, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '47', 'Laboob-e Bard 500 gm', 14.000, 'Jars', '01.11.2026', '2026-11-01', 1620.00, 22680.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(246, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '48', 'Surficol Tab.', 22.000, 'Dabbi', '01.11.2026', '2026-11-01', 360.00, 7920.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(247, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '49', 'Surficol syrup 120ml', 29.000, 'Bottles', '01.05.2027', '2027-05-01', 160.00, 4640.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(248, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '50', 'Surficol syrup 120ml', 1380.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 220800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(249, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '51', 'Majoon Mughaliz Jawahar wali 100gm', 3.000, 'Bottles', '01.01.2027', '2027-01-01', 460.00, 1380.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(250, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '52', 'Mansofar Tablet', 5.000, 'Dabbi', '01.01.2027', '2027-01-01', 190.00, 950.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(251, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '53', 'Rogahn E Badam 30ml', 13.000, 'Bottles', '01.06.2027', '2027-06-01', 430.00, 5590.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(252, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '54', 'Toot Siah 240ml', 60.000, 'Bottles', '01.01.2027', '2027-01-01', 205.00, 12300.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(253, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '55', 'Toot Siah 240ml', 20.000, 'Bottles', '01.07.2027', '2027-07-01', 250.00, 5000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(254, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '56', 'Toot Siah 120ml', 15.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 2400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(255, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '57', 'Arq-e-Gulab Spray 120ml', 96.000, 'Bottles', '01.01.2027', '2027-01-01', 175.00, 16800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(256, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '58', 'Ispaghol Husk 50g', 75.000, 'Box', '01.08.2027', '2027-08-01', 390.00, 29250.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(257, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '59', 'Ispaghol Husk 25g', 15.000, 'Box', '01.08.2027', '2027-08-01', 220.00, 3300.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(258, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '60', 'Ispaghol Husk 25g', 100.000, 'Box', '01.05.2027', '2027-05-01', 180.00, 18000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(259, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '61', 'Ispaghol Husk 50g', 15.000, 'Box', '01.02.2027', '2027-02-01', 325.00, 4875.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(260, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '62', 'Ispaghol Husk 50g', 30.000, 'Box', '01.06.2027', '2027-06-01', 390.00, 11700.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(261, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '63', 'Ispaghol Husk 50g', 15.000, 'Box', '01.09.2027', '2027-09-01', 390.00, 5850.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(262, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '64', 'Ispaghol Husk 140g', 48.000, 'Jars', '01.06.2027', '2027-06-01', 1250.00, 60000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(263, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '65', 'Khatooni 120ml', 15.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 2400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(264, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '66', 'Laal Sharbat 240ml', 10.000, 'Bottles', '01.06.2027', '2027-06-01', 290.00, 2900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(265, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '67', 'Habb E Muqil Khas Tab.', 195.000, 'Dabbi', '01.12.2026', '2026-12-01', 100.00, 19500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(266, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '68', 'CMZ Tab.', 126.000, 'Dabbi', '01.10.2026', '2026-10-01', 900.00, 113400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(267, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '69', 'Corez col 60 ml', 112.000, 'Bottles', '01.06.2027', '2027-06-01', 130.00, 14560.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(268, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '70', 'Aksser-e Jiger 120ml', 4.000, 'Bottles', '01.04.2027', '2027-04-01', 190.00, 760.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(269, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '71', 'Arq Mako 240ml', 2.000, 'Bottles', '01.12.2026', '2026-12-01', 130.00, 260.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(270, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '72', 'Asthamina 120ml', 8.000, 'Bottles', '01.11.2026', '2026-11-01', 160.00, 1280.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(271, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '73', 'Goshina', 873.000, 'Bottles', '01.02.2027', '2027-02-01', 110.00, 96030.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(272, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '74', 'Sharbat Ambreen 240ml', 1.000, 'Bottles', '01.09.2027', '2027-09-01', 410.00, 410.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(273, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '75', 'Jawarish E Jalinoos', 6.000, 'Bottles', '01.01.2027', '2027-01-01', 420.00, 2520.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(274, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '76', 'Laal Sharbat 120ml', 45.000, 'Bottles', '01.12.2026', '2026-12-01', 150.00, 6750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(275, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '77', 'Gestofil Syp. 120ml', 174.000, 'Bottles', '01.01.2027', '2027-01-01', 160.00, 27840.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(276, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '78', 'Gestofil Tab.', 1.000, 'Dabbi', '01.08.2027', '2027-08-01', 150.00, 150.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(277, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '79', 'Habb E Muqil Khas', 15.000, 'Dabbi', '01.12.2026', '2026-12-01', 100.00, 1500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(278, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '80', 'Rogahn E Badam', 14.000, 'Bottles', '01.11.2026', '2026-11-01', 600.00, 8400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(279, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '81', 'Anti Fat Tab.', 4.000, 'Dabbi', '01.09.2027', '2027-09-01', 500.00, 2000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(280, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '82', 'Anti Fat Tab.', 5.000, 'Dabbi', '01.05.2027', '2027-05-01', 500.00, 2500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(281, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '83', 'Tone Up Syp.', 2.000, 'Bottles', '01.05.2027', '2027-05-01', 190.00, 380.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(282, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '84', 'Sadabahar Syp.', 2.000, 'Bottles', '01.07.2027', '2027-07-01', 190.00, 380.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(283, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '85', 'Taryak E Nazla', 2.000, 'Dabbi', '01.06.2027', '2027-06-01', 140.00, 280.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(284, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '86', 'Supari Pak Safoof', 14.000, 'Dabbi', '01.04.2028', '2028-04-01', 220.00, 3080.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(285, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '87', 'Safoof E Tabkhir', 8.000, 'Dabbi', '01.07.2027', '2027-07-01', 260.00, 2080.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(286, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '88', 'Laboob E Kabeer', 1.000, 'Dabbi', '01.01.2027', '2027-01-01', 540.00, 540.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(287, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '89', 'Laboob E Khas', 1.000, 'Dabbi', '01.01.2027', '2027-01-01', 900.00, 900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(288, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '90', 'Musaffeen Tab.', 1.000, 'Jars', '01.04.2029', '2029-04-01', 2100.00, 2100.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(289, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '91', 'Rogahn E Arand Syp.', 5.000, 'Bottles', '01.11.2028', '2028-11-01', 220.00, 1100.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(290, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '92', 'Musaffeen Syp. 120ml', 120.000, 'Bottles', '01.04.2027', '2027-04-01', 140.00, 16800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(291, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '93', 'Musaffeen Syp. 120ml', 6980.000, 'Bottles', '01.06.2027', '2027-06-01', 140.00, 977200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(292, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '94', 'Urosinal Syp.', 120.000, 'Bottles', '01.06.2028', '2028-06-01', 190.00, 22800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(293, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '95', 'Sharbat E Faulad', 2.000, 'Bottles', '01.01.2027', '2027-01-01', 250.00, 500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(294, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '96', 'Sharbat E Faulad', 8.000, 'Bottles', '01.07.2027', '2027-07-01', 250.00, 2000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(295, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '97', 'Surficol Tab.', 10.000, 'Bottles', '01.06.2027', '2027-06-01', 400.00, 4000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(296, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '98', 'Surficol Syp. 120ml Plus', 120.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 19200.00, NULL, 'Damaged', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(297, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '99', 'Surficol Syp. 120ml', 2570.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 411200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(298, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '100', 'Corezcol Syp. 60ml', 240.000, 'Bottles', '01.06.2027', '2027-06-01', 130.00, 31200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(299, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '101', 'Majoon Khabs Ul Hadeed', 24.000, 'Bottles', '01.08.2027', '2027-08-01', 200.00, 4800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(300, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '102', 'Zarjam Tab.', 4.000, 'Bottles', '01.01.2027', '2027-01-01', 4320.00, 17280.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(301, 5, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '103', 'Arq E Mako', 2.000, 'Bottles', '01.04.2027', '2027-04-01', 130.00, 260.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(302, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '1', 'Choarqah 800ml', 12.000, 'Bottles', 'Expired', NULL, 220.00, 2640.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(303, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '2', 'Arq-e-Gulab 120ml', 372.000, 'Bottles', '01.01.2026', '2026-01-01', 75.00, 27900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(304, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '3', 'Arq-e-Gulab 120ml', 30.000, 'Bottles', '01.10.2026', '2026-10-01', 90.00, 2700.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(305, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '4', 'Arq-e-Gulab 120ml', 3432.000, 'Bottles', '01.07.2026', '2026-07-01', 90.00, 308880.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(306, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '5', 'Arq-e-Gulab Spray 120ml', 372.000, 'Bottles', '01.01.2026', '2026-01-01', 145.00, 53940.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(307, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '6', 'Arq-e-Gulab Spray 120ml', 138.000, 'Bottles', '01.06.2026', '2026-06-01', 175.00, 24150.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(308, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '7', 'Arq-e-Gulab 800ml', 60.000, 'Bottles', '01.08.2026', '2026-08-01', 215.00, 12900.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(309, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '8', 'Almond oil', 10.000, 'Bottles', 'Expired', NULL, 430.00, 4300.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(310, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '9', 'Rogan-e-Ujah', 4.000, 'Bottles', '01.10.2026', '2026-10-01', 130.00, 520.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(311, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '10', 'Amrat Dhara', 1.000, 'Bottles', '01.01.2026', '2026-01-01', 150.00, 150.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(312, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '11', 'Arq-e-Gulab 800ml', 2.000, 'Bottles', '01.07.2025', '2025-07-01', 180.00, 360.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(313, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '12', 'Arq-e-Gulab 800ml', 2.000, 'Bottles', '01.11.2024', '2024-11-01', 150.00, 300.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(314, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '13', 'Arq-e-Gulab 800ml', 1.000, 'Bottles', '01.06.2024', '2024-06-01', 150.00, 150.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(315, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '14', 'Arq-e-Gulab 800ml', 24.000, 'Bottles', '01.01.2026', '2026-01-01', 180.00, 4320.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(316, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '15', 'Araq-shirin', 1.000, 'Bottles', '01.03.2020', '2020-03-01', 70.00, 70.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(317, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '16', 'Araq-shirin', 1.000, 'Bottles', '01.05.2025', '2025-05-01', 120.00, 120.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(318, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '17', 'Araq-shirin', 1.000, 'Bottles', '01.05.2023', '2023-05-01', 90.00, 90.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(319, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '18', 'Araq-shirin', 1.000, 'Bottles', '01.10.2024', '2024-10-01', 100.00, 100.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(320, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '19', 'Ispagohle', 9.000, 'Boxes', '01.10.2026', '2026-10-01', 325.00, 2925.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(321, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '20', 'Ispagohle 140 Gm', 10.000, 'Jars', '01.07.2026', '2026-07-01', 1050.00, 10500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(322, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '21', 'Ispagohle 95 Gm', 5.000, 'Jars', '01.01.2026', '2026-01-01', 640.00, 3200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(323, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '22', 'Ispagohle 95 Gm', 19.000, 'Jars', '01.10.2026', '2026-10-01', 675.00, 12825.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(324, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '23', 'Sharbat-e-Faulad 240ml', 46.000, 'Bottles', '01.06.2026', '2026-06-01', 205.00, 9430.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(325, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '24', 'Sharbat-e-Faulad 240ml', 11.000, 'Bottles', '01.09.2026', '2026-09-01', 150.00, 1650.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(326, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '25', 'Garlicare', 706.000, 'Dabbi', '01.09.2026', '2026-09-01', 600.00, 423600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(327, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '26', 'Garlicare', 1092.000, 'Box', '01.08.2026', '2026-08-01', 600.00, 655200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(328, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '27', 'Mussafeen', 15.000, 'Box', '01.10.2026', '2026-10-01', 170.00, 2550.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(329, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '28', 'Qarshi Gutti', 1.000, 'Bottles', '01.10.2026', '2026-10-01', 110.00, 110.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(330, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '29', 'Arq-e Badian 240ml', 6.000, 'Bottles', '01.02.2026', '2026-02-01', 110.00, 660.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(331, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '30', 'Arq-e Badian 800ml', 30.000, 'Bottles', '01.07.2026', '2026-07-01', 260.00, 7800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(332, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '31', 'Garlicare tablets', 144.000, 'Tablet', '01.08.2026', '2026-08-01', 600.00, 86400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(333, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '32', 'Garlicare tablets', 288.000, 'Tablet', '26.09.2026', '2026-09-26', 720.00, 207360.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(334, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '33', 'Arq-e-Gulab 60ml', 35.000, 'Bottles', '01.08.2026', '2026-08-01', 90.00, 3150.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(335, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '34', 'Arq-e-Gulab 800ml', 48.000, 'Bottles', '01.07.2026', '2026-07-01', 215.00, 10320.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(336, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '35', 'Arq-e-Gulab 800ml', 24.000, 'Bottles', '01.01.2026', '2026-01-01', 180.00, 4320.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(337, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '36', 'Arq-e-Gulab 800ml', 12.000, 'Bottles', '01.04.2026', '2026-04-01', 180.00, 2160.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(338, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '37', 'Aqseer-e Pachish', 15.000, 'Bottles', '01.08.2026', '2026-08-01', 110.00, 1650.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(339, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '38', 'Sharbat-e-Faulad 240ml', 19.000, 'Bottles', '01.05.2026', '2026-05-01', 250.00, 4750.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(340, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '39', 'Reno Tablet', 14.000, 'Dabbi', '01.10.2026', '2026-10-01', 160.00, 2240.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(341, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '40', 'Arq-e-Gulab 60ml', 60.000, 'Bottles', '01.07.2026', '2026-07-01', 90.00, 5400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(342, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '41', 'Murakab Abraq', 30.000, 'bottles', '01.12.2025', '2025-12-01', 120.00, 3600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(343, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '42', 'Majoon Azaraqi 500 gm', 3.000, 'bottles', '01.10.2024', '2024-10-01', 400.00, 1200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(344, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '43', 'Majoon e suranjan', 2.000, 'bottles', '01.10.2025', '2025-10-01', 580.00, 1160.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(345, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '44', 'Itrifal Zamani 500gm', 3.000, 'Bottles', '01.09.2025', '2025-09-01', 600.00, 1800.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(346, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '45', 'Reno Tablet', 2.000, 'Bottles', '01.10.2025', '2025-10-01', 1000.00, 2000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(347, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '46', 'Majoon eo Dabibul ward', 1.000, 'Bottles', '01.07.2025', '2025-07-01', 1000.00, 1000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(348, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '47', 'QJJ Sugar Free', 18.000, 'Boxes', '01.10.2025', '2025-10-01', 75.00, 1350.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(349, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '48', 'Arq Makoh', 12.000, 'Bottles', '01.07.2026', '2026-07-01', 260.00, 3120.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(350, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '49', 'Khameera Gao Zaban 50gm', 180.000, 'Dabbi', '01.06.2026', '2026-06-01', 240.00, 43200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(351, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '50', 'CMZ Tab.', 1335.000, 'Dabbi', '01.09.2026', '2026-09-01', 900.00, 1201500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(352, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '51', 'CMZ Tab.', 1044.000, 'Dabbi', '01.08.2026', '2026-08-01', 900.00, 939600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(353, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '52', 'Shahi Capsule', 528.000, 'Dabbi', '01.08.2026', '2026-08-01', 400.00, 211200.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(354, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '53', 'Genxing Capsule', 111.000, 'Dabbi', '01.08.2026', '2026-08-01', 750.00, 83250.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(355, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '54', 'Arq Goauzbaz 800ml', 1.000, 'Dabbi', '01.03.2025', '2025-03-01', 220.00, 220.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(356, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '55', 'Arq Kasni', 1.000, 'Dabbi', '01.09.2024', '2024-09-01', 180.00, 180.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(357, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '56', 'Khatooni 120ml', 75.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 12000.00, NULL, 'Fermentation', NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(358, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '57', 'Toneup 120ml', 6.000, 'Bottles', '01.07.2026', '2026-07-01', 190.00, 1140.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(359, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '58', 'Hab-e Gurda 500g', 2.000, 'Bottles', '01.06.2026', '2026-06-01', 1800.00, 3600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(360, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '59', 'Majoon Mughalaz 100g', 2.000, 'Bottles', '01.07.2026', '2026-07-01', 460.00, 920.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(361, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '60', 'Majoon E Mughallaz', 4.000, 'Bottles', '01.04.2026', '2026-04-01', 460.00, 1840.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(362, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '61', 'Arq E Maqoo 800ml', 12.000, 'Bottles', 'Expired', NULL, 260.00, 3120.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(363, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '62', 'QJJ Zinc', 198.000, 'Boxes', '01.07.2025', '2025-07-01', 750.00, 148500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(364, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '63', 'QJJ Regular', 386.000, 'Boxes', '01.06.2022', '2022-06-01', 750.00, 289500.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(365, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '64', 'QJJ Regular', 340.000, 'Boxes', '01.09.2023', '2023-09-01', 750.00, 255000.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(366, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '65', 'Arq E Mako 240ml', 44.000, 'Bottles', '01.03.2025', '2025-03-01', 110.00, 4840.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(367, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '66', 'Surficol Syp. 120ML', 60.000, 'Bottles', '01.06.2027', '2027-06-01', 160.00, 9600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(368, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '67', 'Garli Care Tab.', 216.000, 'Dabbi', '01.09.2026', '2026-09-01', 600.00, 129600.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(369, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '68', 'CMZ Tab.', 26.000, 'Dabbi', '01.09.2026', '2026-09-01', 900.00, 23400.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(370, 5, 'expired', 'Expired', 'Expired Stock - Market Return Store', '69', 'Piles X', 23.000, 'Bottles', '01.01.2026', '2026-01-01', 180.00, 4140.00, NULL, NULL, NULL, NULL, NULL, 8, 1, '2026-09-27 12:48:39', '2026-09-27 12:48:39'),
(371, 4, 'cabinets', 'Cabinets', '(Utilizable Stock)', '1', 'Honey 150 gm', 144.000, 'Jars', '01.02.2028', '2028-02-01', 290.00, 41760.00, NULL, 'Placed in Cabinet # 01', NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(372, 4, 'export', 'Export Stock', 'Export Quality Stock - Market Return Store', '1', 'Quince', 6.000, 'Nos', '01.09.2026', '2026-09-01', 0.00, 0.00, NULL, 'Export UK', NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(373, 4, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '1', 'Qarshi Honey 150gm', 108.000, 'Jars', '28.07.2028', '2028-07-28', 290.00, 31320.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(374, 4, 'rawalkot', 'RawalKot stock', 'Fresh Stock Saleable - For Rawlakot Distributors', '2', 'Qarshi Honey 450gm', 120.000, 'Jars', '01.05.2028', '2028-05-01', 700.00, 84000.00, NULL, 'Stock will be delivered to distributor after clearance of the roads', NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(375, 4, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '1', 'Qarshi Honey 450gm', 29.000, 'Jars', '01.12.2027', '2027-12-01', 700.00, 20300.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(376, 4, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '2', 'Qarshi Honey 450gm', 12.000, 'Jars', '01.05.2028', '2028-05-01', 700.00, 8400.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(377, 4, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '3', 'Carrot Preserve 430gm', 12.000, 'Jars', '01.11.2027', '2027-11-01', 300.00, 3600.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(378, 4, 'expired', 'Expired', 'Expired Stock - Market Return Store', '1', 'Orange Marmalad', 2.000, 'Jars', '01.07.2024', '2024-07-01', 160.00, 320.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(379, 4, 'expired', 'Expired', 'Expired Stock - Market Return Store', '2', 'Murabba Harar', 2.000, 'Jars', '01.11.2024', '2024-11-01', 250.00, 500.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(380, 4, 'expired', 'Expired', 'Expired Stock - Market Return Store', '3', 'Murabba amla', 1.000, 'Jar', '01.11.2024', '2024-11-01', 200.00, 200.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(381, 4, 'expired', 'Expired', 'Expired Stock - Market Return Store', '4', 'Murabba Carret', 2.000, 'Jar', '01.11.2024', '2024-11-01', 200.00, 400.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(382, 4, 'expired', 'Expired', 'Expired Stock - Market Return Store', '5', 'Murabba Apple', 1.000, 'Jar', '01.11.2024', '2024-11-01', 200.00, 200.00, NULL, NULL, NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13'),
(383, 4, 'expired', 'Expired', 'Expired Stock - Market Return Store', '6', 'Quince', 1.000, 'Jar', '01.10.2024', '2024-10-01', 0.00, 0.00, NULL, 'Export quality', NULL, NULL, NULL, 9, 1, '2026-09-27 13:51:13', '2026-09-27 13:51:13');
INSERT INTO `market_return_items` (`id`, `division_id`, `category_code`, `source_sheet`, `section_name`, `serial_no`, `product_name`, `quantity`, `unit`, `expiry_raw`, `expiry_date`, `retail_price`, `total_amount`, `amount_text`, `remarks`, `aux_label`, `aux_value`, `report_date`, `import_id`, `created_by`, `created_at`, `updated_at`) VALUES
(384, 2, 'fresh_saleable', 'Fresh Saleable Stock final', 'Fresh Saleable Stock - Market Return Store', '1', 'SNW 500ml', 26.000, 'Bottles', '01/11/2026', '2026-11-01', 50.00, 1300.00, NULL, NULL, NULL, NULL, '2026-09-25', 10, 1, '2026-09-27 14:54:19', '2026-09-27 14:54:19'),
(385, 2, 'utilizable', 'Utilizable Stock', 'Utilizable Stock - Market Return Store', '1', 'Springley 500ml', 24.000, 'Bottles', '01/01/2027', '2027-01-01', 50.00, 1200.00, NULL, NULL, NULL, NULL, '2026-09-24', 10, 1, '2026-09-27 14:54:19', '2026-09-27 14:54:19');

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
(4, 3, '120000200', 'Applsh (Demo Only)', 1, '2026-09-25 18:21:20'),
(5, 2, '100409', 'SPRINGLEY 500 ML (1*12)', 1, '2026-09-26 06:26:39'),
(6, 2, '100411', 'SPRINGLEY 1500 ML (1*6)', 1, '2026-09-26 06:26:39'),
(7, 2, '100684', 'SPRINGLEY BOTTLE 19LTR', 1, '2026-09-26 06:26:39'),
(8, 1, '100395', 'JAM-E-SHIRIN 1500ML', 1, '2026-09-26 18:54:16'),
(9, 1, '100399', 'SHARBAT SANDALEEN 800ML', 1, '2026-09-26 18:54:16'),
(10, 1, '100401', 'SHARBAT BAZOOREEN 800ML', 1, '2026-09-26 18:54:16'),
(11, 3, '500711', 'Applsh (Demo Only)', 1, '2026-09-27 11:37:50');

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
(7, 3, 11, '4560021', '2026-09-01', '2026-09-21', NULL, 50.000, 216000.00, 'PCS', 'RTD Store', '2026-09-25 13:21:20', '2026-09-27 11:37:50'),
(8, 2, 5, 'SP180926', '2026-09-18', '2027-09-18', NULL, 2085.000, 5000.00, 'PCS', 'FW02', '2026-09-26 01:26:39', '2026-09-26 01:28:18'),
(9, 2, 6, 'SP090926', '2026-09-09', '2027-09-09', NULL, 3230.000, 50000.00, 'PCS', 'FW02', '2026-09-26 01:26:39', '2026-09-26 01:28:18'),
(10, 2, 7, 'SB220926', '2026-09-22', '2027-09-22', NULL, 220.000, 50000.00, 'PCS', 'FW02', '2026-09-26 01:26:39', '2026-09-26 01:28:18'),
(11, 1, 1, 'JS020926A', '2026-02-09', '2030-02-08', NULL, 2150.000, 2150.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(12, 1, 1, 'JS020926B', '2026-02-09', '2030-02-08', NULL, 2732.000, 2732.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(13, 1, 8, 'JS210826B', '2026-08-21', '2030-07-21', NULL, 265.000, 265.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(14, 1, 2, 'JS220726A', '2026-07-22', '2030-06-22', NULL, 1958.000, 1958.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(15, 1, 2, 'JS210826B', '2026-08-21', '2030-07-21', NULL, 1849.000, 1849.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(16, 1, 3, 'JS200726A', '2026-07-20', '2030-06-20', NULL, 16.000, 16.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(17, 1, 3, 'JS200726B', '2026-07-20', '2030-06-20', NULL, 2380.000, 2380.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(18, 1, 3, 'JS220726A', '2026-07-22', '2030-06-22', NULL, 3453.000, 3453.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(19, 1, 3, 'JS220726B', '2026-07-22', '2030-06-22', NULL, 962.000, 962.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(20, 1, 9, 'SN080726', '2026-08-07', '2029-08-06', NULL, 4030.000, 4030.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16'),
(21, 1, 10, 'BN190526', '2026-05-19', '2029-04-19', NULL, 925.000, 1925.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-27 16:25:07'),
(22, 1, 10, 'BN200526', '2026-05-20', '2029-04-20', NULL, 1214.000, 1214.00, 'Ctn', 'JSD Dispatch', '2026-09-26 18:54:16', '2026-09-26 18:54:16');

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
(4, 2, 'Zubair', 'zubair@qarshi.com', '$2y$10$iPoaU.PZ4ELmFx0KiI0CgO5/rolOGxyUzsUShBybylXDF8SpUs.ZO', 'division_admin', NULL, 1, '2026-09-26 05:57:04'),
(5, 5, 'Faheem', 'faheem.ehsan90@gmail.com', '$2y$10$pXSC9zsyuTJa2U1..eBEb.rGnS/UZ0w2P25uoZEfE..vIKfje0Ydy', 'division_admin', NULL, 1, '2026-09-27 12:49:21');

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
-- Indexes for table `market_return_division_mappings`
--
ALTER TABLE `market_return_division_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mr_mapping` (`match_type`,`match_key`,`division_id`),
  ADD KEY `fk_mr_division_mapping_user` (`created_by`),
  ADD KEY `idx_mr_mapping_key` (`match_type`,`match_key`),
  ADD KEY `idx_mr_mapping_division` (`division_id`,`is_active`);

--
-- Indexes for table `market_return_imports`
--
ALTER TABLE `market_return_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_market_return_import_division` (`division_id`,`imported_at`),
  ADD KEY `idx_market_return_import_user` (`imported_by`,`imported_at`);

--
-- Indexes for table `market_return_items`
--
ALTER TABLE `market_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_market_return_item_user` (`created_by`),
  ADD KEY `idx_market_return_division_category` (`division_id`,`category_code`),
  ADD KEY `idx_market_return_product` (`product_name`),
  ADD KEY `idx_market_return_expiry` (`expiry_date`),
  ADD KEY `idx_market_return_report_date` (`report_date`),
  ADD KEY `idx_market_return_import` (`import_id`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `division_grade_settings`
--
ALTER TABLE `division_grade_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `grade_rules`
--
ALTER TABLE `grade_rules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `market_return_division_mappings`
--
ALTER TABLE `market_return_division_mappings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT for table `market_return_imports`
--
ALTER TABLE `market_return_imports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `market_return_items`
--
ALTER TABLE `market_return_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=386;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `stock_batches`
--
ALTER TABLE `stock_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `market_return_division_mappings`
--
ALTER TABLE `market_return_division_mappings`
  ADD CONSTRAINT `fk_mr_division_mapping_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mr_division_mapping_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `market_return_imports`
--
ALTER TABLE `market_return_imports`
  ADD CONSTRAINT `fk_market_return_import_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  ADD CONSTRAINT `fk_market_return_import_user` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `market_return_items`
--
ALTER TABLE `market_return_items`
  ADD CONSTRAINT `fk_market_return_item_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`),
  ADD CONSTRAINT `fk_market_return_item_import` FOREIGN KEY (`import_id`) REFERENCES `market_return_imports` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_market_return_item_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
