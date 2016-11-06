-- Created at 6.11.2016 14:05 using David Grudl MySQL Dump Utility
-- Host: localhost
-- MySQL Server: 5.5.5-10.1.16-MariaDB
-- Database: naturskolan

SET NAMES utf8;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS=0;
-- --------------------------------------------------------

DROP TABLE IF EXISTS `busstrips`;

CREATE TABLE `busstrips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `events` (`id`, `name`, `Date`) VALUES
(2,	'Hello',	''),
(4,	'Blub',	''),
(5,	'Hans',	'2016-07-09');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `field_history`;

CREATE TABLE `field_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Table_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Row_id` int(11) NOT NULL,
  `Column_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Timestamp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `User` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `School` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Grade` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NumberStudents` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Food` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Info` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `IsActive` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LastChange` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `groups` (`id`, `Name`, `User`, `School`, `Grade`, `NumberStudents`, `Food`, `Info`, `Notes`, `IsActive`, `LastChange`) VALUES
(1,	'Grupp gröna',	'83',	'råbg',	'5',	'13',	'ej halal',	'en elev som stjäl',	'bästa gruppen nånsin?',	'true',	'2016-08-22T16:07:30+02:00'),
(2,	'Grupp röd',	'2',	'saga',	'2',	'17',	'endast rått kött',	'alla är trevligare',	'svarthårig elev har luktande fötter',	'true',	'2016-08-22T16:05:13+02:00'),
(3,	'Galna gänget',	'3',	'cent',	'2',	'28',	'1 elev som inte tål veteöl',	'',	'reagerar starkt på ordet \"ni\"',	'true',	''),
(4,	'2a',	'37',	'råbg',	'2',	'15',	'äter endast knäckebröd och älgstek',	'',	'',	'true',	'2016-08-31T20:59:51+02:00'),
(5,	'2cee',	'60',	'råbg',	'2',	'25',	'2 elever som dricker och äter mjölk',	'kan allt om rymden',	'',	'true',	'2016-06-01T15:38:40+02:00'),
(6,	'en annan klass',	'83',	'råbg',	'2',	'22',	'gärna oliver och pizzasallad',	'alla elever rädda för skogen',	'',	'true',	'2016-06-08T23:11:51+02:00'),
(7,	'Tändstickorna',	'1',	'väri',	'5',	'32',	'mera potatis. 10 elever äter kosher',	'har aldrig sett en räv',	'',	'true',	''),
(8,	'Del 1 av 5:an',	'2',	'pers',	'5',	'54',	'torkade tomater och kaviar, tack!',	'en av lärarna sitter i rullstol',	'',	'false',	''),
(9,	'Del 2 av 5:an',	'3',	'pers',	'5',	'18',	'ej under 27%',	'bussen avgår redan kl 11.20',	'ska inte bjudas igen',	'true',	''),
(10,	'FBK de äldre',	'4',	'vals',	'fbk16',	'15',	'> 1700kcal',	'2 elever vill gärna äta rävkött',	'be skolan att öka antal lärare',	'true',	''),
(11,	'5a',	'1',	'råbg',	'5',	'23',	'3: oliver; 1: kryddor',	'åker helst buss utan chaufför',	'borde inte träffa Fredrik igen',	'true',	''),
(12,	'5b',	'60',	'råbg',	'5',	'15',	'2: ljus mat; 3: allergiska mot laktosfria produkter',	'har läst om evolution',	'läraren bekant med Ludvig',	'true',	'2016-06-01T14:06:20+02:00'),
(13,	'FBK de små',	'106',	'råbg',	'fbk16',	'22',	'alla halal',	'kan Dari, engelska, finska och esperanto',	'eleven med svart hår bör uppmuntras mera',	'true',	'2016-06-01T15:39:14+02:00'),
(14,	'FBK de äldre',	'3',	'råbg',	'fbk79',	'19',	'alla äter endast rävtunga',	'kommer lite senare med bussen',	'2 av eleverna har starka alkoholproblem och man bör ta med en liten plunta för deras bästa.',	'true',	'2016-06-01T22:04:02+02:00');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `locations`;

CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Coordinates` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Timestamp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `passwords`;

CREATE TABLE `passwords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `School` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `passwords` (`id`, `School`, `Password`) VALUES
(3,	'berg',	'berg_qnqs'),
(4,	'cent',	'cent_zzew'),
(5,	'edda',	'edda_szgi'),
(6,	'ekil',	'ekil_isgy'),
(7,	'jose',	'jose_ctau'),
(8,	'norr',	'norr_rjzv'),
(9,	'oden',	'oden_qscn'),
(10,	'råbg',	'råbg_lcni'),
(11,	'olof',	'olof_phut'),
(12,	'pers',	'pers_ysow'),
(13,	'gert',	'gert_qrgm'),
(14,	'saga',	'saga_htev'),
(15,	'sshl',	'sshl_zzce'),
(16,	'skep',	'skep_gxja'),
(17,	'shöj',	'shöj_gvcb'),
(18,	'gala',	'gala_okvo'),
(19,	'sätu',	'sätu_baaa'),
(20,	'ting',	'ting_pfmn'),
(21,	'vals',	'vals_isje'),
(22,	'väri',	'väri_osfl'),
(23,	'gsär',	'gsär_fore'),
(24,	'anna',	'anna_evqo'),
(25,	'natu',	'natu_wkft');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `schools`;

CREATE TABLE `schools` (
  `id` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsAk2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsAk5` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsFbk16` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `GroupsFbk79` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Coordinates` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `VisitOrder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `schools` (`id`, `Name`, `GroupsAk2`, `GroupsAk5`, `GroupsFbk16`, `GroupsFbk79`, `Coordinates`, `VisitOrder`) VALUES
('anna',	'Annan skola',	'0',	'0',	'0',	'0',	'',	'22'),
('berg',	'Bergius',	'0',	'0',	'0',	'0',	'59.620758,17.857049',	'1'),
('cent',	'Centralskolan',	'1',	'2',	'0',	'0',	'59.623361,17.854833',	'2'),
('edda',	'Eddaskolan',	'2',	'0',	'0',	'0',	'59.61297,17.826379',	'3'),
('ekil',	'Ekillaskolan',	'0',	'0',	'0',	'0',	'59.625404,17.846261',	'4'),
('gala',	'Galaxskolan',	'2',	'2',	'0',	'0',	'59.612196,17.811947',	'16'),
('gert',	'S:ta Gertruds skola',	'3',	'2',	'0',	'0',	'59.623818,17.751047',	'11'),
('gsär',	'Grundsärskolan',	'0',	'0',	'0',	'0',	'',	'21'),
('jose',	'Josefinaskolan',	'1',	'1',	'0',	'0',	'59.628129,17.782695',	'5'),
('natu',	'Naturskolan',	'0',	'0',	'0',	'0',	'59.605797,17.768605',	'23'),
('norr',	'Norrbackaskolan',	'2',	'2',	'0',	'0',	'59.634418,17.851555',	'6'),
('oden',	'Odensala skola',	'1',	'1',	'0',	'0',	'59.665704,17.845369',	'7'),
('olof',	'S:t Olofs skola',	'0',	'0',	'0',	'0',	'59.621024,17.724982',	'9'),
('pers',	'S:t Pers skola',	'2',	'2',	'0',	'0',	'59.61553,17.716579',	'10'),
('råbg',	'Råbergsskolan',	'2',	'3',	'0',	'0',	'59.579675,17.890483',	'8'),
('saga',	'Sagaskolan',	'2',	'0',	'0',	'0',	'59.619174,17.829329',	'12'),
('sätu',	'Sätunaskolan',	'2',	'2',	'0',	'0',	'59.631093,17.85645',	'17'),
('shöj',	'Steningehöjdens skola',	'2',	'1',	'0',	'0',	'59.625468,17.795426',	'15'),
('skep',	'Skepptuna skola',	'1',	'1',	'0',	'0',	'59.70681,18.111307',	'14'),
('sshl',	'Sigtunaskolan Humanistiska Läroverket',	'0',	'0',	'0',	'0',	'59.615083,17.709029',	'13'),
('ting',	'Tingvallaskolan',	'1',	'1',	'0',	'0',	'59.626631,17.828393',	'18'),
('vals',	'Valstaskolan',	'0',	'3',	'0',	'0',	'59.61706,17.828409',	'19'),
('väri',	'Väringaskolan',	'1',	'1',	'0',	'0',	'59.622441,17.725464',	'20');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `sentmessages`;

CREATE TABLE `sentmessages` (
  `id` int(11) NOT NULL,
  `Type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `User` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `School` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ExpirationDate` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`, `Hash`, `School`, `ExpirationDate`) VALUES
(1,	'VIpgYYYyDc0FYyX5fVmYNOWMCtGBY93XlvKV9Ag65558MYav8mJ8.',	'ekil',	'2016-06-02T07:33:09+02:00'),
(2,	'aRSKXUkg.TDzd3FHoRuAxOc3oPc9aQ3iY6o8Pzc0jAlDygmo58L4y',	'ekil',	'2016-06-02T07:33:09+02:00'),
(3,	'VJN/RvZC1CkKAuMZrkv43eSYFTbF0wrLDtFcPNnKAwom1NqyLTgwy',	'råbg',	'2016-06-02T07:33:09+02:00'),
(4,	'IkWkdtkDtiMAfsMAZvgyq.3E9Byb.UQeC0R0IzXtK87Ghxq0keFNi',	'råbg',	'2016-06-02T07:33:09+02:00'),
(5,	't8AY//K5tX78xnX75Hd5o.nhg1tZiWKB6fiM2P/1urtNGAqMhtRvS',	'råbg',	'2016-06-02T07:33:09+02:00'),
(6,	'jnnUQ1JFrdTQaGPTIfaev.l9GujhWETtBDqGU7VWFqF0M9zvem5ui',	'råbg',	'2016-06-02T07:33:09+02:00'),
(7,	'GC1zqEaSVYxd8ymcUV/Gc.ppt5MshnkqoWAIMQYsLgxvNP.3ObK9O',	'råbg',	'2016-06-02T07:33:09+02:00'),
(8,	'nKMxmSucmIhCn6xwn5ZEbOYKamz6Mo5pxL1PNejwfPa0hFSSpY1l2',	'råbg',	'2016-06-02T07:33:09+02:00'),
(9,	'gSHs1HZfBHymqZq37Rup2urV/AztfZrn9a3jO1Q2GBAmcNqDsQ9yC',	'råbg',	'2016-06-02T07:33:09+02:00'),
(10,	'1TLKsHAk7KXIEDJpg3Q98e6Al7xxFLPSjyzdRe5ria.m1i1J1cdvG',	'råbg',	'2016-06-02T07:33:09+02:00'),
(11,	'Q132URu6Axk2dicXzHiRj.NeZUm2y1Vy5thRPArQpFn56XxIzEtIC',	'råbg',	'2016-09-06T20:31:44+02:00'),
(12,	'erRSKJySgJ3B374Guodfo.n3o8aT2T7pKoBPGXmrBDUvQFmsr0Wxm',	'råbg',	'2016-09-06T23:09:57+02:00'),
(13,	'X38QdSXqJBsSe/9n9bLvSOCCQI1OeubZUpa3ZGRL3EEXlp4Prn/A6',	'råbg',	'2016-11-18T17:53:51+01:00');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `tasks`;

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `Timestamp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tasks` (`id`, `Name`, `Value`, `Timestamp`) VALUES
(1,	'calendar.status',	'dirty',	''),
(2,	'calendar.last_rebuild',	'2016-09-24T18:10:43+02:00',	''),
(4,	'changed.fields',	'',	''),
(5,	'changed.leaders',	'',	''),
(6,	'slot_counter',	'0',	'');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `topics`;

CREATE TABLE `topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Grade` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `VisitOrder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ShortName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LongName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Food` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `IsLektion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `topics` (`id`, `Grade`, `VisitOrder`, `ShortName`, `LongName`, `Location`, `Food`, `Url`, `IsLektion`) VALUES
(1,	'2',	'1',	'Universum',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/1',	''),
(2,	'2',	'2',	'Vårvandring',	'Liv',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/2',	''),
(3,	'2',	'3',	'Forntidsdag',	'Människor',	'3',	'Pastasallad',	'http://www.sigtunanaturskola.se/db/topic/3',	''),
(4,	'2',	'4',	'BergLuftVatten',	'Berg, luft och vatten',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/4',	''),
(5,	'2',	'5',	'Teknikdag',	'Teknik',	'5',	'Plats i matsalen',	'http://www.sigtunanaturskola.se/db/topic/5',	''),
(6,	'2',	'6',	'Höstvandring',	'Liv',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/6',	''),
(7,	'2',	'7',	'Finallektion',	'Avslutning',	'7',	'',	'http://www.sigtunanaturskola.se/db/topic/7',	'true'),
(8,	'5',	'1',	'Evolutionsdag',	'Evolution',	'4',	'Mackor & Frukt',	'http://www.sigtunanaturskola.se/db/topic/8',	''),
(9,	'5',	'2',	'Medeltidsdag',	'',	'6',	'Plats i matsalen',	'http://www.sigtunanaturskola.se/db/topic/9',	''),
(10,	'5',	'3',	'Energidag',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/10',	''),
(11,	'5',	'4',	'Vintervandring',	'',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/11',	''),
(12,	'5',	'5',	'Kemidag',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/12',	''),
(13,	'5',	'6',	'Supermänniska',	'',	'1',	'Varm mat',	'http://www.sigtunanaturskola.se/db/topic/13',	''),
(14,	'fbk',	'1',	'Matlagning',	'',	'2',	'Gryttillbehör',	'http://www.sigtunanaturskola.se/db/topic/14',	''),
(15,	'fbk',	'2',	'Fiske',	'',	'8',	'',	'http://www.sigtunanaturskola.se/db/topic/15',	''),
(16,	'fbk',	'3',	'Hantverk 1',	'',	'3',	'',	'http://www.sigtunanaturskola.se/db/topic/16',	''),
(17,	'fbk',	'4',	'Skogsvandring',	'',	'2',	'',	'http://www.sigtunanaturskola.se/db/topic/17',	''),
(18,	'fbk',	'5',	'Experiment',	'',	'1',	'',	'http://www.sigtunanaturskola.se/db/topic/18',	''),
(19,	'fbk',	'6',	'Hantverk 2',	'',	'3',	'',	'http://www.sigtunanaturskola.se/db/topic/19',	'');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LastName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mobil` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `School` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LastChange` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DateAdded` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `FirstName`, `LastName`, `Mobil`, `Mail`, `School`, `Status`, `LastChange`, `DateAdded`) VALUES
(1,	'Friedrich',	'Halva',	'070321654',	'fh@sigtunanaturskola.se',	'natu',	'active',	'',	''),
(2,	'Jan-Erik',	'Huggersdottir',	'070456789',	'je@sigtunanaturskola.se',	'natu',	'active',	'',	''),
(3,	'Ludvig',	'Wildur',	'070-987654',	'lw@sigtunanaturskola.se',	'natu',	'active',	'',	''),
(4,	'Marta',	'Leopolda',	'070-123456',	'mla@sigtunanaturskola.se',	'natu',	'active',	'',	''),
(5,	'Per',	'Snowden',	'070963852',	'ps@sigtunanaturskola.se',	'natu',	'active',	'',	''),
(6,	'Martin',	'Lundsson',	'070741258',	'mln@sigtunanaturskola.se',	'natu',	'active',	'',	''),
(7,	'Johan',	'Lokansson',	'070369874',	'jl@sigtunanaturskola.se',	'natu',	'active',	'',	''),
(8,	'Colby',	'Shaffer',	'06 22 42 66 17',	'Duis@SuspendissesagittisNullam.net',	'cent',	'active',	'',	''),
(9,	'Ivor',	'Madden',	'02 34 96 80 38',	'turpis.Nulla@eunulla.org',	'edda',	'active',	'',	''),
(10,	'Caldwell',	'Blair',	'01 95 71 99 85',	'at@consequatpurus.ca',	'ekil',	'active',	'',	''),
(11,	'Kenneth',	'Hendrix',	'01 07 07 40 99',	'odio.a@dolorFusce.ca',	'jose',	'active',	'',	''),
(12,	'Stuart',	'Anthony',	'03 74 46 22 50',	'egestas@primisinfaucibus.ca',	'norr',	'active',	'',	''),
(13,	'Isaiah',	'Mcfarland',	'02 72 97 09 10',	'tempus.non.lacinia@elementumdui.edu',	'oden',	'active',	'',	''),
(14,	'Rajes',	'Zamora',	'09 88 70 30 80',	'neque@facilisismagnatellus.org',	'råbg',	'active',	'',	''),
(15,	'Luke',	'Diaz',	'02 80 47 19 97',	'amet.ornare@perconubia.edu',	'olof',	'active',	'',	''),
(16,	'Dexter',	'Stephens',	'03 46 82 79 22',	'ornare.elit.elit@diam.net',	'pers',	'active',	'',	''),
(17,	'Roth',	'Bates',	'02 68 20 08 68',	'Integer.in@velvulputateeu.com',	'gert',	'active',	'',	''),
(18,	'Channing',	'Phillips',	'09 64 06 32 44',	'fringilla.ornare.placerat@adipiscing.com',	'saga',	'active',	'',	''),
(19,	'Shad',	'Evans',	'05 90 47 95 81',	'Sed.auctor@nislMaecenasmalesuada.co.uk',	'sshl',	'active',	'',	''),
(20,	'Wade',	'Lara',	'09 71 84 85 97',	'tristique.pharetra@sociis.net',	'skep',	'active',	'',	''),
(21,	'Dolan',	'Sargent',	'03 54 47 80 07',	'lorem@atfringillapurus.org',	'shöj',	'active',	'',	''),
(22,	'Todd',	'Hooper',	'02 72 50 76 29',	'In.faucibus.Morbi@auctor.edu',	'gala',	'active',	'',	''),
(23,	'Steel',	'Greene',	'03 45 69 42 94',	'malesuada@nislarcu.ca',	'sätu',	'active',	'',	''),
(24,	'Vernon',	'Weiss',	'07 51 44 54 87',	'cursus.a@quisdiamPellentesque.com',	'ting',	'active',	'',	''),
(25,	'Seth',	'Gill',	'08 93 25 57 67',	'at.pretium@nibh.net',	'vals',	'active',	'',	''),
(26,	'Declan',	'Jefferson',	'07 97 26 71 20',	'nascetur.ridiculus@auctorMauris.ca',	'väri',	'active',	'',	''),
(27,	'Ali',	'Avery',	'01 85 43 25 78',	'aliquet.nec@diamluctus.edu',	'gsär',	'active',	'',	''),
(28,	'Aidan',	'Schneider',	'08 70 29 02 64',	'suscipit.est@arcu.edu',	'anna',	'active',	'',	''),
(29,	'Kelly',	'Sanders',	'06 10 99 83 06',	'Quisque.porttitor@Aliquamgravida.ca',	'natu',	'active',	'',	''),
(30,	'Amal',	'Webb',	'09 19 93 42 30',	'sagittis.felis.Donec@vestibulumMauris.edu',	'berg',	'active',	'',	''),
(31,	'Ethan',	'Cunningham',	'03 43 02 29 86',	'eget.laoreet@enimcommodohendrerit.edu',	'cent',	'active',	'',	''),
(32,	'Marvin',	'Whitley',	'02 40 79 67 32',	'vitae.aliquam.eros@duiSuspendisse.edu',	'edda',	'active',	'',	''),
(33,	'Rashad',	'Berger',	'07 96 37 07 39',	'commodo.at@lectus.co.uk',	'ekil',	'active',	'',	''),
(34,	'Tate',	'Boyer',	'09 87 22 95 51',	'Vestibulum.ante@vitaemauris.com',	'jose',	'active',	'',	''),
(35,	'Honorato',	'Bender',	'01 39 27 45 19',	'consectetuer.ipsum@aliquam.ca',	'norr',	'active',	'',	''),
(36,	'Yardley',	'Day',	'04 74 42 36 54',	'mauris@ipsum.edu',	'oden',	'active',	'',	''),
(37,	'George',	'Burks',	'04 40 22 96 54',	'gravida.mauris@dapibusrutrumjusto.edu',	'råbg',	'active',	'',	''),
(38,	'Flynn',	'Reilly',	'06 10 47 58 06',	'aliquet.diam.Sed@necleo.org',	'olof',	'active',	'',	''),
(39,	'Hall',	'Orr',	'02 95 73 29 60',	'non.arcu.Vivamus@ametlorem.com',	'pers',	'active',	'',	''),
(40,	'Clayton',	'Fisher',	'04 75 82 09 94',	'pede@duiCumsociis.ca',	'gert',	'active',	'',	''),
(41,	'Hunter',	'Mcintosh',	'07 61 34 59 57',	'Vivamus@a.net',	'saga',	'active',	'',	''),
(42,	'Austin',	'Blankenship',	'03 27 56 67 56',	'nonummy.ultricies.ornare@estconguea.edu',	'sshl',	'active',	'',	''),
(43,	'Todd',	'Hart',	'04 71 78 79 00',	'amet.ante.Vivamus@porta.edu',	'skep',	'active',	'',	''),
(44,	'Vaughan',	'Hughes',	'03 10 53 45 68',	'pretium.aliquet@tristique.net',	'shöj',	'active',	'',	''),
(45,	'Anthony',	'Bray',	'09 22 29 17 76',	'nonummy.ipsum.non@ultricesposuerecubilia.ca',	'gala',	'active',	'',	''),
(46,	'Alvin',	'Rose',	'01 71 08 22 46',	'nisi.a@odiotristique.com',	'sätu',	'active',	'',	''),
(47,	'Erich',	'Winters',	'07 69 01 71 75',	'placerat.velit.Quisque@auctor.org',	'ting',	'active',	'',	''),
(48,	'Grady',	'Bush',	'01 79 80 07 54',	'Morbi@fermentum.org',	'vals',	'active',	'',	''),
(49,	'Timothy',	'Kelly',	'09 23 95 13 73',	'Lorem.ipsum@necurna.co.uk',	'väri',	'active',	'',	''),
(50,	'Neil',	'Albert',	'08 73 74 49 57',	'id@morbitristiquesenectus.ca',	'gsär',	'active',	'',	''),
(51,	'Rooney',	'Paul',	'07 00 57 73 89',	'dignissim.tempor.arcu@molestiedapibusligula.net',	'anna',	'active',	'',	''),
(52,	'Todd',	'Stafford',	'08 99 21 48 92',	'In.faucibus@Integer.net',	'natu',	'active',	'',	''),
(53,	'Phelan',	'Hartman',	'02 43 11 80 65',	'est@luctusut.org',	'berg',	'active',	'',	''),
(54,	'Lucius',	'Hodge',	'05 62 27 23 55',	'non.hendrerit.id@natoquepenatibus.ca',	'cent',	'active',	'',	''),
(55,	'Todd',	'Gill',	'05 89 57 20 03',	'elit@tristique.com',	'edda',	'active',	'',	''),
(56,	'Elton',	'Moreno',	'06 15 53 53 96',	'metus.In.lorem@dapibusquamquis.edu',	'ekil',	'active',	'',	''),
(57,	'Mark',	'Duncan',	'01 97 90 96 51',	'mollis.non.cursus@penatibus.ca',	'jose',	'active',	'',	''),
(58,	'Jameson',	'Gibbs',	'07 39 37 32 33',	'lacinia.mattis.Integer@arcuvelquam.edu',	'norr',	'active',	'',	''),
(59,	'Burton',	'Boyd',	'06 14 01 19 86',	'dapibus.ligula.Aliquam@Quisque.com',	'oden',	'active',	'',	''),
(60,	'Carl',	'Stokes',	'09 33 34 10 68',	'pretium@semperrutrum.org',	'råbg',	'active',	'',	''),
(61,	'Jonah',	'Willis',	'09 30 98 47 11',	'rhoncus.id@vestibulumloremsit.co.uk',	'olof',	'active',	'',	''),
(62,	'Bruce',	'Drake',	'05 83 32 29 30',	'semper.dui.lectus@consectetueripsum.co.uk',	'pers',	'active',	'',	''),
(63,	'Clark',	'Smith',	'05 13 65 34 82',	'mus@accumsanconvallisante.co.uk',	'gert',	'active',	'',	''),
(64,	'Xavier',	'Eaton',	'04 88 83 55 07',	'nec.enim@idsapien.ca',	'saga',	'active',	'',	''),
(65,	'Alfonso',	'Holden',	'05 40 09 41 85',	'felis@Praesent.co.uk',	'sshl',	'active',	'',	''),
(66,	'Leonard',	'Price',	'04 55 81 78 07',	'rhoncus@semeget.com',	'skep',	'active',	'',	''),
(67,	'Basil',	'Walter',	'02 31 78 38 99',	'adipiscing.Mauris.molestie@Fusce.co.uk',	'shöj',	'active',	'',	''),
(68,	'Lars',	'Patton',	'01 81 23 32 11',	'dictum.magna@enimCurabitur.co.uk',	'gala',	'active',	'',	''),
(69,	'Hoyt',	'Downs',	'03 42 16 28 12',	'lobortis.nisi@Etiamimperdietdictum.edu',	'sätu',	'active',	'',	''),
(70,	'Arsenio',	'Woods',	'04 69 93 32 10',	'orci.Ut.semper@imperdietullamcorperDuis.edu',	'ting',	'active',	'',	''),
(71,	'Dolan',	'Lang',	'04 37 02 13 10',	'sagittis@magna.ca',	'vals',	'active',	'',	''),
(72,	'Graham',	'Dillard',	'02 17 25 01 00',	'tempor.bibendum.Donec@magnatellus.co.uk',	'väri',	'active',	'',	''),
(73,	'Nissim',	'Clark',	'03 28 51 92 07',	'a@Nunclectuspede.edu',	'gsär',	'active',	'',	''),
(74,	'Ishmael',	'Mccoy',	'06 94 73 76 53',	'vulputate@quispedeSuspendisse.net',	'anna',	'active',	'',	''),
(75,	'Fuller',	'Ochoa',	'05 06 92 33 63',	'consequat@venenatis.org',	'natu',	'active',	'',	''),
(76,	'Elliott',	'Harrell',	'09 18 37 38 14',	'ut.nisi.a@scelerisquesedsapien.net',	'berg',	'active',	'',	''),
(77,	'Xenos',	'Holder',	'06 47 60 52 49',	'iaculis.odio@ipsum.edu',	'cent',	'active',	'',	''),
(78,	'Griffith',	'Donaldson',	'01 96 86 05 41',	'Sed.pharetra.felis@idanteNunc.edu',	'edda',	'active',	'',	''),
(79,	'Felix',	'Powell',	'06 93 09 47 77',	'fermentum.risus@Class.ca',	'ekil',	'active',	'',	''),
(80,	'Judah',	'Logan',	'05 46 62 83 29',	'ut@suscipitnonummyFusce.ca',	'jose',	'active',	'',	''),
(81,	'Drake',	'Pope',	'02 43 74 21 73',	'nulla.at@malesuada.net',	'norr',	'active',	'',	''),
(82,	'Benjamin',	'Fulton',	'06 43 56 82 68',	'adipiscing.non@vestibulum.co.uk',	'oden',	'active',	'',	''),
(83,	'Ralph',	'Barnett',	'05 97 68 33 73',	'mus@montesnasceturridiculus.com',	'råbg',	'active',	'',	''),
(84,	'Burke',	'Hoover',	'08 71 50 87 38',	'tellus.sem.mollis@mauriserat.org',	'olof',	'active',	'',	''),
(85,	'Judah',	'Compton',	'01 55 24 36 02',	'arcu@porttitor.co.uk',	'pers',	'active',	'',	''),
(86,	'Ezra',	'Burns',	'05 82 18 08 32',	'diam.nunc@convalliserateget.edu',	'gert',	'active',	'',	''),
(87,	'Kane',	'Clements',	'01 47 31 36 15',	'Proin@Nullaeget.ca',	'saga',	'active',	'',	''),
(88,	'Shad',	'Noel',	'02 11 51 43 89',	'eu@ornaretortor.org',	'sshl',	'active',	'',	''),
(89,	'Yuli',	'Norton',	'09 84 04 42 18',	'pellentesque@tinciduntvehicularisus.net',	'skep',	'active',	'',	''),
(90,	'Kaseem',	'Hutchinson',	'06 03 83 17 12',	'orci.Ut.sagittis@dictum.edu',	'shöj',	'active',	'',	''),
(91,	'Honorato',	'Mullen',	'04 47 91 19 62',	'ac.mattis.velit@malesuadaaugueut.ca',	'gala',	'active',	'',	''),
(92,	'Magee',	'Grimes',	'04 79 58 21 07',	'diam.nunc@tellus.ca',	'sätu',	'active',	'',	''),
(93,	'Raja',	'Franco',	'02 02 05 61 37',	'pellentesque.tellus.sem@erosNam.com',	'ting',	'active',	'',	''),
(94,	'Cade',	'Orr',	'03 32 31 85 62',	'pellentesque@atiaculis.ca',	'vals',	'active',	'',	''),
(95,	'Carl',	'Todd',	'09 54 46 56 72',	'non.sollicitudin@Maurisvel.co.uk',	'väri',	'active',	'',	''),
(96,	'Jesse',	'Velez',	'01 30 16 55 19',	'risus.a@at.ca',	'gsär',	'active',	'',	''),
(97,	'Hop',	'Hubbard',	'03 12 38 32 89',	'tincidunt.neque@tortorNunc.edu',	'anna',	'active',	'',	''),
(98,	'Burton',	'Serrano',	'03 28 12 27 84',	'purus@duiCras.com',	'natu',	'active',	'',	''),
(99,	'Samuel',	'Rivera',	'06 68 64 78 58',	'ut.quam@pellentesquemassa.co.uk',	'berg',	'active',	'',	''),
(100,	'Burke',	'Macias',	'07 71 02 36 91',	'nulla@Donec.co.uk',	'cent',	'active',	'',	''),
(101,	'Tanek',	'Rivas',	'06 08 08 03 81',	'eget.odio.Aliquam@enimnisl.ca',	'edda',	'active',	'',	''),
(102,	'Deacon',	'Clarke',	'02 08 97 51 23',	'venenatis.vel.faucibus@ametconsectetuer.co.uk',	'ekil',	'active',	'',	''),
(103,	'Galvin',	'Dorsey',	'06 37 74 23 90',	'molestie.pharetra@diam.net',	'jose',	'active',	'',	''),
(104,	'Jerome',	'Russo',	'02 30 84 14 64',	'at@adui.edu',	'norr',	'active',	'',	''),
(105,	'Herman',	'Durham',	'03 86 47 74 80',	'scelerisque.neque@dignissimlacusAliquam.co.uk',	'oden',	'active',	'',	''),
(106,	'ho',	'Dejesus',	'05 77 16 72 67',	'laoreet.lectus@egestasascelerisque.ca',	'råbg',	'active',	'',	''),
(107,	'Fridde',	'Lumberjack',	'01901578459',	'froo@hoho.com',	'råbg',	'active',	'2016-08-20T18:56:59+02:00',	'2016-08-20T18:56:41+02:00');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Topic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Colleague` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Confirmed` tinyint(1) NOT NULL,
  `Time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visits` (`id`, `Group`, `Date`, `Topic`, `Colleague`, `Confirmed`, `Time`) VALUES
(1,	'1 ',	'2016-10-22',	'1',	'1',	0,	''),
(2,	'2 ',	'2016-11-12',	'2',	'2',	0,	''),
(3,	'3 ',	'2016-11-02',	'3',	'3',	0,	''),
(4,	'4 ',	'2016-10-20',	'4',	'4',	0,	''),
(5,	'5 ',	'2016-09-19',	'5',	'5',	0,	''),
(6,	'6 ',	'2016-10-26',	'6',	'6',	0,	''),
(7,	'7 ',	'2016-10-02',	'7',	'7',	0,	''),
(8,	'8 ',	'2016-09-10',	'8',	'1',	0,	''),
(9,	'9 ',	'2016-06-06',	'9',	'2',	0,	''),
(10,	'10',	'2016-09-17',	'10',	'3',	0,	''),
(11,	'1',	'2016-10-30',	'11',	'4',	0,	''),
(12,	'2',	'2016-10-31',	'12',	'5',	0,	''),
(13,	'3',	'2016-11-16',	'6',	'6',	0,	''),
(14,	'4',	'2016-10-18',	'6',	'7',	0,	''),
(15,	'5',	'2016-10-07',	'1',	'1',	0,	''),
(16,	'4',	'2016-09-11',	'2',	'',	0,	''),
(17,	'5',	'2016-09-12',	'3',	'',	0,	''),
(18,	'6',	'2016-12-13',	'4',	'',	0,	''),
(19,	'7',	'2016-12-14',	'5',	'',	0,	''),
(20,	'8',	'2016-12-15',	'6',	'',	0,	''),
(21,	'9',	'2016-12-16',	'7',	'',	0,	''),
(22,	'10',	'2016-12-17',	'1',	'',	0,	''),
(23,	'11',	'2016-12-18',	'2',	'',	0,	''),
(24,	'12',	'2016-12-19',	'3',	'',	0,	''),
(25,	'13',	'2016-12-20',	'4',	'',	0,	''),
(26,	'14',	'2016-12-21',	'5',	'',	0,	'');


-- THE END
