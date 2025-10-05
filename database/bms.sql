-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 05:35 PM
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

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `activity`, `description`, `created_at`) VALUES
(1, 'Admin Update', 'admin credentials has been updated', '2025-09-27 22:08:27'),
(2, 'Admin Update', 'admin profile has been updated', '2025-09-27 22:09:29'),
(3, 'New Resident', 'Added new resident Maloi D Salasain from 123 Loop, Baliwasan', '2025-09-28 07:28:00'),
(4, 'Edit Resident', 'Resident name was changed from \'Maloi D Salasain\' to \'Maloi1 D2 Salasain3\'', '2025-09-28 07:38:32'),
(5, 'Edit Resident', 'Resident address was changed from 123 Loop, Baliwasan to 1234 Loop5, Baliwasan6', '2025-09-28 07:39:28'),
(6, 'Edit Resident', 'Resident Maloi1 D2 Salasain3 credentials has been updated', '2025-09-28 07:39:51'),
(7, 'Edit Resident', 'Resident Maloi1 D2 Salasain3 profile was updated', '2025-09-28 07:40:19'),
(8, 'Edit Resident', 'Resident John K Cena profile was updated', '2025-09-28 14:49:41'),
(9, 'Edit Resident', 'Resident John K Cena credentials have been updated', '2025-09-28 15:19:28'),
(10, 'Edit Resident', 'Resident John K Cena1 credentials have been updated', '2025-09-28 15:19:34'),
(11, 'Edit Resident', 'Resident John K Cena1 credentials have been updated', '2025-09-28 15:22:28'),
(12, 'Profile Update', 'User name was changed from John K Cena1 to John K Cena12', '2025-09-28 15:26:04'),
(13, 'Profile Update', 'User John K Cena12 profile has been updated', '2025-09-28 15:27:09'),
(14, 'Profile Update', 'User John K Cena12 profile has been updated', '2025-09-28 15:30:38'),
(15, 'Admin Update', 'admin credentials has been updated', '2025-09-28 15:32:36'),
(16, 'Admin Update', 'Admin profile image has been updated', '2025-09-28 18:56:07'),
(17, 'Admin Update', 'Admin password has been updated', '2025-09-28 18:56:42'),
(18, 'New Resident', 'Added new resident Kaede D Rukawa from 123 Duston Drive, Baliwasan', '2025-09-28 19:11:40'),
(19, 'Edit Resident', 'Resident Kaede D Rukawa credentials has been updated', '2025-09-28 19:27:19'),
(20, 'Edit Resident', 'Resident address was changed from 123 Duston Drive, Baliwasan to 1234 Duston Drive5, Baliwasan6', '2025-09-28 19:34:27'),
(21, 'Edit Resident', 'Resident Kaede1 D2 Rukawa3 name and profile image has been updated', '2025-09-28 19:35:16'),
(22, 'Profile Update', 'Resident name was changed from John K Cena12 to John Kena Cena12', '2025-09-28 20:28:41');

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

INSERT INTO `admin` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `image`, `logo`, `updated_at`) VALUES
(1, 'Admin', 'D2', 'Hacker', 'admin@gmail.com', '$2y$10$SLPDs4w0FlvhFRaedzq3LOH1aZXhZe5qBLnmCAdw6KbG55otyhAb2', 'Hacker_20250928_125607.png', NULL, '2025-09-28 11:01:01');

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `request_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `additional_info` text DEFAULT NULL,
  `status` enum('pending','processing','approved','ready','completed','rejected','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `payment_reference` varchar(100) DEFAULT NULL,
  `submitted_date` datetime DEFAULT current_timestamp(),
  `approved_date` datetime DEFAULT NULL,
  `released_date` datetime DEFAULT NULL,
  `expected_date` date DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_requests`
--

INSERT INTO `document_requests` (`id`, `request_id`, `user_id`, `document_type_id`, `purpose`, `additional_info`, `status`, `payment_status`, `payment_reference`, `submitted_date`, `approved_date`, `released_date`, `expected_date`, `rejection_reason`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'BR-2025-273712', 1, 4, '123', '3', 'processing', 'unpaid', NULL, '2025-10-04 21:31:24', NULL, NULL, '2025-10-07', NULL, NULL, '2025-10-04 13:31:24', '2025-10-04 15:14:12'),
(2, 'BR-2025-535250', 1, 6, 'Employment', 'qwe', 'pending', 'unpaid', NULL, '2025-10-04 23:02:29', NULL, NULL, '2025-10-09', NULL, NULL, '2025-10-04 15:02:29', '2025-10-04 15:02:29'),
(3, 'BR-2025-653431', 1, 8, 'Employment', 'qwerty', 'cancelled', 'unpaid', NULL, '2025-10-04 23:10:12', NULL, NULL, '2025-10-14', NULL, NULL, '2025-10-04 15:10:12', '2025-10-04 15:17:49');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('certificate','permit') NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `processing_days` varchar(20) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `requirements` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `name`, `type`, `description`, `icon`, `processing_days`, `fee`, `requirements`, `is_active`, `created_at`) VALUES
(1, 'Certificate of Residency', 'certificate', 'Proof of residence within the barangay. Required for various transactions.', 'fa-home', '3-5 days', 50.00, NULL, 1, '2025-10-04 13:09:36'),
(2, 'Certificate of Indigency', 'certificate', 'For financial assistance purposes, medical aid, and educational support.', 'fa-hand-holding-heart', '3-5 days', 0.00, NULL, 1, '2025-10-04 13:09:36'),
(3, 'Barangay Clearance', 'certificate', 'Required for employment, business permits, and other legal purposes.', 'fa-id-card', '5-7 days', 100.00, NULL, 1, '2025-10-04 13:09:36'),
(4, 'Good Moral Character', 'certificate', 'Certification of good standing in the community. Often required for jobs.', 'fa-award', '3-5 days', 50.00, NULL, 1, '2025-10-04 13:09:36'),
(5, 'Low Income Certificate', 'certificate', 'For government assistance programs and subsidies.', 'fa-money-bill-wave', '3-5 days', 0.00, NULL, 1, '2025-10-04 13:09:36'),
(6, 'Certificate of Cohabitation', 'certificate', 'For couples living together without formal marriage.', 'fa-heart', '5-7 days', 75.00, NULL, 1, '2025-10-04 13:09:36'),
(7, 'Business Permit', 'permit', 'Required to operate a business within the barangay jurisdiction.', 'fa-store', '7-10 days', 500.00, NULL, 1, '2025-10-04 13:09:36'),
(8, 'Construction Permit', 'permit', 'For building, renovation, or construction projects in the barangay.', 'fa-hard-hat', '10-14 days', 300.00, NULL, 1, '2025-10-04 13:09:36'),
(9, 'Fencing Permit', 'permit', 'Required for installing or repairing fences and boundary walls.', 'fa-border-style', '5-7 days', 200.00, NULL, 1, '2025-10-04 13:09:36'),
(10, 'Excavation Permit', 'permit', 'For digging or excavation activities within barangay roads.', 'fa-digging', '7-10 days', 250.00, NULL, 1, '2025-10-04 13:09:36');

-- --------------------------------------------------------

--
-- Table structure for table `missed_collections`
--

CREATE TABLE `missed_collections` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `waste_type` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','investigating','resolved','rejected') DEFAULT 'pending',
  `resolution_notes` text DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `missed_collections`
--

INSERT INTO `missed_collections` (`report_id`, `user_id`, `collection_date`, `waste_type`, `location`, `description`, `photo_path`, `status`, `resolution_notes`, `resolved_date`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-10-03', 'Biodegradable Waste', 'Grandline', '123', NULL, 'pending', NULL, NULL, '2025-10-04 07:13:32', '2025-10-04 07:13:32'),
(2, 1, '2025-10-03', 'Recyclable Waste', 'Grandline 44', '1232', 'report_1_1759562081.png', 'rejected', '', NULL, '2025-10-04 07:14:41', '2025-10-04 11:51:25'),
(3, 1, '2025-10-02', 'Non-Biodegradable Waste', 'Grandline 44', '123', 'report_1_1759562472.png', 'investigating', '', '2025-10-04 20:06:09', '2025-10-04 07:21:12', '2025-10-04 12:06:20'),
(4, 1, '2025-10-03', 'Recyclable Waste', 'Grandline', '123', NULL, 'pending', NULL, NULL, '2025-10-04 15:07:08', '2025-10-04 15:07:08');

-- --------------------------------------------------------

--
-- Table structure for table `request_attachments`
--

CREATE TABLE `request_attachments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_attachments`
--

INSERT INTO `request_attachments` (`id`, `request_id`, `file_name`, `file_path`, `file_type`, `uploaded_at`) VALUES
(1, 1, 'Screenshot (75).png', '../uploads/document_requests/BR-2025-273712_1759584684_0.png', 'png', '2025-10-04 13:31:24'),
(2, 2, 'Mukaram_CV.pdf', '../uploads/document_requests/BR-2025-535250_1759590149_0.pdf', 'pdf', '2025-10-04 15:02:29');

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
(1, 'John', 'Kena', 'Cena12', 'test@gmail.com', '$2y$10$SLPDs4w0FlvhFRaedzq3LOH1aZXhZe5qBLnmCAdw6KbG55otyhAb2', 'profile_1_1759043733.jpg', '09551088233', '1999-10-09', 'male', 'single', 'Developer', '1234', 'Loop5', 'Baliwasan', 'active', '2025-09-11 22:12:51', '2025-09-28 12:28:41'),
(2, 'Sheena', 'K', 'Ricalde', 'test2@gmail.com', '$2y$10$xdXSK1ultUwIkkv7pP8zieO/nIcTqNhpIQ1lIkhTnqIj.GwqKW8ai', '', '09771078233', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-11 22:17:09', '2025-09-21 14:30:02'),
(4, 'Aiah', 'D', 'Arceta', 'binimaloi352@gmail.com', '', '', '123', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-19 07:03:44', NULL),
(5, 'Takenori', 'D', 'Akagi', 'akagi@gmail.com', '', '', '09551078233', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 22:16:35', NULL),
(6, 'Maloi', 'D', 'Arceta', 'binimaloi52@gmail.com', '', 'Arceta_09641356677_20250921_171528.jpg', '09641356677', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 22:16:46', '2025-09-21 15:15:28'),
(7, 'Maloi', 'D', 'Arceta', 'binimaloi352asda@gmail.com', '', 'Arceta_09551078263_20250921_171517.png', '09551078263', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 22:39:29', '2025-09-21 15:15:17'),
(9, 'asdsa', 'dasdas', 'dasd', 'samcena.sdas902604@gmail.com', '', '', '09511178235', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 23:26:07', NULL),
(10, 'Maloi', 'sadas', 'asdas', 'sassd02604@gmail.com', '', '', '09551078111', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', '2025-09-21 23:26:21', '2025-09-21 15:37:25'),
(12, 'Maloi', 'D', 'Arceta', 'binimaloi35sdsa42@gmail.com', '', '', '09551012345', NULL, NULL, 'single', 'qwerty', '123', 'Duston Drive', 'Baliwasan', 'active', '2025-09-26 09:34:23', NULL),
(13, 'Takenori1', 'D2', 'Akagi3', 'binimaloi3521233332@gmail.com', '', 'Akagi_09123078233_20250926_034712.png', '09123012345', '2024-05-28', 'male', 'widowed', 'qwerty123', '1234', 'Duston Drive1', 'Baliwasan23', 'inactive', '2025-09-26 09:47:12', '2025-09-26 01:50:47'),
(14, 'Maloi1', 'D2', 'Salasain3', 'binim@gmail.com', '', 'Salasain3_09553218233_20250928_014019.png', '09553218233', '2020-10-01', 'male', 'divorced', 'General', '1234', 'Loop5', 'Baliwasan6', 'active', '2025-09-28 07:28:00', '2025-09-27 23:40:19'),
(15, 'Kaede1', 'D2', 'Rukawa3', 'rukawa@gmail.com', '$2y$10$hhVbbJbDWBbGABaLcVl05ui2YasW9hF8n9Wjk0WCO6BIHJIp5COOq', 'Rukawa3_09123321345_20250928_133516.png', '09123321345', '1999-08-20', 'male', 'single', 'Ace Player', '1234', 'Duston Drive5', 'Baliwasan6', 'active', '2025-09-28 19:11:40', '2025-09-28 11:35:16');

-- --------------------------------------------------------

--
-- Table structure for table `waste_schedules`
--

CREATE TABLE `waste_schedules` (
  `schedule_id` int(11) NOT NULL,
  `waste_type` varchar(50) NOT NULL,
  `collection_days` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waste_schedules`
--

INSERT INTO `waste_schedules` (`schedule_id`, `waste_type`, `collection_days`, `icon`, `color`, `description`, `is_active`, `created_at`) VALUES
(1, 'Recyclable Waste', 'Monday, Tuesday, Wednesday, Friday, Saturday', 'fa-recycle', 'recyclable', 'Plastic, paper, glass, and metal items', 1, '2025-10-04 07:06:39'),
(2, 'Biodegradable Waste', 'Monday,Wednesday,Friday', 'fa-leaf', 'biodegradable', 'Food scraps, yard waste, and organic materials', 1, '2025-10-04 07:06:39'),
(3, 'Non-Biodegradable Waste', 'Tuesday,Thursday,Saturday', 'fa-trash', 'non-biodegradable', 'General waste that cannot be recycled or composted', 1, '2025-10-04 07:06:39'),
(4, 'Special/Hazardous Waste', 'First Saturday', 'fa-hospital', 'hazardous', 'Batteries, chemicals, electronics, and medical waste', 1, '2025-10-04 07:06:39');

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
-- Indexes for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `document_type_id` (`document_type_id`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `missed_collections`
--
ALTER TABLE `missed_collections`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `waste_schedules`
--
ALTER TABLE `waste_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `missed_collections`
--
ALTER TABLE `missed_collections`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `waste_schedules`
--
ALTER TABLE `waste_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `document_requests`
--
ALTER TABLE `document_requests`
  ADD CONSTRAINT `document_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_requests_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD CONSTRAINT `request_attachments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
