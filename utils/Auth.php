<?php
class Auth {
    private $user_id;
    private $user_role;
    private $user_name;
    
    public function __construct() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Load user data from session
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->user_role = $_SESSION['role'] ?? null;
        $this->user_name = $_SESSION['name'] ?? null;
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user's ID
     * @return int|null
     */
    public function getUserId() {
        return $this->user_id;
    }
    
    /**
     * Get current user's role
     * @return string|null
     */
    public function getUserRole() {
        return $this->user_role;
    }
    
    /**
     * Get current user's name
     * @return string|null
     */
    public function getUserName() {
        return $this->user_name;
    }
    
    /**
     * Check if user has specific role
     * @param string|array $roles Single role or array of roles to check
     * @return bool
     */
    public function hasRole($roles): bool {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (is_array($roles)) {
            return in_array($this->user_role, $roles);
        }
        
        return $this->user_role === $roles;
    }
    
    /**
     * Log in a user
     * @param array $user User data from database
     * @return void
     */
    public function login(array $user): void {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];
        
        $this->user_id = $user['id'];
        $this->user_role = $user['role'];
        $this->user_name = $user['full_name'];
    }
    
    /**
     * Log out current user
     * @return void
     */
    public function logout(): void {
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
        
        // Clear instance variables
        $this->user_id = null;
        $this->user_role = null;
        $this->user_name = null;
    }
    
    /**
     * Require authentication for the current page
     * @param string|array $allowed_roles Optional roles to restrict access to
     * @param string $redirect_url URL to redirect to if not authenticated
     * @return void
     */
    public function requireAuth($allowed_roles = null, string $redirect_url = '../auth/login.php'): void {
        if (!$this->isLoggedIn()) {
            header('Location: ' . $redirect_url);
            exit();
        }
        
        if ($allowed_roles !== null) {
            if (!$this->hasRole($allowed_roles)) {
                $_SESSION['error'] = "You don't have permission to access this page.";
                header('Location: ' . $redirect_url);
                exit();
            }
        }
    }
    
    /**
     * Get current user's data
     * @return array|null
     */
    public function getCurrentUser(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $this->user_id,
            'role' => $this->user_role,
            'name' => $this->user_name
        ];
    }
} 