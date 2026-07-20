-- item_kits_dynamic_pricing_flex --
ALTER TABLE phppos_item_kits
ADD COLUMN dynamic_cost_price TINYINT(1) DEFAULT 0,
ADD COLUMN dynamic_unit_price TINYINT(1) DEFAULT 0;

update phppos_item_kits SET dynamic_cost_price = 1 WHERE dynamic_pricing = 1;
update phppos_item_kits SET dynamic_unit_price = 1 WHERE dynamic_pricing = 1;