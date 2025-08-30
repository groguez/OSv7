-- datacap_device_override_per_register --
ALTER TABLE `phppos_registers` ADD `secure_device_emv` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `phppos_registers` ADD `secure_device_non_emv` VARCHAR(255) NULL DEFAULT NULL;
