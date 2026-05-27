<?php
/**
 * Helper de Autorización para OneBox
 * Maneja roles, permisos y validación de suscripciones
 */

// Verifica si el usuario es Super Administrador del sistema
function is_super_admin()
{
    $CI =& get_instance();
    $person_id = $CI->session->userdata('person_id');
    
    if (!$person_id) {
        return FALSE;
    }
    
    // Verificar si el empleado tiene rol de super admin
    $employee_info = $CI->Employee->get_info($person_id);
    
    // El super admin se identifica por tener el rol 'super_admin' o ser el empleado ID 1 (por defecto)
    return isset($employee_info->role) && $employee_info->role === 'super_admin';
}

// Verifica si el usuario es Administrador de Tienda
function is_store_admin()
{
    $CI =& get_instance();
    $person_id = $CI->session->userdata('person_id');
    
    if (!$person_id) {
        return FALSE;
    }
    
    $employee_info = $CI->Employee->get_info($person_id);
    return isset($employee_info->role) && $employee_info->role === 'admin';
}

// Verifica si el usuario está en modo Ghost (suplantando otro usuario)
function is_ghost_mode()
{
    $CI =& get_instance();
    return $CI->session->userdata('ghost_mode') === TRUE;
}

// Obtiene el usuario original en modo Ghost
function get_original_user_info()
{
    $CI =& get_instance();
    
    if (!is_ghost_mode()) {
        return NULL;
    }
    
    $original_person_id = $CI->session->userdata('original_person_id');
    
    if ($original_person_id) {
        return $CI->Employee->get_info($original_person_id);
    }
    
    return NULL;
}

// Sale del modo Ghost
function exit_ghost_mode()
{
    $CI =& get_instance();
    $CI->session->unset_userdata('ghost_mode');
    $CI->session->unset_userdata('ghost_target_person_id');
    $CI->session->unset_userdata('original_person_id');
    $CI->session->unset_userdata('ghost_start_time');
}

// Activa el modo Ghost para un usuario específico
function enter_ghost_mode($target_person_id)
{
    $CI =& get_instance();
    
    // Solo super admin puede usar modo ghost
    if (!is_super_admin()) {
        return FALSE;
    }
    
    $original_person_id = $CI->session->userdata('person_id');
    
    $CI->session->set_userdata('ghost_mode', TRUE);
    $CI->session->set_userdata('ghost_target_person_id', $target_person_id);
    $CI->session->set_userdata('original_person_id', $original_person_id);
    $CI->session->set_userdata('ghost_start_time', time());
    
    // Registrar la acción en logs
    log_ghost_action($original_person_id, $target_person_id, 'start_ghost_session');
    
    return TRUE;
}

// Registra acciones en modo Ghost
function log_ghost_action($admin_person_id, $target_person_id, $action)
{
    $CI =& get_instance();
    
    $log_data = array(
        'admin_person_id' => $admin_person_id,
        'target_person_id' => $target_person_id,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $CI->input->ip_address(),
        'user_agent' => $CI->input->user_agent()
    );
    
    $CI->db->insert('ghost_mode_logs', $log_data);
}

// Verifica si la suscripción de la tienda está activa
function is_store_subscription_active($store_id = NULL)
{
    $CI =& get_instance();
    
    if (!$store_id) {
        $store_id = $CI->config->item('default_location_id');
    }
    
    if (!$store_id) {
        return TRUE; // Si no hay tienda configurada, permitir acceso
    }
    
    $store_info = $CI->Store->get_info($store_id);
    
    if (!$store_info) {
        return TRUE;
    }
    
    // Verificar si está en modo hosting OneBox
    if (!is_on_phppos_host()) {
        return TRUE; // En instalaciones locales, no validar suscripción
    }
    
    // Verificar campos de suscripción
    if (isset($store_info->subscription_status)) {
        if ($store_info->subscription_status === 'cancelled' || $store_info->subscription_status === 'expired') {
            // Verificar período de gracia
            if (isset($store_info->subscription_end_date)) {
                $end_date = strtotime($store_info->subscription_end_date);
                $grace_period = $end_date + (7 * 24 * 60 * 60); // 7 días de gracia
                
                if (time() > $grace_period) {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        }
        
        // Verificar trial
        if ($store_info->subscription_status === 'trial') {
            if (isset($store_info->trial_end_date)) {
                $trial_end = strtotime($store_info->trial_end_date);
                if (time() > $trial_end) {
                    return FALSE;
                }
            }
        }
    }
    
    return TRUE;
}

// Obtiene los detalles de la suscripción de la tienda
function get_store_subscription_info($store_id = NULL)
{
    $CI =& get_instance();
    
    if (!$store_id) {
        $store_id = $CI->config->item('default_location_id');
    }
    
    $store_info = $CI->Store->get_info($store_id);
    
    if (!$store_info) {
        return NULL;
    }
    
    return array(
        'status' => isset($store_info->subscription_status) ? $store_info->subscription_status : 'active',
        'plan' => isset($store_info->subscription_plan) ? $store_info->subscription_plan : 'basic',
        'start_date' => isset($store_info->subscription_start_date) ? $store_info->subscription_start_date : NULL,
        'end_date' => isset($store_info->subscription_end_date) ? $store_info->subscription_end_date : NULL,
        'trial_end_date' => isset($store_info->trial_end_date) ? $store_info->trial_end_date : NULL,
        'amount' => isset($store_info->subscription_amount) ? $store_info->subscription_amount : 0,
        'billing_cycle' => isset($store_info->billing_cycle) ? $store_info->billing_cycle : 'monthly'
    );
}

// Verifica permisos para acceder a configuración global
function can_access_global_config()
{
    return is_super_admin();
}

// Verifica si puede gestionar tiendas
function can_manage_stores()
{
    return is_super_admin();
}

// Verifica si puede gestionar usuarios globales
function can_manage_users()
{
    return is_super_admin();
}

// Redirige según el estado de la suscripción
function check_subscription_and_redirect()
{
    $CI =& get_instance();
    
    // Super admin siempre tiene acceso
    if (is_super_admin()) {
        return TRUE;
    }
    
    // Verificar suscripción de la tienda actual
    if (!is_store_subscription_active()) {
        redirect('subscription/expired');
        return FALSE;
    }
    
    return TRUE;
}
