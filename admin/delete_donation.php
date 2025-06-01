<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Donation.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['donation_id']) || empty($_POST['donation_id'])) {
    $_SESSION['error_message'] = "Invalid request. Donation ID is required.";
    redirect('admin/donations.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate donation model
$donation = new Donation($db);
$donation->id = $_POST['donation_id'];

// Check if donation exists
if (!$donation->read_one()) {
    $_SESSION['error_message'] = "Donation not found.";
    redirect('admin/donations.php');
}

// Add delete method to Donation model if it doesn't exist
if (!method_exists($donation, 'delete')) {
    // Define the delete method directly here
    try {
        $query = "DELETE FROM donations WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $donation->id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Donation deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete donation.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
} else {
    // Use the existing delete method
    if ($donation->delete()) {
        $_SESSION['success_message'] = "Donation deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete donation.";
    }
}

// Redirect back to donations page
redirect('admin/donations.php');
?>
