<?php
/**
 * Modelo de Scorecard de Proveedores
 * Evalúa automáticamente el desempeño de proveedores basado en:
 * - Puntualidad en entregas
 * - Variación de precios
 * - Calidad (rechazos/devoluciones)
 * - Tiempo promedio de entrega (Lead Time)
 */
class Supplier_scorecard_model extends CI_Model {

    public function calculate_monthly_score($supplier_id, $month, $year) {
        $on_time_rate = $this->_calculate_on_time_delivery($supplier_id, $month, $year);
        $price_variance = $this->_calculate_price_variance($supplier_id, $month, $year);
        $rejection_rate = $this->_calculate_rejection_rate($supplier_id, $month, $year);
        $avg_lead_time = $this->_calculate_avg_lead_time($supplier_id, $month, $year);
        
        $score_punctuality = $on_time_rate; 
        $score_price = max(0, 100 - ($price_variance * 10));
        $score_quality = max(0, 100 - ($rejection_rate * 10));
        $score_leadtime = max(0, 100 - ($avg_lead_time * 2));
        
        $total_score = ($score_punctuality * 0.40) + ($score_price * 0.30) + ($score_quality * 0.20) + ($score_leadtime * 0.10);
        
        $data = [
            'supplier_id' => $supplier_id,
            'period_month' => $month,
            'period_year' => $year,
            'on_time_delivery_rate' => round($on_time_rate, 2),
            'price_variance_rate' => round($price_variance, 2),
            'quality_rejection_rate' => round($rejection_rate, 2),
            'lead_time_avg_days' => round($avg_lead_time, 1),
            'total_orders' => $this->_count_orders($supplier_id, $month, $year),
            'score_total' => round($total_score, 2)
        ];
        
        $this->db->replace('supplier_scorecards', $data);
        return $data;
    }

    private function _calculate_on_time_delivery($supplier_id, $month, $year) {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $this->db->select('COUNT(*) as total');
        $this->db->from('purchase_orders');
        $this->db->where('supplier_id', $supplier_id);
        $this->db->where('status', 'received');
        $this->db->where('expected_date IS NOT NULL');
        $this->db->where('DATE(received_date) >=', $start_date);
        $this->db->where('DATE(received_date) <=', $end_date);
        $total = $this->db->get()->row()->total;
        
        if ($total == 0) return 100;
        
        $this->db->select('COUNT(*) as on_time');
        $this->db->from('purchase_orders');
        $this->db->where('supplier_id', $supplier_id);
        $this->db->where('status', 'received');
        $this->db->where('expected_date IS NOT NULL');
        $this->db->where('DATE(received_date) <= DATE(expected_date)');
        $this->db->where('DATE(received_date) >=', $start_date);
        $this->db->where('DATE(received_date) <=', $end_date);
        $on_time = $this->db->get()->row()->on_time;
        
        return ($on_time / $total) * 100;
    }

    private function _calculate_price_variance($supplier_id, $month, $year) {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $this->db->select('AVG(unit_cost) as avg_cost, item_id');
        $this->db->from('receiving_items');
        $this->db->join('receiving r', 'receiving_items.receiving_id = r.receiving_id');
        $this->db->where('r.supplier_id', $supplier_id);
        $this->db->where('DATE(r.receive_date) <', $start_date);
        $this->db->group_by('item_id');
        $historical = $this->db->get()->result_array();
        
        if (empty($historical)) return 0;
        $hist_map = array_column($historical, 'avg_cost', 'item_id');
        
        $this->db->select('unit_cost, item_id');
        $this->db->from('receiving_items');
        $this->db->join('receiving r', 'receiving_items.receiving_id = r.receiving_id');
        $this->db->where('r.supplier_id', $supplier_id);
        $this->db->where('DATE(r.receive_date) >=', $start_date);
        $this->db->where('DATE(r.receive_date) <=', $end_date);
        $current = $this->db->get()->result();
        
        $variances = [];
        foreach ($current as $c) {
            if (isset($hist_map[$c->item_id]) && $hist_map[$c->item_id] > 0) {
                $var = (($c->unit_cost - $hist_map[$c->item_id]) / $hist_map[$c->item_id]) * 100;
                $variances[] = abs($var);
            }
        }
        return empty($variances) ? 0 : array_sum($variances) / count($variances);
    }

    private function _calculate_rejection_rate($supplier_id, $month, $year) {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $this->db->select('SUM(expected_qty) as total_received');
        $this->db->from('receiving_items ri');
        $this->db->join('receiving r', 'ri.receiving_id = r.receiving_id');
        $this->db->where('r.supplier_id', $supplier_id);
        $this->db->where('DATE(r.receive_date) >=', $start_date);
        $this->db->where('DATE(r.receive_date) <=', $end_date);
        $total = $this->db->get()->row()->total_received ?? 0;
        
        if ($total == 0) return 0;
        
        $this->db->select('SUM(quantity_diff) as rejected');
        $this->db->from('receiving_discrepancies rd');
        $this->db->join('receiving r', 'rd.receiving_id = r.receiving_id');
        $this->db->where('r.supplier_id', $supplier_id);
        $this->db->where('rd.discrepancy_type', 'quality_issue');
        $this->db->where('DATE(r.receive_date) >=', $start_date);
        $this->db->where('DATE(r.receive_date) <=', $end_date);
        $rejected = $this->db->get()->row()->rejected ?? 0;
        
        return ($rejected / $total) * 100;
    }

    private function _calculate_avg_lead_time($supplier_id, $month, $year) {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $this->db->select('TIMESTAMPDIFF(DAY, order_date, received_date) as lead_time');
        $this->db->from('purchase_orders');
        $this->db->where('supplier_id', $supplier_id);
        $this->db->where('status', 'received');
        $this->db->where('order_date IS NOT NULL');
        $this->db->where('received_date IS NOT NULL');
        $this->db->where('DATE(received_date) >=', $start_date);
        $this->db->where('DATE(received_date) <=', $end_date);
        $result = $this->db->get()->result();
        
        if (empty($result)) return 0;
        $times = array_column($result, 'lead_time');
        return array_sum($times) / count($times);
    }

    private function _count_orders($supplier_id, $month, $year) {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        $this->db->from('purchase_orders');
        $this->db->where('supplier_id', $supplier_id);
        $this->db->where('DATE(order_date) >=', $start_date);
        $this->db->where('DATE(order_date) <=', $end_date);
        return $this->db->count_all_results();
    }

    public function get_supplier_ranking($limit = 10, $month = null, $year = null) {
        if (!$month) $month = date('n');
        if (!$year) $year = date('Y');
        
        $this->db->select('s.supplier_name, sc.score_total, sc.on_time_delivery_rate, sc.quality_rejection_rate');
        $this->db->from('supplier_scorecards sc');
        $this->db->join('suppliers s', 'sc.supplier_id = s.supplier_id');
        $this->db->where('sc.period_month', $month);
        $this->db->where('sc.period_year', $year);
        $this->db->order_by('sc.score_total', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }
}
