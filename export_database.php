<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Get database configuration
$database = new Database();
$db = $database->getConnection();

// Get database name
$dbname = "oblatos_foundation";

// Set the output file name
$filename = "database_backup_" . date("Y-m-d_H-i-s") . ".sql";

// Start output buffering
ob_start();

// Add database creation
echo "-- Create and use database\n";
echo "CREATE DATABASE IF NOT EXISTS `{$dbname}`;\n";
echo "USE `{$dbname}`;\n\n";

// Get all tables
$tables_query = "SHOW TABLES";
$tables_stmt = $db->prepare($tables_query);
$tables_stmt->execute();
$tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    // Get create table syntax
    $create_query = "SHOW CREATE TABLE `{$table}`";
    $create_stmt = $db->prepare($create_query);
    $create_stmt->execute();
    $create_row = $create_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n-- Table structure for table `{$table}`\n";
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    echo $create_row['Create Table'] . ";\n\n";
    
    // Get table data
    $data_query = "SELECT * FROM `{$table}`";
    $data_stmt = $db->prepare($data_query);
    $data_stmt->execute();
    $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "-- Dumping data for table `{$table}`\n";
        
        // Get column names
        $columns = array_keys($rows[0]);
        $column_list = "`" . implode("`, `", $columns) . "`";
        
        // Create insert statements
        foreach ($rows as $row) {
            $values = array();
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = $db->quote($value);
                }
            }
            echo "INSERT INTO `{$table}` ({$column_list}) VALUES (" . implode(", ", $values) . ");\n";
        }
        echo "\n";
    }
}

// Get the buffer contents
$sql = ob_get_clean();

// Write to file
file_put_contents($filename, $sql);

echo "Database backup has been created successfully: {$filename}\n";
?> 