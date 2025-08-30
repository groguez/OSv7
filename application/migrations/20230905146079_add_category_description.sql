-- add_category_description --
ALTER TABLE `phppos_categories` ADD `category_description` TEXT NULL DEFAULT NULL AFTER `category_info_popup`;