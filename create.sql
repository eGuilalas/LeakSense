-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2024 at 03:37 AM
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
-- Database: `leaksense`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_recipients`
--

CREATE TABLE `email_recipients` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 0, -- 0 for active, 1 for disabled
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_recipients`
--

INSERT INTO `email_recipients` (`id`, `email`, `status`, `created_at`) VALUES
(3, 'tester123@gmail.com', 0, '2024-11-02 00:43:39');

-- --------------------------------------------------------

--
-- Table structure for table `gas_alert_responses`
--

CREATE TABLE `gas_alert_responses` (
  `id` int(11) NOT NULL,
  `reading_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `response_type` enum('acknowledged','false_alarm') NOT NULL,
  `response_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `comments` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gas_alert_responses`
--



-- --------------------------------------------------------

--
-- Table structure for table `gas_readings`
--

CREATE TABLE `gas_readings` (
  `id` int(11) NOT NULL,
  `device_id` varchar(10) DEFAULT NULL,
  `gas_level` float NOT NULL,
  `gas_type` varchar(20) DEFAULT NULL,
  `smoke_status` tinyint(1) DEFAULT NULL,
  `co_status` tinyint(1) DEFAULT NULL,
  `lpg_status` tinyint(1) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `alert_status` tinyint(1) DEFAULT 0,
  `threshold_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `thresholds`
--

CREATE TABLE `thresholds` (
  `id` int(11) NOT NULL,
  `device_id` varchar(10) DEFAULT NULL,
  `smoke_threshold` float DEFAULT NULL,
  `co_threshold` float DEFAULT NULL,
  `lpg_threshold` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thresholds`
--

INSERT INTO `thresholds` (`id`, `device_id`, `smoke_threshold`, `co_threshold`, `lpg_threshold`, `created_at`) VALUES
(1, 'GS1', 5, 5, 5, '2024-11-01 21:29:09'),
(2, 'GS2', 5, 5, 5, '2024-11-01 21:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','super_user','super_admin') NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `expiration_date`, `failed_attempts`, `lockout_until`, `name`, `email`, `phone`, `address`, `employee_id`, `position`) VALUES
(1, 'admin', '$2y$10$hNRRovozD0UAsX3wc4cn0.TGN0FVuIghHaBRk3t4yZOebHlcy.hkq', 'admin', '2033-11-17', 0, NULL, '', '', '', '', '', ''),
(2, 'user', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'user', '2031-11-19', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'super_user', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'super_user', '2029-11-14', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'super_admin', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'super_admin', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'earl', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'admin', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, '1earl', '$2y$10$c7s.Th8Zaf7KXUkfgBjlU.W7tqVV2lSZfvb11UjgS9I9rrA8AtCEO', 'user', '2028-10-17', 0, NULL, 'earl', 'g@mail.com', '1234', 'M8V 2S4', 'F1', 'Student'),
(11, 'jayf', '$2y$10$UJf19LmvbhFzOCiOgf3pEOUrwpLCmoNzKVEpqQwp4cxxU/KLmGhzG', 'user', '2024-12-25', 0, NULL, 'jay', 'jay@mail.com', '123', 'asdfas', 'F02', 'Manager');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_recipients`
--
ALTER TABLE `email_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `gas_alert_responses`
--
ALTER TABLE `gas_alert_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reading_id` (`reading_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `gas_readings`
--
ALTER TABLE `gas_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `threshold_id` (`threshold_id`);

--
-- Indexes for table `thresholds`
--
ALTER TABLE `thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`);

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
-- AUTO_INCREMENT for table `email_recipients`
--
ALTER TABLE `email_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gas_alert_responses`
--
ALTER TABLE `gas_alert_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `gas_readings`
--
ALTER TABLE `gas_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17872;

--
-- AUTO_INCREMENT for table `thresholds`
--
ALTER TABLE `thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gas_alert_responses`
--
ALTER TABLE `gas_alert_responses`
  ADD CONSTRAINT `gas_alert_responses_ibfk_1` FOREIGN KEY (`reading_id`) REFERENCES `gas_readings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `gas_alert_responses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `gas_readings`
--
ALTER TABLE `gas_readings`
  ADD CONSTRAINT `gas_readings_ibfk_1` FOREIGN KEY (`threshold_id`) REFERENCES `thresholds` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Trigger to automatically add a new email recipient when a user is inserted
DELIMITER //
CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO email_recipients (email, status)
    VALUES (NEW.email, 0);
END;
//

-- Trigger to automatically update the email in email_recipients if it is changed in the users table
CREATE TRIGGER after_user_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.email <> NEW.email THEN
        UPDATE email_recipients
        SET email = NEW.email
        WHERE email = OLD.email;
    END IF;
END;
//

DELIMITER ;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- after_user_insert Trigger: Automatically inserts a new user's email and sets the status to 0 (active) in email_recipients.
-- after_user_update Trigger: Updates the email in email_recipients if the corresponding email in users is updated.