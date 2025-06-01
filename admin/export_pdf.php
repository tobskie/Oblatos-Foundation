<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * PDF Report Export for Oblatos Foundation (Admin Access)
 * Creates a dedicated printable/savable page that users can convert to PDF using browser functionality
 * Supports filtering by year, month, and status
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../auth/login.php');
    exit;
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get filter parameters
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? $_GET['month'] : 'all';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Instantiate models
    $donation = new Donation($db);
    $user = new User($db);

    // Build the query based on filters
    $query = "SELECT d.*, 
                     u.full_name as donor_name, u.email as donor_email,
                     v.full_name as verifier_name,
                     CASE 
                         WHEN d.status = 'verified' THEN COALESCE(d.verification_date, d.date_verified, d.created_at)
                         ELSE NULL
                     END as effective_verification_date,
                     CASE
                         WHEN d.status = 'verified' THEN 'verified'
                         ELSE 'pending'
                     END as effective_status
              FROM donations d
              LEFT JOIN users u ON d.donor_id = u.id
              LEFT JOIN users v ON d.verified_by = v.id
              WHERE 1=1";

    $params = array();

    // Add year filter
    if ($year) {
        $query .= " AND YEAR(d.created_at) = :year";
        $params[':year'] = $year;
    }

    // Add month filter if not 'all'
    if ($month !== 'all') {
        $query .= " AND MONTH(d.created_at) = :month";
        $params[':month'] = $month;
    }

    // Add status filter if not 'all'
    if ($status !== 'all') {
        $query .= " AND d.status = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY d.created_at DESC";

    // Prepare and execute the query
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    // Initialize statistics
    $total_amount = 0;
    $transaction_count = 0;
    $payment_methods = [
        'bank_transfer' => 0,
        'gcash' => 0
    ];
    $filtered_donations = [];

    // Process results
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $transaction_count++;
        $total_amount += floatval($row['amount']);
        
        // Count payment methods (case-sensitive)
        if (isset($row['payment_method'])) {
            $method = strtolower($row['payment_method']);
            if (isset($payment_methods[$method])) {
                $payment_methods[$method]++;
            }
        }

        // Debug information
        error_log("Donation ID: " . $row['id']);
        error_log("Status: " . $row['status']);
        error_log("Effective Status: " . $row['effective_status']);
        error_log("Verified By: " . ($row['verified_by'] ?? 'null'));
        error_log("Verification Date: " . ($row['verification_date'] ?? 'null'));
        error_log("Effective Verification Date: " . ($row['effective_verification_date'] ?? 'null'));
        error_log("Created At: " . ($row['created_at'] ?? 'null'));
        error_log("-------------------");

        // Format verification date and status
        $row['status'] = $row['effective_status'];
        if ($row['status'] === 'verified') {
            $row['verification_date'] = $row['effective_verification_date'];
        } else {
            $row['verification_date'] = null;
        }
        
        $filtered_donations[] = $row;
    }

    // Create report title with filters
    $title_parts = [];
    $title_parts[] = 'Donations Report';
    
    if ($status !== 'all') {
        $title_parts[] = ucfirst($status);
    }
    
    if ($month !== 'all') {
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        $title_parts[] = $month_name;
    }
    
    $title_parts[] = $year;
    $report_title = implode(' - ', $title_parts);

    // Create PDF-ready filename
    $filename = 'oblatos_donations_' . $year;
    if ($month !== 'all') $filename .= '_' . str_pad($month, 2, '0', STR_PAD_LEFT);
    if ($status !== 'all') $filename .= '_' . $status;
    $filename .= '.pdf';

    // Start output buffering to catch any unwanted output
    ob_start();
    
    // Include the HTML template
    include 'templates/export_pdf_template.php';
    
    // End output buffering and send the content
    ob_end_flush();

} catch (Exception $e) {
    // Log the error
    error_log("Error in admin/export_pdf.php: " . $e->getMessage());
    
    // Display a user-friendly error message
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Export PDF</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                padding: 20px;
                max-width: 800px;
                margin: 0 auto;
            }
            .error-container {
                background-color: #fee2e2;
                border: 1px solid #ef4444;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            .error-title {
                color: #dc2626;
                margin: 0 0 10px 0;
            }
            .error-message {
                color: #7f1d1d;
                margin: 0;
            }
            .back-button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4b5563;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1 class="error-title">Error Generating Report</h1>
            <p class="error-message">We encountered an error while generating your report. Please try again later or contact support if the problem persists.</p>
            <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
            <p class="error-message"><strong>Debug Info:</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
            <?php endif; ?>
        </div>
        <a href="dashboard.php" class="back-button">Back to Dashboard</a>
    </body>
    </html>
    <?php
}
?>
