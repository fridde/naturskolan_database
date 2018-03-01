-- Find the exported database at D:\Dropbox\scripts\naturskolan_database\misc\SQL_data\naturskolan_export_structure_2018-03-01_182126.sql. Don't forget to add users_colleagues.sql
-- Created at 1.3.2018 18:21 using David Grudl MySQL Dump Utility
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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


-- --------------------------------------------------------

DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `time` int(10) unsigned DEFAULT NULL,
  `source` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`(191)) USING HASH,
  KEY `level` (`level`) USING HASH,
  KEY `time` (`time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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


-- Find the exported database at D:\Dropbox\scripts\naturskolan_database\misc\SQL_data\naturskolan_export_structure_2018-03-01_182126.sql. Don't forget to add users_colleagues.sql
-- Created at 1.3.2018 18:21 using David Grudl MySQL Dump Utility
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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


-- --------------------------------------------------------

DROP TABLE IF EXISTS `log`;

CREATE TABLE `log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `time` int(10) unsigned DEFAULT NULL,
  `source` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `channel` (`channel`(191)) USING HASH,
  KEY `level` (`level`) USING HASH,
  KEY `time` (`time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
(NULL, 'Hash', 'xGvueTHZ9FRoqyceP25WIO1xQU8rpDl9b4kl02pM4nqrLnQEOUXiK', 1, '2018-02-21T13:33:45+01:00', 'natu');

INSERT INTO events (id, StartDate, StartTime, EndDate, EndTime, Title, Description, Location, LastChange)  VALUES 
(NULL, '1-Aug-18 1:00:00', '10:15:00', NULL, NULL, 'Äta jordgubbar', 'Vilda bär bör ätas tidigt i augusti', 'Stadsskogen', NULL),
(NULL, '20-Sep-18 1:00:00', NULL, NULL, NULL, 'Ett heldagsevenemang i det gröna', 'Ta med mat och varma drycker', 'Halland', NULL);

INSERT INTO systemstatus (id, Value, LastChange)  VALUES 
('cron_tasks.activation', '{"send_admin_summary_mail":1,"send_visit_confirmation_message":0,"rebuild_calendar":0,"send_new_user_mail":0,"send_update_profile_reminder":0}', '2018-02-18T22:18:23+01:00'),
('slot_counter', 97, '2018-02-18T22:03:27+01:00');

INSERT INTO users (id, FirstName, LastName, Mobil, Mail, Role, Acronym, Status, LastChange, CreatedAt, School_id)  VALUES 
(1, 'Friedrich', 'Hehl', '073-6665275', 'friedrich.hehl@sigtuna.se', 2, 'F', 1, '2018-01-16T23:00:26+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(2, 'Jan-Erik', 'Haggarsson', '070-3897485', 'Jan-Erik.Haggarsson@sigtuna.se', 2, 'Ja', 1, '2018-01-16T23:00:30+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(3, 'Ludvig', 'Wellander', '070-3471533', 'Skafferiet@edu.sigtuna.se', 2, 'L', 1, '2018-01-16T23:00:32+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(4, 'Per', 'Snöbohm', '070-6552365', 'Per.Snobohm@sigtuna.se', 2, 'P', 1, '2018-01-16T23:00:35+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(5, 'Martin', 'Lindén', '070-7235567', 'martin.linden@edu.sigtuna.se', 2, 'M', 1, '2018-01-16T23:00:39+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(6, 'Johan', 'Lindell', '073-4039803', 'johan.lindell@edu.sigtuna.se', 2, 'Jo', 1, '2018-01-16T23:00:44+01:00', '2018-01-10T14:53:04+01:00', 'natu'),
(10, 'Emelie', 'Eriksson', '070-9644833', 'emelie.k.eriksson@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'cent'),
(11, 'Maud', 'Färnström', '0735411294', 'maud.farnstrom@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gala'),
(12, 'Ingela', 'Sandenskog', NULL, 'ingela.sandenskog@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'norr'),
(13, 'Jonas', 'Malik', NULL, 'Jonas.malik@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'norr'),
(14, 'Madeleine', 'Eldelöv', '0731544991', 'madeleine.eldelov@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'satu'),
(16, 'Jeanette', 'Lindberg', '0702709759', 'jeanette.lindberg@varingaskolan.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'vari'),
(17, 'Sofie', 'Johansson', '0736312547', 'sofie.johansson@josefinaskolan.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'jose'),
(18, 'Sara', 'Skatt', '0735398218', 'sara.skatt@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'oden'),
(19, 'Annica', 'Brink Bodingh', '0735301980', 'annica.brink@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'skep'),
(20, 'Bruna', 'Kulich', '0707777525', 'bruna.kulich@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'rabg'),
(21, 'Mathias', 'Härling', '0739817072', 'mathias.harling@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'rabg'),
(22, 'Ingrid', 'Bergh', '070-2171233', 'ingrid.bergh@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(23, 'Natallia', 'Rizell', '0725306255', 'natallia.rizell@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(24, 'Emelie', 'Forslöf', '072-3658089', 'emelie.forslof@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(25, 'Jessica', 'Fjäll', '0705222208', 'jessica.fjall@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'ting'),
(26, 'Malena', 'Nyberg', '0738368368', 'malena.nyberg@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'ting'),
(27, 'Pernilla', 'Sjödin', '0704200655', 'pernilla.sjodin@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gala'),
(28, 'Caroline', 'Nyström Avander', '070-24485 77', 'Caroline.nystrom@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(29, 'Hanna', 'Persson', '0703648453', 'Hanna.c.persson@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(30, 'Anna', 'Björkegren', '0739182197 (privat mobil)', 'Anna.bjorkegren@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(31, 'Noopur', 'Parekh Nordberg', '0737085829', 'noopur.parekh@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'shoj'),
(32, 'Britt-Marie', 'Nilsson', '073-5014288', 'britt-marie.nilsson@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gala'),
(33, 'Gunilla', 'Brånn', '0731525956', 'gunilla.brann@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gala'),
(34, 'Erika', 'Engdahl', 706311186, 'erika.levin@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'satu'),
(35, 'Josephine', 'Edlund', 730442026, 'josephine.edlund@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'satu'),
(36, 'Ellinor', 'Stålfors', 731835292, 'ellinor.stalfors@varingaskolan.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'vari'),
(37, 'Monica', 'Morenius', 707310206, 'monica.morenius@josefinaskolan.nu', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'jose'),
(38, 'Yvonne', 'Sundberg', '070-3641630', 'Yvonne.sundberg@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'oden'),
(39, 'Anna-Karin', 'Mases Wangler', 707960204, 'annakarin.wangler@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'skep'),
(40, 'Malin', 'Mårtensson', '0707 56 58 88', 'malin.martensson@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'shoj'),
(41, 'Andrea', 'Muniz Malinen', 736004101, 'andrea.malinen@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'norr'),
(42, 'Aynas', 'Selim', 739632396, 'aynas.selim@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'norr'),
(43, 'Marie', 'Kling', '0707-409495', 'marie.kling@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(44, 'Sofia', 'Haegermark Lundin', 705256320, 'sofia.haegermark@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(45, 'Katherine', 'Contreras Torrico', 708795227, 'katherine.contreras@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'gert'),
(46, 'Katalin', 'Lundmark', 706871681, 'katalin.lundmark@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'saga'),
(47, 'Nermin', 'Türkmen', '070 984 57 45', 'nermin.turkmen@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'saga'),
(48, 'Cecilia', 'Lavenius', 737330204, 'cecilia.lavenius@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'rabg'),
(49, 'Sara', 'Eriksson', '073-6200912', 'sara.i.eriksson@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'rabg'),
(50, 'Medhanite', 'Sissaye', NULL, 'medhanite.sissaye@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'cent'),
(51, 'Katarina', 'Stenman', 702735279, 'katarina.stenman@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'edda'),
(52, 'Christine', 'Habra', 723505888, 'christine.habra@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'edda'),
(53, 'Annette', 'Åhman', '0731-54 25 85', 'annette.ahman@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(54, 'Ann-Catrin', 'Enbacka', 735466989, 'ann-catrin.enbacka@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(55, 'Annika', 'Rosell Hazelius', 704243814, 'annika.rosell@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers'),
(56, 'Shara', 'Hassan', NULL, 'Shara.hassan@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'ting'),
(57, 'Charlotta', 'Waltin', '072-8576664', 'charlotta.waltin@edu.sigtuna.se', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'ting'),
(58, 'Heinz', 'Krumbichel', '072-569878', 'heinz@aol.com', 0, 'NULL', 1, 'NULL', '2018-01-01T12:00:00+01:00', 'pers');

