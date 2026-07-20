-- add_ecommerce_fields --
SET foreign_key_checks = 0;
ALTER TABLE `phppos_sales` ADD `ecommerce_restock` INT NULL DEFAULT NULL COMMENT 'NULL : None (Delete Order)\r\n1: Restock inventory (Cancel Order)\r\n2: Do not restock inventory (Cancel Order)\r\n' AFTER `deleted`;
ALTER TABLE `phppos_sales` ADD `ecommerce_reason` VARCHAR(255) NULL AFTER `ecommerce_status`;
ALTER TABLE `phppos_sales` ADD `ecommerce_financial_status` VARCHAR(255) NULL AFTER `ecommerce_status`;
SET foreign_key_checks = 1;
