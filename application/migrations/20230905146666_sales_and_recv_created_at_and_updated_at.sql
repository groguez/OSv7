-- sales_and_recv_created_at_and_updated_at --
ALTER TABLE `phppos_sales` 
ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL;

update phppos_sales SET created_at = sale_time;

ALTER TABLE `phppos_receivings` 
ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL;

update phppos_receivings set created_at = receiving_time;