-- additional_item_number_supplier --
ALTER TABLE phppos_additional_item_numbers ADD COLUMN supplier_id INT(11) NULL DEFAULT NULL,
ADD CONSTRAINT `phppos_additional_item_numbers_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `phppos_suppliers` (`person_id`);