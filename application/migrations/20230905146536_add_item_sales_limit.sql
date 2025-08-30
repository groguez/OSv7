-- add_item_sales_limit --
	ALTER TABLE `phppos_items` ADD `limited_sales_quantity` INT NULL AFTER `ecommerce_first_variation_id`;
	ALTER TABLE `phppos_item_variations` ADD `limited_sales_quantity` INT NULL AFTER `supplier_id`;
	ALTER TABLE `phppos_item_kits` ADD `limited_sales_quantity` INT NULL AFTER `non_profit_sale`;
