<?php
/**
 * Gestión de Tokens "Recordarme"
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthRememberToken {
    protected $CI;
    const TOKEN_DURATION = 2592000; // 30 días
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    public function createToken($employeeId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_DURATION);
        
        $data = [
            'employee_id' => $employeeId,
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->CI->db->insert('remember_tokens', $data);
        
        return $token;
    }
    
    public function validateToken($token) {
        $hashedToken = hash('sha256', $token);
        
        $this->CI->db->where('token', $hashedToken);
        $this->CI->db->where('expires_at >=', date('Y-m-d H:i:s'));
        $result = $this->CI->db->get('remember_tokens')->row();
        
        if (!$result) {
            return false;
        }
        
        return $result->employee_id;
    }
    
    public function refreshToken($employeeId, $oldToken) {
        // Revocar token anterior
        $this->revokeToken($employeeId);
        
        // Crear nuevo token
        return $this->createToken($employeeId);
    }
    
    public function revokeToken($employeeId) {
        $this->CI->db->where('employee_id', $employeeId);
        $this->CI->db->delete('remember_tokens');
    }
}
