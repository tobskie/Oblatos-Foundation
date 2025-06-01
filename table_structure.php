<?php
// A utility script to show the database table structure
require_once 'config/config.php';
require_once 'config/database.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Display donations table structure
echo "<h1>Donations Table Structure</h1>";
try {
    $query = "DESCRIBE donations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Alternative approach: Show the first row of the donations table
echo "<h1>Donations Table Sample Data</h1>";
try {
    $query = "SELECT * FROM donations LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p>No data in donations table.</p>";

        // If no data, let's at least show the column names from information_schema
        $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donations' ORDER BY ORDINAL_POSITION";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        echo "<h2>Column Names</h2>";
        echo "<ul>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>{$row['COLUMN_NAME']}</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
