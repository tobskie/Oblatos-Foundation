<?php
class Donation {
    // Database connection and table name
    private $conn;
    private $table_name = "donations";
    
    // Object properties
    public $id;
    public $donor_id;
    public $amount;
    public $payment_method;
    public $reference_number;
    public $payment_proof;
    public $status;
    public $verified_by;
    public $verified_at;
    public $created_at;
    
    // Additional properties for joined data
    public $donor_name;
    public $donor_email;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new donation
    public function create() {
        // Sanitize inputs
        $this->donor_id = htmlspecialchars(strip_tags($this->donor_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
        $this->reference_number = htmlspecialchars(strip_tags($this->reference_number));
        $this->payment_proof = htmlspecialchars(strip_tags($this->payment_proof));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Debug log
        error_log("Creating donation with values:");
        error_log("- donor_id: " . $this->donor_id);
        error_log("- amount: " . $this->amount);
        error_log("- payment_method: " . $this->payment_method);
        error_log("- reference_number: " . $this->reference_number);
        error_log("- payment_proof: " . $this->payment_proof);
        error_log("- status: " . $this->status);
        
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            // Check if the donor exists in the users table
            $checkUserQuery = "SELECT id FROM users WHERE id = :user_id";
            $checkUserStmt = $this->conn->prepare($checkUserQuery);
            $checkUserStmt->bindParam(':user_id', $this->donor_id);
            $checkUserStmt->execute();
            
            if ($checkUserStmt->rowCount() == 0) {
                error_log("User with ID {$this->donor_id} doesn't exist");
                throw new PDOException("User with ID {$this->donor_id} doesn't exist");
            }
            
            error_log("User exists, proceeding with donation creation");
            
            // Insert the donation
            $query = "INSERT INTO " . $this->table_name . " 
                      SET donor_id = :donor_id, 
                          amount = :amount, 
                          payment_method = :payment_method, 
                          reference_number = :reference_number,
                          payment_proof = :payment_proof,
                          status = :status,
                          created_at = NOW()";
            
            error_log("Prepared query: " . $query);
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(':donor_id', $this->donor_id);
            $stmt->bindParam(':amount', $this->amount);
            $stmt->bindParam(':payment_method', $this->payment_method);
            $stmt->bindParam(':reference_number', $this->reference_number);
            $stmt->bindParam(':payment_proof', $this->payment_proof);
            $stmt->bindParam(':status', $this->status);
            
            error_log("Executing query...");
            
            // Execute the query
            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                error_log("Query execution failed: " . print_r($error, true));
                throw new PDOException("Query execution failed: " . $error[2]);
            }
            
            error_log("Query executed successfully");
            
            // Get the last inserted ID
            $lastId = $this->conn->lastInsertId();
            error_log("Last inserted ID: " . $lastId);
            
            // Commit the transaction
            $this->conn->commit();
            error_log("Transaction committed");
            
            return $lastId;
            
        } catch (PDOException $e) {
            // Rollback the transaction on error
            $this->conn->rollBack();
            error_log("Error in create(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    // Read one donation
    public function read_one() {
        $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, 
                     d.payment_proof, d.status, d.verified_by, d.verified_at, d.created_at,
                     u.full_name as donor_name, u.email as donor_email
              FROM " . $this->table_name . " d
              LEFT JOIN users u ON d.donor_id = u.id
              WHERE d.id = ? 
              LIMIT 0,1";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind ID parameter
        $stmt->bindParam(1, $this->id);
        
        // Execute the query
        $stmt->execute();
        
        // Check if record exists
        if ($stmt->rowCount() > 0) {
            // Get record data
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set properties
            $this->id = $row['id'];
            $this->donor_id = $row['donor_id'];
            $this->amount = $row['amount'];
            $this->payment_method = $row['payment_method'] ?? '';
            $this->reference_number = $row['reference_number'] ?? '';
            $this->payment_proof = $row['payment_proof'] ?? '';
            $this->status = $row['status'] ?? 'pending';
            $this->verified_by = $row['verified_by'] ?? null;
            $this->verified_at = $row['verified_at'] ?? null;
            $this->created_at = $row['created_at'] ?? date('Y-m-d H:i:s');
            
            $this->donor_name = $row['donor_name'] ?? 'Unknown Donor';
            $this->donor_email = $row['donor_email'] ?? '';
            
            return true;
        }
        
        return false;
    }
    
    // Read all donations with optional filters
    public function read_all($user_id = null, $status = null, $start_date = null, $end_date = null) {
        // Start with the base query that includes the required fields
        $query = "SELECT d.id, d.donor_id, 
                        COALESCE(u.full_name, 'Anonymous') as donor_name, 
                        u.email as donor_email, 
                        d.amount, d.payment_method, d.reference_number, d.status, d.created_at,
                        d.verified_by,
                        COALESCE(v.full_name, 'Unknown') as verifier_name
                 FROM " . $this->table_name . " d
                 LEFT JOIN users u ON d.donor_id = u.id
                 LEFT JOIN users v ON d.verified_by = v.id
                 WHERE 1=1";
        
        // Try to add optional fields
        try {
            // Check if payment_proof column exists
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'payment_proof'");
            if ($stmt->rowCount() > 0) {
                $query = str_replace("d.status, d.created_at", 
                                   "d.status, d.payment_proof, d.created_at", $query);
            }
            
            // Check if verified_at column exists
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name . " LIKE 'verified_at'");
            if ($stmt->rowCount() > 0) {
                $query = str_replace("d.created_at", "d.verified_at, d.created_at", $query);
            }
        } catch (PDOException $e) {
            // If there's an error checking columns, continue with the base query
            error_log("Error checking table columns: " . $e->getMessage());
        }
        
        // Add filters if provided
        $params = array();
        
        if ($user_id !== null) {
            $query .= " AND d.donor_id = :donor_id";
            $params[':donor_id'] = $user_id;
        }
        
        if ($status !== null) {
            $query .= " AND d.status = :status";
            $params[':status'] = $status;
        }
        
        if ($start_date !== null && $end_date !== null) {
            $query .= " AND DATE(d.created_at) BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $start_date;
            $params[':end_date'] = $end_date;
        }
        
        $query .= " ORDER BY d.created_at DESC";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Execute the query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Verify donation
    public function verify() {
        // Sanitize inputs
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->verified_by = htmlspecialchars(strip_tags($this->verified_by));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        error_log("Verifying donation with values:");
        error_log("- id: " . $this->id);
        error_log("- verified_by: " . $this->verified_by);
        error_log("- status: " . $this->status);
        
        try {
            // First try with verified_at field
            $query = "UPDATE " . $this->table_name . " 
                      SET status = :status, 
                          verified_by = :verified_by, 
                          verified_at = NOW()
                      WHERE id = :id";
            
            error_log("Prepared query: " . $query);
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':verified_by', $this->verified_by);
            $stmt->bindParam(':id', $this->id);
            
            error_log("Executing query...");
            
            // Execute the query
            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                error_log("Query execution failed: " . print_r($error, true));
                throw new PDOException("Query execution failed: " . $error[2]);
            }
            
            error_log("Query executed successfully");
            return true;
        } catch (PDOException $e) {
            // If there's an error related to the verified_at column, try without it
            if (strpos($e->getMessage(), "Unknown column 'verified_at'") !== false) {
                try {
                    // Query without verified_at field
                    $query = "UPDATE " . $this->table_name . " 
                              SET status = :status, 
                                  verified_by = :verified_by
                              WHERE id = :id";
                    
                    error_log("Retrying with simplified query: " . $query);
                    
                    // Prepare the query
                    $stmt = $this->conn->prepare($query);
                    
                    // Bind values
                    $stmt->bindParam(':status', $this->status);
                    $stmt->bindParam(':verified_by', $this->verified_by);
                    $stmt->bindParam(':id', $this->id);
                    
                    error_log("Executing simplified query...");
                    
                    // Execute the query
                    if (!$stmt->execute()) {
                        $error = $stmt->errorInfo();
                        error_log("Simplified query execution failed: " . print_r($error, true));
                        throw new PDOException("Simplified query execution failed: " . $error[2]);
                    }
                    
                    error_log("Simplified query executed successfully");
                    return true;
                } catch (PDOException $e2) {
                    error_log("Error verifying donation: " . $e2->getMessage());
                    error_log("Stack trace: " . $e2->getTraceAsString());
                    return false;
                }
            } else {
                error_log("Error verifying donation: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                return false;
            }
        }
    }
    
    // Get total donations by user
    public function get_user_total($user_id, $year = null) {
        // Debug: Get all donations for this user first
        $debug_query = "SELECT amount, status, created_at, YEAR(created_at) as donation_year 
                       FROM " . $this->table_name . " 
                       WHERE donor_id = :donor_id";
        
        $debug_stmt = $this->conn->prepare($debug_query);
        $debug_stmt->bindParam(':donor_id', $user_id);
        $debug_stmt->execute();
        
        error_log("=== Donations for user $user_id ===");
        while ($debug_row = $debug_stmt->fetch(PDO::FETCH_ASSOC)) {
            error_log("Amount: " . $debug_row['amount'] . 
                     ", Status: " . $debug_row['status'] . 
                     ", Year: " . $debug_row['donation_year'] . 
                     ", Date: " . $debug_row['created_at']);
        }
        error_log("================================");

        // Query to get total donations by user with year and status filters
        $query = "SELECT SUM(amount) as total
                  FROM " . $this->table_name . " 
                  WHERE donor_id = :donor_id
                  AND status = 'verified'";
        
        // Add year filter if specified
        if ($year !== null) {
            $query .= " AND YEAR(created_at) = :year";
        }

        // Debug: Log the query and parameters
        error_log("Donation Query: " . $query);
        error_log("User ID: " . $user_id);
        if ($year !== null) {
            error_log("Year: " . $year);
        }
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind donor_id parameter
        $stmt->bindParam(':donor_id', $user_id);
        
        // Bind year parameter if specified
        if ($year !== null) {
            $stmt->bindParam(':year', $year);
        }
        
        // Execute the query
        $stmt->execute();
        
        // Get total
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Log the result
        error_log("Total amount: " . ($row['total'] ?: 0));
        
        // Calculate monthly average (divide by 12 if year is specified)
        $total = $row['total'] ?: 0;
        $monthly_average = $total / 12;
        
        // Debug: Log the monthly average
        error_log("Monthly average: " . $monthly_average);
        
        return $total;
    }
    
    // Get pending donations that need verification
    public function get_pending_donations() {
        try {
            // First try with payment_proof (actual column name in DB)
            $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.payment_proof, d.status, d.verified_by, d.created_at, 
                       u.full_name as donor_name, u.email
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.donor_id = u.id
                  WHERE d.status = 'pending'
                  ORDER BY d.created_at DESC";
            
            // Try to prepare and execute with payment_proof
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } catch (PDOException $e) {
            // If error with payment_proof, try with receipt_image
            if (strpos($e->getMessage(), "Unknown column 'd.payment_proof'") !== false) {
                try {
                    // Try with receipt_image instead
                    $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.receipt_image, d.status, d.verified_by, d.created_at, 
                             u.full_name as donor_name, u.email
                      FROM " . $this->table_name . " d
                      LEFT JOIN users u ON d.donor_id = u.id
                      WHERE d.status = 'pending'
                      ORDER BY d.created_at DESC";
                    
                    // Prepare and execute
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                } catch (PDOException $e2) {
                    // If still error, fall back to just basic fields
                    $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.status, d.created_at, 
                             u.full_name as donor_name, u.email
                      FROM " . $this->table_name . " d
                      LEFT JOIN users u ON d.donor_id = u.id
                      WHERE d.status = 'pending'
                      ORDER BY d.created_at DESC";
                    
                    // Prepare and execute with minimal fields
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                }
            } else {
                // If there's an error with any other columns, use absolute minimal fields
                $query = "SELECT d.id, d.donor_id, d.amount, d.status, d.created_at, u.full_name as donor_name, u.email
                          FROM " . $this->table_name . " d
                          LEFT JOIN users u ON d.donor_id = u.id 
                          WHERE d.status = 'pending'
                          ORDER BY d.created_at DESC";
                    
                // Prepare the query with minimal fields
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
            }
        }
        
        return $stmt;
    }
    
    // Get verified donations
    public function get_verified_donations() {
        try {
            // First try with payment_proof (actual column name in DB)
            $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.payment_proof, d.status, d.verified_by, d.verified_at, d.created_at, 
                       u.full_name, u.email
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.donor_id = u.id
                  WHERE d.status = 'verified'
                  ORDER BY d.created_at DESC";
            
            // Try to prepare and execute with payment_proof
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } catch (PDOException $e) {
            // If error with payment_proof or verified_at, try alternatives
            if (strpos($e->getMessage(), "Unknown column 'd.payment_proof'") !== false) {
                try {
                    // Try with receipt_image instead
                    $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.receipt_image, d.status, d.verified_by, d.verified_at, d.created_at, 
                             u.full_name, u.email
                      FROM " . $this->table_name . " d
                      LEFT JOIN users u ON d.donor_id = u.id
                      WHERE d.status = 'verified'
                      ORDER BY d.created_at DESC";
                    
                    // Prepare and execute
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                } catch (PDOException $e2) {
                    // If still error, try without receipt_image but with verified_at
                    try {
                        $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.status, d.verified_by, d.verified_at, d.created_at, 
                                 u.full_name, u.email
                          FROM " . $this->table_name . " d
                          LEFT JOIN users u ON d.donor_id = u.id
                          WHERE d.status = 'verified'
                          ORDER BY d.created_at DESC";
                        
                        // Prepare and execute
                        $stmt = $this->conn->prepare($query);
                        $stmt->execute();
                    } catch (PDOException $e3) {
                        // Fall back to just basic fields
                        $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.status, d.created_at, 
                                 u.full_name, u.email
                          FROM " . $this->table_name . " d
                          LEFT JOIN users u ON d.donor_id = u.id
                          WHERE d.status = 'verified'
                          ORDER BY d.created_at DESC";
                        
                        // Prepare and execute with minimal fields
                        $stmt = $this->conn->prepare($query);
                        $stmt->execute();
                    }
                }
            } else if (strpos($e->getMessage(), "Unknown column 'd.verified_at'") !== false) {
                // If verified_at is missing but payment_proof was OK
                $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, d.payment_proof, d.status, d.verified_by, d.created_at, 
                           u.full_name, u.email
                      FROM " . $this->table_name . " d
                      LEFT JOIN users u ON d.donor_id = u.id
                      WHERE d.status = 'verified'
                      ORDER BY d.created_at DESC";
                
                // Prepare and execute
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
            } else {
                // If there's an error with any other columns, use absolute minimal fields
                $query = "SELECT d.id, d.donor_id, d.amount, d.status, d.created_at, u.full_name, u.email
                          FROM " . $this->table_name . " d
                          LEFT JOIN users u ON d.donor_id = u.id 
                          WHERE d.status = 'verified'
                          ORDER BY d.created_at DESC";
                    
                // Prepare the query with minimal fields
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
            }
        }
        
        return $stmt;
    }
    
    // Get donation statistics for reports (simplified)
    public function get_statistics($period = 'monthly') {
        // Simplified statistics query that only uses fields that exist
        $query = "SELECT 
                    COUNT(*) as total_donations,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    MIN(amount) as minimum_amount,
                    MAX(amount) as maximum_amount
                  FROM " . $this->table_name;
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Execute the query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Update donation
    public function update() {
        try {
            // Start with the basic fields that should always be present
            $query = "UPDATE " . $this->table_name . " 
                      SET donor_id = :donor_id,
                          amount = :amount,
                          payment_method = :payment_method,
                          reference_number = :reference_number,
                          status = :status";
            
            // Add payment_proof if it's set
            if (!empty($this->payment_proof)) {
                // Try to determine if the column is payment_proof or receipt_image
                try {
                    $check_query = "SELECT payment_proof FROM " . $this->table_name . " LIMIT 1";
                    $this->conn->query($check_query);
                    // If we get here, payment_proof exists
                    $query .= ", payment_proof = :payment_proof";
                } catch (PDOException $e) {
                    // Try with receipt_image
                    try {
                        $check_query = "SELECT receipt_image FROM " . $this->table_name . " LIMIT 1";
                        $this->conn->query($check_query);
                        // If we get here, receipt_image exists
                        $query .= ", receipt_image = :receipt_image";
                    } catch (PDOException $e2) {
                        // Neither column exists, don't add it to the query
                    }
                }
            }
            
            // If status is changed to verified and verified_by is set, update verification details
            if ($this->status === 'verified') {
                // Check if verified_at column exists
                try {
                    $check_query = "SELECT verified_at FROM " . $this->table_name . " LIMIT 1";
                    $this->conn->query($check_query);
                    // If we get here, verified_at exists
                    $query .= ", verified_at = NOW()";
                } catch (PDOException $e) {
                    // verified_at doesn't exist, don't add it
                }
                
                // Check if verified_by column exists
                try {
                    $check_query = "SELECT verified_by FROM " . $this->table_name . " LIMIT 1";
                    $this->conn->query($check_query);
                    // If we get here, verified_by exists
                    if (!empty($this->verified_by)) {
                        $query .= ", verified_by = :verified_by";
                    }
                } catch (PDOException $e) {
                    // verified_by doesn't exist, don't add it
                }
            }
            
            // Complete the query
            $query .= " WHERE id = :id";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind values
            $this->donor_id = htmlspecialchars(strip_tags($this->donor_id));
            $this->amount = htmlspecialchars(strip_tags($this->amount));
            $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
            $this->reference_number = htmlspecialchars(strip_tags($this->reference_number));
            $this->status = htmlspecialchars(strip_tags($this->status));
            
            $stmt->bindParam(':donor_id', $this->donor_id);
            $stmt->bindParam(':amount', $this->amount);
            $stmt->bindParam(':payment_method', $this->payment_method);
            $stmt->bindParam(':reference_number', $this->reference_number);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':id', $this->id);
            
            // Bind payment_proof if it was added to the query
            if (!empty($this->payment_proof) && strpos($query, 'payment_proof') !== false || strpos($query, 'receipt_image') !== false) {
                $stmt->bindParam(':payment_proof', $this->payment_proof);
            }
            
            // Bind verified_by if it was added to the query
            if (!empty($this->verified_by) && strpos($query, 'verified_by') !== false) {
                $stmt->bindParam(':verified_by', $this->verified_by);
            }
            
            // Execute the query
            if ($stmt->execute()) {
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            // If there's an error, return false
            return false;
        }
    }
    
    // Delete donation
    public function delete() {
        try {
            // Delete query
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Sanitize and bind ID
            $this->id = htmlspecialchars(strip_tags($this->id));
            $stmt->bindParam(':id', $this->id);
            
            // Execute the query
            if ($stmt->execute()) {
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            // If there's an error, return false
            return false;
        }
    }
    
    // Get all donations for a specific user
    public function get_user_donations($user_id) {
        try {
            // First try with all fields including verified_at
            $query = "SELECT d.*, 
                         u.full_name as donor_name,
                         u.email as donor_email,
                         v.full_name as verifier_name,
                         DATE_FORMAT(d.verified_at, '%Y-%m-%d %H:%i:%s') as verified_at_formatted
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.donor_id = u.id
                  LEFT JOIN users v ON d.verified_by = v.id
                  WHERE d.donor_id = :user_id
                  ORDER BY d.created_at DESC";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind the user ID
            $stmt->bindParam(':user_id', $user_id);
            
            // Execute the query
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            // If there's an error with verified_at or other fields, try with minimal fields
            $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method, d.reference_number, 
                            d.status, d.verified_by, d.verified_at, d.created_at,
                            u.full_name as donor_name,
                            u.email as donor_email,
                            v.full_name as verifier_name,
                            DATE_FORMAT(d.verified_at, '%Y-%m-%d %H:%i:%s') as verified_at_formatted
                     FROM " . $this->table_name . " d
                     LEFT JOIN users u ON d.donor_id = u.id
                     LEFT JOIN users v ON d.verified_by = v.id
                     WHERE d.donor_id = :user_id
                     ORDER BY d.created_at DESC";
            
            // Prepare the query
            $stmt = $this->conn->prepare($query);
            
            // Bind the user ID
            $stmt->bindParam(':user_id', $user_id);
            
            // Execute the query
            $stmt->execute();
            
            return $stmt;
        }
    }
    
    // Get total monthly donations for a user
    public function get_user_monthly_total($user_id) {
        // Get the current month and year
        $current_month = date('m');
        $current_year = date('Y');
        
        // Query to get total donations for the current month
        $query = "SELECT COALESCE(SUM(amount), 0) as total
                  FROM " . $this->table_name . " 
                  WHERE donor_id = :donor_id
                  AND status = 'verified'
                  AND MONTH(created_at) = :month
                  AND YEAR(created_at) = :year";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':donor_id', $user_id);
        $stmt->bindParam(':month', $current_month);
        $stmt->bindParam(':year', $current_year);
        
        // Execute the query
        $stmt->execute();
        
        // Get total
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return floatval($row['total']);
    }
}
?>