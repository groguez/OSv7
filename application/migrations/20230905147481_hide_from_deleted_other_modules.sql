-- hide_from_deleted_other_modules --
ALTER TABLE `phppos_customers` ADD `hide_from_deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `deleted`;
CREATE INDEX `hide_from_deleted` ON `phppos_customers` (`hide_from_deleted`);

ALTER TABLE `phppos_suppliers` ADD `hide_from_deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `deleted`;
CREATE INDEX `hide_from_deleted` ON `phppos_suppliers` (`hide_from_deleted`);

ALTER TABLE `phppos_employees` ADD `hide_from_deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `deleted`;
CREATE INDEX `hide_from_deleted` ON `phppos_employees` (`hide_from_deleted`);

ALTER TABLE `phppos_item_kits` ADD `hide_from_deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `deleted`;
CREATE INDEX `hide_from_deleted` ON `phppos_item_kits` (`hide_from_deleted`);
