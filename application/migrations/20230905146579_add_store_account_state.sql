-- add_store_account_state --
ALTER TABLE `phppos_store_accounts` ADD `state` INT(1) NOT NULL DEFAULT '0' AFTER `comment`;

ALTER TABLE `phppos_supplier_store_accounts` ADD `state` INT(1) NOT NULL DEFAULT '0' AFTER `comment`;