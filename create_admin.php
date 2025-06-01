<?php
// Script to create an admin account
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';

// Set admin credentials - CHANGE THESE!
$admin_username = 'admin';
$admin_full_name = 'System Administrator';
$admin_email = 'admin@example.com';
$admin_password = 'admin123'; // Change this to a secure password!
$admin_role = 'admin';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if admin already exists
$query = "SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $admin_email);
$stmt->bindParam(2, $admin_username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "<p>Admin account already exists. Please use the existing account to log in.</p>";
    echo "<p>If you've forgotten your password, please contact the system administrator.</p>";
} else {
    // Create user object
    $user = new User($db);
    
    // Set user properties
    $user->username = $admin_username;
    $user->full_name = $admin_full_name;
    $user->email = $admin_email;
    $user->password = $admin_password;
    $user->role = $admin_role;
    
    // Create user
    if ($user->create()) {
        echo "<p>Admin account created successfully!</p>";
        echo "<p>Username: {$admin_username}</p>";
        echo "<p>Email: {$admin_email}</p>";
        echo "<p>Password: (As set in the script)</p>";
        echo "<p><a href='auth/login.php'>Go to login page</a></p>";
        echo "<p><strong>IMPORTANT:</strong> Delete this file after creating the admin account for security reasons!</p>";
    } else {
        echo "<p>Failed to create admin account. Please try again or check the database connection.</p>";
    }
}
?>
