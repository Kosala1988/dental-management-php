-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 07:41 AM
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
-- Database: `dental_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('Scheduled','Completed','Canceled','No-Show') DEFAULT 'Scheduled',
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `dentist_id`, `scheduled_date`, `start_time`, `end_time`, `status`, `reason`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2023-06-20', '09:00:00', '09:30:00', 'Scheduled', NULL, NULL, 3, '2025-05-17 18:13:53', '2025-05-17 18:13:53'),
(2, 1, 2, '2025-01-13', '11:30:00', '12:15:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-01-06 18:30:00', '2025-05-18 08:53:15'),
(3, 1, 2, '2024-12-02', '10:30:00', '11:00:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2024-11-22 18:30:00', '2025-05-18 08:53:15'),
(4, 1, 2, '2025-01-02', '14:00:00', '14:30:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2024-12-24 18:30:00', '2025-05-18 08:53:15'),
(5, 1, 2, '2024-12-05', '11:30:00', '12:30:00', 'Completed', 'Root canal therapy', NULL, 3, '2024-11-26 18:30:00', '2025-05-18 08:53:15'),
(6, 1, 2, '2025-03-21', '10:30:00', '11:30:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-03-16 18:30:00', '2025-05-18 08:53:15'),
(7, 1, 2, '2025-06-06', '09:30:00', '10:15:00', 'Scheduled', 'Filling replacement', NULL, 3, '2025-05-28 18:30:00', '2025-05-18 08:53:15'),
(8, 1, 2, '2024-12-05', '10:30:00', '11:00:00', 'Completed', 'Filling replacement', NULL, 3, '2024-12-03 18:30:00', '2025-05-18 08:53:15'),
(9, 1, 2, '2025-04-07', '14:30:00', '15:15:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-04-02 18:30:00', '2025-05-18 08:53:15'),
(10, 1, 2, '2025-06-17', '10:30:00', '11:00:00', 'Scheduled', 'Filling replacement', NULL, 3, '2025-06-02 18:30:00', '2025-05-18 08:53:15'),
(11, 1, 2, '2025-04-18', '10:30:00', '12:00:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-04-12 18:30:00', '2025-05-18 08:53:15'),
(12, 1, 2, '2025-06-10', '15:30:00', '16:15:00', 'Scheduled', 'Crown replacement', NULL, 3, '2025-06-08 18:30:00', '2025-05-18 08:53:15'),
(13, 1, 2, '2025-06-19', '12:00:00', '13:30:00', 'Scheduled', 'Bleeding gums', NULL, 3, '2025-06-16 18:30:00', '2025-05-18 08:53:15'),
(14, 1, 2, '2025-02-26', '14:00:00', '14:45:00', 'Completed', 'Filling replacement', NULL, 3, '2025-02-17 18:30:00', '2025-05-18 08:53:15'),
(15, 1, 2, '2025-04-21', '11:30:00', '12:15:00', 'No-Show', 'Crown replacement', NULL, 3, '2025-04-13 18:30:00', '2025-05-18 08:53:15'),
(16, 1, 2, '2025-06-18', '15:30:00', '16:15:00', 'Scheduled', 'Root canal therapy', NULL, 3, '2025-06-11 18:30:00', '2025-05-18 08:53:15'),
(17, 1, 2, '2025-04-21', '16:30:00', '17:30:00', 'No-Show', 'Toothache - possible cavity', NULL, 3, '2025-04-06 18:30:00', '2025-05-18 08:53:15'),
(18, 1, 2, '2024-12-17', '11:30:00', '12:15:00', 'Completed', 'Bleeding gums', NULL, 3, '2024-12-08 18:30:00', '2025-05-18 08:53:15'),
(19, 1, 2, '2025-03-28', '14:30:00', '15:15:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-03-18 18:30:00', '2025-05-18 08:53:15'),
(20, 1, 2, '2025-03-14', '15:30:00', '16:15:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-02-27 18:30:00', '2025-05-18 08:53:15'),
(21, 1, 2, '2025-02-03', '11:00:00', '12:30:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-01-31 18:30:00', '2025-05-18 08:53:15'),
(22, 1, 2, '2024-12-12', '13:00:00', '14:30:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2024-12-10 18:30:00', '2025-05-18 08:53:15'),
(23, 1, 2, '2024-12-23', '15:30:00', '16:30:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2024-12-12 18:30:00', '2025-05-18 08:53:15'),
(24, 1, 2, '2025-06-24', '09:30:00', '11:00:00', 'Scheduled', 'Emergency - broken tooth', NULL, 3, '2025-06-12 18:30:00', '2025-05-18 08:53:15'),
(25, 1, 2, '2024-12-23', '10:30:00', '11:30:00', 'No-Show', 'Toothache - possible cavity', NULL, 3, '2024-12-10 18:30:00', '2025-05-18 08:53:15'),
(26, 1, 2, '2025-04-28', '11:30:00', '12:15:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-04-18 18:30:00', '2025-05-18 08:53:15'),
(27, 1, 2, '2025-02-17', '09:00:00', '10:00:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-02-13 18:30:00', '2025-05-18 08:53:15'),
(28, 1, 2, '2024-11-26', '11:00:00', '12:00:00', 'Completed', 'Root canal therapy', NULL, 3, '2024-11-23 18:30:00', '2025-05-18 08:53:15'),
(29, 1, 2, '2025-03-10', '11:30:00', '12:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-02-23 18:30:00', '2025-05-18 08:53:15'),
(30, 1, 2, '2025-06-05', '12:30:00', '14:00:00', 'Scheduled', 'Crown replacement', NULL, 3, '2025-05-28 18:30:00', '2025-05-18 08:53:15'),
(31, 1, 2, '2025-05-08', '16:30:00', '18:00:00', 'Canceled', 'Wisdom tooth pain', NULL, 3, '2025-04-29 18:30:00', '2025-05-18 08:53:15'),
(32, 1, 2, '2025-01-01', '16:00:00', '16:30:00', 'Completed', 'Dental implant consultation', NULL, 3, '2024-12-21 18:30:00', '2025-05-18 08:53:15'),
(33, 1, 2, '2025-07-03', '14:30:00', '15:15:00', 'Scheduled', 'Emergency - broken tooth', NULL, 3, '2025-06-19 18:30:00', '2025-05-18 08:53:15'),
(34, 1, 2, '2025-02-10', '14:00:00', '15:00:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-01-30 18:30:00', '2025-05-18 08:53:15'),
(35, 1, 2, '2024-11-25', '10:00:00', '11:00:00', 'Completed', 'Crown replacement', NULL, 3, '2024-11-22 18:30:00', '2025-05-18 08:53:15'),
(36, 1, 2, '2025-02-17', '14:00:00', '14:30:00', 'Completed', 'Filling replacement', NULL, 3, '2025-02-13 18:30:00', '2025-05-18 08:53:15'),
(37, 1, 2, '2025-05-05', '14:00:00', '14:30:00', 'Canceled', 'Filling replacement', NULL, 3, '2025-04-21 18:30:00', '2025-05-18 08:53:15'),
(38, 1, 2, '2025-06-13', '15:30:00', '17:00:00', 'Scheduled', 'Crown replacement', NULL, 3, '2025-06-07 18:30:00', '2025-05-18 08:53:15'),
(39, 1, 2, '2025-05-12', '12:00:00', '12:30:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-05-05 18:30:00', '2025-05-18 08:53:15'),
(40, 1, 2, '2025-07-07', '14:00:00', '15:00:00', 'Scheduled', 'Filling replacement', NULL, 3, '2025-06-23 18:30:00', '2025-05-18 08:53:15'),
(41, 1, 2, '2025-06-09', '15:30:00', '16:00:00', 'Scheduled', 'Toothache - possible cavity', NULL, 3, '2025-05-28 18:30:00', '2025-05-18 08:53:15'),
(42, 1, 2, '2025-01-14', '14:30:00', '15:15:00', 'Completed', 'Crown replacement', NULL, 3, '2025-01-10 18:30:00', '2025-05-18 08:53:15'),
(43, 1, 2, '2024-12-17', '15:30:00', '16:00:00', 'Completed', 'Filling replacement', NULL, 3, '2024-12-13 18:30:00', '2025-05-18 08:53:15'),
(44, 1, 2, '2025-04-15', '09:00:00', '10:30:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-04-04 18:30:00', '2025-05-18 08:53:15'),
(45, 1, 2, '2024-12-06', '15:30:00', '16:15:00', 'Completed', 'Dental implant consultation', NULL, 3, '2024-12-02 18:30:00', '2025-05-18 08:53:15'),
(46, 1, 2, '2024-11-25', '14:00:00', '14:30:00', 'Canceled', 'Emergency - broken tooth', NULL, 3, '2024-11-15 18:30:00', '2025-05-18 08:53:15'),
(47, 1, 2, '2025-01-29', '14:30:00', '16:00:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-01-16 18:30:00', '2025-05-18 08:53:15'),
(48, 1, 2, '2025-03-07', '16:00:00', '16:45:00', 'Canceled', 'Root canal therapy', NULL, 3, '2025-02-22 18:30:00', '2025-05-18 08:53:15'),
(49, 1, 2, '2025-03-25', '16:00:00', '16:30:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-03-12 18:30:00', '2025-05-18 08:53:15'),
(50, 1, 2, '2025-04-28', '09:00:00', '10:00:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-04-22 18:30:00', '2025-05-18 08:53:15'),
(51, 1, 2, '2025-04-18', '14:00:00', '15:30:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-04-04 18:30:00', '2025-05-18 08:53:15'),
(52, 1, 2, '2025-05-18', '12:30:00', '16:30:00', 'Scheduled', 'Teeth whitening', NULL, 3, '2025-06-24 18:30:00', '2025-05-18 08:53:16'),
(53, 1, 2, '2025-07-01', '16:30:00', '17:15:00', 'Scheduled', 'Root canal therapy', NULL, 3, '2025-06-16 18:30:00', '2025-05-18 08:53:15'),
(54, 1, 2, '2025-03-24', '14:00:00', '14:45:00', 'Canceled', 'Crown replacement', NULL, 3, '2025-03-16 18:30:00', '2025-05-18 08:53:15'),
(55, 1, 2, '2025-02-03', '12:30:00', '14:00:00', 'Canceled', 'Crown replacement', NULL, 3, '2025-01-22 18:30:00', '2025-05-18 08:53:15'),
(56, 1, 2, '2025-04-10', '09:30:00', '11:00:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-04-07 18:30:00', '2025-05-18 08:53:15'),
(57, 1, 2, '2024-11-26', '14:00:00', '14:45:00', 'Completed', 'Bleeding gums', NULL, 3, '2024-11-17 18:30:00', '2025-05-18 08:53:15'),
(58, 1, 2, '2025-02-24', '11:30:00', '12:30:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-02-21 18:30:00', '2025-05-18 08:53:15'),
(59, 1, 2, '2024-12-26', '15:30:00', '16:00:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2024-12-13 18:30:00', '2025-05-18 08:53:15'),
(60, 1, 2, '2025-05-15', '15:30:00', '17:00:00', 'Completed', 'Filling replacement', NULL, 3, '2025-05-13 18:30:00', '2025-05-18 08:53:15'),
(61, 1, 2, '2025-01-08', '14:30:00', '15:30:00', 'No-Show', 'Teeth whitening', NULL, 3, '2025-01-05 18:30:00', '2025-05-18 08:53:15'),
(62, 1, 2, '2025-06-09', '13:30:00', '14:00:00', 'Scheduled', 'Dental implant consultation', NULL, 3, '2025-06-04 18:30:00', '2025-05-18 08:53:15'),
(63, 1, 2, '2025-02-24', '09:30:00', '10:15:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-02-17 18:30:00', '2025-05-18 08:53:15'),
(64, 1, 2, '2025-03-31', '14:30:00', '15:30:00', 'Completed', 'Bleeding gums', NULL, 3, '2025-03-29 18:30:00', '2025-05-18 08:53:15'),
(65, 1, 2, '2025-01-27', '11:30:00', '13:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-01-18 18:30:00', '2025-05-18 08:53:15'),
(66, 1, 2, '2024-11-25', '16:30:00', '17:30:00', 'Completed', 'Root canal therapy', NULL, 3, '2024-11-16 18:30:00', '2025-05-18 08:53:15'),
(67, 1, 2, '2025-04-28', '16:30:00', '17:30:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-04-24 18:30:00', '2025-05-18 08:53:15'),
(68, 1, 2, '2025-02-04', '13:30:00', '14:30:00', 'Completed', 'Bleeding gums', NULL, 3, '2025-01-24 18:30:00', '2025-05-18 08:53:15'),
(69, 1, 2, '2025-05-02', '15:00:00', '15:45:00', 'Completed', 'Filling replacement', NULL, 3, '2025-04-22 18:30:00', '2025-05-18 08:53:15'),
(70, 1, 2, '2025-04-21', '13:30:00', '14:30:00', 'No-Show', 'Wisdom tooth pain', NULL, 3, '2025-04-13 18:30:00', '2025-05-18 08:53:15'),
(71, 1, 2, '2025-07-08', '13:00:00', '13:30:00', 'Scheduled', 'Emergency - broken tooth', NULL, 3, '2025-06-28 18:30:00', '2025-05-18 08:53:15'),
(72, 1, 2, '2024-12-09', '16:30:00', '18:00:00', 'Completed', 'Bleeding gums', NULL, 3, '2024-11-27 18:30:00', '2025-05-18 08:53:15'),
(73, 1, 2, '2025-05-13', '12:00:00', '13:30:00', 'Canceled', 'Bleeding gums', NULL, 3, '2025-05-08 18:30:00', '2025-05-18 08:53:15'),
(74, 1, 2, '2025-04-28', '16:00:00', '16:45:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-04-20 18:30:00', '2025-05-18 08:53:15'),
(75, 1, 2, '2025-05-14', '09:00:00', '09:30:00', 'Completed', 'Teeth whitening', NULL, 3, '2025-05-02 18:30:00', '2025-05-18 08:53:15'),
(76, 1, 2, '2025-04-02', '09:30:00', '10:30:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-03-31 18:30:00', '2025-05-18 08:53:15'),
(77, 1, 2, '2025-01-27', '12:00:00', '13:30:00', 'Completed', 'Bleeding gums', NULL, 3, '2025-01-19 18:30:00', '2025-05-18 08:53:15'),
(78, 1, 2, '2025-01-20', '10:00:00', '10:30:00', 'Completed', 'Teeth whitening', NULL, 3, '2025-01-05 18:30:00', '2025-05-18 08:53:15'),
(79, 1, 2, '2025-04-23', '15:00:00', '16:30:00', 'Completed', 'Crown replacement', NULL, 3, '2025-04-10 18:30:00', '2025-05-18 08:53:15'),
(80, 1, 2, '2025-01-22', '09:30:00', '10:30:00', 'No-Show', 'Teeth whitening', NULL, 3, '2025-01-09 18:30:00', '2025-05-18 08:53:15'),
(81, 1, 2, '2025-06-24', '14:30:00', '15:15:00', 'Scheduled', 'Teeth whitening', NULL, 3, '2025-06-19 18:30:00', '2025-05-18 08:53:15'),
(82, 1, 2, '2024-12-16', '12:30:00', '13:00:00', 'Completed', 'Teeth whitening', NULL, 3, '2024-12-09 18:30:00', '2025-05-18 08:53:15'),
(83, 1, 2, '2025-03-28', '10:00:00', '11:30:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-03-20 18:30:00', '2025-05-18 08:53:15'),
(84, 1, 2, '2025-05-18', '13:30:00', '14:00:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-05-07 18:30:00', '2025-05-18 08:53:16'),
(85, 1, 2, '2024-12-20', '16:00:00', '16:45:00', 'Completed', 'Filling replacement', NULL, 3, '2024-12-14 18:30:00', '2025-05-18 08:53:15'),
(86, 1, 2, '2025-06-10', '16:00:00', '17:30:00', 'Scheduled', 'Teeth whitening', NULL, 3, '2025-06-07 18:30:00', '2025-05-18 08:53:15'),
(87, 1, 2, '2025-05-12', '11:00:00', '11:45:00', 'No-Show', 'Emergency - broken tooth', NULL, 3, '2025-05-06 18:30:00', '2025-05-18 08:53:15'),
(88, 1, 2, '2025-04-17', '09:00:00', '10:00:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-04-07 18:30:00', '2025-05-18 08:53:15'),
(89, 1, 2, '2025-02-25', '15:00:00', '16:00:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-02-12 18:30:00', '2025-05-18 08:53:15'),
(90, 1, 2, '2024-12-09', '16:30:00', '18:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2024-11-30 18:30:00', '2025-05-18 08:53:15'),
(91, 1, 2, '2024-11-25', '15:30:00', '17:00:00', 'Completed', 'Bleeding gums', NULL, 3, '2024-11-10 18:30:00', '2025-05-18 08:53:15'),
(92, 1, 2, '2024-12-24', '13:00:00', '13:45:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2024-12-18 18:30:00', '2025-05-18 08:53:15'),
(93, 1, 2, '2025-04-22', '15:30:00', '16:00:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-04-09 18:30:00', '2025-05-18 08:53:15'),
(94, 1, 2, '2025-05-02', '16:30:00', '17:30:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-04-25 18:30:00', '2025-05-18 08:53:15'),
(95, 1, 2, '2025-01-27', '14:30:00', '15:00:00', 'Completed', 'Crown replacement', NULL, 3, '2025-01-17 18:30:00', '2025-05-18 08:53:15'),
(96, 1, 2, '2025-02-10', '13:00:00', '14:30:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-01-30 18:30:00', '2025-05-18 08:53:15'),
(97, 1, 2, '2024-11-20', '13:30:00', '14:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2024-11-16 18:30:00', '2025-05-18 08:53:15'),
(98, 1, 2, '2024-11-25', '14:00:00', '14:30:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2024-11-18 18:30:00', '2025-05-18 08:53:15'),
(99, 1, 2, '2025-02-11', '12:00:00', '13:30:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-02-07 18:30:00', '2025-05-18 08:53:15'),
(100, 1, 2, '2025-04-04', '15:00:00', '15:45:00', 'Canceled', 'Regular checkup and cleaning', NULL, 3, '2025-03-21 18:30:00', '2025-05-18 08:53:15'),
(101, 1, 2, '2025-05-18', '16:30:00', '15:30:00', 'Scheduled', 'Teeth whitening', NULL, 3, '2025-06-04 18:30:00', '2025-05-18 08:53:16'),
(102, 1, 2, '2025-06-30', '14:30:00', '16:00:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-06-16 18:30:00', '2025-05-18 08:53:15'),
(103, 1, 2, '2025-06-19', '13:00:00', '13:30:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-06-17 18:30:00', '2025-05-18 08:53:15'),
(104, 1, 2, '2025-04-25', '15:30:00', '17:00:00', 'Canceled', 'Emergency - broken tooth', NULL, 3, '2025-04-18 18:30:00', '2025-05-18 08:53:15'),
(105, 1, 2, '2024-12-02', '11:30:00', '12:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2024-11-25 18:30:00', '2025-05-18 08:53:15'),
(106, 1, 2, '2025-06-20', '14:30:00', '15:30:00', 'Scheduled', 'Dental implant consultation', NULL, 3, '2025-06-17 18:30:00', '2025-05-18 08:53:15'),
(107, 1, 2, '2025-01-21', '14:30:00', '15:30:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-01-06 18:30:00', '2025-05-18 08:53:15'),
(108, 1, 2, '2025-06-23', '09:00:00', '10:30:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-06-10 18:30:00', '2025-05-18 08:53:15'),
(109, 1, 2, '2025-05-06', '09:30:00', '10:00:00', 'Completed', 'Teeth whitening', NULL, 3, '2025-04-23 18:30:00', '2025-05-18 08:53:15'),
(110, 1, 2, '2025-02-21', '13:30:00', '14:15:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-02-10 18:30:00', '2025-05-18 08:53:15'),
(111, 1, 2, '2025-03-17', '09:30:00', '11:00:00', 'Completed', 'Crown replacement', NULL, 3, '2025-03-04 18:30:00', '2025-05-18 08:53:15'),
(112, 1, 2, '2025-06-05', '12:30:00', '14:00:00', 'Scheduled', 'Emergency - broken tooth', NULL, 3, '2025-05-27 18:30:00', '2025-05-18 08:53:15'),
(113, 1, 2, '2025-06-12', '11:30:00', '13:00:00', 'Scheduled', 'Dental implant consultation', NULL, 3, '2025-06-07 18:30:00', '2025-05-18 08:53:15'),
(114, 1, 2, '2025-05-20', '10:00:00', '11:00:00', 'Scheduled', 'Teeth whitening', NULL, 3, '2025-05-15 18:30:00', '2025-05-18 08:53:15'),
(115, 1, 2, '2025-03-03', '10:00:00', '10:45:00', 'No-Show', 'Emergency - broken tooth', NULL, 3, '2025-02-18 18:30:00', '2025-05-18 08:53:15'),
(116, 1, 2, '2024-12-27', '09:30:00', '10:15:00', 'Completed', 'Crown replacement', NULL, 3, '2024-12-14 18:30:00', '2025-05-18 08:53:15'),
(117, 1, 2, '2025-04-16', '11:30:00', '13:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-04-10 18:30:00', '2025-05-18 08:53:15'),
(118, 1, 2, '2025-07-14', '12:00:00', '12:45:00', 'Scheduled', 'Bleeding gums', NULL, 3, '2025-07-02 18:30:00', '2025-05-18 08:53:15'),
(119, 1, 2, '2025-07-02', '10:00:00', '10:30:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-06-18 18:30:00', '2025-05-18 08:53:15'),
(120, 1, 2, '2025-06-19', '09:30:00', '11:00:00', 'Scheduled', 'Root canal therapy', NULL, 3, '2025-06-11 18:30:00', '2025-05-18 08:53:15'),
(121, 1, 2, '2024-12-12', '16:00:00', '17:30:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2024-12-08 18:30:00', '2025-05-18 08:53:15'),
(122, 1, 2, '2025-05-30', '12:30:00', '13:00:00', 'Canceled', 'Regular checkup and cleaning', '\n\n--- CANCELED ---\nReason: Patient Request\nCanceled by: Reception Staff\nCanceled on: 2025-05-24 23:03:51', 3, '2025-05-16 18:30:00', '2025-05-24 17:33:51'),
(123, 1, 2, '2025-01-09', '10:00:00', '11:00:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-01-06 18:30:00', '2025-05-18 08:53:15'),
(124, 1, 2, '2025-03-10', '11:00:00', '11:45:00', 'Completed', 'Filling replacement', NULL, 3, '2025-02-27 18:30:00', '2025-05-18 08:53:15'),
(125, 1, 2, '2025-05-09', '12:00:00', '13:30:00', 'Completed', 'Crown replacement', NULL, 3, '2025-04-24 18:30:00', '2025-05-18 08:53:15'),
(126, 1, 2, '2025-02-24', '09:00:00', '09:30:00', 'Completed', 'Filling replacement', NULL, 3, '2025-02-16 18:30:00', '2025-05-18 08:53:15'),
(127, 1, 2, '2025-03-10', '16:30:00', '17:30:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-03-04 18:30:00', '2025-05-18 08:53:15'),
(128, 1, 2, '2024-11-26', '12:00:00', '13:00:00', 'Completed', 'Filling replacement', NULL, 3, '2024-11-12 18:30:00', '2025-05-18 08:53:15'),
(129, 1, 2, '2024-11-28', '14:00:00', '14:45:00', 'Completed', 'Filling replacement', NULL, 3, '2024-11-23 18:30:00', '2025-05-18 08:53:15'),
(130, 1, 2, '2025-07-09', '09:30:00', '10:00:00', 'Scheduled', 'Crown replacement', NULL, 3, '2025-07-07 18:30:00', '2025-05-18 08:53:15'),
(131, 1, 2, '2025-04-10', '13:00:00', '13:30:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-04-03 18:30:00', '2025-05-18 08:53:15'),
(132, 1, 2, '2024-12-04', '16:30:00', '18:00:00', 'Completed', 'Bleeding gums', NULL, 3, '2024-11-23 18:30:00', '2025-05-18 08:53:15'),
(133, 1, 2, '2025-06-09', '12:30:00', '13:30:00', 'Scheduled', 'Regular checkup and cleaning', NULL, 3, '2025-06-05 18:30:00', '2025-05-18 08:53:15'),
(134, 1, 2, '2025-04-11', '09:00:00', '09:30:00', 'No-Show', 'Wisdom tooth pain', NULL, 3, '2025-04-06 18:30:00', '2025-05-18 08:53:15'),
(135, 1, 2, '2024-12-16', '16:00:00', '17:30:00', 'No-Show', 'Regular checkup and cleaning', NULL, 3, '2024-12-06 18:30:00', '2025-05-18 08:53:15'),
(136, 1, 2, '2025-01-30', '13:30:00', '14:00:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-01-25 18:30:00', '2025-05-18 08:53:15'),
(137, 1, 2, '2025-04-25', '15:00:00', '16:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-04-10 18:30:00', '2025-05-18 08:53:15'),
(138, 1, 2, '2025-03-21', '13:00:00', '13:30:00', 'No-Show', 'Root canal therapy', NULL, 3, '2025-03-09 18:30:00', '2025-05-18 08:53:15'),
(139, 1, 2, '2025-06-30', '13:30:00', '15:00:00', 'Scheduled', 'Toothache - possible cavity', NULL, 3, '2025-06-17 18:30:00', '2025-05-18 08:53:15'),
(140, 1, 2, '2025-04-10', '12:00:00', '13:00:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-04-03 18:30:00', '2025-05-18 08:53:15'),
(141, 1, 2, '2025-02-26', '09:30:00', '10:00:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-02-16 18:30:00', '2025-05-18 08:53:15'),
(142, 1, 2, '2025-01-31', '15:30:00', '16:15:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-01-24 18:30:00', '2025-05-18 08:53:15'),
(143, 1, 2, '2025-04-14', '16:30:00', '18:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-04-10 18:30:00', '2025-05-18 08:53:15'),
(144, 1, 2, '2024-12-16', '13:00:00', '14:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2024-12-10 18:30:00', '2025-05-18 08:53:15'),
(145, 1, 2, '2025-04-22', '13:30:00', '14:00:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-04-18 18:30:00', '2025-05-18 08:53:15'),
(146, 1, 2, '2025-01-02', '14:30:00', '15:30:00', 'Canceled', 'Emergency - broken tooth', NULL, 3, '2024-12-31 18:30:00', '2025-05-18 08:53:15'),
(147, 1, 2, '2025-03-26', '11:00:00', '11:30:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-03-18 18:30:00', '2025-05-18 08:53:15'),
(148, 1, 2, '2024-12-02', '09:00:00', '10:00:00', 'Completed', 'Crown replacement', NULL, 3, '2024-11-17 18:30:00', '2025-05-18 08:53:15'),
(149, 1, 2, '2025-03-17', '14:30:00', '15:15:00', 'Completed', 'Filling replacement', NULL, 3, '2025-03-04 18:30:00', '2025-05-18 08:53:15'),
(150, 1, 2, '2025-05-18', '09:00:00', '13:30:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-06-14 18:30:00', '2025-05-18 08:53:16'),
(151, 1, 2, '2025-04-28', '14:30:00', '15:30:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-04-22 18:30:00', '2025-05-18 08:53:15'),
(152, 1, 2, '2024-12-16', '11:30:00', '12:00:00', 'Completed', 'Bleeding gums', NULL, 3, '2024-12-02 18:30:00', '2025-05-18 08:53:15'),
(153, 1, 2, '2025-01-29', '16:30:00', '18:00:00', 'Completed', 'Filling replacement', NULL, 3, '2025-01-19 18:30:00', '2025-05-18 08:53:15'),
(154, 1, 2, '2025-01-23', '16:30:00', '17:00:00', 'Completed', 'Bleeding gums', NULL, 3, '2025-01-20 18:30:00', '2025-05-18 08:53:15'),
(155, 1, 2, '2025-03-31', '16:00:00', '17:30:00', 'No-Show', 'Root canal therapy', NULL, 3, '2025-03-25 18:30:00', '2025-05-18 08:53:15'),
(156, 1, 2, '2025-01-06', '09:30:00', '10:30:00', 'Completed', 'Filling replacement', NULL, 3, '2024-12-30 18:30:00', '2025-05-18 08:53:15'),
(157, 1, 2, '2025-02-19', '15:30:00', '16:00:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-02-16 18:30:00', '2025-05-18 08:53:15'),
(158, 1, 2, '2025-02-24', '16:30:00', '18:00:00', 'Completed', 'Filling replacement', NULL, 3, '2025-02-22 18:30:00', '2025-05-18 08:53:15'),
(159, 1, 2, '2024-12-09', '16:00:00', '16:45:00', 'No-Show', 'Teeth whitening', NULL, 3, '2024-11-30 18:30:00', '2025-05-18 08:53:15'),
(160, 1, 2, '2024-12-30', '15:00:00', '15:30:00', 'Completed', 'Bleeding gums', NULL, 3, '2024-12-28 18:30:00', '2025-05-18 08:53:15'),
(161, 1, 2, '2024-12-18', '16:00:00', '17:00:00', 'Completed', 'Crown replacement', NULL, 3, '2024-12-03 18:30:00', '2025-05-18 08:53:15'),
(162, 1, 2, '2025-04-29', '14:00:00', '15:00:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-04-18 18:30:00', '2025-05-18 08:53:15'),
(163, 1, 2, '2025-02-17', '15:00:00', '16:00:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-02-14 18:30:00', '2025-05-18 08:53:15'),
(164, 1, 2, '2024-11-25', '13:30:00', '14:30:00', 'Completed', 'Filling replacement', NULL, 3, '2024-11-18 18:30:00', '2025-05-18 08:53:15'),
(165, 1, 2, '2025-07-11', '13:00:00', '13:45:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-06-28 18:30:00', '2025-05-18 08:53:15'),
(166, 1, 2, '2025-04-29', '15:00:00', '15:30:00', 'Completed', 'Teeth whitening', NULL, 3, '2025-04-21 18:30:00', '2025-05-18 08:53:15'),
(167, 1, 2, '2025-01-31', '16:30:00', '17:00:00', 'Completed', 'Crown replacement', NULL, 3, '2025-01-16 18:30:00', '2025-05-18 08:53:15'),
(168, 1, 2, '2025-05-18', '16:30:00', '14:45:00', 'Scheduled', 'Root canal therapy', NULL, 3, '2025-05-12 18:30:00', '2025-05-18 08:53:16'),
(169, 1, 2, '2025-03-07', '14:00:00', '14:45:00', 'Completed', 'Teeth whitening', NULL, 3, '2025-02-20 18:30:00', '2025-05-18 08:53:15'),
(170, 1, 2, '2025-01-16', '13:00:00', '14:30:00', 'Completed', 'Wisdom tooth pain', NULL, 3, '2025-01-12 18:30:00', '2025-05-18 08:53:15'),
(171, 1, 2, '2024-12-09', '14:00:00', '15:00:00', 'Canceled', 'Regular checkup and cleaning', NULL, 3, '2024-12-02 18:30:00', '2025-05-18 08:53:15'),
(172, 1, 2, '2025-01-07', '15:00:00', '16:00:00', 'Completed', 'Root canal therapy', NULL, 3, '2024-12-29 18:30:00', '2025-05-18 08:53:15'),
(173, 1, 2, '2025-02-03', '11:30:00', '12:15:00', 'Completed', 'Filling replacement', NULL, 3, '2025-01-26 18:30:00', '2025-05-18 08:53:15'),
(174, 1, 2, '2024-12-17', '13:30:00', '14:00:00', 'Completed', 'Filling replacement', NULL, 3, '2024-12-03 18:30:00', '2025-05-18 08:53:15'),
(175, 1, 2, '2025-01-22', '15:00:00', '16:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-01-07 18:30:00', '2025-05-18 08:53:15'),
(176, 1, 2, '2025-04-21', '10:30:00', '11:00:00', 'No-Show', 'Toothache - possible cavity', NULL, 3, '2025-04-12 18:30:00', '2025-05-18 08:53:15'),
(177, 1, 2, '2025-01-29', '12:00:00', '13:00:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-01-23 18:30:00', '2025-05-18 08:53:15'),
(178, 1, 2, '2025-01-30', '15:00:00', '16:00:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-01-23 18:30:00', '2025-05-18 08:53:15'),
(179, 1, 2, '2025-05-09', '14:00:00', '14:45:00', 'Completed', 'Dental implant consultation', NULL, 3, '2025-04-27 18:30:00', '2025-05-18 08:53:15'),
(180, 1, 2, '2025-05-05', '16:00:00', '16:30:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-05-02 18:30:00', '2025-05-18 08:53:15'),
(181, 1, 2, '2024-12-11', '11:00:00', '11:30:00', 'Completed', 'Dental implant consultation', NULL, 3, '2024-11-26 18:30:00', '2025-05-18 08:53:15'),
(182, 1, 2, '2025-05-12', '14:00:00', '14:30:00', 'Completed', 'Root canal therapy', NULL, 3, '2025-05-06 18:30:00', '2025-05-18 08:53:15'),
(183, 1, 2, '2024-12-16', '14:30:00', '15:30:00', 'Canceled', 'Toothache - possible cavity', NULL, 3, '2024-12-08 18:30:00', '2025-05-18 08:53:15'),
(184, 1, 2, '2025-05-21', '13:00:00', '13:30:00', 'Scheduled', 'Wisdom tooth pain', NULL, 3, '2025-05-15 18:30:00', '2025-05-18 08:53:15'),
(185, 1, 2, '2025-01-10', '09:00:00', '09:30:00', 'Completed', 'Toothache - possible cavity', NULL, 3, '2025-01-08 18:30:00', '2025-05-18 08:53:15'),
(186, 1, 2, '2025-06-09', '13:00:00', '14:30:00', 'Scheduled', 'Filling replacement', NULL, 3, '2025-05-26 18:30:00', '2025-05-18 08:53:15'),
(187, 1, 2, '2025-06-16', '09:00:00', '09:30:00', 'Scheduled', 'Crown replacement', NULL, 3, '2025-06-03 18:30:00', '2025-05-18 08:53:15'),
(188, 1, 2, '2025-05-15', '12:00:00', '12:30:00', 'Canceled', 'Emergency - broken tooth', NULL, 3, '2025-05-05 18:30:00', '2025-05-18 08:53:15'),
(189, 1, 2, '2025-07-01', '10:00:00', '11:30:00', 'Scheduled', 'Teeth whitening', NULL, 3, '2025-06-24 18:30:00', '2025-05-18 08:53:15'),
(190, 1, 2, '2024-11-29', '15:00:00', '16:00:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2024-11-19 18:30:00', '2025-05-18 08:53:15'),
(191, 1, 2, '2025-04-15', '16:30:00', '17:00:00', 'Completed', 'Bleeding gums', NULL, 3, '2025-04-06 18:30:00', '2025-05-18 08:53:15'),
(192, 1, 2, '2025-03-31', '09:00:00', '10:30:00', 'Completed', 'Emergency - broken tooth', NULL, 3, '2025-03-17 18:30:00', '2025-05-18 08:53:15'),
(193, 1, 2, '2025-06-30', '11:30:00', '12:15:00', 'Scheduled', 'Crown replacement', NULL, 3, '2025-06-19 18:30:00', '2025-05-18 08:53:15'),
(194, 1, 2, '2025-05-09', '15:30:00', '17:00:00', 'No-Show', 'Dental implant consultation', NULL, 3, '2025-05-07 18:30:00', '2025-05-18 08:53:15'),
(195, 1, 2, '2025-07-16', '09:30:00', '10:30:00', 'Scheduled', 'Crown replacement', NULL, 3, '2025-07-09 18:30:00', '2025-05-18 08:53:15'),
(196, 1, 2, '2025-06-04', '11:30:00', '12:15:00', 'Scheduled', 'Filling replacement', NULL, 3, '2025-05-23 18:30:00', '2025-05-18 08:53:15'),
(197, 1, 2, '2025-01-27', '09:30:00', '11:00:00', 'Canceled', 'Root canal therapy', NULL, 3, '2025-01-22 18:30:00', '2025-05-18 08:53:15'),
(198, 1, 2, '2025-02-13', '11:00:00', '12:30:00', 'Completed', 'Teeth whitening', NULL, 3, '2025-01-29 18:30:00', '2025-05-18 08:53:15'),
(199, 1, 2, '2025-05-12', '12:00:00', '12:30:00', 'Completed', 'Regular checkup and cleaning', NULL, 3, '2025-04-30 18:30:00', '2025-05-18 08:53:15'),
(200, 1, 2, '2025-05-27', '09:30:00', '11:00:00', 'Scheduled', 'Teeth whitening', NULL, 3, '2025-05-17 18:30:00', '2025-05-18 08:53:15'),
(201, 1, 2, '2025-01-01', '12:00:00', '12:45:00', 'No-Show', 'Wisdom tooth pain', NULL, 3, '2024-12-20 18:30:00', '2025-05-18 08:53:15'),
(202, 1, 2, '2025-05-22', '14:30:00', '13:00:00', 'Scheduled', 'Regular checkup and cleaning', NULL, 3, '2025-05-18 08:53:16', '2025-05-18 08:53:16'),
(204, 2, 2, '2025-05-18', '16:30:00', '17:00:00', 'Scheduled', '', '', 3, '2025-05-18 13:10:12', '2025-05-18 13:10:12'),
(205, 2, 2, '2025-05-18', '17:00:00', '17:30:00', 'Scheduled', '', '', 3, '2025-05-18 13:11:30', '2025-05-18 13:11:30'),
(206, 2, 2, '2025-05-18', '17:30:00', '18:00:00', 'Scheduled', '', '', 3, '2025-05-18 13:33:15', '2025-05-18 13:33:15'),
(207, 2, 2, '2025-05-22', '09:00:00', '09:30:00', 'Scheduled', '', '', 3, '2025-05-22 13:53:55', '2025-05-22 13:53:55'),
(208, 1, 2, '2025-05-22', '09:30:00', '10:00:00', 'Scheduled', '', '', 3, '2025-05-22 13:57:37', '2025-05-22 13:57:37'),
(209, 1, 2, '2025-05-22', '09:30:00', '10:00:00', 'Scheduled', '', '', 3, '2025-05-22 14:04:28', '2025-05-22 14:04:28'),
(210, 1, 2, '2025-05-23', '09:00:00', '09:30:00', 'Scheduled', '', '', 3, '2025-05-22 14:07:00', '2025-05-22 14:07:00'),
(211, 1, 2, '2025-05-23', '10:00:00', '10:30:00', 'Scheduled', '', '', 3, '2025-05-22 14:09:27', '2025-05-22 14:09:27'),
(212, 1, 2, '2025-05-24', '09:00:00', '09:30:00', 'Scheduled', '', '', 3, '2025-05-22 16:13:25', '2025-05-22 16:13:25'),
(213, 1, 2, '2025-05-25', '09:00:00', '09:30:00', 'Canceled', '', '\n\n--- CANCELED ---\nReason: Patient Request\nCanceled by: Reception Staff\nCanceled on: 2025-05-24 13:24:41', 3, '2025-05-22 16:27:38', '2025-05-24 07:54:41'),
(214, 1, 2, '2025-05-28', '09:30:00', '10:00:00', 'Scheduled', '', '', 3, '2025-05-22 16:46:00', '2025-05-22 16:46:00'),
(215, 1, 2, '2025-05-22', '20:00:00', '20:30:00', 'Scheduled', '', '', 3, '2025-05-22 18:25:34', '2025-05-22 18:25:34'),
(216, 1, 2, '2025-05-22', '20:30:00', '21:00:00', 'Scheduled', '', '', 3, '2025-05-22 18:27:43', '2025-05-22 18:27:43'),
(217, 1, 2, '2025-05-22', '17:30:00', '18:00:00', 'Scheduled', '', '', 3, '2025-05-22 18:47:54', '2025-05-22 18:47:54'),
(218, 1, 2, '2025-05-22', '19:00:00', '19:30:00', 'Scheduled', '', '', 3, '2025-05-22 18:50:08', '2025-05-22 18:50:08'),
(219, 1, 2, '2025-05-23', '20:30:00', '21:00:00', 'Scheduled', '  ffff', '', 3, '2025-05-23 18:30:00', '2025-05-23 18:30:00'),
(220, 1, 2, '2025-05-23', '20:00:00', '20:30:00', 'Scheduled', 'ddddddd', '', 3, '2025-05-23 18:42:28', '2025-05-23 18:42:28'),
(221, 1, 2, '2025-05-23', '19:30:00', '20:00:00', 'Scheduled', 'ddddddd', '', 3, '2025-05-23 18:50:35', '2025-05-23 18:50:35'),
(222, 1, 2, '2025-05-23', '13:00:00', '13:30:00', 'Scheduled', 'ccccccccc', '', 3, '2025-05-23 18:59:49', '2025-05-23 18:59:49'),
(223, 1, 2, '2025-05-23', '18:30:00', '19:00:00', 'Scheduled', 'ccccccccc', '', 3, '2025-05-23 19:05:34', '2025-05-23 19:05:34'),
(224, 1, 2, '2025-05-26', '20:30:00', '21:00:00', 'Scheduled', 'ddddddd', '', 3, '2025-05-24 06:42:41', '2025-05-24 06:42:41'),
(225, 1, 2, '2025-05-26', '20:00:00', '20:30:00', 'Scheduled', 'ddddddd', '', 3, '2025-05-24 06:44:52', '2025-05-24 06:44:52'),
(226, 1, 2, '2025-05-26', '19:00:00', '19:30:00', 'Scheduled', 'ssssssss', '', 3, '2025-05-24 06:51:04', '2025-05-24 06:51:04'),
(227, 1, 2, '2025-05-26', '19:30:00', '20:00:00', 'Canceled', 'ccccccc', '\n\n--- CANCELED ---\nReason: Patient Illness\nCanceled by: Reception Staff\nCanceled on: 2025-05-24 13:25:07', 3, '2025-05-24 07:05:26', '2025-05-24 07:55:07');

-- --------------------------------------------------------

--
-- Table structure for table `dental_notes`
--

CREATE TABLE `dental_notes` (
  `note_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `note_content` text NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dental_teeth`
--

CREATE TABLE `dental_teeth` (
  `tooth_id` tinyint(4) NOT NULL,
  `universal_number` varchar(5) NOT NULL,
  `quadrant_number` tinyint(4) DEFAULT NULL,
  `fdi_number` varchar(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `quadrant` enum('Upper Right','Upper Left','Lower Left','Lower Right') NOT NULL,
  `type` enum('Molar','Premolar','Canine','Incisor','Wisdom') NOT NULL,
  `is_permanent` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dental_teeth`
--

INSERT INTO `dental_teeth` (`tooth_id`, `universal_number`, `quadrant_number`, `fdi_number`, `name`, `quadrant`, `type`, `is_permanent`) VALUES
(1, '1', 1, '18', 'Upper Right Third Molar', 'Upper Right', 'Molar', 1),
(2, '2', 2, '17', 'Upper Right Second Molar', 'Upper Right', 'Molar', 1),
(3, '3', 3, '16', 'Upper Right First Molar', 'Upper Right', 'Molar', 1),
(4, '4', 4, '15', 'Upper Right Second Premolar', 'Upper Right', 'Premolar', 1),
(5, '5', 5, '14', 'Upper Right First Premolar', 'Upper Right', 'Premolar', 1),
(6, '6', 6, '13', 'Upper Right Canine', 'Upper Right', 'Canine', 1),
(7, '7', 7, '12', 'Upper Right Lateral Incisor', 'Upper Right', 'Incisor', 1),
(8, '8', 8, '11', 'Upper Right Central Incisor', 'Upper Right', 'Incisor', 1),
(9, '9', 1, '21', 'Upper Left Central Incisor', 'Upper Left', 'Incisor', 1),
(10, '10', 2, '22', 'Upper Left Lateral Incisor', 'Upper Left', 'Incisor', 1),
(11, '11', 3, '23', 'Upper Left Canine', 'Upper Left', 'Canine', 1),
(12, '12', 4, '24', 'Upper Left First Premolar', 'Upper Left', 'Premolar', 1),
(13, '13', 5, '25', 'Upper Left Second Premolar', 'Upper Left', 'Premolar', 1),
(14, '14', 6, '26', 'Upper Left First Molar', 'Upper Left', 'Molar', 1),
(15, '15', 7, '27', 'Upper Left Second Molar', 'Upper Left', 'Molar', 1),
(16, '16', 8, '28', 'Upper Left Third Molar', 'Upper Left', 'Molar', 1),
(17, '17', 1, '38', 'Lower Left Third Molar', 'Lower Left', 'Molar', 1),
(18, '18', 2, '37', 'Lower Left Second Molar', 'Lower Left', 'Molar', 1),
(19, '19', 3, '36', 'Lower Left First Molar', 'Lower Left', 'Molar', 1),
(20, '20', 4, '35', 'Lower Left Second Premolar', 'Lower Left', 'Premolar', 1),
(21, '21', 5, '34', 'Lower Left First Premolar', 'Lower Left', 'Premolar', 1),
(22, '22', 6, '33', 'Lower Left Canine', 'Lower Left', 'Canine', 1),
(23, '23', 7, '32', 'Lower Left Lateral Incisor', 'Lower Left', 'Incisor', 1),
(24, '24', 8, '31', 'Lower Left Central Incisor', 'Lower Left', 'Incisor', 1),
(25, '25', 1, '41', 'Lower Right Central Incisor', 'Lower Right', 'Incisor', 1),
(26, '26', 2, '42', 'Lower Right Lateral Incisor', 'Lower Right', 'Incisor', 1),
(27, '27', 3, '43', 'Lower Right Canine', 'Lower Right', 'Canine', 1),
(28, '28', 4, '44', 'Lower Right First Premolar', 'Lower Right', 'Premolar', 1),
(29, '29', 5, '45', 'Lower Right Second Premolar', 'Lower Right', 'Premolar', 1),
(30, '30', 6, '46', 'Lower Right First Molar', 'Lower Right', 'Molar', 1),
(31, '31', 7, '47', 'Lower Right Second Molar', 'Lower Right', 'Molar', 1),
(32, '32', 8, '48', 'Lower Right Third Molar', 'Lower Right', 'Molar', 1);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `file_number` varchar(20) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `file_number`, `photo`, `first_name`, `last_name`, `gender`, `date_of_birth`, `phone`, `email`, `address`, `city`, `state`, `postal_code`, `blood_group`, `allergies`, `medical_history`, `created_at`, `updated_at`) VALUES
(1, 'PT20230001', NULL, 'John', 'Doe', 'Male', '1985-06-15', '0702776622', 'john.doe@example.com', '123 Main St, Anytown, USA', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-17 18:13:53', '2025-05-22 18:49:41'),
(2, 'PT2025-0001', NULL, 'Kosala', 'Muthukumarana', 'Male', '1988-01-04', '(071) 184-1287', '', '393/11 Thalangama North', 'Colombo', '', '10600', 'O+', '', '', '2025-05-18 10:03:20', '2025-05-24 08:14:56');

-- --------------------------------------------------------

--
-- Table structure for table `patient_medical_history`
--

CREATE TABLE `patient_medical_history` (
  `history_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `allergies` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `recorded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_medical_history`
--

INSERT INTO `patient_medical_history` (`history_id`, `patient_id`, `allergies`, `medical_conditions`, `current_medications`, `recorded_date`, `recorded_by`) VALUES
(1, 1, 'Penicillin', 'Hypertension', 'Lisinopril 10mg daily', '2025-05-18 14:50:59', 2),
(2, 1, 'Penicillin', 'Hypertension', 'Lisinopril 10mg daily', '2025-05-17 18:30:00', 2);

-- --------------------------------------------------------

--
-- Table structure for table `patient_notes`
--

CREATE TABLE `patient_notes` (
  `note_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `added_by` int(11) NOT NULL,
  `note_content` text NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_notes`
--

INSERT INTO `patient_notes` (`note_id`, `patient_id`, `added_by`, `note_content`, `date_added`) VALUES
(1, 1, 2, 'Initial consultation notes. Patient reports sensitivity to cold foods.', '2025-05-18 14:50:59');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Credit Card','Debit Card','Bank Transfer','Insurance','Other') NOT NULL,
  `payment_date` date NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `record_id`, `patient_id`, `amount`, `payment_method`, `payment_date`, `transaction_reference`, `notes`, `received_by`, `created_at`) VALUES
(1, 1, 1, 80.00, 'Credit Card', '2023-06-20', NULL, NULL, 3, '2025-05-17 18:13:53'),
(2, 2, 1, 124.69, 'Insurance', '2025-01-14', 'TXN022657', 'Insurance Claim #63601', 3, '2025-05-18 08:53:15'),
(3, 3, 1, 73.10, 'Bank Transfer', '2025-01-20', 'TXN674027', NULL, 3, '2025-05-18 08:53:15'),
(4, 4, 1, 52.09, 'Insurance', '2025-01-20', 'TXN673838', 'Insurance Claim #68424', 3, '2025-05-18 08:53:15'),
(5, 5, 1, 127.50, 'Insurance', '2024-12-06', 'TXN813917', 'Insurance Claim #47861', 3, '2025-05-18 08:53:15'),
(6, 6, 1, 184.23, 'Credit Card', '2025-01-07', 'TXN786286', 'Auth Code: 5BE5E6', 3, '2025-05-18 08:53:15'),
(7, 7, 1, 113.55, 'Credit Card', '2024-12-12', 'TXN990489', 'Auth Code: 9452B0', 3, '2025-05-18 08:53:15'),
(8, 8, 1, 159.37, 'Insurance', '2025-03-24', 'TXN905045', 'Insurance Claim #12271', 3, '2025-05-18 08:53:15'),
(9, 9, 1, 86.90, 'Cash', '2025-03-28', 'TXN252484', NULL, 3, '2025-05-18 08:53:15'),
(10, 10, 1, 85.80, 'Bank Transfer', '2025-03-28', 'TXN662310', NULL, 3, '2025-05-18 08:53:15'),
(11, 11, 1, 185.83, 'Debit Card', '2024-12-11', 'TXN721389', NULL, 3, '2025-05-18 08:53:15'),
(12, 12, 1, 113.32, 'Debit Card', '2024-12-09', 'TXN789406', NULL, 3, '2025-05-18 08:53:15'),
(13, 13, 1, 145.60, 'Bank Transfer', '2024-12-05', 'TXN333771', NULL, 3, '2025-05-18 08:53:15'),
(14, 14, 1, 83.47, 'Debit Card', '2025-04-13', 'TXN541840', NULL, 3, '2025-05-18 08:53:15'),
(15, 15, 1, 85.83, 'Credit Card', '2025-04-18', 'TXN815728', 'Auth Code: 16138B', 3, '2025-05-18 08:53:15'),
(16, 16, 1, 239.35, 'Cash', '2025-03-05', 'TXN089175', NULL, 3, '2025-05-18 08:53:15'),
(17, 17, 1, 257.05, 'Credit Card', '2024-12-22', 'TXN268607', 'Auth Code: D44B65', 3, '2025-05-18 08:53:15'),
(18, 18, 1, 146.97, 'Insurance', '2025-04-04', 'TXN043707', 'Insurance Claim #49059', 3, '2025-05-18 08:53:15'),
(19, 19, 1, 168.66, 'Cash', '2025-03-19', 'TXN196590', NULL, 3, '2025-05-18 08:53:15'),
(20, 20, 1, 79.58, 'Debit Card', '2025-03-14', 'TXN692542', NULL, 3, '2025-05-18 08:53:15'),
(21, 21, 1, 111.15, 'Bank Transfer', '2025-02-05', 'TXN618131', NULL, 3, '2025-05-18 08:53:15'),
(22, 22, 1, 120.18, 'Bank Transfer', '2025-02-10', 'TXN923108', NULL, 3, '2025-05-18 08:53:15'),
(23, 23, 1, 263.95, 'Bank Transfer', '2025-02-03', 'TXN557698', NULL, 3, '2025-05-18 08:53:15'),
(24, 24, 1, 86.88, 'Insurance', '2024-12-14', 'TXN518676', 'Insurance Claim #72595', 3, '2025-05-18 08:53:15'),
(25, 25, 1, 111.91, 'Cash', '2024-12-17', 'TXN090891', NULL, 3, '2025-05-18 08:53:16'),
(26, 26, 1, 126.54, 'Cash', '2024-12-14', 'TXN373953', NULL, 3, '2025-05-18 08:53:16'),
(27, 27, 1, 52.66, 'Cash', '2024-12-30', 'TXN903881', NULL, 3, '2025-05-18 08:53:16'),
(28, 28, 1, 166.32, 'Cash', '2024-12-27', 'TXN741251', NULL, 3, '2025-05-18 08:53:16'),
(29, 29, 1, 119.87, 'Bank Transfer', '2024-12-26', 'TXN034715', NULL, 3, '2025-05-18 08:53:16'),
(30, 30, 1, 121.60, 'Bank Transfer', '2025-04-30', 'TXN865959', NULL, 3, '2025-05-18 08:53:16'),
(31, 31, 1, 163.61, 'Bank Transfer', '2025-05-03', 'TXN212124', NULL, 3, '2025-05-18 08:53:16'),
(32, 32, 1, 54.10, 'Insurance', '2025-02-17', 'TXN616105', 'Insurance Claim #94531', 3, '2025-05-18 08:53:16'),
(33, 33, 1, 160.96, 'Debit Card', '2024-11-27', 'TXN044609', NULL, 3, '2025-05-18 08:53:16'),
(34, 34, 1, 164.74, 'Insurance', '2024-12-02', 'TXN730222', 'Insurance Claim #8471', 3, '2025-05-18 08:53:16'),
(35, 35, 1, 116.96, 'Insurance', '2024-12-02', 'TXN533636', 'Insurance Claim #10942', 3, '2025-05-18 08:53:16'),
(36, 36, 1, 183.90, 'Debit Card', '2025-03-11', 'TXN669433', NULL, 3, '2025-05-18 08:53:16'),
(37, 37, 1, 262.94, 'Cash', '2025-03-10', 'TXN752223', NULL, 3, '2025-05-18 08:53:16'),
(38, 38, 1, 125.06, 'Debit Card', '2025-03-10', 'TXN788666', NULL, 3, '2025-05-18 08:53:16'),
(39, 39, 1, 263.65, 'Insurance', '2025-01-01', 'TXN582484', 'Insurance Claim #68793', 3, '2025-05-18 08:53:16'),
(40, 40, 1, 151.57, 'Credit Card', '2025-02-17', 'TXN358967', 'Auth Code: ACAFCF', 3, '2025-05-18 08:53:16'),
(41, 41, 1, 169.24, 'Cash', '2025-02-13', 'TXN932755', NULL, 3, '2025-05-18 08:53:16'),
(42, 42, 1, 186.43, 'Debit Card', '2024-11-25', 'TXN738777', NULL, 3, '2025-05-18 08:53:16'),
(43, 43, 1, 126.71, 'Bank Transfer', '2024-12-01', 'TXN847647', NULL, 3, '2025-05-18 08:53:16'),
(44, 44, 1, 153.73, 'Bank Transfer', '2024-11-25', 'TXN902217', NULL, 3, '2025-05-18 08:53:16'),
(45, 45, 1, 74.34, 'Bank Transfer', '2025-02-18', 'TXN801102', NULL, 3, '2025-05-18 08:53:16'),
(46, 46, 1, 272.06, 'Bank Transfer', '2025-02-24', 'TXN698485', NULL, 3, '2025-05-18 08:53:16'),
(47, 47, 1, 45.08, 'Bank Transfer', '2025-02-24', 'TXN691380', NULL, 3, '2025-05-18 08:53:16'),
(48, 48, 1, 194.56, 'Debit Card', '2025-05-13', 'TXN566083', NULL, 3, '2025-05-18 08:53:16'),
(49, 49, 1, 174.22, 'Debit Card', '2025-05-13', 'TXN164848', NULL, 3, '2025-05-18 08:53:16'),
(50, 50, 1, 117.88, 'Bank Transfer', '2025-01-15', 'TXN690422', NULL, 3, '2025-05-18 08:53:16'),
(51, 51, 1, 167.20, 'Insurance', '2025-01-16', 'TXN462215', 'Insurance Claim #55917', 3, '2025-05-18 08:53:16'),
(52, 52, 1, 54.07, 'Credit Card', '2024-12-21', 'TXN972653', 'Auth Code: 24F0B9', 3, '2025-05-18 08:53:16'),
(53, 53, 1, 197.76, 'Bank Transfer', '2024-12-19', 'TXN345557', NULL, 3, '2025-05-18 08:53:16'),
(54, 54, 1, 145.37, 'Bank Transfer', '2024-12-19', 'TXN228711', NULL, 3, '2025-05-18 08:53:16'),
(55, 55, 1, 160.32, 'Cash', '2025-04-15', 'TXN457969', NULL, 3, '2025-05-18 08:53:16'),
(56, 56, 1, 47.88, 'Debit Card', '2025-04-18', 'TXN557902', NULL, 3, '2025-05-18 08:53:16'),
(57, 57, 1, 255.06, 'Debit Card', '2024-12-10', 'TXN265103', NULL, 3, '2025-05-18 08:53:16'),
(58, 58, 1, 233.12, 'Bank Transfer', '2024-12-07', 'TXN740428', NULL, 3, '2025-05-18 08:53:16'),
(59, 59, 1, 182.77, 'Credit Card', '2025-02-03', 'TXN493493', 'Auth Code: FA237B', 3, '2025-05-18 08:53:16'),
(60, 60, 1, 80.31, 'Cash', '2025-03-27', 'TXN064082', NULL, 3, '2025-05-18 08:53:16'),
(61, 61, 1, 128.70, 'Cash', '2025-03-30', 'TXN548954', NULL, 3, '2025-05-18 08:53:16'),
(62, 62, 1, 243.17, 'Credit Card', '2025-04-28', 'TXN100516', 'Auth Code: 2C3BBE', 3, '2025-05-18 08:53:16'),
(63, 63, 1, 244.53, 'Bank Transfer', '2025-04-28', 'TXN817809', NULL, 3, '2025-05-18 08:53:16'),
(64, 64, 1, 172.75, 'Insurance', '2025-04-25', 'TXN115816', 'Insurance Claim #84343', 3, '2025-05-18 08:53:16'),
(65, 65, 1, 231.82, 'Insurance', '2025-04-21', 'TXN957198', 'Insurance Claim #33869', 3, '2025-05-18 08:53:16'),
(66, 66, 1, 163.12, 'Cash', '2025-04-18', 'TXN724327', NULL, 3, '2025-05-18 08:53:16'),
(67, 67, 1, 79.67, 'Insurance', '2025-04-16', 'TXN010185', 'Insurance Claim #78416', 3, '2025-05-18 08:53:16'),
(68, 68, 1, 149.56, 'Cash', '2024-12-02', 'TXN819467', NULL, 3, '2025-05-18 08:53:16'),
(69, 69, 1, 177.07, 'Bank Transfer', '2024-11-30', 'TXN560346', NULL, 3, '2025-05-18 08:53:16'),
(70, 70, 1, 54.21, 'Debit Card', '2025-03-03', 'TXN822108', NULL, 3, '2025-05-18 08:53:16'),
(71, 71, 1, 72.04, 'Insurance', '2025-02-26', 'TXN595217', 'Insurance Claim #11424', 3, '2025-05-18 08:53:16'),
(72, 72, 1, 240.58, 'Debit Card', '2025-02-28', 'TXN088612', NULL, 3, '2025-05-18 08:53:16'),
(73, 73, 1, 274.68, 'Debit Card', '2025-01-02', 'TXN140273', NULL, 3, '2025-05-18 08:53:16'),
(74, 74, 1, 150.43, 'Insurance', '2024-12-28', 'TXN936193', 'Insurance Claim #68990', 3, '2025-05-18 08:53:16'),
(75, 75, 1, 50.27, 'Cash', '2025-05-21', 'TXN356728', NULL, 3, '2025-05-18 08:53:16'),
(76, 76, 1, 79.25, 'Debit Card', '2025-05-16', 'TXN475555', NULL, 3, '2025-05-18 08:53:16'),
(77, 77, 1, 153.42, 'Insurance', '2025-03-02', 'TXN220360', 'Insurance Claim #61692', 3, '2025-05-18 08:53:16'),
(78, 78, 1, 233.77, 'Credit Card', '2025-02-24', 'TXN517767', 'Auth Code: 22EE66', 3, '2025-05-18 08:53:16'),
(79, 79, 1, 185.32, 'Insurance', '2025-02-28', 'TXN159797', 'Insurance Claim #92769', 3, '2025-05-18 08:53:16'),
(80, 80, 1, 190.35, 'Cash', '2025-04-04', 'TXN884513', NULL, 3, '2025-05-18 08:53:16'),
(81, 81, 1, 229.92, 'Bank Transfer', '2025-01-30', 'TXN244780', NULL, 3, '2025-05-18 08:53:16'),
(82, 82, 1, 54.38, 'Credit Card', '2024-11-29', 'TXN314186', 'Auth Code: F2202D', 3, '2025-05-18 08:53:16'),
(83, 83, 1, 171.95, 'Insurance', '2025-05-05', 'TXN195145', 'Insurance Claim #15918', 3, '2025-05-18 08:53:16'),
(84, 84, 1, 72.50, 'Debit Card', '2025-04-29', 'TXN492025', NULL, 3, '2025-05-18 08:53:16'),
(85, 85, 1, 116.65, 'Cash', '2025-02-09', 'TXN870326', NULL, 3, '2025-05-18 08:53:16'),
(86, 86, 1, 108.68, 'Insurance', '2025-02-11', 'TXN667864', 'Insurance Claim #58589', 3, '2025-05-18 08:53:16'),
(87, 87, 1, 81.35, 'Insurance', '2025-02-08', 'TXN290285', 'Insurance Claim #70809', 3, '2025-05-18 08:53:16'),
(88, 88, 1, 48.19, 'Credit Card', '2025-05-02', 'TXN879513', 'Auth Code: ECEF2F', 3, '2025-05-18 08:53:16'),
(89, 89, 1, 173.47, 'Credit Card', '2025-05-04', 'TXN743045', 'Auth Code: 05B38E', 3, '2025-05-18 08:53:16'),
(90, 90, 1, 53.12, 'Bank Transfer', '2024-12-12', 'TXN270373', NULL, 3, '2025-05-18 08:53:16'),
(91, 91, 1, 150.33, 'Credit Card', '2024-12-15', 'TXN940824', 'Auth Code: ABEC9D', 3, '2025-05-18 08:53:16'),
(92, 92, 1, 108.60, 'Cash', '2025-05-04', 'TXN441922', NULL, 3, '2025-05-18 08:53:16'),
(93, 93, 1, 159.36, 'Bank Transfer', '2025-05-14', 'TXN057014', NULL, 3, '2025-05-18 08:53:16'),
(94, 94, 1, 109.67, 'Insurance', '2025-05-21', 'TXN760196', 'Insurance Claim #10844', 3, '2025-05-18 08:53:16'),
(95, 95, 1, 249.03, 'Insurance', '2025-04-03', 'TXN697234', 'Insurance Claim #9878', 3, '2025-05-18 08:53:16'),
(96, 96, 1, 80.48, 'Bank Transfer', '2025-04-04', 'TXN693057', NULL, 3, '2025-05-18 08:53:16'),
(97, 97, 1, 248.86, 'Bank Transfer', '2025-01-31', 'TXN716948', NULL, 3, '2025-05-18 08:53:16'),
(98, 98, 1, 46.34, 'Cash', '2025-01-31', 'TXN775715', NULL, 3, '2025-05-18 08:53:16'),
(99, 99, 1, 49.61, 'Bank Transfer', '2025-01-31', 'TXN997551', NULL, 3, '2025-05-18 08:53:16'),
(100, 100, 1, 113.46, 'Credit Card', '2025-01-25', 'TXN327141', 'Auth Code: CFD745', 3, '2025-05-18 08:53:16'),
(101, 101, 1, 54.55, 'Credit Card', '2025-04-26', 'TXN129202', 'Auth Code: 460898', 3, '2025-05-18 08:53:16'),
(102, 102, 1, 188.41, 'Credit Card', '2025-04-28', 'TXN078809', 'Auth Code: 1C410F', 3, '2025-05-18 08:53:16'),
(103, 103, 1, 49.86, 'Bank Transfer', '2024-12-22', 'TXN847252', NULL, 3, '2025-05-18 08:53:16'),
(104, 104, 1, 178.73, 'Bank Transfer', '2025-03-31', 'TXN541620', NULL, 3, '2025-05-18 08:53:16'),
(105, 105, 1, 273.71, 'Cash', '2024-12-27', 'TXN148351', NULL, 3, '2025-05-18 08:53:16'),
(106, 106, 1, 48.64, 'Insurance', '2024-12-20', 'TXN013240', 'Insurance Claim #70946', 3, '2025-05-18 08:53:16'),
(107, 107, 1, 77.35, 'Debit Card', '2024-12-24', 'TXN396622', NULL, 3, '2025-05-18 08:53:16'),
(108, 108, 1, 109.38, 'Insurance', '2025-04-17', 'TXN575119', 'Insurance Claim #80102', 3, '2025-05-18 08:53:16'),
(109, 109, 1, 115.75, 'Insurance', '2025-04-18', 'TXN710117', 'Insurance Claim #13215', 3, '2025-05-18 08:53:16'),
(110, 110, 1, 235.99, 'Credit Card', '2025-03-02', 'TXN669509', 'Auth Code: B698F2', 3, '2025-05-18 08:53:16'),
(111, 111, 1, 136.61, 'Credit Card', '2025-03-04', 'TXN968657', 'Auth Code: 7AE042', 3, '2025-05-18 08:53:16'),
(112, 112, 1, 48.24, 'Cash', '2024-12-09', 'TXN079136', NULL, 3, '2025-05-18 08:53:16'),
(113, 113, 1, 77.48, 'Insurance', '2024-12-12', 'TXN540372', 'Insurance Claim #34318', 3, '2025-05-18 08:53:16'),
(114, 114, 1, 159.62, 'Debit Card', '2024-12-16', 'TXN355134', NULL, 3, '2025-05-18 08:53:16'),
(115, 115, 1, 192.91, 'Bank Transfer', '2024-12-01', 'TXN013369', NULL, 3, '2025-05-18 08:53:16'),
(116, 116, 1, 179.55, 'Debit Card', '2024-11-25', 'TXN819778', NULL, 3, '2025-05-18 08:53:16'),
(117, 117, 1, 138.22, 'Insurance', '2024-12-29', 'TXN101131', 'Insurance Claim #28379', 3, '2025-05-18 08:53:16'),
(118, 118, 1, 180.25, 'Bank Transfer', '2024-12-26', 'TXN250541', NULL, 3, '2025-05-18 08:53:16'),
(119, 119, 1, 272.58, 'Credit Card', '2024-12-31', 'TXN948136', 'Auth Code: 3A098F', 3, '2025-05-18 08:53:16'),
(120, 120, 1, 123.26, 'Cash', '2025-04-29', 'TXN992232', NULL, 3, '2025-05-18 08:53:16'),
(121, 121, 1, 53.37, 'Cash', '2025-04-27', 'TXN472418', NULL, 3, '2025-05-18 08:53:16'),
(122, 122, 1, 126.18, 'Credit Card', '2025-04-22', 'TXN668665', 'Auth Code: AAF4FB', 3, '2025-05-18 08:53:16'),
(123, 123, 1, 158.22, 'Debit Card', '2025-05-02', 'TXN759208', NULL, 3, '2025-05-18 08:53:16'),
(124, 124, 1, 54.38, 'Debit Card', '2025-05-05', 'TXN927836', NULL, 3, '2025-05-18 08:53:16'),
(125, 125, 1, 48.83, 'Insurance', '2025-05-07', 'TXN752173', 'Insurance Claim #59456', 3, '2025-05-18 08:53:16'),
(126, 126, 1, 52.80, 'Bank Transfer', '2025-02-02', 'TXN809080', NULL, 3, '2025-05-18 08:53:16'),
(127, 127, 1, 156.72, 'Cash', '2025-01-30', 'TXN354747', NULL, 3, '2025-05-18 08:53:16'),
(128, 128, 1, 46.20, 'Cash', '2025-01-28', 'TXN142903', NULL, 3, '2025-05-18 08:53:16'),
(129, 129, 1, 176.05, 'Debit Card', '2025-02-16', 'TXN281422', NULL, 3, '2025-05-18 08:53:16'),
(130, 130, 1, 250.89, 'Credit Card', '2024-11-24', 'TXN952735', 'Auth Code: 206B3E', 3, '2025-05-18 08:53:16'),
(131, 131, 1, 73.15, 'Credit Card', '2024-11-27', 'TXN315401', 'Auth Code: A0325B', 3, '2025-05-18 08:53:16'),
(132, 132, 1, 52.48, 'Debit Card', '2024-12-02', 'TXN702497', NULL, 3, '2025-05-18 08:53:16'),
(133, 133, 1, 81.70, 'Debit Card', '2025-02-11', 'TXN516747', NULL, 3, '2025-05-18 08:53:16'),
(134, 134, 1, 53.74, 'Cash', '2025-02-12', 'TXN342168', NULL, 3, '2025-05-18 08:53:16'),
(135, 135, 1, 110.57, 'Cash', '2025-02-17', 'TXN507400', NULL, 3, '2025-05-18 08:53:16'),
(136, 136, 1, 119.09, 'Insurance', '2024-12-06', 'TXN322653', 'Insurance Claim #81963', 3, '2025-05-18 08:53:16'),
(137, 137, 1, 72.58, 'Cash', '2024-12-06', 'TXN276354', NULL, 3, '2025-05-18 08:53:16'),
(138, 138, 1, 124.93, 'Debit Card', '2024-12-06', 'TXN398949', NULL, 3, '2025-05-18 08:53:16'),
(139, 139, 1, 126.36, 'Insurance', '2025-01-27', 'TXN454816', 'Insurance Claim #78637', 3, '2025-05-18 08:53:16'),
(140, 140, 1, 193.12, 'Debit Card', '2025-01-26', 'TXN002738', NULL, 3, '2025-05-18 08:53:16'),
(141, 141, 1, 112.33, 'Bank Transfer', '2025-05-13', 'TXN243429', NULL, 3, '2025-05-18 08:53:16'),
(142, 142, 1, 77.44, 'Insurance', '2025-02-27', 'TXN274548', 'Insurance Claim #4813', 3, '2025-05-18 08:53:16'),
(143, 143, 1, 154.68, 'Insurance', '2025-02-24', 'TXN439919', 'Insurance Claim #84240', 3, '2025-05-18 08:53:16'),
(144, 144, 1, 118.49, 'Insurance', '2025-02-28', 'TXN165355', 'Insurance Claim #84627', 3, '2025-05-18 08:53:16'),
(145, 145, 1, 111.13, 'Cash', '2025-03-20', 'TXN998882', NULL, 3, '2025-05-18 08:53:16'),
(146, 146, 1, 266.71, 'Bank Transfer', '2025-03-17', 'TXN068546', NULL, 3, '2025-05-18 08:53:16'),
(147, 147, 1, 53.50, 'Bank Transfer', '2025-01-01', 'TXN601922', NULL, 3, '2025-05-18 08:53:16'),
(148, 148, 1, 45.90, 'Bank Transfer', '2024-12-31', 'TXN907114', NULL, 3, '2025-05-18 08:53:16'),
(149, 149, 1, 163.16, 'Credit Card', '2025-04-20', 'TXN384587', 'Auth Code: 569FC8', 3, '2025-05-18 08:53:16'),
(150, 150, 1, 152.98, 'Debit Card', '2025-04-19', 'TXN607164', NULL, 3, '2025-05-18 08:53:16'),
(151, 151, 1, 250.09, 'Bank Transfer', '2024-12-18', 'TXN716046', NULL, 3, '2025-05-18 08:53:16'),
(152, 152, 1, 167.59, 'Debit Card', '2024-12-17', 'TXN423684', NULL, 3, '2025-05-18 08:53:16'),
(153, 153, 1, 123.90, 'Credit Card', '2025-01-15', 'TXN323930', 'Auth Code: BEEC15', 3, '2025-05-18 08:53:16'),
(154, 154, 1, 53.17, 'Cash', '2025-01-15', 'TXN628861', NULL, 3, '2025-05-18 08:53:16'),
(155, 155, 1, 179.94, 'Insurance', '2025-03-14', 'TXN144648', 'Insurance Claim #85498', 3, '2025-05-18 08:53:16'),
(156, 156, 1, 169.37, 'Bank Transfer', '2025-03-15', 'TXN464115', NULL, 3, '2025-05-18 08:53:16'),
(157, 157, 1, 76.05, 'Cash', '2025-03-13', 'TXN937928', NULL, 3, '2025-05-18 08:53:16'),
(158, 158, 1, 116.24, 'Credit Card', '2025-05-10', 'TXN116161', 'Auth Code: BDB52F', 3, '2025-05-18 08:53:16'),
(159, 159, 1, 174.87, 'Insurance', '2025-05-15', 'TXN598811', 'Insurance Claim #38063', 3, '2025-05-18 08:53:16'),
(160, 160, 1, 47.39, 'Credit Card', '2025-03-01', 'TXN019551', 'Auth Code: CC0F73', 3, '2025-05-18 08:53:16'),
(161, 161, 1, 150.94, 'Bank Transfer', '2025-03-02', 'TXN326652', NULL, 3, '2025-05-18 08:53:16'),
(162, 162, 1, 149.17, 'Cash', '2025-02-26', 'TXN406761', NULL, 3, '2025-05-18 08:53:16'),
(163, 163, 1, 165.68, 'Cash', '2025-03-12', 'TXN060379', NULL, 3, '2025-05-18 08:53:16'),
(164, 164, 1, 120.36, 'Credit Card', '2025-03-11', 'TXN762364', 'Auth Code: 4FA13E', 3, '2025-05-18 08:53:16'),
(165, 165, 1, 184.54, 'Debit Card', '2025-03-14', 'TXN745639', NULL, 3, '2025-05-18 08:53:16'),
(166, 166, 1, 169.76, 'Credit Card', '2024-11-27', 'TXN835989', 'Auth Code: D4D060', 3, '2025-05-18 08:53:16'),
(167, 167, 1, 151.42, 'Insurance', '2024-11-30', 'TXN173730', 'Insurance Claim #82547', 3, '2025-05-18 08:53:16'),
(168, 168, 1, 52.61, 'Debit Card', '2024-12-05', 'TXN106678', NULL, 3, '2025-05-18 08:53:16'),
(169, 169, 1, 273.03, 'Cash', '2024-11-29', 'TXN727050', NULL, 3, '2025-05-18 08:53:16'),
(170, 170, 1, 111.39, 'Debit Card', '2025-04-10', 'TXN737391', NULL, 3, '2025-05-18 08:53:16'),
(171, 171, 1, 51.45, 'Bank Transfer', '2024-12-07', 'TXN034192', NULL, 3, '2025-05-18 08:53:16'),
(172, 172, 1, 164.16, 'Cash', '2024-12-07', 'TXN999242', NULL, 3, '2025-05-18 08:53:16'),
(173, 173, 1, 73.15, 'Insurance', '2025-02-04', 'TXN970593', 'Insurance Claim #66351', 3, '2025-05-18 08:53:16'),
(174, 174, 1, 235.36, 'Cash', '2025-02-06', 'TXN758870', NULL, 3, '2025-05-18 08:53:16'),
(175, 175, 1, 245.51, 'Cash', '2025-05-01', 'TXN547820', NULL, 3, '2025-05-18 08:53:16'),
(176, 176, 1, 108.27, 'Bank Transfer', '2025-04-27', 'TXN433848', NULL, 3, '2025-05-18 08:53:16'),
(177, 177, 1, 129.29, 'Debit Card', '2025-04-16', 'TXN436623', NULL, 3, '2025-05-18 08:53:16'),
(178, 178, 1, 259.26, 'Credit Card', '2025-04-17', 'TXN620204', 'Auth Code: 165101', 3, '2025-05-18 08:53:16'),
(179, 179, 1, 179.26, 'Debit Card', '2025-04-12', 'TXN373600', NULL, 3, '2025-05-18 08:53:16'),
(180, 180, 1, 237.62, 'Bank Transfer', '2025-03-01', 'TXN165459', NULL, 3, '2025-05-18 08:53:16'),
(181, 181, 1, 233.81, 'Credit Card', '2025-02-28', 'TXN490791', 'Auth Code: AB173A', 3, '2025-05-18 08:53:16'),
(182, 182, 1, 272.65, 'Bank Transfer', '2025-02-07', 'TXN393905', NULL, 3, '2025-05-18 08:53:16'),
(183, 183, 1, 116.24, 'Debit Card', '2025-02-03', 'TXN405848', NULL, 3, '2025-05-18 08:53:16'),
(184, 184, 1, 72.16, 'Debit Card', '2025-02-02', 'TXN299803', NULL, 3, '2025-05-18 08:53:16'),
(185, 185, 1, 196.62, 'Insurance', '2025-04-20', 'TXN958827', 'Insurance Claim #45132', 3, '2025-05-18 08:53:16'),
(186, 186, 1, 184.52, 'Debit Card', '2024-12-20', 'TXN327691', NULL, 3, '2025-05-18 08:53:16'),
(187, 187, 1, 124.02, 'Credit Card', '2024-12-23', 'TXN840696', 'Auth Code: 7A4407', 3, '2025-05-18 08:53:16'),
(188, 188, 1, 261.84, 'Cash', '2025-04-28', 'TXN774227', NULL, 3, '2025-05-18 08:53:16'),
(189, 189, 1, 117.98, 'Bank Transfer', '2025-04-27', 'TXN165979', NULL, 3, '2025-05-18 08:53:16'),
(190, 190, 1, 239.13, 'Insurance', '2025-04-28', 'TXN218636', 'Insurance Claim #58924', 3, '2025-05-18 08:53:16'),
(191, 191, 1, 45.09, 'Bank Transfer', '2025-03-30', 'TXN688723', NULL, 3, '2025-05-18 08:53:16'),
(192, 192, 1, 51.17, 'Insurance', '2025-03-28', 'TXN503460', 'Insurance Claim #73819', 3, '2025-05-18 08:53:16'),
(193, 193, 1, 235.28, 'Bank Transfer', '2025-04-02', 'TXN436320', NULL, 3, '2025-05-18 08:53:16'),
(194, 194, 1, 122.88, 'Cash', '2024-12-02', 'TXN864084', NULL, 3, '2025-05-18 08:53:16'),
(195, 195, 1, 79.47, 'Debit Card', '2025-03-18', 'TXN039034', NULL, 3, '2025-05-18 08:53:16'),
(196, 196, 1, 240.62, 'Bank Transfer', '2025-03-23', 'TXN397860', NULL, 3, '2025-05-18 08:53:16'),
(197, 197, 1, 234.90, 'Insurance', '2025-05-03', 'TXN304402', 'Insurance Claim #55025', 3, '2025-05-18 08:53:16'),
(198, 198, 1, 155.47, 'Debit Card', '2025-04-29', 'TXN298181', NULL, 3, '2025-05-18 08:53:16'),
(199, 199, 1, 228.42, 'Insurance', '2025-04-29', 'TXN706026', 'Insurance Claim #84854', 3, '2025-05-18 08:53:16'),
(200, 200, 1, 110.18, 'Cash', '2024-12-16', 'TXN838591', NULL, 3, '2025-05-18 08:53:16'),
(201, 201, 1, 49.27, 'Credit Card', '2024-12-21', 'TXN904450', 'Auth Code: 8997BE', 3, '2025-05-18 08:53:16'),
(202, 202, 1, 178.83, 'Credit Card', '2024-12-18', 'TXN586310', 'Auth Code: 1C87AA', 3, '2025-05-18 08:53:16'),
(203, 203, 1, 48.80, 'Insurance', '2025-01-29', 'TXN026522', 'Insurance Claim #77312', 3, '2025-05-18 08:53:16'),
(204, 204, 1, 187.96, 'Bank Transfer', '2025-02-03', 'TXN647269', NULL, 3, '2025-05-18 08:53:16'),
(205, 205, 1, 260.83, 'Insurance', '2025-02-02', 'TXN390769', 'Insurance Claim #27785', 3, '2025-05-18 08:53:16'),
(206, 206, 1, 236.57, 'Credit Card', '2025-01-27', 'TXN272234', 'Auth Code: 6B80B7', 3, '2025-05-18 08:53:16'),
(207, 207, 1, 151.15, 'Insurance', '2025-01-12', 'TXN589134', 'Insurance Claim #33428', 3, '2025-05-18 08:53:16'),
(208, 208, 1, 117.11, 'Debit Card', '2025-02-25', 'TXN819898', NULL, 3, '2025-05-18 08:53:16'),
(209, 209, 1, 45.17, 'Insurance', '2025-02-20', 'TXN148027', 'Insurance Claim #19085', 3, '2025-05-18 08:53:16'),
(210, 210, 1, 157.31, 'Insurance', '2025-02-21', 'TXN872529', 'Insurance Claim #27795', 3, '2025-05-18 08:53:16'),
(211, 211, 1, 48.33, 'Cash', '2025-03-02', 'TXN010775', NULL, 3, '2025-05-18 08:53:16'),
(212, 212, 1, 189.57, 'Insurance', '2025-02-24', 'TXN182031', 'Insurance Claim #86924', 3, '2025-05-18 08:53:16'),
(213, 213, 1, 112.98, 'Credit Card', '2025-02-28', 'TXN641638', 'Auth Code: 9EAE0F', 3, '2025-05-18 08:53:16'),
(214, 214, 1, 84.70, 'Bank Transfer', '2025-01-04', 'TXN182841', NULL, 3, '2025-05-18 08:53:16'),
(215, 215, 1, 127.01, 'Insurance', '2025-01-06', 'TXN917526', 'Insurance Claim #77324', 3, '2025-05-18 08:53:16'),
(216, 216, 1, 49.38, 'Credit Card', '2024-12-25', 'TXN761167', 'Auth Code: 28D3D6', 3, '2025-05-18 08:53:16'),
(217, 217, 1, 79.48, 'Insurance', '2025-05-02', 'TXN762698', 'Insurance Claim #47038', 3, '2025-05-18 08:53:16'),
(218, 218, 1, 72.39, 'Insurance', '2025-05-01', 'TXN017375', 'Insurance Claim #4216', 3, '2025-05-18 08:53:16'),
(219, 219, 1, 227.22, 'Bank Transfer', '2025-02-23', 'TXN293117', NULL, 3, '2025-05-18 08:53:16'),
(220, 220, 1, 85.09, 'Debit Card', '2024-11-26', 'TXN124076', NULL, 3, '2025-05-18 08:53:16'),
(221, 221, 1, 49.79, 'Credit Card', '2025-05-06', 'TXN815605', 'Auth Code: 2EAB9E', 3, '2025-05-18 08:53:16'),
(222, 222, 1, 144.27, 'Insurance', '2025-05-04', 'TXN002323', 'Insurance Claim #90439', 3, '2025-05-18 08:53:16'),
(223, 223, 1, 113.69, 'Insurance', '2025-05-05', 'TXN234497', 'Insurance Claim #88056', 3, '2025-05-18 08:53:16'),
(224, 224, 1, 167.38, 'Insurance', '2025-02-01', 'TXN319709', 'Insurance Claim #6773', 3, '2025-05-18 08:53:16'),
(225, 225, 1, 137.07, 'Bank Transfer', '2025-02-02', 'TXN588076', NULL, 3, '2025-05-18 08:53:16'),
(226, 226, 1, 152.05, 'Insurance', '2025-03-13', 'TXN223570', 'Insurance Claim #72427', 3, '2025-05-18 08:53:16'),
(227, 227, 1, 50.06, 'Debit Card', '2025-01-16', 'TXN511348', NULL, 3, '2025-05-18 08:53:16'),
(228, 228, 1, 236.72, 'Debit Card', '2025-01-17', 'TXN726223', NULL, 3, '2025-05-18 08:53:16'),
(229, 229, 1, 231.27, 'Credit Card', '2025-01-08', 'TXN675557', 'Auth Code: AACC5B', 3, '2025-05-18 08:53:16'),
(230, 230, 1, 189.00, 'Debit Card', '2025-02-10', 'TXN170570', NULL, 3, '2025-05-18 08:53:16'),
(231, 231, 1, 167.34, 'Credit Card', '2025-02-05', 'TXN476345', 'Auth Code: 8E9E3C', 3, '2025-05-18 08:53:16'),
(232, 232, 1, 121.76, 'Credit Card', '2024-12-21', 'TXN797077', 'Auth Code: FC1C67', 3, '2025-05-18 08:53:16'),
(233, 233, 1, 140.42, 'Cash', '2025-01-25', 'TXN937017', NULL, 3, '2025-05-18 08:53:16'),
(234, 234, 1, 142.27, 'Cash', '2025-01-26', 'TXN195456', NULL, 3, '2025-05-18 08:53:16'),
(235, 235, 1, 170.28, 'Debit Card', '2025-01-29', 'TXN143208', NULL, 3, '2025-05-18 08:53:16'),
(236, 236, 1, 179.67, 'Debit Card', '2025-01-31', 'TXN288680', NULL, 3, '2025-05-18 08:53:16'),
(237, 237, 1, 108.33, 'Insurance', '2025-02-04', 'TXN417464', 'Insurance Claim #13010', 3, '2025-05-18 08:53:16'),
(238, 238, 1, 160.59, 'Bank Transfer', '2025-05-15', 'TXN235728', NULL, 3, '2025-05-18 08:53:16'),
(239, 239, 1, 269.53, 'Cash', '2025-05-09', 'TXN316376', NULL, 3, '2025-05-18 08:53:16'),
(240, 240, 1, 172.65, 'Credit Card', '2025-05-16', 'TXN423158', 'Auth Code: 8531A6', 3, '2025-05-18 08:53:16'),
(241, 241, 1, 161.21, 'Insurance', '2025-05-09', 'TXN749158', 'Insurance Claim #19484', 3, '2025-05-18 08:53:16'),
(242, 242, 1, 171.02, 'Cash', '2025-05-05', 'TXN181543', NULL, 3, '2025-05-18 08:53:16'),
(243, 243, 1, 228.84, 'Cash', '2025-05-12', 'TXN486923', NULL, 3, '2025-05-18 08:53:16'),
(244, 244, 1, 47.90, 'Cash', '2024-12-17', 'TXN309776', NULL, 3, '2025-05-18 08:53:16'),
(245, 245, 1, 130.34, 'Cash', '2024-12-11', 'TXN812391', NULL, 3, '2025-05-18 08:53:16'),
(246, 246, 1, 117.61, 'Credit Card', '2025-05-13', 'TXN558358', 'Auth Code: C66BDC', 3, '2025-05-18 08:53:16'),
(247, 247, 1, 194.04, 'Bank Transfer', '2025-05-17', 'TXN741606', NULL, 3, '2025-05-18 08:53:16'),
(248, 248, 1, 86.44, 'Bank Transfer', '2025-01-13', 'TXN068399', NULL, 3, '2025-05-18 08:53:16'),
(249, 249, 1, 111.86, 'Bank Transfer', '2024-11-29', 'TXN390906', NULL, 3, '2025-05-18 08:53:16'),
(250, 250, 1, 51.52, 'Bank Transfer', '2024-11-30', 'TXN540720', NULL, 3, '2025-05-18 08:53:16'),
(251, 251, 1, 124.42, 'Cash', '2025-04-16', 'TXN803715', NULL, 3, '2025-05-18 08:53:16'),
(252, 252, 1, 108.64, 'Cash', '2025-04-16', 'TXN876072', NULL, 3, '2025-05-18 08:53:16'),
(253, 253, 1, 54.35, 'Debit Card', '2025-04-18', 'TXN391802', NULL, 3, '2025-05-18 08:53:16'),
(254, 254, 1, 52.99, 'Cash', '2025-04-04', 'TXN426356', NULL, 3, '2025-05-18 08:53:16'),
(255, 255, 1, 158.10, 'Cash', '2025-02-16', 'TXN504173', NULL, 3, '2025-05-18 08:53:16'),
(256, 256, 1, 149.97, 'Insurance', '2025-02-13', 'TXN396747', 'Insurance Claim #75191', 3, '2025-05-18 08:53:16'),
(257, 257, 1, 144.37, 'Debit Card', '2025-05-13', 'TXN459177', NULL, 3, '2025-05-18 08:53:16'),
(258, 258, 1, 113.66, 'Debit Card', '2025-05-14', 'TXN593047', NULL, 3, '2025-05-18 08:53:16'),
(259, 259, 1, 46.22, 'Bank Transfer', '2025-05-15', 'TXN221782', NULL, 3, '2025-05-18 08:53:16'),
(261, 265, 1, 120.00, 'Cash', '2025-05-24', '', '', 3, '2025-05-24 12:51:41'),
(262, 265, 1, 120.00, 'Cash', '2025-05-24', '', '', 3, '2025-05-24 12:57:57'),
(263, 266, 1, 180.00, 'Debit Card', '2025-05-24', '', '', 3, '2025-05-24 13:14:50'),
(264, 264, 1, 120.00, 'Cash', '2025-05-24', '', '', 3, '2025-05-24 13:38:45'),
(265, 263, 1, 50.00, 'Cash', '2025-05-24', 'tttt', '', 3, '2025-05-24 13:49:13'),
(266, 262, 1, 30.00, 'Credit Card', '2025-05-24', '', '', 3, '2025-05-24 13:58:00'),
(267, 262, 1, 10.00, 'Cash', '2025-05-24', '', '', 3, '2025-05-24 14:10:14'),
(268, 262, 1, 10.00, 'Cash', '2025-05-24', '', '', 3, '2025-05-24 14:26:41');

-- --------------------------------------------------------

--
-- Table structure for table `periodontal_charting`
--

CREATE TABLE `periodontal_charting` (
  `chart_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `tooth_id` tinyint(4) NOT NULL,
  `pocket_depths` varchar(50) DEFAULT NULL,
  `mobility` tinyint(4) DEFAULT 0,
  `bleeding` tinyint(1) DEFAULT 0,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms_log`
--

CREATE TABLE `sms_log` (
  `log_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `provider` varchar(50) DEFAULT 'SMSlenz.lk',
  `message_id` varchar(100) DEFAULT NULL,
  `credits_used` decimal(10,2) DEFAULT NULL,
  `credits_remaining` decimal(10,2) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_log`
--

INSERT INTO `sms_log` (`log_id`, `appointment_id`, `patient_id`, `phone_number`, `message`, `status`, `provider`, `message_id`, `credits_used`, `credits_remaining`, `error_message`, `error_code`, `sent_at`, `updated_at`) VALUES
(1, 217, 1, '+94702776622', '🦷 Hi John Doe!\n\nYour dental appointment has been scheduled:\n📅 Date: May 22, 2025\n🕐 Time: 5:30 PM\n👨‍⚕️ Doctor: Dr. John Smith\n\nDenTec Clinic\nReply STOP to opt out.', 'failed', 'SMSlenz.lk', NULL, NULL, NULL, 'Invalid Sri Lankan phone number format', 'INVALID_PHONE', '2025-05-22 18:47:54', '2025-05-22 18:47:54'),
(2, 218, 1, '0702776622', '🦷 Hi John Doe!\n\nYour dental appointment has been scheduled:\n📅 Date: May 22, 2025\n🕐 Time: 7:00 PM\n👨‍⚕️ Doctor: Dr. John Smith\n\nDenTec Clinic\nReply STOP to opt out.', 'failed', 'SMSlenz.lk', NULL, NULL, NULL, 'HTTP Error: 404', 'HTTP_ERROR', '2025-05-22 18:50:08', '2025-05-22 18:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `sms_settings`
--

CREATE TABLE `sms_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_settings`
--

INSERT INTO `sms_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `is_encrypted`, `created_at`, `updated_at`) VALUES
(1, 'smslenz_enabled', '1', 'Enable/disable SMSlenz.lk integration', 0, '2025-05-22 18:24:51', '2025-05-22 18:24:51'),
(2, 'sms_debug_mode', '1', 'Enable debug logging for SMS', 0, '2025-05-22 18:24:52', '2025-05-22 18:24:52'),
(3, 'default_sender_id', 'DENTEC', 'Default sender ID for SMS', 0, '2025-05-22 18:24:52', '2025-05-22 18:24:52'),
(4, 'sms_retry_attempts', '3', 'Number of retry attempts for failed SMS', 0, '2025-05-22 18:24:52', '2025-05-22 18:24:52'),
(5, 'sms_timeout_seconds', '30', 'Timeout for SMS API calls', 0, '2025-05-22 18:24:52', '2025-05-22 18:24:52'),
(6, 'low_credit_threshold', '50', 'Alert when credits fall below this number', 0, '2025-05-22 18:24:52', '2025-05-22 18:24:52'),
(7, 'appointment_sms_template', '🦷 Hi {patient_name}! Your dental appointment is scheduled for {date} at {time} with Dr. {doctor_name}. DenTec Clinic', 'Template for appointment confirmation SMS', 0, '2025-05-22 18:24:52', '2025-05-22 18:24:52'),
(8, 'reminder_sms_template', '🔔 Reminder: Hi {patient_name}! Your dental appointment is tomorrow at {time} with Dr. {doctor_name}. DenTec Clinic', 'Template for appointment reminder SMS', 0, '2025-05-22 18:24:52', '2025-05-22 18:24:52');

-- --------------------------------------------------------

--
-- Table structure for table `treatments`
--

CREATE TABLE `treatments` (
  `treatment_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` smallint(6) NOT NULL COMMENT 'In minutes',
  `cost` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatments`
--

INSERT INTO `treatments` (`treatment_id`, `code`, `name`, `category`, `description`, `duration`, `cost`, `is_active`) VALUES
(1, 'D0120', 'Periodic Oral Evaluation', 'Diagnostic', 'Routine dental examination', 15, 50.00, 1),
(2, 'D1110', 'Prophylaxis - Adult', 'Preventive', 'Teeth cleaning for patients over 14', 45, 80.00, 1),
(3, 'D2140', 'Amalgam - 1 Surface', 'Restorative', 'Silver filling, one surface', 30, 120.00, 1),
(4, 'D2330', 'Resin - 2 Surfaces', 'Restorative', 'White filling, two surfaces', 45, 150.00, 1),
(5, 'D3220', 'Pulpotomy', 'Endodontics', 'Partial root canal treatment', 60, 250.00, 1),
(6, 'D7140', 'Extraction', 'Surgical', 'Simple tooth extraction', 30, 180.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `treatment_records`
--

CREATE TABLE `treatment_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `treatment_id` int(11) NOT NULL,
  `tooth_id` tinyint(4) DEFAULT NULL,
  `diagnosis` text NOT NULL,
  `treatment_notes` text DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `status` enum('Planned','Completed','Cancelled') DEFAULT 'Planned',
  `treatment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_records`
--

INSERT INTO `treatment_records` (`record_id`, `patient_id`, `dentist_id`, `appointment_id`, `treatment_id`, `tooth_id`, `diagnosis`, `treatment_notes`, `cost`, `status`, `treatment_date`, `created_at`) VALUES
(1, 1, 2, 1, 2, 30, 'Plaque buildup', 'Professional cleaning performed', 80.00, 'Completed', '2023-06-20', '2025-05-17 18:13:53'),
(2, 1, 2, 2, 3, 4, 'Gingivitis', 'Treatment performed successfully', 124.69, 'Completed', '2025-01-13', '2025-05-18 08:53:15'),
(3, 1, 2, 2, 2, 3, 'Pulpitis', 'Treatment performed successfully', 73.10, 'Completed', '2025-01-13', '2025-05-18 08:53:15'),
(4, 1, 2, 2, 1, NULL, 'Periodontal disease', 'Treatment performed successfully', 52.09, 'Completed', '2025-01-13', '2025-05-18 08:53:15'),
(5, 1, 2, 3, 3, 20, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 127.50, 'Completed', '2024-12-02', '2025-05-18 08:53:15'),
(6, 1, 2, 4, 6, 9, 'Fractured tooth', 'Patient tolerated procedure well', 184.23, 'Completed', '2025-01-02', '2025-05-18 08:53:15'),
(7, 1, 2, 5, 3, NULL, 'Routine preventive care', 'Local anesthetic administered', 113.55, 'Completed', '2024-12-05', '2025-05-18 08:53:15'),
(8, 1, 2, 6, 4, 19, 'Dental caries', 'Patient tolerated procedure well', 159.37, 'Completed', '2025-03-21', '2025-05-18 08:53:15'),
(9, 1, 2, 6, 2, NULL, 'Dental caries', 'Additional treatment may be needed', 86.90, 'Completed', '2025-03-21', '2025-05-18 08:53:15'),
(10, 1, 2, 6, 2, 20, 'Routine preventive care', 'Local anesthetic administered', 85.80, 'Completed', '2025-03-21', '2025-05-18 08:53:15'),
(11, 1, 2, 8, 6, 24, 'Gingivitis', 'Treatment performed successfully', 185.83, 'Completed', '2024-12-05', '2025-05-18 08:53:15'),
(12, 1, 2, 8, 3, 17, 'Periodontal disease', 'Local anesthetic administered', 113.32, 'Completed', '2024-12-05', '2025-05-18 08:53:15'),
(13, 1, 2, 8, 4, 31, 'Pulpitis', 'Additional treatment may be needed', 145.60, 'Completed', '2024-12-05', '2025-05-18 08:53:15'),
(14, 1, 2, 9, 2, 31, 'Fractured tooth', 'Follow-up recommended in 6 months', 83.47, 'Completed', '2025-04-07', '2025-05-18 08:53:15'),
(15, 1, 2, 11, 2, 15, 'Periodontal disease', 'Additional treatment may be needed', 85.83, 'Completed', '2025-04-18', '2025-05-18 08:53:15'),
(16, 1, 2, 14, 5, 1, 'Routine preventive care', 'Patient tolerated procedure well', 239.35, 'Completed', '2025-02-26', '2025-05-18 08:53:15'),
(17, 1, 2, 18, 5, 7, 'Periodontal disease', 'Follow-up recommended in 6 months', 257.05, 'Completed', '2024-12-17', '2025-05-18 08:53:15'),
(18, 1, 2, 19, 4, 18, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 146.97, 'Completed', '2025-03-28', '2025-05-18 08:53:15'),
(19, 1, 2, 20, 6, NULL, 'Routine preventive care', 'Local anesthetic administered', 168.66, 'Completed', '2025-03-14', '2025-05-18 08:53:15'),
(20, 1, 2, 20, 2, 12, 'Periodontal disease', 'Patient tolerated procedure well', 79.58, 'Completed', '2025-03-14', '2025-05-18 08:53:15'),
(21, 1, 2, 21, 3, 2, 'Routine preventive care', 'Local anesthetic administered', 111.15, 'Completed', '2025-02-03', '2025-05-18 08:53:15'),
(22, 1, 2, 21, 3, 12, 'Fractured tooth', 'Local anesthetic administered', 120.18, 'Completed', '2025-02-03', '2025-05-18 08:53:15'),
(23, 1, 2, 21, 5, 8, 'Fractured tooth', 'Patient tolerated procedure well', 263.95, 'Completed', '2025-02-03', '2025-05-18 08:53:15'),
(24, 1, 2, 22, 2, NULL, 'Fractured tooth', 'Follow-up recommended in 6 months', 86.88, 'Completed', '2024-12-12', '2025-05-18 08:53:15'),
(25, 1, 2, 22, 3, 18, 'Pulpitis', 'Treatment performed successfully', 111.91, 'Completed', '2024-12-12', '2025-05-18 08:53:15'),
(26, 1, 2, 22, 3, 25, 'Gingivitis', 'Local anesthetic administered', 126.54, 'Completed', '2024-12-12', '2025-05-18 08:53:15'),
(27, 1, 2, 23, 1, 31, 'Impacted wisdom tooth', 'Treatment performed successfully', 52.66, 'Completed', '2024-12-23', '2025-05-18 08:53:15'),
(28, 1, 2, 23, 6, 29, 'Periodontal disease', 'Additional treatment may be needed', 166.32, 'Completed', '2024-12-23', '2025-05-18 08:53:15'),
(29, 1, 2, 23, 3, 26, 'Impacted wisdom tooth', 'Treatment performed successfully', 119.87, 'Completed', '2024-12-23', '2025-05-18 08:53:15'),
(30, 1, 2, 26, 3, NULL, 'Pulpitis', 'Local anesthetic administered', 121.60, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(31, 1, 2, 26, 6, NULL, 'Pulpitis', 'Local anesthetic administered', 163.61, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(32, 1, 2, 27, 1, 1, 'Fractured tooth', 'Local anesthetic administered', 54.10, 'Completed', '2025-02-17', '2025-05-18 08:53:15'),
(33, 1, 2, 28, 4, NULL, 'Dental caries', 'Local anesthetic administered', 160.96, 'Completed', '2024-11-26', '2025-05-18 08:53:15'),
(34, 1, 2, 28, 4, 4, 'Dental caries', 'Follow-up recommended in 6 months', 164.74, 'Completed', '2024-11-26', '2025-05-18 08:53:15'),
(35, 1, 2, 28, 3, 2, 'Fractured tooth', 'Follow-up recommended in 6 months', 116.96, 'Completed', '2024-11-26', '2025-05-18 08:53:15'),
(36, 1, 2, 29, 6, 25, 'Dental caries', 'Follow-up recommended in 6 months', 183.90, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(37, 1, 2, 29, 5, 26, 'Routine preventive care', 'Local anesthetic administered', 262.94, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(38, 1, 2, 29, 3, 31, 'Gingivitis', 'Follow-up recommended in 6 months', 125.06, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(39, 1, 2, 32, 5, 13, 'Dental caries', 'Follow-up recommended in 6 months', 263.65, 'Completed', '2025-01-01', '2025-05-18 08:53:15'),
(40, 1, 2, 34, 4, 18, 'Dental caries', 'Treatment performed successfully', 151.57, 'Completed', '2025-02-10', '2025-05-18 08:53:15'),
(41, 1, 2, 34, 6, 17, 'Fractured tooth', 'Local anesthetic administered', 169.24, 'Completed', '2025-02-10', '2025-05-18 08:53:15'),
(42, 1, 2, 35, 6, 8, 'Pulpitis', 'Follow-up recommended in 6 months', 186.43, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(43, 1, 2, 35, 3, 12, 'Impacted wisdom tooth', 'Additional treatment may be needed', 126.71, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(44, 1, 2, 35, 4, 15, 'Dental caries', 'Follow-up recommended in 6 months', 153.73, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(45, 1, 2, 36, 2, NULL, 'Dental caries', 'Patient tolerated procedure well', 74.34, 'Completed', '2025-02-17', '2025-05-18 08:53:15'),
(46, 1, 2, 36, 5, 13, 'Routine preventive care', 'Local anesthetic administered', 272.06, 'Completed', '2025-02-17', '2025-05-18 08:53:15'),
(47, 1, 2, 36, 1, 18, 'Periodontal disease', 'Local anesthetic administered', 45.08, 'Completed', '2025-02-17', '2025-05-18 08:53:15'),
(48, 1, 2, 39, 6, NULL, 'Gingivitis', 'Additional treatment may be needed', 194.56, 'Completed', '2025-05-12', '2025-05-18 08:53:15'),
(49, 1, 2, 39, 6, 11, 'Gingivitis', 'Follow-up recommended in 6 months', 174.22, 'Completed', '2025-05-12', '2025-05-18 08:53:15'),
(50, 1, 2, 42, 3, 17, 'Gingivitis', 'Local anesthetic administered', 117.88, 'Completed', '2025-01-14', '2025-05-18 08:53:15'),
(51, 1, 2, 42, 6, NULL, 'Fractured tooth', 'Additional treatment may be needed', 167.20, 'Completed', '2025-01-14', '2025-05-18 08:53:15'),
(52, 1, 2, 43, 1, NULL, 'Routine preventive care', 'Treatment performed successfully', 54.07, 'Completed', '2024-12-17', '2025-05-18 08:53:15'),
(53, 1, 2, 43, 6, 27, 'Fractured tooth', 'Local anesthetic administered', 197.76, 'Completed', '2024-12-17', '2025-05-18 08:53:15'),
(54, 1, 2, 43, 4, 14, 'Dental caries', 'Local anesthetic administered', 145.37, 'Completed', '2024-12-17', '2025-05-18 08:53:15'),
(55, 1, 2, 44, 4, 18, 'Periodontal disease', 'Local anesthetic administered', 160.32, 'Completed', '2025-04-15', '2025-05-18 08:53:15'),
(56, 1, 2, 44, 1, 19, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 47.88, 'Completed', '2025-04-15', '2025-05-18 08:53:15'),
(57, 1, 2, 45, 5, NULL, 'Fractured tooth', 'Treatment performed successfully', 255.06, 'Completed', '2024-12-06', '2025-05-18 08:53:15'),
(58, 1, 2, 45, 5, 28, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 233.12, 'Completed', '2024-12-06', '2025-05-18 08:53:15'),
(59, 1, 2, 47, 6, 31, 'Pulpitis', 'Treatment performed successfully', 182.77, 'Completed', '2025-01-29', '2025-05-18 08:53:15'),
(60, 1, 2, 49, 2, 20, 'Pulpitis', 'Follow-up recommended in 6 months', 80.31, 'Completed', '2025-03-25', '2025-05-18 08:53:15'),
(61, 1, 2, 49, 3, 13, 'Routine preventive care', 'Treatment performed successfully', 128.70, 'Completed', '2025-03-25', '2025-05-18 08:53:15'),
(62, 1, 2, 50, 5, 9, 'Routine preventive care', 'Treatment performed successfully', 243.17, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(63, 1, 2, 50, 5, 27, 'Pulpitis', 'Local anesthetic administered', 244.53, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(64, 1, 2, 51, 6, 5, 'Fractured tooth', 'Treatment performed successfully', 172.75, 'Completed', '2025-04-18', '2025-05-18 08:53:15'),
(65, 1, 2, 51, 5, 22, 'Fractured tooth', 'Follow-up recommended in 6 months', 231.82, 'Completed', '2025-04-18', '2025-05-18 08:53:15'),
(66, 1, 2, 51, 4, NULL, 'Pulpitis', 'Additional treatment may be needed', 163.12, 'Completed', '2025-04-18', '2025-05-18 08:53:15'),
(67, 1, 2, 56, 2, 9, 'Gingivitis', 'Local anesthetic administered', 79.67, 'Completed', '2025-04-10', '2025-05-18 08:53:15'),
(68, 1, 2, 57, 4, 13, 'Routine preventive care', 'Patient tolerated procedure well', 149.56, 'Completed', '2024-11-26', '2025-05-18 08:53:15'),
(69, 1, 2, 57, 6, 8, 'Impacted wisdom tooth', 'Local anesthetic administered', 177.07, 'Completed', '2024-11-26', '2025-05-18 08:53:15'),
(70, 1, 2, 58, 1, 20, 'Dental caries', 'Follow-up recommended in 6 months', 54.21, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(71, 1, 2, 58, 2, 29, 'Gingivitis', 'Patient tolerated procedure well', 72.04, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(72, 1, 2, 58, 5, 9, 'Routine preventive care', 'Follow-up recommended in 6 months', 240.58, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(73, 1, 2, 59, 5, 9, 'Dental caries', 'Patient tolerated procedure well', 274.68, 'Completed', '2024-12-26', '2025-05-18 08:53:15'),
(74, 1, 2, 59, 4, 16, 'Periodontal disease', 'Treatment performed successfully', 150.43, 'Completed', '2024-12-26', '2025-05-18 08:53:15'),
(75, 1, 2, 60, 1, 18, 'Periodontal disease', 'Additional treatment may be needed', 50.27, 'Completed', '2025-05-15', '2025-05-18 08:53:15'),
(76, 1, 2, 60, 2, 16, 'Fractured tooth', 'Additional treatment may be needed', 79.25, 'Completed', '2025-05-15', '2025-05-18 08:53:15'),
(77, 1, 2, 63, 4, 25, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 153.42, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(78, 1, 2, 63, 5, 2, 'Routine preventive care', 'Follow-up recommended in 6 months', 233.77, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(79, 1, 2, 63, 6, 22, 'Periodontal disease', 'Patient tolerated procedure well', 185.32, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(80, 1, 2, 64, 6, 29, 'Routine preventive care', 'Patient tolerated procedure well', 190.35, 'Completed', '2025-03-31', '2025-05-18 08:53:15'),
(81, 1, 2, 65, 5, 9, 'Periodontal disease', 'Follow-up recommended in 6 months', 229.92, 'Completed', '2025-01-27', '2025-05-18 08:53:15'),
(82, 1, 2, 66, 1, 2, 'Dental caries', 'Local anesthetic administered', 54.38, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(83, 1, 2, 67, 6, 29, 'Fractured tooth', 'Follow-up recommended in 6 months', 171.95, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(84, 1, 2, 67, 2, NULL, 'Dental caries', 'Local anesthetic administered', 72.50, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(85, 1, 2, 68, 3, 11, 'Periodontal disease', 'Additional treatment may be needed', 116.65, 'Completed', '2025-02-04', '2025-05-18 08:53:15'),
(86, 1, 2, 68, 3, 25, 'Routine preventive care', 'Additional treatment may be needed', 108.68, 'Completed', '2025-02-04', '2025-05-18 08:53:15'),
(87, 1, 2, 68, 2, 15, 'Periodontal disease', 'Follow-up recommended in 6 months', 81.35, 'Completed', '2025-02-04', '2025-05-18 08:53:15'),
(88, 1, 2, 69, 1, 20, 'Dental caries', 'Additional treatment may be needed', 48.19, 'Completed', '2025-05-02', '2025-05-18 08:53:15'),
(89, 1, 2, 69, 6, 1, 'Impacted wisdom tooth', 'Local anesthetic administered', 173.47, 'Completed', '2025-05-02', '2025-05-18 08:53:15'),
(90, 1, 2, 72, 1, 13, 'Dental caries', 'Additional treatment may be needed', 53.12, 'Completed', '2024-12-09', '2025-05-18 08:53:15'),
(91, 1, 2, 72, 4, NULL, 'Periodontal disease', 'Additional treatment may be needed', 150.33, 'Completed', '2024-12-09', '2025-05-18 08:53:15'),
(92, 1, 2, 74, 3, 3, 'Periodontal disease', 'Additional treatment may be needed', 108.60, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(93, 1, 2, 75, 4, 10, 'Pulpitis', 'Treatment performed successfully', 159.36, 'Completed', '2025-05-14', '2025-05-18 08:53:15'),
(94, 1, 2, 75, 3, NULL, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 109.67, 'Completed', '2025-05-14', '2025-05-18 08:53:15'),
(95, 1, 2, 76, 5, 23, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 249.03, 'Completed', '2025-04-02', '2025-05-18 08:53:15'),
(96, 1, 2, 76, 2, NULL, 'Impacted wisdom tooth', 'Treatment performed successfully', 80.48, 'Completed', '2025-04-02', '2025-05-18 08:53:15'),
(97, 1, 2, 77, 5, 17, 'Fractured tooth', 'Additional treatment may be needed', 248.86, 'Completed', '2025-01-27', '2025-05-18 08:53:15'),
(98, 1, 2, 77, 1, 2, 'Pulpitis', 'Local anesthetic administered', 46.34, 'Completed', '2025-01-27', '2025-05-18 08:53:15'),
(99, 1, 2, 77, 1, 9, 'Dental caries', 'Local anesthetic administered', 49.61, 'Completed', '2025-01-27', '2025-05-18 08:53:15'),
(100, 1, 2, 78, 3, 12, 'Impacted wisdom tooth', 'Treatment performed successfully', 113.46, 'Completed', '2025-01-20', '2025-05-18 08:53:15'),
(101, 1, 2, 79, 1, 24, 'Dental caries', 'Follow-up recommended in 6 months', 54.55, 'Completed', '2025-04-23', '2025-05-18 08:53:15'),
(102, 1, 2, 79, 6, 26, 'Routine preventive care', 'Patient tolerated procedure well', 188.41, 'Completed', '2025-04-23', '2025-05-18 08:53:15'),
(103, 1, 2, 82, 1, NULL, 'Dental caries', 'Local anesthetic administered', 49.86, 'Completed', '2024-12-16', '2025-05-18 08:53:15'),
(104, 1, 2, 83, 6, 17, 'Fractured tooth', 'Additional treatment may be needed', 178.73, 'Completed', '2025-03-28', '2025-05-18 08:53:15'),
(105, 1, 2, 85, 5, 20, 'Gingivitis', 'Treatment performed successfully', 273.71, 'Completed', '2024-12-20', '2025-05-18 08:53:15'),
(106, 1, 2, 85, 1, 2, 'Periodontal disease', 'Follow-up recommended in 6 months', 48.64, 'Completed', '2024-12-20', '2025-05-18 08:53:15'),
(107, 1, 2, 85, 2, 20, 'Periodontal disease', 'Additional treatment may be needed', 77.35, 'Completed', '2024-12-20', '2025-05-18 08:53:15'),
(108, 1, 2, 88, 3, 14, 'Routine preventive care', 'Treatment performed successfully', 109.38, 'Completed', '2025-04-17', '2025-05-18 08:53:15'),
(109, 1, 2, 88, 3, 21, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 115.75, 'Completed', '2025-04-17', '2025-05-18 08:53:15'),
(110, 1, 2, 89, 5, 9, 'Fractured tooth', 'Patient tolerated procedure well', 235.99, 'Completed', '2025-02-25', '2025-05-18 08:53:15'),
(111, 1, 2, 89, 4, 7, 'Routine preventive care', 'Follow-up recommended in 6 months', 136.61, 'Completed', '2025-02-25', '2025-05-18 08:53:15'),
(112, 1, 2, 90, 1, 28, 'Dental caries', 'Treatment performed successfully', 48.24, 'Completed', '2024-12-09', '2025-05-18 08:53:15'),
(113, 1, 2, 90, 2, 24, 'Fractured tooth', 'Local anesthetic administered', 77.48, 'Completed', '2024-12-09', '2025-05-18 08:53:15'),
(114, 1, 2, 90, 4, 10, 'Pulpitis', 'Treatment performed successfully', 159.62, 'Completed', '2024-12-09', '2025-05-18 08:53:15'),
(115, 1, 2, 91, 6, 22, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 192.91, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(116, 1, 2, 91, 6, 22, 'Impacted wisdom tooth', 'Local anesthetic administered', 179.55, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(117, 1, 2, 92, 4, 4, 'Routine preventive care', 'Patient tolerated procedure well', 138.22, 'Completed', '2024-12-24', '2025-05-18 08:53:15'),
(118, 1, 2, 92, 6, 6, 'Gingivitis', 'Treatment performed successfully', 180.25, 'Completed', '2024-12-24', '2025-05-18 08:53:15'),
(119, 1, 2, 92, 5, 23, 'Periodontal disease', 'Follow-up recommended in 6 months', 272.58, 'Completed', '2024-12-24', '2025-05-18 08:53:15'),
(120, 1, 2, 93, 3, 19, 'Gingivitis', 'Local anesthetic administered', 123.26, 'Completed', '2025-04-22', '2025-05-18 08:53:15'),
(121, 1, 2, 93, 1, NULL, 'Pulpitis', 'Follow-up recommended in 6 months', 53.37, 'Completed', '2025-04-22', '2025-05-18 08:53:15'),
(122, 1, 2, 93, 3, 7, 'Gingivitis', 'Treatment performed successfully', 126.18, 'Completed', '2025-04-22', '2025-05-18 08:53:15'),
(123, 1, 2, 94, 4, 10, 'Fractured tooth', 'Additional treatment may be needed', 158.22, 'Completed', '2025-05-02', '2025-05-18 08:53:15'),
(124, 1, 2, 94, 1, 7, 'Gingivitis', 'Treatment performed successfully', 54.38, 'Completed', '2025-05-02', '2025-05-18 08:53:15'),
(125, 1, 2, 94, 1, NULL, 'Routine preventive care', 'Patient tolerated procedure well', 48.83, 'Completed', '2025-05-02', '2025-05-18 08:53:15'),
(126, 1, 2, 95, 1, 27, 'Impacted wisdom tooth', 'Local anesthetic administered', 52.80, 'Completed', '2025-01-27', '2025-05-18 08:53:15'),
(127, 1, 2, 95, 4, 11, 'Periodontal disease', 'Follow-up recommended in 6 months', 156.72, 'Completed', '2025-01-27', '2025-05-18 08:53:15'),
(128, 1, 2, 95, 1, NULL, 'Periodontal disease', 'Additional treatment may be needed', 46.20, 'Completed', '2025-01-27', '2025-05-18 08:53:15'),
(129, 1, 2, 96, 6, NULL, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 176.05, 'Completed', '2025-02-10', '2025-05-18 08:53:15'),
(130, 1, 2, 97, 5, 3, 'Periodontal disease', 'Patient tolerated procedure well', 250.89, 'Completed', '2024-11-20', '2025-05-18 08:53:15'),
(131, 1, 2, 97, 2, 9, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 73.15, 'Completed', '2024-11-20', '2025-05-18 08:53:15'),
(132, 1, 2, 98, 1, 1, 'Periodontal disease', 'Follow-up recommended in 6 months', 52.48, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(133, 1, 2, 99, 2, 16, 'Routine preventive care', 'Local anesthetic administered', 81.70, 'Completed', '2025-02-11', '2025-05-18 08:53:15'),
(134, 1, 2, 99, 1, 2, 'Fractured tooth', 'Patient tolerated procedure well', 53.74, 'Completed', '2025-02-11', '2025-05-18 08:53:15'),
(135, 1, 2, 99, 3, 22, 'Periodontal disease', 'Local anesthetic administered', 110.57, 'Completed', '2025-02-11', '2025-05-18 08:53:15'),
(136, 1, 2, 105, 3, 31, 'Routine preventive care', 'Local anesthetic administered', 119.09, 'Completed', '2024-12-02', '2025-05-18 08:53:15'),
(137, 1, 2, 105, 2, NULL, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 72.58, 'Completed', '2024-12-02', '2025-05-18 08:53:15'),
(138, 1, 2, 105, 3, 5, 'Gingivitis', 'Treatment performed successfully', 124.93, 'Completed', '2024-12-02', '2025-05-18 08:53:15'),
(139, 1, 2, 107, 3, 15, 'Dental caries', 'Local anesthetic administered', 126.36, 'Completed', '2025-01-21', '2025-05-18 08:53:15'),
(140, 1, 2, 107, 6, 29, 'Pulpitis', 'Follow-up recommended in 6 months', 193.12, 'Completed', '2025-01-21', '2025-05-18 08:53:15'),
(141, 1, 2, 109, 3, 30, 'Periodontal disease', 'Treatment performed successfully', 112.33, 'Completed', '2025-05-06', '2025-05-18 08:53:15'),
(142, 1, 2, 110, 2, 25, 'Periodontal disease', 'Local anesthetic administered', 77.44, 'Completed', '2025-02-21', '2025-05-18 08:53:15'),
(143, 1, 2, 110, 4, 32, 'Gingivitis', 'Patient tolerated procedure well', 154.68, 'Completed', '2025-02-21', '2025-05-18 08:53:15'),
(144, 1, 2, 110, 3, 5, 'Pulpitis', 'Follow-up recommended in 6 months', 118.49, 'Completed', '2025-02-21', '2025-05-18 08:53:15'),
(145, 1, 2, 111, 3, 28, 'Routine preventive care', 'Treatment performed successfully', 111.13, 'Completed', '2025-03-17', '2025-05-18 08:53:15'),
(146, 1, 2, 111, 5, 30, 'Pulpitis', 'Patient tolerated procedure well', 266.71, 'Completed', '2025-03-17', '2025-05-18 08:53:15'),
(147, 1, 2, 116, 1, 32, 'Dental caries', 'Patient tolerated procedure well', 53.50, 'Completed', '2024-12-27', '2025-05-18 08:53:15'),
(148, 1, 2, 116, 1, 26, 'Routine preventive care', 'Treatment performed successfully', 45.90, 'Completed', '2024-12-27', '2025-05-18 08:53:15'),
(149, 1, 2, 117, 4, 12, 'Dental caries', 'Local anesthetic administered', 163.16, 'Completed', '2025-04-16', '2025-05-18 08:53:15'),
(150, 1, 2, 117, 4, 24, 'Periodontal disease', 'Follow-up recommended in 6 months', 152.98, 'Completed', '2025-04-16', '2025-05-18 08:53:15'),
(151, 1, 2, 121, 5, 24, 'Routine preventive care', 'Patient tolerated procedure well', 250.09, 'Completed', '2024-12-12', '2025-05-18 08:53:15'),
(152, 1, 2, 121, 6, 28, 'Pulpitis', 'Treatment performed successfully', 167.59, 'Completed', '2024-12-12', '2025-05-18 08:53:15'),
(153, 1, 2, 123, 3, 13, 'Fractured tooth', 'Treatment performed successfully', 123.90, 'Completed', '2025-01-09', '2025-05-18 08:53:15'),
(154, 1, 2, 123, 1, 7, 'Dental caries', 'Local anesthetic administered', 53.17, 'Completed', '2025-01-09', '2025-05-18 08:53:15'),
(155, 1, 2, 124, 6, 32, 'Dental caries', 'Local anesthetic administered', 179.94, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(156, 1, 2, 124, 6, 22, 'Gingivitis', 'Treatment performed successfully', 169.37, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(157, 1, 2, 124, 2, 28, 'Gingivitis', 'Follow-up recommended in 6 months', 76.05, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(158, 1, 2, 125, 3, 26, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 116.24, 'Completed', '2025-05-09', '2025-05-18 08:53:15'),
(159, 1, 2, 125, 6, 19, 'Periodontal disease', 'Treatment performed successfully', 174.87, 'Completed', '2025-05-09', '2025-05-18 08:53:15'),
(160, 1, 2, 126, 1, 8, 'Impacted wisdom tooth', 'Local anesthetic administered', 47.39, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(161, 1, 2, 126, 4, 29, 'Pulpitis', 'Additional treatment may be needed', 150.94, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(162, 1, 2, 126, 4, 23, 'Routine preventive care', 'Patient tolerated procedure well', 149.17, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(163, 1, 2, 127, 6, 6, 'Routine preventive care', 'Treatment performed successfully', 165.68, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(164, 1, 2, 127, 3, 27, 'Dental caries', 'Treatment performed successfully', 120.36, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(165, 1, 2, 127, 6, 25, 'Dental caries', 'Additional treatment may be needed', 184.54, 'Completed', '2025-03-10', '2025-05-18 08:53:15'),
(166, 1, 2, 128, 6, 6, 'Dental caries', 'Local anesthetic administered', 169.76, 'Completed', '2024-11-26', '2025-05-18 08:53:15'),
(167, 1, 2, 129, 4, NULL, 'Pulpitis', 'Treatment performed successfully', 151.42, 'Completed', '2024-11-28', '2025-05-18 08:53:15'),
(168, 1, 2, 129, 1, 13, 'Fractured tooth', 'Local anesthetic administered', 52.61, 'Completed', '2024-11-28', '2025-05-18 08:53:15'),
(169, 1, 2, 129, 5, 17, 'Pulpitis', 'Patient tolerated procedure well', 273.03, 'Completed', '2024-11-28', '2025-05-18 08:53:15'),
(170, 1, 2, 131, 3, 8, 'Gingivitis', 'Additional treatment may be needed', 111.39, 'Completed', '2025-04-10', '2025-05-18 08:53:15'),
(171, 1, 2, 132, 1, NULL, 'Periodontal disease', 'Patient tolerated procedure well', 51.45, 'Completed', '2024-12-04', '2025-05-18 08:53:15'),
(172, 1, 2, 132, 4, 1, 'Routine preventive care', 'Follow-up recommended in 6 months', 164.16, 'Completed', '2024-12-04', '2025-05-18 08:53:15'),
(173, 1, 2, 136, 2, 2, 'Routine preventive care', 'Patient tolerated procedure well', 73.15, 'Completed', '2025-01-30', '2025-05-18 08:53:15'),
(174, 1, 2, 136, 5, 3, 'Pulpitis', 'Additional treatment may be needed', 235.36, 'Completed', '2025-01-30', '2025-05-18 08:53:15'),
(175, 1, 2, 137, 5, 3, 'Dental caries', 'Additional treatment may be needed', 245.51, 'Completed', '2025-04-25', '2025-05-18 08:53:15'),
(176, 1, 2, 137, 3, 22, 'Pulpitis', 'Treatment performed successfully', 108.27, 'Completed', '2025-04-25', '2025-05-18 08:53:15'),
(177, 1, 2, 140, 3, 30, 'Gingivitis', 'Follow-up recommended in 6 months', 129.29, 'Completed', '2025-04-10', '2025-05-18 08:53:15'),
(178, 1, 2, 140, 5, 19, 'Pulpitis', 'Follow-up recommended in 6 months', 259.26, 'Completed', '2025-04-10', '2025-05-18 08:53:15'),
(179, 1, 2, 140, 6, 24, 'Pulpitis', 'Treatment performed successfully', 179.26, 'Completed', '2025-04-10', '2025-05-18 08:53:15'),
(180, 1, 2, 141, 5, 12, 'Periodontal disease', 'Patient tolerated procedure well', 237.62, 'Completed', '2025-02-26', '2025-05-18 08:53:15'),
(181, 1, 2, 141, 5, NULL, 'Fractured tooth', 'Patient tolerated procedure well', 233.81, 'Completed', '2025-02-26', '2025-05-18 08:53:15'),
(182, 1, 2, 142, 5, 10, 'Pulpitis', 'Additional treatment may be needed', 272.65, 'Completed', '2025-01-31', '2025-05-18 08:53:15'),
(183, 1, 2, 142, 3, 29, 'Dental caries', 'Treatment performed successfully', 116.24, 'Completed', '2025-01-31', '2025-05-18 08:53:15'),
(184, 1, 2, 142, 2, 4, 'Gingivitis', 'Follow-up recommended in 6 months', 72.16, 'Completed', '2025-01-31', '2025-05-18 08:53:15'),
(185, 1, 2, 143, 6, 12, 'Routine preventive care', 'Follow-up recommended in 6 months', 196.62, 'Completed', '2025-04-14', '2025-05-18 08:53:15'),
(186, 1, 2, 144, 6, NULL, 'Routine preventive care', 'Patient tolerated procedure well', 184.52, 'Completed', '2024-12-16', '2025-05-18 08:53:15'),
(187, 1, 2, 144, 3, 11, 'Routine preventive care', 'Patient tolerated procedure well', 124.02, 'Completed', '2024-12-16', '2025-05-18 08:53:15'),
(188, 1, 2, 145, 5, NULL, 'Fractured tooth', 'Treatment performed successfully', 261.84, 'Completed', '2025-04-22', '2025-05-18 08:53:15'),
(189, 1, 2, 145, 3, 24, 'Periodontal disease', 'Additional treatment may be needed', 117.98, 'Completed', '2025-04-22', '2025-05-18 08:53:15'),
(190, 1, 2, 145, 5, 1, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 239.13, 'Completed', '2025-04-22', '2025-05-18 08:53:15'),
(191, 1, 2, 147, 1, 7, 'Dental caries', 'Local anesthetic administered', 45.09, 'Completed', '2025-03-26', '2025-05-18 08:53:15'),
(192, 1, 2, 147, 1, 3, 'Periodontal disease', 'Additional treatment may be needed', 51.17, 'Completed', '2025-03-26', '2025-05-18 08:53:15'),
(193, 1, 2, 147, 5, NULL, 'Gingivitis', 'Local anesthetic administered', 235.28, 'Completed', '2025-03-26', '2025-05-18 08:53:15'),
(194, 1, 2, 148, 3, 24, 'Pulpitis', 'Additional treatment may be needed', 122.88, 'Completed', '2024-12-02', '2025-05-18 08:53:15'),
(195, 1, 2, 149, 2, 8, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 79.47, 'Completed', '2025-03-17', '2025-05-18 08:53:15'),
(196, 1, 2, 149, 5, 10, 'Pulpitis', 'Follow-up recommended in 6 months', 240.62, 'Completed', '2025-03-17', '2025-05-18 08:53:15'),
(197, 1, 2, 151, 5, 14, 'Routine preventive care', 'Follow-up recommended in 6 months', 234.90, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(198, 1, 2, 151, 4, 17, 'Gingivitis', 'Additional treatment may be needed', 155.47, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(199, 1, 2, 151, 5, 1, 'Dental caries', 'Patient tolerated procedure well', 228.42, 'Completed', '2025-04-28', '2025-05-18 08:53:15'),
(200, 1, 2, 152, 3, NULL, 'Routine preventive care', 'Additional treatment may be needed', 110.18, 'Completed', '2024-12-16', '2025-05-18 08:53:15'),
(201, 1, 2, 152, 1, 13, 'Gingivitis', 'Additional treatment may be needed', 49.27, 'Completed', '2024-12-16', '2025-05-18 08:53:15'),
(202, 1, 2, 152, 6, 8, 'Routine preventive care', 'Follow-up recommended in 6 months', 178.83, 'Completed', '2024-12-16', '2025-05-18 08:53:15'),
(203, 1, 2, 153, 1, 5, 'Gingivitis', 'Local anesthetic administered', 48.80, 'Completed', '2025-01-29', '2025-05-18 08:53:15'),
(204, 1, 2, 153, 6, 14, 'Gingivitis', 'Patient tolerated procedure well', 187.96, 'Completed', '2025-01-29', '2025-05-18 08:53:15'),
(205, 1, 2, 153, 5, 7, 'Impacted wisdom tooth', 'Additional treatment may be needed', 260.83, 'Completed', '2025-01-29', '2025-05-18 08:53:15'),
(206, 1, 2, 154, 5, 12, 'Routine preventive care', 'Treatment performed successfully', 236.57, 'Completed', '2025-01-23', '2025-05-18 08:53:15'),
(207, 1, 2, 156, 4, 23, 'Pulpitis', 'Local anesthetic administered', 151.15, 'Completed', '2025-01-06', '2025-05-18 08:53:15'),
(208, 1, 2, 157, 3, 8, 'Impacted wisdom tooth', 'Treatment performed successfully', 117.11, 'Completed', '2025-02-19', '2025-05-18 08:53:15'),
(209, 1, 2, 157, 1, 4, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 45.17, 'Completed', '2025-02-19', '2025-05-18 08:53:15'),
(210, 1, 2, 157, 4, 13, 'Gingivitis', 'Local anesthetic administered', 157.31, 'Completed', '2025-02-19', '2025-05-18 08:53:15'),
(211, 1, 2, 158, 1, 29, 'Impacted wisdom tooth', 'Additional treatment may be needed', 48.33, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(212, 1, 2, 158, 6, 31, 'Gingivitis', 'Additional treatment may be needed', 189.57, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(213, 1, 2, 158, 3, 19, 'Dental caries', 'Patient tolerated procedure well', 112.98, 'Completed', '2025-02-24', '2025-05-18 08:53:15'),
(214, 1, 2, 160, 2, 1, 'Periodontal disease', 'Additional treatment may be needed', 84.70, 'Completed', '2024-12-30', '2025-05-18 08:53:15'),
(215, 1, 2, 160, 3, 29, 'Periodontal disease', 'Patient tolerated procedure well', 127.01, 'Completed', '2024-12-30', '2025-05-18 08:53:15'),
(216, 1, 2, 161, 1, 20, 'Gingivitis', 'Patient tolerated procedure well', 49.38, 'Completed', '2024-12-18', '2025-05-18 08:53:15'),
(217, 1, 2, 162, 2, 32, 'Fractured tooth', 'Local anesthetic administered', 79.48, 'Completed', '2025-04-29', '2025-05-18 08:53:15'),
(218, 1, 2, 162, 2, 19, 'Routine preventive care', 'Patient tolerated procedure well', 72.39, 'Completed', '2025-04-29', '2025-05-18 08:53:15'),
(219, 1, 2, 163, 5, 3, 'Dental caries', 'Treatment performed successfully', 227.22, 'Completed', '2025-02-17', '2025-05-18 08:53:15'),
(220, 1, 2, 164, 2, NULL, 'Fractured tooth', 'Local anesthetic administered', 85.09, 'Completed', '2024-11-25', '2025-05-18 08:53:15'),
(221, 1, 2, 166, 1, NULL, 'Fractured tooth', 'Follow-up recommended in 6 months', 49.79, 'Completed', '2025-04-29', '2025-05-18 08:53:15'),
(222, 1, 2, 166, 4, 14, 'Gingivitis', 'Patient tolerated procedure well', 144.27, 'Completed', '2025-04-29', '2025-05-18 08:53:15'),
(223, 1, 2, 166, 3, NULL, 'Pulpitis', 'Local anesthetic administered', 113.69, 'Completed', '2025-04-29', '2025-05-18 08:53:15'),
(224, 1, 2, 167, 6, 28, 'Pulpitis', 'Treatment performed successfully', 167.38, 'Completed', '2025-01-31', '2025-05-18 08:53:15'),
(225, 1, 2, 167, 4, 15, 'Fractured tooth', 'Additional treatment may be needed', 137.07, 'Completed', '2025-01-31', '2025-05-18 08:53:15'),
(226, 1, 2, 169, 4, 23, 'Impacted wisdom tooth', 'Follow-up recommended in 6 months', 152.05, 'Completed', '2025-03-07', '2025-05-18 08:53:15'),
(227, 1, 2, 170, 1, 26, 'Dental caries', 'Treatment performed successfully', 50.06, 'Completed', '2025-01-16', '2025-05-18 08:53:15'),
(228, 1, 2, 170, 5, 26, 'Dental caries', 'Follow-up recommended in 6 months', 236.72, 'Completed', '2025-01-16', '2025-05-18 08:53:15'),
(229, 1, 2, 172, 5, 17, 'Routine preventive care', 'Treatment performed successfully', 231.27, 'Completed', '2025-01-07', '2025-05-18 08:53:15'),
(230, 1, 2, 173, 6, 27, 'Routine preventive care', 'Patient tolerated procedure well', 189.00, 'Completed', '2025-02-03', '2025-05-18 08:53:15'),
(231, 1, 2, 173, 6, 30, 'Pulpitis', 'Additional treatment may be needed', 167.34, 'Completed', '2025-02-03', '2025-05-18 08:53:15'),
(232, 1, 2, 174, 3, 31, 'Pulpitis', 'Treatment performed successfully', 121.76, 'Completed', '2024-12-17', '2025-05-18 08:53:15'),
(233, 1, 2, 175, 4, 27, 'Impacted wisdom tooth', 'Local anesthetic administered', 140.42, 'Completed', '2025-01-22', '2025-05-18 08:53:15'),
(234, 1, 2, 175, 4, 19, 'Fractured tooth', 'Local anesthetic administered', 142.27, 'Completed', '2025-01-22', '2025-05-18 08:53:15'),
(235, 1, 2, 177, 6, 20, 'Impacted wisdom tooth', 'Patient tolerated procedure well', 170.28, 'Completed', '2025-01-29', '2025-05-18 08:53:15'),
(236, 1, 2, 177, 6, 14, 'Pulpitis', 'Treatment performed successfully', 179.67, 'Completed', '2025-01-29', '2025-05-18 08:53:15'),
(237, 1, 2, 178, 3, 27, 'Routine preventive care', 'Local anesthetic administered', 108.33, 'Completed', '2025-01-30', '2025-05-18 08:53:15'),
(238, 1, 2, 179, 4, 17, 'Routine preventive care', 'Follow-up recommended in 6 months', 160.59, 'Completed', '2025-05-09', '2025-05-18 08:53:15'),
(239, 1, 2, 179, 5, NULL, 'Periodontal disease', 'Additional treatment may be needed', 269.53, 'Completed', '2025-05-09', '2025-05-18 08:53:15'),
(240, 1, 2, 179, 6, 14, 'Pulpitis', 'Patient tolerated procedure well', 172.65, 'Completed', '2025-05-09', '2025-05-18 08:53:15'),
(241, 1, 2, 180, 4, 20, 'Dental caries', 'Local anesthetic administered', 161.21, 'Completed', '2025-05-05', '2025-05-18 08:53:15'),
(242, 1, 2, 180, 6, 5, 'Periodontal disease', 'Follow-up recommended in 6 months', 171.02, 'Completed', '2025-05-05', '2025-05-18 08:53:15'),
(243, 1, 2, 180, 5, 32, 'Routine preventive care', 'Local anesthetic administered', 228.84, 'Completed', '2025-05-05', '2025-05-18 08:53:15'),
(244, 1, 2, 181, 1, 22, 'Pulpitis', 'Local anesthetic administered', 47.90, 'Completed', '2024-12-11', '2025-05-18 08:53:15'),
(245, 1, 2, 181, 3, 16, 'Periodontal disease', 'Treatment performed successfully', 130.34, 'Completed', '2024-12-11', '2025-05-18 08:53:15'),
(246, 1, 2, 182, 3, 10, 'Gingivitis', 'Treatment performed successfully', 117.61, 'Completed', '2025-05-12', '2025-05-18 08:53:15'),
(247, 1, 2, 182, 6, 23, 'Dental caries', 'Patient tolerated procedure well', 194.04, 'Completed', '2025-05-12', '2025-05-18 08:53:15'),
(248, 1, 2, 185, 2, 30, 'Dental caries', 'Follow-up recommended in 6 months', 86.44, 'Completed', '2025-01-10', '2025-05-18 08:53:15'),
(249, 1, 2, 190, 3, 22, 'Periodontal disease', 'Follow-up recommended in 6 months', 111.86, 'Completed', '2024-11-29', '2025-05-18 08:53:15'),
(250, 1, 2, 190, 1, 20, 'Periodontal disease', 'Local anesthetic administered', 51.52, 'Completed', '2024-11-29', '2025-05-18 08:53:15'),
(251, 1, 2, 191, 3, 17, 'Periodontal disease', 'Additional treatment may be needed', 124.42, 'Completed', '2025-04-15', '2025-05-18 08:53:15'),
(252, 1, 2, 191, 3, 16, 'Gingivitis', 'Follow-up recommended in 6 months', 108.64, 'Completed', '2025-04-15', '2025-05-18 08:53:15'),
(253, 1, 2, 191, 1, 6, 'Routine preventive care', 'Treatment performed successfully', 54.35, 'Completed', '2025-04-15', '2025-05-18 08:53:15'),
(254, 1, 2, 192, 1, 1, 'Gingivitis', 'Additional treatment may be needed', 52.99, 'Completed', '2025-03-31', '2025-05-18 08:53:15'),
(255, 1, 2, 198, 4, 32, 'Dental caries', 'Additional treatment may be needed', 158.10, 'Completed', '2025-02-13', '2025-05-18 08:53:15'),
(256, 1, 2, 198, 4, NULL, 'Dental caries', 'Additional treatment may be needed', 149.97, 'Completed', '2025-02-13', '2025-05-18 08:53:15'),
(257, 1, 2, 199, 4, NULL, 'Dental caries', 'Treatment performed successfully', 144.37, 'Completed', '2025-05-12', '2025-05-18 08:53:15'),
(258, 1, 2, 199, 3, 29, 'Routine preventive care', 'Local anesthetic administered', 113.66, 'Completed', '2025-05-12', '2025-05-18 08:53:15'),
(259, 1, 2, 199, 1, NULL, 'Routine preventive care', 'Additional treatment may be needed', 46.22, 'Completed', '2025-05-12', '2025-05-18 08:53:15'),
(260, 1, 2, NULL, 3, NULL, 'Routine care', '', 120.00, 'Planned', '2025-05-24', '2025-05-24 09:30:17'),
(261, 1, 2, NULL, 3, 16, 'Routine care', '', 120.00, 'Planned', '2025-05-24', '2025-05-24 09:30:27'),
(262, 1, 2, NULL, 1, NULL, 'Routine care', '', 50.00, 'Completed', '2025-05-24', '2025-05-24 09:30:52'),
(263, 1, 2, NULL, 1, NULL, 'General treatment', '', 50.00, 'Completed', '2025-05-24', '2025-05-24 09:50:59'),
(264, 1, 2, NULL, 3, 16, 'Routine care', '', 120.00, 'Completed', '2025-05-24', '2025-05-24 09:51:23'),
(265, 1, 2, NULL, 3, 24, 'Routine care', 'vvvvvvvv', 120.00, 'Completed', '2025-05-24', '2025-05-24 12:46:17'),
(266, 1, 2, NULL, 6, 16, 'Routine care', '', 180.00, 'Completed', '2025-05-24', '2025-05-24 13:14:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','dentist','staff') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(2, 'dr.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith', 'dr.smith@clinic.com', 'dentist', 1, '2025-05-15 23:23:16', '2025-05-17 18:13:53', '2025-05-18 08:53:16'),
(3, 'reception', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Reception Staff', 'reception@clinic.com', 'staff', 1, '2025-05-16 09:23:16', '2025-05-17 18:13:53', '2025-05-18 08:53:16'),
(4, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@dentalclinic.com', 'admin', 1, '2025-05-18 14:23:16', '2025-05-17 18:47:10', '2025-05-18 08:53:16'),
(5, 'Shashini', '$2y$10$z8eK6Bjnu44OB9yvkO6heOAekudit6Gjrmq7QoyK73YWQYIY2MXTy', 'Shashini Weerkkody', 'shashiniminosha2019@gmail.com', 'dentist', 1, NULL, '2025-05-24 08:36:54', '2025-05-24 08:36:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `dentist_id` (`dentist_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_appointment_date` (`scheduled_date`),
  ADD KEY `idx_appointment_status` (`status`),
  ADD KEY `idx_patient_appointments` (`patient_id`,`scheduled_date`);

--
-- Indexes for table `dental_notes`
--
ALTER TABLE `dental_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `dental_teeth`
--
ALTER TABLE `dental_teeth`
  ADD PRIMARY KEY (`tooth_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `file_number` (`file_number`),
  ADD KEY `idx_file_number` (`file_number`),
  ADD KEY `idx_patient_name` (`last_name`,`first_name`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `patient_medical_history`
--
ALTER TABLE `patient_medical_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_patient_medical` (`patient_id`);

--
-- Indexes for table `patient_notes`
--
ALTER TABLE `patient_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_note_date` (`date_added`),
  ADD KEY `idx_patient_notes` (`patient_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `record_id` (`record_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_patient_payments` (`patient_id`,`payment_date`);

--
-- Indexes for table `periodontal_charting`
--
ALTER TABLE `periodontal_charting`
  ADD PRIMARY KEY (`chart_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `tooth_id` (`tooth_id`);

--
-- Indexes for table `sms_log`
--
ALTER TABLE `sms_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_appointment_sms` (`appointment_id`),
  ADD KEY `idx_patient_sms` (`patient_id`),
  ADD KEY `idx_sms_status` (`status`),
  ADD KEY `idx_provider` (`provider`),
  ADD KEY `idx_sent_date` (`sent_at`),
  ADD KEY `idx_message_id` (`message_id`);

--
-- Indexes for table `sms_settings`
--
ALTER TABLE `sms_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `treatments`
--
ALTER TABLE `treatments`
  ADD PRIMARY KEY (`treatment_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_treatment_code` (`code`),
  ADD KEY `idx_treatment_name` (`name`);

--
-- Indexes for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `dentist_id` (`dentist_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `treatment_id` (`treatment_id`),
  ADD KEY `tooth_id` (`tooth_id`),
  ADD KEY `idx_treatment_date` (`treatment_date`),
  ADD KEY `idx_patient_treatments` (`patient_id`,`treatment_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `dental_notes`
--
ALTER TABLE `dental_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_medical_history`
--
ALTER TABLE `patient_medical_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_notes`
--
ALTER TABLE `patient_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=269;

--
-- AUTO_INCREMENT for table `periodontal_charting`
--
ALTER TABLE `periodontal_charting`
  MODIFY `chart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_log`
--
ALTER TABLE `sms_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sms_settings`
--
ALTER TABLE `sms_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `treatments`
--
ALTER TABLE `treatments`
  MODIFY `treatment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `treatment_records`
--
ALTER TABLE `treatment_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`dentist_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `dental_notes`
--
ALTER TABLE `dental_notes`
  ADD CONSTRAINT `dental_notes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dental_notes_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_medical_history`
--
ALTER TABLE `patient_medical_history`
  ADD CONSTRAINT `patient_medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_medical_history_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_notes`
--
ALTER TABLE `patient_notes`
  ADD CONSTRAINT `patient_notes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_notes_ibfk_2` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `treatment_records` (`record_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `periodontal_charting`
--
ALTER TABLE `periodontal_charting`
  ADD CONSTRAINT `periodontal_charting_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `periodontal_charting_ibfk_2` FOREIGN KEY (`tooth_id`) REFERENCES `dental_teeth` (`tooth_id`) ON DELETE CASCADE;

--
-- Constraints for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD CONSTRAINT `treatment_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_records_ibfk_2` FOREIGN KEY (`dentist_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatment_records_ibfk_4` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`treatment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_records_ibfk_5` FOREIGN KEY (`tooth_id`) REFERENCES `dental_teeth` (`tooth_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
