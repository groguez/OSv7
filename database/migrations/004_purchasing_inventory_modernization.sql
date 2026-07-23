-- Modernización de Compras e Inventarios
-- Añade trazabilidad, control multidimensional y métricas de proveedores

-- 1. Trazabilidad de Lotes y Caducidad
ALTER TABLE inventory ADD COLUMN IF NOT EXISTS lot_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE inventory ADD COLUMN IF NOT EXISTS expiration_date DATE DEFAULT NULL;
ALTER TABLE receiving ADD COLUMN IF NOT EXISTS lot_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE receiving ADD COLUMN IF NOT EXISTS expiration_date DATE DEFAULT NULL;

-- 2. Stock Multidimensional (Físico, Reservado, En Tránsito)
ALTER TABLE items ADD COLUMN IF NOT EXISTS stock_physical INT DEFAULT 0;
ALTER TABLE items ADD COLUMN IF NOT EXISTS stock_reserved INT DEFAULT 0;
ALTER TABLE items ADD COLUMN IF NOT EXISTS stock_in_transit INT DEFAULT 0;

-- 3. Scorecard de Proveedores
CREATE TABLE IF NOT EXISTS supplier_scorecards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    period_month INT NOT NULL,
    period_year INT NOT NULL,
    on_time_delivery_rate DECIMAL(5,2) DEFAULT 0.00, -- % entregas a tiempo
    price_variance_rate DECIMAL(5,2) DEFAULT 0.00, -- % variación de precios
    quality_rejection_rate DECIMAL(5,2) DEFAULT 0.00, -- % devoluciones/rechazos
    lead_time_avg_days INT DEFAULT 0,
    total_orders INT DEFAULT 0,
    score_total DECIMAL(5,2) DEFAULT 0.00, -- Ponderado final
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_supplier_period (supplier_id, period_month, period_year),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE
);

-- 4. Órdenes de Compra Mejoradas
ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS expected_date DATE DEFAULT NULL;
ALTER TABLE purchase_orders ADD COLUMN IF NOT EXISTS status ENUM('pending', 'partial', 'received', 'cancelled') DEFAULT 'pending';
ALTER TABLE purchase_order_items ADD COLUMN IF NOT EXISTS received_qty INT DEFAULT 0;
ALTER TABLE purchase_order_items ADD COLUMN IF NOT EXISTS unit_cost_expected DECIMAL(10,2) DEFAULT 0.00;

-- 5. Tabla de Desviaciones en Recepción
CREATE TABLE IF NOT EXISTS receiving_discrepancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receiving_id INT NOT NULL,
    item_id INT NOT NULL,
    expected_qty INT NOT NULL,
    received_qty INT NOT NULL,
    expected_cost DECIMAL(10,2) NOT NULL,
    actual_cost DECIMAL(10,2) NOT NULL,
    discrepancy_type ENUM('quantity_short', 'quantity_over', 'price_variance', 'quality_issue') NOT NULL,
    notes TEXT,
    resolved_by INT DEFAULT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receiving_id) REFERENCES receiving(receiving_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
);

-- Índices para rendimiento
CREATE INDEX IF NOT EXISTS idx_inventory_expiration ON inventory(expiration_date);
CREATE INDEX IF NOT EXISTS idx_items_stock_levels ON items(stock_physical, stock_reserved, stock_in_transit);
CREATE INDEX IF NOT EXISTS idx_supplier_scores ON supplier_scorecards(supplier_id, period_year, period_month);
