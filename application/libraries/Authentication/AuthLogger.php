<?php
/**
 * Logger de Auditoría de Autenticación
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthLogger {
    protected $CI;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    public function logAttempt($username, $action, $details = '') {
        $data = [
            'employee_id' => $this->CI->session->userdata('employee_id') ?? null,
            'username' => $username,
            'action' => $action,
            'details' => $details,
            'ip_address' => $this->CI->input->ip_address(),
            'user_agent' => $this->CI->input->user_agent(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->CI->db->insert('auth_logs', $data);
    }
    
    public function logLogout($employeeId, $username) {
        $this->logAttempt($username, 'LOGOUT', 'Employee ID: ' . $employeeId);
    }
}
