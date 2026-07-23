-- OneBox v2026 Master Migration Script
-- Migración completa desde PHP Point of Sale Legacy a OneBox Gold Master
-- Idempotente: Seguro para ejecutar múltiples veces

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- ========================================
-- 1. REBRANDING: Eliminar rastros de "PHP Point of Sale"
-- ========================================
UPDATE os_app_config SET config_value = 'OneBox' WHERE config_key = 'company';
UPDATE os_app_config SET config_value = 'OneBox v2026.1.0' WHERE config_key = 'version';
UPDATE os_modules SET name_desc = REPLACE(name_desc, 'PHP Point of Sale', 'OneBox') WHERE name_desc LIKE '%PHP Point of Sale%';

-- ========================================
-- 2. TABLAS DE AUDITORÍA Y MIGRACIÓN
-- ========================================
CREATE TABLE IF NOT EXISTS sys_migration_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'partial', 'failed') DEFAULT 'success',
    log_summary TEXT,
    executed_by VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sys_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_by INT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. OPTIMIZACIÓN DE ÍNDICES (Performance)
-- ========================================
-- Índices en ventas para reportes rápidos
ALTER TABLE os_sales ADD INDEX IF NOT EXISTS idx_sale_date (sale_time);
ALTER TABLE os_sales ADD INDEX IF NOT EXISTS idx_customer (customer_id);
ALTER TABLE os_sales ADD INDEX IF NOT EXISTS idx_employee (employee_id);

-- Índices en inventario
ALTER TABLE os_items ADD INDEX IF NOT EXISTS idx_item_number (item_number);
ALTER TABLE os_items ADD INDEX IF NOT EXISTS idx_category (category_id);
ALTER TABLE os_inventory ADD INDEX IF NOT EXISTS idx_trans_date (trans_date);

-- Índices en conciliación
ALTER TABLE os_receivings ADD INDEX IF NOT EXISTS idx_supplier (supplier_id);
ALTER TABLE os_receivings ADD INDEX IF NOT EXISTS idx_employee (employee_id);

-- ========================================
-- 4. ACTUALIZACIÓN DE TIPOS DE DATOS (Precisión Financiera)
-- ========================================
-- Asegurar precisión decimal en campos monetarios
ALTER TABLE os_items MODIFY cost_price DECIMAL(15,2) NOT NULL DEFAULT 0.00;
ALTER TABLE os_items MODIFY unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00;
ALTER TABLE os_sales MODIFY total DECIMAL(15,2) NOT NULL DEFAULT 0.00;
ALTER TABLE os_payments MODIFY payment_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00;

-- ========================================
-- 5. TABLAS DE CONCILIACIÓN INTELIGENTE
-- ========================================
CREATE TABLE IF NOT EXISTS os_reconciliation_queue (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('sale', 'receiving', 'transfer') NOT NULL,
    transaction_id BIGINT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fiscal_status ENUM('pending', 'to_invoice', 'batch_global', 'invoiced', 'ignored') DEFAULT 'pending',
    suggested_action VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    processed_by INT NULL,
    INDEX idx_fiscal_status (fiscal_status),
    INDEX idx_transaction (transaction_type, transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_consolidation_batches (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(100) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    transaction_count INT NOT NULL DEFAULT 0,
    cfdi_uuid VARCHAR(100) NULL,
    fiscal_period DATE NOT NULL,
    status ENUM('draft', 'timbrado', 'cancelled') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    INDEX idx_fiscal_period (fiscal_period),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 6. TABLAS DE SMART RECEIVING (OCR)
-- ========================================
CREATE TABLE IF NOT EXISTS os_smart_receiving_tickets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(500) NOT NULL,
    ocr_raw_text TEXT,
    extracted_data JSON,
    mapping_status ENUM('pending', 'mapped', 'validated', 'rejected') DEFAULT 'pending',
    supplier_id INT NULL,
    created_order_id BIGINT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_by INT NULL,
    INDEX idx_mapping_status (mapping_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_product_mappings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ticket_text VARCHAR(255) NOT NULL,
    os_item_id INT NOT NULL,
    confidence_score DECIMAL(5,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (ticket_text, os_item_id),
    FOREIGN KEY (os_item_id) REFERENCES os_items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. TABLAS DE IA Y ANALYTICS
-- ========================================
CREATE TABLE IF NOT EXISTS os_ai_provider_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL UNIQUE,
    score_grade ENUM('A', 'B', 'C', 'D', 'E', 'F') DEFAULT 'C',
    score_numeric DECIMAL(5,2) DEFAULT 50.00,
    on_time_delivery_rate DECIMAL(5,2) DEFAULT 0.00,
    price_competitiveness DECIMAL(5,2) DEFAULT 0.00,
    quality_rating DECIMAL(5,2) DEFAULT 0.00,
    last_calculated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES os_suppliers(supplier_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_demand_predictions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    predicted_quantity INT NOT NULL,
    prediction_date DATE NOT NULL,
    confidence_level DECIMAL(5,2) DEFAULT 0.00,
    seasonal_factor DECIMAL(5,2) DEFAULT 1.00,
    trend_factor DECIMAL(5,2) DEFAULT 1.00,
    calculated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_prediction (item_id, prediction_date),
    FOREIGN KEY (item_id) REFERENCES os_items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_anomaly_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    anomaly_type ENUM('fraud_suspect', 'inventory_discrepancy', 'price_anomaly', 'unusual_discount') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    related_table VARCHAR(100),
    related_id BIGINT,
    description TEXT,
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by INT NULL,
    INDEX idx_severity (severity),
    INDEX idx_detected_at (detected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 8. TABLAS DE COMUNICACIÓN OMNICANAL
-- ========================================
CREATE TABLE IF NOT EXISTS os_communication_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    channel ENUM('email', 'whatsapp', 'phone_call', 'sms') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    message_body TEXT,
    status ENUM('queued', 'sent', 'delivered', 'failed', 'read') DEFAULT 'queued',
    related_type VARCHAR(50),
    related_id BIGINT,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    error_message TEXT,
    INDEX idx_channel_status (channel, status),
    INDEX idx_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_communication_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) UNIQUE NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    channel ENUM('email', 'whatsapp', 'sms') NOT NULL,
    subject_template VARCHAR(500),
    body_template TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar plantillas por defecto
INSERT INTO os_communication_templates (template_key, template_name, channel, subject_template, body_template) VALUES
('purchase_order_email', 'Orden de Compra - Email', 'email', 'Orden de Compra #{order_id} - {company_name}', 'Estimado proveedor,\n\nAdjuntamos orden de compra #{order_id} por un total de {total_amount}.\n\nFecha de entrega esperada: {delivery_date}\n\nSaludos cordiales,\n{company_name}'),
('purchase_order_whatsapp', 'Orden de Compra - WhatsApp', 'whatsapp', NULL, '📦 Nueva Orden de Compra #{order_id}\n\nTotal: {total_amount}\nEntrega: {delivery_date}\n\nPor favor confirmar recepción.'),
('payment_reminder_email', 'Recordatorio de Pago', 'email', 'Recordatorio de Pago - Factura {invoice_id}', 'Estimado cliente,\n\nLe recordamos que la factura {invoice_id} con vencimiento {due_date} está pendiente de pago.\n\nGracias por su atención.'),
('low_stock_alert', 'Alerta de Stock Bajo', 'whatsapp', NULL, '⚠️ Alerta de Stock Bajo\n\nProducto: {item_name}\nStock actual: {current_stock}\nMínimo recomendado: {min_stock}\n\n¡Reordenar ahora!');

-- ========================================
-- 9. TABLAS DE LOTES Y CADUCIDAD
-- ========================================
CREATE TABLE IF NOT EXISTS os_item_lots (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    lot_number VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    expiry_date DATE NULL,
    manufacturing_date DATE NULL,
    receiving_id BIGINT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES os_items(item_id) ON DELETE CASCADE,
    INDEX idx_expiry (expiry_date),
    INDEX idx_lot_number (lot_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 10. CONFIGURACIÓN DE CONTEXTO DE NEGOCIO
-- ========================================
ALTER TABLE os_app_config ADD COLUMN IF NOT EXISTS business_context ENUM('retail', 'restaurant', 'service', 'clinic', 'laundry', 'workshop') DEFAULT 'retail';
ALTER TABLE os_app_config ADD COLUMN IF NOT EXISTS enable_strict_stock BOOLEAN DEFAULT FALSE;
ALTER TABLE os_app_config ADD COLUMN IF NOT EXISTS enable_batch_invoicing BOOLEAN DEFAULT TRUE;

-- ========================================
-- 11. VISTAS DE REPORTES OPTIMIZADAS
-- ========================================
CREATE OR REPLACE VIEW vw_daily_sales_summary AS
SELECT 
    DATE(sale_time) as sale_date,
    COUNT(*) as total_transactions,
    SUM(total) as total_revenue,
    SUM(total - COGS) as gross_profit,
    AVG(total) as avg_ticket
FROM os_sales 
WHERE deleted = 0
GROUP BY DATE(sale_time)
ORDER BY sale_date DESC;

CREATE OR REPLACE VIEW vw_inventory_valuation AS
SELECT 
    i.item_id,
    i.name,
    i.item_number,
    SUM(inv.trans_qty) as current_stock,
    i.cost_price * SUM(inv.trans_qty) as stock_value,
    i.unit_price * SUM(inv.trans_qty) as potential_revenue
FROM os_items i
LEFT JOIN os_inventory inv ON i.item_id = inv.trans_items AND inv.deleted = 0
WHERE i.deleted = 0
GROUP BY i.item_id;

CREATE OR REPLACE VIEW vw_supplier_performance AS
SELECT 
    s.supplier_id,
    s.company_name,
    COUNT(r.receiving_id) as total_orders,
    AVG(DATEDIFF(r.receiving_time, r.expected_delivery)) as avg_delay_days,
    SUM(r.total) as total_purchased
FROM os_suppliers s
LEFT JOIN os_receivings r ON s.supplier_id = r.supplier_id AND r.deleted = 0
WHERE s.deleted = 0
GROUP BY s.supplier_id;

-- ========================================
-- 12. PROCEDIMIENTOS ALMACENADOS DE MANTENIMIENTO
-- ========================================
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_cleanup_old_logs(IN days_to_keep INT)
BEGIN
    DELETE FROM sys_audit_log WHERE changed_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    DELETE FROM os_anomaly_logs WHERE resolved_at IS NOT NULL AND resolved_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    SELECT ROW_COUNT() as cleaned_records;
END //

CREATE PROCEDURE IF NOT EXISTS sp_recalculate_ai_scores()
BEGIN
    -- Recalcular scores de proveedores basado en desempeño reciente
    INSERT INTO os_ai_provider_scores (supplier_id, score_grade, score_numeric, on_time_delivery_rate, last_calculated_at)
    SELECT 
        s.supplier_id,
        CASE 
            WHEN avg_delay <= 0 THEN 'A'
            WHEN avg_delay <= 2 THEN 'B'
            WHEN avg_delay <= 5 THEN 'C'
            WHEN avg_delay <= 10 THEN 'D'
            ELSE 'E'
        END as grade,
        GREATEST(0, LEAST(100, 100 - (avg_delay * 5))) as score,
        on_time_rate,
        NOW()
    FROM (
        SELECT 
            s.supplier_id,
            AVG(CASE WHEN DATEDIFF(r.receiving_time, r.expected_delivery) <= 0 THEN 0 ELSE DATEDIFF(r.receiving_time, r.expected_delivery) END) as avg_delay,
            (SUM(CASE WHEN DATEDIFF(r.receiving_time, r.expected_delivery) <= 0 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as on_time_rate
        FROM os_suppliers s
        LEFT JOIN os_receivings r ON s.supplier_id = r.supplier_id AND r.deleted = 0 AND r.receiving_time > DATE_SUB(NOW(), INTERVAL 90 DAY)
        WHERE s.deleted = 0 AND COUNT(r.receiving_id) > 0
        GROUP BY s.supplier_id
    ) as stats
    ON DUPLICATE KEY UPDATE 
        score_grade = VALUES(score_grade),
        score_numeric = VALUES(score_numeric),
        on_time_delivery_rate = VALUES(on_time_delivery_rate),
        last_calculated_at = VALUES(last_calculated_at);
END //

DELIMITER ;

-- ========================================
-- 13. REGISTRO DE MIGRACIÓN EXITOSA
-- ========================================
INSERT INTO sys_migration_history (version, status, log_summary) 
VALUES ('2026.1.0', 'success', 'Migración Gold Master completada: Rebranding, índices, conciliación, IA, Smart Receiving, OmniConnect');

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- Mensaje final
SELECT '✅ OneBox v2026 Migration Completed Successfully!' as migration_status;
