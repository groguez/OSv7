<?php
/**
 * AI-Lite Engine - Motor de Sugerencias Predictivas para OneBox
 * 
 * Este modelo proporciona inteligencia de negocios ligera para:
 * - Predicción de reabastecimiento basado en ventas históricas
 * - Detección de tendencias y estacionalidad
 * - Sugerencias de compra inteligente
 * - Alertas proactivas de inventario
 */

class Ai_lite_model extends CI_Model
{
    // Umbrales de configuración
    private $default_prediction_days = 30;
    private $safety_stock_multiplier = 1.2;
    private $trend_sensitivity = 0.15;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('date');
    }

    /**
     * Obtiene todos los items que necesitan reabastecimiento urgente
     * Incluye cálculo predictivo basado en ventas recientes
     */
    public function get_items_needing_reorder($location_id = NULL, $limit = 50)
    {
        if (!$location_id) {
            $location_id = $this->Employee->get_logged_in_employee_current_location_id();
        }

        $prediction_days = $this->default_prediction_days;
        $start_date = date('Y-m-d', strtotime("-{$prediction_days} days"));
        $end_date = date('Y-m-d 23:59:59');

        // Consulta principal con predicción de ventas diarias
        $this->db->select('
            items.item_id,
            items.name,
            items.item_number,
            items.product_id,
            items.category_id,
            categories.name as category_name,
            suppliers.company_name as supplier_name,
            items.supplier_id,
            location_items.quantity as current_quantity,
            IFNULL(location_items.reorder_level, items.reorder_level) as reorder_level,
            IFNULL(location_items.replenish_level, items.replenish_level) as replenish_level,
            items.cost_price,
            items.unit_price,
            
            -- Ventas totales en el período
            COALESCE(SUM(sales_items.quantity_purchased), 0) as total_sold,
            
            -- Promedio diario de ventas
            COALESCE(SUM(sales_items.quantity_purchased) / GREATEST(DATEDIFF(NOW(), \'' . $start_date . '\'), 1), 0) as avg_daily_sales,
            
            -- Días estimados hasta agotarse
            CASE 
                WHEN COALESCE(SUM(sales_items.quantity_purchased) / GREATEST(DATEDIFF(NOW(), \'' . $start_date . '\'), 1), 0) > 0 
                THEN FLOOR(location_items.quantity / (SUM(sales_items.quantity_purchased) / GREATEST(DATEDIFF(NOW(), \'' . $start_date . '\'), 1)))
                ELSE 999 
            END as days_until_stockout,
            
            -- Cantidad sugerida a pedir (con stock de seguridad)
            GREATEST(
                0,
                (COALESCE(SUM(sales_items.quantity_purchased) / GREATEST(DATEDIFF(NOW(), \'' . $start_date . '\'), 1), 0) * 30 * ' . $this->safety_stock_multiplier . ') 
                - location_items.quantity
            ) as suggested_order_quantity,
            
            -- Valor estimado del pedido
            GREATEST(
                0,
                ((COALESCE(SUM(sales_items.quantity_purchased) / GREATEST(DATEDIFF(NOW(), \'' . $start_date . '\'), 1), 0) * 30 * ' . $this->safety_stock_multiplier . ') 
                - location_items.quantity) * items.cost_price
            ) as estimated_order_value
            
        ', FALSE);

        $this->db->from('items');
        $this->db->join('location_items', 'location_items.item_id = items.item_id AND location_items.location_id = ' . $location_id, 'left');
        $this->db->join('categories', 'items.category_id = categories.id', 'left');
        $this->db->join('suppliers', 'items.supplier_id = suppliers.person_id', 'left');
        $this->db->join('sales_items', 'sales_items.item_id = items.item_id', 'left');
        $this->db->join('sales', 'sales_items.sale_id = sales.sale_id AND sales.sale_time >= "' . $start_date . '" AND sales.sale_time <= "' . $end_date . '" AND sales.location_id = ' . $location_id, 'left');

        $this->db->where('items.deleted', 0);
        $this->db->where('items.system_item', 0);
        $this->db->group_by('items.item_id');
        
        // Filtrar solo items que necesitan reorden (cantidad actual < reorder_level)
        $this->db->having('location_items.quantity < IFNULL(location_items.reorder_level, items.reorder_level)');
        
        $this->db->order_by('days_until_stockout', 'ASC');
        $this->db->limit($limit);

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Obtiene productos con tendencia de venta creciente (productos estrella)
     */
    public function get_trending_products($location_id = NULL, $limit = 20)
    {
        if (!$location_id) {
            $location_id = $this->Employee->get_logged_in_employee_current_location_id();
        }

        $period_days = 30;
        $current_start = date('Y-m-d', strtotime("-{$period_days} days"));
        $previous_start = date('Y-m-d', strtotime("-" . ($period_days * 2) . " days"));

        $this->db->select('
            items.item_id,
            items.name,
            items.item_number,
            categories.name as category_name,
            
            -- Ventas período actual
            COALESCE(SUM(CASE WHEN sales.sale_time >= "' . $current_start . '" THEN sales_items.quantity_purchased ELSE 0 END), 0) as current_period_sales,
            
            -- Ventas período anterior
            COALESCE(SUM(CASE WHEN sales.sale_time >= "' . $previous_start . '" AND sales.sale_time < "' . $current_start . '" THEN sales_items.quantity_purchased ELSE 0 END), 0) as previous_period_sales,
            
            -- Porcentaje de crecimiento
            CASE 
                WHEN COALESCE(SUM(CASE WHEN sales.sale_time >= "' . $previous_start . '" AND sales.sale_time < "' . $current_start . '" THEN sales_items.quantity_purchased ELSE 0 END), 0) > 0
                THEN ROUND(
                    ((SUM(CASE WHEN sales.sale_time >= "' . $current_start . '" THEN sales_items.quantity_purchased ELSE 0 END) - 
                      SUM(CASE WHEN sales.sale_time >= "' . $previous_start . '" AND sales.sale_time < "' . $current_start . '" THEN sales_items.quantity_purchased ELSE 0 END)) / 
                     SUM(CASE WHEN sales.sale_time >= "' . $previous_start . '" AND sales.sale_time < "' . $current_start . '" THEN sales_items.quantity_purchased ELSE 0 END)) * 100, 2
                )
                ELSE 0
            END as growth_percentage,
            
            items.unit_price,
            items.cost_price
            
        ', FALSE);

        $this->db->from('items');
        $this->db->join('sales_items', 'sales_items.item_id = items.item_id');
        $this->db->join('sales', 'sales_items.sale_id = sales.sale_id AND sales.sale_time >= "' . $previous_start . '" AND sales.location_id = ' . $location_id);
        $this->db->join('categories', 'items.category_id = categories.id', 'left');

        $this->db->where('items.deleted', 0);
        $this->db->where('items.system_item', 0);
        $this->db->group_by('items.item_id');
        $this->db->having('current_period_sales > 0');
        $this->db->order_by('growth_percentage', 'DESC');
        $this->db->limit($limit);

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Obtiene productos huérfanos (sin ventas en período determinado)
     */
    public function get_orphan_products($location_id = NULL, $days_without_sales = 60, $limit = 20)
    {
        if (!$location_id) {
            $location_id = $this->Employee->get_logged_in_employee_current_location_id();
        }

        $cutoff_date = date('Y-m-d', strtotime("-{$days_without_sales} days"));

        $this->db->select('
            items.item_id,
            items.name,
            items.item_number,
            items.category_id,
            categories.name as category_name,
            location_items.quantity as current_quantity,
            items.cost_price,
            items.unit_price,
            (items.cost_price * location_items.quantity) as invested_capital,
            MAX(sales.sale_time) as last_sale_date,
            DATEDIFF(NOW(), MAX(sales.sale_time)) as days_since_last_sale
        ', FALSE);

        $this->db->from('items');
        $this->db->join('location_items', 'location_items.item_id = items.item_id AND location_items.location_id = ' . $location_id, 'left');
        $this->db->join('categories', 'items.category_id = categories.id', 'left');
        $this->db->join('sales_items', 'sales_items.item_id = items.item_id');
        $this->db->join('sales', 'sales_items.sale_id = sales.sale_id AND sales.location_id = ' . $location_id, 'left');

        $this->db->where('items.deleted', 0);
        $this->db->where('items.system_item', 0);
        $this->db->group_by('items.item_id');
        $this->db->having('MAX(sales.sale_time) < "' . $cutoff_date . '" OR MAX(sales.sale_time) IS NULL');
        $this->db->having('location_items.quantity > 0');
        $this->db->order_by('invested_capital', 'DESC');
        $this->db->limit($limit);

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Genera sugerencias de compra consolidadas por proveedor
     */
    public function get_purchase_suggestions_by_supplier($location_id = NULL)
    {
        $items = $this->get_items_needing_reorder($location_id, 500);
        
        $suggestions = [];
        
        foreach ($items as $item) {
            $supplier_id = $item['supplier_id'];
            $supplier_name = $item['supplier_name'] ?? 'Sin Proveedor';
            
            if (!isset($suggestions[$supplier_id])) {
                $suggestions[$supplier_id] = [
                    'supplier_id' => $supplier_id,
                    'supplier_name' => $supplier_name,
                    'items' => [],
                    'total_items' => 0,
                    'total_quantity' => 0,
                    'total_value' => 0
                ];
            }
            
            $suggestions[$supplier_id]['items'][] = $item;
            $suggestions[$supplier_id]['total_items']++;
            $suggestions[$supplier_id]['total_quantity'] += $item['suggested_order_quantity'];
            $suggestions[$supplier_id]['total_value'] += $item['estimated_order_value'];
        }
        
        // Ordenar por valor total
        usort($suggestions, function($a, $b) {
            return $b['total_value'] <=> $a['total_value'];
        });
        
        return array_values($suggestions);
    }

    /**
     * Calcula métricas de salud de inventario
     */
    public function get_inventory_health_metrics($location_id = NULL)
    {
        if (!$location_id) {
            $location_id = $this->Employee->get_logged_in_employee_current_location_id();
        }

        // Total de items activos
        $this->db->select('COUNT(*) as total_items');
        $this->db->from('items');
        $this->db->join('location_items', 'location_items.item_id = items.item_id AND location_items.location_id = ' . $location_id, 'left');
        $this->db->where('items.deleted', 0);
        $this->db->where('items.system_item', 0);
        $total_items = $this->db->get()->row()->total_items;

        // Items por debajo de reorder level
        $this->db->select('COUNT(*) as low_stock_items');
        $this->db->from('items');
        $this->db->join('location_items', 'location_items.item_id = items.item_id AND location_items.location_id = ' . $location_id, 'left');
        $this->db->where('items.deleted', 0);
        $this->db->where('items.system_item', 0);
        $this->db->where('location_items.quantity < IFNULL(location_items.reorder_level, items.reorder_level)');
        $low_stock_items = $this->db->get()->row()->low_stock_items;

        // Items sin stock (cantidad = 0)
        $this->db->select('COUNT(*) as out_of_stock_items');
        $this->db->from('items');
        $this->db->join('location_items', 'location_items.item_id = items.item_id AND location_items.location_id = ' . $location_id, 'left');
        $this->db->where('items.deleted', 0);
        $this->db->where('items.system_item', 0);
        $this->db->where('location_items.quantity = 0');
        $out_of_stock_items = $this->db->get()->row()->out_of_stock_items;

        // Items huérfanos (>60 días sin ventas)
        $orphan_items = count($this->get_orphan_products($location_id, 60, 1000));

        // Score de salud (0-100)
        $health_score = 100;
        if ($total_items > 0) {
            $low_stock_penalty = ($low_stock_items / $total_items) * 40;
            $out_of_stock_penalty = ($out_of_stock_items / $total_items) * 30;
            $orphan_penalty = ($orphan_items / $total_items) * 30;
            $health_score = max(0, 100 - $low_stock_penalty - $out_of_stock_penalty - $orphan_penalty);
        }

        return [
            'total_items' => $total_items,
            'low_stock_items' => $low_stock_items,
            'out_of_stock_items' => $out_of_stock_items,
            'orphan_items' => $orphan_items,
            'health_score' => round($health_score, 1),
            'health_status' => $health_score >= 80 ? 'excellent' : ($health_score >= 60 ? 'good' : ($health_score >= 40 ? 'warning' : 'critical'))
        ];
    }

    /**
     * Predice ventas para los próximos N días usando media móvil ponderada
     */
    public function predict_sales($item_id, $location_id = NULL, $days_to_predict = 30)
    {
        if (!$location_id) {
            $location_id = $this->Employee->get_logged_in_employee_current_location_id();
        }

        // Obtener ventas de los últimos 90 días
        $start_date = date('Y-m-d', strtotime("-90 days"));
        
        $this->db->select('DATE(sale_time) as sale_date, SUM(quantity_purchased) as daily_quantity');
        $this->db->from('sales_items');
        $this->db->join('sales', 'sales_items.sale_id = sales.sale_id');
        $this->db->where('sales_items.item_id', $item_id);
        $this->db->where('sales.sale_time >=', $start_date);
        $this->db->where('sales.location_id', $location_id);
        $this->db->group_by('DATE(sale_time)');
        $this->db->order_by('sale_date', 'ASC');
        
        $query = $this->db->get();
        $daily_sales = $query->result_array();

        if (empty($daily_sales)) {
            return [
                'predicted_quantity' => 0,
                'confidence' => 0,
                'trend' => 'stable',
                'avg_daily_sales' => 0
            ];
        }

        // Calcular media móvil ponderada (últimos 30 días tienen más peso)
        $recent_sales = array_slice($daily_sales, -30);
        $older_sales = array_slice($daily_sales, 0, -30);

        $recent_avg = 0;
        $older_avg = 0;

        if (!empty($recent_sales)) {
            $recent_total = array_sum(array_column($recent_sales, 'daily_quantity'));
            $recent_avg = $recent_total / count($recent_sales);
        }

        if (!empty($older_sales)) {
            $older_total = array_sum(array_column($older_sales, 'daily_quantity'));
            $older_avg = $older_total / count($older_sales);
        }

        // Media ponderada (70% reciente, 30% antiguo)
        $weighted_avg = ($recent_avg * 0.7) + ($older_avg * 0.3);
        
        // Detectar tendencia
        $trend = 'stable';
        if ($recent_avg > $older_avg * (1 + $this->trend_sensitivity)) {
            $trend = 'increasing';
        } elseif ($recent_avg < $older_avg * (1 - $this->trend_sensitivity)) {
            $trend = 'decreasing';
        }

        // Predecir cantidad para próximos N días
        $predicted_quantity = $weighted_avg * $days_to_predict;

        // Calcular confianza basada en consistencia
        $std_dev = 0;
        if (count($recent_sales) > 1) {
            $mean = $recent_avg;
            $variance = array_reduce($recent_sales, function($carry, $item) use ($mean) {
                return $carry + pow($item['daily_quantity'] - $mean, 2);
            }, 0) / count($recent_sales);
            $std_dev = sqrt($variance);
        }

        $cv = $recent_avg > 0 ? ($std_dev / $recent_avg) : 1;
        $confidence = max(0, min(100, (1 - $cv) * 100));

        return [
            'predicted_quantity' => round($predicted_quantity, 2),
            'confidence' => round($confidence, 1),
            'trend' => $trend,
            'avg_daily_sales' => round($weighted_avg, 2),
            'recent_avg' => round($recent_avg, 2),
            'older_avg' => round($older_avg, 2)
        ];
    }

    /**
     * Genera alerta para el dashboard
     */
    public function get_dashboard_alerts($location_id = NULL)
    {
        $metrics = $this->get_inventory_health_metrics($location_id);
        $low_stock_items = $this->get_items_needing_reorder($location_id, 10);
        $trending_items = $this->get_trending_products($location_id, 5);
        
        $alerts = [];

        // Alerta crítica: Items sin stock
        if ($metrics['out_of_stock_items'] > 0) {
            $alerts[] = [
                'type' => 'critical',
                'icon' => 'ti-alert',
                'title' => 'Items Sin Stock',
                'message' => $metrics['out_of_stock_items'] . ' productos están agotados',
                'action_url' => site_url('items/inventory'),
                'priority' => 1
            ];
        }

        // Alerta alta: Stock bajo
        if ($metrics['low_stock_items'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'ti-arrow-down',
                'title' => 'Stock Bajo',
                'message' => $metrics['low_stock_items'] . ' productos necesitan reabastecimiento',
                'action_url' => site_url('items/inventory'),
                'priority' => 2
            ];
        }

        // Alerta informativa: Productos huérfanos
        if ($metrics['orphan_items'] > 5) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'ti-time',
                'title' => 'Productos Sin Movimiento',
                'message' => $metrics['orphan_items'] . ' productos sin ventas en 60+ días',
                'action_url' => site_url('items/manage'),
                'priority' => 4
            ];
        }

        // Notificación positiva: Productos trending
        if (!empty($trending_items)) {
            $top_trend = reset($trending_items);
            if ($top_trend['growth_percentage'] > 50) {
                $alerts[] = [
                    'type' => 'success',
                    'icon' => 'ti-arrow-up',
                    'title' => 'Producto Estrella',
                    'message' => $top_trend['name'] . ' creció ' . round($top_trend['growth_percentage']) . '%',
                    'action_url' => site_url('items/edit/' . $top_trend['item_id']),
                    'priority' => 3
                ];
            }
        }

        // Ordenar por prioridad
        usort($alerts, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $alerts;
    }
}
