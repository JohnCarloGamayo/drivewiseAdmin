-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 20, 2025 at 05:30 AM
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `layout_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_preferences`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `email`, `password`, `is_verified`, `created_at`, `updated_at`, `layout_preferences`) VALUES
(1, 'johncarlogamayo@gmail.com', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-16 02:20:49', '2025-06-16 09:47:56', NULL),
(2, 'rafaeliantimothy11@gmail.com', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-15 18:20:49', '2025-06-15 19:16:37', NULL),
(3, 'papsianrafael@gmail.com\r\n', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-15 18:20:49', '2025-06-15 21:19:15', NULL),
(4, 'harveyc634@gmail.com', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-15 10:20:49', '2025-06-15 11:16:37', NULL),
(5, 'rastynkhenacasio18@gmail.com', '$2y$10$c0bohylvTdJ8saibApXEN.AIcHqhYURv8dZxu3gABk/v/GzW/EFTG', 1, '2025-06-15 18:20:49', '2025-06-15 19:16:37', NULL);

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
(1, 1, '553031', 'johncarlogamayo@gmail.com', '2025-06-16 09:47:56', 1, '2025-06-16 02:57:13');

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
(18, 1, '028983', '2025-06-16 04:36:49', 1, '2025-06-16 04:36:39'),
(19, 1, '145034', '2025-06-16 05:36:18', 0, '2025-06-16 05:26:18'),
(20, 1, '101622', '2025-06-16 05:27:51', 1, '2025-06-16 05:27:17'),
(21, 2, '112540', '2025-06-16 05:39:46', 0, '2025-06-16 05:29:46'),
(22, 1, '065569', '2025-06-16 09:47:32', 1, '2025-06-16 09:47:16'),
(23, 1, '130027', '2025-06-16 12:15:26', 1, '2025-06-16 12:15:04'),
(24, 1, '561892', '2025-06-16 12:24:13', 1, '2025-06-16 12:24:02'),
(25, 1, '511768', '2025-06-16 12:30:15', 1, '2025-06-16 12:29:54'),
(26, 1, '379331', '2025-06-16 12:33:09', 1, '2025-06-16 12:32:59'),
(27, 1, '829099', '2025-06-16 12:49:49', 1, '2025-06-16 12:49:41'),
(28, 1, '994103', '2025-06-16 13:00:35', 1, '2025-06-16 13:00:25'),
(29, 1, '605959', '2025-06-16 13:08:48', 1, '2025-06-16 13:08:37'),
(30, 1, '477619', '2025-06-16 13:16:35', 1, '2025-06-16 13:16:24'),
(31, 1, '734607', '2025-06-16 13:29:51', 1, '2025-06-16 13:29:37'),
(32, 1, '821745', '2025-06-16 13:43:19', 1, '2025-06-16 13:43:09'),
(33, 1, '640334', '2025-06-17 02:14:20', 0, '2025-06-17 02:04:20'),
(34, 1, '802776', '2025-06-17 02:18:12', 0, '2025-06-17 02:08:12'),
(35, 1, '011584', '2025-06-17 02:23:04', 0, '2025-06-17 02:13:04'),
(36, 1, '325605', '2025-06-17 02:23:21', 0, '2025-06-17 02:13:21'),
(37, 1, '614553', '2025-06-17 02:23:32', 0, '2025-06-17 02:13:32'),
(38, 1, '160470', '2025-06-17 02:23:43', 0, '2025-06-17 02:13:43'),
(39, 1, '059588', '2025-06-17 02:30:03', 0, '2025-06-17 02:20:03'),
(40, 1, '413638', '2025-06-17 02:31:06', 0, '2025-06-17 02:21:06'),
(41, 1, '546163', '2025-06-17 02:31:17', 0, '2025-06-17 02:21:17'),
(42, 1, '522553', '2025-06-17 02:24:44', 1, '2025-06-17 02:24:14'),
(43, 1, '016375', '2025-06-17 02:35:07', 0, '2025-06-17 02:25:07'),
(44, 1, '802425', '2025-06-17 10:01:00', 1, '2025-06-17 10:00:43'),
(45, 5, '833093', '2025-06-18 02:08:53', 1, '2025-06-18 02:08:25'),
(46, 5, '700171', '2025-06-18 02:51:11', 0, '2025-06-18 02:41:11'),
(47, 5, '645518', '2025-06-18 02:53:24', 1, '2025-06-18 02:52:27'),
(48, 5, '988322', '2025-06-18 04:09:42', 1, '2025-06-18 04:09:05'),
(49, 5, '864300', '2025-06-18 04:17:11', 1, '2025-06-18 04:16:50'),
(50, 5, '134425', '2025-06-18 07:36:52', 1, '2025-06-18 07:36:31'),
(51, 5, '451503', '2025-06-18 15:22:16', 1, '2025-06-18 15:21:00'),
(52, 1, '483022', '2025-06-20 02:39:58', 1, '2025-06-20 02:39:44'),
(53, 1, '867471', '2025-06-20 02:54:45', 1, '2025-06-20 02:54:35'),
(54, 1, '314517', '2025-06-20 03:00:19', 1, '2025-06-20 03:00:07'),
(55, 1, '993298', '2025-06-20 03:09:11', 1, '2025-06-20 03:08:58');

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
(1, 'Basic Traffic Rules', 'Learn fundamental traffic rules and regulations', NULL, 10, 'beginner', 'Traffic Rules', 15, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 1),
(2, 'Road Signs Recognition', 'Identify and understand common road signs', NULL, 15, 'beginner', 'Road Signs', 20, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 2),
(3, 'Parking Techniques', 'Master parallel and perpendicular parking', NULL, 20, 'intermediate', 'Parking', 25, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 3),
(4, 'Highway Driving', 'Safe highway driving practices', NULL, 25, 'intermediate', 'Highway', 30, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 4),
(5, 'Night Driving', 'Techniques for safe night driving', NULL, 30, 'advanced', 'Advanced', 35, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 5),
(6, 'Weather Conditions', 'Driving in various weather conditions', NULL, 25, 'intermediate', 'Weather', 30, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 6),
(7, 'Emergency Procedures', 'What to do in emergency situations', NULL, 35, 'advanced', 'Emergency', 40, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 7),
(8, 'Vehicle Maintenance', 'Basic vehicle maintenance and checks', NULL, 20, 'beginner', 'Maintenance', 25, 1, '2025-06-16 02:06:11', '2025-06-16 02:06:11', 8);

-- --------------------------------------------------------

--
-- Table structure for table `notifications_sent`
--

CREATE TABLE `notifications_sent` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `recipient_type` enum('all','specific') NOT NULL,
  `recipient_user_id` int(11) DEFAULT NULL,
  `recipient_username` varchar(100) DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','delivered','read','failed') DEFAULT 'sent',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications_sent`
--

INSERT INTO `notifications_sent` (`id`, `title`, `message`, `recipient_type`, `recipient_user_id`, `recipient_username`, `recipient_email`, `sent_at`, `status`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to DriveWise!', 'Welcome to DriveWise! Start your learning journey today and improve your driving skills.', 'all', NULL, NULL, NULL, '2025-06-18 13:29:57', 'sent', NULL, '2025-06-18 19:29:57', '2025-06-18 19:29:57'),
(2, 'App Update', 'We have a new update to our app with [feature].  Please update to the latest version.', 'all', NULL, NULL, NULL, '2025-06-18 13:33:00', 'sent', NULL, '2025-06-18 19:33:00', '2025-06-18 19:33:00'),
(3, 'Weekly Progress Update', 'Check out your weekly progress and see how much you have improved this week!', 'specific', 8, 'lisa_garcia', 'lisa@example.com', '2025-06-18 13:33:46', 'sent', NULL, '2025-06-18 19:33:46', '2025-06-18 19:33:46'),
(4, 'Welcome to DriveWise!', 'Welcome to DriveWise mga ugok! Start your learning journey today and improve your driving skills.', 'all', NULL, NULL, NULL, '2025-06-19 19:32:59', 'sent', NULL, '2025-06-20 01:32:59', '2025-06-20 01:32:59');

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `name`, `title`, `message`, `created_at`, `updated_at`) VALUES
(1, 'Welcome Message', 'Welcome to DriveWise!', 'Welcome to DriveWise! Start your learning journey today and improve your driving skills.', '2025-06-18 04:16:15', '2025-06-18 04:16:15'),
(2, 'Weekly Update', 'Weekly Progress Update', 'Check out your weekly progress and see how much you have improved this week!', '2025-06-18 04:16:15', '2025-06-18 04:16:15'),
(3, 'Achievement Unlock', 'New Achievement Unlocked!', 'Congratulations! You have unlocked a new achievement. Keep up the great work!', '2025-06-18 04:16:15', '2025-06-18 04:16:15'),
(4, 'App Update', 'App Update', 'We have a new update to our app with [feature].  Please update to the latest version.', '2025-06-18 04:21:18', '2025-06-18 04:21:18');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `issue_type` enum('bug','feature_request','account_issue','payment_issue','technical_support','other') NOT NULL,
  `description` text NOT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','replied','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notification_read` tinyint(1) DEFAULT 0,
  `notification_read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `issue_type`, `description`, `screenshot_path`, `status`, `created_at`, `updated_at`, `notification_read`, `notification_read_at`) VALUES
(1, 1, 'bug', 'The app crashes when I try to upload a profile picture. This happens every time I select an image from my gallery.', NULL, 'replied', '2025-06-16 19:39:35', '2025-06-18 18:25:18', 1, '2025-06-18 12:25:18'),
(2, 2, 'feature_request', 'It would be great to have a dark mode option in the app. Many users prefer dark themes, especially for night usage.', NULL, 'replied', '2025-06-16 19:39:35', '2025-06-18 08:22:17', 0, NULL),
(3, 3, 'account_issue', 'I cannot log into my account. I keep getting an \"invalid credentials\" error even though I am sure my password is correct.', NULL, 'in_progress', '2025-06-16 19:39:35', '2025-06-18 08:22:17', 0, NULL),
(4, 1, 'payment_issue', 'My payment was charged twice for the premium subscription. I need a refund for the duplicate charge.', 'HFGHFGHF', 'replied', '2025-06-16 19:39:35', '2025-06-20 02:39:27', 1, '2025-06-18 13:31:12'),
(5, 4, 'technical_support', 'The GPS tracking is not working properly. It shows my location incorrectly and the route calculations are wrong.', NULL, 'resolved', '2025-06-16 19:39:35', '2025-06-18 16:00:47', 1, '2025-06-18 10:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `report_replies`
--

CREATE TABLE `report_replies` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_replies`
--

INSERT INTO `report_replies` (`id`, `report_id`, `admin_id`, `message`, `created_at`) VALUES
(1, 4, 1, 'We are currently investigating this issue and working on a solution. We will keep you updated on our progress and notify you once the issue has been resolved.', '2025-06-17 10:44:57'),
(2, 3, 1, 'This issue has been resolved. The fix has been implemented and should now be working properly. If you continue to experience any problems, please don\'t hesitate to contact us again.', '2025-06-17 10:45:05'),
(3, 3, 1, 'We need additional information to help resolve this issue. Could you please provide more details about when this problem occurs and any steps you took before encountering it?', '2025-06-17 10:45:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `birthday` date DEFAULT NULL,
  `play_time` int(11) DEFAULT 0,
  `quizzes_passed` int(11) DEFAULT 0,
  `modules_done` int(11) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notification_read` tinyint(1) DEFAULT 0,
  `notification_read_at` timestamp NULL DEFAULT NULL,
  `total_points` int(11) DEFAULT 0,
  `level` varchar(20) DEFAULT 'beginner'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `birthday`, `play_time`, `quizzes_passed`, `modules_done`, `points_earned`, `created_at`, `updated_at`, `notification_read`, `notification_read_at`, `total_points`, `level`) VALUES
(1, 'john_doe', 'john@example.com', '1995-03-15', 120, 5, 3, 75, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner'),
(2, 'jane_smith', 'jane@example.com', '1992-07-22', 450, 15, 8, 250, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner'),
(3, 'mike_wilson', 'mike@example.com', '1988-11-10', 890, 35, 20, 650, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner'),
(4, 'sarah_jones', 'sarah@example.com', '1997-01-05', 200, 8, 4, 150, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner'),
(5, 'alex_brown', 'alex@example.com', '1990-09-18', 1200, 50, 25, 800, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner'),
(6, 'emily_davis', 'emily@example.com', '1994-12-03', 60, 2, 1, 25, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner'),
(7, 'david_miller', 'david@example.com', '1991-06-28', 350, 12, 7, 320, '2025-06-16 12:48:05', '2025-06-18 16:01:19', 1, '2025-06-18 10:01:19', 0, 'beginner'),
(8, 'lisa_garcia', 'lisa@example.com', '1996-04-14', 180, 6, 3, 90, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner'),
(9, 'tom_anderson', 'tom@example.com', '1989-08-07', 750, 28, 15, 550, '2025-06-16 12:48:05', '2025-06-18 19:32:12', 1, '2025-06-18 13:32:12', 0, 'beginner'),
(10, 'anna_taylor', 'anna@example.com', '1993-02-20', 95, 3, 2, 45, '2025-06-16 12:48:05', '2025-06-18 08:22:17', 0, NULL, 0, 'beginner');

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
  `attempts` int(11) DEFAULT 1,
  `points_earned` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_lessons`
--

INSERT INTO `user_lessons` (`id`, `user_id`, `lesson_id`, `completed`, `score`, `time_spent_minutes`, `started_at`, `completed_at`, `attempts`, `points_earned`) VALUES
(1, 1, 1, 1, 85, 18, '2025-06-16 02:06:11', '2024-01-14 19:00:00', 1, 0),
(2, 1, 2, 1, 92, 22, '2025-06-16 02:06:11', '2024-01-14 20:30:00', 1, 0),
(3, 1, 3, 0, NULL, 10, '2025-06-16 02:06:11', NULL, 1, 0),
(4, 2, 1, 1, 95, 15, '2025-06-16 02:06:11', '2024-01-09 18:00:00', 1, 0),
(5, 2, 2, 1, 88, 20, '2025-06-16 02:06:11', '2024-01-09 19:30:00', 1, 0),
(6, 2, 3, 1, 90, 25, '2025-06-16 02:06:11', '2024-01-10 22:00:00', 1, 0),
(7, 2, 4, 1, 87, 28, '2025-06-16 02:06:11', '2024-01-12 00:00:00', 1, 0),
(8, 3, 1, 1, 78, 20, '2025-06-16 02:06:11', '2024-01-17 20:00:00', 2, 0),
(9, 3, 2, 0, NULL, 15, '2025-06-16 02:06:11', NULL, 1, 0),
(10, 4, 1, 1, 98, 14, '2025-06-16 02:06:11', '2024-01-04 17:30:00', 1, 0),
(11, 4, 2, 1, 94, 18, '2025-06-16 02:06:11', '2024-01-04 19:00:00', 1, 0),
(12, 4, 3, 1, 91, 24, '2025-06-16 02:06:11', '2024-01-05 21:30:00', 1, 0),
(13, 4, 4, 1, 89, 29, '2025-06-16 02:06:11', '2024-01-06 23:00:00', 1, 0),
(14, 4, 5, 1, 93, 33, '2025-06-16 02:06:11', '2024-01-07 18:30:00', 1, 0);

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
-- Indexes for table `notifications_sent`
--
ALTER TABLE `notifications_sent`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient_user` (`recipient_user_id`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_reports_admin_viewed` (`created_at`),
  ADD KEY `idx_reports_created_at` (`created_at`);

--
-- Indexes for table `report_replies`
--
ALTER TABLE `report_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_notification_read` (`created_at`),
  ADD KEY `idx_users_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `email_change_pins`
--
ALTER TABLE `email_change_pins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `email_tokens`
--
ALTER TABLE `email_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `notifications_sent`
--
ALTER TABLE `notifications_sent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `report_replies`
--
ALTER TABLE `report_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- Constraints for table `report_replies`
--
ALTER TABLE `report_replies`
  ADD CONSTRAINT `fk_report_replies_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_replies_report_id` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
