<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';
require_once '../vendor/autoload.php';

// Check if user is logged in and has appropriate role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('cashier'))) {
    redirect('../auth/login.php');
    exit;
}

// Check if donor ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Donor ID is required';
    redirect('donors.php');
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate models
$user = new User($db);
$donation = new Donation($db);

// Get donor information
$user->id = $_GET['id'];
if (!$user->read_one() || $user->role !== 'donor') {
    $_SESSION['error_message'] = 'Donor not found';
    redirect('donors.php');
    exit;
}

// Get current year for calculations
$current_year = date('Y');

// Calculate donor statistics
$annual_total = $donation->get_user_total($user->id, $current_year);
$monthly_average = $annual_total / 12;

// Determine donor tier
$tier = '';
$tier_color = '';
if ($monthly_average >= GOLD_TIER_MIN) {
    $tier = 'Gold';
    $tier_color = 'rgb(234, 179, 8)';
} elseif ($monthly_average >= SILVER_TIER_MIN) {
    $tier = 'Silver';
    $tier_color = 'rgb(156, 163, 175)';
} elseif ($monthly_average >= BRONZE_TIER_MIN) {
    $tier = 'Bronze';
    $tier_color = 'rgb(180, 83, 9)';
} elseif ($monthly_average >= BLUE_TIER_MIN) {
    $tier = 'Blue';
    $tier_color = 'rgb(59, 130, 246)';
}

// Get donation history
$donations = $donation->get_user_donations($user->id);

// Calculate statistics
$lifetime_total = 0;
$donation_count = 0;
$payment_methods = ['bank_transfer' => 0, 'gcash' => 0];
$monthly_donations = array_fill(0, 12, 0);
$status_counts = ['pending' => 0, 'verified' => 0, 'rejected' => 0];

$filtered_donations = [];
while ($row = $donations->fetch(PDO::FETCH_ASSOC)) {
    $filtered_donations[] = $row;
    $lifetime_total += $row['amount'];
    $donation_count++;
    $payment_methods[$row['payment_method']]++;
    $status_counts[$row['status']]++;
    
    // Update monthly donations array for current year
    if (date('Y', strtotime($row['created_at'])) == $current_year) {
        $month_index = intval(date('n', strtotime($row['created_at']))) - 1;
        $monthly_donations[$month_index] += $row['amount'];
    }
}

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Oblatos Foundation');
$pdf->SetAuthor('Oblatos Foundation');
$pdf->SetTitle('Donor Profile - ' . $user->full_name);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont('courier');

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(true, 15);

// Set image scale factor
$pdf->setImageScale(1.25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 20);

// Title
$pdf->Cell(0, 10, 'Donor Profile', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 5, 'Generated on: ' . date('F j, Y'), 0, 1, 'C');
$pdf->Ln(10);

// Donor Information Section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Personal Information', 0, 1);
$pdf->SetFont('helvetica', '', 12);

// Create donor info table
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(50, 10, 'Full Name:', 1, 0, 'L', true);
$pdf->Cell(125, 10, $user->full_name, 1, 1, 'L');

$pdf->Cell(50, 10, 'Email:', 1, 0, 'L', true);
$pdf->Cell(125, 10, $user->email, 1, 1, 'L');

$pdf->Cell(50, 10, 'Phone Number:', 1, 0, 'L', true);
$pdf->Cell(125, 10, $user->phone_number ?? 'Not provided', 1, 1, 'L');

$pdf->Cell(50, 10, 'Donor Tier:', 1, 0, 'L', true);
$pdf->Cell(125, 10, $tier, 1, 1, 'L');

$pdf->Cell(50, 10, 'Account Status:', 1, 0, 'L', true);
$pdf->Cell(125, 10, ucfirst($user->status), 1, 1, 'L');

$pdf->Ln(10);

// Donation Statistics Section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Donation Statistics', 0, 1);
$pdf->SetFont('helvetica', '', 12);

// Create statistics table
$pdf->Cell(85, 10, 'Monthly Average (' . $current_year . '):', 1, 0, 'L', true);
$pdf->Cell(90, 10, '₱' . number_format($monthly_average, 2), 1, 1, 'L');

$pdf->Cell(85, 10, 'Total Donations (' . $current_year . '):', 1, 0, 'L', true);
$pdf->Cell(90, 10, '₱' . number_format($annual_total, 2), 1, 1, 'L');

$pdf->Cell(85, 10, 'Lifetime Total:', 1, 0, 'L', true);
$pdf->Cell(90, 10, '₱' . number_format($lifetime_total, 2), 1, 1, 'L');

$pdf->Cell(85, 10, 'Total Transactions:', 1, 0, 'L', true);
$pdf->Cell(90, 10, $donation_count, 1, 1, 'L');

$pdf->Ln(10);

// Donation History Section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Donation History', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Create donation history table header
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(30, 7, 'Date', 1, 0, 'C', true);
$pdf->Cell(35, 7, 'Amount', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Payment Method', 1, 0, 'C', true);
$pdf->Cell(50, 7, 'Reference #', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Status', 1, 1, 'C', true);

// Add donation records
foreach ($filtered_donations as $row) {
    $pdf->Cell(30, 7, date('M d, Y', strtotime($row['created_at'])), 1, 0, 'L');
    $pdf->Cell(35, 7, '₱' . number_format($row['amount'], 2), 1, 0, 'R');
    $pdf->Cell(40, 7, $row['payment_method'] === 'bank_transfer' ? 'Bank Transfer' : 'GCash', 1, 0, 'L');
    $pdf->Cell(50, 7, $row['reference_number'], 1, 0, 'L');
    $pdf->Cell(20, 7, ucfirst($row['status']), 1, 1, 'C');
}

// Output the PDF
$pdf_file = '../uploads/reports/donor_profile_' . $user->id . '_' . date('Ymd') . '.pdf';

// Create directory if it doesn't exist
if (!is_dir('../uploads/reports/')) {
    mkdir('../uploads/reports/', 0755, true);
}

// Save PDF to file
$pdf->Output($pdf_file, 'F');

// Force download the file
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($pdf_file) . '"');
header('Content-Length: ' . filesize($pdf_file));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($pdf_file);
unlink($pdf_file); // Delete the file after sending
exit;
?> 