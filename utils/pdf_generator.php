<?php
/**
 * PDF Generator for Oblatos Foundation
 * Handles PDF report generation using TCPDF library
 */

// Include TCPDF library - we'll install this via Composer
require_once '../vendor/autoload.php';

/**
 * Generates a PDF donation report
 * 
 * @param array $donations Array of donation records
 * @param array $summary Summary statistics of donations
 * @param array $filters Applied filters (year, month, status)
 * @return string Path to the generated PDF file
 */
function generate_donation_report_pdf($donations, $summary, $filters) {
    // Create new PDF document
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Oblatos Foundation');
    $pdf->SetAuthor('Oblatos Foundation');
    $pdf->SetTitle('Donation Report');
    $pdf->SetSubject('Donation Report');
    $pdf->SetKeywords('Donation, Report, Oblatos, Foundation');
    
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
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Generate report title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Oblatos Foundation by OSJ', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, 'Donation Report', 0, 1, 'C');
    
    // Report filters
    $pdf->SetFont('helvetica', 'I', 10);
    $filterText = 'Year: ' . $filters['year'];
    if ($filters['month'] !== 'all') {
        $months = [
            '01' => 'January', '02' => 'February', '03' => 'March', 
            '04' => 'April', '05' => 'May', '06' => 'June', 
            '07' => 'July', '08' => 'August', '09' => 'September', 
            '10' => 'October', '11' => 'November', '12' => 'December'
        ];
        $filterText .= ' | Month: ' . $months[$filters['month']];
    }
    
    if ($filters['status'] !== 'all') {
        $filterText .= ' | Status: ' . ucfirst($filters['status']);
    }
    
    $pdf->Cell(0, 6, $filterText, 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y'), 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // Summary section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Summary', 0, 1);
    
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', '', 10);
    
    // Summary table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(45, 7, 'Total Donations:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(45, 7, '₱' . number_format($summary['total_amount'], 2), 1, 1, 'R');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(45, 7, 'Transactions:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(45, 7, $summary['transaction_count'], 1, 1, 'R');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(45, 7, 'Verified Amount:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(45, 7, '₱' . number_format($summary['verified_amount'], 2), 1, 1, 'R');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(45, 7, 'Pending Amount:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(45, 7, '₱' . number_format($summary['pending_amount'], 2), 1, 1, 'R');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(45, 7, 'Rejected Amount:', 1, 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(45, 7, '₱' . number_format($summary['rejected_amount'], 2), 1, 1, 'R');
    
    $pdf->Ln(5);
    
    // Donations table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Donation List', 0, 1);
    
    // Table header
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(25, 7, 'Date', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Donor', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Amount', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Payment Method', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Reference #', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Status', 1, 1, 'C', true);
    
    // Table content
    $pdf->SetFont('helvetica', '', 9);
    $fill = false;
    
    if (count($donations) > 0) {
        foreach ($donations as $row) {
            $status_text = ucfirst($row['status']);
            
            $pdf->Cell(25, 6, date('M d, Y', strtotime($row['created_at'])), 1, 0, 'L', $fill);
            $pdf->Cell(50, 6, $row['donor_name'], 1, 0, 'L', $fill);
            $pdf->Cell(25, 6, '₱' . number_format($row['amount'], 2), 1, 0, 'R', $fill);
            
            $payment_method = $row['payment_method'] === 'bank_transfer' ? 'Bank Transfer' : 'GCash';
            $pdf->Cell(30, 6, $payment_method, 1, 0, 'L', $fill);
            
            $pdf->Cell(35, 6, $row['reference_number'], 1, 0, 'L', $fill);
            $pdf->Cell(20, 6, $status_text, 1, 1, 'C', $fill);
            
            $fill = !$fill; // Alternate row colors
        }
    } else {
        $pdf->Cell(185, 7, 'No donations found for the selected filters.', 1, 1, 'C');
    }
    
    // Close and output PDF document
    $pdf_directory = '../uploads/reports/';
    
    // Create directory if it doesn't exist
    if (!is_dir($pdf_directory)) {
        mkdir($pdf_directory, 0755, true);
    }
    
    // Generate unique filename
    $filename = 'donation_report_' . $filters['year'];
    if ($filters['month'] !== 'all') {
        $filename .= '_' . $filters['month'];
    }
    if ($filters['status'] !== 'all') {
        $filename .= '_' . $filters['status'];
    }
    $filename .= '_' . date('Ymd_His') . '.pdf';
    
    $filepath = $pdf_directory . $filename;
    
    // Save PDF to file
    $pdf->Output($filepath, 'F');
    
    return $filepath;
}
