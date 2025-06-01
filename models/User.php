<?php
class User {
    /**
     * Static method to create a default admin if none exists
     * @param PDO $db Database connection
     * @return bool True if admin was created or already exists, False if creation failed
     */
    public static function create_default_admin($db) {
        // Check if any admin exists
        $query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Admin already exists
            return true;
        }
        
        // Create admin user
        $user = new User($db);
        $user->username = 'admin';
        $user->full_name = 'System Administrator';
        $user->email = 'admin@example.com';
        $user->password = 'admin123';
        $user->role = 'admin';
        
        return $user->create();
    }
    // Database connection and table name
    private $conn;
    private $table_name = "users";
    
   // Object properties
    public $id;
    public $uuid;
    public $username;
    public $full_name;  // Changed from $name to $full_name
    public $email;
    public $password;
    public $role;
    public $status;
    public $created_at;
    public $phone_number;  // Added phone_number property
        
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Generate a UUID v4
     * 
     * @return string UUID v4
     */
    private function generate_uuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    // Create new user
    public function create() {
        // Sanitize inputs
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // Generate username from email if not set
        if (empty($this->username)) {
            $this->username = strtok($this->email, '@');
        }
        
        // Set default status if not set
        if (empty($this->status)) {
            $this->status = 'active';
        }
        
        // Generate UUID if not set
        if (empty($this->uuid)) {
            $this->uuid = $this->generate_uuid();
        }
        
        // Hash the password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Insert query with username and uuid
        $query = "INSERT INTO " . $this->table_name . " 
                  SET uuid = :uuid,
                      username = :username,
                      full_name = :full_name, 
                      email = :email, 
                      password = :password, 
                      role = :role,
                      status = :status,
                      created_at = NOW()";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(':uuid', $this->uuid);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);
        
        // Execute the query
        return $stmt->execute();
    }
    
    // Check if email exists
    public function emailExists() {
        // Sanitize input
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Query to check if email exists
        $query = "SELECT id, uuid, username, full_name, email, password, role
                  FROM " . $this->table_name . " 
                  WHERE email = ? 
                  LIMIT 0,1";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind email parameter
        $stmt->bindParam(1, $this->email);
        
        // Execute the query
        $stmt->execute();
        
        // Check if email exists
        if ($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
    
    // Login user
    public function login($email, $password) {
        // Sanitize inputs
        $email = htmlspecialchars(strip_tags($email));
        
        try {
            // First try with status field
            $query = "SELECT id, uuid, username, full_name, email, password, role, status
                      FROM " . $this->table_name . " 
                      WHERE email = ? 
                      LIMIT 0,1";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind email parameter
            $stmt->bindParam(1, $email);
            
            // Execute the query
            $stmt->execute();
        } catch (PDOException $e) {
            // If there's an error related to the status column, try without it
            if (strpos($e->getMessage(), "Unknown column 'status'") !== false) {
                $query = "SELECT id, uuid, username, full_name, email, password, role
                          FROM " . $this->table_name . " 
                          WHERE email = ? 
                          LIMIT 0,1";
                
                // Prepare the query
                $stmt = $this->conn->prepare($query);
                
                // Bind email parameter
                $stmt->bindParam(1, $email);
                
                // Execute the query
                $stmt->execute();
            } else {
                // If it's a different error, rethrow it
                throw $e;
            }
        }
        
        // Check if user exists
        if ($stmt->rowCount() > 0) {
            // Get user data
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get status from session if not in database
            $status = $row['status'] ?? ($_SESSION['user_statuses'][$row['id']] ?? 'active');
            
            // Check if user is inactive
            if ($status === 'inactive') {
                // Set error message
                $this->error = "Account is inactive. Please contact the administrator.";
                return false;
            }
            
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Set user properties
                $this->id = $row['id'];
                $this->uuid = $row['uuid'] ?? null;
                $this->username = $row['username'];
                $this->full_name = $row['full_name'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->status = $status;
                
                return true;
            }
        }
        
        return false;
    }
    
    // Read one user
    public function read_one() {
        try {
            // First try with all fields including phone_number
            $query = "SELECT id, uuid, username, full_name, email, password, role, status, phone_number, created_at
                      FROM " . $this->table_name . " 
                      WHERE id = ? 
                      LIMIT 0,1";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind ID parameter
            $stmt->bindParam(1, $this->id);
            
            // Execute the query
            $stmt->execute();
        } catch (PDOException $e) {
            // If there's an error with phone_number column, try without it
            if (strpos($e->getMessage(), "Unknown column 'phone_number'") !== false) {
                $query = "SELECT id, uuid, username, full_name, email, password, role, status, created_at
                          FROM " . $this->table_name . " 
                          WHERE id = ? 
                          LIMIT 0,1";
                
                // Prepare the query
                $stmt = $this->conn->prepare($query);
                
                // Bind ID parameter
                $stmt->bindParam(1, $this->id);
                
                // Execute the query
                $stmt->execute();
            } else {
                // If it's a different error, rethrow it
                throw $e;
            }
        }
        
        // Check if record exists
        if ($stmt->rowCount() > 0) {
            // Get record data
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set properties
            $this->id = $row['id'];
            $this->uuid = $row['uuid'];
            $this->username = $row['username'];
            $this->full_name = $row['full_name'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            $this->status = $row['status'] ?? 'active';
            $this->created_at = $row['created_at'];
            $this->phone_number = $row['phone_number'] ?? null;  // Set phone_number, default to null if not exists
            
            return true;
        }
        
        return false;
    }
    
    // Get all users with optional role filter
    public function read_all($role = null) {
        try {
            // First try to query with status field
            $query = "SELECT id, uuid, username, full_name, email, role, status, created_at
                      FROM " . $this->table_name;
            
            // Add role filter if provided
            if ($role !== null) {
                $query .= " WHERE role = :role";
            }
            
            $query .= " ORDER BY created_at DESC";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind role parameter if provided
            if ($role !== null) {
                $stmt->bindParam(':role', $role);
            }
            
            // Execute the query
            $stmt->execute();
        } catch (PDOException $e) {
            // If there's an error (like missing column), try without the status field
            if (strpos($e->getMessage(), "Column not found: 1054 Unknown column 'status'") !== false) {
                // Query without status field
                $query = "SELECT id, uuid, username, full_name, email, role, created_at
                          FROM " . $this->table_name;
                
                // Add role filter if provided
                if ($role !== null) {
                    $query .= " WHERE role = :role";
                }
                
                $query .= " ORDER BY created_at DESC";
                
                // Prepare the query
                $stmt = $this->conn->prepare($query);
                
                // Bind role parameter if provided
                if ($role !== null) {
                    $stmt->bindParam(':role', $role);
                }
                
                // Execute the query
                $stmt->execute();
            } else {
                // If it's a different error, rethrow it
                throw $e;
            }
        }
        
        return $stmt;
    }
    
    // Update user
    public function update() {
        // Sanitize inputs
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        // Query to update user
        $query = "UPDATE " . $this->table_name . " 
                  SET username = :username,
                      full_name = :full_name, 
                      email = :email";
        
        // Always update role if it's set - this is the key change
        $query .= ", role = :role";
        
        // Update uuid if it's not set
        if (empty($this->uuid)) {
            $this->uuid = $this->generate_uuid();
            $query .= ", uuid = :uuid";
        }
        
        $query .= " WHERE id = :id";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        if (empty($this->uuid)) {
            $stmt->bindParam(':uuid', $this->uuid);
        }
        $stmt->bindParam(':id', $this->id);
        
        // Execute the query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Change password
    public function update_password($new_password) {
        // Sanitize input
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Hash the new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Query to update password
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password 
                  WHERE id = :id";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $this->id);
        
        // Execute the query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate a password reset token for a user
     * 
     * @param string $email User email
     * @return string|bool Reset token if successful, false otherwise
     */
    public function generate_reset_token($email) {
        // Sanitize input
        $email = htmlspecialchars(strip_tags($email));
        
        // Query to get user data
        $query = "SELECT id, email
                  FROM " . $this->table_name . " 
                  WHERE email = ? 
                  LIMIT 0,1";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind email parameter
        $stmt->bindParam(1, $email);
        
        // Execute the query
        $stmt->execute();
        
        // Check if user exists
        if ($stmt->rowCount() > 0) {
            // Get user data
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set user ID
            $this->id = $row['id'];
            
            // Generate a random token
            $token = bin2hex(random_bytes(32)); // Increased from 16 to 32 bytes for better security
            
            // Set token expiry (24 hours from now)
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            try {
                // Query to update user with reset token
                $query = "UPDATE " . $this->table_name . " 
                          SET reset_token = :token, 
                              reset_token_expiry = :expiry 
                          WHERE id = :id";
                
                // Prepare the query
                $stmt = $this->conn->prepare($query);
                
                // Bind values
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expiry', $expiry);
                $stmt->bindParam(':id', $this->id);
                
                // Execute the query
                if ($stmt->execute()) {
                    return $token;
                }
            } catch (PDOException $e) {
                // If there's an error related to missing columns, add them
                if (strpos($e->getMessage(), "Unknown column 'reset_token'") !== false) {
                    // Add reset token columns
                    $alterQuery = "ALTER TABLE " . $this->table_name . " 
                                   ADD COLUMN reset_token VARCHAR(255) NULL,
                                   ADD COLUMN reset_token_expiry DATETIME NULL,
                                   ADD INDEX idx_reset_token (reset_token),
                                   ADD INDEX idx_reset_token_expiry (reset_token_expiry)";
                    
                    $alterStmt = $this->conn->prepare($alterQuery);
                    
                    if ($alterStmt->execute()) {
                        // Try again with the new columns
                        $query = "UPDATE " . $this->table_name . " 
                                  SET reset_token = :token, 
                                      reset_token_expiry = :expiry 
                                  WHERE id = :id";
                        
                        // Prepare the query
                        $stmt = $this->conn->prepare($query);
                        
                        // Bind values
                        $stmt->bindParam(':token', $token);
                        $stmt->bindParam(':expiry', $expiry);
                        $stmt->bindParam(':id', $this->id);
                        
                        // Execute the query
                        if ($stmt->execute()) {
                            return $token;
                        }
                    }
                }
                
                // Log the error
                error_log("Error generating reset token: " . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * Verify a password reset token
     * 
     * @param string $email User email
     * @param string $token Reset token
     * @return bool True if token is valid, false otherwise
     */
    public function verify_reset_token($email, $token) {
        // Sanitize inputs
        $email = htmlspecialchars(strip_tags($email));
        $token = htmlspecialchars(strip_tags($token));
        
        // Query to get user data with token
        $query = "SELECT id, reset_token, reset_token_expiry
                  FROM " . $this->table_name . " 
                  WHERE email = ? 
                  AND reset_token = ?
                  AND reset_token_expiry > NOW()
                  LIMIT 0,1";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(1, $email);
        $stmt->bindParam(2, $token);
        
        // Execute the query
        $stmt->execute();
        
        // Check if valid token exists
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Toggle user status (active/inactive)
     * 
     * @return bool True if status was updated, false otherwise
     */
    public function toggle_status() {
        // Query to get current status
        $query = "SELECT status FROM " . $this->table_name . " WHERE id = :id";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind ID parameter
        $stmt->bindParam(':id', $this->id);
        
        // Execute the query
        $stmt->execute();
        
        // Get current status
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_status = $row['status'] ?? 'active';
        
        // Toggle status
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
        
        return $this->set_status($new_status);
    }
    
    /**
     * Set specific user status
     * 
     * @param string $status Status to set ('active' or 'inactive')
     * @return bool True if status was updated, false otherwise
     */
    public function set_status($status) {
        // Validate status
        if (!in_array($status, ['active', 'inactive'])) {
            return false;
        }
        
        try {
            // Query to update status
            $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $this->id);
            
            // Execute the query
            if ($stmt->execute()) {
                $this->status = $status;
                return true;
            }
        } catch (PDOException $e) {
            // If there's an error related to missing status column, add it
            if (strpos($e->getMessage(), "Unknown column 'status'") !== false) {
                // Add status column
                $alterQuery = "ALTER TABLE " . $this->table_name . " 
                               ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
                
                $alterStmt = $this->conn->prepare($alterQuery);
                
                if ($alterStmt->execute()) {
                    // Try again with the new column
                    $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
                    
                    // Prepare the query
                    $stmt = $this->conn->prepare($query);
                    
                    // Bind parameters
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':id', $this->id);
                    
                    // Execute the query
                    if ($stmt->execute()) {
                        $this->status = $status;
                        return true;
                    }
                }
            } else {
                // If it's a different error, store status in session
                $_SESSION['user_statuses'][$this->id] = $status;
                $this->status = $status;
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Reset password using token
     * 
     * @param string $email User email
     * @param string $token Reset token
     * @param string $new_password New password
     * @return bool True if password was reset, false otherwise
     */
    public function reset_password_by_token($email, $token, $new_password) {
        // Verify token first
        if ($this->verify_reset_token($email, $token)) {
            // Hash the new password
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            try {
                // Begin transaction
                $this->conn->beginTransaction();
                
                // Update password
                $query = "UPDATE " . $this->table_name . " 
                          SET password = :password,
                              reset_token = NULL,
                              reset_token_expiry = NULL
                          WHERE id = :id
                          AND reset_token = :token";
                
                // Prepare the query
                $stmt = $this->conn->prepare($query);
                
                // Bind values
                $stmt->bindParam(':password', $password_hash);
                $stmt->bindParam(':id', $this->id);
                $stmt->bindParam(':token', $token);
                
                // Execute the query
                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    $this->conn->commit();
                    return true;
                }
                
                $this->conn->rollBack();
            } catch (PDOException $e) {
                $this->conn->rollBack();
                error_log("Error resetting password: " . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * Get user by UUID
     * 
     * @param string $uuid User UUID
     * @return bool True if user was found, false otherwise
     */
    public function get_by_uuid($uuid) {
        // Sanitize input
        $uuid = htmlspecialchars(strip_tags($uuid));
        
        try {
            // First try to query with status field
            $query = "SELECT id, uuid, username, full_name, email, role, status, created_at
                      FROM " . $this->table_name . " 
                      WHERE uuid = ? 
                      LIMIT 0,1";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind UUID parameter
            $stmt->bindParam(1, $uuid);
            
            // Execute the query
            $stmt->execute();
        } catch (PDOException $e) {
            // If there's an error (like missing column), try without the status field
            if (strpos($e->getMessage(), "Column not found: 1054 Unknown column 'status'") !== false) {
                // Query without status field
                $query = "SELECT id, uuid, username, full_name, email, role, created_at
                          FROM " . $this->table_name . " 
                          WHERE uuid = ? 
                          LIMIT 0,1";
                
                // Prepare the query
                $stmt = $this->conn->prepare($query);
                
                // Bind UUID parameter
                $stmt->bindParam(1, $uuid);
                
                // Execute the query
                $stmt->execute();
            } else {
                // If it's a different error, rethrow it
                throw $e;
            }
        }
        
        // Check if user exists
        if ($stmt->rowCount() > 0) {
            // Get user data
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set user properties
            $this->id = $row['id'];
            $this->uuid = $row['uuid'];
            $this->username = $row['username'];
            $this->full_name = $row['full_name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->status = $row['status'] ?? 'active'; // Default to 'active' if status column doesn't exist
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }
}
?>
