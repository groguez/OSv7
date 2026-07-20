ALTER TABLE phppos_locations
ADD COLUMN do_not_allow_below_cost TINYINT(1) DEFAULT NULL,
ADD COLUMN do_not_allow_out_of_stock_items_to_be_sold TINYINT(1) DEFAULT NULL,
ADD COLUMN do_not_allow_items_to_go_out_of_stock_when_transfering TINYINT(1) DEFAULT NULL;
