-- Migración: Módulo de Contabilidad Inteligente y Conciliación
-- OneBox v7 - Feature: Modernización 2026

CREATE TABLE IF NOT EXISTS `onebox_chart_of_accounts` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0, -- Cuentas bloqueadas por el sistema
  `is_active` tinyint(1) DEFAULT 1,
  `tax_category` varchar(50) DEFAULT NULL, -- Para mapeo automático de impuestos
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `unique_code` (`account_code`),
  KEY `idx_type` (`account_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `onebox_journal_entries` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` datetime NOT NULL,
  `reference_type` varchar(50) NOT NULL, -- 'SALE', 'PURCHASE', 'EXPENSE', 'CONSOLIDATION'
  `reference_id` int(11) NOT NULL, -- ID de la venta, compra, etc.
  `description` varchar(255) NOT NULL,
  `is_posted` tinyint(1) DEFAULT 0,
  `is_fiscal` tinyint(1) DEFAULT 0, -- ¿Tiene CFDI asociado?
  `cfdi_uuid` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  KEY `idx_ref` (`reference_type`, `reference_id`),
  KEY `idx_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `onebox_journal_lines` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,4) DEFAULT 0.0000,
  `credit` decimal(15,4) DEFAULT 0.0000,
  `cost_center` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`line_id`),
  KEY `idx_entry` (`entry_id`),
  CONSTRAINT `fk_journal_entry` FOREIGN KEY (`entry_id`) REFERENCES `onebox_journal_entries` (`entry_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla Clave: Cola de Conciliación Inteligente
CREATE TABLE IF NOT EXISTS `onebox_reconciliation_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(50) NOT NULL, -- 'SALE', 'PURCHASE'
  `transaction_id` int(11) NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `tax_amount` decimal(15,4) DEFAULT 0.0000,
  `customer_supplier_id` int(11) DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `status` enum('PENDING_REVIEW','AUTO_SUGGESTED','FACTURED','NON_FACTURED_IGNORED','NON_FACTURED_GLOBAL') DEFAULT 'PENDING_REVIEW',
  `suggestion_reason` varchar(255) DEFAULT NULL, -- Por qué el sistema sugiere esto
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `consolidation_batch_id` int(11) DEFAULT NULL, -- Para agrupar no facturados
  PRIMARY KEY (`queue_id`),
  UNIQUE KEY `unique_trans` (`transaction_type`, `transaction_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para Lotes de Facturación Global (No facturados)
CREATE TABLE IF NOT EXISTS `onebox_consolidation_batches` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_date` date NOT NULL,
  `total_amount` decimal(15,4) NOT NULL,
  `total_tax` decimal(15,4) NOT NULL,
  `transaction_count` int(11) NOT NULL,
  `cfdi_uuid` varchar(50) DEFAULT NULL, -- UUID del CFDI global si se genera
  `status` enum('OPEN','CLOSED','TIMBRADO') DEFAULT 'OPEN',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar Catálogo de Cuentas Base (Simplificado para PyMES)
INSERT INTO `onebox_chart_of_accounts` (`account_code`, `account_name`, `account_type`, `is_system`, `tax_category`) VALUES
('1000', 'ACTIVOS', 'ASSET', 1, NULL),
('1100', 'Bancos', 'ASSET', 1, NULL),
('1101', 'Banco BBVA', 'ASSET', 0, NULL),
('1200', 'Clientes', 'ASSET', 1, NULL),
('1300', 'Inventarios', 'ASSET', 1, NULL),
('2000', 'PASIVOS', 'LIABILITY', 1, NULL),
('2100', 'Proveedores', 'LIABILITY', 1, NULL),
('2200', 'Impuestos por Pagar', 'LIABILITY', 1, 'IVA_TRANSFERIDO'),
('3000', 'CAPITAL CONTABLE', 'EQUITY', 1, NULL),
('3100', 'Capital Social', 'EQUITY', 1, NULL),
('3200', 'Utilidad del Ejercicio', 'EQUITY', 1, NULL),
('4000', 'INGRESOS', 'REVENUE', 1, NULL),
('4100', 'Ventas Mostrador', 'REVENUE', 0, 'IVA_ACRETADO'),
('4200', 'Ventas Facturadas', 'REVENUE', 0, 'IVA_ACRETADO'),
('5000', 'COSTOS Y GASTOS', 'EXPENSE', 1, NULL),
('5100', 'Costo de Ventas', 'EXPENSE', 0, 'IVA_ACREDITABLE'),
('5200', 'Gastos Operativos', 'EXPENSE', 0, 'IVA_ACREDITABLE'),
('5300', 'Compras No Facturadas (Global)', 'EXPENSE', 0, 'IVA_ACREDITABLE');
