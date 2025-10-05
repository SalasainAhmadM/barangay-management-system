-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 12:49 AM
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
-- Database: `bms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `activity` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `image`) VALUES
(1, 'Admin', 'D', 'Hacker', 'admin@gmail.com', '$2y$10$hUBpNvt6mydVbtqDZyVXKuqV5mbV2BF9Xqwk7WEWB6rIpFEzriQ2O', '');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `contact_number` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `civil_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `house_number` varchar(100) DEFAULT NULL,
  `street_name` varchar(255) DEFAULT NULL,
  `barangay` varchar(255) DEFAULT 'Baliwasan',
  `status` enum('active','inactive','moved') DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `image`, `contact_number`, `date_of_birth`, `gender`, `civil_status`, `occupation`, `house_number`, `street_name`, `barangay`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John', 'K', 'Cena', 'test@gmail.com', '$2y$10$hUBpNvt6mydVbtqDZyVXKuqV5mbV2BF9Xqwk7WEWB6rIpFEzriQ2O', '', '09551088233', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-11 22:12:51', '2025-09-21 15:27:50'),
(2, 'Sheena', 'K', 'Ricalde', 'test2@gmail.com', '$2y$10$xdXSK1ultUwIkkv7pP8zieO/nIcTqNhpIQ1lIkhTnqIj.GwqKW8ai', '', '09771078233', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-11 22:17:09', '2025-09-21 14:30:02'),
(4, 'Aiah', 'D', 'Arceta', 'binimaloi352@gmail.com', '', '', '123', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-19 07:03:44', NULL),
(5, 'Takenori', 'D', 'Akagi', 'akagi@gmail.com', '', '', '09551078233', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 22:16:35', NULL),
(6, 'Maloi', 'D', 'Arceta', 'binimaloi52@gmail.com', '', 'Arceta_09641356677_20250921_171528.jpg', '09641356677', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 22:16:46', '2025-09-21 15:15:28'),
(7, 'Maloi', 'D', 'Arceta', 'binimaloi352asda@gmail.com', '', 'Arceta_09551078263_20250921_171517.png', '09551078263', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 22:39:29', '2025-09-21 15:15:17'),
(9, 'asdsa', 'dasdas', 'dasd', 'samcena.sdas902604@gmail.com', '', '', '09511178235', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 23:26:07', NULL),
(10, 'Maloi', 'sadas', 'asdas', 'sassd02604@gmail.com', '', '', '09551078111', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 23:26:21', '2025-09-21 15:37:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
