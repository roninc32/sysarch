-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 06:37 AM
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
-- Database: `user_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_sit_ins`
--

CREATE TABLE `active_sit_ins` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sit_in_purpose` varchar(255) NOT NULL,
  `lab_number` varchar(50) NOT NULL,
  `pc_number` varchar(20) DEFAULT NULL,
  `login_time` time NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`) VALUES
(2, '', 'The College of Computer Studies will open the registration of students for the Sit-in privilege starting tomorrow. Thank you! Lab Supervisor', '2025-03-11 03:23:29'),
(4, '', 'Important Announcement We are excited to announce the launch of our new website! 🎉 Explore our latest products and services now!', '2025-03-11 03:29:08'),
(5, '', 'Make an amazing announcement for yourself quickly & easily in minutes with our free online announcement maker Customize a template or create a striking announcement from scratch to get the word out there about your business.', '2025-03-11 03:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `lab_number` varchar(10) NOT NULL,
  `rating` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `had_issues` tinyint(1) DEFAULT 0,
  `issues_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `reservation_id`, `user_id`, `lab_number`, `rating`, `message`, `had_issues`, `issues_description`, `created_at`) VALUES
(1, 24, '1', '524', 5, 'fasfaf', 0, '', '2025-04-27 04:54:49'),
(2, 23, '1', '524', 4, 'dvsrwe', 0, '', '2025-04-27 05:05:56'),
(3, 26, '2', '524', 5, 'orayt', 0, '', '2025-04-27 10:43:15');

-- --------------------------------------------------------

--
-- Table structure for table `pc_sessions`
--

CREATE TABLE `pc_sessions` (
  `id` int(11) NOT NULL,
  `pc_id` varchar(20) NOT NULL,
  `lab_number` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pc_status`
--

CREATE TABLE `pc_status` (
  `id` int(11) NOT NULL,
  `pc_id` varchar(20) NOT NULL,
  `lab_number` varchar(50) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pc_status`
--

INSERT INTO `pc_status` (`id`, `pc_id`, `lab_number`, `is_available`, `last_updated`) VALUES
(1, 'PC 2', '524', 0, '2025-04-27 22:13:26'),
(2, 'PC 3', '524', 0, '2025-04-27 22:13:28'),
(3, 'PC 1', '524', 0, '2025-04-27 22:13:23'),
(4, 'PC 4', '524', 0, '2025-04-27 22:13:30'),
(5, 'PC 5', '524', 0, '2025-04-27 22:13:33'),
(6, 'PC 6', '524', 0, '2025-04-29 01:53:10'),
(7, 'PC 7', '524', 0, '2025-04-29 01:53:10'),
(8, 'PC 8', '524', 0, '2025-04-29 01:53:10'),
(9, 'PC 9', '524', 0, '2025-04-29 01:53:10'),
(10, 'PC 10', '524', 0, '2025-04-29 01:53:10'),
(11, 'PC 11', '524', 0, '2025-04-29 01:53:10'),
(12, 'PC 12', '524', 0, '2025-04-29 01:53:10'),
(13, 'PC 13', '524', 0, '2025-04-29 01:53:10'),
(14, 'PC 14', '524', 0, '2025-04-29 01:53:10'),
(15, 'PC 15', '524', 0, '2025-04-29 01:53:10'),
(16, 'PC 16', '524', 0, '2025-04-29 01:53:10'),
(17, 'PC 17', '524', 0, '2025-04-29 01:53:11'),
(18, 'PC 18', '524', 0, '2025-04-29 01:53:11'),
(19, 'PC 19', '524', 0, '2025-04-29 01:53:11'),
(20, 'PC 20', '524', 0, '2025-04-29 01:53:11'),
(21, 'PC 21', '524', 0, '2025-04-29 01:53:11'),
(22, 'PC 22', '524', 0, '2025-04-29 01:53:11'),
(23, 'PC 23', '524', 0, '2025-04-29 01:53:11'),
(24, 'PC 24', '524', 0, '2025-04-29 01:53:11'),
(25, 'PC 25', '524', 0, '2025-04-29 01:53:11'),
(26, 'PC 26', '524', 0, '2025-04-29 01:53:11'),
(27, 'PC 27', '524', 0, '2025-04-29 01:53:11'),
(28, 'PC 28', '524', 0, '2025-04-29 01:53:11'),
(29, 'PC 29', '524', 0, '2025-04-29 01:53:11'),
(30, 'PC 30', '524', 0, '2025-04-29 01:53:11'),
(31, 'PC 31', '524', 0, '2025-04-29 01:53:11'),
(32, 'PC 32', '524', 0, '2025-04-29 01:53:11'),
(33, 'PC 33', '524', 0, '2025-04-29 01:53:11'),
(34, 'PC 34', '524', 0, '2025-04-29 01:53:11'),
(35, 'PC 35', '524', 0, '2025-04-29 01:53:11'),
(36, 'PC 36', '524', 0, '2025-04-29 01:53:11'),
(37, 'PC 37', '524', 0, '2025-04-29 01:53:11'),
(38, 'PC 38', '524', 0, '2025-04-29 01:53:11'),
(39, 'PC 39', '524', 0, '2025-04-29 01:53:12'),
(40, 'PC 40', '524', 0, '2025-04-29 01:53:12'),
(41, 'PC 41', '524', 0, '2025-04-29 01:53:12'),
(42, 'PC 42', '524', 0, '2025-04-29 01:53:12'),
(43, 'PC 43', '524', 0, '2025-04-29 01:53:12'),
(44, 'PC 44', '524', 0, '2025-04-29 01:53:12'),
(45, 'PC 45', '524', 0, '2025-04-29 01:53:12'),
(46, 'PC 46', '524', 1, '2025-04-29 03:02:54'),
(47, 'PC 47', '524', 1, '2025-04-29 03:02:55'),
(48, 'PC 48', '524', 1, '2025-04-29 03:02:56'),
(49, 'PC 49', '524', 1, '2025-04-29 03:02:57'),
(50, 'PC 50', '524', 1, '2025-04-29 03:02:57'),
(51, 'PC 1', '526', 1, '2025-04-29 03:05:23'),
(52, 'PC 2', '526', 1, '2025-04-29 03:05:25'),
(53, 'PC 5', '526', 1, '2025-04-29 03:05:28'),
(54, 'PC 6', '526', 0, '2025-04-29 03:05:19'),
(55, 'PC 4', '526', 1, '2025-04-29 03:05:27'),
(56, 'PC 3', '526', 1, '2025-04-29 03:05:26'),
(57, 'PC 7', '526', 0, '2025-04-29 03:05:20'),
(58, 'PC 8', '526', 0, '2025-04-29 03:05:20'),
(59, 'PC 9', '526', 0, '2025-04-29 03:05:20'),
(60, 'PC 10', '526', 0, '2025-04-29 03:05:20'),
(61, 'PC 11', '526', 0, '2025-04-29 03:05:20'),
(62, 'PC 12', '526', 0, '2025-04-29 03:05:20'),
(63, 'PC 13', '526', 0, '2025-04-29 03:05:20'),
(64, 'PC 14', '526', 0, '2025-04-29 03:05:20'),
(65, 'PC 15', '526', 0, '2025-04-29 03:05:20'),
(66, 'PC 16', '526', 0, '2025-04-29 03:05:20'),
(67, 'PC 17', '526', 0, '2025-04-29 03:05:20'),
(68, 'PC 18', '526', 0, '2025-04-29 03:05:20'),
(69, 'PC 19', '526', 0, '2025-04-29 03:05:20'),
(70, 'PC 20', '526', 0, '2025-04-29 03:05:20'),
(71, 'PC 21', '526', 0, '2025-04-29 03:05:20'),
(72, 'PC 22', '526', 0, '2025-04-29 03:05:20'),
(73, 'PC 23', '526', 0, '2025-04-29 03:05:21'),
(74, 'PC 24', '526', 0, '2025-04-29 03:05:21'),
(75, 'PC 25', '526', 0, '2025-04-29 03:05:21'),
(76, 'PC 26', '526', 0, '2025-04-29 03:05:21'),
(77, 'PC 27', '526', 0, '2025-04-29 03:05:21'),
(78, 'PC 28', '526', 0, '2025-04-29 03:05:21'),
(79, 'PC 29', '526', 0, '2025-04-29 03:05:21'),
(80, 'PC 30', '526', 0, '2025-04-29 03:05:21'),
(81, 'PC 31', '526', 0, '2025-04-29 03:05:21'),
(82, 'PC 32', '526', 0, '2025-04-29 03:05:21'),
(83, 'PC 33', '526', 0, '2025-04-29 03:05:22'),
(84, 'PC 34', '526', 0, '2025-04-29 03:05:22'),
(85, 'PC 35', '526', 0, '2025-04-29 03:05:22'),
(86, 'PC 36', '526', 0, '2025-04-29 03:05:22'),
(87, 'PC 37', '526', 0, '2025-04-29 03:05:22'),
(88, 'PC 38', '526', 0, '2025-04-29 03:05:22'),
(89, 'PC 39', '526', 0, '2025-04-29 03:05:22'),
(90, 'PC 40', '526', 0, '2025-04-29 03:05:22'),
(91, 'PC 41', '526', 0, '2025-04-29 03:05:22'),
(92, 'PC 42', '526', 0, '2025-04-29 03:05:22'),
(93, 'PC 43', '526', 0, '2025-04-29 03:05:22'),
(94, 'PC 44', '526', 0, '2025-04-29 03:05:22'),
(95, 'PC 45', '526', 0, '2025-04-29 03:05:22'),
(96, 'PC 46', '526', 0, '2025-04-29 03:05:23'),
(97, 'PC 47', '526', 0, '2025-04-29 03:05:23'),
(98, 'PC 48', '526', 0, '2025-04-29 03:05:23'),
(99, 'PC 49', '526', 0, '2025-04-29 03:05:23'),
(100, 'PC 50', '526', 0, '2025-04-29 03:05:23');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sit_in_purpose` varchar(255) NOT NULL,
  `lab_number` varchar(50) NOT NULL,
  `pc_number` varchar(20) DEFAULT NULL,
  `login_time` time NOT NULL,
  `logout_time` time DEFAULT NULL,
  `date` date NOT NULL,
  `has_feedback` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'pending',
  `admin_comments` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `id_number`, `name`, `sit_in_purpose`, `lab_number`, `pc_number`, `login_time`, `logout_time`, `date`, `has_feedback`, `status`, `admin_comments`, `processed_at`, `created_at`) VALUES
(21, '1', 'Ronin cabusao', 'C programming', '524', NULL, '13:50:05', '13:55:46', '2025-03-19', 0, 'pending', NULL, NULL, '2025-04-27 20:29:03'),
(22, '1', 'Ronin cabusao', 'Java programming', '526', NULL, '13:57:46', '13:58:04', '2025-03-19', 0, 'pending', NULL, NULL, '2025-04-27 20:29:03'),
(23, '1', 'Ronin cabusao', 'C programming', '524', NULL, '18:10:44', '18:10:48', '2025-03-19', 1, 'pending', NULL, NULL, '2025-04-27 20:29:03'),
(24, '1', 'Ronin cabusao', 'C programming', '524', NULL, '22:39:07', '22:39:15', '2025-04-23', 1, 'pending', NULL, NULL, '2025-04-27 20:29:03'),
(25, '2', 'John Doe', 'C programming', '524', NULL, '00:01:59', '01:07:10', '2025-04-24', 0, 'pending', NULL, NULL, '2025-04-27 20:29:03'),
(26, '2', 'John Doe', 'C programming', '524', NULL, '12:42:41', '12:42:53', '2025-04-27', 1, 'pending', NULL, NULL, '2025-04-27 20:29:03'),
(27, '1', 'Ronin cabusao', 'C programming', 'Mac Laboratory', NULL, '13:15:33', '13:15:54', '2025-04-27', 0, 'pending', NULL, NULL, '2025-04-27 20:29:03'),
(28, '1', 'Ronin cabusao', 'C Programming', '526', NULL, '08:40:00', '00:00:00', '2025-04-28', 0, 'pending', NULL, NULL, '2025-04-27 20:38:53'),
(29, '1', 'Ronin cabusao', 'C Programming', '526', NULL, '08:40:00', '00:43:28', '2025-04-28', 0, 'pending', NULL, NULL, '2025-04-27 22:43:28'),
(30, '1', 'Ronin cabusao', 'C programming', '524', NULL, '00:44:39', NULL, '2025-04-28', 0, 'pending', NULL, NULL, '2025-04-27 22:44:39'),
(31, '1', 'Ronin cabusao', 'C programming', '524', NULL, '00:44:39', '00:45:16', '2025-04-28', 0, 'pending', NULL, NULL, '2025-04-27 22:45:16');

-- --------------------------------------------------------

--
-- Table structure for table `reservations_backup`
--

CREATE TABLE `reservations_backup` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sit_in_purpose` varchar(255) NOT NULL,
  `lab_number` varchar(50) NOT NULL,
  `login_time` time NOT NULL,
  `logout_time` time NOT NULL,
  `date` date NOT NULL,
  `has_feedback` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'pending',
  `admin_comments` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations_backup`
--

INSERT INTO `reservations_backup` (`id`, `id_number`, `name`, `sit_in_purpose`, `lab_number`, `login_time`, `logout_time`, `date`, `has_feedback`, `status`, `admin_comments`, `processed_at`) VALUES
(21, '1', 'Ronin cabusao', 'C programming', '524', '13:50:05', '13:55:46', '2025-03-19', 0, 'pending', NULL, NULL),
(22, '1', 'Ronin cabusao', 'Java programming', '526', '13:57:46', '13:58:04', '2025-03-19', 0, 'pending', NULL, NULL),
(23, '1', 'Ronin cabusao', 'C programming', '524', '18:10:44', '18:10:48', '2025-03-19', 1, 'pending', NULL, NULL),
(24, '1', 'Ronin cabusao', 'C programming', '524', '22:39:07', '22:39:15', '2025-04-23', 1, 'pending', NULL, NULL),
(25, '2', 'John Doe', 'C programming', '524', '00:01:59', '01:07:10', '2025-04-24', 0, 'pending', NULL, NULL),
(26, '2', 'John Doe', 'C programming', '524', '12:42:41', '12:42:53', '2025-04-27', 1, 'pending', NULL, NULL),
(27, '1', 'Ronin cabusao', 'C programming', 'Mac Laboratory', '13:15:33', '13:15:54', '2025-04-27', 0, 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sitin_reservation`
--

CREATE TABLE `sitin_reservation` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `laboratory` varchar(50) NOT NULL,
  `pc_number` varchar(50) DEFAULT NULL,
  `time_in` time NOT NULL,
  `date` date NOT NULL,
  `remaining_session` int(11) DEFAULT 30,
  `status` varchar(20) DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `processed_date` timestamp NULL DEFAULT NULL,
  `admin_comments` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_reservation`
--

INSERT INTO `sitin_reservation` (`id`, `student_name`, `purpose`, `laboratory`, `pc_number`, `time_in`, `date`, `remaining_session`, `status`, `admin_comment`, `processed_date`, `admin_comments`, `processed_at`) VALUES
(1, 'Ronin  cabusao', 'C# Programming', '530', NULL, '17:06:00', '2025-04-28', 29, 'pending', NULL, NULL, NULL, NULL),
(2, 'Ronin  cabusao', 'C# Programming', '524', NULL, '18:28:00', '2025-04-29', 28, 'pending', NULL, NULL, NULL, NULL),
(3, 'Ronin  cabusao', 'C# Programming', '526', NULL, '18:00:00', '2025-04-29', 27, 'disapproved', 'you have violation\r\n', '2025-04-29 03:18:23', NULL, NULL),
(4, 'Ronin  cabusao', 'C# Programming', '526', 'PC 12', '18:00:00', '2025-04-29', 26, 'approved', '', '2025-04-29 01:59:12', NULL, NULL),
(5, 'Ronin  cabusao', 'ASP.NET Programming', '530', 'PC 12', '19:42:00', '2025-04-29', 25, 'pending', NULL, NULL, NULL, NULL),
(6, 'Ronin  cabusao', 'C Programming', '526', 'PC 3', '21:23:00', '2025-04-29', 24, 'pending', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_history`
--

CREATE TABLE `sit_in_history` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sit_in_purpose` text NOT NULL,
  `lab_number` varchar(50) NOT NULL,
  `pc_number` varchar(20) DEFAULT NULL,
  `login_time` time NOT NULL,
  `logout_time` time DEFAULT NULL,
  `date` date NOT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `course_level` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `confirm_password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT 'profile.jpg',
  `sessions_left` int(11) DEFAULT 30,
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `last_name`, `is_admin`, `first_name`, `middle_name`, `course_level`, `password`, `confirm_password`, `email`, `course`, `address`, `created_at`, `profile_image`, `sessions_left`, `points`) VALUES
(11, '1', 'cabusao', 0, 'Ronin', '', '3rd Year', '$2y$10$.qaSB9ub6ifoUejICj0HAusC9tgTCHv4di8hivOhp63vMkkoL1gKK', '', 'ronin@gmail.com', 'Information Technology', 'Cebu City', '2025-03-19 12:44:06', 'assets/images/profile.jpg', 28, 3),
(12, '2', 'Doe', 0, 'John', '', '2nd Year', '$2y$10$C21pyh8Y6J7H1TE8.SLXa./jv4a9amb5qVZxo3T8E1JQpHKtM8ItK', '', 'john@gmail.com', 'Software Engineering', 'Manila City', '2025-03-19 18:28:52', 'assets/uploads/profile_2_1745750484.jpg', 28, 1),
(13, '3', 'Marcus', 0, 'Damascus', '', '1st Year', '$2y$10$ZtT2TImDR/Hz/LTeUbhm..s0GPVhoENvLqmWXRjIlaTVa34eeEaEC', '', 'marcus@gmail.com', 'Computer Science', 'Pasig City', '2025-03-19 18:29:53', 'assets/images/profile.jpg', 30, 0),
(14, '4', 'Montana', 0, 'Hanna', '', '1st Year', '$2y$10$6T3H5hmWIjrbIEh4BtMnXOrrKEttIic/PUhXZiqyGp63gtwMPuHdO', '', 'hannah@email.com', 'Computer Science', 'Japan', '2025-04-29 00:23:42', 'assets/images/profile.jpg', 30, 0),
(15, '5', 'Parker', 0, 'Peter', '', '1st Year', '$2y$10$1HOqbFc6rGY5wVMo4Ij2ZuUDC0ivu62E0IUHWt1yXxtj05ruC69dO', '', 'peter@email.com', 'Computer Science', 'Ohio', '2025-04-29 00:24:13', 'assets/images/profile.jpg', 30, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_sit_ins`
--
ALTER TABLE `active_sit_ins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_sitin_student` (`student_id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_feedback_user` (`user_id`),
  ADD KEY `idx_feedback_reservation` (`reservation_id`);

--
-- Indexes for table `pc_sessions`
--
ALTER TABLE `pc_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pc_id` (`pc_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `pc_status`
--
ALTER TABLE `pc_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pc_lab_unique` (`pc_id`,`lab_number`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservation_status` (`status`),
  ADD KEY `idx_reservation_date` (`date`),
  ADD KEY `idx_reservation_date_time` (`date`,`login_time`),
  ADD KEY `idx_reservation_student` (`id_number`);

--
-- Indexes for table `reservations_backup`
--
ALTER TABLE `reservations_backup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sitin_reservation`
--
ALTER TABLE `sitin_reservation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sit_in_history`
--
ALTER TABLE `sit_in_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_sit_ins`
--
ALTER TABLE `active_sit_ins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pc_sessions`
--
ALTER TABLE `pc_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pc_status`
--
ALTER TABLE `pc_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `reservations_backup`
--
ALTER TABLE `reservations_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `sitin_reservation`
--
ALTER TABLE `sitin_reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sit_in_history`
--
ALTER TABLE `sit_in_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
