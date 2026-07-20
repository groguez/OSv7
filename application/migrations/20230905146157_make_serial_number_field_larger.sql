-- make_serial_number_field_larger --
ALTER TABLE `phppos_sales_items` DROP INDEX `serialnumber`;
ALTER TABLE `phppos_sales_items` CHANGE `serialnumber` `serialnumber` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `phppos_sales_items` ADD INDEX `serialnumber` (`serialnumber`(255));
