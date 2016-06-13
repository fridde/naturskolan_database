-- Created at 13.6.2016 7:50 using David Grudl MySQL Dump Utility
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
(1,	'Bum',	''),
(2,	'Hello',	''),
(4,	'Blub',	''),
(5,	'Hans',	'2016-7-09');


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
(1,	'Grupp grön',	'1',	'saga',	'2',	'19',	'ej halall',	'en elev som stjäl',	'bästa gruppen nånsin',	'true',	'2016-06-12T19:47:00+02:00'),
(2,	'Grupp röd',	'2',	'saga',	'2',	'21',	'endast rått kött',	'alla är trevligare',	'svarthårig elev har luktande fötter',	'true',	''),
(3,	'Galna gänget',	'3',	'cent',	'2',	'28',	'1 elev som inte tål veteöl',	'',	'reagerar starkt på ordet \"ni\"',	'true',	''),
(4,	'2a',	'106',	'råbg',	'2',	'33',	'äter endast knäckebrödet',	'',	'',	'true',	'2016-06-12T19:47:09+02:00'),
(5,	'2b',	'60',	'råbg',	'2',	'25',	'2 elever som dricker och äter mjölk',	'kan allt om rymden',	'',	'true',	'2016-06-01T15:38:40+02:00'),
(6,	'2c',	'83',	'råbg',	'2',	'22',	'gärna oliver och pizzasallad',	'alla elever rädda för skogen',	'',	'true',	'2016-06-08T23:11:51+02:00'),
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

DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
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

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `School` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ExpirationDate` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(12,	'erRSKJySgJ3B374Guodfo.n3o8aT2T7pKoBPGXmrBDUvQFmsr0Wxm',	'råbg',	'2016-09-06T23:09:57+02:00');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `topics`;

CREATE TABLE `topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Grade` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `VisitOrder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ShortName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LongName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `Mailchimp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FirstName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LastName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mobil` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `School` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LastChange` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `Mailchimp`, `FirstName`, `LastName`, `Mobil`, `Mail`, `School`, `Status`, `LastChange`) VALUES
(1,	'',	'Friedrich',	'',	'',	'',	'natu',	'false',	''),
(2,	'',	'Jan-Erik',	'',	'',	'',	'natu',	'false',	''),
(3,	'',	'Ludvig',	'',	'',	'',	'natu',	'false',	''),
(4,	'',	'Marta',	'',	'',	'',	'natu',	'false',	''),
(5,	'',	'Per',	'',	'',	'',	'natu',	'true',	''),
(6,	'',	'Martin',	'',	'',	'',	'natu',	'false',	''),
(7,	'XXL16FNW4YH',	'Merrill',	'Hayden',	'05 71 65 16 74',	'ultrices.posuere@adipiscinglacusUt.ca',	'berg',	'1',	''),
(8,	'EYQ04EZH0JP',	'Colby',	'Shaffer',	'06 22 42 66 17',	'Duis@SuspendissesagittisNullam.net',	'cent',	'1',	''),
(9,	'WTF98HTJ3OG',	'Ivor',	'Madden',	'02 34 96 80 38',	'turpis.Nulla@eunulla.org',	'edda',	'0',	''),
(10,	'PMS55UEK8ML',	'Caldwell',	'Blair',	'01 95 71 99 85',	'at@consequatpurus.ca',	'ekil',	'1',	''),
(11,	'YSE56CXX6CX',	'Kenneth',	'Hendrix',	'01 07 07 40 99',	'odio.a@dolorFusce.ca',	'jose',	'1',	''),
(12,	'UTO19UJV8HK',	'Stuart',	'Anthony',	'03 74 46 22 50',	'egestas@primisinfaucibus.ca',	'norr',	'1',	''),
(13,	'BIA99NGZ9HA',	'Isaiah',	'Mcfarland',	'02 72 97 09 10',	'tempus.non.lacinia@elementumdui.edu',	'oden',	'1',	''),
(14,	'OIG79XWE5TA',	'Rajes',	'Zamora',	'09 88 70 30 80',	'neque@facilisismagnatellus.org',	'råbg',	'0',	''),
(15,	'BOP92FQM9TI',	'Luke',	'Diaz',	'02 80 47 19 97',	'amet.ornare@perconubia.edu',	'olof',	'0',	''),
(16,	'TER75KMJ5FQ',	'Dexter',	'Stephens',	'03 46 82 79 22',	'ornare.elit.elit@diam.net',	'pers',	'0',	''),
(17,	'OZN98APD9GS',	'Roth',	'Bates',	'02 68 20 08 68',	'Integer.in@velvulputateeu.com',	'gert',	'0',	''),
(18,	'DBE28GYO2UR',	'Channing',	'Phillips',	'09 64 06 32 44',	'fringilla.ornare.placerat@adipiscing.com',	'saga',	'0',	''),
(19,	'UFE84VNT6RH',	'Shad',	'Evans',	'05 90 47 95 81',	'Sed.auctor@nislMaecenasmalesuada.co.uk',	'sshl',	'1',	''),
(20,	'ALR66AMZ3LQ',	'Wade',	'Lara',	'09 71 84 85 97',	'tristique.pharetra@sociis.net',	'skep',	'0',	''),
(21,	'JDX28OAX4NN',	'Dolan',	'Sargent',	'03 54 47 80 07',	'lorem@atfringillapurus.org',	'shöj',	'1',	''),
(22,	'YFH34YKP5DE',	'Todd',	'Hooper',	'02 72 50 76 29',	'In.faucibus.Morbi@auctor.edu',	'gala',	'1',	''),
(23,	'GJM31STT8SN',	'Steel',	'Greene',	'03 45 69 42 94',	'malesuada@nislarcu.ca',	'sätu',	'0',	''),
(24,	'FFO51ADE0MT',	'Vernon',	'Weiss',	'07 51 44 54 87',	'cursus.a@quisdiamPellentesque.com',	'ting',	'1',	''),
(25,	'TRG95MXR8RZ',	'Seth',	'Gill',	'08 93 25 57 67',	'at.pretium@nibh.net',	'vals',	'1',	''),
(26,	'SEB97EXH2GH',	'Declan',	'Jefferson',	'07 97 26 71 20',	'nascetur.ridiculus@auctorMauris.ca',	'väri',	'1',	''),
(27,	'PLH47XVM1PQ',	'Ali',	'Avery',	'01 85 43 25 78',	'aliquet.nec@diamluctus.edu',	'gsär',	'1',	''),
(28,	'KBM11WKE2LY',	'Aidan',	'Schneider',	'08 70 29 02 64',	'suscipit.est@arcu.edu',	'anna',	'0',	''),
(29,	'XOS55TXS9BZ',	'Kelly',	'Sanders',	'06 10 99 83 06',	'Quisque.porttitor@Aliquamgravida.ca',	'natu',	'0',	''),
(30,	'GYI28DSX0MW',	'Amal',	'Webb',	'09 19 93 42 30',	'sagittis.felis.Donec@vestibulumMauris.edu',	'berg',	'0',	''),
(31,	'TJU98LZS1ZA',	'Ethan',	'Cunningham',	'03 43 02 29 86',	'eget.laoreet@enimcommodohendrerit.edu',	'cent',	'0',	''),
(32,	'KVC80LIY7JN',	'Marvin',	'Whitley',	'02 40 79 67 32',	'vitae.aliquam.eros@duiSuspendisse.edu',	'edda',	'0',	''),
(33,	'LAR49ZTI6TV',	'Rashad',	'Berger',	'07 96 37 07 39',	'commodo.at@lectus.co.uk',	'ekil',	'0',	''),
(34,	'LAI00BQH0PR',	'Tate',	'Boyer',	'09 87 22 95 51',	'Vestibulum.ante@vitaemauris.com',	'jose',	'1',	''),
(35,	'IUR11ZIL4XF',	'Honorato',	'Bender',	'01 39 27 45 19',	'consectetuer.ipsum@aliquam.ca',	'norr',	'0',	''),
(36,	'CPG67VUI5GH',	'Yardley',	'Day',	'04 74 42 36 54',	'mauris@ipsum.edu',	'oden',	'1',	''),
(37,	'WQQ05WCU1BT',	'George',	'Burks',	'04 40 22 96 54',	'gravida.mauris@dapibusrutrumjusto.edu',	'råbg',	'0',	''),
(38,	'FIY80JZC5VU',	'Flynn',	'Reilly',	'06 10 47 58 06',	'aliquet.diam.Sed@necleo.org',	'olof',	'1',	''),
(39,	'ZVX38PDQ7HB',	'Hall',	'Orr',	'02 95 73 29 60',	'non.arcu.Vivamus@ametlorem.com',	'pers',	'0',	''),
(40,	'BFI54MUF7JC',	'Clayton',	'Fisher',	'04 75 82 09 94',	'pede@duiCumsociis.ca',	'gert',	'0',	''),
(41,	'MKP01PHZ4TQ',	'Hunter',	'Mcintosh',	'07 61 34 59 57',	'Vivamus@a.net',	'saga',	'0',	''),
(42,	'DVV78LQM3XC',	'Austin',	'Blankenship',	'03 27 56 67 56',	'nonummy.ultricies.ornare@estconguea.edu',	'sshl',	'1',	''),
(43,	'VAQ68FVJ5MZ',	'Todd',	'Hart',	'04 71 78 79 00',	'amet.ante.Vivamus@porta.edu',	'skep',	'1',	''),
(44,	'PKC69MIT6YC',	'Vaughan',	'Hughes',	'03 10 53 45 68',	'pretium.aliquet@tristique.net',	'shöj',	'0',	''),
(45,	'JPP00VPT2QP',	'Anthony',	'Bray',	'09 22 29 17 76',	'nonummy.ipsum.non@ultricesposuerecubilia.ca',	'gala',	'1',	''),
(46,	'JAB55LEC4RN',	'Alvin',	'Rose',	'01 71 08 22 46',	'nisi.a@odiotristique.com',	'sätu',	'1',	''),
(47,	'LZI07TBA8UH',	'Erich',	'Winters',	'07 69 01 71 75',	'placerat.velit.Quisque@auctor.org',	'ting',	'1',	''),
(48,	'AKO78WYA3NE',	'Grady',	'Bush',	'01 79 80 07 54',	'Morbi@fermentum.org',	'vals',	'0',	''),
(49,	'MVP40UGE3PI',	'Timothy',	'Kelly',	'09 23 95 13 73',	'Lorem.ipsum@necurna.co.uk',	'väri',	'0',	''),
(50,	'PDR92HKJ6TL',	'Neil',	'Albert',	'08 73 74 49 57',	'id@morbitristiquesenectus.ca',	'gsär',	'0',	''),
(51,	'WXE21MOF7CX',	'Rooney',	'Paul',	'07 00 57 73 89',	'dignissim.tempor.arcu@molestiedapibusligula.net',	'anna',	'1',	''),
(52,	'OBF72DUZ6ES',	'Todd',	'Stafford',	'08 99 21 48 92',	'In.faucibus@Integer.net',	'natu',	'1',	''),
(53,	'PHI24SHC2HV',	'Phelan',	'Hartman',	'02 43 11 80 65',	'est@luctusut.org',	'berg',	'0',	''),
(54,	'DQU44COP4CK',	'Lucius',	'Hodge',	'05 62 27 23 55',	'non.hendrerit.id@natoquepenatibus.ca',	'cent',	'0',	''),
(55,	'FOA40VBB8FH',	'Todd',	'Gill',	'05 89 57 20 03',	'elit@tristique.com',	'edda',	'1',	''),
(56,	'AWK47NDN9HM',	'Elton',	'Moreno',	'06 15 53 53 96',	'metus.In.lorem@dapibusquamquis.edu',	'ekil',	'0',	''),
(57,	'NCK27MXQ3SW',	'Mark',	'Duncan',	'01 97 90 96 51',	'mollis.non.cursus@penatibus.ca',	'jose',	'1',	''),
(58,	'IBH53SQJ1GA',	'Jameson',	'Gibbs',	'07 39 37 32 33',	'lacinia.mattis.Integer@arcuvelquam.edu',	'norr',	'0',	''),
(59,	'MXF57UUF9YC',	'Burton',	'Boyd',	'06 14 01 19 86',	'dapibus.ligula.Aliquam@Quisque.com',	'oden',	'1',	''),
(60,	'ION20YMU4OF',	'Carl',	'Stokes',	'09 33 34 10 68',	'pretium@semperrutrum.org',	'råbg',	'1',	''),
(61,	'ZBJ09HIS0CE',	'Jonah',	'Willis',	'09 30 98 47 11',	'rhoncus.id@vestibulumloremsit.co.uk',	'olof',	'0',	''),
(62,	'NFI78SLQ2HD',	'Bruce',	'Drake',	'05 83 32 29 30',	'semper.dui.lectus@consectetueripsum.co.uk',	'pers',	'0',	''),
(63,	'GXR48KNV0HV',	'Clark',	'Smith',	'05 13 65 34 82',	'mus@accumsanconvallisante.co.uk',	'gert',	'0',	''),
(64,	'EGT60HJR8YU',	'Xavier',	'Eaton',	'04 88 83 55 07',	'nec.enim@idsapien.ca',	'saga',	'1',	''),
(65,	'KHG51UUF8FO',	'Alfonso',	'Holden',	'05 40 09 41 85',	'felis@Praesent.co.uk',	'sshl',	'0',	''),
(66,	'XNO76MBD8LD',	'Leonard',	'Price',	'04 55 81 78 07',	'rhoncus@semeget.com',	'skep',	'1',	''),
(67,	'RWV18PMA9IG',	'Basil',	'Walter',	'02 31 78 38 99',	'adipiscing.Mauris.molestie@Fusce.co.uk',	'shöj',	'1',	''),
(68,	'VNG02WLW0JN',	'Lars',	'Patton',	'01 81 23 32 11',	'dictum.magna@enimCurabitur.co.uk',	'gala',	'0',	''),
(69,	'TCJ97CGL6EO',	'Hoyt',	'Downs',	'03 42 16 28 12',	'lobortis.nisi@Etiamimperdietdictum.edu',	'sätu',	'0',	''),
(70,	'OHM76QUY8WH',	'Arsenio',	'Woods',	'04 69 93 32 10',	'orci.Ut.semper@imperdietullamcorperDuis.edu',	'ting',	'0',	''),
(71,	'NTI60NPE7LY',	'Dolan',	'Lang',	'04 37 02 13 10',	'sagittis@magna.ca',	'vals',	'0',	''),
(72,	'UFU29OWF1JN',	'Graham',	'Dillard',	'02 17 25 01 00',	'tempor.bibendum.Donec@magnatellus.co.uk',	'väri',	'1',	''),
(73,	'COV30NND8QK',	'Nissim',	'Clark',	'03 28 51 92 07',	'a@Nunclectuspede.edu',	'gsär',	'1',	''),
(74,	'KUS91DDY2XZ',	'Ishmael',	'Mccoy',	'06 94 73 76 53',	'vulputate@quispedeSuspendisse.net',	'anna',	'1',	''),
(75,	'SBI55JZJ6PP',	'Fuller',	'Ochoa',	'05 06 92 33 63',	'consequat@venenatis.org',	'natu',	'0',	''),
(76,	'IVW18HXU5NA',	'Elliott',	'Harrell',	'09 18 37 38 14',	'ut.nisi.a@scelerisquesedsapien.net',	'berg',	'0',	''),
(77,	'RST21XIU9CJ',	'Xenos',	'Holder',	'06 47 60 52 49',	'iaculis.odio@ipsum.edu',	'cent',	'0',	''),
(78,	'HPR25ADQ9GK',	'Griffith',	'Donaldson',	'01 96 86 05 41',	'Sed.pharetra.felis@idanteNunc.edu',	'edda',	'1',	''),
(79,	'LER95VOD7TP',	'Felix',	'Powell',	'06 93 09 47 77',	'fermentum.risus@Class.ca',	'ekil',	'0',	''),
(80,	'QCB26EJR3ET',	'Judah',	'Logan',	'05 46 62 83 29',	'ut@suscipitnonummyFusce.ca',	'jose',	'1',	''),
(81,	'AEN41QPC5LL',	'Drake',	'Pope',	'02 43 74 21 73',	'nulla.at@malesuada.net',	'norr',	'1',	''),
(82,	'NEO76KOZ3EC',	'Benjamin',	'Fulton',	'06 43 56 82 68',	'adipiscing.non@vestibulum.co.uk',	'oden',	'1',	''),
(83,	'RVL38XNG6DL',	'Ralph',	'Barnett',	'05 97 68 33 73',	'mus@montesnasceturridiculus.com',	'råbg',	'1',	''),
(84,	'HPD41LAT5ZW',	'Burke',	'Hoover',	'08 71 50 87 38',	'tellus.sem.mollis@mauriserat.org',	'olof',	'0',	''),
(85,	'BYA23MPW5PH',	'Judah',	'Compton',	'01 55 24 36 02',	'arcu@porttitor.co.uk',	'pers',	'0',	''),
(86,	'FCG40NPY1EL',	'Ezra',	'Burns',	'05 82 18 08 32',	'diam.nunc@convalliserateget.edu',	'gert',	'0',	''),
(87,	'VCP03SAY0EC',	'Kane',	'Clements',	'01 47 31 36 15',	'Proin@Nullaeget.ca',	'saga',	'1',	''),
(88,	'ZFT30BPR2MT',	'Shad',	'Noel',	'02 11 51 43 89',	'eu@ornaretortor.org',	'sshl',	'0',	''),
(89,	'TCH11EEZ2RH',	'Yuli',	'Norton',	'09 84 04 42 18',	'pellentesque@tinciduntvehicularisus.net',	'skep',	'1',	''),
(90,	'CRO43TEO7TG',	'Kaseem',	'Hutchinson',	'06 03 83 17 12',	'orci.Ut.sagittis@dictum.edu',	'shöj',	'0',	''),
(91,	'PCH89IIU2RG',	'Honorato',	'Mullen',	'04 47 91 19 62',	'ac.mattis.velit@malesuadaaugueut.ca',	'gala',	'1',	''),
(92,	'SOI15WGC3PE',	'Magee',	'Grimes',	'04 79 58 21 07',	'diam.nunc@tellus.ca',	'sätu',	'1',	''),
(93,	'JIT09NCS3RU',	'Raja',	'Franco',	'02 02 05 61 37',	'pellentesque.tellus.sem@erosNam.com',	'ting',	'0',	''),
(94,	'GBW88DCB2KU',	'Cade',	'Orr',	'03 32 31 85 62',	'pellentesque@atiaculis.ca',	'vals',	'1',	''),
(95,	'KFZ64QNY6UQ',	'Carl',	'Todd',	'09 54 46 56 72',	'non.sollicitudin@Maurisvel.co.uk',	'väri',	'1',	''),
(96,	'SHA26KPK2PD',	'Jesse',	'Velez',	'01 30 16 55 19',	'risus.a@at.ca',	'gsär',	'0',	''),
(97,	'LGV87JAA7NL',	'Hop',	'Hubbard',	'03 12 38 32 89',	'tincidunt.neque@tortorNunc.edu',	'anna',	'0',	''),
(98,	'WFF17OFS7MV',	'Burton',	'Serrano',	'03 28 12 27 84',	'purus@duiCras.com',	'natu',	'0',	''),
(99,	'HRT08HPW2NM',	'Samuel',	'Rivera',	'06 68 64 78 58',	'ut.quam@pellentesquemassa.co.uk',	'berg',	'1',	''),
(100,	'CGN15RKK3RW',	'Burke',	'Macias',	'07 71 02 36 91',	'nulla@Donec.co.uk',	'cent',	'1',	''),
(101,	'HTO74MNE0IL',	'Tanek',	'Rivas',	'06 08 08 03 81',	'eget.odio.Aliquam@enimnisl.ca',	'edda',	'1',	''),
(102,	'LXB13YSJ0DZ',	'Deacon',	'Clarke',	'02 08 97 51 23',	'venenatis.vel.faucibus@ametconsectetuer.co.uk',	'ekil',	'1',	''),
(103,	'UBB21TRI3KP',	'Galvin',	'Dorsey',	'06 37 74 23 90',	'molestie.pharetra@diam.net',	'jose',	'0',	''),
(104,	'HCI22ZJS9OX',	'Jerome',	'Russo',	'02 30 84 14 64',	'at@adui.edu',	'norr',	'0',	''),
(105,	'YIB85QBZ0RE',	'Herman',	'Durham',	'03 86 47 74 80',	'scelerisque.neque@dignissimlacusAliquam.co.uk',	'oden',	'1',	''),
(106,	'FCM50GHL4TV',	'ho',	'Dejesus',	'05 77 16 72 67',	'laoreet.lectus@egestasascelerisque.ca',	'råbg',	'0',	''),
(110,	'',	'',	'',	'75',	'',	'',	'',	''),
(111,	'',	'',	'',	'75',	'',	'råbg',	'0',	''),
(112,	'',	'',	'Monika',	'',	'hej',	'råbg',	'0',	''),
(113,	'',	'Hele',	'NaIng',	'04851',	'asda',	'råbg',	'0',	'');


-- --------------------------------------------------------

DROP TABLE IF EXISTS `visits`;

CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Topic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Colleague` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Confirmed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visits` (`id`, `Group`, `Date`, `Topic`, `Colleague`, `Confirmed`) VALUES
(1,	'1 ',	'2016-07-22',	'1',	'1',	'false'),
(2,	'2 ',	'2016-08-12',	'2',	'2',	'false'),
(3,	'3 ',	'2016-08-02',	'3',	'3',	'false'),
(4,	'4 ',	'2016-07-20',	'4',	'4',	'false'),
(5,	'5 ',	'2016-06-19',	'5',	'5',	'false'),
(6,	'6 ',	'2016-07-26',	'6',	'6',	'false'),
(7,	'7 ',	'2016-06-02',	'7',	'7',	'false'),
(8,	'8 ',	'2016-06-10',	'8',	'1',	'false'),
(9,	'9 ',	'2016-06-06',	'9',	'2',	'false'),
(10,	'10',	'2016-06-17',	'10',	'3',	'false'),
(11,	'1',	'2016-07-30',	'11',	'4',	'false'),
(12,	'2',	'2016-07-31',	'12',	'5',	'false'),
(13,	'3',	'2016-08-16',	'6',	'6',	'false'),
(14,	'4',	'2016-07-18',	'6',	'7',	'false'),
(15,	'5',	'2016-07-07',	'1',	'1',	'false'),
(16,	'4',	'2016-05-11',	'2',	'',	''),
(17,	'4',	'2016-05-12',	'3',	'',	''),
(18,	'4',	'2016-05-13',	'4',	'',	''),
(19,	'4',	'2016-05-14',	'5',	'',	''),
(20,	'4',	'2016-05-15',	'6',	'',	''),
(21,	'4',	'2016-05-16',	'7',	'',	''),
(22,	'4',	'2016-05-17',	'1',	'',	''),
(23,	'4',	'2016-05-18',	'2',	'',	''),
(24,	'4',	'2016-05-19',	'3',	'',	''),
(25,	'4',	'2016-05-20',	'4',	'',	''),
(26,	'4',	'2016-05-21',	'5',	'',	'');


-- THE END
