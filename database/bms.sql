-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 01:32 AM
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
(22, 'Profile Update', 'Resident name was changed from John K Cena12 to John Kena Cena12', '2025-09-28 20:28:41'),
(23, 'Deleted a Resident', 'Removed resident \'Maloi1 D2 Salasain3\' (ID: 14) from the system.', '2025-10-05 13:33:09'),
(24, 'Add Document Type', 'Added a new document type named TEST (permit) without a fee.', '2025-10-05 13:33:48'),
(25, 'Edited a Document Type', 'Updated the document type named \'TEST2\' (certificate) without a fee.', '2025-10-05 13:34:16'),
(26, 'Deleted a Document Type', 'Deleted a document type named \'TEST2\' (certificate).', '2025-10-05 13:37:46'),
(27, 'Edited Waste Schedule', 'Updated schedule (ID: 5) - Waste Type: \'tsy\', Collection Days: \'Monday, Saturday\'.', '2025-10-05 13:45:40'),
(28, 'Added a Waste Collection Schedule', 'Added a new waste collection schedule for \'tsy1231\' on Tuesday, Saturday.', '2025-10-05 13:45:58'),
(29, 'Deleted Waste Schedule', 'Removed schedule (ID: 6) - Waste Type: \'tsy1231\', Collection Days: \'Tuesday, Saturday\'.', '2025-10-05 13:48:57'),
(30, 'Deleted a report', 'Deleted a missed collection report at \'Grandline 44\' (Report ID: 3).', '2025-10-05 16:57:58'),
(31, 'Updated report status', 'Updated report ID 4 status to \'resolved\' at \'Grandline\'. No resolution notes.', '2025-10-05 17:32:57'),
(32, 'Deleted a report', 'Deleted a missed collection report at \'Grandline 44\' (Report ID: 2).', '2025-10-05 17:39:34'),
(33, 'Updated document request status', 'Admin ID 1 updated the status of request ID 3 for \'Construction Permit\' to \'pending\'.', '2025-10-05 17:40:50'),
(34, 'Updated document request status', 'Updated the status of request ID 3 for \'Construction Permit\' to \'approved\'.', '2025-10-05 17:41:13'),
(35, 'Updated report status', 'Updated report ID 4 status to \'rejected\' at \'Grandline\'. No resolution notes.', '2025-10-05 17:41:23'),
(36, 'Deleted Document Request', 'Deleted a certificate request (ID: 1) for \'Good Moral Character\' with a fee of 50.00. Submitted by John Kena Cena12.', '2025-10-05 17:47:31'),
(37, 'User Login', 'User John K. Cena12 (test@gmail.com) has logged in.', '2025-10-05 18:26:58'),
(38, 'User Login', 'User John K. Cena12 (test@gmail.com) has logged in.', '2025-10-06 07:34:11'),
(39, 'Updated document request status', 'Updated the status of request ID 11 for \'Fencing Permit\' to \'pending\'.', '2025-10-06 07:55:26'),
(40, 'User Login', 'User John K. Cena12 (test@gmail.com) has logged in.', '2025-10-08 09:40:43'),
(41, 'User Login', 'User John K. Cena12 (test@gmail.com) has logged in.', '2025-10-11 16:10:33'),
(42, 'Notification Preferences Update', 'Updated notification preferences for user \'Aiah D Arceta\' (ID: 4). Waste Reminders: Disabled, Request Updates: Enabled, Announcements: Enabled, SMS Notifications: Enabled', '2025-10-11 21:40:17'),
(43, 'Notification Preferences Update', 'Updated notification preferences for user \'Kaede1 D2 Rukawa3\' (ID: 15). Waste Reminders: Enabled, Request Updates: Disabled, Announcements: Disabled, SMS Notifications: Enabled', '2025-10-11 21:41:52'),
(44, 'Bulk Notification Created', 'Created \'announcement\' notification for all users (8 users). Title: \'test\'', '2025-10-11 22:53:42'),
(45, 'Notification Updated', 'Updated notification #8 for user \'John Cena12\'. Changes: Type: \'announcement\' → \'waste\', Title updated, Message updated', '2025-10-11 22:58:03'),
(46, 'Edit Resident', 'Resident Maloi D Arceta credentials has been updated', '2025-10-11 23:29:03'),
(47, 'Notification Preferences Update', 'Updated notification preferences for user \'Aiah D Arceta\' (ID: 4). Waste Reminders: Disabled, Request Updates: Disabled, Announcements: Enabled, SMS Notifications: Enabled', '2025-10-11 23:37:04'),
(48, 'Notification Updated', 'Updated notification #6 for user \'John Cena12\'. Changes: Title updated, Status: Marked as unread', '2025-10-12 00:04:05'),
(49, 'User Login', 'User John K. Cena12 (test@gmail.com) has logged in.', '2025-10-12 00:19:35'),
(50, 'User Login', 'User Sheena K. Ricalde (test2@gmail.com) has logged in.', '2025-10-12 21:01:43'),
(51, 'User Login', 'User Sheena K. Ricalde (test2@gmail.com) has logged in.', '2025-10-12 21:03:37'),
(52, 'New Account', 'A new user account was created for Rocks D Xebec (rocks@gmail.com)', '2025-10-12 21:23:59'),
(53, 'User Login', 'User Rocks D. Xebec (rocks@gmail.com) has logged in.', '2025-10-12 21:24:23'),
(54, 'User Login', 'User Rocks D. Xebec (rocks@gmail.com) has logged in.', '2025-10-12 21:29:18'),
(55, 'User Login', 'User Rocks D. Xebec (rocks@gmail.com) has logged in.', '2025-10-12 21:29:46');

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
(3, 'BR-2025-653431', 1, 8, 'Employment', 'qwerty', 'approved', 'unpaid', NULL, '2025-10-04 23:10:12', '2025-10-05 17:41:13', NULL, '2025-10-14', NULL, 'test change', '2025-10-04 15:10:12', '2025-10-05 09:41:13'),
(6, 'BR-2025-653432', 1, 7, 'Employment', 'qwerty', 'completed', 'unpaid', NULL, '2025-10-04 23:10:12', '2025-10-05 17:41:13', NULL, '2025-10-14', NULL, 'test change', '2025-10-04 15:10:12', '2025-10-05 09:41:13'),
(8, 'BR-2025-653433', 1, 3, 'Employment', 'qwerty', 'pending', 'unpaid', NULL, '2025-10-04 23:10:12', '2025-10-05 17:41:13', NULL, '2025-10-14', NULL, 'test change', '2025-10-04 15:10:12', '2025-10-05 09:41:13'),
(9, 'BR-2025-653434', 1, 10, 'Employment', 'qwerty', 'processing', 'unpaid', NULL, '2025-10-04 23:10:12', '2025-10-05 17:41:13', NULL, '2025-10-14', NULL, 'test change', '2025-10-04 15:10:12', '2025-10-05 09:41:13'),
(10, 'BR-2025-653435', 1, 4, 'Employment', 'qwerty', 'ready', 'unpaid', NULL, '2025-10-04 23:10:12', '2025-10-05 17:41:13', NULL, '2025-10-14', NULL, 'test change', '2025-10-04 15:10:12', '2025-10-05 09:41:13'),
(11, 'BR-2025-653436', 1, 9, 'Employment', 'qwerty', 'pending', 'unpaid', NULL, '2025-10-04 23:10:12', '2025-10-05 17:41:13', NULL, '2025-10-14', NULL, 'test change', '2025-10-04 15:10:12', '2025-10-05 23:55:26'),
(12, 'BR-2025-653437', 1, 5, 'Employment', 'qwerty', 'cancelled', 'unpaid', NULL, '2025-10-04 23:10:12', '2025-10-05 17:41:13', NULL, '2025-10-14', NULL, 'test change', '2025-10-04 15:10:12', '2025-10-05 09:41:13'),
(13, 'BR-2025-037993', 1, 8, 'Employment', 'qwe', 'pending', 'unpaid', NULL, '2025-10-12 12:18:58', NULL, NULL, '2025-10-22', NULL, NULL, '2025-10-12 04:18:58', '2025-10-12 04:18:58'),
(14, 'BR-2025-363976', 1, 3, 'Employment', 'qqq', 'pending', 'unpaid', NULL, '2025-10-12 12:19:15', NULL, NULL, '2025-10-17', NULL, NULL, '2025-10-12 04:19:15', '2025-10-12 04:19:15'),
(15, 'BR-2025-264220', 1, 1, 'Employment', '', 'pending', 'unpaid', NULL, '2025-10-12 12:19:24', NULL, NULL, '2025-10-15', NULL, NULL, '2025-10-12 04:19:24', '2025-10-12 04:19:24');

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
(1, 'Certificate of Residency', 'certificate', 'Proof of residence within the barangay. Required for various transactions.', 'fa-home', '3-5 days', 0.00, 'N/A', 1, '2025-10-04 13:09:36'),
(2, 'Certificate of Indigency', 'certificate', 'For financial assistance purposes, medical aid, and educational support.', 'fa-user-check', '3-5 days', 0.00, 'N/A', 1, '2025-10-04 13:09:36'),
(3, 'Barangay Clearance', 'certificate', 'Required for employment, business permits, and other legal purposes.', 'fa-id-card', '5-7 days', 0.00, 'N/A', 1, '2025-10-04 13:09:36'),
(4, 'Good Moral Character', 'certificate', 'Certification of good standing in the community. Often required for jobs.', 'fa-certificate', '3-5 days', 50.00, 'N/A', 1, '2025-10-04 13:09:36'),
(5, 'Low Income Certificate', 'certificate', 'For government assistance programs and subsidies.', 'fa-file-alt', '3-5 days', 0.00, 'N/A', 1, '2025-10-04 13:09:36'),
(6, 'Certificate of Cohabitation', 'certificate', 'For couples living together without formal marriage.', 'fa-briefcase', '5-7 days', 10.00, 'N/A', 1, '2025-10-04 13:09:36'),
(7, 'Business Permit', 'certificate', 'Required to operate a business within the barangay jurisdiction.', 'fa-id-card', '7-10 days', 0.00, 'N/A', 1, '2025-10-04 13:09:36'),
(8, 'Construction Permit', 'permit', 'For building, renovation, or construction projects in the barangay.', 'fa-home', '10-14 days', 30.00, 'N/A', 1, '2025-10-04 13:09:36'),
(9, 'Fencing Permit', 'permit', 'Required for installing or repairing fences and boundary walls.', 'fa-home', '5-7 days', 10.00, 'N/A', 1, '2025-10-04 13:09:36'),
(10, 'Excavation Permit', 'permit', 'For digging or excavation activities within barangay roads.', 'fa-home', '7-10 days', 5.00, 'N/A', 1, '2025-10-04 13:09:36');

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
(4, 1, '2025-10-03', 'Recyclable Waste', 'Grandline', '123', NULL, 'rejected', '', '2025-10-05 17:32:57', '2025-10-04 15:07:08', '2025-10-05 09:41:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('waste','request','announcement','alert','success') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL COMMENT 'Related record ID (request_id, report_id, etc)',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'Type of related record',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `icon`, `is_read`, `related_id`, `related_type`, `created_at`, `updated_at`) VALUES
(1, 1, 'waste', 'Waste Collection Reminder', 'Reminder: Recyclable waste collection is scheduled for tomorrow, October 6, 2025. Please prepare your bins before 6:00 AM.', 'fa-trash-alt', 1, NULL, NULL, '2025-10-05 09:07:41', '2025-10-05 11:28:17'),
(2, 1, 'success', 'Certificate Ready for Pickup', 'Your Certificate of Residency (Request #BR-2025-001198) has been approved and is ready for pickup at the barangay hall.', 'fa-check-circle', 1, 1, 'document_request', '2025-10-05 06:07:41', '2025-10-05 11:28:17'),
(3, 1, 'announcement', 'Community Clean-up Drive', 'Join us this Saturday, October 5, for our monthly community clean-up drive. Gathering at the barangay hall at 6:00 AM. Your participation matters!', 'fa-bullhorn', 1, NULL, NULL, '2025-10-04 11:07:41', '2025-10-05 11:28:17'),
(4, 1, 'request', 'Request Status Update', 'Your Barangay Clearance request (Request #BR-2025-001234) is now being processed. Expected completion: October 5, 2025.', 'fa-hourglass-half', 1, NULL, 'document_request', '2025-10-03 11:07:41', '2025-10-05 11:07:41'),
(5, 1, 'waste', 'Biodegradable Waste Collection', 'Biodegradable waste collection completed successfully in your area. Next collection: October 7, 2025.', 'fa-leaf', 1, NULL, NULL, '2025-10-02 11:07:41', '2025-10-05 11:07:41'),
(7, 1, 'alert', 'Missed Collection Report Update', 'Your missed collection report has been resolved. Make-up collection was completed on September 25, 2025. Thank you for your patience.', 'fa-exclamation-circle', 1, 3, 'missed_collection', '2025-09-28 11:07:41', '2025-10-05 11:07:41'),
(8, 1, 'waste', 'test2', 'earth please be safe1', 'fa-trash-alt', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:58:03'),
(9, 2, 'announcement', 'test', 'earth please be safe', 'fa-bullhorn', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:53:42'),
(10, 4, 'announcement', 'test', 'earth please be safe', 'fa-bullhorn', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:53:42'),
(11, 5, 'announcement', 'test', 'earth please be safe', 'fa-bullhorn', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:53:42'),
(12, 6, 'announcement', 'test', 'earth please be safe', 'fa-bullhorn', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:53:42'),
(13, 7, 'announcement', 'test', 'earth please be safe', 'fa-bullhorn', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:53:42'),
(14, 12, 'announcement', 'test', 'earth please be safe', 'fa-bullhorn', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:53:42'),
(15, 15, 'announcement', 'test', 'earth please be safe', 'fa-bullhorn', 0, NULL, NULL, '2025-10-11 14:53:42', '2025-10-11 14:53:42');

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `waste_reminders` tinyint(1) DEFAULT 1,
  `request_updates` tinyint(1) DEFAULT 1,
  `announcements` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`id`, `user_id`, `waste_reminders`, `request_updates`, `announcements`, `sms_notifications`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 0, 0, 0, '2025-10-05 11:07:41', '2025-10-05 12:23:19'),
(2, 2, 1, 1, 1, 1, '2025-10-05 11:07:41', '2025-10-05 11:07:41'),
(3, 4, 0, 0, 1, 1, '2025-10-05 11:07:41', '2025-10-11 15:37:04'),
(4, 5, 1, 1, 1, 1, '2025-10-05 11:07:41', '2025-10-05 11:07:41'),
(5, 6, 1, 1, 1, 1, '2025-10-05 11:07:41', '2025-10-05 11:07:41'),
(6, 7, 1, 1, 1, 1, '2025-10-05 11:07:41', '2025-10-05 11:07:41'),
(7, 12, 1, 1, 1, 1, '2025-10-05 11:07:41', '2025-10-05 11:07:41'),
(8, 15, 1, 0, 0, 1, '2025-10-05 11:07:41', '2025-10-11 13:41:51');

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
  `is_new` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `image`, `contact_number`, `date_of_birth`, `gender`, `civil_status`, `occupation`, `house_number`, `street_name`, `barangay`, `status`, `is_new`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Kena', 'Cena12', 'test@gmail.com', '$2y$10$SLPDs4w0FlvhFRaedzq3LOH1aZXhZe5qBLnmCAdw6KbG55otyhAb2', 'profile_1_1759043733.jpg', '09551088233', '1999-10-09', 'male', 'single', 'Developer', '1234', 'Loop5', 'Baliwasan', 'active', 1, '2025-09-11 22:12:51', '2025-09-28 12:28:41'),
(2, 'Sheena', 'K', 'Ricalde', 'test2@gmail.com', '$2y$10$SLPDs4w0FlvhFRaedzq3LOH1aZXhZe5qBLnmCAdw6KbG55otyhAb2', '', '09771078233', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', 1, '2025-09-11 22:17:09', '2025-10-12 13:01:32'),
(4, 'Aiah', 'D', 'Arceta', 'binimaloi352@gmail.com', '', '', '123', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', 1, '2025-09-19 07:03:44', NULL),
(5, 'Takenori', 'D', 'Akagi', 'akagi@gmail.com', '', '', '09551078233', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', 1, '2025-09-21 22:16:35', NULL),
(6, 'Maloi', 'D', 'Arceta', 'binimaloi52@gmail.com', '', 'Arceta_09641356677_20250921_171528.jpg', '09641356677', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', 1, '2025-09-21 22:16:46', '2025-09-21 15:15:28'),
(7, 'Maloi', 'D', 'Arceta', 'binimaloi352asda@gmail.com', '', 'Arceta_09551078263_20250921_171517.png', '09551078263', NULL, NULL, NULL, NULL, NULL, NULL, 'Baliwasan', 'active', 1, '2025-09-21 22:39:29', '2025-09-21 15:15:17'),
(12, 'Maloi', 'D', 'Arceta', 'binimaloi35sdsa42@gmail.com', '', '', '09551012345', '0000-00-00', 'female', 'single', 'qwerty', '123', 'Duston Drive', 'Baliwasan', 'active', 1, '2025-09-26 09:34:23', '2025-10-11 15:29:03'),
(15, 'Kaede1', 'D2', 'Rukawa3', 'rukawa@gmail.com', '$2y$10$hhVbbJbDWBbGABaLcVl05ui2YasW9hF8n9Wjk0WCO6BIHJIp5COOq', 'Rukawa3_09123321345_20250928_133516.png', '09123321345', '1999-08-20', 'male', 'single', 'Ace Player', '1234', 'Duston Drive5', 'Baliwasan6', 'active', 1, '2025-09-28 19:11:40', '2025-09-28 11:35:16'),
(16, 'Rocks', 'D', 'Xebec', 'rocks@gmail.com', '$2y$10$c4jFu6PpZKaRzrqexyayYuubt4dk4QoLs8DFhyMEnhNE9731bZ6ZO', '', '09771068455', '2006-01-12', 'male', 'single', 'General', 'JLC', 'Normal Road', 'Baliwasan', 'active', 0, '2025-10-12 21:23:59', '2025-10-12 13:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_daily_request_limits`
--

CREATE TABLE `user_daily_request_limits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `certificate_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_daily_request_limits`
--

INSERT INTO `user_daily_request_limits` (`id`, `user_id`, `request_date`, `certificate_count`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-10-11', 3, '2025-10-11 04:18:58', '2025-10-11 04:19:24');

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
(4, 'Special/Hazardous Waste', 'First Saturday', 'fa-hospital', 'hazardous', 'Batteries, chemicals, electronics, and medical waste', 1, '2025-10-04 07:06:39'),
(5, 'tsy', 'Monday, Saturday', 'fa-leaf', 'hazardous', '123', 1, '2025-10-05 05:44:58');

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `type` (`type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

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
-- Indexes for table `user_daily_request_limits`
--
ALTER TABLE `user_daily_request_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`request_date`),
  ADD KEY `idx_user_date` (`user_id`,`request_date`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_requests`
--
ALTER TABLE `document_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `missed_collections`
--
ALTER TABLE `missed_collections`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `request_attachments`
--
ALTER TABLE `request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_daily_request_limits`
--
ALTER TABLE `user_daily_request_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `waste_schedules`
--
ALTER TABLE `waste_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_attachments`
--
ALTER TABLE `request_attachments`
  ADD CONSTRAINT `request_attachments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `document_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_daily_request_limits`
--
ALTER TABLE `user_daily_request_limits`
  ADD CONSTRAINT `fk_daily_limit_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
