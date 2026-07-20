<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * OneBox - Asistente Contable Inteligente
 * Modelo principal para conciliación y generación de asientos contables
 * 
 * Lógica: 
 * - Todas las ventas/compras van a la cola de conciliación
 * - El sistema sugiere automáticamente qué facturar
 * - El usuario decide manualmente o acepta sugerencias
 * - Se generan asientos contables automáticos
 * - Los no facturados se consolidan en lotes globales
 */
class Accounting_model extends CI_Model
{
    // Reglas de negocio para sugerencias automáticas
    private $auto_invoice_rules = [
        'min_amount_for_invoice' => 500.00, // Sugerir facturar si > $500
        'require_invoice_for_credit' => true, // Siempre facturar ventas a crédito
        'default_customer_threshold' => 1000.00, // Clientes frecuentes > $1000
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Agrega una transacción a la cola de conciliación
     * Se llama automáticamente desde POS/Ventas/Compras
     */
    public function add_to_reconciliation_queue($transaction_type, $transaction_id, $amount, $tax_amount = 0, $customer_supplier_id = null)
    {
        $data = [
            'transaction_type' => $transaction_type, // 'SALE' o 'PURCHASE'
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'tax_amount' => $tax_amount,
            'customer_supplier_id' => $customer_supplier_id,
            'transaction_date' => date('Y-m-d H:i:s'),
            'status' => 'PENDING_REVIEW',
            'suggestion_reason' => $this->generate_auto_suggestion($transaction_type, $amount, $customer_supplier_id)
        ];

        $this->db->insert('onebox_reconciliation_queue', $data);
        return $this->db->insert_id();
    }

    /**
     * Genera sugerencia automática basada en reglas de negocio
     * Esta es la "IA" básica del asistente contable
     */
    private function generate_auto_suggestion($transaction_type, $amount, $customer_supplier_id)
    {
        $suggestions = [];

        // Regla 1: Monto alto siempre sugiere facturar
        if ($amount >= $this->auto_invoice_rules['min_amount_for_invoice']) {
            $suggestions[] = "Monto superior a $" . number_format($this->auto_invoice_rules['min_amount_for_invoice'], 2);
        }

        // Regla 2: Verificar si es cliente/proveedor frecuente
        if ($customer_supplier_id) {
            $total_history = $this->get_transaction_history($transaction_type, $customer_supplier_id);
            if ($total_history >= $this->auto_invoice_rules['default_customer_threshold']) {
                $suggestions[] = "Cliente/Proveedor frecuente con historial > $" . number_format($this->auto_invoice_rules['default_customer_threshold'], 2);
            }
        }

        // Regla 3: Ventas a crédito siempre requieren factura
        if ($transaction_type === 'SALE') {
            $sale_info = $this->get_sale_payment_info($transaction_id);
            if ($sale_info && $sale_info['payment_type'] === 'credit') {
                $suggestions[] = "Venta a crédito requiere comprobantes";
            }
        }

        if (empty($suggestions)) {
            return null;
        }

        return implode('; ', $suggestions);
    }

    /**
     * Obtiene todas las transacciones pendientes de conciliación
     */
    public function get_reconciliation_queue($filters = [])
    {
        $this->db->select('q.*, c.name as customer_name, s.name as supplier_name');
        $this->db->from('onebox_reconciliation_queue q');
        $this->db->join('customers c', 'q.customer_supplier_id = c.person_id', 'left');
        $this->db->join('suppliers s', 'q.customer_supplier_id = s.person_id', 'left');
        
        if (isset($filters['status'])) {
            $this->db->where('q.status', $filters['status']);
        }
        
        if (isset($filters['transaction_type'])) {
            $this->db->where('q.transaction_type', $filters['transaction_type']);
        }
        
        if (isset($filters['date_from'])) {
            $this->db->where('q.transaction_date >=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $this->db->where('q.transaction_date <=', $filters['date_to']);
        }

        $this->db->order_by('q.transaction_date', 'desc');
        return $this->db->get()->result_array();
    }

    /**
     * Procesa la decisión del usuario sobre una transacción
     * Opciones: FACTURED, NON_FACTURED_IGNORED, NON_FACTURED_GLOBAL
     */
    public function process_reconciliation_decision($queue_id, $decision, $cfdi_uuid = null, $user_id = null)
    {
        $this->db->trans_start();

        // Actualizar estado en cola
        $update_data = [
            'status' => $decision,
            'reviewed_by' => $user_id,
            'reviewed_at' => date('Y-m-d H:i:s')
        ];

        if ($cfdi_uuid) {
            $update_data['cfdi_uuid'] = $cfdi_uuid;
        }

        $this->db->where('queue_id', $queue_id);
        $this->db->update('onebox_reconciliation_queue', $update_data);

        // Obtener datos de la transacción
        $this->db->where('queue_id', $queue_id);
        $queue_item = $this->db->get('onebox_reconciliation_queue')->row_array();

        if (!$queue_item) {
            $this->db->trans_rollback();
            return false;
        }

        // Generar asiento contable según decisión
        if ($decision === 'FACTURED') {
            $this->create_journal_entry_from_transaction(
                $queue_item['transaction_type'],
                $queue_item['transaction_id'],
                $queue_item['amount'],
                $queue_item['tax_amount'],
                true, // is_fiscal
                $cfdi_uuid
            );
        } elseif ($decision === 'NON_FACTURED_GLOBAL') {
            // Agregar a lote de consolidación
            $batch_id = $this->add_to_consolidation_batch($queue_item);
            $this->db->where('queue_id', $queue_id);
            $this->db->update('onebox_reconciliation_queue', ['consolidation_batch_id' => $batch_id]);
        }
        // NON_FACTURED_IGNORED no genera asiento, solo registro de control

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Crea asiento contable automático desde transacción
     */
    private function create_journal_entry_from_transaction($transaction_type, $transaction_id, $amount, $tax_amount, $is_fiscal = false, $cfdi_uuid = null)
    {
        $entry_data = [
            'entry_date' => date('Y-m-d H:i:s'),
            'reference_type' => $transaction_type,
            'reference_id' => $transaction_id,
            'description' => ($transaction_type === 'SALE' ? 'Venta' : 'Compra') . ' #' . $transaction_id . ($is_fiscal ? ' (Facturada)' : ''),
            'is_posted' => 1,
            'is_fiscal' => $is_fiscal ? 1 : 0,
            'cfdi_uuid' => $cfdi_uuid
        ];

        $this->db->insert('onebox_journal_entries', $entry_data);
        $entry_id = $this->db->insert_id();

        // Definir cuentas según tipo de transacción (esto debería venir de configuración)
        if ($transaction_type === 'SALE') {
            // Venta: Cargo a Banco/Caja, Abono a Ingresos e IVA
            $lines = [
                ['account_code' => '1101', 'debit' => $amount + $tax_amount, 'credit' => 0, 'description' => 'Banco por venta'],
                ['account_code' => '4200', 'debit' => 0, 'credit' => $amount, 'description' => 'Ingreso por venta'],
                ['account_code' => '2200', 'debit' => 0, 'credit' => $tax_amount, 'description' => 'IVA trasladado']
            ];
        } else {
            // Compra: Cargo a Inventario/Gasto e IVA, Abono a Proveedores/Banco
            $lines = [
                ['account_code' => '5100', 'debit' => $amount, 'credit' => 0, 'description' => 'Costo/Compra'],
                ['account_code' => '5200', 'debit' => $tax_amount, 'credit' => 0, 'description' => 'IVA acreditable'],
                ['account_code' => '2100', 'debit' => 0, 'credit' => $amount + $tax_amount, 'description' => 'Proveedor por pagar']
            ];
        }

        foreach ($lines as $line) {
            $this->db->select('account_id');
            $this->db->where('account_code', $line['account_code']);
            $account = $this->db->get('onebox_chart_of_accounts')->row_array();

            if ($account) {
                $line_data = [
                    'entry_id' => $entry_id,
                    'account_id' => $account['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description']
                ];
                $this->db->insert('onebox_journal_lines', $line_data);
            }
        }

        return $entry_id;
    }

    /**
     * Agrega transacción a lote de consolidación global
     */
    private function add_to_consolidation_batch($queue_item)
    {
        $batch_date = date('Y-m-d', strtotime($queue_item['transaction_date']));

        // Buscar lote abierto del día
        $this->db->where('batch_date', $batch_date);
        $this->db->where('status', 'OPEN');
        $existing_batch = $this->db->get('onebox_consolidation_batches')->row_array();

        if ($existing_batch) {
            $batch_id = $existing_batch['batch_id'];
            // Actualizar totales
            $this->db->set('total_amount', 'total_amount + ' . $queue_item['amount'], FALSE);
            $this->db->set('total_tax', 'total_tax + ' . $queue_item['tax_amount'], FALSE);
            $this->db->set('transaction_count', 'transaction_count + 1', FALSE);
            $this->db->where('batch_id', $batch_id);
            $this->db->update('onebox_consolidation_batches');
        } else {
            // Crear nuevo lote
            $batch_data = [
                'batch_date' => $batch_date,
                'total_amount' => $queue_item['amount'],
                'total_tax' => $queue_item['tax_amount'],
                'transaction_count' => 1,
                'status' => 'OPEN'
            ];
            $this->db->insert('onebox_consolidation_batches', $batch_data);
            $batch_id = $this->db->insert_id();
        }

        return $batch_id;
    }

    /**
     * Genera asiento contable global desde lote consolidado
     * Se llama cuando se decide facturar todo el lote o al cierre del día
     */
    public function create_global_journal_entry_from_batch($batch_id, $cfdi_uuid = null)
    {
        $this->db->trans_start();

        // Obtener datos del lote
        $this->db->where('batch_id', $batch_id);
        $batch = $this->db->get('onebox_consolidation_batches')->row_array();

        if (!$batch) {
            return false;
        }

        // Crear asiento global
        $entry_data = [
            'entry_date' => date('Y-m-d H:i:s'),
            'reference_type' => 'CONSOLIDATION',
            'reference_id' => $batch_id,
            'description' => 'Venta Global Consolidada (' . $batch['transaction_count'] . ' operaciones)',
            'is_posted' => 1,
            'is_fiscal' => $cfdi_uuid ? 1 : 0,
            'cfdi_uuid' => $cfdi_uuid
        ];

        $this->db->insert('onebox_journal_entries', $entry_data);
        $entry_id = $this->db->insert_id();

        // Líneas contables globales
        $total_with_tax = $batch['total_amount'] + $batch['total_tax'];
        $lines = [
            ['account_code' => '1101', 'debit' => $total_with_tax, 'credit' => 0, 'description' => 'Banco por ventas globales'],
            ['account_code' => '4100', 'debit' => 0, 'credit' => $batch['total_amount'], 'description' => 'Ingresos mostrador (no facturados individualmente)'],
            ['account_code' => '2200', 'debit' => 0, 'credit' => $batch['total_tax'], 'description' => 'IVA trasladado global']
        ];

        foreach ($lines as $line) {
            $this->db->select('account_id');
            $this->db->where('account_code', $line['account_code']);
            $account = $this->db->get('onebox_chart_of_accounts')->row_array();

            if ($account) {
                $line_data = [
                    'entry_id' => $entry_id,
                    'account_id' => $account['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description']
                ];
                $this->db->insert('onebox_journal_lines', $line_data);
            }
        }

        // Cerrar lote
        $this->db->where('batch_id', $batch_id);
        $this->db->update('onebox_consolidation_batches', ['status' => $cfdi_uuid ? 'TIMBRADO' : 'CLOSED']);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Helpers para obtener información de transacciones
     */
    private function get_transaction_history($transaction_type, $person_id)
    {
        $table = ($transaction_type === 'SALE') ? 'sales' : 'receivings';
        $field = ($transaction_type === 'SALE') ? 'customer_id' : 'supplier_id';

        $this->db->select_sum('total');
        $this->db->where($field, $person_id);
        $result = $this->db->get($table)->row_array();
        return $result['total'] ?? 0;
    }

    private function get_sale_payment_info($sale_id)
    {
        $this->db->select('payment_type');
        $this->db->where('sale_id', $sale_id);
        return $this->db->get('sales')->row_array();
    }

    /**
     * Reportes para el dashboard contable
     */
    public function get_accounting_dashboard_summary($date_from, $date_to)
    {
        // Total ventas pendientes de conciliar
        $this->db->select_sum('amount');
        $this->db->where('transaction_type', 'SALE');
        $this->db->where('status', 'PENDING_REVIEW');
        $pending_sales = $this->db->get('onebox_reconciliation_queue')->row_array();

        // Total compras pendientes
        $this->db->select_sum('amount');
        $this->db->where('transaction_type', 'PURCHASE');
        $this->db->where('status', 'PENDING_REVIEW');
        $pending_purchases = $this->db->get('onebox_reconciliation_queue')->row_array();

        // Asientos contables del período
        $this->db->select('SUM(debit) as total_debit, SUM(credit) as total_credit');
        $this->db->join('onebox_journal_lines l', 'e.entry_id = l.entry_id');
        $this->db->where('e.entry_date BETWEEN "' . $date_from . '" AND "' . $date_to . '"');
        $journal_summary = $this->db->get('onebox_journal_entries e')->row_array();

        // Lotes consolidados pendientes
        $this->db->select('COUNT(*) as count, SUM(total_amount) as total');
        $this->db->where('status', 'OPEN');
        $open_batches = $this->db->get('onebox_consolidation_batches')->row_array();

        return [
            'pending_sales' => $pending_sales['amount'] ?? 0,
            'pending_purchases' => $pending_purchases['amount'] ?? 0,
            'total_debits' => $journal_summary['total_debit'] ?? 0,
            'total_credits' => $journal_summary['total_credit'] ?? 0,
            'open_batches_count' => $open_batches['count'] ?? 0,
            'open_batches_amount' => $open_batches['total'] ?? 0
        ];
    }

    /**
     * Obtiene los totales de debit/credit para un asiento contable
     * Usado en el dashboard
     */
    public function get_entry_totals($entry_id)
    {
        $this->db->select('SUM(debit) as total_debit, SUM(credit) as total_credit');
        $this->db->where('entry_id', $entry_id);
        $result = $this->db->get('onebox_journal_lines')->row_array();
        
        return [
            $result['total_debit'] ?? 0,
            $result['total_credit'] ?? 0
        ];
    }
}
