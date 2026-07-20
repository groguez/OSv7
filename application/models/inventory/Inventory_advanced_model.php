<?php
/**
 * Modelo Avanzado de Inventarios
 * Extiende funcionalidades existentes para soportar:
 * - Stock multidimensional (Físico, Reservado, En Tránsito)
 * - Trazabilidad de Lotes y Caducidad
 * - Cálculo de costos de reposición
 */
class Inventory_advanced_model extends CI_Model {
    
    /**
     * Obtiene el stock real disponible para venta
     * Fórmula: Físico - Reservado + En Tránsito (si se permite vender en tránsito)
     */
    public function get_available_stock($item_id, $location_id = null) {
        $this->db->select('stock_physical, stock_reserved, stock_in_transit');
        if ($location_id) {
            $this->db->where('location_id', $location_id);
        }
        $query = $this->db->get_where('items', ['item_id' => $item_id]);
        $row = $query->row();
        
        if (!$row) return 0;
        
        // Lógica configurable: ¿Se puede vender stock en tránsito?
        $allow_backorder = $this->config->item('allow_sell_in_transit') ?? false;
        $available = $row->stock_physical - $row->stock_reserved;
        
        if ($allow_backorder) {
            $available += $row->stock_in_transit;
        }
        
        return max(0, $available);
    }

    /**
     * Reserva stock para una orden de venta o producción
     */
    public function reserve_stock($item_id, $quantity, $reference_id, $type = 'sales_order') {
        $current = $this->db->get_where('items', ['item_id' => $item_id])->row();
        if (!$current) return false;
        
        $available = $current->stock_physical - $current->stock_reserved;
        if ($quantity > $available) {
            return false; // Stock insuficiente
        }
        
        $this->db->trans_start();
        $this->db->set('stock_reserved', 'stock_reserved + ' . (int)$quantity, FALSE);
        $this->db->where('item_id', $item_id);
        $this->db->update('items');
        
        // Registrar la reserva
        $this->db->insert('stock_reservations', [
            'item_id' => $item_id,
            'quantity' => $quantity,
            'reference_id' => $reference_id,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->trans_status();
    }

    /**
     * Libera stock reservado (cancelación de orden)
     */
    public function release_reservation($reservation_id) {
        $res = $this->db->get_where('stock_reservations', ['id' => $reservation_id])->row();
        if (!$res) return false;
        
        $this->db->trans_start();
        $this->db->set('stock_reserved', 'stock_reserved - ' . (int)$res->quantity, FALSE);
        $this->db->where('item_id', $res->item_id);
        $this->db->update('items');
        
        $this->db->delete('stock_reservations', ['id' => $reservation_id]);
        return $this->db->trans_status();
    }

    /**
     * Gestiona lotes y fechas de caducidad
     * Retorna lotes disponibles ordenados por FEFO (First Expired First Out)
     */
    public function get_lots_by_item($item_id, $min_qty = 1) {
        $this->db->select('inventory_id, lot_number, expiration_date, qty');
        $this->db->where('item_id', $item_id);
        $this->db->where('qty >=', $min_qty);
        $this->db->order_by('expiration_date', 'ASC');
        return $this->db->get('inventory')->result();
    }

    /**
     * Alerta de productos próximos a vencer
     */
    public function get_expiring_items($days_threshold = 30) {
        $threshold_date = date('Y-m-d', strtotime("+{$days_threshold} days"));
        
        $this->db->select('i.name, i.item_number, inv.lot_number, inv.expiration_date, SUM(inv.qty) as total_qty');
        $this->db->from('inventory inv');
        $this->db->join('items i', 'inv.item_id = i.item_id');
        $this->db->where('inv.expiration_date IS NOT NULL');
        $this->db->where('inv.expiration_date <=', $threshold_date);
        $this->db->where('inv.expiration_date >=', date('Y-m-d')); // Solo futuros
        $this->db->group_by('inv.item_id, inv.lot_number');
        $this->db->order_by('inv.expiration_date', 'ASC');
        
        return $this->db->get()->result();
    }

    /**
     * Calcula el costo de reposición basado en el último precio de compra + tendencia
     */
    public function get_replacement_cost($item_id) {
        // Obtener último costo de recepción
        $this->db->select('unit_cost');
        $this->db->from('receiving_items');
        $this->db->where('item_id', $item_id);
        $this->db->order_by('receiving_id', 'DESC');
        $last_cost = $this->db->get()->row();
        
        if (!$last_cost) return 0;
        
        // Analizar tendencia de últimos 3 movimientos (opcional)
        $this->db->select('unit_cost');
        $this->db->from('receiving_items');
        $this->db->where('item_id', $item_id);
        $this->db->order_by('receiving_id', 'DESC');
        $this->db->limit(3);
        $history = $this->db->get()->result();
        
        if (count($history) < 2) return $last_cost->unit_cost;
        
        // Calcular promedio ponderado simple de tendencia
        $trend_factor = 1.0;
        if ($history[0]->unit_cost > $history[1]->unit_cost) {
            $trend_factor = 1 + (($history[0]->unit_cost - $history[1]->unit_cost) / $history[1]->unit_cost);
        }
        
        return round($last_cost->unit_cost * $trend_factor, 2);
    }
}
