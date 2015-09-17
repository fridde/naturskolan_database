-- phpMyAdmin SQL Dump
-- version 4.4.12
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2015 at 11:56 PM
-- Server version: 5.6.25
-- PHP Version: 5.6.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `testdatabase`
--

-- --------------------------------------------------------

--
-- Table structure for table `arbetsfordelning`
--

DROP TABLE IF EXISTS `arbetsfordelning`;
CREATE TABLE IF NOT EXISTS `arbetsfordelning` (
  `id` int(11) NOT NULL,
  `dag` tinytext NOT NULL,
  `aktivitet` tinytext NOT NULL,
  `personal` tinytext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `arbetsfordelning`
--

INSERT INTO `arbetsfordelning` (`id`, `dag`, `aktivitet`, `personal`) VALUES
(115, '2015-08-24', 'BergLuftVatten med Josefina (Tommy L)', 'F+J+L+M+P');

-- --------------------------------------------------------

--
-- Table structure for table `grupper`
--

DROP TABLE IF EXISTS `grupper`;
CREATE TABLE IF NOT EXISTS `grupper` (
  `id` int(11) NOT NULL,
  `larar_id` tinytext NOT NULL,
  `skola` tinytext NOT NULL,
  `klass` tinytext NOT NULL,
  `elever` tinytext NOT NULL,
  `mat` text NOT NULL,
  `info` text NOT NULL,
  `notes` text NOT NULL,
  `ltid` tinytext NOT NULL,
  `d1` tinytext NOT NULL,
  `d2` tinytext NOT NULL,
  `d3` tinytext NOT NULL,
  `d4` tinytext NOT NULL,
  `d5` tinytext NOT NULL,
  `d6` tinytext NOT NULL,
  `d7` tinytext NOT NULL,
  `d8` tinytext NOT NULL,
  `special` text NOT NULL,
  `g_arskurs` tinytext NOT NULL,
  `updated` tinytext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `grupper`
--

INSERT INTO `grupper` (`id`, `larar_id`, `skola`, `klass`, `elever`, `mat`, `info`, `notes`, `ltid`, `d1`, `d2`, `d3`, `d4`, `d5`, `d6`, `d7`, `d8`, `special`, `g_arskurs`, `updated`) VALUES
(2, '2', 'råbg', 'Grupp 1 av åk2', '18', '1 glutenintolerant  och 1 ej fläskkött', '', '', '', '', '', '', '', '2015-09-26', '2015-10-26', '2015-11-26', '2015-12-26', '', '2/3', '2015-08-18 16:05:48'),
(11, '11', 'råbg', '2', '18', 'en icke griskött', '', '', '', '', '', '', '', '', '', '', '', '', '2/3', '2015-07-01 14:34:07'),
(32, '32', 'stpe', 'Klass 2b', '27', 'Tre av eleverna är muslimer.', '', '', '', '', '', '', '', '', '', '', '', '', '2/3', '2015-08-18 16:05:29'),
(47, '47', 'råbg', '4-5B', '22', 'Två allergiska mot laktos, en mot nötter, en mot gluten, en som bara äter vegetariskt (äter ägg och fisk) och en elev med väldigt speciella matbehov - äter inte "kladdig mat". Om vi får veta vad som serveras denna dag, så kan vi se om det kan vara något.', '', '', '', '', '', '', '', '', '', '', '', '', '5', '2015-08-25 21:12:59'),
(48, '47', 'råbg', '4-5A', '22', 'En allergisk mot Gluten och äggvita, en mot bara gluten, en mot kiwi och en mot stenfrukter och nötter.', '', '', '', '', '', '', '', '', '', '', '', '', '5', '2015-08-25 21:14:07'),
(96, '96', 'råbg', '4-5d', '23', 'En allergisk mot laktos, en mot nötter, en mot stenfrukter, äpplen och päron och en mot "exotiska frukter"', '', '', '', '', '', '', '', '', '', '', '', '', '5', '2015-08-25 21:23:41');

-- --------------------------------------------------------

--
-- Table structure for table `kalender`
--

DROP TABLE IF EXISTS `kalender`;
CREATE TABLE IF NOT EXISTS `kalender` (
  `id` int(11) NOT NULL,
  `mailchimp_id` tinytext NOT NULL,
  `startdate` tinytext NOT NULL,
  `title` tinytext NOT NULL,
  `enddate` tinytext NOT NULL,
  `starttime` tinytext NOT NULL,
  `endtime` tinytext NOT NULL,
  `location` tinytext NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `kalender`
--

INSERT INTO `kalender` (`id`, `mailchimp_id`, `startdate`, `title`, `enddate`, `starttime`, `endtime`, `location`, `description`) VALUES
(12906, 'wuiydiuyi3uy2', '2015-12-03', 'BergLuftVatten med Saga (Anna)', '2015-12-03', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: Anna Nybling\\nÅrskurs: 2/3\\nMobil: \\nMejl: tom.testsson@edu.sigtuna.se\\nK ');

-- --------------------------------------------------------

--
-- Table structure for table `larare`
--

DROP TABLE IF EXISTS `larare`;
CREATE TABLE IF NOT EXISTS `larare` (
  `id` int(11) NOT NULL,
  `mailchimp_id` tinytext NOT NULL,
  `status` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `fname` tinytext NOT NULL,
  `lname` tinytext NOT NULL,
  `skola` tinytext NOT NULL,
  `mobil` tinytext NOT NULL,
  `notes` text NOT NULL,
  `verified` tinytext NOT NULL,
  `rektor` tinytext NOT NULL,
  `updated` tinytext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `larare`
--

INSERT INTO `larare` (`id`, `mailchimp_id`, `status`, `email`, `fname`, `lname`, `skola`, `mobil`, `notes`, `verified`, `rektor`, `updated`) VALUES
(2, 'fA3n3v7e', 'subscribed', 'Tom.Testsson@edu.sigtuna.se', 'Tom', 'Testsson', 'råbg', '070-123456', '', 'true', '', '2015-08-18 16:05:48'),
(11, '8Pi4UGJy', 'archived', 'tanja.testsson@edu.sigtuna.se', 'Tanja', 'Testsson', 'råbg', '070-87654321', '', 'true', '', '2015-07-01 14:34:07'),
(32, 'JYV519aw', 'subscribed', 'anna.testsson@edu.sigtuna.se', 'Anna', 'Testsson-Mockupsson', 'stpe', '070-111111', '', 'true', '', '2015-08-18 16:05:29'),
(47, 'lCt4qY9K', 'subscribed', 'bjorn.testsson@edu.sigtuna.se', 'Björn', 'Testsson', 'råbg', '070-222222', '', 'true', '', '2015-08-25 21:12:59'),
(48, '5D55o7Zr', 'subscribed', 'cecilia.testson@edu.sigtuna.se', 'Cecilia', 'Testsson', 'råbg', '070333333', '', 'true', '', '2015-08-25 21:14:07'),
(96, 'oRI5a50n', 'subscribed', 'david.testson@edu.sigtuna.se', 'David', 'Testson', 'råbg', '070-444444', '', 'true', '', '2015-08-25 21:23:41'),
(104, '28nc6Y1G', 'subscribed', 'erik.testson@edu.sigtuna.se', 'Erik', 'Testson', 'råbg', '070-555555', '', 'true', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `skolor`
--

DROP TABLE IF EXISTS `skolor`;
CREATE TABLE IF NOT EXISTS `skolor` (
  `id` int(11) NOT NULL,
  `long_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupper_ak2` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupper_ak5` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lon` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skolor`
--

INSERT INTO `skolor` (`id`, `long_name`, `short_name`, `grupper_ak2`, `grupper_ak5`, `lat`, `lon`) VALUES
(1, 'Bergius ', 'berg', '', '', '59.620758', '17.857049'),
(2, 'Centralskolan ', 'cent', '', '', '59.623361', '17.854833'),
(3, 'Eddaskolan ', 'edda', '', '', '59.61297', '17.826379'),
(4, 'Ekillaskolan ', 'ekil', '', '', '59.625404', '17.846261'),
(5, 'Josefinaskolan ', 'jose', '', '', '59.628129', '17.782695'),
(6, 'Norrbackaskolan ', 'norr', '', '', '59.634418', '17.851555'),
(7, 'Odensala skola ', 'oden', '', '', '59.665704', '17.845369'),
(8, 'Råbergsskolan ', 'råbg', '', '', '59.579675', '17.890483'),
(9, 'S:t Olofs skola ', 'olof', '', '', '59.621024', '17.724982'),
(10, 'S:t Pers skola ', 'pers', '', '', '59.61553', '17.716579'),
(11, 'S:ta Gertruds skola ', 'gert', '', '', '59.623818', '17.751047'),
(12, 'Sagaskolan', 'saga', '', '', '59.619174', '17.829329'),
(13, 'Sigtunaskolan Humanistiska Läroverket ', 'sshl', '', '', '59.615083', '17.709029'),
(14, 'Skepptuna skola ', 'skep', '', '', '59.70681', '18.111307'),
(15, 'Steningehöjdens skola ', 'shöj', '', '', '59.625468', '17.795426'),
(16, 'Steningeskolan Orion och Galaxen ', 'sten', '', '', '59.612196', '17.811947'),
(17, 'Sätunaskolan ', 'sätu', '', '', '59.631093', '17.85645'),
(18, 'Tingvallaskolan ', 'ting', '', '', '59.626631', '17.828393'),
(19, 'Valstaskolan ', 'vals', '', '', '59.61706', '17.828409'),
(20, 'Väringaskolan ', 'väri', '', '', '59.622441', '17.725464'),
(21, 'Grundsärskolan', 'gsär', '', '', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `grupper`
--
ALTER TABLE `grupper`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `larare`
--
ALTER TABLE `larare`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `skolor`
--
ALTER TABLE `skolor`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `skolor`
--
ALTER TABLE `skolor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
