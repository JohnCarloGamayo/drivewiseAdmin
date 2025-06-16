-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2025 at 05:57 AM
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
-- Database: `drivewise_admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `email`, `password`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 'johncarlogamayo@gmail.com', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-16 02:20:49', '2025-06-16 03:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `email_change_pins`
--

CREATE TABLE `email_change_pins` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `pin` varchar(6) NOT NULL,
  `new_email` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_change_pins`
--

INSERT INTO `email_change_pins` (`id`, `admin_id`, `pin`, `new_email`, `expires_at`, `used`, `created_at`) VALUES
(1, 1, '754074', 'johncarlogayo@gmail.com', '2025-06-16 03:41:30', 0, '2025-06-16 02:57:13');

-- --------------------------------------------------------

--
-- Table structure for table `email_tokens`
--

CREATE TABLE `email_tokens` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_tokens`
--

INSERT INTO `email_tokens` (`id`, `admin_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 1, '559782', '2025-06-16 02:32:03', 0, '2025-06-16 02:22:03'),
(2, 1, '484176', '2025-06-16 02:32:20', 0, '2025-06-16 02:22:20'),
(3, 1, '636845', '2025-06-16 02:33:06', 0, '2025-06-16 02:23:06'),
(4, 1, '400661', '2025-06-16 02:28:56', 1, '2025-06-16 02:28:30'),
(5, 1, '448782', '2025-06-16 02:30:57', 1, '2025-06-16 02:30:37'),
(6, 1, '920781', '2025-06-16 02:40:50', 1, '2025-06-16 02:40:08'),
(7, 1, '022565', '2025-06-16 02:41:44', 1, '2025-06-16 02:41:22'),
(8, 1, '087154', '2025-06-16 02:42:45', 1, '2025-06-16 02:42:32'),
(9, 1, '533609', '2025-06-16 02:56:39', 1, '2025-06-16 02:56:23'),
(10, 1, '125779', '2025-06-16 03:01:58', 1, '2025-06-16 03:01:41'),
(11, 1, '286223', '2025-06-16 03:10:01', 1, '2025-06-16 03:09:49'),
(12, 1, '433179', '2025-06-16 03:14:45', 1, '2025-06-16 03:14:33'),
(13, 1, '925955', '2025-06-16 03:20:04', 1, '2025-06-16 03:19:34'),
(14, 1, '203952', '2025-06-16 03:23:52', 1, '2025-06-16 03:23:44'),
(15, 1, '793480', '2025-06-16 03:27:19', 1, '2025-06-16 03:27:09'),
(16, 1, '210022', '2025-06-16 03:29:49', 1, '2025-06-16 03:29:27'),
(17, 1, '642191', '2025-06-16 03:31:12', 1, '2025-06-16 03:30:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `email_change_pins`
--
ALTER TABLE `email_change_pins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_admin_pin` (`admin_id`);

--
-- Indexes for table `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_change_pins`
--
ALTER TABLE `email_change_pins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `email_tokens`
--
ALTER TABLE `email_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_change_pins`
--
ALTER TABLE `email_change_pins`
  ADD CONSTRAINT `email_change_pins_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_tokens`
--
ALTER TABLE `email_tokens`
  ADD CONSTRAINT `email_tokens_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
