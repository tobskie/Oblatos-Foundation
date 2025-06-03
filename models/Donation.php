<?php
class Donation {
    // Database connection and table name
    private $db;
    private $table_name = "donations";
    
    // Object properties
    public $id;
    public $donor_id;
    public $amount;
    public $payment_method;
    public $reference_number;
    public $receipt_number;
    public $payment_proof;
    public $receipt_image;
    public $status;
    public $verified_by;
    public $verified_at;
    public $created_at;
    
    // Additional properties for joined data
    public $donor_name;
    public $donor_email;
    public $verifier_name;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Create new donation
    public function create() {
        try {
            $this->db->beginTransaction();
            
            // Insert into donations table
            $query = "INSERT INTO " . $this->table_name . "
                    (donor_id, amount, payment_method_id, reference_number, created_at)
                    SELECT :donor_id, :amount, pm.id, :reference_number, :created_at
                    FROM payment_methods pm 
                    WHERE pm.name = :payment_method";
            
            $stmt = $this->db->prepare($query);
            
            $this->created_at = date('Y-m-d H:i:s');
            
            $stmt->bindParam(":donor_id", $this->donor_id);
            $stmt->bindParam(":amount", $this->amount);
            $stmt->bindParam(":payment_method", $this->payment_method);
            $stmt->bindParam(":reference_number", $this->reference_number);
            $stmt->bindParam(":created_at", $this->created_at);
            
            $stmt->execute();
            $this->id = $this->db->lastInsertId();
            
            // Insert receipt if provided
            if (!empty($this->receipt_number)) {
                $query = "INSERT INTO donation_receipts
                        (donation_id, receipt_number, payment_proof)
                        VALUES (:donation_id, :receipt_number, :payment_proof)";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":donation_id", $this->id);
                $stmt->bindParam(":receipt_number", $this->receipt_number);
                $stmt->bindParam(":payment_proof", $this->payment_proof);
                $stmt->execute();
            }
            
            // Insert initial status
            $query = "INSERT INTO donation_status_history
                    (donation_id, status_id, changed_by, changed_at)
                    SELECT :donation_id, ds.id, :changed_by, :changed_at
                    FROM donation_statuses ds
                    WHERE ds.name = 'pending'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":donation_id", $this->id);
            $stmt->bindParam(":changed_by", $this->donor_id);
            $stmt->bindParam(":changed_at", $this->created_at);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // Read one donation
    public function read_one() {
        $query = "SELECT d.*, 
                         pm.name as payment_method,
                         dr.receipt_number,
                         dr.payment_proof,
                         ds.name as status,
                         dsh.changed_by as verified_by,
                         dsh.changed_at as verified_at,
                         u.email as donor_email,
                         up.full_name as donor_name,
                         vup.full_name as verifier_name
              FROM " . $this->table_name . " d
                  JOIN payment_methods pm ON d.payment_method_id = pm.id
                  LEFT JOIN donation_receipts dr ON d.id = dr.donation_id
                  LEFT JOIN (
                      SELECT donation_id, MAX(changed_at) as latest_status
                      FROM donation_status_history
                      GROUP BY donation_id
                  ) latest ON d.id = latest.donation_id
                  LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                      AND latest.latest_status = dsh.changed_at
                  LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
              LEFT JOIN users u ON d.donor_id = u.id
                  LEFT JOIN user_profiles up ON u.id = up.user_id
                  LEFT JOIN users vu ON dsh.changed_by = vu.id
                  LEFT JOIN user_profiles vup ON vu.id = vup.user_id
                  WHERE d.id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->donor_id = $row['donor_id'];
            $this->amount = $row['amount'];
            $this->payment_method = $row['payment_method'];
            $this->reference_number = $row['reference_number'];
            $this->receipt_number = $row['receipt_number'];
            $this->payment_proof = $row['payment_proof'];
            $this->status = $row['status'] ?? 'pending';
            $this->verified_by = $row['verified_by'];
            $this->verified_at = $row['verified_at'];
            $this->created_at = $row['created_at'];
            $this->donor_email = $row['donor_email'];
            $this->donor_name = $row['donor_name'];
            $this->verifier_name = $row['verifier_name'];
            return true;
        }
        return false;
    }
    
    // Read all donations with optional filters
    public function read_all($donor_id = null) {
        $query = "SELECT d.*, 
                         pm.name as payment_method,
                         dr.receipt_number,
                         dr.payment_proof,
                         ds.name as status,
                         dsh.changed_by as verified_by,
                         dsh.changed_at as verified_at,
                         up.full_name as donor_name,
                         vup.full_name as verifier_name
                 FROM " . $this->table_name . " d
                  JOIN payment_methods pm ON d.payment_method_id = pm.id
                  LEFT JOIN donation_receipts dr ON d.id = dr.donation_id
                  LEFT JOIN (
                      SELECT donation_id, MAX(changed_at) as latest_status
                      FROM donation_status_history
                      GROUP BY donation_id
                  ) latest ON d.id = latest.donation_id
                  LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                      AND latest.latest_status = dsh.changed_at
                  LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                 LEFT JOIN users u ON d.donor_id = u.id
                  LEFT JOIN user_profiles up ON u.id = up.user_id
                  LEFT JOIN users vu ON dsh.changed_by = vu.id
                  LEFT JOIN user_profiles vup ON vu.id = vup.user_id";
        
        if ($donor_id !== null) {
            $query .= " WHERE d.donor_id = :donor_id";
        }
        
        $query .= " ORDER BY d.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        
        if ($donor_id !== null) {
            $stmt->bindParam(":donor_id", $donor_id);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    // Verify donation
    public function verify() {
        try {
            $this->db->beginTransaction();
            
            // Get the status ID from donation_statuses table
            $status_query = "SELECT id FROM donation_statuses WHERE name = :status_name";
            $status_stmt = $this->db->prepare($status_query);
            $status_stmt->bindParam(":status_name", $this->status);
            $status_stmt->execute();
            
            if ($status_row = $status_stmt->fetch(PDO::FETCH_ASSOC)) {
                $status_id = $status_row['id'];
                
                // Insert into donation_status_history
                $history_query = "INSERT INTO donation_status_history 
                                (donation_id, status_id, changed_by, changed_at)
                                VALUES (:donation_id, :status_id, :changed_by, NOW())";
                
                $history_stmt = $this->db->prepare($history_query);
                $history_stmt->bindParam(":donation_id", $this->id);
                $history_stmt->bindParam(":status_id", $status_id);
                $history_stmt->bindParam(":changed_by", $this->verified_by);
                
                if ($history_stmt->execute()) {
                    $this->db->commit();
                    return true;
                }
            }
            
            $this->db->rollBack();
            return false;
        } catch (PDOException $e) {
            error_log("Error in verify donation: " . $e->getMessage());
            $this->db->rollBack();
            
            // Try legacy update method as fallback
            try {
                return $this->update();
                } catch (PDOException $e2) {
                error_log("Error in verify donation fallback: " . $e2->getMessage());
                return false;
            }
        }
    }
    
    // Get total donations by user
    public function get_user_total($user_id, $year = null) {
        try {
        // Debug: Get all donations for this user first
            $debug_query = "SELECT d.amount, ds.name as status, d.created_at, YEAR(d.created_at) as donation_year 
                           FROM " . $this->table_name . " d
                           LEFT JOIN (
                               SELECT donation_id, MAX(changed_at) as latest_status
                               FROM donation_status_history
                               GROUP BY donation_id
                           ) latest ON d.id = latest.donation_id
                           LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                               AND latest.latest_status = dsh.changed_at
                           LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                           WHERE d.donor_id = :donor_id";
            
            $debug_stmt = $this->db->prepare($debug_query);
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
            $query = "SELECT COALESCE(SUM(d.amount), 0) as total
                     FROM " . $this->table_name . " d
                     LEFT JOIN (
                         SELECT donation_id, MAX(changed_at) as latest_status
                         FROM donation_status_history
                         GROUP BY donation_id
                     ) latest ON d.id = latest.donation_id
                     LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                         AND latest.latest_status = dsh.changed_at
                     LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                     WHERE d.donor_id = :donor_id
                     AND ds.name = 'verified'";
        
        // Add year filter if specified
        if ($year !== null) {
                $query .= " AND YEAR(d.created_at) = :year";
        }

        // Debug: Log the query and parameters
        error_log("Donation Query: " . $query);
        error_log("User ID: " . $user_id);
        if ($year !== null) {
            error_log("Year: " . $year);
        }
        
        // Prepare the query
            $stmt = $this->db->prepare($query);
        
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
        
            return floatval($row['total']);
        } catch (PDOException $e) {
            // Log the error
            error_log("Error in get_user_total: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Try a simpler query that might work with older schema
            try {
                $query = "SELECT COALESCE(SUM(amount), 0) as total
                         FROM " . $this->table_name . " 
                         WHERE donor_id = :donor_id";
                
                if ($year !== null) {
                    $query .= " AND YEAR(created_at) = :year";
                }
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':donor_id', $user_id);
                
                if ($year !== null) {
                    $stmt->bindParam(':year', $year);
                }
                
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return floatval($row['total']);
            } catch (PDOException $e2) {
                error_log("Error in get_user_total fallback: " . $e2->getMessage());
                return 0; // Return 0 if all queries fail
            }
        }
    }
    
    // Get monthly totals for all users or a specific user
    public function get_monthly_totals($user_id = null, $year = null) {
        try {
            // Base query using the normalized schema
            $query = "SELECT MONTH(d.created_at) as month,
                            COALESCE(SUM(d.amount), 0) as total
                     FROM " . $this->table_name . " d
                     LEFT JOIN (
                         SELECT donation_id, MAX(changed_at) as latest_status
                         FROM donation_status_history
                         GROUP BY donation_id
                     ) latest ON d.id = latest.donation_id
                     LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                         AND latest.latest_status = dsh.changed_at
                     LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                     WHERE ds.name = 'verified'";
            
            // Add user filter if provided
            if ($user_id !== null) {
                $query .= " AND d.donor_id = :user_id";
            }
            
            // Add year filter if provided
            if ($year !== null) {
                $query .= " AND YEAR(d.created_at) = :year";
            }
            
            $query .= " GROUP BY MONTH(d.created_at)";
            
            $stmt = $this->db->prepare($query);
            
            // Bind parameters
            if ($user_id !== null) {
                $stmt->bindParam(":user_id", $user_id);
            }
            if ($year !== null) {
                $stmt->bindParam(":year", $year);
            }
            
            $stmt->execute();
            
            // Initialize array with all months set to 0
            $monthly_totals = array_fill(1, 12, 0);
            
            // Fill in actual totals
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $monthly_totals[(int)$row['month']] = (float)$row['total'];
            }
            
            return $monthly_totals;
            
        } catch (PDOException $e) {
            // If there's an error with the normalized schema, try the simple schema
            $query = "SELECT MONTH(created_at) as month,
                            COALESCE(SUM(amount), 0) as total
                     FROM " . $this->table_name . "
                     WHERE status = 'verified'";
            
            if ($user_id !== null) {
                $query .= " AND donor_id = :user_id";
            }
            
            if ($year !== null) {
                $query .= " AND YEAR(created_at) = :year";
            }
            
            $query .= " GROUP BY MONTH(created_at)";
            
            $stmt = $this->db->prepare($query);
            
            if ($user_id !== null) {
                $stmt->bindParam(":user_id", $user_id);
            }
            if ($year !== null) {
                $stmt->bindParam(":year", $year);
            }
            
            $stmt->execute();
            
            $monthly_totals = array_fill(1, 12, 0);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $monthly_totals[(int)$row['month']] = (float)$row['total'];
            }
            
            return $monthly_totals;
        }
    }
    
    // Get yearly totals for a user
    public function get_yearly_totals($user_id) {
        $query = "SELECT 
                    YEAR(created_at) as year,
                    SUM(amount) as total
                  FROM " . $this->table_name . " 
                  WHERE donor_id = :user_id
                  AND status = 'verified'
                  GROUP BY YEAR(created_at)
                  ORDER BY YEAR(created_at)";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get pending donations that need verification
    public function get_pending_donations() {
        try {
            // Query using the normalized schema
            $query = "SELECT d.id, d.donor_id, d.amount, pm.name as payment_method, 
                            d.reference_number, dr.payment_proof, ds.name as status, 
                            d.created_at, up.full_name as donor_name, u.email
                  FROM " . $this->table_name . " d
                     LEFT JOIN payment_methods pm ON d.payment_method_id = pm.id
                     LEFT JOIN donation_receipts dr ON d.id = dr.donation_id
                     LEFT JOIN (
                         SELECT donation_id, MAX(changed_at) as latest_status
                         FROM donation_status_history
                         GROUP BY donation_id
                     ) latest ON d.id = latest.donation_id
                     LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                         AND latest.latest_status = dsh.changed_at
                     LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                  LEFT JOIN users u ON d.donor_id = u.id
                     LEFT JOIN user_profiles up ON u.id = up.user_id
                     WHERE ds.name = 'pending' OR ds.name IS NULL
                  ORDER BY d.created_at DESC";
            
            // Prepare and execute
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in get_pending_donations: " . $e->getMessage());
            
            // Try a simpler query that might work with older schema
            try {
                $query = "SELECT d.id, d.donor_id, d.amount, d.payment_method_id, 
                                d.reference_number, dr.payment_proof, 'pending' as status,
                                d.created_at, up.full_name as donor_name, u.email
                      FROM " . $this->table_name . " d
                         LEFT JOIN donation_receipts dr ON d.id = dr.donation_id
                      LEFT JOIN users u ON d.donor_id = u.id
                         LEFT JOIN user_profiles up ON u.id = up.user_id
                      ORDER BY d.created_at DESC";
                    
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                
                return $stmt;
            } catch (PDOException $e2) {
                error_log("Error in get_pending_donations fallback: " . $e2->getMessage());
                throw $e2;
            }
        }
    }
    
    // Get verified donations
    public function get_verified_donations() {
        try {
            // Query using the normalized schema
            $query = "SELECT 
                        d.id, 
                        d.donor_id, 
                        d.amount, 
                        pm.name as payment_method,
                        d.reference_number, 
                        dr.payment_proof, 
                        ds.name as status,
                        dsh.changed_at as verified_at, 
                        dsh.changed_by as verified_by,
                        d.created_at, 
                        up.full_name,
                        u.email,
                        vp.full_name as verifier_name,
                        dsh.notes
                  FROM " . $this->table_name . " d
                    JOIN payment_methods pm ON d.payment_method_id = pm.id
                    LEFT JOIN donation_receipts dr ON d.id = dr.donation_id
                    LEFT JOIN (
                        SELECT donation_id, MAX(changed_at) as latest_status
                        FROM donation_status_history
                        GROUP BY donation_id
                    ) latest ON d.id = latest.donation_id
                    LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                        AND latest.latest_status = dsh.changed_at
                    LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                  LEFT JOIN users u ON d.donor_id = u.id
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    LEFT JOIN users v ON dsh.changed_by = v.id
                    LEFT JOIN user_profiles vp ON v.id = vp.user_id
                    WHERE ds.name = 'verified'
                    ORDER BY dsh.changed_at DESC
                    LIMIT 30";
            
            // Prepare and execute
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in get_verified_donations: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Get donation statistics for reports (simplified)
    public function get_statistics($period = 'monthly') {
        try {
            // Base query for statistics
            $query = "SELECT 
                        COUNT(*) as total_donations,
                        COALESCE(SUM(amount), 0) as total_amount,
                        COALESCE(AVG(amount), 0) as average_amount,
                        COALESCE(MIN(amount), 0) as minimum_amount,
                        COALESCE(MAX(amount), 0) as maximum_amount
                    FROM " . $this->table_name . " d
                    LEFT JOIN (
                        SELECT donation_id, MAX(changed_at) as latest_status
                        FROM donation_status_history
                        GROUP BY donation_id
                    ) latest ON d.id = latest.donation_id
                    LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                        AND latest.latest_status = dsh.changed_at
                    LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                    WHERE ds.name = 'verified'";

            // Add period filter if specified
            if ($period === 'monthly') {
                $query .= " AND MONTH(d.created_at) = MONTH(CURRENT_DATE())
                           AND YEAR(d.created_at) = YEAR(CURRENT_DATE())";
            } elseif ($period === 'yearly') {
                $query .= " AND YEAR(d.created_at) = YEAR(CURRENT_DATE())";
            }
            
            // Prepare and execute the query
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            // Log the error
            error_log("Error getting donation statistics: " . $e->getMessage());
            
            // Try a simpler query if the complex one fails
        $query = "SELECT 
                    COUNT(*) as total_donations,
                        COALESCE(SUM(amount), 0) as total_amount,
                        COALESCE(AVG(amount), 0) as average_amount,
                        COALESCE(MIN(amount), 0) as minimum_amount,
                        COALESCE(MAX(amount), 0) as maximum_amount
                    FROM " . $this->table_name . "
                    WHERE status = 'verified'";
            
            if ($period === 'monthly') {
                $query .= " AND MONTH(created_at) = MONTH(CURRENT_DATE())
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            } elseif ($period === 'yearly') {
                $query .= " AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            }
            
            $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt;
        }
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
                    $this->db->query($check_query);
                    // If we get here, payment_proof exists
                    $query .= ", payment_proof = :payment_proof";
                } catch (PDOException $e) {
                    // Try with receipt_image
                    try {
                        $check_query = "SELECT receipt_image FROM " . $this->table_name . " LIMIT 1";
                        $this->db->query($check_query);
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
                    $this->db->query($check_query);
                    // If we get here, verified_at exists
                    $query .= ", verified_at = NOW()";
                } catch (PDOException $e) {
                    // verified_at doesn't exist, don't add it
                }
                
                // Check if verified_by column exists
                try {
                    $check_query = "SELECT verified_by FROM " . $this->table_name . " LIMIT 1";
                    $this->db->query($check_query);
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
            $stmt = $this->db->prepare($query);
            
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
            $stmt = $this->db->prepare($query);
            
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
            $stmt = $this->db->prepare($query);
            
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
            $stmt = $this->db->prepare($query);
            
            // Bind the user ID
            $stmt->bindParam(':user_id', $user_id);
            
            // Execute the query
            $stmt->execute();
            
            return $stmt;
        }
    }
    
    // Get total monthly donations for a user
    public function get_user_monthly_total($user_id) {
        try {
        // Get the current month and year
        $current_month = date('m');
        $current_year = date('Y');
        
            // Query using the normalized schema
            $query = "SELECT COALESCE(SUM(d.amount), 0) as total
                     FROM " . $this->table_name . " d
                     LEFT JOIN (
                         SELECT donation_id, MAX(changed_at) as latest_status
                         FROM donation_status_history
                         GROUP BY donation_id
                     ) latest ON d.id = latest.donation_id
                     LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                         AND latest.latest_status = dsh.changed_at
                     LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
                     WHERE d.donor_id = :donor_id
                     AND ds.name = 'verified'
                     AND MONTH(d.created_at) = :month
                     AND YEAR(d.created_at) = :year";
        
        // Prepare the query
            $stmt = $this->db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':donor_id', $user_id);
        $stmt->bindParam(':month', $current_month);
        $stmt->bindParam(':year', $current_year);
        
        // Execute the query
        $stmt->execute();
        
        // Get total
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return floatval($row['total']);
            
        } catch (PDOException $e) {
            error_log("Error in get_user_monthly_total: " . $e->getMessage());
            
            // Try a simpler query that might work with older schema
            try {
                $query = "SELECT COALESCE(SUM(amount), 0) as total
                         FROM " . $this->table_name . " 
                         WHERE donor_id = :donor_id
                         AND MONTH(created_at) = :month
                         AND YEAR(created_at) = :year
                         AND status = 'verified'";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':donor_id', $user_id);
                $stmt->bindParam(':month', $current_month);
                $stmt->bindParam(':year', $current_year);
                $stmt->execute();
                
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return floatval($row['total']);
            } catch (PDOException $e2) {
                error_log("Error in get_user_monthly_total fallback: " . $e2->getMessage());
                return 0; // Return 0 if all queries fail
            }
        }
    }

    public function update_status($donation_id, $status, $changed_by, $notes = '') {
        try {
            $this->db->beginTransaction();
            
            // Get the status ID
            $status_query = "SELECT id FROM donation_statuses WHERE name = :name";
            $status_stmt = $this->db->prepare($status_query);
            $status_stmt->execute(['name' => $status]);
            $status_id = $status_stmt->fetchColumn();
            
            if (!$status_id) {
                throw new Exception("Invalid status: " . $status);
            }
            
            // Insert into status history
            $query = "INSERT INTO donation_status_history 
                    (donation_id, status_id, changed_by, notes)
                    VALUES (:donation_id, :status_id, :changed_by, :notes)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'donation_id' => $donation_id,
                'status_id' => $status_id,
                'changed_by' => $changed_by,
                'notes' => $notes
            ]);
            
            // Get donor ID for notification
            $donor_query = "SELECT donor_id, amount FROM donations WHERE id = :id";
            $donor_stmt = $this->db->prepare($donor_query);
            $donor_stmt->execute(['id' => $donation_id]);
            $donation = $donor_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($donation) {
                // Create notification for the donor
                require_once 'Notification.php';
                $notification = new Notification($this->db);
                $notification->user_id = $donation['donor_id'];
                
                // Format amount with peso sign and 2 decimal places
                $amount = '₱' . number_format($donation['amount'], 2);
                
                if ($status === 'verified') {
                    $notification->title = 'Donation Verified';
                    $notification->message = "Your donation of {$amount} has been verified. Thank you for your generosity!";
                    $notification->type = 'success';
                } elseif ($status === 'rejected') {
                    $notification->title = 'Donation Rejected';
                    $notification->message = "Your donation of {$amount} has been rejected. Reason: {$notes}";
                    $notification->type = 'error';
                }
                
                if (isset($notification->title)) {
                    $notification->create();
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error updating donation status: " . $e->getMessage());
            return false;
        }
    }
}
?>