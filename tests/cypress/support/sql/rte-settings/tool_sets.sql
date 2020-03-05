DROP TABLE IF EXISTS `exp_rte_toolsets`;

CREATE TABLE `exp_rte_toolsets` (
  `toolset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) DEFAULT '0',
  `name` varchar(100) DEFAULT NULL,
  `tools` text,
  `enabled` char(1) DEFAULT 'y',
  PRIMARY KEY (`toolset_id`),
  KEY `member_id` (`member_id`),
  KEY `enabled` (`enabled`)
) DEFAULT CHARSET=utf8;

LOCK TABLES `exp_rte_toolsets` WRITE;
/*!40000 ALTER TABLE `exp_rte_toolsets` DISABLE KEYS */;

INSERT INTO `exp_rte_toolsets` (`toolset_id`, `member_id`, `name`, `tools`, `enabled`)
VALUES
	(1,0,'Default','3|2|5|1|9|7|6|4|10','y'),
	(2,0,'Simple','2|5|8','y'),
	(3,0,'Advanced','2|9|3|4|5|6|7|1|8|10','y'),
	(4,0,'Lists Only','9|7','y'),
	(5,0,'Odd','2|3|5|7|8','y'),
	(6,0,'Even','9|4|6|1|10','y'),
	(7,0,'Everything','2|9|3|4|5|6|7|1|8|10','y');

/*!40000 ALTER TABLE `exp_rte_toolsets` ENABLE KEYS */;
UNLOCK TABLES;
