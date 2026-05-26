<?php
/**
 * Control de Rate Limiting para intentos de login
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class AuthRateLimiter {
    protected $CI;
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minutos
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    public function isLocked($username) {
        $this->CI->db->where('username', $username);
        $this->CI->db->where('attempt_time >=', date('Y-m-d H:i:s', time() - self::LOCKOUT_TIME));
        $this->CI->db->where('success', 0);
        $attempts = $this->CI->db->count_all_results('login_attempts');
        
        return $attempts >= self::MAX_ATTEMPTS;
    }
    
    public function recordAttempt($username, $success) {
        $data = [
            'username' => $username,
            'ip_address' => $this->CI->input->ip_address(),
            'attempt_time' => date('Y-m-d H:i:s'),
            'success' => $success ? 1 : 0
        ];
        $this->CI->db->insert('login_attempts', $data);
    }
    
    public function resetAttempts($username) {
        $this->CI->db->where('username', $username);
        $this->CI->db->delete('login_attempts');
    }
}
