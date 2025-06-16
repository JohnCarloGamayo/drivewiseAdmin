-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2025 at 01:45 PM
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
(1, 'papsianrafael@gmail.com\r\n', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-16 02:20:49', '2025-06-16 05:19:15'),
(2, 'rafaeliantimothy11@gmail.com', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-15 18:20:49', '2025-06-15 19:16:37');

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
(17, 1, '642191', '2025-06-16 03:31:12', 1, '2025-06-16 03:30:52'),
(18, 2, '928447', '2025-06-16 05:41:09', 1, '2025-06-16 05:40:50'),
(19, 2, '166020', '2025-06-16 09:36:43', 1, '2025-06-16 09:36:08'),
(20, 2, '169635', '2025-06-16 09:46:21', 1, '2025-06-16 09:45:55'),
(21, 2, '721054', '2025-06-16 09:57:45', 1, '2025-06-16 09:57:17'),
(22, 2, '855300', '2025-06-16 10:21:27', 1, '2025-06-16 10:21:07'),
(23, 2, '864131', '2025-06-16 10:29:59', 1, '2025-06-16 10:29:43'),
(24, 2, '355576', '2025-06-16 10:31:05', 1, '2025-06-16 10:30:49'),
(25, 2, '965055', '2025-06-16 10:32:33', 1, '2025-06-16 10:32:22'),
(26, 2, '401890', '2025-06-16 10:35:58', 1, '2025-06-16 10:35:43'),
(27, 2, '598828', '2025-06-16 10:59:52', 1, '2025-06-16 10:59:35'),
(28, 2, '388636', '2025-06-16 11:38:14', 1, '2025-06-16 11:37:46'),
(29, 2, '696337', '2025-06-16 11:40:55', 1, '2025-06-16 11:40:43');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `xp_reward` int(11) DEFAULT 10,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `category` varchar(100) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 15,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `title`, `description`, `content`, `xp_reward`, `difficulty_level`, `category`, `duration_minutes`, `is_active`, `created_at`, `updated_at`, `order_index`) VALUES
(1, 'Basic Traffic Rules', 'Learn fundamental traffic rules and regulations', NULL, 10, 'beginner', 'Traffic Rules', 15, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 1),
(2, 'Road Signs Recognition', 'Identify and understand common road signs', NULL, 15, 'beginner', 'Road Signs', 20, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 2),
(3, 'Parking Techniques', 'Master parallel and perpendicular parking', NULL, 20, 'intermediate', 'Parking', 25, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 3),
(4, 'Highway Driving', 'Safe highway driving practices', NULL, 25, 'intermediate', 'Highway', 30, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 4),
(5, 'Night Driving', 'Techniques for safe night driving', NULL, 30, 'advanced', 'Advanced', 35, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 5),
(6, 'Weather Conditions', 'Driving in various weather conditions', NULL, 25, 'intermediate', 'Weather', 30, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 6),
(7, 'Emergency Procedures', 'What to do in emergency situations', NULL, 35, 'advanced', 'Emergency', 40, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 7),
(8, 'Vehicle Maintenance', 'Basic vehicle maintenance and checks', NULL, 20, 'beginner', 'Maintenance', 25, 1, '2025-06-16 10:06:11', '2025-06-16 10:06:11', 8);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `xp_points` int(11) DEFAULT 0,
  `level` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `xp_points`, `level`, `is_active`, `created_at`, `updated_at`, `last_login`, `profile_image`, `phone`, `date_of_birth`, `address`, `city`, `state`, `country`, `postal_code`) VALUES
(1, 'john_doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 150, 2, 1, '2024-01-15 02:30:00', '2025-06-16 10:06:11', '2024-01-20 06:22:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'jane_smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 320, 3, 1, '2024-01-10 01:15:00', '2025-06-16 10:06:11', '2024-01-21 08:45:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'mike_johnson', 'mike.johnson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Johnson', 75, 1, 1, '2024-01-18 03:20:00', '2025-06-16 10:06:11', '2024-01-19 05:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'sarah_wilson', 'sarah.wilson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Wilson', 480, 4, 1, '2024-01-05 00:45:00', '2025-06-16 10:06:11', '2024-01-21 02:15:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'david_brown', 'david.brown@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Brown', 25, 1, 0, '2024-01-20 07:30:00', '2025-06-16 10:06:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'lisa_garcia', 'lisa.garcia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Garcia', 200, 2, 1, '2024-01-12 04:00:00', '2025-06-16 10:06:11', '2024-01-20 01:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'robert_taylor', 'robert.taylor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert', 'Taylor', 350, 3, 1, '2024-01-08 06:15:00', '2025-06-16 10:06:11', '2024-01-21 03:20:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'emily_davis', 'emily.davis@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily', 'Davis', 120, 2, 1, '2024-01-16 08:45:00', '2025-06-16 10:06:11', '2024-01-20 07:10:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'chris_miller', 'chris.miller@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chris', 'Miller', 90, 1, 1, '2024-01-19 02:30:00', '2025-06-16 10:11:16', '2024-01-21 00:45:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'amanda_wilson', 'amanda.wilson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amanda', 'Wilson', 275, 3, 1, '2024-01-11 05:20:00', '2025-06-16 10:06:11', '2024-01-20 09:30:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_lessons`
--

CREATE TABLE `user_lessons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `score` int(11) DEFAULT NULL,
  `time_spent_minutes` int(11) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `attempts` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_lessons`
--

INSERT INTO `user_lessons` (`id`, `user_id`, `lesson_id`, `completed`, `score`, `time_spent_minutes`, `started_at`, `completed_at`, `attempts`) VALUES
(1, 1, 1, 1, 85, 18, '2025-06-16 10:06:11', '2024-01-15 03:00:00', 1),
(2, 1, 2, 1, 92, 22, '2025-06-16 10:06:11', '2024-01-15 04:30:00', 1),
(3, 1, 3, 0, NULL, 10, '2025-06-16 10:06:11', NULL, 1),
(4, 2, 1, 1, 95, 15, '2025-06-16 10:06:11', '2024-01-10 02:00:00', 1),
(5, 2, 2, 1, 88, 20, '2025-06-16 10:06:11', '2024-01-10 03:30:00', 1),
(6, 2, 3, 1, 90, 25, '2025-06-16 10:06:11', '2024-01-11 06:00:00', 1),
(7, 2, 4, 1, 87, 28, '2025-06-16 10:06:11', '2024-01-12 08:00:00', 1),
(8, 3, 1, 1, 78, 20, '2025-06-16 10:06:11', '2024-01-18 04:00:00', 2),
(9, 3, 2, 0, NULL, 15, '2025-06-16 10:06:11', NULL, 1),
(10, 4, 1, 1, 98, 14, '2025-06-16 10:06:11', '2024-01-05 01:30:00', 1),
(11, 4, 2, 1, 94, 18, '2025-06-16 10:06:11', '2024-01-05 03:00:00', 1),
(12, 4, 3, 1, 91, 24, '2025-06-16 10:06:11', '2024-01-06 05:30:00', 1),
(13, 4, 4, 1, 89, 29, '2025-06-16 10:06:11', '2024-01-07 07:00:00', 1),
(14, 4, 5, 1, 93, 33, '2025-06-16 10:06:11', '2024-01-08 02:30:00', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admin_users_email` (`email`);

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
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lessons_category` (`category`),
  ADD KEY `idx_lessons_difficulty` (`difficulty_level`),
  ADD KEY `idx_lessons_is_active` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_xp_points` (`xp_points`),
  ADD KEY `idx_users_level` (`level`),
  ADD KEY `idx_users_created_at` (`created_at`),
  ADD KEY `idx_users_is_active` (`is_active`);

--
-- Indexes for table `user_lessons`
--
ALTER TABLE `user_lessons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_lesson` (`user_id`,`lesson_id`),
  ADD KEY `idx_user_lessons_user_id` (`user_id`),
  ADD KEY `idx_user_lessons_lesson_id` (`lesson_id`),
  ADD KEY `idx_user_lessons_completed` (`completed`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_change_pins`
--
ALTER TABLE `email_change_pins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `email_tokens`
--
ALTER TABLE `email_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_lessons`
--
ALTER TABLE `user_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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

--
-- Constraints for table `user_lessons`
--
ALTER TABLE `user_lessons`
  ADD CONSTRAINT `user_lessons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_lessons_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
