<?php
/**
 * Configuración de Flujos de Ventas por Industria
 * 
 * Define cómo se comportan los módulos interconectados
 * según el tipo de negocio seleccionado.
 * 
 * @package OneBox\Config
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$config['sales_workflows'] = [
    // ==========================================
    // RETAIL (Tiendas de autoservicio, boutiques, ferreterías)
    // ==========================================
    'retail' => [
        'modules_enabled' => [
            'pos' => true,
            'gift_cards' => true,
            'suspended_sales' => true,
            'layaways' => true,
            'customer_credit' => true,
            'work_orders' => false,
            'table_management' => false,
            'appointments' => false,
            'delivery_scheduling' => true,
            'expenses_quick_add' => false
        ],
        'cash_register' => [
            'require_opening_balance' => true,
            'allow_cash_removal' => true,
            'allow_cash_addition' => true,
            'track_expenses_in_register' => false,
            'blind_close' => false
        ],
        'transaction_flow' => [
            'default_type' => 'sale',
            'allow_partial_payment' => true,
            'require_customer' => false,
            'auto_apply_gift_card' => true,
            'show_suspended_count' => true
        ],
        'ui_preferences' => [
            'layout' => 'grid_large',
            'show_product_image' => true,
            'show_inventory_bar' => true,
            'quick_keys_enabled' => true,
            'barcode_focus_default' => true
        ]
    ],
    
    // ==========================================
    // RESTAURANTE (Restaurantes, cafeterías, bares)
    // ==========================================
    'restaurant' => [
        'modules_enabled' => [
            'pos' => true,
            'gift_cards' => true,
            'suspended_sales' => false,
            'layaways' => false,
            'customer_credit' => false,
            'work_orders' => false,
            'table_management' => true,
            'appointments' => true, // Reservas
            'delivery_scheduling' => true,
            'expenses_quick_add' => false,
            'kitchen_display' => true,
            'modifiers' => true,
            'split_checks' => true,
            'tips' => true
        ],
        'cash_register' => [
            'require_opening_balance' => true,
            'allow_cash_removal' => true,
            'allow_cash_addition' => true,
            'track_expenses_in_register' => false,
            'blind_close' => true,
            'tip_pooling' => true
        ],
        'transaction_flow' => [
            'default_type' => 'order',
            'allow_partial_payment' => true,
            'require_customer' => false,
            'auto_apply_gift_card' => false,
            'show_suspended_count' => false,
            'require_table_selection' => true,
            'send_to_kitchen_auto' => true
        ],
        'ui_preferences' => [
            'layout' => 'table_grid',
            'show_product_image' => true,
            'show_inventory_bar' => false,
            'quick_keys_enabled' => true,
            'barcode_focus_default' => false,
            'show_modifiers_panel' => true
        ]
    ],
    
    // ==========================================
    // TALLER (Servicios técnicos, reparaciones, automotriz)
    // ==========================================
    'workshop' => [
        'modules_enabled' => [
            'pos' => true,
            'gift_cards' => false,
            'suspended_sales' => true,
            'layaways' => false,
            'customer_credit' => true,
            'work_orders' => true,
            'table_management' => false,
            'appointments' => true,
            'delivery_scheduling' => true,
            'expenses_quick_add' => true,
            'labor_tracking' => true,
            'estimates' => true,
            'signature_capture' => true
        ],
        'cash_register' => [
            'require_opening_balance' => true,
            'allow_cash_removal' => true,
            'allow_cash_addition' => true,
            'track_expenses_in_register' => true,
            'blind_close' => false,
            'link_expenses_to_wo' => true
        ],
        'transaction_flow' => [
            'default_type' => 'work_order_invoice',
            'allow_partial_payment' => true,
            'require_customer' => true,
            'auto_apply_gift_card' => false,
            'show_suspended_count' => true,
            'require_work_order_link' => true,
            'allow_estimates_conversion' => true
        ],
        'ui_preferences' => [
            'layout' => 'detail_form',
            'show_product_image' => false,
            'show_inventory_bar' => true,
            'quick_keys_enabled' => false,
            'barcode_focus_default' => false,
            'show_labor_hours' => true,
            'show_vehicle_info' => true
        ]
    ],
    
    // ==========================================
    // SALUD (Clínicas, farmacias, consultorios)
    // ==========================================
    'healthcare' => [
        'modules_enabled' => [
            'pos' => true,
            'gift_cards' => false,
            'suspended_sales' => true,
            'layaways' => false,
            'customer_credit' => true,
            'work_orders' => false,
            'table_management' => false,
            'appointments' => true,
            'delivery_scheduling' => false,
            'expenses_quick_add' => false,
            'patient_records' => true,
            'prescription_tracking' => true,
            'insurance_billing' => true,
            'signature_capture' => true
        ],
        'cash_register' => [
            'require_opening_balance' => true,
            'allow_cash_removal' => true,
            'allow_cash_addition' => true,
            'track_expenses_in_register' => false,
            'blind_close' => false
        ],
        'transaction_flow' => [
            'default_type' => 'prescription_sale',
            'allow_partial_payment' => true,
            'require_customer' => true, // Paciente
            'auto_apply_gift_card' => false,
            'show_suspended_count' => true,
            'require_appointment_link' => false,
            'show_patient_history' => true,
            'check_allergies' => true
        ],
        'ui_preferences' => [
            'layout' => 'detail_form',
            'show_product_image' => false,
            'show_inventory_bar' => true,
            'show_expiration_dates' => true,
            'quick_keys_enabled' => false,
            'barcode_focus_default' => true,
            'show_patient_panel' => true
        ]
    ],
    
    // ==========================================
    // LAVANDERÍA (Lavanderías, tintorerías)
    // ==========================================
    'laundry' => [
        'modules_enabled' => [
            'pos' => true,
            'gift_cards' => true,
            'suspended_sales' => false,
            'layaways' => false,
            'customer_credit' => true,
            'work_orders' => true, // Órdenes de lavado
            'table_management' => false,
            'appointments' => true, // Recolecciones
            'delivery_scheduling' => true,
            'expenses_quick_add' => false,
            'garment_tracking' => true,
            'status_workflow' => true,
            'subscription_plans' => true
        ],
        'cash_register' => [
            'require_opening_balance' => true,
            'allow_cash_removal' => true,
            'allow_cash_addition' => true,
            'track_expenses_in_register' => false,
            'blind_close' => false
        ],
        'transaction_flow' => [
            'default_type' => 'service_order',
            'allow_partial_payment' => true, // Anticipo
            'require_customer' => true,
            'auto_apply_gift_card' => true,
            'show_suspended_count' => false,
            'require_pickup_date' => true,
            'track_garment_items' => true,
            'print_claim_check' => true
        ],
        'ui_preferences' => [
            'layout' => 'detail_form',
            'show_product_image' => false,
            'show_inventory_bar' => false,
            'quick_keys_enabled' => true,
            'barcode_focus_default' => false,
            'show_garment_counter' => true,
            'show_status_timeline' => true
        ]
    ]
];

// Configuración global de gastos rápidos en POS
$config['quick_expense_categories'] = [
    'change_fund' => 'Fondo de cambio',
    'supplies' => 'Insumos de caja',
    'emergency' => 'Gasto de emergencia',
    'refund' => 'Devolución a cliente',
    'discount_override' => 'Descuento autorizado'
];

// Límites de gasto rápido sin autorización
$config['quick_expense_limits'] = [
    'cashier' => 50.00,
    'manager' => 200.00,
    'admin' => 1000.00
];
