<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modelo de Onboarding Inteligente
 * Maneja la configuración inicial del sistema y aplicación de presets por industria
 */
class Onboarding_model extends CI_Model 
{
    /**
     * Verificar si el onboarding ya fue completado
     */
    public function is_configured()
    {
        $this->load->helper('appconfig');
        return appconfig_get('onboarding_completed', '0') === '1';
    }

    /**
     * Obtener lista de industrias disponibles con sus características
     */
    public function get_industries()
    {
        return [
            'retail' => [
                'name' => 'Retail / Tienda Minorista',
                'icon' => 'fa-store',
                'description' => 'Tiendas de ropa, abarrotes, farmacias, ferreterías, accesorios',
                'features' => ['inventory_tracking', 'gift_cards', 'loyalty_points', 'barcode_scanning'],
                'modules' => ['sales', 'inventory', 'customers', 'giftcards', 'reports'],
                'presets' => [
                    'require_customer' => false,
                    'default_payment' => 'cash',
                    'print_receipt' => true,
                    'enable_giftcards' => true
                ]
            ],
            'restaurant' => [
                'name' => 'Restaurante / Alimentos y Bebidas',
                'icon' => 'fa-utensils',
                'description' => 'Restaurantes, cafeterías, bares, food trucks',
                'features' => ['table_management', 'modifiers', 'kitchen_display', 'tips', 'delivery'],
                'modules' => ['sales', 'inventory', 'customers', 'deliveries', 'reports'],
                'presets' => [
                    'require_table' => true,
                    'enable_modifiers' => true,
                    'enable_tips' => true,
                    'receipt_type' => 'kitchen_ticket'
                ]
            ],
            'service' => [
                'name' => 'Servicios / Talleres',
                'icon' => 'fa-tools',
                'description' => 'Talleres mecánicos, lavanderías, reparación de equipos, servicios profesionales',
                'features' => ['work_orders', 'appointments', 'service_tracking', 'labor_charges'],
                'modules' => ['sales', 'work_orders', 'customers', 'employees', 'reports'],
                'presets' => [
                    'require_work_order' => true,
                    'enable_labor' => true,
                    'track_service_status' => true
                ]
            ],
            'health' => [
                'name' => 'Salud / Clínica Médica',
                'icon' => 'fa-stethoscope',
                'description' => 'Consultorios médicos, clínicas, farmacias con consultorio',
                'features' => ['patient_records', 'appointments', 'prescriptions', 'insurance'],
                'modules' => ['sales', 'customers', 'employees', 'reports'],
                'presets' => [
                    'require_patient' => true,
                    'enable_appointments' => true,
                    'privacy_mode' => true
                ]
            ],
            'wholesale' => [
                'name' => 'Mayorista / Distribución',
                'icon' => 'fa-truck-loading',
                'description' => 'Distribuidores, mayoristas, venta a negocios',
                'features' => ['credit_lines', 'bulk_pricing', 'delivery_scheduling', 'invoices'],
                'modules' => ['sales', 'inventory', 'customers', 'invoices', 'deliveries', 'reports'],
                'presets' => [
                    'require_customer' => true,
                    'enable_credit' => true,
                    'default_payment' => 'account',
                    'enable_invoices' => true
                ]
            ],
            'beauty' => [
                'name' => 'Belleza / Salón',
                'icon' => 'fa-spa',
                'description' => 'Salones de belleza, barberías, spas',
                'features' => ['appointments', 'employee_commission', 'service_packages'],
                'modules' => ['sales', 'customers', 'employees', 'reports'],
                'presets' => [
                    'require_appointment' => true,
                    'enable_commissions' => true,
                    'track_employee_performance' => true
                ]
            ]
        ];
    }

    /**
     * Aplicar configuración general del sistema
     */
    public function apply_configuration($data)
    {
        $this->load->helper('appconfig');
        
        try {
            // Configuración básica
            appconfig_set('business_name', $data['business_name']);
            appconfig_set('industry_type', $data['industry_type']);
            appconfig_set('inventory_mode', $data['inventory_mode']);
            appconfig_set('sales_mode', $data['sales_mode']);
            appconfig_set('billing_mode', $data['billing_mode']);
            
            // Configuración específica
            if (isset($data['tax_id'])) {
                appconfig_set('tax_id', $data['tax_id']);
            }
            
            if (isset($data['currency_symbol'])) {
                appconfig_set('currency_symbol', $data['currency_symbol']);
            }
            
            if (isset($data['number_of_users'])) {
                appconfig_set('max_users', $data['number_of_users']);
            }
            
            if (isset($data['store_count'])) {
                appconfig_set('planned_stores', $data['store_count']);
            }
            
            return true;
        } catch (Exception $e) {
            log_message('error', 'Error applying onboarding configuration: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aplicar presets específicos de la industria seleccionada
     */
    public function apply_industry_presets($industry_type)
    {
        $industries = $this->get_industries();
        
        if (!isset($industries[$industry_type])) {
            return false;
        }
        
        $industry = $industries[$industry_type];
        $presets = $industry['presets'];
        
        $this->load->helper('appconfig');
        
        // Aplicar cada preset
        foreach ($presets as $key => $value) {
            appconfig_set($key, $value);
        }
        
        // Configurar módulos activos
        appconfig_set('active_modules', implode(',', $industry['modules']));
        
        // Configurar características habilitadas
        appconfig_set('enabled_features', implode(',', $industry['features']));
        
        log_message('info', "Industry presets applied: {$industry_type}");
        
        return true;
    }

    /**
     * Obtener configuración guardada en sesión
     */
    public function get_saved_config()
    {
        $CI =& get_instance();
        return $CI->session->userdata('onboarding_data');
    }

    /**
     * Validar datos del onboarding
     */
    public function validate_step($step, $data)
    {
        switch ($step) {
            case 1:
                return !empty($data['business_name']) && !empty($data['contact_email']);
            case 2:
                return !empty($data['industry_type']) && !empty($data['business_size']);
            case 3:
                return !empty($data['inventory_mode']);
            case 4:
                return !empty($data['sales_mode']);
            case 5:
                return !empty($data['billing_mode']);
            default:
                return true;
        }
    }
}
