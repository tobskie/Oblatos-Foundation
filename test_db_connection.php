<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test if we can query the database
        $stmt = $db->query("SELECT DATABASE()");
        $current_db = $stmt->fetchColumn();
        echo "<p>Connected to database: " . htmlspecialchars($current_db) . "</p>";
        
        // Check if required tables exist
        $required_tables = [
            'users',
            'user_profiles',
            'roles',
            'user_roles',
            'donations',
            'donation_receipts',
            'donation_statuses',
            'donation_status_history',
            'payment_methods'
        ];
        
        echo "<h2>Checking Required Tables:</h2>";
        echo "<ul>";
        
        foreach ($required_tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            $exists = $stmt->rowCount() > 0;
            
            if ($exists) {
                echo "<li style='color: green;'>✓ Table '{$table}' exists</li>";
                
                // Get table structure
                $stmt = $db->query("DESCRIBE `{$table}`");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<ul>";
                foreach ($columns as $column) {
                    echo "<li style='font-family: monospace; font-size: 0.9em;'>" . 
                         htmlspecialchars($column['Field']) . " - " . 
                         htmlspecialchars($column['Type']) . 
                         ($column['Key'] === 'PRI' ? ' (Primary Key)' : '') . 
                         "</li>";
                }
                echo "</ul>";
            } else {
                echo "<li style='color: red;'>✗ Table '{$table}' is missing</li>";
            }
        }
        
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} 