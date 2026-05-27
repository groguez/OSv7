<?php
class Migration_Add_roles_and_store_subscriptions extends CI_Migration
{
    public function up()
    {
        // Agregar campo role a la tabla employees
        $this->db->query("
            ALTER TABLE `phppos_employees` 
            ADD COLUMN `role` enum('super_admin','admin','employee') NOT NULL DEFAULT 'employee' AFTER `person_id`
        ");
        
        // Agregar campos de suscripción a la tabla stores
        $this->db->query("
            ALTER TABLE `phppos_stores` 
            ADD COLUMN `subscription_status` enum('active','trial','cancelled','expired','failed') NOT NULL DEFAULT 'trial' AFTER `store_name`,
            ADD COLUMN `subscription_plan` varchar(50) DEFAULT 'basic' AFTER `subscription_status`,
            ADD COLUMN `subscription_start_date` date DEFAULT NULL AFTER `subscription_plan`,
            ADD COLUMN `subscription_end_date` date DEFAULT NULL AFTER `subscription_start_date`,
            ADD COLUMN `trial_end_date` date DEFAULT NULL AFTER `subscription_end_date`,
            ADD COLUMN `subscription_amount` decimal(10,2) DEFAULT 0.00 AFTER `trial_end_date`,
            ADD COLUMN `billing_cycle` enum('monthly','annual','lifetime') DEFAULT 'monthly' AFTER `subscription_amount`,
            ADD COLUMN `grace_period_end` date DEFAULT NULL AFTER `billing_cycle`
        ");
        
        // Crear tabla para logs del modo Ghost
        $this->db->query("
            CREATE TABLE `phppos_ghost_mode_logs` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `admin_person_id` int(10) UNSIGNED NOT NULL,
                `target_person_id` int(10) UNSIGNED NOT NULL,
                `action` varchar(100) NOT NULL,
                `timestamp` datetime NOT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `details` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_admin` (`admin_person_id`),
                KEY `idx_target` (`target_person_id`),
                KEY `idx_timestamp` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        ");
        
        // Establecer al empleado ID 1 como super_admin por defecto
        $this->db->query("
            UPDATE `phppos_employees` 
            SET `role` = 'super_admin' 
            WHERE `person_id` = 1
        ");
        
        // Agregar permisos para Super Admin
        $this->db->query("
            INSERT INTO `phppos_modules` (`module_id`, `name_lang_key`, `description_lang_key`, `sort`) 
            VALUES 
                ('super_admin', 'module_super_admin', 'module_super_admin_desc', 100),
                ('global_config', 'module_global_config', 'module_global_config_desc', 98)
            ON DUPLICATE KEY UPDATE `name_lang_key`=`name_lang_key`
        ");
        
        // Acciones para módulo Super Admin
        $this->db->query("
            INSERT INTO `phppos_module_actions` (`action_id`, `module_id`, `name_lang_key`, `sort`) 
            VALUES 
                ('super_admin_view', 'super_admin', 'super_admin_view', 1),
                ('super_admin_manage_users', 'super_admin', 'super_admin_manage_users', 2),
                ('super_admin_manage_stores', 'super_admin', 'super_admin_manage_stores', 3),
                ('super_admin_ghost_mode', 'super_admin', 'super_admin_ghost_mode', 4),
                ('global_config_edit', 'global_config', 'global_config_edit', 1),
                ('global_config_view', 'global_config', 'global_config_view', 2)
            ON DUPLICATE KEY UPDATE `name_lang_key`=`name_lang_key`
        ");
    }
    
    public function down()
    {
        // Eliminar campos agregados
        $this->db->query("ALTER TABLE `phppos_employees` DROP COLUMN `role`");
        
        $this->db->query("
            ALTER TABLE `phppos_stores` 
            DROP COLUMN `subscription_status`,
            DROP COLUMN `subscription_plan`,
            DROP COLUMN `subscription_start_date`,
            DROP COLUMN `subscription_end_date`,
            DROP COLUMN `trial_end_date`,
            DROP COLUMN `subscription_amount`,
            DROP COLUMN `billing_cycle`,
            DROP COLUMN `grace_period_end`
        ");
        
        // Eliminar tabla de logs Ghost
        $this->db->query("DROP TABLE IF EXISTS `phppos_ghost_mode_logs`");
        
        // Eliminar módulos y acciones
        $this->db->query("DELETE FROM `phppos_module_actions` WHERE `module_id` IN ('super_admin', 'global_config')");
        $this->db->query("DELETE FROM `phppos_modules` WHERE `module_id` IN ('super_admin', 'global_config')");
    }
}
