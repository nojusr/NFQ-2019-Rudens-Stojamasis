-- Adminer 4.7.3 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DELIMITER ;;

DROP PROCEDURE IF EXISTS `GET_LEAST_BUSY_SPECIALIST`;;
CREATE PROCEDURE `GET_LEAST_BUSY_SPECIALIST`()
SELECT *, (SELECT COUNT(*) from client WHERE client.specialist_id=specialist.sid AND client.appointment_finished = 0) AS client_count FROM specialist ORDER BY client_count ASC, sid ASC;;

DELIMITER ;

DROP TABLE IF EXISTS `client`;
CREATE TABLE `client` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `specialist_id` bigint(20) NOT NULL,
  `random_viewlink` text NOT NULL,
  `name` text NOT NULL,
  `surname` text,
  `email` text,
  `reason` mediumtext,
  `appointment_day` bigint(20) NOT NULL DEFAULT '0',
  `appointment_start_time` bigint(20) DEFAULT '0',
  `appointment_end_time` bigint(20) DEFAULT '0',
  `appointment_finished` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`),
  KEY `specialist_id` (`specialist_id`),
  CONSTRAINT `client_ibfk_1` FOREIGN KEY (`specialist_id`) REFERENCES `specialist` (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `specialist`;
CREATE TABLE `specialist` (
  `sid` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `surname` text NOT NULL,
  `time_added` bigint(20) unsigned NOT NULL DEFAULT '0',
  `clients_served` int(10) unsigned NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;


-- 2019-09-21 12:43:17
