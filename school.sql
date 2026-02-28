-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 24, 2026 at 09:46 AM
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
-- Database: `school`
--

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `program_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `years` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program`
--

INSERT INTO `program` (`program_id`, `code`, `title`, `years`) VALUES
(1, 'BSA', 'Bachelor of Science in Accountancy', 4),
(2, 'BSCE', 'Bachelor of Science in Civil Engineering', 4),
(3, 'BSPSY', 'Bachelor of Science in Psychology', 3),
(4, 'BSCS', 'Bachelor of Science in Computer Science', 4),
(5, 'BSIT', 'Bachelor of Science in Information Technology', 4);

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `unit` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `code`, `title`, `unit`) VALUES
(1, 'CS 1130', 'Introduction to Computing', 3),
(2, 'Philo 1000', 'Philosophy', 3),
(3, 'PE 1114', 'Path-Fit I', 2),
(4, 'CS 1232', 'Discrete Structures 1', 3),
(5, 'CS 4155', 'CS Elective 3', 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` enum('admin','staff','teacher','student') NOT NULL,
  `created_on` datetime DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `account_type`, `created_on`, `created_by`, `updated_on`, `updated_by`) VALUES
(1, 'admin', '$2y$10$HOgDs3wTs0E78kemm3k9F.9oQyziU7g13rNw1Efi7JaXpss8WU5Ka', 'admin', '2026-02-04 09:34:22', 1, '2026-02-24 16:36:27', 1),
(2, 'Brandon Santos', '$2y$10$6KTmcebenCSARscp1Spou.hg8lQpQ/.HlViaicrO0iYKmSd.zhNKC', 'student', '2026-02-04 22:58:57', 1, '2026-02-05 08:43:24', 2),
(3, 'Noah', '$2y$10$/fe5R13Us7P4UwQwF4UBk.XRywinAlTPa/T8JUoP2Fd1yNIiYoypq', 'teacher', '2026-02-04 23:01:09', 1, '2026-02-05 08:46:46', 3),
(4, 'Franz', '$2y$10$EpqQ1vy9NRy17qAUPRC1tO7J.ERjn/Jm7Wq6LaEVRyXUfRHrzjHGW', 'staff', '2026-02-05 08:44:13', 1, '2026-02-05 08:44:57', 4),
(5, 'test1', '$2y$10$zP1u9HSQOF9yX602feE4BuvMjrpLQssRVQMhB1SKHe5PLTATI7txS', 'staff', '2026-02-24 16:29:18', 1, '2026-02-24 16:29:49', 5),
(6, 'staff', '$2y$10$Vkf.FigjpSq6DhRiLohkH.8DuRTo3Qg1h60J8hqb1d63xfjvjSHSa', 'staff', '2026-02-24 16:31:32', 1, NULL, NULL),
(7, 'teacher', '$2y$10$mBvYcaMW11BzLU/Y.etYZuLnhUGGNt2OJ7N0yQ0E.LtMgzXHHrQCe', 'teacher', '2026-02-24 16:31:45', 1, NULL, NULL),
(8, 'student', '$2y$10$kFP4l6eiFWV5wuEYTSsu9es2/WmJwiZfyEkG3X0BCv6PvW8VgB0AO', 'student', '2026-02-24 16:32:24', 1, NULL, NULL),
(9, 'student1', '$2y$10$E6w0NKijqLKefG.XiFlnLO0uYZvUC.C5kc4QYl8I00gj0y9l.wHgu', 'teacher', '2026-02-24 16:34:03', 1, '2026-02-24 16:34:23', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `program`
--
ALTER TABLE `program`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
