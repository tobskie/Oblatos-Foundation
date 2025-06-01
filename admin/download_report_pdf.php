<?php
/**
 * PDF Report Download Handler for Oblatos Foundation
 * This script processes report filters and generates a downloadable PDF report
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';
require_once '../utils/pdf_generator.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('cashier'))) {
    redirect('../auth/login.php');
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate models
$donation = new Donation($db);

// Get filter parameters
$year_filter = $_GET['year'] ?? date('Y');
$month_filter = $_GET['month'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Prepare date filters
$start_date = null;
$end_date = null;

if ($month_filter !== 'all') {
    $start_date = $year_filter . '-' . $month_filter . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
} else {
    $start_date = $year_filter . '-01-01';
    $end_date = $year_filter . '-12-31';
}

// Get filtered donations
$donations = $donation->read_all(null, $status_filter !== 'all' ? $status_filter : null, $start_date, $end_date);

// Calculate summary statistics
$total_amount = 0;
$verified_amount = 0;
$pending_amount = 0;
$rejected_amount = 0;
$transaction_count = 0;
$payment_methods = ['bank_transfer' => 0, 'gcash' => 0];

$filtered_donations = [];

while ($row = $donations->fetch(PDO::FETCH_ASSOC)) {
    $transaction_count++;
    $amount = $row['amount'];
    
    switch ($row['status']) {
        case 'verified':
            $verified_amount += $amount;
            $total_amount += $amount;
            break;
        case 'pending':
            $pending_amount += $amount;
            break;
        case 'rejected':
            $rejected_amount += $amount;
            break;
    }
    
    $payment_methods[$row['payment_method']]++;
    
    $filtered_donations[] = $row;
}

// Prepare summary for PDF
$summary = [
    'total_amount' => $total_amount,
    'transaction_count' => $transaction_count,
    'verified_amount' => $verified_amount,
    'pending_amount' => $pending_amount,
    'rejected_amount' => $rejected_amount,
    'payment_methods' => $payment_methods
];

// Prepare filters for PDF
$filters = [
    'year' => $year_filter,
    'month' => $month_filter,
    'status' => $status_filter
];

// Generate PDF file
$pdf_file = generate_donation_report_pdf($filtered_donations, $summary, $filters);

// Check if file was generated successfully
if (file_exists($pdf_file)) {
    // Force download the file
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($pdf_file) . '"');
    header('Content-Length: ' . filesize($pdf_file));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($pdf_file);
    exit;
} else {
    // Set error message and redirect back to reports page
    $_SESSION['error_message'] = 'Failed to generate PDF report. Please try again.';
    redirect('reports.php');
}
?>
