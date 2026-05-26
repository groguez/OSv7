<?php
/**
 * Modelo de Planeación Estratégica
 * 
 * Gestiona planes estratégicos, proyecciones de ventas
 * Comparación Proforma vs Forecast vs Real
 * Motor de sugerencias Smart Insights
 */

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/SmartInsights/PredictionEngine.php';

class Strategic_planning_model extends CI_Model {
    
    protected $predictionEngine;
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->predictionEngine = new PredictionEngine();
    }
    
    /**
     * Crear nuevo plan estratégico
     */
    public function createPlan($data) {
        $planData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'type' => $data['type'], // 'ventas', 'gastos', 'general'
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'draft',
            'created_by' => $this->session->userdata('employee_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('strategic_plans', $planData);
        $planId = $this->db->insert_id();
        
        // Guardar metas/proyecciones si existen
        if (isset($data['projections'])) {
            foreach ($data['projections'] as $projection) {
                $this->saveProjection($planId, $projection);
            }
        }
        
        return $planId;
    }
    
    /**
     * Guardar proyección individual
     */
    public function saveProjection($planId, $projection) {
        $projData = [
            'plan_id' => $planId,
            'period' => $projection['period'], // '2026-01', '2026-Q1', etc.
            'forecast_amount' => $projection['forecast'],
            'proforma_amount' => $projection['proforma'] ?? null,
            'actual_amount' => $projection['actual'] ?? null,
            'notes' => $projection['notes'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('plan_projections', $projData);
        return $this->db->insert_id();
    }
    
    /**
     * Obtener sugerencias del motor Smart Insights
     */
    public function getSuggestions($type = 'ventas', $months = 12) {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$months} months"));
        
        // Obtener datos históricos
        $historicalData = $this->getHistoricalData($type, $startDate, $endDate);
        
        // Generar predicciones
        $predictions = $this->predictionEngine->predict($historicalData, $months);
        
        // Obtener factores de contexto
        $contextFactors = $this->getContextFactors();
        
        // Ajustar predicciones con contexto
        $adjustedPredictions = $this->adjustWithContext($predictions, $contextFactors);
        
        return [
            'predictions' => $adjustedPredictions,
            'confidence' => $this->predictionEngine->getConfidenceLevel(),
            'factors' => $contextFactors,
            'recommendations' => $this->generateRecommendations($adjustedPredictions, $contextFactors)
        ];
    }
    
    /**
     * Obtener datos históricos de ventas/gastos
     */
    protected function getHistoricalData($type, $startDate, $endDate) {
        $data = [];
        
        if ($type === 'ventas') {
            $query = $this->db->query("
                SELECT 
                    DATE_FORMAT(sale_time, '%Y-%m') as period,
                    SUM(total) as amount,
                    COUNT(*) as transactions
                FROM sales
                WHERE sale_time BETWEEN ? AND ?
                AND deleted = 0
                GROUP BY DATE_FORMAT(sale_time, '%Y-%m')
                ORDER BY period ASC
            ", [$startDate, $endDate]);
        } else {
            $query = $this->db->query("
                SELECT 
                    DATE_FORMAT(expense_date, '%Y-%m') as period,
                    SUM(amount) as amount,
                    COUNT(*) as transactions
                FROM expenses
                WHERE expense_date BETWEEN ? AND ?
                AND deleted = 0
                GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
                ORDER BY period ASC
            ", [$startDate, $endDate]);
        }
        
        foreach ($query->result() as $row) {
            $data[] = [
                'period' => $row->period,
                'amount' => (float)$row->amount,
                'transactions' => (int)$row->transactions
            ];
        }
        
        return $data;
    }
    
    /**
     * Obtener factores de contexto de negocio
     */
    protected function getContextFactors() {
        $factors = [];
        
        // Temporadas/promociones activas
        $this->db->where('start_date <=', date('Y-m-d'));
        $this->db->where('end_date >=', date('Y-m-d'));
        $activePromos = $this->db->count_all_results('price_rules');
        $factors['active_promotions'] = $activePromos > 0;
        
        // Stock disponible
        $this->db->select_sum('quantity');
        $totalStock = $this->db->get('inventory')->row()->quantity ?? 0;
        $factors['stock_level'] = $totalStock > 1000 ? 'high' : ($totalStock > 500 ? 'medium' : 'low');
        
        // Día de la semana/mes
        $currentDay = (int)date('d');
        $factors['is_month_end'] = $currentDay >= 25;
        $factors['is_weekend'] = in_array(date('N'), [6, 7]);
        
        return $factors;
    }
    
    /**
     * Ajustar predicciones con factores de contexto
     */
    protected function adjustWithContext($predictions, $factors) {
        $adjusted = $predictions;
        
        // Ajuste por promociones activas (+10% si hay promos)
        if ($factors['active_promotions']) {
            foreach ($adjusted as &$pred) {
                $pred['adjusted_amount'] = $pred['amount'] * 1.10;
                $pred['adjustment_reason'] = 'Promociones activas';
            }
        }
        
        // Ajuste por nivel de stock
        if ($factors['stock_level'] === 'low') {
            foreach ($adjusted as &$pred) {
                $pred['adjusted_amount'] = $pred['amount'] * 0.85;
                $pred['adjustment_reason'] = ($pred['adjustment_reason'] ?? '') . ' | Stock bajo';
            }
        }
        
        return $adjusted;
    }
    
    /**
     * Generar recomendaciones accionables
     */
    protected function generateRecommendations($predictions, $factors) {
        $recommendations = [];
        
        // Analizar tendencia
        $trend = $this->analyzeTrend($predictions);
        
        if ($trend === 'increasing') {
            $recommendations[] = [
                'type' => 'opportunity',
                'title' => 'Tendencia de Crecimiento',
                'message' => 'Las ventas muestran una tendencia al alza. Considere aumentar inventario.',
                'priority' => 'high'
            ];
        } elseif ($trend === 'decreasing') {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Tendencia a la Baja',
                'message' => 'Se proyecta una disminución en ventas. Revise estrategias de marketing.',
                'priority' => 'high'
            ];
        }
        
        // Recomendaciones por factores
        if ($factors['stock_level'] === 'low') {
            $recommendations[] = [
                'type' => 'alert',
                'title' => 'Stock Bajo',
                'message' => 'El nivel de inventario es bajo. Realice un pedido a proveedores.',
                'priority' => 'critical'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Analizar tendencia de predicciones
     */
    protected function analyzeTrend($predictions) {
        if (count($predictions) < 2) {
            return 'stable';
        }
        
        $firstHalf = array_slice($predictions, 0, count($predictions) / 2);
        $secondHalf = array_slice($predictions, count($predictions) / 2);
        
        $avgFirst = array_sum(array_column($firstHalf, 'amount')) / count($firstHalf);
        $avgSecond = array_sum(array_column($secondHalf, 'amount')) / count($secondHalf);
        
        $change = ($avgSecond - $avgFirst) / $avgFirst;
        
        if ($change > 0.05) {
            return 'increasing';
        } elseif ($change < -0.05) {
            return 'decreasing';
        }
        
        return 'stable';
    }
    
    /**
     * Guardar ajuste manual del usuario (para aprendizaje)
     */
    public function saveManualAdjustment($projectionId, $originalValue, $adjustedValue, $reason) {
        $data = [
            'projection_id' => $projectionId,
            'original_value' => $originalValue,
            'adjusted_value' => $adjustedValue,
            'adjustment_reason' => $reason,
            'adjusted_by' => $this->session->userdata('employee_id'),
            'adjusted_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('projection_adjustments', $data);
        
        // Actualizar proyección
        $this->db->where('id', $projectionId);
        $this->db->update('plan_projections', [
            'proforma_amount' => $adjustedValue,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    }
    
    /**
     * Obtener comparativa Proforma vs Forecast vs Real
     */
    public function getComparison($planId) {
        $this->db->where('plan_id', $planId);
        $projections = $this->db->get('plan_projections')->result_array();
        
        $comparison = [];
        foreach ($projections as $proj) {
            $comparison[] = [
                'period' => $proj['period'],
                'forecast' => (float)$proj['forecast_amount'],
                'proforma' => (float)$proj['proforma_amount'],
                'actual' => (float)$proj['actual_amount'],
                'variance_forecast' => $proj['actual_amount'] ? 
                    (($proj['actual_amount'] - $proj['forecast_amount']) / $proj['forecast_amount']) * 100 : null,
                'variance_proforma' => $proj['actual_amount'] ? 
                    (($proj['actual_amount'] - $proj['proforma_amount']) / $proj['proforma_amount']) * 100 : null
            ];
        }
        
        return $comparison;
    }
    
    /**
     * Actualizar montos reales desde ventas/expenses
     */
    public function updateActualAmounts($planId) {
        $projections = $this->db->where('plan_id', $planId)->get('plan_projections')->result();
        
        foreach ($projections as $proj) {
            $period = $proj->period; // formato YYYY-MM
            
            // Obtener total real de ventas del período
            $query = $this->db->query("
                SELECT SUM(total) as total
                FROM sales
                WHERE DATE_FORMAT(sale_time, '%Y-%m') = ?
                AND deleted = 0
            ", [$period]);
            
            $actual = $query->row()->total ?? 0;
            
            // Actualizar proyección
            $this->db->where('id', $proj->id);
            $this->db->update('plan_projections', ['actual_amount' => $actual]);
        }
        
        return true;
    }
}
