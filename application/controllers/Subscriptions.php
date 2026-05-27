<?php
require_once(APPPATH."controllers/Security.php");

class Subscriptions extends Security
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Employee_subscription');
        $this->lang->load('subscriptions');
    }
    
    function index()
    {
        if (!$this->Employee->has_module_permission('subscriptions', $this->Employee->get_logged_in_employee_info()->person_id))
        {
            redirect('no_access/subscriptions');
        }
        
        $data['controller_name'] = strtolower(get_class());
        
        $config['base_url'] = site_url('subscriptions/index');
        $config['total_rows'] = $this->Employee->count_all();
        $config['per_page'] = 10;
        $config['uri_segment'] = 3;
        
        $this->pagination->initialize($config);
        
        $data['pagination'] = $this->pagination->create_links();
        $data['employees'] = $this->Employee->get_all(0, $config['per_page'], $this->uri->segment(3));
        
        $this->load->view('subscriptions/manage', $data);
    }
    
    function view($employee_id)
    {
        if (!$this->Employee->has_module_permission('subscriptions', $this->Employee->get_logged_in_employee_info()->person_id))
        {
            redirect('no_access/subscriptions');
        }
        
        $data['employee_info'] = $this->Employee->get_info($employee_id);
        $data['subscription_info'] = $this->Employee_subscription->get_subscription_info($employee_id);
        $data['subscription_types'] = $this->Employee_subscription->get_subscription_types();
        
        $this->load->view('subscriptions/form', $data);
    }
    
    function save($employee_id = FALSE)
    {
        $subscription_data = array(
            'subscription_type' => $this->input->post('subscription_type'),
            'status' => $this->input->post('status'),
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date') ? $this->input->post('end_date') : NULL,
            'trial_end_date' => $this->input->post('trial_end_date') ? $this->input->post('trial_end_date') : NULL,
            'amount' => $this->input->post('amount'),
            'currency' => $this->input->post('currency'),
            'auto_renew' => $this->input->post('auto_renew') ? 1 : 0,
            'notes' => $this->input->post('notes')
        );
        
        if ($this->Employee_subscription->save_subscription($employee_id, $subscription_data))
        {
            echo json_encode(array('success' => TRUE, 'message' => lang('subscriptions_saved_successfully')));
        }
        else
        {
            echo json_encode(array('success' => FALSE, 'message' => lang('subscriptions_save_failed')));
        }
    }
    
    function delete($employee_id)
    {
        if ($this->Employee_subscription->delete_subscription($employee_id))
        {
            echo json_encode(array('success' => TRUE, 'message' => lang('subscriptions_delete_successful')));
        }
        else
        {
            echo json_encode(array('success' => FALSE, 'message' => lang('subscriptions_delete_failed')));
        }
    }
}
