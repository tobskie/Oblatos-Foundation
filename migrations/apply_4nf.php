<?php
require_once '../config/config.php';
require_once '../config/database.php';

class Migration4NF {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function migrate() {
        try {
            // Disable foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            // Apply new schema
            $this->applyNewSchema();
            
            // Start transaction for data migration
            $this->db->beginTransaction();
            
            // Migrate data if old tables exist
            if ($this->oldTablesExist()) {
                $this->migrateData();
            } else {
                echo "No old tables found. Creating fresh schema.\n";
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Re-enable foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            echo "Migration completed successfully!\n";
            return true;
        } catch (Exception $e) {
            // Only rollback if there's an active transaction
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            // Re-enable foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            echo "Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function oldTablesExist() {
        $tables = ['users_old', 'donations_old', 'donors_old'];
        foreach ($tables as $table) {
            $stmt = $this->db->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                return true;
            }
        }
        return false;
    }
    
    private function applyNewSchema() {
        // Read and execute the 4NF schema SQL
        $sql = file_get_contents(__DIR__ . '/database_4nf.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        // Skip the RENAME TABLE statements since we already have _old tables
        $statements = array_filter($statements, function($stmt) {
            return !str_starts_with(trim($stmt), 'RENAME TABLE');
        });
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->db->exec($statement);
            }
        }
    }
    
    private function migrateData() {
        // Check each table before migrating
        if ($this->tableExists('users_old')) {
            $this->migrateUsers();
            $this->migrateRoles();
        }
        
        if ($this->tableExists('donations_old')) {
            $this->migrateDonations();
        }
        
        if ($this->tableExists('donors_old')) {
            $this->migrateDonorProfiles();
        }
    }
    
    private function tableExists($table) {
        $stmt = $this->db->query("SHOW TABLES LIKE '{$table}'");
        return $stmt->rowCount() > 0;
    }
    
    private function migrateUsers() {
        $stmt = $this->db->query("SELECT * FROM users_old");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Insert into users table
            $query = "INSERT INTO users (id, uuid, username, email, password, created_at) 
                     VALUES (:id, :uuid, :username, :email, :password, :created_at)";
            $this->db->prepare($query)->execute([
                'id' => $row['id'],
                'uuid' => $row['uuid'] ?? bin2hex(random_bytes(16)),
                'username' => $row['username'],
                'email' => $row['email'],
                'password' => $row['password'],
                'created_at' => $row['created_at']
            ]);
            
            // Insert into user_profiles
            $query = "INSERT INTO user_profiles (user_id, full_name, display_name, status) 
                     VALUES (:user_id, :full_name, :display_name, :status)";
            $this->db->prepare($query)->execute([
                'user_id' => $row['id'],
                'full_name' => $row['full_name'],
                'display_name' => $row['name'],
                'status' => $row['status']
            ]);
            
            // Handle password reset if exists
            if (!empty($row['reset_token'])) {
                $query = "INSERT INTO password_resets (user_id, token, expires_at, created_at)
                         VALUES (:user_id, :token, :expires_at, :created_at)";
                $this->db->prepare($query)->execute([
                    'user_id' => $row['id'],
                    'token' => $row['reset_token'],
                    'expires_at' => $row['reset_token_expiry'] ?? date('Y-m-d H:i:s', strtotime('+24 hours')),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
    
    private function migrateRoles() {
        $stmt = $this->db->query("SELECT * FROM users_old");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roleQuery = "SELECT id FROM roles WHERE name = :role";
            $roleStmt = $this->db->prepare($roleQuery);
            $roleStmt->execute(['role' => $row['role']]);
            $roleId = $roleStmt->fetchColumn();
            
            if ($roleId) {
                $query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
                $this->db->prepare($query)->execute([
                    'user_id' => $row['id'],
                    'role_id' => $roleId
                ]);
            }
        }
    }
    
    private function migrateDonations() {
        $stmt = $this->db->query("SELECT * FROM donations_old");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get payment method ID
            $methodQuery = "SELECT id FROM payment_methods WHERE name = :name";
            $methodStmt = $this->db->prepare($methodQuery);
            $methodStmt->execute(['name' => $row['payment_method']]);
            $methodId = $methodStmt->fetchColumn();
            
            // Insert donation
            $query = "INSERT INTO donations (id, donor_id, amount, payment_method_id, reference_number, created_at)
                     VALUES (:id, :donor_id, :amount, :payment_method_id, :reference_number, :created_at)";
            $this->db->prepare($query)->execute([
                'id' => $row['id'],
                'donor_id' => $row['donor_id'],
                'amount' => $row['amount'],
                'payment_method_id' => $methodId,
                'reference_number' => $row['reference_number'],
                'created_at' => $row['created_at']
            ]);
            
            // Insert receipt if exists
            if ($row['receipt_number']) {
                $query = "INSERT INTO donation_receipts (donation_id, receipt_number, payment_proof)
                         VALUES (:donation_id, :receipt_number, :payment_proof)";
                $this->db->prepare($query)->execute([
                    'donation_id' => $row['id'],
                    'receipt_number' => $row['receipt_number'],
                    'payment_proof' => $row['payment_proof']
                ]);
            }
            
            // Insert status history
            $statusQuery = "SELECT id FROM donation_statuses WHERE name = :name";
            $statusStmt = $this->db->prepare($statusQuery);
            $statusStmt->execute(['name' => $row['status']]);
            $statusId = $statusStmt->fetchColumn();
            
            $query = "INSERT INTO donation_status_history (donation_id, status_id, changed_by, changed_at)
                     VALUES (:donation_id, :status_id, :changed_by, :changed_at)";
            $this->db->prepare($query)->execute([
                'donation_id' => $row['id'],
                'status_id' => $statusId,
                'changed_by' => $row['verified_by'] ?? $row['donor_id'],
                'changed_at' => $row['verified_at'] ?? $row['created_at']
            ]);
        }
    }
    
    private function migrateDonorProfiles() {
        $stmt = $this->db->query("SELECT * FROM donors_old");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Get tier ID
            $tierQuery = "SELECT id FROM donor_tiers WHERE name = :name";
            $tierStmt = $this->db->prepare($tierQuery);
            $tierStmt->execute(['name' => $row['tier']]);
            $tierId = $tierStmt->fetchColumn();
            
            $query = "INSERT INTO donor_profiles (user_id, tier_id, total_donations, last_donation_date)
                     VALUES (:user_id, :tier_id, :total_donations, :last_donation_date)";
            $this->db->prepare($query)->execute([
                'user_id' => $row['user_id'],
                'tier_id' => $tierId,
                'total_donations' => $row['total_donations'],
                'last_donation_date' => $row['last_donation_date']
            ]);
        }
    }
}

// Run migration
$database = new Database();
$db = $database->getConnection();

$migration = new Migration4NF($db);
$migration->migrate(); 