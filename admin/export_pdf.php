<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * PDF Report Export for Oblatos Foundation (Admin Access)
 * Creates a dedicated printable/savable page that users can convert to PDF using browser functionality
 * Supports filtering by year, month, and status
 */

session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';
require_once '../utils/format_helpers.php';

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

    // Get current user's name for the report
    $current_user = new User($db);
    $current_user->id = $_SESSION['user_id'];
    $current_user->read_one();
    $current_user_name = $current_user->full_name ?? $_SESSION['username'];

    // Instantiate models
    $donation = new Donation($db);

    // Build the query based on filters using the normalized schema
    $query = "SELECT d.*, 
                     pm.name as payment_method,
                     dr.receipt_number,
                     ds.name as status,
                     dsh.changed_by as verified_by,
                     dsh.changed_at as verification_date,
                     u.email as donor_email,
                     up.full_name as donor_name,
                     vup.full_name as verifier_name
              FROM donations d
              LEFT JOIN payment_methods pm ON d.payment_method_id = pm.id
              LEFT JOIN donation_receipts dr ON d.id = dr.donation_id
              LEFT JOIN (
                  SELECT donation_id, MAX(changed_at) as latest_status
                  FROM donation_status_history
                  GROUP BY donation_id
              ) latest ON d.id = latest.donation_id
              LEFT JOIN donation_status_history dsh ON latest.donation_id = dsh.donation_id 
                  AND latest.latest_status = dsh.changed_at
              LEFT JOIN donation_statuses ds ON dsh.status_id = ds.id
              LEFT JOIN users u ON d.donor_id = u.id
              LEFT JOIN user_profiles up ON u.id = up.user_id
              LEFT JOIN users vu ON dsh.changed_by = vu.id
              LEFT JOIN user_profiles vup ON vu.id = vup.user_id
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
        $query .= " AND ds.name = :status";
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
        
        // Count payment methods
        $method = strtolower($row['payment_method'] ?? 'unknown');
        if (isset($payment_methods[$method])) {
            $payment_methods[$method]++;
        }

        // Set default status if null
        $row['status'] = $row['status'] ?? 'pending';
        
        // Format dates
        $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        if (!empty($row['verification_date'])) {
            $row['verification_date'] = date('Y-m-d H:i:s', strtotime($row['verification_date']));
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
    // Log the error with more details
    error_log("Error in admin/export_pdf.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
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
            .back-button:hover {
                background-color: #374151;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1 class="error-title">Error Generating Report</h1>
            <p class="error-message">We encountered an error while generating your report. Please try again later or contact support if the problem persists.</p>
            <?php if (isLoggedIn() && hasRole('admin')): ?>
            <p class="error-message" style="margin-top: 10px; font-family: monospace; font-size: 12px;">
                Error details: <?php echo htmlspecialchars($e->getMessage()); ?>
            </p>
            <?php endif; ?>
        </div>
        <a href="dashboard.php" class="back-button">Back to Dashboard</a>
    </body>
    </html>
    <?php
}
?>
