--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `username` varchar(50) NOT NULL,
  `password` varchar(35) NOT NULL,
  `apiuser` int(10) unsigned NOT NULL,
  `apikey` text NOT NULL,
  `corp_name` text NOT NULL,
  `ally_name` text,
  `charID` int(10) unsigned NOT NULL,
  `corpID` int(10) unsigned NOT NULL,
  `allyID` int(10) unsigned default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
