-- update_multiple_item_image.php --
SET FOREIGN_KEY_CHECKS=0;
ALTER TABLE `phppos_items` DROP FOREIGN KEY `phppos_items_ibfk_7`; ALTER TABLE `phppos_items` ADD CONSTRAINT `phppos_items_ibfk_7` FOREIGN KEY (`main_image_id`) REFERENCES `phppos_app_files`(`file_id`) ON DELETE SET NULL ON UPDATE RESTRICT;
SET FOREIGN_KEY_CHECKS=1;