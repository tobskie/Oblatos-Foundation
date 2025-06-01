<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * PDF Report Export for Oblatos Foundation (Cashier Access)
 * Creates a dedicated printable/savable page that users can convert to PDF using browser functionality
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';

// Check if user is logged in and is a cashier
if (!isLoggedIn() || !hasRole('cashier')) {
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

    // Instantiate models
    $donation = new Donation($db);
    $user = new User($db);

    // Get verified donations
    $verified_donations = $donation->get_verified_donations();
    if (!$verified_donations) {
        throw new Exception("Failed to fetch verified donations");
    }

    // Calculate summary statistics
    $total_amount = 0;
    $transaction_count = 0;
    $payment_methods = [
        'bank_transfer' => 0,
        'gcash' => 0
    ];

    $filtered_donations = [];

    // Create a cache for user data to avoid multiple database queries
    $user_cache = [];

    while ($row = $verified_donations->fetch(PDO::FETCH_ASSOC)) {
        $transaction_count++;
        $total_amount += floatval($row['amount']);
        
        // Count payment methods (case-sensitive)
        if (isset($row['payment_method'])) {
            $method = strtolower($row['payment_method']);
            if (isset($payment_methods[$method])) {
                $payment_methods[$method]++;
            }
        }
        
        // Get donor information from cache or database
        if (isset($row['donor_id']) && !empty($row['donor_id'])) {
            if (!isset($user_cache['donor_' . $row['donor_id']])) {
                $donor = new User($db);
                $donor->id = $row['donor_id'];
                if ($donor->read_one()) {
                    $user_cache['donor_' . $row['donor_id']] = [
                        'name' => $donor->full_name,
                        'email' => $donor->email
                    ];
                } else {
                    $user_cache['donor_' . $row['donor_id']] = [
                        'name' => 'Unknown Donor',
                        'email' => 'N/A'
                    ];
                }
            }
            $row['donor_name'] = $user_cache['donor_' . $row['donor_id']]['name'];
            $row['donor_email'] = $user_cache['donor_' . $row['donor_id']]['email'];
        } else {
            $row['donor_name'] = 'Unknown Donor';
            $row['donor_email'] = 'N/A';
        }
        
        // Get verifier information from cache or database
        if (!empty($row['verified_by'])) {
            if (!isset($user_cache['verifier_' . $row['verified_by']])) {
                $verifier = new User($db);
                $verifier->id = $row['verified_by'];
                if ($verifier->read_one()) {
                    $user_cache['verifier_' . $row['verified_by']] = $verifier->full_name;
                } else {
                    $user_cache['verifier_' . $row['verified_by']] = 'N/A';
                }
            }
            $row['verifier_name'] = $user_cache['verifier_' . $row['verified_by']];
        } else {
            $row['verifier_name'] = 'N/A';
        }
        
        $filtered_donations[] = $row;
    }

    // Current date info
    $year = date('Y');
    $month = date('F');

    // Create report title
    $report_title = 'Verified Donations Report - ' . $month . ' ' . $year;

    // Create PDF-ready filename
    $filename = 'oblatos_verified_donations_' . $year . '_' . date('m') . '.pdf';

    // Start output buffering to catch any unwanted output
    ob_start();
    
    // Include the HTML template
    include 'templates/export_pdf_template.php';
    
    // End output buffering and send the content
    ob_end_flush();

} catch (Exception $e) {
    // Log the error
    error_log("Error in export_pdf.php: " . $e->getMessage());
    
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
