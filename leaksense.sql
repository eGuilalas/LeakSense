-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2024 at 11:08 PM
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
-- Table structure for table `alert`
--

CREATE TABLE `alert` (
  `alertID` int(11) NOT NULL,
  `deviceID` varchar(11) DEFAULT NULL,
  `readingID` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `gastype` varchar(20) NOT NULL,
  `gaslevel` float NOT NULL,
  `thresholdlevel` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device`
--

CREATE TABLE `device` (
  `deviceID` varchar(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `Devicename` varchar(100) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `device`
--

INSERT INTO `device` (`deviceID`, `userID`, `Devicename`, `location`, `status`, `timestamp`) VALUES
('GS1', 1, 'Gas Sensor 1', 'Living Room', 0, '2024-11-05 21:10:01'),
('GS2', 1, 'Gas Sensor 2', 'Kitchen', 0, '2024-11-05 21:10:01');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_reading`
--

CREATE TABLE `sensor_reading` (
  `readingID` int(11) NOT NULL,
  `deviceID` varchar(11) DEFAULT NULL,
  `ppm` float NOT NULL,
  `smoke_status` tinyint(1) DEFAULT 0,
  `co_status` tinyint(1) DEFAULT 0,
  `lpg_status` tinyint(1) DEFAULT 0,
  `actionby` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `actionbytimestamp` timestamp NULL DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_reading`
--

INSERT INTO `sensor_reading` (`readingID`, `deviceID`, `ppm`, `smoke_status`, `co_status`, `lpg_status`, `actionby`, `status`, `actionbytimestamp`, `timestamp`) VALUES
(58, 'GS1', 0.95, 0, 0, 0, NULL, 0, NULL, '2024-11-05 21:10:02'),
(59, 'GS1', 0.95, 0, 0, 0, NULL, 0, NULL, '2024-11-05 21:10:05'),
-- --------------------------------------------------------

--
-- Table structure for table `thresholds`
--

CREATE TABLE `thresholds` (
  `thresholdID` int(11) NOT NULL,
  `deviceID` varchar(11) DEFAULT NULL,
  `smoke_threshold` float NOT NULL,
  `co_threshold` float NOT NULL,
  `lpg_threshold` float NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thresholds`
--

INSERT INTO `thresholds` (`thresholdID`, `deviceID`, `smoke_threshold`, `co_threshold`, `lpg_threshold`, `timestamp`) VALUES
(1, 'GS1', 5, 5, 5, '2024-11-05 21:14:03'),
(2, 'GS2', 5, 5, 5, '2024-11-05 21:14:03');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `userrole` enum('admin','user','super_user','super_admin') NOT NULL,
  `type` enum('corporate','homeowner') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `login_attempt` int(11) DEFAULT 0,
  `lockout_until` timestamp NULL DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `username`, `password`, `userrole`, `type`, `name`, `email`, `phone`, `address`, `status`, `login_attempt`, `lockout_until`, `timestamp`) VALUES
(1, 'earl', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'admin', 'corporate', 'Admin User', 'admin@example.com', '123-456-7890', '123 Admin St, City', 0, 0, NULL, '2024-11-05 19:21:00'),
(2, 'user', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'user', 'homeowner', 'Regular User', 'user@example.com', '234-567-8901', '456 User Rd, City', 0, 0, NULL, '2024-11-05 19:21:00'),
(3, 'super_user', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'super_user', 'corporate', 'Super User', 'super_user@example.com', '345-678-9012', '789 Super Ln, City', 0, 0, NULL, '2024-11-05 19:21:00'),
(4, 'super_admin', '$2y$10$ws79HXJGsifxhi2/4OWVYe3I0uCDwZ5xX9uCBbMeLRnRjRNkD3h/e', 'super_admin', 'corporate', 'Super Admin', 'super_admin@example.com', '456-789-0123', '101 Super Admin Ave, City', 0, 0, NULL, '2024-11-05 19:21:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alert`
--
ALTER TABLE `alert`
  ADD PRIMARY KEY (`alertID`),
  ADD KEY `deviceID` (`deviceID`),
  ADD KEY `readingID` (`readingID`);

--
-- Indexes for table `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`deviceID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `sensor_reading`
--
ALTER TABLE `sensor_reading`
  ADD PRIMARY KEY (`readingID`),
  ADD KEY `deviceID` (`deviceID`),
  ADD KEY `actionby` (`actionby`);

--
-- Indexes for table `thresholds`
--
ALTER TABLE `thresholds`
  ADD PRIMARY KEY (`thresholdID`),
  ADD KEY `deviceID` (`deviceID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alert`
--
ALTER TABLE `alert`
  MODIFY `alertID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor_reading`
--
ALTER TABLE `sensor_reading`
  MODIFY `readingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=656;

--
-- AUTO_INCREMENT for table `thresholds`
--
ALTER TABLE `thresholds`
  MODIFY `thresholdID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alert`
--
ALTER TABLE `alert`
  ADD CONSTRAINT `alert_ibfk_1` FOREIGN KEY (`deviceID`) REFERENCES `device` (`deviceID`) ON DELETE CASCADE,
  ADD CONSTRAINT `alert_ibfk_2` FOREIGN KEY (`readingID`) REFERENCES `sensor_reading` (`readingID`) ON DELETE CASCADE;

--
-- Constraints for table `device`
--
ALTER TABLE `device`
  ADD CONSTRAINT `device_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `sensor_reading`
--
ALTER TABLE `sensor_reading`
  ADD CONSTRAINT `sensor_reading_ibfk_1` FOREIGN KEY (`deviceID`) REFERENCES `device` (`deviceID`) ON DELETE CASCADE,
  ADD CONSTRAINT `sensor_reading_ibfk_2` FOREIGN KEY (`actionby`) REFERENCES `user` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `thresholds`
--
ALTER TABLE `thresholds`
  ADD CONSTRAINT `thresholds_ibfk_1` FOREIGN KEY (`deviceID`) REFERENCES `device` (`deviceID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

DELIMITER //

DELIMITER //

CREATE EVENT update_device_status
ON SCHEDULE EVERY 1 SECOND
DO
BEGIN
    -- Update the status of devices to 0 if no reading has been recorded in the last 10 seconds
    UPDATE device d
    SET status = 0
    WHERE d.status = 1 AND NOT EXISTS (
        SELECT 1
        FROM sensor_reading sr
        WHERE sr.deviceID = d.deviceID
        AND sr.timestamp > NOW() - INTERVAL 10 SECOND
    );

    -- Update the status of devices to 1 if there is a recent reading
    UPDATE device d
    SET status = 1
    WHERE d.status = 0 AND EXISTS (
        SELECT 1
        FROM sensor_reading sr
        WHERE sr.deviceID = d.deviceID
        AND sr.timestamp > NOW() - INTERVAL 10 SECOND
    );
END //

DELIMITER ;


SET GLOBAL event_scheduler = ON;


