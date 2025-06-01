<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Invalid request: Donor ID is required';
    redirect('admin/donors.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate User object
$user = new User($db);
$user->id = $_GET['id'];

// Toggle user status
if ($user->toggle_status()) {
    $_SESSION['success_message'] = 'Donor status updated successfully';
} else {
    $_SESSION['error_message'] = 'Failed to update donor status';
}

// Redirect back to donors page
redirect('admin/donors.php'); 