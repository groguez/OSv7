-- customers_additional_phone_numbers --
CREATE TABLE phppos_customers_phone_numbers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    phone_customer_id INT(11),
    additional_phone_number varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    KEY `additional_phone_number` (`additional_phone_number`),
    FOREIGN KEY (phone_customer_id) REFERENCES phppos_customers(person_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;