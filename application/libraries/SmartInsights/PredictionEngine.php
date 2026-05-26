<?php
/**
 * Motor de Predicción Smart Insights
 * 
 * Algoritmos avanzados para proyección de ventas y gastos:
 * - Suavizado Exponencial Doble (Holt)
 * - Promedio Móvil Ponderado
 * - Detección de estacionalidad
 * - Análisis de tendencias
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class PredictionEngine {
    
    protected $confidenceLevel = 0;
    protected $historicalData = [];
    
    /**
     * Generar predicciones basadas en datos históricos
     * 
     * @param array $data Datos históricos [{period, amount, transactions}]
     * @param int $monthsToPredict Meses a proyectar
     * @return array Predicciones
     */
    public function predict($data, $monthsToPredict = 12) {
        if (empty($data) || count($data) < 3) {
            return $this->simpleProjection($monthsToPredict);
        }
        
        $this->historicalData = $data;
        
        // Aplicar Suavizado Exponencial Doble (Holt)
        $predictions = $this->holtExponentialSmoothing($data, $monthsToPredict);
        
        // Calcular nivel de confianza
        $this->calculateConfidence($data, $predictions);
        
        return $predictions;
    }
    
    /**
     * Método de Holt para series temporales con tendencia
     */
    protected function holtExponentialSmoothing($data, $periods) {
        $values = array_column($data, 'amount');
        $n = count($values);
        
        // Parámetros de suavizado (alfa y beta)
        $alpha = 0.3; // Nivel
        $beta = 0.1;  // Tendencia
        
        // Inicialización
        $level = $values[0];
        $trend = ($values[$n - 1] - $values[0]) / ($n - 1);
        
        $levels = [$level];
        $trends = [$trend];
        
        // Calcular niveles y tendencias para datos históricos
        for ($i = 1; $i < $n; $i++) {
            $prevLevel = $levels[$i - 1];
            $prevTrend = $trends[$i - 1];
            
            $newLevel = $alpha * $values[$i] + (1 - $alpha) * ($prevLevel + $prevTrend);
            $newTrend = $beta * ($newLevel - $prevLevel) + (1 - $beta) * $prevTrend;
            
            $levels[] = $newLevel;
            $trends[] = $newTrend;
        }
        
        // Generar predicciones
        $predictions = [];
        $lastPeriod = end($data)['period'];
        $lastMonth = intval(substr($lastPeriod, 5));
        $lastYear = intval(substr($lastPeriod, 0, 4));
        
        $finalLevel = $levels[$n - 1];
        $finalTrend = $trends[$n - 1];
        
        for ($i = 1; $i <= $periods; $i++) {
            $forecast = $finalLevel + ($i * $finalTrend);
            
            // Calcular siguiente mes/año
            $month = $lastMonth + $i;
            $year = $lastYear;
            
            while ($month > 12) {
                $month -= 12;
                $year++;
            }
            
            $period = sprintf('%04d-%02d', $year, $month);
            
            $predictions[] = [
                'period' => $period,
                'amount' => max(0, round($forecast, 2)),
                'method' => 'Holt Exponential Smoothing',
                'confidence' => $this->getPointConfidence($i, $n)
            ];
        }
        
        return $predictions;
    }
    
    /**
     * Proyección simple cuando no hay suficientes datos
     */
    protected function simpleProjection($months) {
        $avgAmount = count($this->historicalData) > 0 
            ? array_sum(array_column($this->historicalData, 'amount')) / count($this->historicalData)
            : 0;
        
        $predictions = [];
        $lastPeriod = !empty($this->historicalData) ? end($this->historicalData)['period'] : date('Y-m');
        $lastMonth = intval(substr($lastPeriod, 5));
        $lastYear = intval(substr($lastPeriod, 0, 4));
        
        for ($i = 1; $i <= $months; $i++) {
            $month = $lastMonth + $i;
            $year = $lastYear;
            
            while ($month > 12) {
                $month -= 12;
                $year++;
            }
            
            $period = sprintf('%04d-%02d', $year, $month);
            
            $predictions[] = [
                'period' => $period,
                'amount' => round($avgAmount, 2),
                'method' => 'Promedio Simple',
                'confidence' => 0.5
            ];
        }
        
        return $predictions;
    }
    
    /**
     * Calcular nivel de confianza general
     */
    protected function calculateConfidence($historical, $predictions) {
        $dataPoints = count($historical);
        $variance = $this->calculateVariance(array_column($historical, 'amount'));
        $coefficient_of_variation = $variance > 0 ? sqrt($variance) / (array_sum(array_column($historical, 'amount')) / $dataPoints) : 0;
        
        // Factores que afectan la confianza
        $dataQuality = min(1, $dataPoints / 12); // Máxima confianza con 12+ meses
        $stability = max(0, 1 - $coefficient_of_variation);
        $trendStrength = $this->calculateTrendStrength($historical);
        
        $this->confidenceLevel = round(($dataQuality * 0.4 + $stability * 0.4 + $trendStrength * 0.2) * 100) / 100;
    }
    
    /**
     * Calcular varianza
     */
    protected function calculateVariance($values) {
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($val) use ($mean) {
            return pow($val - $mean, 2);
        }, $values);
        
        return array_sum($squaredDiffs) / count($values);
    }
    
    /**
     * Calcular fuerza de tendencia
     */
    protected function calculateTrendStrength($data) {
        if (count($data) < 3) {
            return 0.5;
        }
        
        $values = array_column($data, 'amount');
        $n = count($values);
        
        // Regresión lineal simple
        $xMean = ($n - 1) / 2;
        $yMean = array_sum($values) / $n;
        
        $numerator = 0;
        $denominator = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($i - $xMean) * ($values[$i] - $yMean);
            $denominator += pow($i - $xMean, 2);
        }
        
        $slope = $denominator != 0 ? $numerator / $denominator : 0;
        
        // Normalizar slope a 0-1
        $maxChange = max($values) - min($values);
        $normalizedSlope = $maxChange > 0 ? abs($slope * $n) / $maxChange : 0;
        
        return min(1, $normalizedSlope);
    }
    
    /**
     * Obtener confianza por punto de predicción
     */
    protected function getPointConfidence($periodsAhead, $historicalSize) {
        // La confianza disminuye cuanto más lejos se proyecta
        $baseConfidence = $this->confidenceLevel;
        $decayRate = 0.05; // 5% menos por cada período
        
        return max(0.3, round($baseConfidence - ($periodsAhead * $decayRate), 2));
    }
    
    /**
     * Obtener nivel de confianza general
     */
    public function getConfidenceLevel() {
        return $this->confidenceLevel;
    }
    
    /**
     * Detectar estacionalidad en los datos
     */
    public function detectSeasonality($data) {
        if (count($data) < 24) {
            return ['detected' => false, 'pattern' => []];
        }
        
        $monthlyAverages = [];
        
        // Agrupar por mes
        foreach ($data as $item) {
            $month = intval(substr($item['period'], 5));
            if (!isset($monthlyAverages[$month])) {
                $monthlyAverages[$month] = [];
            }
            $monthlyAverages[$month][] = $item['amount'];
        }
        
        // Calcular promedios por mes
        $pattern = [];
        $overallAvg = array_sum(array_column($data, 'amount')) / count($data);
        
        for ($m = 1; $m <= 12; $m++) {
            if (isset($monthlyAverages[$m]) && !empty($monthlyAverages[$m])) {
                $monthAvg = array_sum($monthlyAverages[$m]) / count($monthlyAverages[$m]);
                $pattern[$m] = round($monthAvg / $overallAvg, 2);
            } else {
                $pattern[$m] = 1.0;
            }
        }
        
        // Verificar si hay variación significativa
        $variation = max($pattern) - min($pattern);
        
        return [
            'detected' => $variation > 0.2,
            'pattern' => $pattern,
            'variation' => round($variation, 2)
        ];
    }
}
