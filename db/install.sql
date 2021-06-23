CREATE TABLE IF NOT EXISTS `[prefix]salesbeat_order` (
  `id` int(11) not null auto_increment,
  `order_id` varchar(255) DEFAULT NULL,
  `sb_order_id` varchar(255) DEFAULT NULL,
  `track_code` varchar(255) DEFAULT NULL,
  `date_order` DATETIME,
  `sent_courier` tinyint(1) DEFAULT NULL,
  `date_courier` DATETIME,
  `tracking_status` varchar(255) DEFAULT NULL,
  `date_tracking` DATETIME,
  PRIMARY KEY(`id`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;