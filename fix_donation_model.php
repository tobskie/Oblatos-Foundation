<?php
// This script will update the Donation model to match the database structure
require_once 'config/config.php';
require_once 'config/database.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Get the table structure of donations table
$query = "DESCRIBE donations";
$stmt = $db->prepare($query);
$stmt->execute();

// Create output to help diagnose the issue
echo "<h2>Donations Table Structure</h2>";
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

echo "<p>To fix the error in the Donation model, you need to update the field names in the model to match your database structure.</p>";
echo "<p>The most common issue is that the 'user_id' field might be named differently in your database, such as 'donor_id' or 'userId'.</p>";
echo "<p>Please check the table structure above and update the Donation.php model accordingly.</p>";
?>
