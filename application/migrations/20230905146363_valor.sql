-- valor --
ALTER TABLE `phppos_locations` ADD `valor_appid`  varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE `phppos_registers` ADD `valor_appkey` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL;

ALTER TABLE `phppos_sales_payments` ADD `tran_no` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT '' AFTER `ref_no`;


ALTER TABLE `phppos_customers` ADD `valor_vault_id` TEXT NULL;
