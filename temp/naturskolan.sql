-- Created at 16.5.2016 9:19 using David Grudl MySQL Dump Utility
-- Host: localhost
-- MySQL Server: 5.6.25
-- Database: naturskolan

SET NAMES utf8;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS=0;
-- --------------------------------------------------------

DROP TABLE IF EXISTS `busstrips`;

CREATE TABLE `busstrips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `User` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `School` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Grade` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `NumberStudents` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Food` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Info` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Notes` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `IsActive` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `groups` (`id`, `Name`, `User`, `School`, `Grade`, `NumberStudents`, `Food`, `Info`, `Notes`, `IsActive`) VALUES
(1,	'Grupp grön',	'1',	'saga',	'2',	'23',	'ej halal',	'en elev som stjäl',	'bästa gruppen nånsin',	'true'),
(2,	'Grupp röd',	'2',	'saga',	'2',	'21',	'endast rått kött',	'alla är trevliga',	'svarthårig elev har luktande fötter',	'true'),
(3,	'Galna gänget',	'3',	'central',	'2',	'28',	'1 elev som inte tål veteöl',	'',	'reagerar starkt på ordet \"ni\"',	'true'),
(4,	'2a',	'4',	'råbg',	'2',	'22',	'äter endast knäckebröd',	'',	'',	'true'),
(5,	'2b',	'5',	'råbg',	'2',	'25',	'2 elever som dricker och äter mjölk',	'kan allt om rymden',	'',	'true'),
(6,	'2c',	'6',	'råbg',	'2',	'26',	'gärna oliver och pizzasallad',	'många elever rädda för skogen',	'',	'true'),
(7,	'Tändstickorna',	'1',	'väri',	'5',	'32',	'mera potatis. 10 elever äter kosher',	'har aldrig sett en räv',	'',	'true'),
(8,	'Del 1 av 5:an',	'2',	'pers',	'5',	'54',	'torkade tomater och kaviar, tack!',	'en av lärarna sitter i rullstol',	'',	'false'),
(9,	'Del 2 av 5:an',	'3',	'pers',	'5',	'18',	'ej under 27%',	'bussen avgår redan kl 11.20',	'ska inte bjudas igen',	'true'),
(10,	'FBK de äldre',	'4',	'vals',	'fbk16',	'15',	'> 1700kcal',	'2 elever vill gärna äta rävkött',	'be skolan att öka antal lärare',	'true');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `locations`;

CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Coordinates` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `locations` (`id`, `Name`, `Coordinates`) VALUES
(1,	'Flottvik',	'59.605797,17.768605'),
(2,	'Skogen',	'59.626083,17.771278'),
(3,	'Näsudden',	'59.600585,17.767853'),
(4,	'Garnsviken',	'59.621426,17.734659'),
(5,	'Konsthall Märsta',	'59.617297,17.723661'),
(6,	'Museum Sigtuna',	'59.617297,17.723661'),
(7,	'Skolan',	''),
(8,	'Annan Plats',	'');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `passwords`;

CREATE TABLE `passwords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `schools`;

CREATE TABLE `schools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ShortName` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `LongName` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsAk2` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsAk5` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsFbk16` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsFbk79` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Coordinates` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `VisitOrder` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `schools` (`id`, `ShortName`, `LongName`, `GroupsAk2`, `GroupsAk5`, `GroupsFbk16`, `GroupsFbk79`, `Coordinates`, `VisitOrder`) VALUES
(1,	'berg',	'Bergius',	'0',	'0',	'0',	'0',	'59.620758,17.857049',	'1'),
(2,	'cent',	'Centralskolan',	'1',	'2',	'0',	'0',	'59.623361,17.854833',	'2'),
(3,	'edda',	'Eddaskolan',	'2',	'0',	'0',	'0',	'59.61297,17.826379',	'3'),
(4,	'ekil',	'Ekillaskolan',	'0',	'0',	'0',	'0',	'59.625404,17.846261',	'4'),
(5,	'jose',	'Josefinaskolan',	'1',	'1',	'0',	'0',	'59.628129,17.782695',	'5'),
(6,	'norr',	'Norrbackaskolan',	'2',	'2',	'0',	'0',	'59.634418,17.851555',	'6'),
(7,	'oden',	'Odensala skola',	'1',	'1',	'0',	'0',	'59.665704,17.845369',	'7'),
(8,	'råbg',	'Råbergsskolan',	'2',	'3',	'0',	'0',	'59.579675,17.890483',	'8'),
(9,	'olof',	'S:t Olofs skola',	'0',	'0',	'0',	'0',	'59.621024,17.724982',	'9'),
(10,	'pers',	'S:t Pers skola',	'2',	'2',	'0',	'0',	'59.61553,17.716579',	'10'),
(11,	'gert',	'S:ta Gertruds skola',	'3',	'2',	'0',	'0',	'59.623818,17.751047',	'11'),
(12,	'saga',	'Sagaskolan',	'2',	'0',	'0',	'0',	'59.619174,17.829329',	'12'),
(13,	'sshl',	'Sigtunaskolan Humanistiska Läroverket',	'0',	'0',	'0',	'0',	'59.615083,17.709029',	'13'),
(14,	'skep',	'Skepptuna skola',	'1',	'1',	'0',	'0',	'59.70681,18.111307',	'14'),
(15,	'shöj',	'Steningehöjdens skola',	'2',	'1',	'0',	'0',	'59.625468,17.795426',	'15'),
(16,	'gala',	'Galaxskolan',	'2',	'2',	'0',	'0',	'59.612196,17.811947',	'16'),
(17,	'sätu',	'Sätunaskolan',	'2',	'2',	'0',	'0',	'59.631093,17.85645',	'17'),
(18,	'ting',	'Tingvallaskolan',	'1',	'1',	'0',	'0',	'59.626631,17.828393',	'18'),
(19,	'vals',	'Valstaskolan',	'0',	'3',	'0',	'0',	'59.61706,17.828409',	'19'),
(20,	'väri',	'Väringaskolan',	'1',	'1',	'0',	'0',	'59.622441,17.725464',	'20'),
(21,	'gsär',	'Grundsärskolan',	'0',	'0',	'0',	'0',	'',	'21'),
(22,	'anna',	'Annan skola',	'0',	'0',	'0',	'0',	'',	'22'),
(23,	'natu',	'Naturskolan',	'0',	'0',	'0',	'0',	'59.605797,17.768605',	'23');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `topics`;

CREATE TABLE `topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Grade` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `VisitOrder` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `ShortName` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `LongName` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Location` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Url` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `topics` (`id`, `Grade`, `VisitOrder`, `ShortName`, `LongName`, `Location`, `Mat`, `Url`) VALUES
(1,	'2',	'1',	'Universum',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/1'),
(2,	'2',	'2',	'Vårvandring',	'Liv',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/2'),
(3,	'2',	'3',	'Forntidsdag',	'Människor',	'3',	'Pastasallad',	'http://www.sigtunanaturskola.se/db/topic/3'),
(4,	'2',	'4',	'BergLuftVatten',	'Berg, luft och vatten',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/4'),
(5,	'2',	'5',	'Teknikdag',	'Teknik',	'5',	'Plats i matsalen',	'http://www.sigtunanaturskola.se/db/topic/5'),
(6,	'2',	'6',	'Höstvandring',	'Liv',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/6'),
(7,	'2',	'7',	'Finallektion',	'Avslutning',	'7',	'',	'http://www.sigtunanaturskola.se/db/topic/7'),
(8,	'5',	'1',	'Evolutionsdag',	'Evolution',	'4',	'Mackor & Frukt',	'http://www.sigtunanaturskola.se/db/topic/8'),
(9,	'5',	'2',	'Medeltidsdag',	'',	'6',	'Plats i matsalen',	'http://www.sigtunanaturskola.se/db/topic/9'),
(10,	'5',	'3',	'Energidag',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/10'),
(11,	'5',	'4',	'Vintervandring',	'',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/11'),
(12,	'5',	'5',	'Kemidag',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/12'),
(13,	'5',	'6',	'Supermänniska',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/13'),
(14,	'fbk',	'1',	'Matlagning',	'',	'2',	'Gryttillbehör',	'http://www.sigtunanaturskola.se/db/topic/14'),
(15,	'fbk',	'2',	'Fiske',	'',	'8',	'',	'http://www.sigtunanaturskola.se/db/topic/15'),
(16,	'fbk',	'3',	'Hantverk 1',	'',	'3',	'',	'http://www.sigtunanaturskola.se/db/topic/16'),
(17,	'fbk',	'4',	'Skogsvandring',	'',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/17'),
(18,	'fbk',	'5',	'Experiment',	'',	'1',	'',	'http://www.sigtunanaturskola.se/db/topic/18'),
(19,	'fbk',	'6',	'Hantverk 2',	'',	'3',	'',	'http://www.sigtunanaturskola.se/db/topic/19');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Mailchimp` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `FirstName` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `LastName` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mobil` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mail` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `School` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `IsRektor` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `Mailchimp`, `FirstName`, `LastName`, `Mobil`, `Mail`, `School`, `IsRektor`, `Password`) VALUES
(1,	'',	'Friedrich',	'Hehl',	'+46-736665275',	'friedrich.hehl@sigtuna.se',	'natu',	'false',	'abcd1234'),
(2,	'',	'Jan-Erik',	'Haggarsson',	'+46-70-3897485',	'jan-erik.haggarsson@sigtuna.se',	'natu',	'false',	'bcde2345'),
(3,	'',	'Ludvig',	'Wellander',	'+46-70-3471533',	'skafferiet@edu.sigtuna.se',	'natu',	'false',	'cdef3456'),
(4,	'',	'Marta',	'Larraona-Puy',	'0736665274',	'marta.larraona-puy@sigtuna.se',	'natu',	'false',	'defg4567'),
(5,	'',	'Per',	'Snöbohm',	'0706552365',	'per.snobohm@sigtuna.se',	'natu',	'true',	'efgh5678'),
(6,	'',	'Martin',	'Martinsson',	'08-123456',	'martin.martinsson@sigtuna.se',	'natu',	'false',	'fghi6789');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Group` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `User` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Date` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Topic` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Colleague` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Confirmed` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visits` (`id`, `Group`, `User`, `Date`, `Topic`, `Colleague`, `Confirmed`) VALUES
(1,	'1 ',	'7',	'2016-07-22',	'1',	'1',	'false'),
(2,	'2 ',	'6',	'2016-08-12',	'2',	'2',	'false'),
(3,	'3 ',	'5',	'2016-08-02',	'3',	'3',	'false'),
(4,	'4 ',	'4',	'2016-07-20',	'4',	'4',	'false'),
(5,	'5 ',	'3',	'2016-06-19',	'5',	'5',	'false'),
(6,	'6 ',	'2',	'2016-07-26',	'6',	'6',	'false'),
(7,	'7 ',	'1',	'2016-06-02',	'7',	'7',	'false'),
(8,	'8 ',	'7',	'2016-06-10',	'8',	'1',	'false'),
(9,	'9 ',	'5',	'2016-06-06',	'9',	'2',	'false'),
(10,	'10',	'3',	'2016-06-17',	'10',	'3',	'false'),
(11,	'1',	'1',	'2016-07-30',	'11',	'4',	'false'),
(12,	'2',	'2',	'2016-07-31',	'12',	'5',	'false'),
(13,	'3',	'4',	'2016-08-16',	'13',	'6',	'false'),
(14,	'4',	'6',	'2016-07-18',	'14',	'7',	'false'),
(15,	'5',	'2',	'2016-07-07',	'15',	'1',	'false');


-- THE END
