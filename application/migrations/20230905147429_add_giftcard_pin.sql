-- add_giftcard_pin --
ALTER TABLE `phppos_giftcards` ADD `giftcard_pin` VARCHAR(255) NULL AFTER `integrated_auth_code`;