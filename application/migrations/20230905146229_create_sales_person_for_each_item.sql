-- create_sales_person_for_each_item --
ALTER TABLE `phppos_sales_items` ADD `sold_by_employee_id_2` INT(10) NULL DEFAULT NULL AFTER `is_repair_item`;
ALTER TABLE `phppos_sales_items` ADD FOREIGN KEY (`sold_by_employee_id_2`) REFERENCES `phppos_employees`(`person_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

ALTER TABLE `phppos_sales_item_kits` ADD `sold_by_employee_id_2` INT(10) NULL AFTER `is_repair_item`;
ALTER TABLE `phppos_sales_item_kits` ADD FOREIGN KEY (`sold_by_employee_id_2`) REFERENCES `phppos_employees`(`person_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
