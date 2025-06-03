<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/logger.php';

class Migration4NF {
    private $db;
    private $logger;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->logger = new Logger('4nf_migration');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function migrate() {
        try {
            // Step 1: Create new tables
            $this->createNewTables();
            
            // Begin transaction for data migration
            $this->db->beginTransaction();
            
            // Step 2: Migrate user data
            $this->migrateUsers();
            
            // Step 3: Migrate roles
            $this->migrateRoles();
            
            // Step 4: Migrate donor data
            $this->migrateDonors();
            
            // Step 5: Migrate donations
            $this->migrateDonations();

            $this->db->commit();
            $this->logger->info("Migration completed successfully");
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger->error("Migration failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function createNewTables() {
        // Drop existing tables if they exist
        $tables = [
            'donation_status_history',
            'donation_statuses',
            'donation_receipts',
            'donations',
            'payment_methods',
            'donor_profiles',
            'donor_tiers',
            'password_resets',
            'user_roles',
            'roles',
            'user_profiles',
            'users'
        ];

        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS `$table`");
        }

        $sql = file_get_contents(__DIR__ . '/database_4nf.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->db->exec($statement);
            }
        }
    }

    private function migrateUsers() {
        $this->logger->info("Migrating users...");
        $users = $this->db->query("SELECT * FROM users_old")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            // Insert into new users table
            $stmt = $this->db->prepare("
                INSERT INTO users (id, uuid, username, email, password, created_at)
                VALUES (:id, :uuid, :username, :email, :password, :created_at)
            ");
            $stmt->execute([
                'id' => $user['id'],
                'uuid' => $user['uuid'] ?? uniqid('usr_', true),
                'username' => $user['username'],
                'email' => $user['email'],
                'password' => $user['password'],
                'created_at' => $user['created_at']
            ]);

            // Insert into user_profiles
            $stmt = $this->db->prepare("
                INSERT INTO user_profiles (user_id, full_name, display_name, status)
                VALUES (:user_id, :full_name, :display_name, :status)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'full_name' => $user['full_name'],
                'display_name' => $user['name'] ?? $user['full_name'],
                'status' => $user['status'] ?? 'active'
            ]);

            // Handle password reset if exists
            if (!empty($user['reset_token'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (:user_id, :token, :expires_at)
                ");
                $stmt->execute([
                    'user_id' => $user['id'],
                    'token' => $user['reset_token'],
                    'expires_at' => $user['reset_token_expiry'] ?? date('Y-m-d H:i:s', strtotime('+24 hours'))
                ]);
            }
        }
    }

    private function migrateRoles() {
        $this->logger->info("Migrating roles...");
        $users = $this->db->query("SELECT id, role FROM users_old")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            $stmt = $this->db->prepare("
                INSERT INTO user_roles (user_id, role_id)
                SELECT :user_id, id FROM roles WHERE name = :role_name
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'role_name' => $user['role']
            ]);
        }
    }

    private function migrateDonors() {
        $this->logger->info("Migrating donors...");
        $donors = $this->db->query("SELECT * FROM donors_old")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($donors as $donor) {
            $stmt = $this->db->prepare("
                INSERT INTO donor_profiles (user_id, tier_id, total_donations, last_donation_date)
                SELECT :user_id, id, :total_donations, :last_donation_date 
                FROM donor_tiers WHERE name = :tier_name
            ");
            $stmt->execute([
                'user_id' => $donor['user_id'],
                'total_donations' => $donor['total_donations'] ?? 0,
                'last_donation_date' => $donor['last_donation_date'],
                'tier_name' => $donor['tier'] ?? 'blue'
            ]);
        }
    }

    private function migrateDonations() {
        $this->logger->info("Migrating donations...");
        $donations = $this->db->query("SELECT * FROM donations_old")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($donations as $donation) {
            try {
                // Insert into donations table
                $stmt = $this->db->prepare("
                    INSERT INTO donations (id, donor_id, amount, payment_method_id, reference_number, created_at)
                    SELECT :id, :donor_id, :amount, pm.id, :reference_number, :created_at
                    FROM payment_methods pm WHERE pm.name = :payment_method
                ");
                $stmt->execute([
                    'id' => $donation['id'],
                    'donor_id' => $donation['donor_id'],
                    'amount' => $donation['amount'],
                    'reference_number' => $donation['reference_number'],
                    'created_at' => $donation['created_at'],
                    'payment_method' => $donation['payment_method']
                ]);

                // Insert receipt if exists
                if (!empty($donation['receipt_number'])) {
                    $stmt = $this->db->prepare("
                        INSERT INTO donation_receipts (donation_id, receipt_number, payment_proof)
                        VALUES (:donation_id, :receipt_number, :payment_proof)
                    ");
                    $stmt->execute([
                        'donation_id' => $donation['id'],
                        'receipt_number' => $donation['receipt_number'],
                        'payment_proof' => $donation['payment_proof']
                    ]);
                }

                // Insert status history
                $stmt = $this->db->prepare("
                    INSERT INTO donation_status_history (donation_id, status_id, changed_by, changed_at)
                    SELECT :donation_id, ds.id, :changed_by, :changed_at
                    FROM donation_statuses ds WHERE ds.name = :status
                ");
                $stmt->execute([
                    'donation_id' => $donation['id'],
                    'changed_by' => $donation['verified_by'] ?? 1,
                    'changed_at' => $donation['verified_at'] ?? $donation['created_at'],
                    'status' => $donation['status']
                ]);
            } catch (Exception $e) {
                $this->logger->error("Failed to migrate donation {$donation['id']}: " . $e->getMessage());
                throw $e;
            }
        }
    }

    public function rollback() {
        try {
            $this->db->beginTransaction();

            // Drop new tables in reverse order
            $tables = [
                'donation_status_history',
                'donation_statuses',
                'donation_receipts',
                'donations',
                'payment_methods',
                'donor_profiles',
                'donor_tiers',
                'password_resets',
                'user_roles',
                'roles',
                'user_profiles',
                'users'
            ];

            foreach ($tables as $table) {
                $this->db->exec("DROP TABLE IF EXISTS `$table`");
            }

            $this->db->commit();
            $this->logger->info("Rollback completed successfully");
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger->error("Rollback failed: " . $e->getMessage());
            throw $e;
        }
    }
}

// Execute migration
if (php_sapi_name() === 'cli') {
    $migration = new Migration4NF();
    
    if (isset($argv[1]) && $argv[1] === '--rollback') {
        $migration->rollback();
    } else {
        $migration->migrate();
    }
} 