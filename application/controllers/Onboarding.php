<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controlador de Onboarding Inteligente
 * Guía al administrador en la configuración inicial del sistema
 * según el tipo de negocio, tamaño y necesidades operativas.
 */
class Onboarding extends MY_Controller 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Onboarding_model');
        $this->load->library('session');
        $this->load->helper('form');
        
        // Solo accesible para Super Admin o Admin sin configuración
        if (!$this->Employee->has_module_grant('config', $this->Employee->get_logged_in_employee_info()->person_id) 
            && !$this->Employee->is_super_admin($this->Employee->get_logged_in_employee_info()->person_id)) {
            redirect('no_access/onboarding');
        }
    }

    /**
     * Paso 1: Información básica del negocio
     */
    public function index()
    {
        $data['step'] = 1;
        $data['page_title'] = 'Configuración Inicial - OneBox';
        
        // Verificar si ya está configurado
        if ($this->Onboarding_model->is_configured()) {
            redirect('home');
        }
        
        $this->load->view('onboarding/wizard_step1', $data);
    }

    /**
     * Paso 2: Tipo de industria y modelo operativo
     */
    public function industry()
    {
        $data['step'] = 2;
        $data['page_title'] = 'Industria y Modelo Operativo';
        $data['industries'] = $this->Onboarding_model->get_industries();
        
        $this->load->view('onboarding/wizard_step2', $data);
    }

    /**
     * Paso 3: Configuración de inventario y productos
     */
    public function inventory()
    {
        $data['step'] = 3;
        $data['page_title'] = 'Configuración de Inventario';
        
        $this->load->view('onboarding/wizard_step3', $data);
    }

    /**
     * Paso 4: Configuración de ventas y caja
     */
    public function sales()
    {
        $data['step'] = 4;
        $data['page_title'] = 'Configuración de Ventas y Caja';
        
        $this->load->view('onboarding/wizard_step4', $data);
    }

    /**
     * Paso 5: Facturación y configuración fiscal
     */
    public function billing()
    {
        $data['step'] = 5;
        $data['page_title'] = 'Configuración Fiscal y Facturación';
        $data['billing_modes'] = ['fiscal' => 'Con Facturación CFDI', 'non_fiscal' => 'Sin Facturación', 'both' => 'Ambos'];
        
        $this->load->view('onboarding/wizard_step5', $data);
    }

    /**
     * Paso 6: Usuarios y permisos
     */
    public function users()
    {
        $data['step'] = 6;
        $data['page_title'] = 'Configuración de Usuarios y Roles';
        
        $this->load->view('onboarding/wizard_step6', $data);
    }

    /**
     * Paso 7: Resumen y activación
     */
    public function summary()
    {
        $data['step'] = 7;
        $data['page_title'] = 'Resumen y Activación';
        $data['config_summary'] = $this->session->userdata('onboarding_data');
        
        $this->load->view('onboarding/wizard_step7', $data);
    }

    /**
     * Guardar configuración del wizard
     */
    public function save_configuration()
    {
        $post_data = $this->input->post();
        
        // Validar datos
        $validation_rules = [
            'business_name' => 'required',
            'industry_type' => 'required',
            'inventory_mode' => 'required',
            'sales_mode' => 'required',
            'billing_mode' => 'required'
        ];
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules($validation_rules);
        
        if ($this->form_validation->run() === FALSE) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        // Guardar en sesión temporal
        $this->session->set_userdata('onboarding_data', $post_data);
        
        // Aplicar configuración al sistema
        $result = $this->Onboarding_model->apply_configuration($post_data);
        
        if ($result) {
            // Marcar como configurado
            $this->Appconfig->save('onboarding_completed', '1');
            $this->Appconfig->save('onboarding_date', date('Y-m-d H:i:s'));
            
            // Aplicar presets de industria
            $this->Onboarding_model->apply_industry_presets($post_data['industry_type']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Configuración guardada exitosamente',
                'redirect' => site_url('home')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar configuración']);
        }
    }

    /**
     * Saltar onboarding (solo para pruebas)
     */
    public function skip()
    {
        if ($this->Employee->is_super_admin($this->Employee->get_logged_in_employee_info()->person_id)) {
            $this->Appconfig->save('onboarding_completed', '1');
            redirect('home');
        }
        redirect('no_access');
    }

    /**
     * Reiniciar onboarding (solo Super Admin)
     */
    public function reset()
    {
        if ($this->Employee->is_super_admin($this->Employee->get_logged_in_employee_info()->person_id)) {
            $this->Appconfig->save('onboarding_completed', '0');
            $this->session->unset_userdata('onboarding_data');
            redirect('onboarding');
        }
        redirect('no_access');
    }
}
