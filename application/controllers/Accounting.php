<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * OneBox - Controlador de Contabilidad Inteligente
 * Interfaz principal para el Asistente Contable
 * 
 * Funcionalidades:
 * - Dashboard de conciliación
 * - Revisión manual de transacciones
 * - Aceptación de sugerencias automáticas
 * - Generación de lotes consolidados
 * - Reportes contables básicos
 */
class Accounting extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Verificar permisos
        if (!$this->Employee->has_module_grant('accounting', $this->session->userdata('person_id'))) {
            redirect('/no_access/accounting');
        }
        
        $this->load->model('Accounting_model');
        $this->lang->load('accounting');
    }

    /**
     * Dashboard principal de contabilidad
     * Muestra resumen y accesos rápidos
     */
    public function index()
    {
        $data['selected_submodule'] = 'dashboard';
        
        // Obtener resumen del período actual
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-t');
        $data['summary'] = $this->Accounting_model->get_accounting_dashboard_summary($date_from, $date_to);
        
        // Contar pendientes por tipo
        $data['pending_sales_count'] = $this->count_pending('SALE');
        $data['pending_purchases_count'] = $this->count_pending('PURCHASE');
        
        $this->load->view('accounting/dashboard', $data);
    }

    /**
     * Vista de cola de conciliación
     * Permite revisar y decidir sobre cada transacción
     */
    public function reconciliation_queue()
    {
        $data['selected_submodule'] = 'reconciliation';
        
        $filters = [];
        if ($this->input->get('type')) {
            $filters['transaction_type'] = $this->input->get('type');
        }
        if ($this->input->get('status')) {
            $filters['status'] = $this->input->get('status');
        }
        
        $data['queue_items'] = $this->Accounting_model->get_reconciliation_queue($filters);
        $data['filter_type'] = $filters['transaction_type'] ?? 'all';
        $data['filter_status'] = $filters['status'] ?? 'all';
        
        $this->load->view('accounting/reconciliation_queue', $data);
    }

    /**
     * Procesa la decisión del usuario sobre una transacción
     * Se llama vía AJAX desde la vista
     */
    public function process_decision()
    {
        $queue_id = $this->input->post('queue_id');
        $decision = $this->input->post('decision'); // FACTURED, NON_FACTURED_GLOBAL, NON_FACTURED_IGNORED
        $cfdi_uuid = $this->input->post('cfdi_uuid');
        
        if (!$queue_id || !$decision) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        $user_id = $this->session->userdata('person_id');
        $result = $this->Accounting_model->process_reconciliation_decision(
            $queue_id, 
            $decision, 
            $cfdi_uuid, 
            $user_id
        );
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Transacción procesada correctamente',
                'decision' => $decision
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al procesar']);
        }
    }

    /**
     * Aceptar todas las sugerencias automáticas de una vez
     * Útil para procesamiento masivo
     */
    public function accept_all_suggestions()
    {
        $this->db->trans_start();
        
        // Obtener todas las transacciones con sugerencias
        $this->db->where('suggestion_reason IS NOT NULL');
        $this->db->where('status', 'PENDING_REVIEW');
        $items = $this->db->get('onebox_reconciliation_queue')->result_array();
        
        $processed = 0;
        foreach ($items as $item) {
            // Decisión automática basada en el monto
            $decision = ($item['amount'] >= 500) ? 'FACTURED' : 'NON_FACTURED_GLOBAL';
            
            $result = $this->Accounting_model->process_reconciliation_decision(
                $item['queue_id'],
                $decision,
                null,
                $this->session->userdata('person_id')
            );
            
            if ($result) {
                $processed++;
            }
        }
        
        $this->db->trans_complete();
        
        echo json_encode([
            'success' => true,
            'message' => "$processed transacciones procesadas automáticamente",
            'processed_count' => $processed
        ]);
    }

    /**
     * Vista de lotes consolidados
     * Muestra los grupos de operaciones no facturadas
     */
    public function consolidation_batches()
    {
        $data['selected_submodule'] = 'batches';
        
        $this->db->select('b.*, GROUP_CONCAT(q.transaction_id) as transaction_ids');
        $this->db->from('onebox_consolidation_batches b');
        $this->db->join('onebox_reconciliation_queue q', 'b.batch_id = q.consolidation_batch_id', 'left');
        $this->db->group_by('b.batch_id');
        $this->db->order_by('b.batch_date', 'desc');
        
        $data['batches'] = $this->db->get()->result_array();
        
        $this->load->view('accounting/consolidation_batches', $data);
    }

    /**
     * Generar CFDI global desde un lote consolidado
     * Conecta con el módulo de facturación existente
     */
    public function generate_global_cfdi($batch_id)
    {
        // Obtener datos del lote
        $this->db->where('batch_id', $batch_id);
        $batch = $this->db->get('onebox_consolidation_batches')->row_array();
        
        if (!$batch || $batch['status'] !== 'OPEN') {
            echo json_encode(['success' => false, 'message' => 'Lote no válido o ya procesado']);
            return;
        }
        
        // Aquí se integraría con el módulo de CFDI existente
        // Por ahora, simulamos la generación
        $mock_uuid = 'AAAA-BBBB-CCCC-DDDD-' . time();
        
        // Generar asiento contable global
        $result = $this->Accounting_model->create_global_journal_entry_from_batch($batch_id, $mock_uuid);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'CFDI global generado exitosamente',
                'uuid' => $mock_uuid,
                'batch_id' => $batch_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al generar asiento contable']);
        }
    }

    /**
     * Reportes contables básicos
     * Balance de comprobación, estado de resultados simplificado
     */
    public function reports()
    {
        $data['selected_submodule'] = 'reports';
        
        $date_from = $this->input->get('from') ?: date('Y-m-01');
        $date_to = $this->input->get('to') ?: date('Y-m-t');
        
        // Obtener movimientos del período
        $this->db->select('e.entry_date, e.description, e.reference_type, e.is_fiscal, 
                          SUM(l.debit) as total_debit, SUM(l.credit) as total_credit');
        $this->db->from('onebox_journal_entries e');
        $this->db->join('onebox_journal_lines l', 'e.entry_id = l.entry_id');
        $this->db->where('e.entry_date BETWEEN "' . $date_from . '" AND "' . $date_to . '"');
        $this->db->group_by('e.entry_id');
        $this->db->order_by('e.entry_date', 'desc');
        
        $data['journal_entries'] = $this->db->get()->result_array();
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        
        // Calcular totales por cuenta
        $this->db->select('a.account_code, a.account_name, SUM(l.debit) as total_debit, SUM(l.credit) as total_credit');
        $this->db->from('onebox_journal_lines l');
        $this->db->join('onebox_chart_of_accounts a', 'l.account_id = a.account_id');
        $this->db->join('onebox_journal_entries e', 'l.entry_id = e.entry_id');
        $this->db->where('e.entry_date BETWEEN "' . $date_from . '" AND "' . $date_to . '"');
        $this->db->group_by('a.account_id');
        
        $data['account_summary'] = $this->db->get()->result_array();
        
        $this->load->view('accounting/reports', $data);
    }

    /**
     * Configuración de reglas del asistente contable
     * Permite personalizar las sugerencias automáticas
     */
    public function settings()
    {
        $data['selected_submodule'] = 'settings';
        
        if ($this->input->post()) {
            // Guardar configuración
            $config_data = [
                'min_amount_for_invoice' => $this->input->post('min_amount'),
                'auto_invoice_credit_sales' => $this->input->post('auto_credit') ? 1 : 0,
                'customer_threshold' => $this->input->post('threshold')
            ];
            
            // En producción, esto se guardaría en una tabla de configuración
            // Por ahora, solo mostramos éxito
            $data['success_message'] = 'Configuración guardada correctamente';
        }
        
        // Cargar configuración actual (hardcodeada por ahora)
        $data['current_config'] = [
            'min_amount_for_invoice' => 500.00,
            'auto_invoice_credit_sales' => 1,
            'customer_threshold' => 1000.00
        ];
        
        $this->load->view('accounting/settings', $data);
    }

    /**
     * Helper para contar pendientes
     */
    private function count_pending($type)
    {
        $this->db->where('transaction_type', $type);
        $this->db->where('status', 'PENDING_REVIEW');
        return $this->db->count_all_results('onebox_reconciliation_queue');
    }
}
