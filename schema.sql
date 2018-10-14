-- phpMyAdmin SQL Dump
-- version 4.5.3.1
-- http://www.phpmyadmin.net
--
-- Host: mysql.monstrouspeace.com
-- Generation Time: Apr 08, 2016 at 01:43 AM
-- Server version: 5.6.25-log
-- PHP Version: 7.0.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mp_robin`
--

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `guid` char(36) NOT NULL,
  `room` char(10) NOT NULL,
  `tier` int(2) NOT NULL,
  `parent` char(36) DEFAULT NULL,
  `child0` char(36) DEFAULT NULL,
  `child1` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `track`
--

CREATE TABLE `track` (
  `id` int(11) NOT NULL,
  `guid` varchar(36) NOT NULL,
  `room` char(10) NOT NULL,
  `count` int(6) NOT NULL,
  `grow` int(6) NOT NULL,
  `stay` int(6) NOT NULL,
  `abandon` int(6) NOT NULL,
  `novote` int(6) NOT NULL,
  `formation` int(11) NOT NULL,
  `reap` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `track_storage`
--

CREATE TABLE `track_storage` (
  `id` int(11) NOT NULL,
  `guid` varchar(36) NOT NULL,
  `room` char(10) NOT NULL,
  `count` int(6) NOT NULL,
  `grow` int(6) NOT NULL,
  `stay` int(6) NOT NULL,
  `abandon` int(6) NOT NULL,
  `novote` int(6) NOT NULL,
  `formation` int(11) NOT NULL,
  `reap` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`guid`),
  ADD KEY `room` (`room`),
  ADD KEY `parent` (`parent`),
  ADD KEY `child0` (`child0`),
  ADD KEY `child1` (`child1`),
  ADD KEY `tier` (`tier`);

--
-- Indexes for table `track`
--
ALTER TABLE `track`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room` (`room`),
  ADD KEY `count` (`count`),
  ADD KEY `guid` (`guid`),
  ADD KEY `guid_2` (`guid`,`count`),
  ADD KEY `ip` (`ip`),
  ADD KEY `time` (`time`),
  ADD KEY `guid_3` (`guid`,`ip`),
  ADD KEY `guid_4` (`guid`,`count`,`time`),
  ADD KEY `guid_5` (`guid`,`time`);

--
-- Indexes for table `track_storage`
--
ALTER TABLE `track_storage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room` (`room`),
  ADD KEY `count` (`count`),
  ADD KEY `guid` (`guid`),
  ADD KEY `guid_2` (`guid`,`ip`),
  ADD KEY `ip` (`ip`),
  ADD KEY `time` (`time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `track`
--
ALTER TABLE `track`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `track_storage`
--
ALTER TABLE `track_storage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

