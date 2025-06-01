<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "Running Database Migrations...\n";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/create_tables.sql');
    
    // Split SQL file into individual statements
    $statements = array_filter(
        array_map(
            'trim',
            explode(';', $sql)
        )
    );
    
    // Begin transaction
    $db->beginTransaction();
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            // Replace DELIMITER commands for triggers
            if (stripos($statement, 'DELIMITER') === false) {
                $db->exec($statement);
                echo "Executed statement successfully.\n";
            }
        }
    }
    
    // Create default admin user if not exists
    require_once __DIR__ . '/../models/User.php';
    if (User::create_default_admin($db)) {
        echo "Default admin user exists or was created successfully.\n";
    } else {
        echo "Failed to create default admin user.\n";
    }
    
    // Commit transaction
    $db->commit();
    echo "All migrations completed successfully!\n";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    
    // Additional error details
    echo "\nError Details:\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} 