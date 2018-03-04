-- Find the exported database at D:\Dropbox\scripts\naturskolan_database\misc\SQL_data\naturskolan_export_default_2018-03-04_125637.sql. Don't forget to add users_colleagues.sql
-- Created at 4.3.2018 12:56 using David Grudl MySQL Dump Utility
-- MySQL Server: 5.5.5-10.1.19-MariaDB
-- Database: naturskolan

SET NAMES utf8;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS=0;
-- --------------------------------------------------------

DROP TABLE IF EXISTS `changes`;

CREATE TABLE `changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Type` int(11) NOT NULL,
  `EntityClass` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `EntityId` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Property` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OldValue` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Processed` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Timestamp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `colleagues_visits`;

CREATE TABLE `colleagues_visits` (
  `visit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`visit_id`,`user_id`),
  KEY `IDX_B4F058775FA0FF2` (`visit_id`),
  KEY `IDX_B4F0587A76ED395` (`user_id`),
  CONSTRAINT `FK_B4F058775FA0FF2` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B4F0587A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `cookies`;

CREATE TABLE `cookies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Rights` int(11) NOT NULL,
  `CreatedAt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `School_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF55EBDC41DBC54D` (`School_id`),
  CONSTRAINT `FK_BF55EBDC41DBC54D` FOREIGN KEY (`School_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `StartDate` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `StartTime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndDate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndTime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Description` longtext COLLATE utf8_unicode_ci,
  `Location` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Grade` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartYear` int(11) DEFAULT NULL,
  `NumberStudents` int(11) DEFAULT NULL,
  `Food` longtext COLLATE utf8_unicode_ci,
  `Info` longtext COLLATE utf8_unicode_ci,
  `Notes` longtext COLLATE utf8_unicode_ci,
  `Status` int(11) NOT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CreatedAt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `User_id` int(11) DEFAULT NULL,
  `School_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F06D397068D3EA09` (`User_id`),
  KEY `IDX_F06D397041DBC54D` (`School_id`),
  CONSTRAINT `FK_F06D397041DBC54D` FOREIGN KEY (`School_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `FK_F06D397068D3EA09` FOREIGN KEY (`User_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `locations`;

CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Coordinates` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BusId` int(11) NOT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_17E64ABA85232B9E` (`BusId`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `locations` (`id`, `Name`, `Coordinates`, `Description`, `BusId`, `LastChange`) VALUES
(1,	'Annan Plats',	'',	'',	0,	NULL),
(2,	'Flottvik',	'59.605797,17.768605',	'',	1,	NULL),
(3,	'Näsudden',	'59.600585,17.767853',	'',	2,	NULL),
(4,	'Skogen',	'59.626083,17.771278',	'',	3,	NULL),
(5,	'Garnsviken',	'59.621426,17.734659',	'',	4,	NULL),
(6,	'Konsthall Märsta',	'59.617297,17.723661',	'',	5,	NULL),
(7,	'Museum Sigtuna',	'59.617297,17.723661',	'',	6,	NULL),
(8,	'Skolan',	'',	'',	7,	NULL);


-- --------------------------------------------------------


-- --------------------------------------------------------

DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Subject` int(11) DEFAULT NULL,
  `Carrier` int(11) DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `ExtId` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Content` longtext COLLATE utf8_unicode_ci,
  `Timestamp` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `User_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DB021E9668D3EA09` (`User_id`),
  CONSTRAINT `FK_DB021E9668D3EA09` FOREIGN KEY (`User_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `schools`;

CREATE TABLE `schools` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `GroupNumbers` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `Coordinates` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VisitOrder` int(11) DEFAULT NULL,
  `BusRule` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `schools` (`id`, `Name`, `GroupNumbers`, `Coordinates`, `VisitOrder`, `BusRule`) VALUES
('anna',	'Annan skola',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0}}',	'',	22,	0),
('berg',	'Bergius',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.620758,17.857049',	3,	7),
('cent',	'Centralskolan',	'{\"2017\":{\"2\":2,\"5\":1,\"fbk\":0},\"2016\":{\"2\":2,\"5\":1},\"2018\":{\"2\":1}}',	'59.623361,17.854833',	1,	7),
('edda',	'Eddaskolan',	'{\"2017\":{\"2\":2,\"5\":0,\"fbk\":0},\"2016\":{\"2\":2,\"5\":0},\"2018\":{\"2\":2}}',	'59.61297,17.826379',	5,	7),
('ekil',	'Ekillaskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.625404,17.846261',	2,	7),
('gala',	'Galaxskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.612196,17.811947',	16,	7),
('gert',	'S:ta Gertruds skola',	'{\"2017\":{\"2\":2,\"5\":3,\"fbk\":0},\"2016\":{\"2\":2,\"5\":3},\"2018\":{\"2\":3}}',	'59.623818,17.751047',	11,	7),
('gsar',	'Grundsärskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0}}',	'',	21,	7),
('jose',	'Josefinaskolan',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.628129,17.782695',	4,	7),
('natu',	'Naturskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0}}',	'59.605797,17.768605',	23,	0),
('norr',	'Norrbackaskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.634418,17.851555',	6,	7),
('oden',	'Odensala skola',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.665704,17.845369',	7,	79),
('olof',	'S:t Olofs skola',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.621024,17.724982',	9,	7),
('pers',	'S:t Pers skola',	'{\"2017\":{\"2\":3,\"5\":3,\"fbk\":0},\"2016\":{\"2\":3,\"5\":3},\"2018\":{\"2\":3}}',	'59.61553,17.716579',	10,	7),
('rabg',	'Råbergsskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.579675,17.890483',	8,	127),
('saga',	'Sagaskolan',	'{\"2017\":{\"2\":2,\"5\":0,\"fbk\":0},\"2016\":{\"2\":2,\"5\":0},\"2018\":{\"2\":2}}',	'59.619174,17.829329',	12,	7),
('satu',	'Sätunaskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.631093,17.85645',	18,	7),
('shoj',	'Steningehöjdens skola',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.625468,17.795426',	15,	7),
('skep',	'Skepptuna skola',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.70681,18.111307',	14,	127),
('sshl',	'Sigtunaskolan Humanistiska Läroverket',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.615083,17.709029',	13,	7),
('ting',	'Tingvallaskolan',	'{\"2017\":{\"2\":1,\"5\":2,\"fbk\":0},\"2016\":{\"2\":1,\"5\":2},\"2018\":{\"2\":2}}',	'59.626631,17.828393',	19,	7),
('vals',	'Valstaskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.61706,17.828409',	20,	7),
('vari',	'Väringaskolan',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.622441,17.725464',	17,	7);


-- --------------------------------------------------------

DROP TABLE IF EXISTS `systemstatus`;

CREATE TABLE `systemstatus` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `topics`;

CREATE TABLE `topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Grade` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `VisitOrder` int(11) NOT NULL,
  `ShortName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `LongName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Food` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FoodOrder` int(11) DEFAULT NULL,
  `Url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `IsLektion` int(11) DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Location_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_91F6463980D1AE59` (`Location_id`),
  CONSTRAINT `FK_91F6463980D1AE59` FOREIGN KEY (`Location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `topics` (`id`, `Grade`, `VisitOrder`, `ShortName`, `LongName`, `Food`, `FoodOrder`, `Url`, `IsLektion`, `LastChange`, `Location_id`) VALUES
(1,	'2',	1,	'Universum',	'',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-1-universum/',	0,	NULL,	2),
(2,	'2',	2,	'Vårvandring',	'Liv',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-2-liv-varvandring/',	0,	NULL,	4),
(3,	'2',	3,	'Forntidsdag',	'Människor',	'Pastasallad',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-3-manniskor/',	0,	NULL,	3),
(4,	'2',	4,	'BergLuftVatten',	'Berg, luft och vatten',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-4-berg-luft-och-vatten/',	0,	NULL,	2),
(5,	'2',	5,	'Teknikdag',	'Teknik',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-5-teknik/',	0,	NULL,	6),
(6,	'2',	6,	'Höstvandring',	'Liv',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-6-liv-hostvandring/',	0,	NULL,	4),
(7,	'2',	7,	'Finallektion',	'Avslutning',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/lektion-2-avslutning/',	0,	NULL,	8),
(8,	'5',	1,	'Evolutionsdag',	'Evolution',	'Mackor & Frukt',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-1-evolution/',	0,	NULL,	5),
(9,	'5',	2,	'Medeltidsdag',	'',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-2-teknikutveckling/',	0,	NULL,	7),
(10,	'5',	3,	'Energidag',	'',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-3-energiomvandlingar/',	0,	NULL,	2),
(11,	'5',	4,	'Vintervandring',	'',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-4-kretslopp-vintervandring/',	0,	NULL,	4),
(12,	'5',	5,	'Kemidag',	'',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-5-kemiska-reaktioner/',	0,	NULL,	2),
(13,	'5',	6,	'Lösningar',	'',	'Matlådor',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-6-losningar/',	0,	NULL,	4),
(14,	'fbk',	1,	'Matlagning',	'',	'Gryttillbehör',	1,	'',	0,	NULL,	4),
(15,	'fbk',	2,	'Fiske',	'',	'',	0,	'',	0,	NULL,	7),
(16,	'fbk',	3,	'Hantverk',	'',	'',	0,	'',	0,	NULL,	3),
(17,	'fbk',	4,	'Skogsvandring',	'',	'',	1,	'',	0,	NULL,	4),
(18,	'fbk',	5,	'Experiment',	'',	'',	0,	'',	0,	NULL,	2),
(19,	'fbk',	6,	'Teknik&Konst',	'',	'',	0,	'',	0,	NULL,	6);


-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Mobil` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Mail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Role` int(11) DEFAULT NULL,
  `Acronym` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CreatedAt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `School_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1483A5E941DBC54D` (`School_id`),
  CONSTRAINT `FK_1483A5E941DBC54D` FOREIGN KEY (`School_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Date` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Confirmed` int(11) NOT NULL,
  `Time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Status` int(11) NOT NULL,
  `BusIsBooked` int(11) DEFAULT NULL,
  `FoodIsBooked` int(11) DEFAULT NULL,
  `Group_id` int(11) DEFAULT NULL,
  `Topic_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_444839EA722BB11` (`Group_id`),
  KEY `IDX_444839EAE623426B` (`Topic_id`),
  CONSTRAINT `FK_444839EA722BB11` FOREIGN KEY (`Group_id`) REFERENCES `groups` (`id`),
  CONSTRAINT `FK_444839EAE623426B` FOREIGN KEY (`Topic_id`) REFERENCES `topics` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- THE END


-- Find the exported database at D:\Dropbox\scripts\naturskolan_database\misc\SQL_data\naturskolan_export_default_2018-03-04_125637.sql. Don't forget to add users_colleagues.sql
-- Created at 4.3.2018 12:56 using David Grudl MySQL Dump Utility
-- MySQL Server: 5.5.5-10.1.19-MariaDB
-- Database: naturskolan

SET NAMES utf8;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS=0;
-- --------------------------------------------------------

DROP TABLE IF EXISTS `changes`;

CREATE TABLE `changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Type` int(11) NOT NULL,
  `EntityClass` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `EntityId` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Property` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OldValue` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Processed` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Timestamp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `colleagues_visits`;

CREATE TABLE `colleagues_visits` (
  `visit_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`visit_id`,`user_id`),
  KEY `IDX_B4F058775FA0FF2` (`visit_id`),
  KEY `IDX_B4F0587A76ED395` (`user_id`),
  CONSTRAINT `FK_B4F058775FA0FF2` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B4F0587A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `cookies`;

CREATE TABLE `cookies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Rights` int(11) NOT NULL,
  `CreatedAt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `School_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF55EBDC41DBC54D` (`School_id`),
  CONSTRAINT `FK_BF55EBDC41DBC54D` FOREIGN KEY (`School_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `StartDate` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `StartTime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndDate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndTime` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Description` longtext COLLATE utf8_unicode_ci,
  `Location` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Grade` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartYear` int(11) DEFAULT NULL,
  `NumberStudents` int(11) DEFAULT NULL,
  `Food` longtext COLLATE utf8_unicode_ci,
  `Info` longtext COLLATE utf8_unicode_ci,
  `Notes` longtext COLLATE utf8_unicode_ci,
  `Status` int(11) NOT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CreatedAt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `User_id` int(11) DEFAULT NULL,
  `School_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F06D397068D3EA09` (`User_id`),
  KEY `IDX_F06D397041DBC54D` (`School_id`),
  CONSTRAINT `FK_F06D397041DBC54D` FOREIGN KEY (`School_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `FK_F06D397068D3EA09` FOREIGN KEY (`User_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `locations`;

CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Coordinates` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BusId` int(11) NOT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_17E64ABA85232B9E` (`BusId`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `locations` (`id`, `Name`, `Coordinates`, `Description`, `BusId`, `LastChange`) VALUES
(1,	'Annan Plats',	'',	'',	0,	NULL),
(2,	'Flottvik',	'59.605797,17.768605',	'',	1,	NULL),
(3,	'Näsudden',	'59.600585,17.767853',	'',	2,	NULL),
(4,	'Skogen',	'59.626083,17.771278',	'',	3,	NULL),
(5,	'Garnsviken',	'59.621426,17.734659',	'',	4,	NULL),
(6,	'Konsthall Märsta',	'59.617297,17.723661',	'',	5,	NULL),
(7,	'Museum Sigtuna',	'59.617297,17.723661',	'',	6,	NULL),
(8,	'Skolan',	'',	'',	7,	NULL);


-- --------------------------------------------------------


-- --------------------------------------------------------

DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Subject` int(11) DEFAULT NULL,
  `Carrier` int(11) DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `ExtId` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Content` longtext COLLATE utf8_unicode_ci,
  `Timestamp` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `User_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DB021E9668D3EA09` (`User_id`),
  CONSTRAINT `FK_DB021E9668D3EA09` FOREIGN KEY (`User_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `schools`;

CREATE TABLE `schools` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `GroupNumbers` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `Coordinates` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `VisitOrder` int(11) DEFAULT NULL,
  `BusRule` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `schools` (`id`, `Name`, `GroupNumbers`, `Coordinates`, `VisitOrder`, `BusRule`) VALUES
('anna',	'Annan skola',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0}}',	'',	22,	0),
('berg',	'Bergius',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.620758,17.857049',	3,	7),
('cent',	'Centralskolan',	'{\"2017\":{\"2\":2,\"5\":1,\"fbk\":0},\"2016\":{\"2\":2,\"5\":1},\"2018\":{\"2\":1}}',	'59.623361,17.854833',	1,	7),
('edda',	'Eddaskolan',	'{\"2017\":{\"2\":2,\"5\":0,\"fbk\":0},\"2016\":{\"2\":2,\"5\":0},\"2018\":{\"2\":2}}',	'59.61297,17.826379',	5,	7),
('ekil',	'Ekillaskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.625404,17.846261',	2,	7),
('gala',	'Galaxskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.612196,17.811947',	16,	7),
('gert',	'S:ta Gertruds skola',	'{\"2017\":{\"2\":2,\"5\":3,\"fbk\":0},\"2016\":{\"2\":2,\"5\":3},\"2018\":{\"2\":3}}',	'59.623818,17.751047',	11,	7),
('gsar',	'Grundsärskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0}}',	'',	21,	7),
('jose',	'Josefinaskolan',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.628129,17.782695',	4,	7),
('natu',	'Naturskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0}}',	'59.605797,17.768605',	23,	0),
('norr',	'Norrbackaskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.634418,17.851555',	6,	7),
('oden',	'Odensala skola',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.665704,17.845369',	7,	79),
('olof',	'S:t Olofs skola',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.621024,17.724982',	9,	7),
('pers',	'S:t Pers skola',	'{\"2017\":{\"2\":3,\"5\":3,\"fbk\":0},\"2016\":{\"2\":3,\"5\":3},\"2018\":{\"2\":3}}',	'59.61553,17.716579',	10,	7),
('rabg',	'Råbergsskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.579675,17.890483',	8,	127),
('saga',	'Sagaskolan',	'{\"2017\":{\"2\":2,\"5\":0,\"fbk\":0},\"2016\":{\"2\":2,\"5\":0},\"2018\":{\"2\":2}}',	'59.619174,17.829329',	12,	7),
('satu',	'Sätunaskolan',	'{\"2017\":{\"2\":2,\"5\":2,\"fbk\":0},\"2016\":{\"2\":2,\"5\":2},\"2018\":{\"2\":2}}',	'59.631093,17.85645',	18,	7),
('shoj',	'Steningehöjdens skola',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.625468,17.795426',	15,	7),
('skep',	'Skepptuna skola',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.70681,18.111307',	14,	127),
('sshl',	'Sigtunaskolan Humanistiska Läroverket',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.615083,17.709029',	13,	7),
('ting',	'Tingvallaskolan',	'{\"2017\":{\"2\":1,\"5\":2,\"fbk\":0},\"2016\":{\"2\":1,\"5\":2},\"2018\":{\"2\":2}}',	'59.626631,17.828393',	19,	7),
('vals',	'Valstaskolan',	'{\"2017\":{\"2\":0,\"5\":0,\"fbk\":0},\"2016\":{\"2\":0,\"5\":0},\"2018\":{\"2\":0}}',	'59.61706,17.828409',	20,	7),
('vari',	'Väringaskolan',	'{\"2017\":{\"2\":1,\"5\":1,\"fbk\":0},\"2016\":{\"2\":1,\"5\":1},\"2018\":{\"2\":1}}',	'59.622441,17.725464',	17,	7);


-- --------------------------------------------------------

DROP TABLE IF EXISTS `systemstatus`;

CREATE TABLE `systemstatus` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `topics`;

CREATE TABLE `topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Grade` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `VisitOrder` int(11) NOT NULL,
  `ShortName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `LongName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Food` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FoodOrder` int(11) DEFAULT NULL,
  `Url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `IsLektion` int(11) DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Location_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_91F6463980D1AE59` (`Location_id`),
  CONSTRAINT `FK_91F6463980D1AE59` FOREIGN KEY (`Location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `topics` (`id`, `Grade`, `VisitOrder`, `ShortName`, `LongName`, `Food`, `FoodOrder`, `Url`, `IsLektion`, `LastChange`, `Location_id`) VALUES
(1,	'2',	1,	'Universum',	'',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-1-universum/',	0,	NULL,	2),
(2,	'2',	2,	'Vårvandring',	'Liv',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-2-liv-varvandring/',	0,	NULL,	4),
(3,	'2',	3,	'Forntidsdag',	'Människor',	'Pastasallad',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-3-manniskor/',	0,	NULL,	3),
(4,	'2',	4,	'BergLuftVatten',	'Berg, luft och vatten',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-4-berg-luft-och-vatten/',	0,	NULL,	2),
(5,	'2',	5,	'Teknikdag',	'Teknik',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-5-teknik/',	0,	NULL,	6),
(6,	'2',	6,	'Höstvandring',	'Liv',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-6-liv-hostvandring/',	0,	NULL,	4),
(7,	'2',	7,	'Finallektion',	'Avslutning',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/lektion-2-avslutning/',	0,	NULL,	8),
(8,	'5',	1,	'Evolutionsdag',	'Evolution',	'Mackor & Frukt',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-1-evolution/',	0,	NULL,	5),
(9,	'5',	2,	'Medeltidsdag',	'',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-2-teknikutveckling/',	0,	NULL,	7),
(10,	'5',	3,	'Energidag',	'',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-3-energiomvandlingar/',	0,	NULL,	2),
(11,	'5',	4,	'Vintervandring',	'',	'',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-4-kretslopp-vintervandring/',	0,	NULL,	4),
(12,	'5',	5,	'Kemidag',	'',	'Varm mat',	1,	'http://www.sigtunanaturskola.se/aventyren/dag-5-kemiska-reaktioner/',	0,	NULL,	2),
(13,	'5',	6,	'Lösningar',	'',	'Matlådor',	2,	'http://www.sigtunanaturskola.se/aventyren/dag-6-losningar/',	0,	NULL,	4),
(14,	'fbk',	1,	'Matlagning',	'',	'Gryttillbehör',	1,	'',	0,	NULL,	4),
(15,	'fbk',	2,	'Fiske',	'',	'',	0,	'',	0,	NULL,	7),
(16,	'fbk',	3,	'Hantverk',	'',	'',	0,	'',	0,	NULL,	3),
(17,	'fbk',	4,	'Skogsvandring',	'',	'',	1,	'',	0,	NULL,	4),
(18,	'fbk',	5,	'Experiment',	'',	'',	0,	'',	0,	NULL,	2),
(19,	'fbk',	6,	'Teknik&Konst',	'',	'',	0,	'',	0,	NULL,	6);


-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Mobil` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Mail` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Role` int(11) DEFAULT NULL,
  `Acronym` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Status` int(11) DEFAULT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CreatedAt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `School_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1483A5E941DBC54D` (`School_id`),
  CONSTRAINT `FK_1483A5E941DBC54D` FOREIGN KEY (`School_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Date` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `LastChange` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Confirmed` int(11) NOT NULL,
  `Time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Status` int(11) NOT NULL,
  `BusIsBooked` int(11) DEFAULT NULL,
  `FoodIsBooked` int(11) DEFAULT NULL,
  `Group_id` int(11) DEFAULT NULL,
  `Topic_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_444839EA722BB11` (`Group_id`),
  KEY `IDX_444839EAE623426B` (`Topic_id`),
  CONSTRAINT `FK_444839EA722BB11` FOREIGN KEY (`Group_id`) REFERENCES `groups` (`id`),
  CONSTRAINT `FK_444839EAE623426B` FOREIGN KEY (`Topic_id`) REFERENCES `topics` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- THE END


INSERT INTO changes (id, Type, EntityClass, EntityId, Property, OldValue, Processed, Timestamp)  VALUES 
(1, 5, '2017-12-02', 22, 2, 12, 12, 99);

INSERT INTO cookies (id, Name, Value, Rights, CreatedAt, School_id)  VALUES 
(NULL, 'Hash', 'xGvueTHZ9FRoqyceP25WIO1xQU8rpDl9b4kl02pM4nqrLnQEOUXiK', 1, '2018-02-21T13:33:45+01:00', 'natu'),
(NULL, 'Hash', 'IhKWKA9lROo3oDwR3tV/2.fwu9yzPyKDRf3swEg1sHh5BKYV2bQ9K', 1, '2018-02-21T13:33:45+01:00', 'pers');

INSERT INTO events (id, StartDate, StartTime, EndDate, EndTime, Title, Description, Location, LastChange)  VALUES 
(NULL, '1-Aug-18 1:00:00', '10:15:00', NULL, NULL, 'Äta jordgubbar', 'Vilda bär bör ätas tidigt i augusti', 'Stadsskogen', NULL),
(NULL, '20-Sep-18 1:00:00', NULL, NULL, NULL, 'Ett heldagsevenemang i det gröna', 'Ta med mat och varma drycker', 'Halland', NULL);

INSERT INTO groups (id, Name, Grade, StartYear, NumberStudents, Food, Info, Notes, Status, LastChange, CreatedAt, User_id, School_id)  VALUES 
(13, '5C', '5', 2017, 26, 'inga', NULL, 'NULL', 1, '2018-02-24T22:01:20+01:00', '2018-01-01T12:00:00+01:00', 22, 'pers'),
(14, '5a', '5', 2017, 27, 'Några ej fläsk/halal.', NULL, 'NULL', 1, '2018-02-24T22:01:20+01:00', '2018-01-01T12:00:00+01:00', 23, 'pers'),
(15, '5b', '5', 2017, 26, 'Halal, fisk-allergi', NULL, 'NULL', 1, '2018-02-24T22:01:20+01:00', '2018-01-01T12:00:00+01:00', 24, 'pers'),
(44, '2A', '2', 2018, 27, 'minus mjölk x 1..... gluten x 1....inga nötter...', NULL, 'NULL', 1, '2018-02-24T22:01:20+01:00', '2018-01-01T12:00:00+01:00', 53, 'pers'),
(45, '2B', '2', 2018, 25, '1 (ägg, mjölk), och 1 halal', NULL, 'NULL', 1, '2018-02-24T22:01:20+01:00', '2018-01-01T12:00:00+01:00', 54, 'pers'),
(46, '2C', '2', 2018, 24, '1 elev tål ej falukorv', NULL, 'NULL', 1, '2018-02-24T22:01:20+01:00', '2018-01-01T12:00:00+01:00', 55, 'pers');

INSERT INTO systemstatus (id, Value, LastChange)  VALUES 
('cron_tasks.activation', '{"send_admin_summary_mail":1,"send_visit_confirmation_message":0,"rebuild_calendar":0,"send_new_user_mail":0,"send_update_profile_reminder":0}', '2018-02-18T22:18:23+01:00'),
('slot_counter', 97, '2018-02-18T22:03:27+01:00');

INSERT INTO users (id, FirstName, LastName, Mobil, Mail, Role, Acronym, Status, LastChange, CreatedAt, School_id)  VALUES 
(1, 'Frank', 'Häll', '071-8414155', 'vel@edu.sigtuna.se', 2, 'F', 1, '2018-01-16T23:00:26+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(2, 'Jan-Ove', 'Hallkvist', '073-7736058', 'eleifend.Cras@edu.sigtuna.se', 2, 'Ja', 1, '2018-01-16T23:00:30+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(3, 'Lars', 'Wellpapp', '077-4120147', 'non.nisi.Aenean@edu.sigtuna.se', 2, 'L', 1, '2018-01-16T23:00:32+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(4, 'Peter', 'Snöskoter', '074-0545033', 'non.magna@edu.sigtuna.se', 2, 'P', 1, '2018-01-16T23:00:35+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(5, 'Markus', 'Linszén', '072-2191185', 'nunc.interdum@edu.sigtuna.se', 2, 'M', 1, '2018-01-16T23:00:39+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(6, 'Jonas', 'Lindersberg', '075-0590505', 'arcu@edu.sigtuna.se', 2, 'Jo', 1, '2018-01-16T23:00:44+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(11, 'Elena', 'Staffansson', '076-9555010', 'Nullam.suscipit@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gala'),
(13, 'Sanna', 'Håkansson', '072-4682743', 'pulvinar.arcu@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'norr'),
(16, 'Christian', 'Andersson', '074-1371138', 'faucibus@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'vari'),
(23, 'Tomas', 'Samuelsson', '078-6661898', 'Nulla@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(24, 'Anna', 'Svensson', '074-2257145', 'ipsum.leo@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(41, 'Kristian', 'Degersberg', '078-1491681', 'ante@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'norr'),
(42, 'Henrik', 'Carlsson', '079-2009997', 'eget.massa.Suspendisse@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'norr'),
(43, 'Erica', 'Karlsson', '078-2409299', 'nulla.Integer@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(44, 'Sara', 'Andersson', '078-9905014', 'at.auctor@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(46, 'Erika', 'Ericsson', '077-0566606', 'odio@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'saga'),
(48, 'Sara', 'Bodin', '077-3025282', 'libero.dui.nec@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'rabg'),
(50, 'Märta', 'Rilven', '079-5378024', 'nec.mauris@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'cent'),
(52, 'Peter', 'Samuelsson', '079-2650873', 'eu.odio@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'edda'),
(54, 'Pär', 'Hedin', '079-3129414', 'pellentesque.Sed.dictum@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(56, 'Stefan', 'Eriksson', '074-2021103', 'Proin.sed.turpis@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'ting'),
(58, 'Anna', 'Rågwall', '071-6611082', 'In.lorem.Donec@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers');

INSERT INTO visits (id, Date, LastChange, Confirmed, Time, Status, BusIsBooked, FoodIsBooked, Group_id, Topic_id)  VALUES 
(10, '2017-09-05', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 13, 8),
(11, '2017-09-05', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 14, 8),
(12, '2017-09-06', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 15, 8),
(34, '2017-10-17', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 13, 9),
(35, '2017-10-18', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 15, 9),
(40, '2017-10-26', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 14, 9),
(58, '2017-12-05', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 14, 10),
(59, '2017-12-06', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 13, 10),
(60, '2017-12-06', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 15, 10),
(85, '2018-01-30', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 15, 11),
(87, '2018-01-31', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 13, 11),
(88, '2018-01-31', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 14, 11),
(102, '2018-02-16', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 44, 1),
(103, '2018-02-19', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 45, 1),
(104, '2018-02-20', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 46, 1),
(142, '2018-04-11', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 15, 12),
(144, '2018-04-12', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 13, 12),
(145, '2018-04-12', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 14, 12),
(150, '2018-04-13', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 44, 2),
(151, '2018-04-13', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 45, 2),
(152, '2018-04-16', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 46, 2),
(189, '2018-05-31', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 15, 13),
(191, '2018-06-01', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 14, 13),
(193, '2018-06-04', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 13, 13),
(198, '2018-06-07', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 44, 3),
(199, '2018-06-08', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 45, 3),
(200, '2018-06-11', '2018-02-24T22:01:20+01:00', 0, 'NULL', 0, 'NULL', 'NULL', 46, 3);

