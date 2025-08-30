-- inventory_index --
CREATE INDEX idx_inventory_composite
ON phppos_inventory (trans_items, location_id, item_variation_id, trans_id DESC);