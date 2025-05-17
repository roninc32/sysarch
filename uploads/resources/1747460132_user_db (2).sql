-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2025 at 05:09 AM
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
  `login_time` time NOT NULL,
  `date` date NOT NULL
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
(5, '', 'Make an amazing announcement for yourself quickly & easily in minutes with our free online announcement maker Customize a template or create a striking announcement from scratch to get the word out there about your business.', '2025-03-11 03:29:56'),
(6, '', 'university of cebu', '2025-03-20 04:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `feedback_text` text NOT NULL,
  `rating` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `reservation_id`, `id_number`, `feedback_text`, `rating`, `created_at`) VALUES
(1, 6, '1', 'nice', 4, '2025-04-10 04:50:01'),
(2, 16, '2', 'Good experience', 5, '2025-04-10 05:02:05');

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
(4, '17651704', 'ronin cabusao', 'ASP.NET programming', 'Mac Laboratory', '04:05:02', '04:05:08', '2025-03-20'),
(5, '17651704', 'ronin cabusao', 'ASP.NET programming', '528', '05:01:27', '05:04:37', '2025-03-20'),
(6, '1', 'John Doe', 'C programming', '524', '05:16:42', '05:17:43', '2025-03-20'),
(7, '1', 'John Doe', 'C programming', '524', '05:23:42', '05:46:21', '2025-03-20'),
(8, '17651704', 'ronin cabusao', 'C programming', '524', '05:45:09', '01:19:55', '2025-03-20'),
(9, '1', 'John Doe', 'C programming', '524', '04:45:18', '04:45:20', '2025-04-03'),
(10, '1', 'John Doe', 'C programming', '524', '04:45:26', '04:45:28', '2025-04-03'),
(11, '1', 'John Doe', 'C programming', '524', '04:45:32', '04:45:35', '2025-04-03'),
(12, '1', 'John Doe', 'C programming', '524', '05:29:31', '05:29:37', '2025-04-03'),
(13, '5', 'Kathryn Bernardo', 'C programming', '524', '05:29:58', '05:30:01', '2025-04-03'),
(14, '4', 'Miracle Dela Cruz', 'C programming', '524', '05:29:53', '05:30:02', '2025-04-03'),
(15, '3', 'Daniel Padilla', 'C programming', '524', '05:29:48', '05:30:04', '2025-04-03'),
(16, '2', 'Miriam Webster', 'C programming', '524', '05:29:42', '05:30:06', '2025-04-03');

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
(2, '17651704', 'cabusao', 'ronin', '', '1st Year', '$2y$10$H1og5blFsJq6SA0sWrm2rOViMUHehkL4EvxJv7EOtfstxx0UrZk3i', '$2y$10$1csKTWb9aqESxDLCAe3xWOib7ppFd/HEvvsAt3MO.3bqjjDaFbWYS', 'asd@gmail.com', 'BSCS', 'cebu city', '2025-02-06 04:32:56', 'uploads/ccs-logo.png', 30),
(10, '1', 'Doe', 'John', '', '3rd Year', '$2y$10$8/LXVh2vf..PmPJmT.t.e.sjZyx7VU2gB0eyy4L25oxZVtLQzHBPW', '', 'johndoe@gmail.com', 'Computer Science', 'Manila City', '2025-03-20 04:16:19', 'assets/images/profile.jpg', 30),
(11, '2', 'Webster', 'Miriam', '', '5th Year', '$2y$10$AbjDBvW8HXmX7dy2RO/e2.8i1V/gU4BP28osbzNt1otbI9nsJPFJa', '', 'miriam@email.com', 'Computer Science', 'Pangasinan City', '2025-03-20 04:24:50', 'assets/images/profile.jpg', 30),
(12, '3', 'Padilla', 'Daniel', '', '3rd Year', '$2y$10$UKiHWG6i8.Eo4noWgErmuOAbvJo2ZqVlBMySa5mf.HK5ceUD.IQUy', '', 'danielp@email.com', 'Software Engineering', 'Pampanga City', '2025-03-20 04:25:31', 'assets/images/profile.jpg', 30),
(13, '4', 'Dela Cruz', 'Miracle', '', '1st Year', '$2y$10$ZcBv3w9ogmjTo/xro0Bjo.ucVG3FDnytNgvLydxariQwVuqvbtkGy', '', 'miracleg@email.com', 'Computer Science', 'Isabela City', '2025-03-20 04:26:19', 'assets/images/profile.jpg', 30),
(14, '5', 'Bernardo', 'Kathryn', '', '1st Year', '$2y$10$ByzcO395N5JA3RLvbQK4t.W3bhL9GSOlbDlEjN9KzY9SK1u5sXI5W', '', 'kathb@email.com', 'Information Technology', 'Manila City', '2025-03-20 04:28:15', 'assets/images/profile.jpg', 30),
(15, '6', 'Igot', 'Vince Clave', 'Datanagan', '4th Year', '$2y$10$XWEuACouM7E100NUbFbhVORBYEJgosQG9qnjA.AtAS4Velfb7e1jq', '', 'vinceigop@email.com', 'Information Technology', 'Philippines', '2025-03-20 04:32:55', 'assets/images/profile.jpg', 30),
(16, '7', 'Monreal', 'Jeff', '', '3rd Year', '$2y$10$q4slRPPcR87rygNfarZ79.3fzfrIrZRpDSa5LtvELc73zxtX8aFra', '', 'jeffpogi@email.com', 'Information Technology', 'America', '2025-03-20 04:33:32', 'assets/images/profile.jpg', 30),
(17, '8', 'Paraiso', 'Justine', '', '1st Year', '$2y$10$UxMnAUHAjtfLwE7PlypCQ.7dBqJ4GbXeyPm.1vkrKBru/ssqtolBe', '', 'justine@email.com', 'Computer Science', 'Canada', '2025-03-20 04:34:16', 'assets/images/profile.jpg', 30),
(18, '9', 'Catubig', 'Mark', 'Dave', '1st Year', '$2y$10$IeMMtD9zvmDpRCCpTQKY8eKgLaOvENNypd544z1SLyp5hHwliThSi', '', 'markdave@yahoo.com', 'Computer Science', 'Japan', '2025-03-20 04:35:14', 'assets/images/profile.jpg', 30),
(19, '10', 'Palacio', 'Real', 'John', '1st Year', '$2y$10$6MnbzZbYVltTR2w56sz8AORKOaHe/Hi5waYM76XtAbl9YuR0vVrQ2', '', 'realjohn@html.com', 'Computer Science', 'Ukraine', '2025-03-20 04:40:48', 'assets/images/profile.jpg', 30);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_sit_ins`
--
ALTER TABLE `active_sit_ins`
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
  ADD KEY `reservation_id` (`reservation_id`);

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
-- AUTO_INCREMENT for table `active_sit_ins`
--
ALTER TABLE `active_sit_ins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
