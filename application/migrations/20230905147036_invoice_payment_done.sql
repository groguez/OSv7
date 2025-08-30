-- invoice_payment_done --
ALTER TABLE phppos_customer_invoice_payments ADD COLUMN paid_online INT(1) DEFAULT 0;