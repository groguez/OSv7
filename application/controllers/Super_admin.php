<?php
class Super_admin extends MY_Controller 
{
    function __construct()
    {
        parent::__construct();
        
        // Verificar que sea super admin
        if (!$this->Employee->is_logged_in()) {
            redirect('login');
        }
        
        $this->load->helper('authorization');
        
        if (!is_super_admin()) {
            show_error('No tienes permisos para acceder a esta sección', 403);
        }
        
        $this->lang->load('super_admin');
    }
    
    // Dashboard del Super Admin
    function index()
    {
        $data = array();
        
        // Estadísticas generales
        $data['total_stores'] = $this->Store->get_all_stores_count();
        $data['total_employees'] = $this->Employee->get_total_employees_count();
        $data['active_subscriptions'] = $this->Store->get_active_subscriptions_count();
        $data['trial_stores'] = $this->Store->get_trial_stores_count();
        $data['expired_subscriptions'] = $this->Store->get_expired_subscriptions_count();
        
        // Lista de tiendas con suscripción próxima a vencer
        $data['expiring_soon'] = $this->Store->get_expiring_subscriptions(7);
        
        // Logs recientes de Ghost Mode
        $data['recent_ghost_logs'] = $this->Ghost_log->get_recent_logs(10);
        
        $this->load->view('super_admin/dashboard', $data);
    }
    
    // Gestión de Usuarios Globales
    function users()
    {
        $data = array();
        
        $config['base_url'] = site_url('super_admin/users');
        $config['total_rows'] = $this->Employee->get_total_employees_count();
        $config['per_page'] = 20;
        $this->pagination->initialize($config);
        
        $data['employees'] = $this->Employee->get_all_employees($config['per_page'], $this->uri->segment(3));
        $data['page_links'] = $this->pagination->create_links();
        
        $this->load->view('super_admin/users', $data);
    }
    
    // Crear/Editar Usuario
    function user_form($employee_id = -1)
    {
        $data = array();
        
        if ($employee_id == -1) {
            $data['employee_info'] = $this->Employee->get_empty_object();
            $data['person_info'] = $this->Person->get_empty_object();
        } else {
            $data['employee_info'] = $this->Employee->get_info($employee_id);
            $person_info = $this->Person->get_info($employee_id);
            $data['person_info'] = $person_info;
        }
        
        $this->load->view('super_admin/user_form', $data);
    }
    
    // Guardar Usuario
    function save_user($employee_id = -1)
    {
        $person_data = array();
        $employee_data = array();
        
        // Procesar datos del formulario
        $person_data['first_name'] = $this->input->post('first_name');
        $person_data['last_name'] = $this->input->post('last_name');
        $person_data['email'] = $this->input->post('email');
        $person_data['phone_number'] = $this->input->post('phone_number');
        
        $employee_data['username'] = $this->input->post('username');
        $employee_data['password'] = $this->input->post('password');
        $employee_data['role'] = $this->input->post('role');
        
        if ($this->Employee->save_employee($person_data, $employee_data, $employee_id == -1 ? NULL : $employee_id)) {
            echo json_encode(array('success' => TRUE, 'message' => 'Usuario guardado exitosamente'));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => 'Error al guardar usuario'));
        }
    }
    
    // Gestión de Tiendas
    function stores()
    {
        $data = array();
        
        $config['base_url'] = site_url('super_admin/stores');
        $config['total_rows'] = $this->Store->get_all_stores_count();
        $config['per_page'] = 20;
        $this->pagination->initialize($config);
        
        $data['stores'] = $this->Store->get_all_stores($config['per_page'], $this->uri->segment(3));
        $data['page_links'] = $this->pagination->create_links();
        
        $this->load->view('super_admin/stores', $data);
    }
    
    // Editar Suscripción de Tienda
    function store_subscription_form($store_id)
    {
        $data = array();
        $data['store_info'] = $this->Store->get_info($store_id);
        $data['subscription_info'] = get_store_subscription_info($store_id);
        
        $this->load->view('super_admin/store_subscription_form', $data);
    }
    
    // Guardar Suscripción
    function save_subscription($store_id)
    {
        $subscription_data = array(
            'subscription_status' => $this->input->post('subscription_status'),
            'subscription_plan' => $this->input->post('subscription_plan'),
            'subscription_start_date' => $this->input->post('subscription_start_date'),
            'subscription_end_date' => $this->input->post('subscription_end_date'),
            'trial_end_date' => $this->input->post('trial_end_date'),
            'subscription_amount' => $this->input->post('subscription_amount'),
            'billing_cycle' => $this->input->post('billing_cycle')
        );
        
        if ($this->Store->save_subscription($store_id, $subscription_data)) {
            echo json_encode(array('success' => TRUE, 'message' => 'Suscripción actualizada'));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => 'Error al actualizar'));
        }
    }
    
    // Modo Ghost - Activar
    function activate_ghost_mode($target_person_id)
    {
        if (!is_super_admin()) {
            echo json_encode(array('success' => FALSE, 'message' => 'No autorizado'));
            return;
        }
        
        if (enter_ghost_mode($target_person_id)) {
            echo json_encode(array('success' => TRUE, 'message' => 'Modo Ghost activado'));
        } else {
            echo json_encode(array('success' => FALSE, 'message' => 'Error al activar modo Ghost'));
        }
    }
    
    // Modo Ghost - Salir
    function exit_ghost_mode()
    {
        exit_ghost_mode();
        redirect('home');
    }
    
    // Configuración Global
    function global_config()
    {
        $data = array();
        $data['config_values'] = $this->Appconfig->get_all_configs();
        
        $this->load->view('super_admin/global_config', $data);
    }
    
    // Guardar Configuración Global
    function save_global_config()
    {
        $config_items = $this->input->post('config');
        
        foreach ($config_items as $key => $value) {
            $this->Appconfig->save($key, $value);
        }
        
        echo json_encode(array('success' => TRUE, 'message' => 'Configuración guardada'));
    }
    
    // Logs de Auditoría
    function audit_logs()
    {
        $data = array();
        
        $config['base_url'] = site_url('super_admin/audit_logs');
        $config['total_rows'] = $this->db->count_all('audit_logs');
        $config['per_page'] = 50;
        $this->pagination->initialize($config);
        
        $data['logs'] = $this->db->get('audit_logs', $config['per_page'], $this->uri->segment(3))->result();
        $data['page_links'] = $this->pagination->create_links();
        
        $this->load->view('super_admin/audit_logs', $data);
    }
}
