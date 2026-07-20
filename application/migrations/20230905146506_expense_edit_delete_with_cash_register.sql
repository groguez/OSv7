-- expense_edit_delete_with_cash_register --
ALTER TABLE `phppos_register_log_audit` ADD `expense_id` INT(10) NULL AFTER `payment_type`;
ALTER TABLE `phppos_expenses` ADD `register_id` INT(11) NULL AFTER `expense_image_id`;

ALTER TABLE `phppos_register_log_audit` ADD FOREIGN KEY (`expense_id`) REFERENCES `phppos_expenses`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `phppos_expenses` ADD FOREIGN KEY (`register_id`) REFERENCES `phppos_registers`(`register_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
