-- shopify_variation_fix --
ALTER TABLE phppos_items ADD COLUMN ecommerce_first_variation_id VARCHAR(255) NULL DEFAULT NULL;
