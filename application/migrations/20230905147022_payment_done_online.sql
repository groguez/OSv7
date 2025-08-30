-- payment_done_online --
ALTER TABLE phppos_sales_payments ADD COLUMN paid_online INT(1) DEFAULT 0;