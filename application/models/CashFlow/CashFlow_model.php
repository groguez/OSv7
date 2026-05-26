<?php
/**
 * Modelo de Flujo de Efectivo (Cash Flow)
 * 
 * Gestión de entidades financieras (bancos, cajas, billeteras)
 * Control de ingresos/egresos por método de pago
 * Conciliación y proyección de liquidez
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class CashFlow_model extends CI_Model {
    
    // Tipos de entidades financieras
    const TYPE_CAJA = 'caja';
    const TYPE_BANCO = 'banco';
    const TYPE_BILLETERA = 'billetera';
    const TYPE_TARJETA = 'tarjeta';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Obtener todas las entidades financieras
     */
    public function getFinancialEntities($activeOnly = true) {
        if ($activeOnly) {
            $this->db->where('deleted', 0);
        }
        
        $this->db->order_by('name', 'ASC');
        return $this->db->get('financial_entities')->result();
    }
    
    /**
     * Obtener entidad por ID
     */
    public function getEntityById($entityId) {
        return $this->db->where('id', $entityId)->get('financial_entities')->row();
    }
    
    /**
     * Crear nueva entidad financiera
     */
    public function createEntity($data) {
        $entityData = [
            'name' => $data['name'],
            'type' => $data['type'],
            'account_number' => $data['account_number'] ?? null,
            'initial_balance' => $data['initial_balance'] ?? 0,
            'current_balance' => $data['initial_balance'] ?? 0,
            'currency' => $data['currency'] ?? 'MXN',
            'description' => $data['description'] ?? '',
            'is_default' => $data['is_default'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->session->userdata('employee_id')
        ];
        
        $this->db->insert('financial_entities', $entityData);
        return $this->db->insert_id();
    }
    
    /**
     * Registrar movimiento de efectivo
     */
    public function recordTransaction($data) {
        $transaction = [
            'entity_id' => $data['entity_id'],
            'type' => $data['type'], // 'income' o 'expense'
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? '',
            'description' => $data['description'] ?? '',
            'sale_id' => $data['sale_id'] ?? null,
            'expense_id' => $data['expense_id'] ?? null,
            'transaction_date' => $data['transaction_date'] ?? date('Y-m-d H:i:s'),
            'created_by' => $this->session->userdata('employee_id')
        ];
        
        $this->db->insert('cash_flow_transactions', $transaction);
        $transactionId = $this->db->insert_id();
        
        // Actualizar saldo de la entidad
        $entity = $this->getEntityById($data['entity_id']);
        $newBalance = $data['type'] === 'income' 
            ? $entity->current_balance + $data['amount']
            : $entity->current_balance - $data['amount'];
        
        $this->db->where('id', $data['entity_id']);
        $this->db->update('financial_entities', ['current_balance' => $newBalance]);
        
        return $transactionId;
    }
    
    /**
     * Obtener movimientos por entidad y período
     */
    public function getTransactions($entityId = null, $startDate = null, $endDate = null) {
        if ($entityId) {
            $this->db->where('entity_id', $entityId);
        }
        
        if ($startDate) {
            $this->db->where('transaction_date >=', $startDate);
        }
        
        if ($endDate) {
            $this->db->where('transaction_date <=', $endDate);
        }
        
        $this->db->order_by('transaction_date', 'DESC');
        return $this->db->get('cash_flow_transactions')->result();
    }
    
    /**
     * Obtener resumen de flujo de efectivo
     */
    public function getCashFlowSummary($startDate, $endDate, $entityId = null) {
        $select = "
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
            COUNT(*) as total_transactions
        ";
        
        $this->db->select($select);
        
        if ($entityId) {
            $this->db->where('entity_id', $entityId);
        }
        
        $this->db->where('transaction_date >=', $startDate);
        $this->db->where('transaction_date <=', $endDate);
        
        $result = $this->db->get('cash_flow_transactions')->row();
        
        return [
            'total_income' => (float)($result->total_income ?? 0),
            'total_expense' => (float)($result->total_expense ?? 0),
            'net_flow' => (float)(($result->total_income ?? 0) - ($result->total_expense ?? 0)),
            'total_transactions' => (int)($result->total_transactions ?? 0)
        ];
    }
    
    /**
     * Obtener distribución por método de pago
     */
    public function getPaymentMethodDistribution($startDate, $endDate, $type = 'income') {
        $this->db->select('payment_method, SUM(amount) as total, COUNT(*) as count');
        $this->db->where('type', $type);
        $this->db->where('transaction_date >=', $startDate);
        $this->db->where('transaction_date <=', $endDate);
        $this->db->group_by('payment_method');
        
        return $this->db->get('cash_flow_transactions')->result_array();
    }
    
    /**
     * Proyección de flujo de efectivo
     */
    public function projectCashFlow($days = 30) {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-90 days'));
        
        // Obtener histórico de 90 días
        $historical = $this->getCashFlowDaily($startDate, $endDate);
        
        // Calcular promedio diario
        $avgDailyIncome = array_sum(array_column($historical, 'income')) / max(1, count($historical));
        $avgDailyExpense = array_sum(array_column($historical, 'expense')) / max(1, count($historical));
        
        // Obtener saldos actuales
        $entities = $this->getFinancialEntities();
        $totalCurrentBalance = array_sum(array_column($entities, 'current_balance'));
        
        // Proyectar
        $projections = [];
        $projectedBalance = $totalCurrentBalance;
        
        for ($i = 1; $i <= $days; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $projectedBalance += ($avgDailyIncome - $avgDailyExpense);
            
            $projections[] = [
                'date' => $date,
                'projected_income' => round($avgDailyIncome, 2),
                'projected_expense' => round($avgDailyExpense, 2),
                'projected_balance' => round($projectedBalance, 2)
            ];
        }
        
        return [
            'current_balance' => $totalCurrentBalance,
            'avg_daily_income' => round($avgDailyIncome, 2),
            'avg_daily_expense' => round($avgDailyExpense, 2),
            'projections' => $projections
        ];
    }
    
    /**
     * Obtener flujo diario histórico
     */
    protected function getCashFlowDaily($startDate, $endDate) {
        $query = $this->db->query("
            SELECT 
                DATE(transaction_date) as date,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
            FROM cash_flow_transactions
            WHERE transaction_date BETWEEN ? AND ?
            GROUP BY DATE(transaction_date)
            ORDER BY date ASC
        ", [$startDate, $endDate]);
        
        return $query->result_array();
    }
    
    /**
     * Conciliación de entidad
     */
    public function reconcile($entityId, $expectedBalance, $notes = '') {
        $entity = $this->getEntityById($entityId);
        
        if (!$entity) {
            return false;
        }
        
        $difference = $expectedBalance - $entity->current_balance;
        
        $reconciliation = [
            'entity_id' => $entityId,
            'expected_balance' => $expectedBalance,
            'actual_balance' => $entity->current_balance,
            'difference' => $difference,
            'notes' => $notes,
            'reconciled_by' => $this->session->userdata('employee_id'),
            'reconciled_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('cash_flow_reconciliations', $reconciliation);
        
        // Si hay diferencia, registrar ajuste
        if ($difference != 0) {
            $this->recordTransaction([
                'entity_id' => $entityId,
                'type' => $difference > 0 ? 'income' : 'expense',
                'amount' => abs($difference),
                'payment_method' => 'ajuste',
                'description' => 'Ajuste por conciliación: ' . $notes,
                'reference' => 'RECON-' . date('Ymd') . '-' . $entityId
            ]);
        }
        
        return true;
    }
    
    /**
     * Alertas de liquidez
     */
    public function getLiquidityAlerts($threshold = 1000) {
        $entities = $this->getFinancialEntities();
        $alerts = [];
        
        foreach ($entities as $entity) {
            if ($entity->current_balance < $threshold) {
                $alerts[] = [
                    'entity_id' => $entity->id,
                    'entity_name' => $entity->name,
                    'current_balance' => $entity->current_balance,
                    'threshold' => $threshold,
                    'severity' => $entity->current_balance < ($threshold / 2) ? 'critical' : 'warning',
                    'message' => "Saldo bajo en {$entity->name}: $" . number_format($entity->current_balance, 2)
                ];
            }
        }
        
        return $alerts;
    }
}
