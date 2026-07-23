<?php
/**
 * Sales Context Engine - Motor de Contexto para Ventas Adaptables
 * 
 * Detecta el tipo de negocio y configura dinámicamente:
 * - Elementos visibles/ocultos en el POS
 * - Etiquetas personalizadas (Producto vs Platillo vs Servicio)
 * - Flujos de trabajo obligatorios/opcionales
 * - Validaciones específicas por industria
 * 
 * @package OneBox\Core
 * @version 2.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Sales_Context_Engine {
    
    protected $CI;
    protected $industry_config;
    protected $store_id;
    protected $active_features;
    
    // Mapeo de industrias a configuraciones
    private $industry_profiles = [
        'retail' => [
            'label_product' => 'Producto',
            'label_customer' => 'Cliente',
            'label_transaction' => 'Venta',
            'priority_features' => ['barcode_scan', 'quick_sale', 'gift_cards', 'suspended_sales'],
            'optional_features' => ['appointments', 'table_management', 'signatures'],
            'required_fields' => ['item_id', 'quantity'],
            'ui_mode' => 'speed',
            'show_inventory_levels' => true,
            'show_expiration_dates' => false,
            'enable_table_assignment' => false,
            'enable_appointment_linking' => false,
            'enable_signature_capture' => false,
            'default_payment_flow' => 'immediate'
        ],
        'restaurant' => [
            'label_product' => 'Platillo',
            'label_customer' => 'Comensal',
            'label_transaction' => 'Orden',
            'priority_features' => ['table_management', 'kitchen_display', 'modifiers', 'split_checks'],
            'optional_features' => ['delivery_scheduling', 'reservations', 'tips'],
            'required_fields' => ['item_id', 'table_id'],
            'ui_mode' => 'visual',
            'show_inventory_levels' => false,
            'show_expiration_dates' => true,
            'enable_table_assignment' => true,
            'enable_appointment_linking' => false,
            'enable_signature_capture' => false,
            'default_payment_flow' => 'deferred'
        ],
        'workshop' => [
            'label_product' => 'Servicio/Refacción',
            'label_customer' => 'Cliente',
            'label_transaction' => 'Orden de Servicio',
            'priority_features' => ['work_orders', 'labor_tracking', 'estimates', 'signatures'],
            'optional_features' => ['appointment_scheduling', 'vehicle_info', 'photo_evidence'],
            'required_fields' => ['work_order_id', 'customer_id'],
            'ui_mode' => 'detail',
            'show_inventory_levels' => true,
            'show_expiration_dates' => false,
            'enable_table_assignment' => false,
            'enable_appointment_linking' => true,
            'enable_signature_capture' => true,
            'default_payment_flow' => 'partial'
        ],
        'healthcare' => [
            'label_product' => 'Medicamento/Servicio',
            'label_customer' => 'Paciente',
            'label_transaction' => 'Receta/Consulta',
            'priority_features' => ['patient_records', 'prescription_tracking', 'insurance_billing'],
            'optional_features' => ['appointment_scheduling', 'medical_history', 'allergy_alerts'],
            'required_fields' => ['patient_id', 'prescription_id'],
            'ui_mode' => 'detail',
            'show_inventory_levels' => true,
            'show_expiration_dates' => true,
            'enable_table_assignment' => false,
            'enable_appointment_linking' => true,
            'enable_signature_capture' => true,
            'default_payment_flow' => 'partial'
        ],
        'laundry' => [
            'label_product' => 'Servicio de Lavado',
            'label_customer' => 'Cliente',
            'label_transaction' => 'Orden de Lavandería',
            'priority_features' => ['garment_tracking', 'pickup_delivery', 'status_workflow'],
            'optional_features' => ['subscription_plans', 'route_optimization'],
            'required_fields' => ['garment_items', 'customer_id'],
            'ui_mode' => 'detail',
            'show_inventory_levels' => false,
            'show_expiration_dates' => false,
            'enable_table_assignment' => false,
            'enable_appointment_linking' => true,
            'enable_signature_capture' => true,
            'default_payment_flow' => 'partial'
        ]
    ];
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Store_model');
        $this->CI->load->model('Employee_model');
        
        $this->store_id = $this->CI->config->item('location_id') ?: 1;
        $this->load_industry_config();
        $this->active_features = $this->get_active_features();
    }
    
    /**
     * Carga el contexto específico para un empleado/tienda
     * Método llamado desde Sales.php para inicialización dinámica
     */
    public function load_context($employee_id = null) {
        // Recargar configuración si hay overrides por empleado
        if ($employee_id) {
            $employee_info = $this->CI->Employee_model->get_info($employee_id);
            if ($employee_info && $employee_info->business_type_override) {
                $this->industry_config = isset($this->industry_profiles[$employee_info->business_type_override]) 
                    ? $this->industry_profiles[$employee_info->business_type_override]
                    : $this->industry_config;
            }
        }
        $this->active_features = $this->get_active_features();
        return $this;
    }
    
    /**
     * Obtiene la configuración activa para ser usada en el controlador
     * Retorna array compatible con las vistas y lógica de negocio
     */
    public function get_active_config() {
        return [
            'industry' => array_search($this->industry_config, $this->industry_profiles) ?: 'retail',
            'item_label' => $this->industry_config['label_product'],
            'customer_label' => $this->industry_config['label_customer'],
            'transaction_label' => $this->industry_config['label_transaction'],
            'ui_mode' => $this->industry_config['ui_mode'],
            'features' => $this->active_features,
            'required_fields' => $this->get_required_fields(),
            'show_inventory' => $this->industry_config['show_inventory_levels'],
            'show_expiration' => $this->industry_config['show_expiration_dates'],
            'enable_tables' => $this->industry_config['enable_table_assignment'],
            'enable_appointments' => $this->industry_config['enable_appointment_linking'],
            'enable_signatures' => $this->industry_config['enable_signature_capture'],
            'payment_flow' => $this->industry_config['default_payment_flow']
        ];
    }
    
    /**
     * Carga la configuración de industria desde la BD o usa defaults
     */
    private function load_industry_config() {
        // Intentar obtener configuración específica de la tienda
        $store_info = $this->CI->Store_model->get_info($this->store_id);
        
        if ($store_info && $store_info->business_type) {
            $business_type = $store_info->business_type;
            $this->industry_config = isset($this->industry_profiles[$business_type]) 
                ? $this->industry_profiles[$business_type]
                : $this->industry_profiles['retail'];
        } else {
            // Default a retail si no está configurado
            $this->industry_config = $this->industry_profiles['retail'];
        }
        
        // Permitir sobrescritura por configuración manual
        $manual_overrides = $this->CI->config->item('sales_context_overrides');
        if ($manual_overrides) {
            $this->industry_config = array_merge($this->industry_config, $manual_overrides);
        }
    }
    
    /**
     * Obtiene la etiqueta apropiada para un elemento según la industria
     */
    public function get_label($element) {
        $mapping = [
            'product' => 'label_product',
            'customer' => 'label_customer',
            'transaction' => 'label_transaction',
            'sale' => 'label_transaction'
        ];
        
        $config_key = isset($mapping[$element]) ? $mapping[$element] : null;
        return $config_key ? $this->industry_config[$config_key] : ucfirst($element);
    }
    
    /**
     * Verifica si una característica está habilitada para esta industria
     */
    public function is_feature_enabled($feature_name) {
        return in_array($feature_name, $this->industry_config['priority_features']) ||
               in_array($feature_name, $this->industry_config['optional_features']);
    }
    
    /**
     * Obtiene todas las características activas
     */
    public function get_active_features() {
        return array_merge(
            $this->industry_config['priority_features'],
            $this->industry_config['optional_features']
        );
    }
    
    /**
     * Determina el modo de UI recomendado
     */
    public function get_ui_mode() {
        return $this->industry_config['ui_mode'];
    }
    
    /**
     * Obtiene campos requeridos para la transacción
     */
    public function get_required_fields() {
        return $this->industry_config['required_fields'];
    }
    
    /**
     * Verifica si se debe mostrar información específica
     */
    public function should_show($element) {
        $show_map = [
            'inventory_levels' => 'show_inventory_levels',
            'expiration_dates' => 'show_expiration_dates',
            'table_assignment' => 'enable_table_assignment',
            'appointment_linking' => 'enable_appointment_linking',
            'signature_capture' => 'enable_signature_capture'
        ];
        
        $config_key = isset($show_map[$element]) ? $show_map[$element] : null;
        return $config_key ? $this->industry_config[$config_key] : false;
    }
    
    /**
     * Obtiene el flujo de pago por defecto
     */
    public function get_default_payment_flow() {
        return $this->industry_config['default_payment_flow'];
    }
    
    /**
     * Genera configuración JSON para el frontend
     */
    public function get_frontend_config() {
        return [
            'industry' => array_search($this->industry_config, $this->industry_profiles) ?: 'retail',
            'labels' => [
                'product' => $this->industry_config['label_product'],
                'customer' => $this->industry_config['label_customer'],
                'transaction' => $this->industry_config['label_transaction']
            ],
            'features' => $this->get_active_features(),
            'ui_mode' => $this->get_ui_mode(),
            'required_fields' => $this->get_required_fields(),
            'visibility' => [
                'inventory_levels' => $this->should_show('inventory_levels'),
                'expiration_dates' => $this->should_show('expiration_dates'),
                'table_assignment' => $this->should_show('table_assignment'),
                'appointment_linking' => $this->should_show('appointment_linking'),
                'signature_capture' => $this->should_show('signature_capture')
            ],
            'payment_flow' => $this->get_default_payment_flow()
        ];
    }
    
    /**
     * Valida que la transacción cumpla con los requisitos de la industria
     */
    public function validate_transaction($cart_data) {
        $required_fields = $this->get_required_fields();
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($cart_data[$field])) {
                $errors[] = "El campo '{$field}' es requerido para este tipo de negocio.";
            }
        }
        
        // Validaciones específicas por industria
        if ($this->industry_config['enable_table_assignment'] && empty($cart_data['table_id'])) {
            $errors[] = "Debe asignar una mesa para continuar.";
        }
        
        if ($this->industry_config['enable_appointment_linking'] && empty($cart_data['appointment_id'])) {
            $errors[] = "Debe vincular una cita/agenda para continuar.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
