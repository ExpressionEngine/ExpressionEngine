# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.6.19)
# Database: expressionengine
# Generation Time: 2014-06-13 21:39:01 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table exp_cp_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `exp_cp_log`;

CREATE TABLE `exp_cp_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `site_id` int(4) unsigned NOT NULL DEFAULT '1',
  `member_id` int(10) unsigned NOT NULL,
  `username` varchar(32) NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `act_date` int(10) NOT NULL,
  `action` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `exp_cp_log` WRITE;
/*!40000 ALTER TABLE `exp_cp_log` DISABLE KEYS */;

INSERT INTO `exp_cp_log` (`id`, `site_id`, `member_id`, `username`, `ip_address`, `act_date`, `action`)
VALUES
	(174,1,1,'admin','192.168.0.90',1391112349,'Logged in'),
	(175,1,1,'admin','192.168.0.90',1391115352,'Logged in'),
	(176,1,1,'admin','192.168.0.90',1391124004,'Logged in'),
	(177,1,1,'admin','192.168.0.90',1391462893,'Logged in'),
	(178,1,1,'admin','192.168.0.90',1391468657,'Logged in'),
	(179,1,1,'admin','192.168.0.90',1391536881,'Logged in'),
	(180,1,1,'admin','192.168.0.90',1391555594,'Logged in'),
	(181,1,1,'admin','192.168.0.90',1391624048,'Logged in'),
	(182,1,1,'admin','192.168.0.90',1391724429,'Logged in'),
	(183,1,1,'admin','192.168.0.90',1391803868,'Logged in'),
	(184,1,1,'admin','192.168.0.90',1392070716,'Logged in'),
	(185,1,1,'admin','192.168.0.90',1392226768,'Logged in'),
	(186,1,1,'admin','192.168.0.90',1392227010,'Channel Created:&nbsp;&nbsp;Pages'),
	(187,1,1,'admin','192.168.0.90',1392232468,'Logged in'),
	(188,1,1,'admin','192.168.0.90',1392238052,'Logged in'),
	(189,1,1,'admin','192.168.0.90',1392243889,'Logged in'),
	(190,1,1,'admin','192.168.0.90',1392328274,'Logged in'),
	(191,1,1,'admin','192.168.0.90',1392332272,'Logged in'),
	(192,1,1,'admin','192.168.0.90',1392403282,'Logged in'),
	(193,1,1,'admin','192.168.0.90',1392407676,'Logged in'),
	(194,1,1,'admin','192.168.0.90',1392411612,'Logged in'),
	(195,1,1,'admin','192.168.0.90',1392417318,'Logged in'),
	(196,1,1,'admin','192.168.0.90',1392490561,'Logged in'),
	(197,1,1,'admin','192.168.0.90',1392498704,'Logged in'),
	(198,1,1,'admin','192.168.0.90',1392663308,'Logged in'),
	(199,1,1,'admin','192.168.0.90',1392744613,'Logged in'),
	(200,1,1,'admin','192.168.0.90',1392744613,'Logged in'),
	(201,1,1,'admin','192.168.0.90',1392748025,'Field Group Created:&nbsp;Reviews'),
	(202,1,1,'admin','192.168.0.90',1392748915,'Channel Created:&nbsp;&nbsp;Fountain Pen Reviews'),
	(203,1,1,'admin','192.168.0.90',1392756166,'Logged in'),
	(204,1,1,'admin','192.168.0.90',1392778940,'Logged in'),
	(205,1,1,'admin','192.168.0.90',1392831442,'Logged in'),
	(206,1,1,'admin','192.168.0.90',1392833421,'Logged in'),
	(207,1,1,'admin','192.168.0.90',1392833916,'Category Group Created:&nbsp;&nbsp;Stationery'),
	(208,1,1,'admin','192.168.0.90',1392835168,'Channel Created:&nbsp;&nbsp;Reviews: Board Games'),
	(209,1,1,'admin','192.168.0.90',1392835188,'Channel Created:&nbsp;&nbsp;Reviews: Books'),
	(210,1,1,'admin','192.168.0.90',1392844533,'Logged in'),
	(211,1,1,'admin','192.168.0.90',1392852480,'Logged in'),
	(212,1,1,'admin','192.168.0.90',1392919303,'Logged in'),
	(213,1,1,'admin','192.168.0.90',1392932871,'Logged in'),
	(214,1,1,'admin','192.168.0.90',1393367671,'Logged in'),
	(215,1,1,'admin','192.168.0.90',1393481631,'Logged in'),
	(216,1,1,'admin','192.168.0.90',1393516699,'Logged in'),
	(217,1,1,'admin','192.168.0.90',1393531897,'Logged in'),
	(218,1,1,'admin','192.168.0.90',1393543686,'Logged in'),
	(219,1,1,'admin','192.168.0.90',1393602326,'Logged in'),
	(220,1,1,'admin','192.168.0.90',1393609950,'Logged in'),
	(221,1,1,'admin','192.168.0.90',1393701051,'Logged in'),
	(222,1,1,'admin','192.168.0.90',1393701070,'Field Group Created:&nbsp;Ink'),
	(223,1,1,'admin','192.168.0.90',1393701576,'Category Group Created:&nbsp;&nbsp;Ink Properties'),
	(224,1,1,'admin','192.168.0.90',1393701750,'Channel Created:&nbsp;&nbsp;Ink'),
	(225,1,1,'admin','192.168.0.90',1393709248,'Logged in'),
	(226,1,1,'admin','192.168.0.90',1393718838,'Logged in'),
	(227,1,1,'admin','192.168.0.90',1393729865,'Logged in'),
	(228,1,1,'admin','192.168.0.90',1393729910,'Field Group Created:&nbsp;Pen'),
	(229,1,1,'admin','192.168.0.90',1393730168,'Field Group Created:&nbsp;Pen Rotation'),
	(230,1,1,'admin','192.168.0.90',1393730256,'Channel Created:&nbsp;&nbsp;Pen'),
	(231,1,1,'admin','192.168.0.90',1393730289,'Channel Created:&nbsp;&nbsp;Pen Rotation'),
	(232,1,1,'admin','192.168.0.90',1393813874,'Logged in'),
	(233,1,1,'admin','192.168.0.90',1393813878,'Logged in'),
	(234,1,1,'admin','192.168.0.90',1393827957,'Logged in'),
	(235,1,1,'admin','192.168.0.90',1393864866,'Logged in'),
	(236,1,1,'admin','192.168.0.90',1393875818,'Logged in'),
	(237,1,1,'admin','192.168.0.90',1393889090,'Logged in'),
	(238,1,1,'admin','192.168.0.90',1393960993,'Logged in'),
	(239,1,1,'admin','192.168.0.90',1393966092,'Logged in'),
	(240,1,1,'admin','192.168.0.90',1393970930,'Logged in'),
	(241,1,1,'admin','192.168.0.90',1394057026,'Logged in'),
	(242,1,1,'admin','192.168.0.90',1394124593,'Logged in'),
	(243,1,1,'admin','192.168.0.90',1394133950,'Logged in'),
	(244,1,1,'admin','192.168.0.90',1394138314,'Logged in'),
	(245,1,1,'admin','192.168.0.90',1394142294,'Logged in'),
	(246,1,1,'admin','192.168.0.90',1394222517,'Logged in'),
	(247,1,1,'admin','192.168.0.90',1394222517,'Logged in'),
	(248,1,1,'admin','192.168.0.90',1394473367,'Logged in'),
	(249,1,1,'admin','192.168.0.90',1394473365,'Logged in'),
	(250,1,1,'admin','192.168.0.90',1394481932,'Logged in'),
	(251,1,1,'admin','192.168.0.90',1394492669,'Logged in'),
	(252,1,1,'admin','192.168.0.90',1394559458,'Logged in'),
	(253,1,1,'admin','192.168.0.90',1394573654,'Logged in'),
	(254,1,1,'admin','192.168.0.90',1394596632,'Logged in'),
	(255,1,1,'admin','192.168.0.90',1394596630,'Logged in'),
	(256,1,1,'admin','192.168.0.90',1394647370,'Logged in'),
	(257,1,1,'admin','192.168.0.90',1394654813,'Logged in'),
	(258,1,1,'admin','192.168.0.90',1394658732,'Logged in'),
	(259,1,1,'admin','192.168.0.90',1394727851,'Logged in'),
	(260,1,1,'admin','192.168.0.90',1394732346,'Logged in'),
	(261,1,1,'admin','192.168.0.90',1394736858,'Logged in'),
	(262,1,1,'admin','192.168.0.90',1394742267,'Logged in'),
	(263,1,1,'admin','192.168.0.90',1394820340,'Logged in'),
	(264,1,1,'admin','192.168.0.90',1394829149,'Logged in'),
	(265,1,1,'admin','192.168.0.90',1395081004,'Logged in'),
	(266,1,1,'admin','192.168.0.90',1395088589,'Logged in'),
	(267,1,1,'admin','192.168.0.90',1395166946,'Logged in'),
	(268,1,1,'admin','192.168.0.90',1395167056,'Member Group Created:&nbsp;&nbsp;Admins'),
	(269,1,1,'admin','192.168.0.90',1395167123,'Member profile created:&nbsp;&nbsp;t_admin'),
	(270,1,1,'admin','192.168.0.90',1395167208,'Category Group Created:&nbsp;&nbsp;Secondary Upload Types'),
	(271,1,1,'admin','192.168.0.90',1395167267,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(272,1,3,'t_admin','192.168.0.90',1395167454,'Logged in'),
	(273,1,1,'admin','192.168.0.90',1395167499,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(274,1,3,'t_admin','192.168.0.90',1395167511,'Logged out'),
	(275,1,3,'t_admin','192.168.0.90',1395167514,'Logged in'),
	(276,1,1,'admin','192.168.0.90',1395173948,'Logged in'),
	(277,1,3,'t_admin','192.168.0.90',1395173957,'Logged in'),
	(278,1,3,'t_admin','192.168.0.90',1395174144,'Logged out'),
	(279,1,3,'t_admin','192.168.0.90',1395174167,'Logged in'),
	(280,1,1,'admin','192.168.0.90',1395174554,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(281,1,1,'admin','192.168.0.90',1395175013,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(282,1,1,'admin','192.168.0.90',1395175337,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(283,1,3,'t_admin','192.168.0.90',1395175355,'Logged out'),
	(284,1,3,'t_admin','192.168.0.90',1395175359,'Logged in'),
	(285,1,1,'admin','192.168.0.90',1395175423,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(286,1,1,'admin','192.168.0.90',1395176583,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(287,1,1,'admin','192.168.0.90',1395177646,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(288,1,1,'admin','192.168.0.90',1395177670,'Member Group Updated:&nbsp;&nbsp;Admins'),
	(289,1,1,'admin','192.168.0.90',1395246058,'Logged in'),
	(290,1,3,'t_admin','192.168.0.90',1395347158,'Logged in'),
	(291,1,1,'admin','192.168.0.90',1395347803,'Logged in'),
	(292,1,1,'admin','192.168.0.90',1395672824,'Logged in'),
	(293,1,1,'admin','192.168.0.90',1395695372,'Logged in'),
	(294,1,1,'admin','192.168.0.90',1395774603,'Logged in'),
	(295,1,1,'admin','192.168.0.90',1395850111,'Logged in'),
	(296,1,1,'admin','192.168.0.90',1395850219,'Logged in'),
	(297,1,1,'admin','192.168.0.90',1395850293,'Logged in'),
	(298,1,1,'admin','192.168.0.90',1395856780,'Logged in'),
	(299,1,1,'admin','192.168.0.90',1395860257,'Logged in'),
	(300,1,1,'admin','192.168.0.90',1395940744,'Logged in'),
	(301,1,1,'admin','192.168.0.90',1395944681,'Logged in'),
	(302,1,1,'admin','192.168.0.90',1395953229,'Logged in'),
	(303,1,1,'admin','192.168.0.90',1395957256,'Logged in'),
	(304,1,1,'admin','192.168.0.90',1396026010,'Logged in'),
	(305,1,1,'admin','192.168.0.90',1396036169,'Logged in'),
	(306,1,1,'admin','192.168.0.90',1396305456,'Logged in'),
	(307,1,1,'admin','192.168.0.90',1396386920,'Logged in'),
	(308,1,1,'admin','192.168.0.90',1396387889,'Logged out'),
	(309,1,1,'admin','192.168.0.90',1396477878,'Logged in'),
	(310,1,1,'admin','192.168.0.90',1396553802,'Logged in'),
	(311,1,1,'admin','192.168.0.90',1396563830,'Logged in'),
	(312,1,1,'admin','192.168.0.90',1396973923,'Logged in'),
	(313,1,1,'admin','192.168.0.90',1396991478,'Logged in'),
	(314,1,1,'admin','192.168.0.90',1397068032,'Logged in'),
	(315,1,1,'admin','192.168.0.90',1397068055,'Logged in'),
	(316,1,1,'admin','192.168.0.90',1397073831,'Logged in'),
	(317,1,1,'admin','192.168.0.90',1397167255,'Logged in'),
	(318,1,1,'admin','192.168.0.90',1397237412,'Logged in'),
	(319,1,1,'admin','192.168.0.90',1397242549,'Logged in'),
	(320,1,1,'admin','192.168.0.90',1397250248,'Logged in'),
	(321,1,1,'admin','192.168.0.90',1397494126,'Logged in'),
	(322,1,1,'admin','192.168.0.90',1397499396,'Logged in'),
	(323,1,1,'admin','192.168.0.90',1397499782,'Logged in'),
	(324,1,1,'admin','192.168.0.90',1397580737,'Logged in'),
	(325,1,3,'t_admin','192.168.0.90',1397667267,'Logged in'),
	(326,1,3,'t_admin','192.168.0.90',1397667323,'Logged out'),
	(327,1,1,'admin','192.168.0.90',1397667326,'Logged in'),
	(328,1,1,'admin','192.168.0.90',1397680668,'Logged in'),
	(329,1,1,'admin','192.168.0.90',1397686028,'Logged in'),
	(330,1,1,'admin','192.168.0.90',1397756536,'Logged in'),
	(331,1,1,'admin','192.168.0.90',1397764116,'Logged in'),
	(332,1,1,'admin','192.168.0.90',1398785208,'Logged in'),
	(333,1,1,'admin','192.168.0.90',1398881122,'Logged in'),
	(334,1,1,'admin','192.168.0.90',1398882733,'Logged in'),
	(335,1,1,'admin','192.168.0.90',1399060695,'Logged in'),
	(336,1,1,'admin','192.168.0.90',1399061264,'Logged in'),
	(337,1,1,'admin','192.168.0.90',1399068034,'Logged in'),
	(338,1,1,'admin','192.168.0.90',1399399303,'Logged in'),
	(339,1,1,'admin','192.168.0.90',1399994499,'Logged in'),
	(340,1,1,'admin','192.168.0.90',1399994499,'Logged in'),
	(341,1,1,'admin','192.168.0.90',1400699662,'Logged in'),
	(342,1,1,'admin','192.168.0.90',1400705234,'Logged in'),
	(343,1,1,'admin','192.168.0.90',1400775057,'Logged in'),
	(350,1,1,'admin','192.168.0.90',1402328780,'Logged in'),
	(351,1,1,'admin','192.168.0.90',1402334379,'Logged in'),
	(352,1,1,'admin','192.168.0.90',1402334509,'Field Group Created:&nbsp;Family Tree'),
	(353,1,1,'admin','192.168.0.90',1402334718,'Channel Created:&nbsp;&nbsp;People'),
	(354,1,1,'admin','192.168.0.90',1402346592,'Logged in'),
	(355,1,1,'admin','192.168.0.90',1402349062,'Logged in'),
	(356,1,1,'admin','192.168.0.90',1402421162,'Logged in'),
	(357,1,1,'admin','192.168.0.90',1402430299,'Logged in'),
	(358,1,1,'admin','192.168.0.90',1402504438,'Logged in'),
	(359,1,1,'admin','192.168.0.90',1402520181,'Logged in'),
	(360,1,1,'admin','192.168.0.90',1402591696,'Logged in'),
	(361,1,1,'admin','192.168.0.90',1402681493,'Logged in');

/*!40000 ALTER TABLE `exp_cp_log` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
