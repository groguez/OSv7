-- add_price_tiers_to_categories --

CREATE TABLE `phppos_categories_tier_prices` (
  `tier_id` int(10) NOT NULL,
  `category_id` int(10) NOT NULL,
  `unit_price` decimal(23,10) DEFAULT 0.0000000000,
  `percent_off` decimal(15,3) DEFAULT NULL,
  `cost_plus_percent` decimal(15,3) DEFAULT NULL,
  `cost_plus_fixed_amount` decimal(23,10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `phppos_categories_tier_prices`
--
ALTER TABLE `phppos_categories_tier_prices`
  ADD PRIMARY KEY (`tier_id`,`category_id`),
  ADD KEY `phppos_items_tier_prices_ibfk_2` (`category_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `phppos_categories_tier_prices`
--
ALTER TABLE `phppos_categories_tier_prices`
  ADD CONSTRAINT `phppos_categories_tier_prices_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `phppos_categories` (`id`),
  ADD CONSTRAINT `phppos_categories_tier_prices_ibfk_2` FOREIGN KEY (`tier_id`) REFERENCES `phppos_price_tiers` (`id`);
COMMIT;