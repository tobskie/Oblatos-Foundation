<?php
// Migration to fix the foreign key constraint issue between donations and users tables
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "<h1>Fixing Donation Foreign Key Constraint</h1>";
echo "<p>This script will update the database structure to fix the foreign key constraint issue.</p>";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Step 1: Check if donors table exists
    $checkDonorsTable = "SHOW TABLES LIKE 'donors'";
    $donorsTableStmt = $db->prepare($checkDonorsTable);
    $donorsTableStmt->execute();
    
    $donorsTableExists = $donorsTableStmt->rowCount() > 0;
    
    if ($donorsTableExists) {
        echo "<p>Found donors table. Checking structure...</p>";
        
        // Check if donors table has records
        $checkDonorsRecords = "SELECT COUNT(*) FROM donors";
        $donorsRecordsStmt = $db->prepare($checkDonorsRecords);
        $donorsRecordsStmt->execute();
        $donorsCount = $donorsRecordsStmt->fetchColumn();
        
        echo "<p>Found $donorsCount records in donors table.</p>";
        
        if ($donorsCount > 0) {
            // Option 1: If donors table has data, we need to preserve it
            echo "<p>Creating a mapping between users and donors...</p>";
            
            // Create a temporary mapping table
            $createMappingTable = "CREATE TEMPORARY TABLE donor_user_mapping (
                donor_id INT,
                user_id INT
            )";
            $db->exec($createMappingTable);
            
            // Get all users with role='donor'
            $getUsersQuery = "SELECT id, email FROM users WHERE role = 'donor'";
            $usersStmt = $db->prepare($getUsersQuery);
            $usersStmt->execute();
            $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get all donors
            $getDonorsQuery = "SELECT id, email FROM donors";
            $donorsStmt = $db->prepare($getDonorsQuery);
            $donorsStmt->execute();
            $donors = $donorsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create mapping based on email
            $insertMapping = "INSERT INTO donor_user_mapping (donor_id, user_id) VALUES (?, ?)";
            $mappingStmt = $db->prepare($insertMapping);
            
            $mappedCount = 0;
            foreach ($donors as $donor) {
                foreach ($users as $user) {
                    if ($donor['email'] === $user['email']) {
                        $mappingStmt->execute([$donor['id'], $user['id']]);
                        $mappedCount++;
                        break;
                    }
                }
            }
            
            echo "<p>Mapped $mappedCount donors to users.</p>";
            
            // Update donations to use user_id instead of donor_id
            echo "<p>Updating donations to reference users table...</p>";
            
            // First drop the foreign key constraint
            $showKeysQuery = "SHOW CREATE TABLE donations";
            $showKeysStmt = $db->prepare($showKeysQuery);
            $showKeysStmt->execute();
            $tableInfo = $showKeysStmt->fetch(PDO::FETCH_ASSOC);
            
            // Extract the constraint name
            $createTable = $tableInfo['Create Table'] ?? '';
            preg_match('/CONSTRAINT `(donations_ibfk_\d+)` FOREIGN KEY \(`donor_id`\) REFERENCES `donors`/', $createTable, $matches);
            
            if (!empty($matches[1])) {
                $constraintName = $matches[1];
                echo "<p>Found foreign key constraint: $constraintName</p>";
                
                // Drop the constraint
                $dropConstraint = "ALTER TABLE donations DROP FOREIGN KEY $constraintName";
                $db->exec($dropConstraint);
                echo "<p>Dropped foreign key constraint.</p>";
            } else {
                echo "<p>Could not find foreign key constraint name. Attempting to drop all foreign keys on donations table.</p>";
                
                // Alternative approach: drop all foreign keys
                $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                $db->exec("ALTER TABLE donations DROP FOREIGN KEY IF EXISTS `donations_ibfk_1`");
                $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            }
            
            // Update donations table to reference user_id
            $updateDonations = "UPDATE donations d 
                               JOIN donor_user_mapping m ON d.donor_id = m.donor_id 
                               SET d.donor_id = m.user_id";
            $db->exec($updateDonations);
            
            echo "<p>Updated donation references.</p>";
            
            // Add new foreign key constraint to users table
            $addConstraint = "ALTER TABLE donations 
                             ADD CONSTRAINT donations_user_fk 
                             FOREIGN KEY (donor_id) REFERENCES users(id) 
                             ON DELETE CASCADE";
            $db->exec($addConstraint);
            
            echo "<p>Added new foreign key constraint to users table.</p>";
            
        } else {
            // Option 2: If donors table is empty, we can simply drop it and update the constraint
            echo "<p>Donors table is empty. Dropping foreign key constraint...</p>";
            
            // Drop the foreign key constraint
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("ALTER TABLE donations DROP FOREIGN KEY IF EXISTS `donations_ibfk_1`");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Add new foreign key constraint to users table
            $addConstraint = "ALTER TABLE donations 
                             ADD CONSTRAINT donations_user_fk 
                             FOREIGN KEY (donor_id) REFERENCES users(id) 
                             ON DELETE CASCADE";
            $db->exec($addConstraint);
            
            echo "<p>Added new foreign key constraint to users table.</p>";
        }
    } else {
        // Option 3: If donors table doesn't exist, we need to check and update the donations table
        echo "<p>Donors table does not exist. Checking donations table structure...</p>";
        
        // Check if donations table has a foreign key constraint
        $showKeysQuery = "SHOW CREATE TABLE donations";
        $showKeysStmt = $db->prepare($showKeysQuery);
        $showKeysStmt->execute();
        $tableInfo = $showKeysStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if there's a constraint referencing donors table
        $createTable = $tableInfo['Create Table'] ?? '';
        if (strpos($createTable, 'REFERENCES `donors`') !== false) {
            echo "<p>Found reference to donors table in constraint. Dropping constraint...</p>";
            
            // Drop all foreign keys
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("ALTER TABLE donations DROP FOREIGN KEY IF EXISTS `donations_ibfk_1`");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Add new foreign key constraint to users table
            $addConstraint = "ALTER TABLE donations 
                             ADD CONSTRAINT donations_user_fk 
                             FOREIGN KEY (donor_id) REFERENCES users(id) 
                             ON DELETE CASCADE";
            $db->exec($addConstraint);
            
            echo "<p>Added new foreign key constraint to users table.</p>";
        } else {
            echo "<p>No reference to donors table found in constraints. Adding constraint to users table...</p>";
            
            // Add foreign key constraint to users table
            $addConstraint = "ALTER TABLE donations 
                             ADD CONSTRAINT donations_user_fk 
                             FOREIGN KEY (donor_id) REFERENCES users(id) 
                             ON DELETE CASCADE";
            $db->exec($addConstraint);
            
            echo "<p>Added foreign key constraint to users table.</p>";
        }
    }
    
    // Commit transaction
    $db->commit();
    echo "<p style='color: green; font-weight: bold;'>Migration completed successfully!</p>";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $db->rollBack();
    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Transaction rolled back.</p>";
    
    // Provide more detailed error information
    echo "<h2>Detailed Error Information</h2>";
    echo "<pre>";
    print_r($e);
    echo "</pre>";
    
    // Suggest manual fix
    echo "<h2>Manual Fix</h2>";
    echo "<p>You can try to manually fix the issue with these SQL commands:</p>";
    echo "<pre>
    -- Disable foreign key checks
    SET FOREIGN_KEY_CHECKS = 0;
    
    -- Drop the existing foreign key constraint
    ALTER TABLE donations DROP FOREIGN KEY donations_ibfk_1;
    
    -- Add new foreign key constraint to users table
    ALTER TABLE donations 
    ADD CONSTRAINT donations_user_fk 
    FOREIGN KEY (donor_id) REFERENCES users(id) 
    ON DELETE CASCADE;
    
    -- Re-enable foreign key checks
    SET FOREIGN_KEY_CHECKS = 1;
    </pre>";
}

echo "<p><a href='../index.php'>Return to homepage</a></p>";
?>
