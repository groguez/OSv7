-- related_items table and preferences
CREATE TABLE phppos_related_items (
  item_id INT(10) NOT NULL,
  related_item_id INT(10) NOT NULL,
  sort_order INT(11) DEFAULT 0,
  PRIMARY KEY (item_id, related_item_id),
  KEY related_item_id (related_item_id),
  CONSTRAINT phppos_related_items_ibfk_1 FOREIGN KEY (item_id) REFERENCES phppos_items(item_id) ON DELETE CASCADE,
  CONSTRAINT phppos_related_items_ibfk_2 FOREIGN KEY (related_item_id) REFERENCES phppos_items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


SET foreign_key_checks = 0;
ALTER TABLE phppos_items
  ADD COLUMN disable_related_sales_prompt TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN disable_related_receivings_prompt TINYINT(1) NOT NULL DEFAULT 0;
SET foreign_key_checks = 1;
