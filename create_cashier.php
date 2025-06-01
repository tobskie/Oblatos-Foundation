<?php
// Script to create a cashier account
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';

// Set cashier credentials
$cashier_username = 'cashier';
$cashier_full_name = 'System Cashier';
$cashier_email = 'cashier@example.com';
$cashier_password = 'cashier123'; // Change this to a secure password!
$cashier_role = 'cashier';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if cashier already exists
$query = "SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $cashier_email);
$stmt->bindParam(2, $cashier_username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "<p>Cashier account already exists. Please use the existing account to log in.</p>";
    echo "<p>If you've forgotten your password, please contact the system administrator.</p>";
} else {
    // Create user object
    $user = new User($db);
    
    // Set user properties
    $user->username = $cashier_username;
    $user->full_name = $cashier_full_name;
    $user->email = $cashier_email;
    $user->password = $cashier_password;
    $user->role = $cashier_role;
    
    // Create user
    if ($user->create()) {
        echo "<p>Cashier account created successfully!</p>";
        echo "<p>Username: {$cashier_username}</p>";
        echo "<p>Email: {$cashier_email}</p>";
        echo "<p>Password: (As set in the script)</p>";
        echo "<p><a href='auth/login.php'>Go to login page</a></p>";
        echo "<p><strong>IMPORTANT:</strong> Delete this file after creating the cashier account for security reasons!</p>";
    } else {
        echo "<p>Failed to create cashier account. Please try again or check the database connection.</p>";
    }
}
?> 