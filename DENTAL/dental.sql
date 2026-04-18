-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2024 at 01:44 PM
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
-- Database: `dental`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_appointments`
--

CREATE TABLE `tbl_appointments` (
  `id` int(11) NOT NULL,
  `name` int(255) NOT NULL,
  `contact` varchar(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `modified_date` date DEFAULT NULL,
  `modified_time` time DEFAULT NULL,
  `modified_by` int(3) DEFAULT NULL,
  `service_type` int(11) NOT NULL,
  `status` int(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_appointments`
--

INSERT INTO `tbl_appointments` (`id`, `name`, `contact`, `date`, `time`, `modified_date`, `modified_time`, `modified_by`, `service_type`, `status`) VALUES
(5, 5, '07654321234', '2024-11-23', '10:30:00', '2024-11-23', '09:00:00', 3, 8, 2),
(6, 6, '09666666666', '2024-11-24', '12:30:00', NULL, NULL, NULL, 7, 3),
(7, 7, '09666666666', '2024-11-25', '15:00:00', NULL, NULL, NULL, 5, 3),
(12, 12, '09971636182', '2024-11-25', '12:30:00', NULL, NULL, NULL, 10, 3),
(13, 13, '09109239817', '2024-11-26', '15:00:00', NULL, NULL, NULL, 9, 3);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_archives`
--

CREATE TABLE `tbl_archives` (
  `id` int(11) NOT NULL,
  `name` int(255) NOT NULL,
  `contact` varchar(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `modified_date` date DEFAULT NULL,
  `modified_time` time DEFAULT NULL,
  `service_type` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `completion` enum('1','2','3') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_archives`
--

INSERT INTO `tbl_archives` (`id`, `name`, `contact`, `date`, `time`, `modified_date`, `modified_time`, `service_type`, `note`, `price`, `completion`) VALUES
(1, 1, '09234567890', '2024-11-23', '15:00:00', '2024-11-24', '15:00:00', 11, 'dental cleaning', 40000.00, '2'),
(2, 4, '06784956789', '2024-11-23', '10:30:00', NULL, NULL, 4, 'Pa brace ka next week.', 100000.00, '2'),
(3, 2, '01293612873', '2024-11-29', '15:00:00', '2024-11-27', '12:30:00', 4, 'done', 100000.00, '2'),
(7, 3, '09981632871', '2024-11-29', '13:30:00', NULL, NULL, 10, 'ssss', 40000.00, '2'),
(8, 11, '09143143143', '2024-11-28', '13:30:00', NULL, NULL, 9, 'dasdad', 121213.00, '1'),
(9, 9, '09281717171', '2024-11-27', '15:00:00', NULL, NULL, 8, 'sdada', 111213.00, '2');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bin`
--

CREATE TABLE `tbl_bin` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `modified_date` date DEFAULT NULL,
  `modified_time` time DEFAULT NULL,
  `modified_by` int(3) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_patient`
--

CREATE TABLE `tbl_patient` (
  `id` int(11) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  `middle_name` varchar(2) DEFAULT NULL,
  `last_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_patient`
--

INSERT INTO `tbl_patient` (`id`, `first_name`, `middle_name`, `last_name`) VALUES
(1, 'Roland', 'A.', 'Verdan'),
(2, 'JAJA', 'G', 'Rosmo'),
(3, 'Allyah', 'M', 'Laroa'),
(4, 'Ris', 'N', 'Ribleza '),
(5, 'Shane', 'S.', 'Laurente'),
(6, 'Jude Marion', '', 'Hisoler'),
(7, 'Christian', '', 'Pocong'),
(8, 'kai', 'P', 'Young'),
(9, 'Jovi', 'S.', 'Von'),
(10, 'Alejo', 'E', 'Jan Anthony '),
(11, 'Benavidez', 'S', 'Celestina'),
(12, 'Lyra', '', 'Genabe'),
(13, 'Allyssa', '', 'Gacer'),
(14, 'Ichigo', 'D.', 'Kurosaki');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_role`
--

CREATE TABLE `tbl_role` (
  `id` int(11) NOT NULL,
  `role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_role`
--

INSERT INTO `tbl_role` (`id`, `role`) VALUES
(1, 'admin'),
(2, 'doctor'),
(3, 'dental_assistant');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_services`
--

CREATE TABLE `tbl_services` (
  `id` int(11) NOT NULL,
  `service_image` varchar(255) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `service_description` text NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_services`
--

INSERT INTO `tbl_services` (`id`, `service_image`, `service_name`, `service_description`, `price`) VALUES
(21, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/att.kzztlIPm6WQjw49NU43LQ-FjkW7fVT7Fh9Avo57z1NA.jpg', 'All Porcelain Veneers & Zirconia', 'Dental veneers are custom-made shells that fit over the front surfaces of your teeth. They conceal cracks, chips, stains and other cosmetic imperfections.', 18000.00),
(22, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/att.vF9Xup2c0HmPNW9y711D11AaSEl7GwDfYo5xBzNH9EQ.jpg', 'Crown & Bridge', 'This is a custom-made solution to restore damaged or missing teeth. Crowns cover a weakened tooth, strengthen, and keep the natural appearance of the teeth. Bridges completely replace one or more teeth by anchoring the adjacent tooth and filling gaps.', 10000.00),
(23, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/before-and-after-photo-of-dentist-cleaning-teeth.jpg', 'Dental Cleaning', 'Professional cleaning that removes plaques, tartar, stains that are stuck in your teeth and gums, and polishing to prevent cavities, gum disease, and bad breath. Giving you a cleaner, brighter and healthier smile', 1000.00),
(24, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/att.MmQNc0krmGLGMoirJ-Rphh5KnSl_luV-qCJi2mE6igQ.jpg', 'Dental Implants', 'Permanent solution for missing teeth. This procedure includes putting a titanium post in your jawbone that acts as a strong foundation for a natural looking crown.', 55000.00),
(25, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/whiteing-blog-pic.jpg', 'Dental Whitening', 'Removing stains caused by coffee, wine, tea or aging. This procedure can lighten your teeth giving you a brighter smile.', 10000.00),
(26, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/depositphotos_595237212-stock-photo-pictures-dental-implants-press-ceramic.jpg', 'Dentures', 'Dentures are removable oral appliances that replace missing teeth. They help restore oral health and function so you can chew and speak more easily.', 20000.00),
(27, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/DentalSurgicalExtraction.jpg', 'Extraction', 'Safe removal of badly decaying, infected teeth or overcrowding.', 1000.00),
(28, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/att.f7PTUiLO7KKupv17ibJLse6-PUYbOV9IHy1_JfskSCg.jpg', 'Full Exam & X-Ray', 'Gives a full check-up of your teeth and gums and X-ray helps to spot hidden problems ensuring early diagnosis and prevention for healthier teeth.', 500.00),
(29, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/att.eFo73D7w5khpFqds095oE_STPx-qtnvZMu9w0Kvk0vI.jpg', 'Orthodontic Braces', 'Straighten misaligned teeth and correct bite issues by using wires, brackets, and braces to slowly shift your teeth into the ideal position. This service is customized depending on the patient\'s issue. \r\n\r\n', 50000.00),
(30, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/mercury-free-restorations.jpg', 'Restoration', 'Repairing damaged and decaying teeth to restore their functions and appearance. This gives your teeth protection from further damage.', 1000.00),
(31, 'C:/xampp/htdocs/DENTAL/HOME_PAGE/SERVICES/SERVICES_IMAGES/Root+canal+radiograp+x-rays+showing+molar+teeth+before+and+after+treatment.jpg', 'Root Canal Treatment', 'Removal of decaying or badly infected tooth, clean the inside of the tooth, and seal it to prevent further damage. This treatment relieves pain, saves your natural tooth, and restores normal function of the tooth.', 8000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_service_type`
--

CREATE TABLE `tbl_service_type` (
  `id` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_service_type`
--

INSERT INTO `tbl_service_type` (`id`, `service_type`, `price`) VALUES
(1, 'All Porcelain Veneers & Zirconia', 30000.00),
(2, 'Crown & Bridge', 30000.00),
(3, 'Dental Cleaning', 2000.00),
(4, 'Dental Implants', 100000.00),
(5, 'Dental Whitening', 20000.00),
(6, 'Dentures', 30000.00),
(7, 'Extraction', 1500.00),
(8, 'Full Exam & X-Ray', 2000.00),
(9, 'Orthodontic Braces', 280000.00),
(10, 'Restoration', 40000.00),
(11, 'Root Canal Treatment', 40000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_status`
--

CREATE TABLE `tbl_status` (
  `id` int(11) NOT NULL,
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_status`
--

INSERT INTO `tbl_status` (`id`, `status`) VALUES
(1, 'pending'),
(2, 'declined'),
(3, 'approved'),
(4, 'finished');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transaction_history`
--

CREATE TABLE `tbl_transaction_history` (
  `id` int(11) NOT NULL,
  `name` int(255) NOT NULL,
  `contact` varchar(11) NOT NULL,
  `service_type` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `bill` decimal(10,2) NOT NULL,
  `paid` decimal(10,2) NOT NULL,
  `outstanding_balance` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_transaction_history`
--

INSERT INTO `tbl_transaction_history` (`id`, `name`, `contact`, `service_type`, `date`, `bill`, `paid`, `outstanding_balance`) VALUES
(4, 8, '09727352826', 9, '2024-11-28', 50000.00, 15000.00, 35000.00),
(5, 10, '09666669999', 9, '2024-11-27', 50000.00, 15000.00, 35000.00),
(6, 14, '12345678907', 9, '2024-11-26', 50000.00, 15000.00, 35000.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` int(3) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contact` varchar(20) DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `username`, `password`, `role`) VALUES
(13, 'doctor', '$2y$10$fON8fGAXO/XSQmP0u1fK4eArKL1/UIAq8lG4Yq1zqORFCXm/wNeO2', 2),
(14, 'admin', '$2y$10$3nObhg0eCoRN9wrwdn75AOI/pnfnG9hZFCEJYZ1QP.5VpTimUwcFu', 1),
(15, 'dental', '$2y$10$kFqaObkRUetj00kIS.r.huQ2R06JYKse3gz0IAl/bThA.pb5OJvUK', 3),
(17, 'ss', '$2y$10$SbMfVs7W94Us6R7pMMKG5O2OllJ2K5qyzyVsVSHnB7LpT0fXpwjvK', 2),
(18, 'ssss', '$2y$10$Ztw2s2AZad8kJI/UeLIAvu7fBckrSwfJJv2biXUHGYs/Ld/7FKqty', 1),
(19, 'sssss', '$2y$10$0lfrOAMxKkv22iNCzaGn5up6kVXG86kwUTRYgURp3PdJa5Pv7SNo.', 3),
(20, 'ssssssss', '$2y$10$elk8cx/rJRhG7mz96bunauDceZmrJ8Rl.7QLuuqQVZRLcfOe8qCrK', 0),
(21, 'sss', '$2y$10$wzVGor8lgRr8sG46sh/pUeyAEeBwHK6tYNaSWzW9m.m0gGKk99qXm', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_appointments`
--
ALTER TABLE `tbl_appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_archives`
--
ALTER TABLE `tbl_archives`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_bin`
--
ALTER TABLE `tbl_bin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_patient`
--
ALTER TABLE `tbl_patient`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_role`
--
ALTER TABLE `tbl_role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_services`
--
ALTER TABLE `tbl_services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_service_type`
--
ALTER TABLE `tbl_service_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_status`
--
ALTER TABLE `tbl_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_transaction_history`
--
ALTER TABLE `tbl_transaction_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_appointments`
--
ALTER TABLE `tbl_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tbl_archives`
--
ALTER TABLE `tbl_archives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_bin`
--
ALTER TABLE `tbl_bin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_patient`
--
ALTER TABLE `tbl_patient`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tbl_role`
--
ALTER TABLE `tbl_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_services`
--
ALTER TABLE `tbl_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tbl_service_type`
--
ALTER TABLE `tbl_service_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_status`
--
ALTER TABLE `tbl_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_transaction_history`
--
ALTER TABLE `tbl_transaction_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
