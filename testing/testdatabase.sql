-- phpMyAdmin SQL Dump
-- version 4.4.12
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2015 at 02:17 AM
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
-- Table structure for table `alias_login`
--

DROP TABLE IF EXISTS `alias_login`;
CREATE TABLE IF NOT EXISTS `alias_login` (
  `id` int(11) NOT NULL,
  `larar_id` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_mc_id` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8;

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
  `status` tinytext NOT NULL,
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
  `checked` tinytext NOT NULL,
  `updated` tinytext NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `grupper`
--

INSERT INTO `grupper` (`id`, `status`, `larar_id`, `skola`, `klass`, `elever`, `mat`, `info`, `notes`, `ltid`, `d1`, `d2`, `d3`, `d4`, `d5`, `d6`, `d7`, `d8`, `special`, `g_arskurs`, `checked`, `updated`) VALUES
(2, 'active', '2', 'råbg', 'Grupp 1 av åk2', '22', '1 glutenintolerant  och 1 ej fläskköt', '', '', '', '', '', '', '2015-09-26', '2015-09-26', '2015-10-26', '2015-11-26', '2015-12-26', '', '2/3', 'yes', '2015-08-18 16:05:48'),
(11, 'active', '47', 'råbg', 'Grupp 2', '20', 'en icke griskött', 'Leker helst vid fina sandstränder. ', '', '', '2015-07-03', '2015-07-04', '2015-09-26', '2015-10-11', '', '', '', '', '', '2/3', 'yes', '2015-07-01 14:34:07'),
(32, 'active', '32', 'pers', 'Klass 2b', '27', 'Tre av eleverna är muslimer.', '', '', '', '', '', '', '', '', '', '', '', '', '2/3', '', '2015-08-18 16:05:29'),
(47, 'active', '47', 'råbg', '4-5B', '22', 'Två allergiska mot laktos, en mot nötter, en mot gluten, en som bara äter vegetariskt (äter ägg och fisk) och en elev med väldigt speciella matbehov - äter inte "kladdig mat". Om vi får veta vad som serveras denna dag, så kan vi se om det kan vara något.', 'Tre elever är rädda för träd.', '', '', '2015-10-07', '2015-10-24', '2015-11-03', '2015-11-27', '', '', '', '', '', '5', '', '2015-08-25 21:12:59'),
(48, 'active', '47', 'råbg', '4-5A', '31', 'En allergisk mot Gluten och äggvita, en mot bara gluten, en mot kiwi och en mot stenfrukter och nötter.', 'Vi brukar inte ta raster. Så ta inga raster!', '', '', '', '', '', '', '2015-12-10', '2015-12-16', '2015-12-22', '2015-12-25', '', '5', '', '2015-08-25 21:14:07'),
(96, 'active', '96', 'råbg', '4-5d', '23', 'En allergisk mot laktos, en mot nötter, en mot stenfrukter, äpplen och päron och en mot "exotiska frukter"', '', '', '', '2015-10-16', '2015-10-24', '2015-10-30', '2015-11-12', '', '', '', '', '', '5', '', '2015-08-25 21:23:41');

-- --------------------------------------------------------

--
-- Table structure for table `kalender`
--

DROP TABLE IF EXISTS `kalender`;
CREATE TABLE IF NOT EXISTS `kalender` (
  `id` int(11) NOT NULL,
  `startdate` tinytext NOT NULL,
  `title` tinytext NOT NULL,
  `enddate` tinytext NOT NULL,
  `starttime` tinytext NOT NULL,
  `endtime` tinytext NOT NULL,
  `location` tinytext NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `kalender`
--

INSERT INTO `kalender` (`id`, `startdate`, `title`, `enddate`, `starttime`, `endtime`, `location`, `description`) VALUES
(33, '2015-09-26', 'BergLuftVatten med Råbergsskolan  (Tom T)', '2015-09-26', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: Tom Testsson\\nÅrskurs: 2/3\\nMobil: 070-123456\\nMejl: Tom.Testsson@edu.sigtuna.se\\nKlass Grupp 1 av åk2 med 22 elever\\nMatpreferenser: 1 glutenintolerant  och 1 ej fläskköt\\nAnnat: '),
(34, '2015-10-26', 'Teknikdag med Råbergsskolan  (Tom T)', '2015-10-26', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: Tom Testsson\\nÅrskurs: 2/3\\nMobil: 070-123456\\nMejl: Tom.Testsson@edu.sigtuna.se\\nKlass Grupp 1 av åk2 med 22 elever\\nMatpreferenser: 1 glutenintolerant  och 1 ej fläskköt\\nAnnat: '),
(35, '2015-11-26', 'Höstvandring med Råbergsskolan  (Tom T)', '2015-11-26', '0815', '1330', 'Skogen', 'Tid: 0815-1330\\nLärare: Tom Testsson\\nÅrskurs: 2/3\\nMobil: 070-123456\\nMejl: Tom.Testsson@edu.sigtuna.se\\nKlass Grupp 1 av åk2 med 22 elever\\nMatpreferenser: 1 glutenintolerant  och 1 ej fläskköt\\nAnnat: '),
(36, '2015-12-26', 'Finallektion med Råbergsskolan  (Tom T)', '2015-12-26', '0815', '2359', 'Skolan', 'Tid: 0815-2359\\nLärare: Tom Testsson\\nÅrskurs: 2/3\\nMobil: 070-123456\\nMejl: Tom.Testsson@edu.sigtuna.se\\nKlass Grupp 1 av åk2 med 22 elever\\nMatpreferenser: 1 glutenintolerant  och 1 ej fläskköt\\nAnnat: '),
(37, '2015-07-03', 'Rymddag med Råbergsskolan  (Tim V)', '2015-07-03', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 2/3\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass Grupp 2 med 20 elever\\nMatpreferenser: en icke griskött\\nAnnat: Leker helst vid fina sandstränder. '),
(38, '2015-07-04', 'Livslektion med Råbergsskolan  (Tim V)', '2015-07-04', '0815', '2359', 'Skolan', 'Tid: 0815-2359\\nLärare: Tim Vikarie\\nÅrskurs: 2/3\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass Grupp 2 med 20 elever\\nMatpreferenser: en icke griskött\\nAnnat: Leker helst vid fina sandstränder. '),
(39, '2015-09-26', 'Vårvandring med Råbergsskolan  (Tim V)', '2015-09-26', '0815', '1330', 'Skogen', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 2/3\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass Grupp 2 med 20 elever\\nMatpreferenser: en icke griskött\\nAnnat: Leker helst vid fina sandstränder. '),
(40, '2015-10-11', 'Forntidsdag med Råbergsskolan  (Tim V)', '2015-10-11', '0815', '1330', 'Näsudden', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 2/3\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass Grupp 2 med 20 elever\\nMatpreferenser: en icke griskött\\nAnnat: Leker helst vid fina sandstränder. '),
(41, '2015-10-07', 'Evolutionsdag med Råbergsskolan  (Tim V)', '2015-10-07', '0815', '1330', 'Garnsviken', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5B med 22 elever\\nMatpreferenser: Två allergiska mot laktos, en mot nötter, en mot gluten, en som bara äter vegetariskt (äter ägg och fisk) och en elev med väldigt speciella matbehov - äter inte "kladdig mat". Om vi får veta vad som serveras denna dag, så kan vi se om det kan vara något.\\nAnnat: Tre elever är rädda för träd.'),
(42, '2015-10-24', 'Medeltidsdag med Råbergsskolan  (Tim V)', '2015-10-24', '0815', '1330', 'Sigtuna', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5B med 22 elever\\nMatpreferenser: Två allergiska mot laktos, en mot nötter, en mot gluten, en som bara äter vegetariskt (äter ägg och fisk) och en elev med väldigt speciella matbehov - äter inte "kladdig mat". Om vi får veta vad som serveras denna dag, så kan vi se om det kan vara något.\\nAnnat: Tre elever är rädda för träd.'),
(43, '2015-11-03', 'Energidag med Råbergsskolan  (Tim V)', '2015-11-03', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5B med 22 elever\\nMatpreferenser: Två allergiska mot laktos, en mot nötter, en mot gluten, en som bara äter vegetariskt (äter ägg och fisk) och en elev med väldigt speciella matbehov - äter inte "kladdig mat". Om vi får veta vad som serveras denna dag, så kan vi se om det kan vara något.\\nAnnat: Tre elever är rädda för träd.'),
(44, '2015-11-27', 'LektionEtt med Råbergsskolan  (Tim V)', '2015-11-27', '0815', '2359', 'Skolan', 'Tid: 0815-2359\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5B med 22 elever\\nMatpreferenser: Två allergiska mot laktos, en mot nötter, en mot gluten, en som bara äter vegetariskt (äter ägg och fisk) och en elev med väldigt speciella matbehov - äter inte "kladdig mat". Om vi får veta vad som serveras denna dag, så kan vi se om det kan vara något.\\nAnnat: Tre elever är rädda för träd.'),
(45, '2015-12-10', 'Vintervandring med Råbergsskolan  (Tim V)', '2015-12-10', '0815', '1330', 'Skogen', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5A med 22 elever\\nMatpreferenser: En allergisk mot Gluten och äggvita, en mot bara gluten, en mot kiwi och en mot stenfrukter och nötter.\\nAnnat: Vi brukar inte ta raster. Så ta inga raster!'),
(46, '2015-12-16', 'LektionTvå med Råbergsskolan  (Tim V)', '2015-12-16', '0815', '2359', 'Skogen', 'Tid: 0815-2359\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5A med 22 elever\\nMatpreferenser: En allergisk mot Gluten och äggvita, en mot bara gluten, en mot kiwi och en mot stenfrukter och nötter.\\nAnnat: Vi brukar inte ta raster. Så ta inga raster!'),
(47, '2015-12-22', 'Kemidag med Råbergsskolan  (Tim V)', '2015-12-22', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5A med 22 elever\\nMatpreferenser: En allergisk mot Gluten och äggvita, en mot bara gluten, en mot kiwi och en mot stenfrukter och nötter.\\nAnnat: Vi brukar inte ta raster. Så ta inga raster!'),
(48, '2015-12-25', 'Supermänniska med Råbergsskolan  (Tim V)', '2015-12-25', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: Tim Vikarie\\nÅrskurs: 5\\nMobil: 070-222222\\nMejl: tim.vikarie@edu.sigtuna.se\\nKlass 4-5A med 22 elever\\nMatpreferenser: En allergisk mot Gluten och äggvita, en mot bara gluten, en mot kiwi och en mot stenfrukter och nötter.\\nAnnat: Vi brukar inte ta raster. Så ta inga raster!'),
(49, '2015-10-16', 'Evolutionsdag med Råbergsskolan  (David T)', '2015-10-16', '0815', '1330', 'Garnsviken', 'Tid: 0815-1330\\nLärare: David Testson\\nÅrskurs: 5\\nMobil: 070-444444\\nMejl: david.testson@edu.sigtuna.se\\nKlass 4-5d med 23 elever\\nMatpreferenser: En allergisk mot laktos, en mot nötter, en mot stenfrukter, äpplen och päron och en mot "exotiska frukter"\\nAnnat: '),
(50, '2015-10-24', 'Medeltidsdag med Råbergsskolan  (David T)', '2015-10-24', '0815', '1330', 'Sigtuna', 'Tid: 0815-1330\\nLärare: David Testson\\nÅrskurs: 5\\nMobil: 070-444444\\nMejl: david.testson@edu.sigtuna.se\\nKlass 4-5d med 23 elever\\nMatpreferenser: En allergisk mot laktos, en mot nötter, en mot stenfrukter, äpplen och päron och en mot "exotiska frukter"\\nAnnat: '),
(51, '2015-10-30', 'Energidag med Råbergsskolan  (David T)', '2015-10-30', '0815', '1330', 'Flottvik', 'Tid: 0815-1330\\nLärare: David Testson\\nÅrskurs: 5\\nMobil: 070-444444\\nMejl: david.testson@edu.sigtuna.se\\nKlass 4-5d med 23 elever\\nMatpreferenser: En allergisk mot laktos, en mot nötter, en mot stenfrukter, äpplen och päron och en mot "exotiska frukter"\\nAnnat: '),
(52, '2015-11-12', 'LektionEtt med Råbergsskolan  (David T)', '2015-11-12', '0815', '2359', 'Skolan', 'Tid: 0815-2359\\nLärare: David Testson\\nÅrskurs: 5\\nMobil: 070-444444\\nMejl: david.testson@edu.sigtuna.se\\nKlass 4-5d med 23 elever\\nMatpreferenser: En allergisk mot laktos, en mot nötter, en mot stenfrukter, äpplen och päron och en mot "exotiska frukter"\\nAnnat: ');

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
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `larare`
--

INSERT INTO `larare` (`id`, `mailchimp_id`, `status`, `email`, `fname`, `lname`, `skola`, `mobil`, `notes`, `verified`, `rektor`, `updated`) VALUES
(2, 'fA3n3v7e', 'subscribed', 'Tom.Testsson@edu.sigtuna.se', 'Tom', 'Testsson', 'råbg', '070-123456', '', 'true', '', '2015-08-18 16:05:48'),
(11, '8Pi4UGJy', 'archived', 'tanja.testsson@edu.sigtuna.se', 'Tanja', 'Testsson', 'råbg', '070-87654321', '', 'true', '', '2015-07-01 14:34:07'),
(32, 'JYV519aw', 'subscribed', 'anna.testsson@edu.sigtuna.se', 'Anna', 'Testsson-Mockupsson', 'pers', '070-111111', '', 'true', '', '2015-08-18 16:05:29'),
(47, 'lCt4qY9K', 'subscribed', 'tim.vikarie@edu.sigtuna.se', 'Tim', 'Vikarie', 'råbg', '070-222222', '', 'true', '', '2015-08-25 21:12:59'),
(48, '5D55o7Zr', 'subscribed', 'cecilia.testson@edu.sigtuna.se', 'Cecilia', 'Testsson', 'råbg', '070333333', '', 'true', '', '2015-08-25 21:14:07'),
(96, 'oRI5a50n', 'subscribed', 'david.testson@edu.sigtuna.se', 'David', 'Testson', 'råbg', '070-444444', '', 'true', '', '2015-08-25 21:23:41'),
(104, '28nc6Y1G', 'subscribed', 'erik.testson@edu.sigtuna.se', 'Erik', 'Testson', 'råbg', '070-555555', '', 'true', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `rektorer`
--

DROP TABLE IF EXISTS `rektorer`;
CREATE TABLE IF NOT EXISTS `rektorer` (
  `id` int(11) NOT NULL,
  `mailchimp_id` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `fname` tinytext NOT NULL,
  `lname` tinytext NOT NULL,
  `skola` tinytext NOT NULL,
  `notes` tinytext NOT NULL,
  `registered` tinytext NOT NULL,
  `special` tinytext NOT NULL,
  `updated` tinytext NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `rektorer`
--

INSERT INTO `rektorer` (`id`, `mailchimp_id`, `email`, `fname`, `lname`, `skola`, `notes`, `registered`, `special`, `updated`) VALUES
(1, '724b5baf04', 'merja.olkinuora@Sigtuna.se', 'Merja', 'Olkinuora', 'Norrbacka', '', 'ja', '', '2015-04-17 12:10:20:3.247'),
(2, '01b6688d01', 'gunilla.ordell@sigtuna.se', 'Gunilla', 'Ordell', 'Råbergs', '', 'nej', '', '2015-04-17 12:10:20:3.293'),
(3, '45c83c5a60', 'Christina.Carlsson@sigtuna.se', 'Christina', 'Carlsson', 'Central', '', 'nej', '', '2015-04-28 10:58:10:0.266'),
(4, 'fa55c9bebc', 'tommy.olsson@sigtuna.se', 'Tommy', 'Olsson', 'Ekilla', '', 'ja', '', ''),
(5, 'd637a81d14', 'tina.haggholm@varingaskolan.se', 'Tina', 'Haggholm', 'Väringa', '', 'nej', '', '2015-04-17 12:10:20:1.559'),
(6, '60d63fbed7', 'Marianne.Lofgren@sigtuna.se', 'Marianne', 'Löfgren', 'S:ta Gertruds', '', 'ja', '', '2015-04-17 12:24:02:2.855'),
(7, 'd6612ebd4d', 'johan.johanson@sigtuna.se', 'Johan', 'Johanson', 'Saga', '', 'nej', '', '2015-04-17 12:07:39:2.097'),
(9, 'bfd221a0bc', 'asa.lomakka@sigtuna.se', 'Åsa', 'Lomakka', 'Valsta', '', 'ja', '', ''),
(10, '624a0e27a5', 'Carola.Sjodin@sigtuna.se', 'Carola', 'Sjödin', 'Steningehöjdens', '', 'ja', '', '2015-04-26 22:15:47:7.885'),
(11, 'deba0c1588', 'helena.eriksson@sigtuna.se', 'Helena', 'Eriksson', 'Tingvalla', '', 'nej', '', '2015-04-17 12:19:35:5.417'),
(12, '7727edfab5', 'Anneli.Hultin@sigtuna.se', 'Anneli', 'Hultin', 'Sätuna', '', 'ja', '', '2015-04-17 12:20:49:9.655'),
(13, '4a125bde57', 'camilla.uddman@sigtuna.se', 'Camilla', 'Uddman', 'Skepptuna+Odensala', 'Förvaltar även Skepptuna', 'ja', '', '2015-04-23 15:45:41:2.918'),
(14, '2e5908ccd9', 'margret.benedikz@sshl.se', 'Margret', 'Benedikz', 'SSHL', '0', 'ja', '', ''),
(15, '6839aa54c9', 'helena.gullberg@sigtuna.se', 'Helena', 'Gullberg', 'S:t Olofs', '', 'ja', '', ''),
(16, '245e030c9f', 'Rose-Marie.Hoglund@sigtuna.se', 'Rose-Marie', 'Höglund', 'Steninge', '', 'ja', '', '2015-04-17 12:10:20:2.118'),
(17, '32353aa93e', 'helena.jekellking@josefinaskolan.nu', 'Helena', 'Jekell-King', 'Josefina', '', 'ja', '', '2015-04-26 22:15:47:8.850'),
(18, '71e5387dfb', 'magnus@skolanbergius.se', 'Magnus', 'Lindqvist', 'Bergius', '', 'ja', '', ''),
(19, '031f220455', 'f@hehl.se', 'Marit', 'Jansson', 'S:t Pers', '', 'ja', '', ''),
(20, 'afd44bc72d', 'mattias.ramberg@sigtuna.se', 'Mattias', 'Ramberg', 'Edda', '', 'ja', '', '2015-04-17 12:25:14:5.009'),
(21, '4704a3dd33', 'dummy-1@sigtuna.se', 'Helena', 'Eriksson', 'Central', '', 'nej', '', '2015-04-15 11:54:44:5.321'),
(22, 'a1497493c9', 'marit.jansson@sigtuna.se', 'Marit', 'Jansson', 'S:t Pers', '', 'ja', '', '2015-04-28 11:14:37:7.395'),
(23, 'bba020b9d0', 'dummy@dummyadress.se', 'dummysson', 'Remove Me soon...', 'SSHL', '', 'nej', '', '2015-04-15 11:59:24:4.912'),
(24, '008234afb9', 'ylva.hultblad-hederfors@sigtuna.se', 'Ylva', 'Hultblad Hederfors', 'Valsta', '', 'ja', '', '2015-04-26 22:14:41:2.463');

-- --------------------------------------------------------

--
-- Table structure for table `skolor`
--

DROP TABLE IF EXISTS `skolor`;
CREATE TABLE IF NOT EXISTS `skolor` (
  `id` int(11) NOT NULL,
  `short_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `long_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupper_ak2` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupper_ak5` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lon` tinytext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skolor`
--

INSERT INTO `skolor` (`id`, `short_name`, `long_name`, `grupper_ak2`, `grupper_ak5`, `lat`, `lon`) VALUES
(1, 'berg', 'Bergius ', '', '', '59.620758', '17.857049'),
(2, 'cent', 'Centralskolan ', '', '', '59.623361', '17.854833'),
(3, 'edda', 'Eddaskolan ', '', '', '59.61297', '17.826379'),
(4, 'ekil', 'Ekillaskolan ', '', '', '59.625404', '17.846261'),
(5, 'jose', 'Josefinaskolan ', '', '', '59.628129', '17.782695'),
(6, 'norr', 'Norrbackaskolan ', '', '', '59.634418', '17.851555'),
(7, 'oden', 'Odensala skola ', '', '', '59.665704', '17.845369'),
(8, 'råbg', 'Råbergsskolan ', '', '', '59.579675', '17.890483'),
(9, 'olof', 'S:t Olofs skola ', '', '', '59.621024', '17.724982'),
(10, 'pers', 'S:t Pers skola ', '', '', '59.61553', '17.716579'),
(11, 'gert', 'S:ta Gertruds skola ', '', '', '59.623818', '17.751047'),
(12, 'saga', 'Sagaskolan', '', '', '59.619174', '17.829329'),
(13, 'sshl', 'Sigtunaskolan Humanistiska Läroverket ', '0', '0', '59.615083', '17.709029'),
(14, 'skep', 'Skepptuna skola ', '', '', '59.70681', '18.111307'),
(15, 'shöj', 'Steningehöjdens skola ', '', '', '59.625468', '17.795426'),
(16, 'sten', 'Steningeskolan Orion och Galaxen ', '', '', '59.612196', '17.811947'),
(17, 'sätu', 'Sätunaskolan ', '', '', '59.631093', '17.85645'),
(18, 'ting', 'Tingvallaskolan ', '', '', '59.626631', '17.828393'),
(19, 'vals', 'Valstaskolan ', '0', '3', '59.61706', '17.828409'),
(20, 'väri', 'Väringaskolan ', '', '', '59.622441', '17.725464'),
(21, 'gsär', 'Grundsärskolan', '0', '0', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alias_login`
--
ALTER TABLE `alias_login`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `arbetsfordelning`
--
ALTER TABLE `arbetsfordelning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grupper`
--
ALTER TABLE `grupper`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `kalender`
--
ALTER TABLE `kalender`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_2` (`id`),
  ADD KEY `id` (`id`),
  ADD KEY `id_3` (`id`);

--
-- Indexes for table `larare`
--
ALTER TABLE `larare`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `rektorer`
--
ALTER TABLE `rektorer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `skolor`
--
ALTER TABLE `skolor`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alias_login`
--
ALTER TABLE `alias_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `arbetsfordelning`
--
ALTER TABLE `arbetsfordelning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=116;
--
-- AUTO_INCREMENT for table `grupper`
--
ALTER TABLE `grupper`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=97;
--
-- AUTO_INCREMENT for table `kalender`
--
ALTER TABLE `kalender`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=53;
--
-- AUTO_INCREMENT for table `larare`
--
ALTER TABLE `larare`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=105;
--
-- AUTO_INCREMENT for table `rektorer`
--
ALTER TABLE `rektorer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `skolor`
--
ALTER TABLE `skolor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=22;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
