<?php
/**
 * Modelo de Gestión de Suscripciones
 * 
 * Maneja planes de suscripción (Trial, Mensual, Anual, Lifetime)
 * Validación de estado activo/vencido
 * Asignación a empleados/usuarios
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription_model extends CI_Model {
    
    const PLAN_TRIAL = 'trial';
    const PLAN_MENSUAL = 'mensual';
    const PLAN_ANUAL = 'anual';
    const PLAN_LIFETIME = 'lifetime';
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Obtener suscripción activa de un empleado
     */
    public function getActiveSubscription($employeeId) {
        $this->db->where('employee_id', $employeeId);
        $this->db->where('status', 'active');
        $this->db->order_by('end_date', 'DESC');
        
        $query = $this->db->get('employee_subscriptions');
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        
        return null;
    }
    
    /**
     * Obtener todos los planes disponibles
     */
    public function getAvailablePlans() {
        $plans = [
            self::PLAN_TRIAL => [
                'name' => 'Prueba Gratuita',
                'duration_days' => 14,
                'price' => 0,
                'features' => ['Hasta 100 ventas/mes', '1 usuario', 'Soporte básico']
            ],
            self::PLAN_MENSUAL => [
                'name' => 'Plan Mensual',
                'duration_days' => 30,
                'price' => 29.99,
                'features' => ['Ventas ilimitadas', 'Usuarios ilimitados', 'Soporte prioritario', 'Reportes avanzados']
            ],
            self::PLAN_ANUAL => [
                'name' => 'Plan Anual',
                'duration_days' => 365,
                'price' => 299.99,
                'features' => ['Todo lo del plan mensual', '2 meses gratis', 'Capacitación incluida', 'API access']
            ],
            self::PLAN_LIFETIME => [
                'name' => 'Licencia Vitalicia',
                'duration_days' => 99999,
                'price' => 999.99,
                'features' => ['Acceso de por vida', 'Actualizaciones incluidas', 'Soporte VIP', 'Personalización']
            ]
        ];
        
        return $plans;
    }
    
    /**
     * Crear nueva suscripción para empleado
     */
    public function createSubscription($employeeId, $planType, $startDate = null, $endDate = null) {
        $plans = $this->getAvailablePlans();
        
        if (!isset($plans[$planType])) {
            return false;
        }
        
        $startDate = $startDate ?? date('Y-m-d H:i:s');
        
        // Calcular fecha fin si no se proporcionó
        if (!$endDate) {
            $durationDays = $plans[$planType]['duration_days'];
            $endDate = date('Y-m-d H:i:s', strtotime($startDate . " +{$durationDays} days"));
        }
        
        $data = [
            'employee_id' => $employeeId,
            'plan_type' => $planType,
            'plan_name' => $plans[$planType]['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $this->session->userdata('employee_id')
        ];
        
        $this->db->insert('employee_subscriptions', $data);
        
        return $this->db->insert_id();
    }
    
    /**
     * Actualizar suscripción
     */
    public function updateSubscription($subscriptionId, $data) {
        $this->db->where('id', $subscriptionId);
        return $this->db->update('employee_subscriptions', $data);
    }
    
    /**
     * Cancelar suscripción
     */
    public function cancelSubscription($subscriptionId, $reason = '') {
        $data = [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $reason
        ];
        
        $this->db->where('id', $subscriptionId);
        return $this->db->update('employee_subscriptions', $data);
    }
    
    /**
     * Obtener historial de suscripciones de un empleado
     */
    public function getSubscriptionHistory($employeeId) {
        $this->db->where('employee_id', $employeeId);
        $this->db->order_by('created_at', 'DESC');
        
        $query = $this->db->get('employee_subscriptions');
        
        return $query->result();
    }
    
    /**
     * Verificar si empleado tiene suscripción activa
     */
    public function hasActiveSubscription($employeeId) {
        $subscription = $this->getActiveSubscription($employeeId);
        return $subscription !== null;
    }
    
    /**
     * Obtener estadísticas de suscripciones
     */
    public function getSubscriptionStats() {
        $stats = [];
        
        // Total por tipo de plan
        $this->db->select('plan_type, COUNT(*) as total');
        $this->db->where('status', 'active');
        $this->db->group_by('plan_type');
        $query = $this->db->get('employee_subscriptions');
        $stats['by_plan'] = $query->result_array();
        
        // Próximos a vencer (7 días)
        $this->db->where('status', 'active');
        $this->db->where('end_date <=', date('Y-m-d H:i:s', strtotime('+7 days')));
        $stats['expiring_soon'] = $this->db->count_all_results('employee_subscriptions');
        
        // Vencidos
        $this->db->where('status', 'active');
        $this->db->where('end_date <', date('Y-m-d H:i:s'));
        $stats['expired'] = $this->db->count_all_results('employee_subscriptions');
        
        return $stats;
    }
    
    /**
     * Renovar suscripción
     */
    public function renewSubscription($subscriptionId, $newEndDate = null) {
        $subscription = $this->db->where('id', $subscriptionId)->get('employee_subscriptions')->row();
        
        if (!$subscription) {
            return false;
        }
        
        $plans = $this->getAvailablePlans();
        $planType = $subscription->plan_type;
        
        // Calcular nueva fecha fin
        if (!$newEndDate) {
            $durationDays = $plans[$planType]['duration_days'];
            $baseDate = strtotime($subscription->end_date) > time() 
                ? $subscription->end_date 
                : date('Y-m-d H:i:s');
            $newEndDate = date('Y-m-d H:i:s', strtotime($baseDate . " +{$durationDays} days"));
        }
        
        $data = [
            'end_date' => $newEndDate,
            'status' => 'active',
            'renewed_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('id', $subscriptionId);
        return $this->db->update('employee_subscriptions', $data);
    }
}
