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
    private $db;
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
    public $display_name;
    public $roles = [];
    public $error;
        
    
    // Constructor with database connection
    public function __construct($db) {
        $this->db = $db;
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
        try {
            $this->db->beginTransaction();

            // Insert into users table
            $query = "INSERT INTO " . $this->table_name . "
                    (uuid, username, email, password, created_at)
                    VALUES
                    (:uuid, :username, :email, :password, :created_at)";
            
            $stmt = $this->db->prepare($query);
            
            $this->uuid = uniqid('usr_', true);
            $this->created_at = date('Y-m-d H:i:s');
            
            $stmt->bindParam(":uuid", $this->uuid);
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":created_at", $this->created_at);
            
            $stmt->execute();
            $this->id = $this->db->lastInsertId();

            // Insert into user_profiles
            $query = "INSERT INTO user_profiles
                    (user_id, full_name, display_name, phone_number, status)
                    VALUES
                    (:user_id, :full_name, :display_name, :phone_number, :status)";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(":user_id", $this->id);
            $stmt->bindParam(":full_name", $this->full_name);
            $stmt->bindParam(":display_name", $this->display_name);
            $stmt->bindParam(":phone_number", $this->phone_number);
            $stmt->bindParam(":status", $this->status);
            
            $stmt->execute();

            // Assign roles
            foreach ($this->roles as $role_name) {
                $query = "INSERT INTO user_roles (user_id, role_id)
                         SELECT :user_id, id FROM roles WHERE name = :role_name";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":user_id", $this->id);
                $stmt->bindParam(":role_name", $role_name);
                $stmt->execute();
            }

            $this->db->commit();
            return true;
        } catch(Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
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
        $stmt = $this->db->prepare($query);
        
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
        // Sanitize email
        $email = htmlspecialchars(strip_tags($email));
        
        try {
            // Query using the normalized schema
            $query = "SELECT u.id, u.uuid, u.username, u.email, u.password,
                             up.full_name, up.status,
                             GROUP_CONCAT(r.name) as roles
                      FROM " . $this->table_name . " u
                      LEFT JOIN user_profiles up ON u.id = up.user_id
                      LEFT JOIN user_roles ur ON u.id = ur.user_id
                      LEFT JOIN roles r ON ur.role_id = r.id
                      WHERE u.email = ?
                      GROUP BY u.id
                      LIMIT 0,1";
            
            // Prepare the query
            $stmt = $this->db->prepare($query);
            
            // Bind email parameter
            $stmt->bindParam(1, $email);
            
            // Execute the query
            $stmt->execute();
            
            // Check if user exists
            if ($stmt->rowCount() > 0) {
                // Get user data
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get status from profile or session
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
                    $this->roles = $row['roles'] ? explode(',', $row['roles']) : ['donor'];
                    $this->role = $this->roles[0]; // For backward compatibility
                    $this->status = $status;
                    
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            // If there's an error with the normalized schema, try the legacy schema
            try {
                $query = "SELECT id, uuid, username, full_name, email, password, role, status
                          FROM " . $this->table_name . " 
                          WHERE email = ? 
                          LIMIT 0,1";
                
                // Prepare the query
                $stmt = $this->db->prepare($query);
                
                // Bind email parameter
                $stmt->bindParam(1, $email);
                
                // Execute the query
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $status = $row['status'] ?? 'active';
                    
                    if ($status === 'inactive') {
                        $this->error = "Account is inactive. Please contact the administrator.";
                        return false;
                    }
                    
                    if (password_verify($password, $row['password'])) {
                        $this->id = $row['id'];
                        $this->uuid = $row['uuid'] ?? null;
                        $this->username = $row['username'];
                        $this->full_name = $row['full_name'];
                        $this->email = $row['email'];
                        $this->role = $row['role'];
                        $this->roles = [$row['role']];
                        $this->status = $status;
                        
                        return true;
                    }
                }
                
                return false;
            } catch (PDOException $e2) {
                error_log("Login error: " . $e2->getMessage());
                throw $e2;
            }
        }
    }
    
    // Read one user
    public function read_one() {
        try {
            $query = "SELECT u.id, u.uuid, u.username, u.email, u.password, u.created_at,
                             up.full_name, up.display_name, up.status,
                             GROUP_CONCAT(r.name) as roles
                      FROM " . $this->table_name . " u
                      LEFT JOIN user_profiles up ON u.id = up.user_id
                      LEFT JOIN user_roles ur ON u.id = ur.user_id
                      LEFT JOIN roles r ON ur.role_id = r.id
                      WHERE u.id = :id
                      GROUP BY u.id
                      LIMIT 0,1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Set properties
                $this->id = $row['id'];
                $this->uuid = $row['uuid'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->password = $row['password'];
                $this->full_name = $row['full_name'];
                $this->display_name = $row['display_name'];
                $this->status = $row['status'] ?? 'active';
                $this->created_at = $row['created_at'];
                $this->roles = $row['roles'] ? explode(',', $row['roles']) : [];
                
                // For backward compatibility
                $this->role = $this->roles[0] ?? 'donor';
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            // If there's an error with the new schema, try the old schema
            $query = "SELECT id, uuid, username, full_name, email, password, role, status, created_at
                      FROM " . $this->table_name . " 
                      WHERE id = :id 
                      LIMIT 0,1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $this->id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
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
                $this->roles = [$row['role']];
                
                return true;
            }
            
            return false;
        }
    }
    
    // Get all users with optional role filter
    public function read_all($role = null) {
        try {
            $query = "SELECT u.id, u.uuid, u.username, u.email, u.created_at,
                             up.full_name, up.display_name, up.status,
                             GROUP_CONCAT(r.name) as roles,
                             COALESCE(GROUP_CONCAT(r.name), 'donor') as role
                      FROM " . $this->table_name . " u
                      LEFT JOIN user_profiles up ON u.id = up.user_id
                      LEFT JOIN user_roles ur ON u.id = ur.user_id
                      LEFT JOIN roles r ON ur.role_id = r.id";
            
            // Add role filter if provided
            if ($role !== null) {
                $query .= " WHERE r.name = :role";
            }
            
            $query .= " GROUP BY u.id ORDER BY u.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            
            // Bind role parameter if provided
            if ($role !== null) {
                $stmt->bindParam(':role', $role);
            }
            
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            // If there's an error with the new schema, try the old schema
            $query = "SELECT id, uuid, username, email, full_name, 
                            COALESCE(role, 'donor') as role, 
                            COALESCE(status, 'active') as status, 
                            created_at
                      FROM " . $this->table_name;
            
            // Add role filter if provided
            if ($role !== null) {
                $query .= " WHERE role = :role";
            }
            
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($query);
            
            // Bind role parameter if provided
            if ($role !== null) {
                $stmt->bindParam(':role', $role);
            }
            
            $stmt->execute();
            return $stmt;
        }
    }
    
    // Update user
    public function update() {
        try {
            $this->db->beginTransaction();

            // Update users table
            $query = "UPDATE " . $this->table_name . "
                    SET username = :username,
                        email = :email
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->id);
            
            $stmt->execute();

            // Update user_profiles
            $query = "UPDATE user_profiles
                    SET full_name = :full_name,
                        display_name = :display_name,
                        phone_number = :phone_number,
                        status = :status
                    WHERE user_id = :id";
            
            $stmt = $this->db->prepare($query);
            
            $stmt->bindParam(":full_name", $this->full_name);
            $stmt->bindParam(":display_name", $this->display_name);
            $stmt->bindParam(":phone_number", $this->phone_number);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":id", $this->id);
            
            $stmt->execute();

            // Update roles
            $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $this->id);
            $stmt->execute();

            foreach ($this->roles as $role_name) {
                $query = "INSERT INTO user_roles (user_id, role_id)
                         SELECT :user_id, id FROM roles WHERE name = :role_name";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":user_id", $this->id);
                $stmt->bindParam(":role_name", $role_name);
                $stmt->execute();
            }

            $this->db->commit();
            return true;
        } catch(Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
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
        $stmt = $this->db->prepare($query);
        
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
        $stmt = $this->db->prepare($query);
        
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
                $stmt = $this->db->prepare($query);
                
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
                    
                    $alterStmt = $this->db->prepare($alterQuery);
                    
                    if ($alterStmt->execute()) {
                        // Try again with the new columns
                        $query = "UPDATE " . $this->table_name . " 
                                  SET reset_token = :token, 
                                      reset_token_expiry = :expiry 
                                  WHERE id = :id";
                        
                        // Prepare the query
                        $stmt = $this->db->prepare($query);
                        
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
        $stmt = $this->db->prepare($query);
        
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
        $stmt = $this->db->prepare($query);
        
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
            $stmt = $this->db->prepare($query);
            
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
                
                $alterStmt = $this->db->prepare($alterQuery);
                
                if ($alterStmt->execute()) {
                    // Try again with the new column
                    $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
                    
                    // Prepare the query
                    $stmt = $this->db->prepare($query);
                    
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
                $this->db->beginTransaction();
                
                // Update password
                $query = "UPDATE " . $this->table_name . " 
                          SET password = :password,
                              reset_token = NULL,
                              reset_token_expiry = NULL
                          WHERE id = :id
                          AND reset_token = :token";
                
                // Prepare the query
                $stmt = $this->db->prepare($query);
                
                // Bind values
                $stmt->bindParam(':password', $password_hash);
                $stmt->bindParam(':id', $this->id);
                $stmt->bindParam(':token', $token);
                
                // Execute the query
                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    $this->db->commit();
                    return true;
                }
                
                $this->db->rollBack();
            } catch (PDOException $e) {
                $this->db->rollBack();
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
            $stmt = $this->db->prepare($query);
            
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
                $stmt = $this->db->prepare($query);
                
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

    public function read($id) {
        $query = "SELECT u.*, up.full_name, up.display_name, up.status,
                        GROUP_CONCAT(r.name) as roles
                 FROM " . $this->table_name . " u
                 LEFT JOIN user_profiles up ON u.id = up.user_id
                 LEFT JOIN user_roles ur ON u.id = ur.user_id
                 LEFT JOIN roles r ON ur.role_id = r.id
                 WHERE u.id = :id
                 GROUP BY u.id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->uuid = $row['uuid'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->display_name = $row['display_name'];
            $this->status = $row['status'];
            $this->roles = $row['roles'] ? explode(',', $row['roles']) : [];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    public function delete() {
        // With CASCADE constraints, we only need to delete from users table
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    public function hasRole($role_name) {
        return in_array($role_name, $this->roles);
    }

    public function createPasswordReset() {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $query = "INSERT INTO password_resets (user_id, token, expires_at)
                 VALUES (:user_id, :token, :expires_at)
                 ON DUPLICATE KEY UPDATE
                 token = VALUES(token),
                 expires_at = VALUES(expires_at)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires_at", $expires);
        
        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

    public function verifyPasswordReset($token) {
        $query = "SELECT * FROM password_resets
                 WHERE user_id = :user_id
                 AND token = :token
                 AND expires_at > NOW()";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>
