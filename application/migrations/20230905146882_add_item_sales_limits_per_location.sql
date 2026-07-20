-- add_item_sales_limits_per_location --
	ALTER TABLE `phppos_location_items` ADD `limited_sales_quantity` INT NULL;
	ALTER TABLE `phppos_location_item_variations` ADD `limited_sales_quantity` INT NULL;
	ALTER TABLE `phppos_location_item_kits` ADD `limited_sales_quantity` INT NULL;
