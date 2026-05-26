<?php
/**
 * Migración 024: Modernización Completa del Sistema
 * 
 * Incluye:
 * - Tablas de autenticación mejorada
 * - Sistema de suscripciones
 * - Planeación estratégica
 * - Flujo de efectivo
 * - Entidades financieras
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Modernizacion_completa extends CI_Migration {
    
    public function up() {
        // ============================================
        // TABLAS DE AUTENTICACIÓN
        // ============================================
        
        // Tabla de intentos de login (rate limiting)
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'username' => ['type' => 'VARCHAR', 'constraint' => 255],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'attempt_time' => ['type' => 'DATETIME'],
            'success' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(['username', 'attempt_time']);
        $this->dbforge->create_table('login_attempts');
        
        // Tabla de tokens "Recordarme"
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11],
            'token' => ['type' => 'VARCHAR', 'constraint' => 255],
            'expires_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME']
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('employee_id');
        $this->dbforge->add_key('token');
        $this->dbforge->create_table('remember_tokens');
        
        // Tabla de logs de auditoría de autenticación
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11],
            'username' => ['type' => 'VARCHAR', 'constraint' => 255],
            'action' => ['type' => 'VARCHAR', 'constraint' => 50], // LOGIN, LOGOUT, FAILED, etc.
            'details' => ['type' => 'TEXT', 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME']
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key(['employee_id', 'created_at']);
        $this->dbforge->create_table('auth_logs');
        
        // ============================================
        // TABLAS DE SUSCRIPCIONES
        // ============================================
        
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11],
            'plan_type' => ['type' => 'VARCHAR', 'constraint' => 50], // trial, mensual, anual, lifetime
            'plan_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'start_date' => ['type' => 'DATETIME'],
            'end_date' => ['type' => 'DATETIME'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'cancellation_reason' => ['type' => 'TEXT', 'null' => true],
            'cancelled_at' => ['type' => 'DATETIME', 'null' => true],
            'renewed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'created_by' => ['type' => 'INT', 'constraint' => 11]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('employee_id');
        $this->dbforge->add_key('status');
        $this->dbforge->create_table('employee_subscriptions');
        
        // ============================================
        // TABLAS DE PLANEACIÓN ESTRATÉGICA
        // ============================================
        
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 200],
            'description' => ['type' => 'TEXT', 'null' => true],
            'type' => ['type' => 'VARCHAR', 'constraint' => 50], // ventas, gastos, general
            'start_date' => ['type' => 'DATE'],
            'end_date' => ['type' => 'DATE'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'created_by' => ['type' => 'INT', 'constraint' => 11],
            'created_at' => ['type' => 'DATETIME']
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('strategic_plans');
        
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'plan_id' => ['type' => 'INT', 'constraint' => 11],
            'period' => ['type' => 'VARCHAR', 'constraint' => 20], // YYYY-MM o YYYY-QN
            'forecast_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'proforma_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'actual_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME', 'null' => true]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('plan_id');
        $this->dbforge->create_table('plan_projections');
        
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'projection_id' => ['type' => 'INT', 'constraint' => 11],
            'original_value' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'adjusted_value' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'adjustment_reason' => ['type' => 'TEXT', 'null' => true],
            'adjusted_by' => ['type' => 'INT', 'constraint' => 11],
            'adjusted_at' => ['type' => 'DATETIME']
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('projection_id');
        $this->dbforge->create_table('projection_adjustments');
        
        // ============================================
        // TABLAS DE FLUJO DE EFECTIVO
        // ============================================
        
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'type' => ['type' => 'VARCHAR', 'constraint' => 20], // caja, banco, billetera, tarjeta
            'account_number' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'initial_balance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'current_balance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'currency' => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'MXN'],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME'],
            'created_by' => ['type' => 'INT', 'constraint' => 11]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('financial_entities');
        
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'entity_id' => ['type' => 'INT', 'constraint' => 11],
            'type' => ['type' => 'VARCHAR', 'constraint' => 20], // income, expense
            'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'payment_method' => ['type' => 'VARCHAR', 'constraint' => 50],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'sale_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'expense_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'transaction_date' => ['type' => 'DATETIME'],
            'created_by' => ['type' => 'INT', 'constraint' => 11]
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('entity_id');
        $this->dbforge->add_key('transaction_date');
        $this->dbforge->add_key('sale_id');
        $this->dbforge->create_table('cash_flow_transactions');
        
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'entity_id' => ['type' => 'INT', 'constraint' => 11],
            'expected_balance' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'actual_balance' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'difference' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'reconciled_by' => ['type' => 'INT', 'constraint' => 11],
            'reconciled_at' => ['type' => 'DATETIME']
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('entity_id');
        $this->dbforge->create_table('cash_flow_reconciliations');
        
        // ============================================
        // CAMPOS ADICIONALES A TABLAS EXISTENTES
        // ============================================
        
        // Agregar campos fiscales a customers
        $fields = [
            'rfc' => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'uso_cfdi' => ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true],
            'regimen_fiscal' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true],
            'codigo_postal' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true]
        ];
        $this->dbforge->add_column('customers', $fields);
        
        // Agregar campos fiscales a locations
        $fields = [
            'rfc_emisor' => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => true],
            'regimen_fiscal' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true]
        ];
        $this->dbforge->add_column('locations', $fields);
        
        // Agregar campos CFDI a items
        $fields = [
            'clave_prod_serv' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'clave_unidad' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'unidad' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true]
        ];
        $this->dbforge->add_column('items', $fields);
        
        // Agregar campo password_hash actualizado a employees
        $fields = [
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
        ];
        $this->dbforge->add_column('employees', $fields);
    }
    
    public function down() {
        // Eliminar tablas nuevas
        $this->dbforge->drop_table('login_attempts');
        $this->dbforge->drop_table('remember_tokens');
        $this->dbforge->drop_table('auth_logs');
        $this->dbforge->drop_table('employee_subscriptions');
        $this->dbforge->drop_table('strategic_plans');
        $this->dbforge->drop_table('plan_projections');
        $this->dbforge->drop_table('projection_adjustments');
        $this->dbforge->drop_table('financial_entities');
        $this->dbforge->drop_table('cash_flow_transactions');
        $this->dbforge->drop_table('cash_flow_reconciliations');
        
        // Eliminar campos agregados
        $this->dbforge->drop_column('customers', 'rfc');
        $this->dbforge->drop_column('customers', 'uso_cfdi');
        $this->dbforge->drop_column('customers', 'regimen_fiscal');
        $this->dbforge->drop_column('customers', 'codigo_postal');
        
        $this->dbforge->drop_column('locations', 'rfc_emisor');
        $this->dbforge->drop_column('locations', 'regimen_fiscal');
        
        $this->dbforge->drop_column('items', 'clave_prod_serv');
        $this->dbforge->drop_column('items', 'clave_unidad');
        $this->dbforge->drop_column('items', 'unidad');
        
        $this->dbforge->drop_column('employees', 'password_hash');
    }
}
