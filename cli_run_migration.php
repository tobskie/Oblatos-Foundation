<?php
// CLI script to run the migration to add UUID to users table
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "Running Migration: Add UUID to Users\n";
echo "This script will add a UUID field to the users table and generate UUIDs for existing users.\n";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if the uuid column already exists
$checkColumnQuery = "SHOW COLUMNS FROM users LIKE 'uuid'";
$checkColumnStmt = $db->prepare($checkColumnQuery);
$checkColumnStmt->execute();

if ($checkColumnStmt->rowCount() == 0) {
    // Add uuid column to users table
    $alterTableQuery = "ALTER TABLE users ADD COLUMN uuid VARCHAR(36) AFTER id";
    $alterTableStmt = $db->prepare($alterTableQuery);
    
    if ($alterTableStmt->execute()) {
        echo "Added uuid column to users table.\n";
        
        // Generate UUIDs for existing users
        $selectUsersQuery = "SELECT id FROM users";
        $selectUsersStmt = $db->prepare($selectUsersQuery);
        $selectUsersStmt->execute();
        
        $updateCount = 0;
        
        while ($user = $selectUsersStmt->fetch(PDO::FETCH_ASSOC)) {
            // Generate a UUID v4
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            // Update user with UUID
            $updateUserQuery = "UPDATE users SET uuid = :uuid WHERE id = :id";
            $updateUserStmt = $db->prepare($updateUserQuery);
            $updateUserStmt->bindParam(':uuid', $uuid);
            $updateUserStmt->bindParam(':id', $user['id']);
            
            if ($updateUserStmt->execute()) {
                $updateCount++;
            }
        }
        
        echo "Updated $updateCount existing users with UUIDs.\n";
        
        // Add unique constraint to uuid column
        $addUniqueQuery = "ALTER TABLE users ADD UNIQUE (uuid)";
        $addUniqueStmt = $db->prepare($addUniqueQuery);
        
        if ($addUniqueStmt->execute()) {
            echo "Added unique constraint to uuid column.\n";
        } else {
            echo "Failed to add unique constraint to uuid column.\n";
        }
    } else {
        echo "Failed to add uuid column to users table.\n";
    }
} else {
    echo "UUID column already exists in users table.\n";
}

// Check if the status column exists
$checkStatusQuery = "SHOW COLUMNS FROM users LIKE 'status'";
$checkStatusStmt = $db->prepare($checkStatusQuery);
$checkStatusStmt->execute();

if ($checkStatusStmt->rowCount() == 0) {
    // Add status column to users table
    $addStatusQuery = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER role";
    $addStatusStmt = $db->prepare($addStatusQuery);
    
    if ($addStatusStmt->execute()) {
        echo "Added status column to users table.\n";
    } else {
        echo "Failed to add status column to users table.\n";
    }
} else {
    echo "Status column already exists in users table.\n";
}

echo "Migration completed.\n";
?>
