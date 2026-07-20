-- hide_from_deleted_column --
SET foreign_key_checks = 0;
ALTER TABLE `phppos_items` ADD `hide_from_deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `deleted`;
CREATE INDEX `hide_from_deleted` ON `phppos_items` (`hide_from_deleted`);
SET foreign_key_checks = 1;