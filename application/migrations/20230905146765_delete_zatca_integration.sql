-- delete_zatca_integration --
DROP TABLE `phppos_customers_zatca`;
DROP TABLE `phppos_zatca_config`;
DROP TABLE `phppos_zatca_invoices`;

ALTER TABLE `phppos_sales` DROP FOREIGN KEY `phppos_sales_ibfk_13`, ALGORITHM=INPLACE, LOCK=NONE;
ALTER TABLE `phppos_sales` DROP COLUMN `ref_sale_id`, DROP COLUMN `ref_sale_desc`, ALGORITHM=INPLACE, LOCK=NONE;
