<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Database Structure Check</h1>";

// Check tables
$tables = ['users', 'donors', 'donations'];
foreach ($tables as $table) {
    $query = "SHOW TABLES LIKE '$table'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<h2>Table: $table</h2>";
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Table exists</p>";
        
        // Show table structure
        $structureQuery = "DESCRIBE $table";
        $structureStmt = $db->prepare($structureQuery);
        $structureStmt->execute();
        
        echo "<h3>Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $structureStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Show foreign keys
        $foreignKeysQuery = "
            SELECT 
                COLUMN_NAME, 
                CONSTRAINT_NAME, 
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = DATABASE() AND
                TABLE_NAME = '$table' AND
                REFERENCED_TABLE_NAME IS NOT NULL
        ";
        
        $foreignKeysStmt = $db->prepare($foreignKeysQuery);
        $foreignKeysStmt->execute();
        
        if ($foreignKeysStmt->rowCount() > 0) {
            echo "<h3>Foreign Keys:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Column</th><th>Constraint Name</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
            
            while ($row = $foreignKeysStmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['COLUMN_NAME'] . "</td>";
                echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
                echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
                echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No foreign keys defined for this table.</p>";
        }
        
        // Count records
        $countQuery = "SELECT COUNT(*) FROM $table";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute();
        $count = $countStmt->fetchColumn();
        
        echo "<p>Records: $count</p>";
        
        // Sample data (first 5 rows)
        if ($count > 0) {
            $sampleQuery = "SELECT * FROM $table LIMIT 5";
            $sampleStmt = $db->prepare($sampleQuery);
            $sampleStmt->execute();
            $rows = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                echo "<h3>Sample Data:</h3>";
                echo "<table border='1' cellpadding='5'>";
                
                // Headers
                echo "<tr>";
                foreach (array_keys($rows[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                
                // Data
                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        }
    } else {
        echo "<p>❌ Table does not exist</p>";
    }
    
    echo "<hr>";
}
?>
