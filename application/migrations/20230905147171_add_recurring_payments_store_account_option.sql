-- add_recurring_payments_store_account_option --
ALTER TABLE `phppos_customer_subscriptions` ADD `payment_option` INT(1) NOT NULL DEFAULT '1' COMMENT '1 - credit card\r\n2 - store account' AFTER `retries_attempted`;
