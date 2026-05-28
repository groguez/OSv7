<?php
require_once ("Secure_area.php");

class Ai_lite extends Secure_area 
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Ai_lite_model');
        $this->load->model('Item');
        $this->load->model('Location');
        $this->lang->load('items');
        $this->lang->load('common');
    }

    /**
     * Dashboard principal del Motor de Sugerencias AI-Lite
     */
    function index()
    {
        $data['page_title'] = 'Motor de Sugerencias - OneBox AI';
        
        // Métricas de salud de inventario
        $data['health_metrics'] = $this->Ai_lite_model->get_inventory_health_metrics();
        
        // Items que necesitan reabastecimiento urgente
        $data['reorder_items'] = $this->Ai_lite_model->get_items_needing_reorder(NULL, 25);
        
        // Productos trending (estrella)
        $data['trending_products'] = $this->Ai_lite_model->get_trending_products(NULL, 10);
        
        // Productos huérfanos (sin movimiento)
        $data['orphan_products'] = $this->Ai_lite_model->get_orphan_products(NULL, 60, 10);
        
        // Sugerencias consolidadas por proveedor
        $data['purchase_suggestions'] = $this->Ai_lite_model->get_purchase_suggestions_by_supplier();
        
        // Alertas para el dashboard
        $data['alerts'] = $this->Ai_lite_model->get_dashboard_alerts();
        
        $this->load->view('ai_lite/dashboard', $data);
    }

    /**
     * API: Obtener items que necesitan reabastecimiento
     */
    function get_reorder_items($limit = 50)
    {
        $items = $this->Ai_lite_model->get_items_needing_reorder(NULL, $limit);
        echo json_encode([
            'success' => true,
            'data' => $items,
            'count' => count($items)
        ]);
    }

    /**
     * API: Obtener productos trending
     */
    function get_trending_products($limit = 20)
    {
        $products = $this->Ai_lite_model->get_trending_products(NULL, $limit);
        echo json_encode([
            'success' => true,
            'data' => $products,
            'count' => count($products)
        ]);
    }

    /**
     * API: Obtener predicción de ventas para un item específico
     */
    function predict_item_sales($item_id)
    {
        $prediction = $this->Ai_lite_model->predict_sales($item_id);
        echo json_encode([
            'success' => true,
            'item_id' => $item_id,
            'prediction' => $prediction
        ]);
    }

    /**
     * API: Obtener métricas de salud
     */
    function get_health_metrics()
    {
        $metrics = $this->Ai_lite_model->get_inventory_health_metrics();
        echo json_encode([
            'success' => true,
            'metrics' => $metrics
        ]);
    }

    /**
     * API: Generar orden de compra sugerida para un proveedor
     */
    function generate_purchase_order($supplier_id)
    {
        $suggestions = $this->Ai_lite_model->get_purchase_suggestions_by_supplier();
        
        $supplier_suggestion = null;
        foreach ($suggestions as $suggestion) {
            if ($suggestion['supplier_id'] == $supplier_id) {
                $supplier_suggestion = $suggestion;
                break;
            }
        }
        
        if (!$supplier_suggestion) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay sugerencias de compra para este proveedor'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'supplier' => $supplier_suggestion,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Vista modal para detalles de predicción de un producto
     */
    function item_prediction_modal($item_id)
    {
        $item_info = $this->Item->get_info($item_id);
        $prediction = $this->Ai_lite_model->predict_sales($item_id);
        $location_info = $this->Location->get_info($this->Employee->get_logged_in_employee_current_location_id());
        
        $data['item_info'] = $item_info;
        $data['prediction'] = $prediction;
        $data['location_name'] = $location_info->name;
        
        $this->load->view('ai_lite/item_prediction_modal', $data);
    }
}
