<?php
/**
 * AuthService - Servicio de Autenticación Centralizado
 * 
 * Maneja toda la lógica de autenticación, sesiones y seguridad del login
 */
class AuthService {
    
    protected $CI;
    
    // Configuración de seguridad
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME_MINUTES = 15;
    const REMEMBER_ME_DURATION_DAYS = 30;
    const RESET_TOKEN_EXPIRY_HOURS = 1;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('security');
    }
    
    /**
     * Intenta autenticar un usuario con credenciales
     * 
     * @param string $username Usuario o número de empleado
     * @param string $password Contraseña en texto plano
     * @return array ['success' => bool, 'message' => string, 'employee_id' => int|null]
     */
    public function authenticate($username, $password) {
        // Verificar rate limiting primero
        if ($this->isRateLimited($username)) {
            return [
                'success' => false,
                'message' => lang('login_too_many_failed_attempts'),
                'employee_id' => null
            ];
        }
        
        // Buscar empleado por username o employee_number
        $employee = $this->findEmployee($username);
        
        if (!$employee) {
            $this->recordFailedAttempt($username);
            return [
                'success' => false,
                'message' => lang('login_invalid_username_and_password'),
                'employee_id' => null
            ];
        }
        
        // Verificar contraseña (soporte dual: bcrypt + MD5 para migración)
        $passwordValid = $this->verifyPassword($password, $employee->password, $employee->person_id);
        
        if (!$passwordValid) {
            $this->recordFailedAttempt($username);
            return [
                'success' => false,
                'message' => lang('login_invalid_username_and_password'),
                'employee_id' => null
            ];
        }
        
        // Verificar si el empleado está activo y no eliminado
        if ($employee->deleted || $employee->inactive) {
            return [
                'success' => false,
                'message' => lang('login_account_disabled'),
                'employee_id' => null
            ];
        }
        
        // Verificar restricciones de horario
        if (!$this->checkLoginTimeRestrictions($employee)) {
            return [
                'success' => false,
                'message' => lang('login_outside_allowed_hours'),
                'employee_id' => null
            ];
        }
        
        // Login exitoso - limpiar intentos fallidos
        $this->clearFailedAttempts($username);
        
        // Migrar contraseña si estaba en MD5
        if ($this->needsPasswordMigration($employee->password)) {
            $this->migratePasswordToBcrypt($employee->person_id, $password);
        }
        
        return [
            'success' => true,
            'message' => lang('login_successful'),
            'employee_id' => $employee->person_id
        ];
    }
    
    /**
     * Verifica contraseña con soporte dual (bcrypt + MD5)
     */
    private function verifyPassword($password, $hash, $person_id) {
        // Primero intentar con bcrypt (nuevo estándar)
        if (password_verify($password, $hash)) {
            return true;
        }
        
        // Fallback a MD5 para migración (solo si el hash tiene longitud de MD5)
        if (strlen($hash) === 32 && md5($password) === $hash) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica si la contraseña necesita migración de MD5 a bcrypt
     */
    private function needsPasswordMigration($hash) {
        return strlen($hash) === 32; // Los hashes MD5 tienen 32 caracteres
    }
    
    /**
     * Migra contraseña de MD5 a bcrypt
     */
    private function migratePasswordToBcrypt($person_id, $password) {
        $newHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
        $this->CI->db->update('employees', ['password' => $newHash], ['person_id' => $person_id]);
    }
    
    /**
     * Busca un empleado por username o employee_number
     */
    private function findEmployee($username) {
        $this->CI->db->where('deleted', 0);
        $this->CI->db->where('inactive', 0);
        $this->CI->db->group_start();
        $this->CI->db->where('username', $username);
        $this->CI->db->or_where('employee_number', $username);
        $this->CI->db->group_end();
        
        $query = $this->CI->db->get('employees', 1);
        
        if ($query->num_rows() === 1) {
            return $query->row();
        }
        
        return null;
    }
    
    /**
     * Verifica restricciones de horario de login
     */
    private function checkLoginTimeRestrictions($employee) {
        if ($employee->login_start_time === NULL || $employee->login_end_time === NULL) {
            return true; // Sin restricciones
        }
        
        $timezone_orig = date_default_timezone_get();
        $timezone_config = $this->CI->Location->get_info_for_key('timezone', 1);
        
        if ($timezone_config) {
            date_default_timezone_set($timezone_config);
        }
        
        $now = time();
        $start_time = strtotime($employee->login_start_time);
        $end_time = strtotime($employee->login_end_time);
        
        date_default_timezone_set($timezone_orig);
        
        return ($now >= $start_time && $now <= $end_time);
    }
    
    /**
     * Registra intento fallido de login
     */
    private function recordFailedAttempt($username) {
        $ip_address = $this->CI->input->ip_address();
        $timestamp = date('Y-m-d H:i:s');
        
        // Crear tabla si no existe
        $this->ensureLoginAttemptsTableExists();
        
        $this->CI->db->insert('login_attempts', [
            'username' => $username,
            'ip_address' => $ip_address,
            'attempt_time' => $timestamp
        ]);
    }
    
    /**
     * Verifica si el usuario/IP está rate limited
     */
    private function isRateLimited($username) {
        $ip_address = $this->CI->input->ip_address();
        $time_threshold = date('Y-m-d H:i:s', strtotime('-' . self::LOCKOUT_TIME_MINUTES . ' minutes'));
        
        $this->CI->db->where('attempt_time >', $time_threshold);
        $this->CI->db->group_start();
        $this->CI->db->where('username', $username);
        $this->CI->db->or_where('ip_address', $ip_address);
        $this->CI->db->group_end();
        
        $query = $this->CI->db->get('login_attempts');
        
        return $query->num_rows() >= self::MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Limpia intentos fallidos después de login exitoso
     */
    private function clearFailedAttempts($username) {
        $ip_address = $this->CI->input->ip_address();
        
        $this->CI->db->where('username', $username);
        $this->CI->db->or_where('ip_address', $ip_address);
        $this->CI->db->delete('login_attempts');
    }
    
    /**
     * Inicia sesión para un empleado
     */
    public function startSession($employee_id) {
        $this->CI->session->set_userdata('person_id', $employee_id);
        
        // Gestionar sesiones permitidas
        $allowed_sessions = $this->CI->Employee_appconfig->get('allowed_sessions');
        $allowed_sessions = $allowed_sessions ? json_decode($allowed_sessions) : [];
        
        $current_session = session_id();
        if (!in_array($current_session, $allowed_sessions)) {
            $allowed_sessions[] = $current_session;
        }
        
        $this->CI->Employee_appconfig->save('allowed_sessions', json_encode($allowed_sessions));
    }
    
    /**
     * Genera token seguro para "Recordarme"
     */
    public function generateRememberMeToken($employee_id) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+' . self::REMEMBER_ME_DURATION_DAYS . ' days'));
        
        $this->ensureRememberMeTokensTableExists();
        
        $this->CI->db->insert('remember_me_tokens', [
            'employee_id' => $employee_id,
            'token' => hash('sha256', $token),
            'expires_at' => $expiry,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }
    
    /**
     * Valida token "Recordarme"
     */
    public function validateRememberMeToken($token) {
        $hashed_token = hash('sha256', $token);
        
        $this->CI->db->where('token', $hashed_token);
        $this->CI->db->where('expires_at >', date('Y-m-d H:i:s'));
        $this->CI->db->where('used', 0);
        
        $query = $this->CI->db->get('remember_me_tokens', 1);
        
        if ($query->num_rows() === 1) {
            $row = $query->row();
            
            // Rotar token para seguridad
            $this->CI->db->update('remember_me_tokens', ['used' => 1], ['id' => $row->id]);
            
            return $row->employee_id;
        }
        
        return null;
    }
    
    /**
     * Genera token único para reset de contraseña
     */
    public function generateResetToken($employee_id) {
        $token = bin2hex(random_bytes(16));
        $expiry = date('Y-m-d H:i:s', strtotime('+' . self::RESET_TOKEN_EXPIRY_HOURS . ' hours'));
        
        $this->ensurePasswordResetTokensTableExists();
        
        // Invalidar tokens anteriores
        $this->CI->db->update('password_reset_tokens', 
            ['valid' => 0], 
            ['employee_id' => $employee_id]
        );
        
        $this->CI->db->insert('password_reset_tokens', [
            'employee_id' => $employee_id,
            'token' => hash('sha256', $token),
            'expires_at' => $expiry,
            'created_at' => date('Y-m-d H:i:s'),
            'valid' => 1
        ]);
        
        return $token;
    }
    
    /**
     * Valida token de reset de contraseña
     */
    public function validateResetToken($token) {
        $hashed_token = hash('sha256', $token);
        
        $this->CI->db->select('prt.employee_id, p.username, p.first_name, p.last_name');
        $this->CI->db->from('password_reset_tokens prt');
        $this->CI->db->join('employees e', 'prt.employee_id = e.person_id');
        $this->CI->db->join('people p', 'e.person_id = p.person_id');
        $this->CI->db->where('prt.token', $hashed_token);
        $this->CI->db->where('prt.valid', 1);
        $this->CI->db->where('prt.expires_at >', date('Y-m-d H:i:s'));
        $this->CI->db->where('e.deleted', 0);
        $this->CI->db->where('e.inactive', 0);
        
        $query = $this->CI->db->get();
        
        if ($query->num_rows() === 1) {
            return $query->row();
        }
        
        return null;
    }
    
    /**
     * Establece nueva contraseña con bcrypt
     */
    public function setPassword($employee_id, $new_password) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT, ['cost' => 12]);
        
        $this->CI->db->update('employees', ['password' => $hash], ['person_id' => $employee_id]);
        
        // Invalidar todos los tokens de reset usados
        $this->CI->db->update('password_reset_tokens', 
            ['valid' => 0], 
            ['employee_id' => $employee_id]
        );
        
        // Invalidar todas las sesiones activas por seguridad
        $this->CI->Employee_appconfig->save('allowed_sessions', json_encode([]));
        
        return true;
    }
    
    /**
     * Obtiene historial de logins de un empleado
     */
    public function getLoginHistory($employee_id, $limit = 10) {
        $this->ensureLoginLogsTableExists();
        
        $this->CI->db->where('employee_id', $employee_id);
        $this->CI->db->where('login_result', 'success');
        $this->CI->db->order_by('login_time', 'DESC');
        
        $query = $this->CI->db->get('employee_login_logs', $limit);
        
        return $query->result();
    }
    
    /**
     * Asegura que exista la tabla login_attempts
     */
    private function ensureLoginAttemptsTableExists() {
        $this->CI->db->query("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempt_time DATETIME NOT NULL,
                INDEX idx_username (username),
                INDEX idx_ip (ip_address),
                INDEX idx_time (attempt_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    /**
     * Asegura que exista la tabla remember_me_tokens
     */
    private function ensureRememberMeTokensTableExists() {
        $this->CI->db->query("
            CREATE TABLE IF NOT EXISTS remember_me_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                INDEX idx_token (token),
                INDEX idx_employee (employee_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    /**
     * Asegura que exista la tabla password_reset_tokens
     */
    private function ensurePasswordResetTokensTableExists() {
        $this->CI->db->query("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                valid TINYINT(1) DEFAULT 1,
                INDEX idx_token (token),
                INDEX idx_employee (employee_id),
                INDEX idx_valid (valid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    /**
     * Asegura que exista la tabla employee_login_logs
     */
    private function ensureLoginLogsTableExists() {
        $this->CI->db->query("
            CREATE TABLE IF NOT EXISTS employee_login_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NULL,
                username VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                login_result ENUM('success', 'failed') NOT NULL,
                failure_reason VARCHAR(255),
                login_time DATETIME NOT NULL,
                INDEX idx_employee (employee_id),
                INDEX idx_result (login_result),
                INDEX idx_time (login_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
