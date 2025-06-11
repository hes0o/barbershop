<?php
require_once 'config.php';
require_once 'db.php';

class Auth {
    private $db;
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutes in seconds
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($email, $password) {
        try {
            // Validate input
            if (empty($email) || empty($password)) {
                throw new Exception('Please provide both email and password');
            }
            
            // Check for too many failed attempts
            if ($this->isAccountLocked($email)) {
                throw new Exception('Account is temporarily locked. Please try again later.');
            }
            
            // Get user by email
            $user = $this->db->getUserByEmail($email);
            
            if (!$user) {
                $this->logFailedAttempt($email, false);
                throw new Exception('Invalid email or password');
            }
            
            // Check if account is active (safely check status field)
            if (isset($user['status']) && $user['status'] !== 'active') {
                throw new Exception('Account is not active. Please contact support.');
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->logFailedAttempt($email, false);
                throw new Exception('Invalid email or password');
            }
            
            // Log successful attempt
            $this->logFailedAttempt($email, true);
            
            // Update last login time
            $this->updateLastLogin($user['id']);
            
            // Create session
            $this->createSession($user);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function createSession($user) {
        // Clear any existing session
        session_unset();
        session_destroy();
        session_start();
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    private function logFailedAttempt($email, $success) {
        try {
            $conn = $this->db->getConnection();
            $ip = $_SERVER['REMOTE_ADDR'];
            
            $stmt = $conn->prepare("
                INSERT INTO login_attempts (email, ip_address, attempt_time, success)
                VALUES (?, ?, NOW(), ?)
            ");
            
            if ($stmt === false) {
                error_log("Error preparing statement in logFailedAttempt: " . $conn->error);
                return;
            }
            
            $stmt->bind_param("ssi", $email, $ip, $success);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in logFailedAttempt: " . $e->getMessage());
        }
    }
    
    private function isAccountLocked($email) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT COUNT(*) as attempt_count
                FROM login_attempts
                WHERE email = ?
                AND success = 0
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            if ($stmt === false) {
                error_log("Error preparing statement in isAccountLocked: " . $conn->error);
                return false;
            }
            
            $stmt->bind_param("si", $email, $this->lockoutTime);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            return $result['attempt_count'] >= $this->maxLoginAttempts;
        } catch (Exception $e) {
            error_log("Error in isAccountLocked: " . $e->getMessage());
            return false;
        }
    }
    
    private function updateLastLogin($user_id) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE users 
                SET last_login = NOW() 
                WHERE id = ?
            ");
            
            if ($stmt === false) {
                error_log("Error preparing statement in updateLastLogin: " . $conn->error);
                return;
            }
            
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in updateLastLogin: " . $e->getMessage());
        }
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        ];
    }
    
    public function checkSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Check if session has expired (30 minutes)
        if (time() - $_SESSION['last_activity'] > 1800) {
            $this->logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function requireLogin() {
        if (!$this->checkSession()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireRole($roles) {
        $this->requireLogin();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($_SESSION['role'], $roles)) {
            header('Location: index.php');
            exit;
        }
    }
} 