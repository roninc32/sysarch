-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2025 at 05:28 AM
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
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sit_in_purpose` varchar(255) NOT NULL,
  `lab_number` varchar(50) NOT NULL,
  `login_time` time NOT NULL,
  `logout_time` time NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `id_number`, `name`, `sit_in_purpose`, `lab_number`, `login_time`, `logout_time`, `date`) VALUES
(1, '17651704', '', 'PHP programming', '524', '12:57:26', '00:00:00', '2025-03-11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `last_name` varchar(50) NOT NULL,
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
  `sessions_left` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `last_name`, `first_name`, `middle_name`, `course_level`, `password`, `confirm_password`, `email`, `course`, `address`, `created_at`, `profile_image`, `sessions_left`) VALUES
(1, '17651704', 'cabusao', 'ronin', '', '1st Year', '$2y$10$yVPM5CXVMP65o5VRxzvoD.tGr4k6sMjmqUDU4RBzJunpalnZ5Le3K', '$2y$10$.OQHbEHaqgZ43kH0ohKnBO5A38beGDc3UPom1FTMoV936M1YMkVUC', 'asd@gmail.com', 'BSCS', 'cebu city', '2025-02-06 04:29:21', 'uploads/ccs-logo.png', 29),
(2, '17651704', 'cabusao', 'ronin', '', '1st Year', '$2y$10$H1og5blFsJq6SA0sWrm2rOViMUHehkL4EvxJv7EOtfstxx0UrZk3i', '$2y$10$1csKTWb9aqESxDLCAe3xWOib7ppFd/HEvvsAt3MO.3bqjjDaFbWYS', 'asd@gmail.com', 'BSCS', 'cebu city', '2025-02-06 04:32:56', 'uploads/ccs-logo.png', 29),
(3, '17651704', 'cabusao', 'ronin', '', '1st Year', '$2y$10$Bi1bgoFEQUCLL45zbEf0ieEwJenuxtYqobQZwDJHsksh0mWK1Li2y', '$2y$10$Mm35c1GEou38vzBcpYNFquIIu3eg46aWHZdQCEbRj9Cl9fJkpemke', 'asd@gmail.com', 'BSCS', 'cebu city', '2025-02-06 04:34:29', 'uploads/ccs-logo.png', 29),
(4, '17651704', 'cabusao', 'ronin', '', '1st Year', '$2y$10$A7IJxwH4rKc4NOsJv/FaEOKsQ5WS2eERaKfZKoMAHaHZEr8ueZdPK', '$2y$10$YabA6cg2/syE2wcr028VuuwR8WQQy0vyT8Vk9TCROuUna.w2n5XUK', 'asd@gmail.com', 'BSCS', 'cebu city', '2025-02-06 04:38:58', 'uploads/ccs-logo.png', 29),
(5, '17651704', 'cabusao', 'ronin', '', '1st Year', '$2y$10$vlvConl3cehSPb8sUkJ9Ve4Gkqa4XKjzfowIZbea1FqCs3OgHjpLS', '', 'asd@gmail.com', 'BSCS', 'cebu city', '2025-02-06 04:42:00', 'uploads/ccs-logo.png', 29),
(6, '17651704', 'cabusao', 'ronin', '', '1st Year', '$2y$10$k9p3wkYa5GTHKJpeRqYPLeYwpyWEBocKt/p4GzlgkgpC7smrStMIG', '', 'asd@gmail.com', 'BSCS', 'cebu city', '2025-02-06 04:43:14', 'uploads/ccs-logo.png', 29),
(7, 'asd', 'Roronoa', 'Zoro', '', '3rd Year', '$2y$10$Xv2o8N3pNjguU9m5aoyzG.5mhn5WwDKjo7npnk2FyKZii6xQg/jEq', '', 'roninc32@gmail.com', 'BSIT', 'Cebu City', '2025-02-11 02:46:55', 'uploads/zoro.jpg', 30),
(9, '2', 'asd', 'asd', 'asd', '1st Year', '$2y$10$P9ezZALZOkoPWgP5awxqduuKxHEGk5vITz6vaONqYEr4VQKKMgANm', '', '123@gmail.com', 'Computer Science', 'cebu', '2025-03-04 04:18:01', 'assets/images/profile.jpg', 30);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
