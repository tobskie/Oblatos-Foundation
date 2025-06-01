<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Check if user is logged in and redirect to appropriate dashboard
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'cashier':
            redirect('cashier/dashboard.php');
            break;
        case 'donor':
            redirect('donor/dashboard.php');
            break;
    }
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Load header
include 'includes/header.php';
?>

<!-- Main content -->
<div class="container">
    <h1>Welcome to <?php echo APP_NAME; ?></h1>
    <!-- Add your main content here -->
</div>

<?php
// Load footer
include 'includes/footer.php';
?>