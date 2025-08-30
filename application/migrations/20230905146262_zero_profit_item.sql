-- zero_profit_item --
ALTER TABLE `phppos_items` ADD `non_profit_sale` INT(11) NOT NULL DEFAULT '0' AFTER `shopify_item_level_inventory_policy`;
ALTER TABLE `phppos_item_kits` ADD `non_profit_sale` INT(11) NOT NULL DEFAULT '0' AFTER `loyalty_multiplier`;