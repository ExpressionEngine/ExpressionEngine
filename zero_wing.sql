--
-- Table structure for table `exp_zero_wing`
--
CREATE TABLE `exp_zero_wing` (
  `relationship_id` int(10) unsigned NOT NULL,
  `entry_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`relationship_id`,`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `exp_zero_wing`
--
INSERT INTO `exp_zero_wing` VALUES (1,2),(1,3),(1,4),(2,5),(2,6),(2,7),(3,9),(4,8),(5,8),(6,9),(7,8),(7,9),(8,10),(8,11);
