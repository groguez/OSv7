<?php
require_once(APPPATH . "controllers/Secure.php");
/**
 * Controlador de Inteligencia de Compras e Inventarios
 * Centraliza la gestión moderna de:
 * - Órdenes de compra inteligentes
 * - Recepción con validación de desviaciones
 * - Scorecard de proveedores
 * - Alertas de inventario y caducidad
 */
class Purchasing_intelligence extends Secure {
    
    function __construct() {
        parent::__construct();
        $this->load->model('inventory/Inventory_advanced_model');
        $this->load->model('purchasing/Supplier_scorecard_model');
        $this->lang->load('purchasing');
    }

    /**
     * Dashboard principal de inteligencia de compras
     */
    function index() {
        $data['allowed_modules'] = $this->Module->get_allowed_modules();
        
        // Métricas clave
        $data['expiring_items'] = $this->Inventory_advanced_model->get_expiring_items(30);
        $data['supplier_ranking'] = $this->Supplier_scorecard_model->get_supplier_ranking(5);
        
        // Stock crítico (productos por debajo del punto de reorden)
        $this->db->select('i.name, i.item_number, i.quantity, i.reorder_level');
        $this->db->from('items i');
        $this->db->where('i.quantity <= i.reorder_level');
        $this->db->where('i.is_deleted', 0);
        $data['low_stock_items'] = $this->db->get()->result();
        
        $this->load->view('purchasing/dashboard', $data);
    }

    /**
     * Calcula scores de proveedores para el mes actual
     */
    function calculate_supplier_scores() {
        $month = date('n');
        $year = date('Y');
        
        $this->db->select('supplier_id');
        $this->db->from('suppliers');
        $this->db->where('is_deleted', 0);
        $suppliers = $this->db->get()->result();
        
        $results = [];
        foreach ($suppliers as $s) {
            $score = $this->Supplier_scorecard_model->calculate_monthly_score($s->supplier_id, $month, $year);
            $results[] = $score;
        }
        
        echo json_encode(['success' => true, 'scores' => $results]);
    }

    /**
     * Vista de alertas de inventario
     */
    function alerts() {
        $data['expiring_soon'] = $this->Inventory_advanced_model->get_expiring_items(15);
        $data['expiring_medium'] = $this->Inventory_advanced_model->get_expiring_items(45);
        
        // Stock negativo o reservado excesivo
        $this->db->select('i.name, i.item_number, i.stock_physical, i.stock_reserved');
        $this->db->from('items i');
        $this->db->where('i.stock_physical < i.stock_reserved');
        $data['overreserved_items'] = $this->db->get()->result();
        
        $this->load->view('purchasing/alerts', $data);
    }

    /**
     * API para obtener costo de reposición de un item
     */
    function get_replacement_cost($item_id) {
        $cost = $this->Inventory_advanced_model->get_replacement_cost($item_id);
        echo json_encode(['item_id' => $item_id, 'replacement_cost' => $cost]);
    }
}
