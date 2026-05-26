<?php
/**
 * Servicio de Autenticación Modernizado
 * 
 * Maneja el proceso de login con seguridad mejorada:
 * - Soporte dual bcrypt/MD5 (migración automática)
 * - Rate limiting para intentos fallidos
 * - Tokens CSRF
 * - Auditoría completa
 * - Gestión de sesiones segura
 * - Funcionalidad "Recordarme"
 */

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/Authentication/AuthRateLimiter.php';
require_once APPPATH . 'libraries/Authentication/AuthLogger.php';
require_once APPPATH . 'libraries/Authentication/AuthRememberToken.php';

class AuthService {
    
    protected $CI;
    protected $rateLimiter;
    protected $logger;
    protected $rememberToken;
    
    // Configuración de seguridad
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minutos
    const REMEMBER_ME_DURATION = 2592000; // 30 días
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Employee');
        $this->CI->load->model('Person');
        $this->CI->load->helper('security');
        
        $this->rateLimiter = new AuthRateLimiter();
        $this->logger = new AuthLogger();
        $this->rememberToken = new AuthRememberToken();
    }
    
    /**
     * Proceso principal de autenticación
     */
    public function authenticate($username, $password, $rememberMe = false, $twoFaCode = null) {
        // Verificar rate limiting
        if ($this->rateLimiter->isLocked($username)) {
            $this->logger->logAttempt($username, 'LOCKED_OUT', 'IP: ' . $this->CI->input->ip_address());
            return [
                'success' => false,
                'error' => 'Demasiados intentos fallidos. Intente nuevamente en 15 minutos.',
                'locked' => true
            ];
        }
        
        // Buscar empleado por username o email
        $employeeId = $this->CI->Employee->get_employee_id_by_username($username);
        
        if (!$employeeId) {
            $employeeId = $this->CI->Employee->get_employee_id_by_email($username);
        }
        
        if (!$employeeId) {
            $this->rateLimiter->recordAttempt($username, false);
            $this->logger->logAttempt($username, 'USER_NOT_FOUND', 'IP: ' . $this->CI->input->ip_address());
            return [
                'success' => false,
                'error' => 'Usuario o contraseña incorrectos'
            ];
        }
        
        // Obtener datos del empleado
        $employeeInfo = $this->CI->Employee->get_info($employeeId);
        $personInfo = $this->CI->Person->get_info($employeeId->person_id);
        
        // Verificar estado del empleado
        if ($employeeInfo->deleted) {
            $this->logger->logAttempt($username, 'DELETED_ACCOUNT', 'Employee ID: ' . $employeeId);
            return [
                'success' => false,
                'error' => 'Esta cuenta ha sido desactivada'
            ];
        }
        
        // Verificar contraseña (soporte dual bcrypt/MD5)
        $passwordValid = $this->verifyPassword($password, $employeeInfo->password_hash);
        
        if (!$passwordValid) {
            $this->rateLimiter->recordAttempt($username, false);
            $this->logger->logAttempt($username, 'INVALID_PASSWORD', 'IP: ' . $this->CI->input->ip_address());
            return [
                'success' => false,
                'error' => 'Usuario o contraseña incorrectos'
            ];
        }
        
        // Migrar hash MD5 a bcrypt si es necesario
        if ($this->needsPasswordMigration($employeeInfo->password_hash)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            $this->CI->Employee->update_password_hash($employeeId, $newHash);
        }
        
        // Verificar 2FA si está habilitado
        if ($employeeInfo->twofa_secret_required && empty($employeeInfo->twofa_secret_override)) {
            if ($twoFaCode === null) {
                // Guardar credenciales temporales para verificación 2FA
                $this->CI->session->set_userdata('pending_employee_id', $employeeId);
                $this->CI->session->set_userdata('pending_2fa_required', true);
                
                $this->logger->logAttempt($username, '2FA_REQUIRED', 'Pending 2FA verification');
                return [
                    'success' => false,
                    'require_2fa' => true,
                    'employee_id' => $employeeId
                ];
            } else {
                // Verificar código 2FA
                if (!$this->verifyTwoFaCode($employeeInfo->twofa_secret, $twoFaCode)) {
                    $this->rateLimiter->recordAttempt($username, false);
                    $this->logger->logAttempt($username, 'INVALID_2FA', 'IP: ' . $this->CI->input->ip_address());
                    return [
                        'success' => false,
                        'error' => 'Código de autenticación inválido'
                    ];
                }
            }
        }
        
        // Login exitoso - resetear contador de intentos
        $this->rateLimiter->resetAttempts($username);
        
        // Configurar sesión
        $this->setupSession($employeeId, $personInfo);
        
        // Generar token "Recordarme" si se solicitó
        if ($rememberMe) {
            $token = $this->rememberToken->createToken($employeeId);
            setcookie('remember_token', $token, time() + self::REMEMBER_ME_DURATION, '/', '', config_item('cookie_secure'), true);
        }
        
        // Registrar log exitoso
        $this->logger->logAttempt($username, 'SUCCESS', 'IP: ' . $this->CI->input->ip_address() . ' | Employee ID: ' . $employeeId);
        
        // Verificar suscripción/trial
        $subscriptionStatus = $this->checkSubscription($employeeId);
        
        return [
            'success' => true,
            'employee_id' => $employeeId,
            'redirect' => $subscriptionStatus['valid'] ? 'home' : 'subscription/expired',
            'subscription' => $subscriptionStatus
        ];
    }
    
    /**
     * Verificar contraseña con soporte dual
     */
    protected function verifyPassword($password, $hash) {
        // Si el hash comienza con $2y$, es bcrypt
        if (substr($hash, 0, 4) === '$2y$') {
            return password_verify($password, $hash);
        }
        
        // De lo contrario, es MD5 (legacy)
        return md5($password) === $hash;
    }
    
    /**
     * Verificar si necesita migración de contraseña
     */
    protected function needsPasswordMigration($hash) {
        return substr($hash, 0, 4) !== '$2y$';
    }
    
    /**
     * Configurar sesión de usuario
     */
    protected function setupSession($employeeId, $personInfo) {
        $this->CI->session->set_userdata('employee_id', $employeeId);
        $this->CI->session->set_userdata('person_id', $personInfo->person_id);
        $this->CI->session->set_userdata('user_first_name', $personInfo->first_name);
        $this->CI->session->set_userdata('user_last_name', $personInfo->last_name);
        $this->CI->session->set_userdata('user_email', $personInfo->email);
        $this->CI->session->set_userdata('logged_in', true);
        
        // Información adicional
        $employeeInfo = $this->CI->Employee->get_info($employeeId);
        $this->CI->session->set_userdata('user_role', $employeeInfo->role ?? 'employee');
        $this->CI->session->set_userdata('language_code', $employeeInfo->language_code ?? 'es');
        
        // Timestamp de login
        $this->CI->session->set_userdata('login_time', time());
    }
    
    /**
     * Verificar código 2FA
     */
    protected function verifyTwoFaCode($secret, $code) {
        if (empty($secret) || empty($code)) {
            return false;
        }
        
        // Implementación básica de TOTP
        // En producción usar librería como PHPGangsta/GoogleAuthenticator
        $generatedCode = $this->generateTOTP($secret);
        return $generatedCode === $code;
    }
    
    /**
     * Generar código TOTP
     */
    protected function generateTOTP($secret, $timeStep = 30) {
        $time = floor(time() / $timeStep);
        $base32 = $this->base32Decode($secret);
        $timePack = pack('N', 0) . pack('N', $time);
        $hmac = hash_hmac('sha1', $timePack, $base32, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $binary = unpack('N', substr($hmac, $offset, 4));
        $otp = $binary[1] & 0x7FFFFFFF;
        return str_pad(substr($otp, -6), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Decodificar Base32
     */
    protected function base32Decode($secret) {
        $encoding = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $decoded = '';
        $buffer = 0;
        $bitsLeft = 0;
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = strtoupper($secret[$i]);
            if ($char === '=') continue;
            
            $value = strpos($encoding, $char);
            if ($value === false) continue;
            
            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        
        return $decoded;
    }
    
    /**
     * Verificar estado de suscripción
     */
    protected function checkSubscription($employeeId) {
        $this->CI->load->model('Subscription/Subscription_model');
        
        $subscription = $this->CI->Subscription_model->getActiveSubscription($employeeId);
        
        if (!$subscription) {
            return ['valid' => false, 'status' => 'NO_SUBSCRIPTION'];
        }
        
        $now = date('Y-m-d H:i:s');
        $endDate = $subscription->end_date;
        
        if ($now > $endDate) {
            return ['valid' => false, 'status' => 'EXPIRED', 'end_date' => $endDate];
        }
        
        // Verificar si está próximo a vencer (7 días)
        $daysUntilExpiry = floor((strtotime($endDate) - time()) / 86400);
        $warning = $daysUntilExpiry <= 7;
        
        return [
            'valid' => true,
            'status' => 'ACTIVE',
            'plan' => $subscription->plan_name,
            'end_date' => $endDate,
            'days_until_expiry' => $daysUntilExpiry,
            'warning' => $warning
        ];
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $employeeId = $this->CI->session->userdata('employee_id');
        $username = $this->CI->session->userdata('user_email');
        
        // Eliminar token remember me
        $this->rememberToken->revokeToken($employeeId);
        
        // Registrar logout
        $this->logger->logLogout($employeeId, $username);
        
        // Destruir sesión
        $this->CI->session->sess_destroy();
        
        // Eliminar cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    /**
     * Intentar login automático con remember token
     */
    public function autoLogin() {
        $token = $_COOKIE['remember_token'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $employeeId = $this->rememberToken->validateToken($token);
        
        if (!$employeeId) {
            return false;
        }
        
        // Regenerar token
        $newToken = $this->rememberToken->refreshToken($employeeId, $token);
        setcookie('remember_token', $newToken, time() + self::REMEMBER_ME_DURATION, '/', '', config_item('cookie_secure'), true);
        
        // Setup sesión
        $employeeInfo = $this->CI->Employee->get_info($employeeId);
        $personInfo = $this->CI->Person->get_info($employeeId->person_id);
        $this->setupSession($employeeId, $personInfo);
        
        return true;
    }
}
