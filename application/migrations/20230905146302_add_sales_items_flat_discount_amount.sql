-- add_sales_items_flat_discount_amount --
ALTER TABLE `phppos_sales_items` ADD `flat_discount_amount` decimal(15, 10) NOT NULL DEFAULT 0.00000000 AFTER `discount_percent`;