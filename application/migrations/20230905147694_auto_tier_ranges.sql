-- auto_tier_ranges --
CREATE TABLE `phppos_auto_tier_ranges` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `tier_id` int(10) NOT NULL,
  `min_sales` decimal(23,10) NOT NULL,
  `max_sales` decimal(23,10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tier_id` (`tier_id`),
  CONSTRAINT `phppos_auto_tier_ranges_ibfk_1` FOREIGN KEY (`tier_id`) REFERENCES `phppos_price_tiers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
