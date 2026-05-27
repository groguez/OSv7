<?php
class Migration_Add_employee_subscriptions extends CI_Migration
{
    public function up()
    {
        // Crear tabla de suscripciones de empleados
        $this->db->query("
            CREATE TABLE `phppos_employee_subscriptions` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `employee_id` int(10) UNSIGNED NOT NULL,
                `subscription_type` enum('trial','monthly','annual','lifetime') NOT NULL DEFAULT 'trial',
                `status` enum('active','trial','cancelled','failed','expired') NOT NULL DEFAULT 'trial',
                `start_date` date NOT NULL,
                `end_date` date DEFAULT NULL,
                `cancel_date` datetime DEFAULT NULL,
                `cancel_reason` text DEFAULT NULL,
                `payment_method` varchar(50) DEFAULT NULL,
                `payment_token` varchar(255) DEFAULT NULL,
                `amount` decimal(10,2) DEFAULT NULL,
                `currency` varchar(10) DEFAULT 'USD',
                `billing_cycle_day` tinyint(2) DEFAULT NULL,
                `next_billing_date` date DEFAULT NULL,
                `last_payment_date` date DEFAULT NULL,
                `last_payment_amount` decimal(10,2) DEFAULT NULL,
                `trial_end_date` date DEFAULT NULL,
                `grace_period_days` int(3) DEFAULT 3,
                `auto_renew` tinyint(1) DEFAULT 1,
                `notes` text DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_employee` (`employee_id`),
                KEY `idx_status` (`status`),
                KEY `idx_subscription_type` (`subscription_type`),
                KEY `idx_end_date` (`end_date`),
                CONSTRAINT `fk_employee_sub_employee` FOREIGN KEY (`employee_id`) REFERENCES `phppos_employees` (`person_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
        
        // Agregar permisos para gestión de suscripciones
        $this->db->query("
            INSERT INTO `phppos_modules` (`module_id`, `name_lang_key`, `description_lang_key`, `sort`) 
            VALUES ('subscriptions', 'module_subscriptions', 'module_subscriptions_desc', 99)
            ON DUPLICATE KEY UPDATE `name_lang_key`='module_subscriptions'
        ");
        
        // Agregar acciones de permisos
        $this->db->query("
            INSERT INTO `phppos_module_actions` (`action_id`, `module_id`, `name_lang_key`, `sort`) 
            VALUES 
                ('subscriptions_view', 'subscriptions', 'subscriptions_view', 1),
                ('subscriptions_edit', 'subscriptions', 'subscriptions_edit', 2),
                ('subscriptions_delete', 'subscriptions', 'subscriptions_delete', 3),
                ('subscriptions_manage_plans', 'subscriptions', 'subscriptions_manage_plans', 4)
            ON DUPLICATE KEY UPDATE `name_lang_key`=`name_lang_key`
        ");
    }
    
    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `phppos_employee_subscriptions`");
        $this->db->query("DELETE FROM `phppos_module_actions` WHERE `module_id` = 'subscriptions'");
        $this->db->query("DELETE FROM `phppos_modules` WHERE `module_id` = 'subscriptions'");
    }
}
