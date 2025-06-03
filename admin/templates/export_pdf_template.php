<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($report_title); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .report-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .report-header p {
            margin: 5px 0;
            color: #666;
        }
        .summary-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f5f5f5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-box h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 14px;
            color: #666;
        }
        .stat-box p {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-box {
            flex: 1;
            min-width: 300px;
            max-width: 500px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            page-break-inside: avoid;
            margin: 0 auto;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .chart-box h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            text-align: center;
        }
        .chart-canvas {
            height: 300px;
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-verified {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-rejected {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .no-print {
            margin-bottom: 20px;
        }
        .error-message {
            color: #dc2626;
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
            .chart-box {
                max-width: none;
                width: 100%;
                height: auto;
            }
            .chart-wrapper {
                height: 400px;
            }
            .error-message {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print();" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
            Print / Save as PDF
        </button>
        <p>
            <i>Instructions: Click the button above to print this report or save it as a PDF. In the print dialog, select "Save as PDF" as the destination to create a PDF file.</i>
        </p>
        <hr>
    </div>
    
    <div class="report-header">
        <h1>Oblatos Foundation by OSJ</h1>
        <p><?php echo htmlspecialchars($report_title); ?></p>
        <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
        <p>Generated by: <?php echo htmlspecialchars($current_user_name); ?></p>
    </div>
    
    <div class="summary-stats">
        <div class="stat-box">
            <h3>Total Amount</h3>
            <p><?php echo formatPeso($total_amount); ?></p>
        </div>
        <div class="stat-box">
            <h3>Number of Transactions</h3>
            <p><?php echo $transaction_count; ?></p>
        </div>
        <div class="stat-box">
            <h3>Bank Transfers</h3>
            <p><?php echo $payment_methods['bank_transfer']; ?></p>
        </div>
        <div class="stat-box">
            <h3>GCash Payments</h3>
            <p><?php echo $payment_methods['gcash']; ?></p>
        </div>
    </div>

    <div class="chart-container">
        <div class="chart-box">
            <h3>Payment Methods Distribution</h3>
            <div id="chartError" class="error-message">
                Unable to load chart. The data is still available in the table below.
            </div>
            <div class="chart-wrapper">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>
    </div>
    
    <h2>Donations List</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Donor</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Reference #</th>
                <th>Status</th>
                <th>Verified By</th>
                <th>Verification Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($filtered_donations)): ?>
            <tr>
                <td colspan="8" style="text-align: center;">No donations found for the selected period.</td>
            </tr>
            <?php else: ?>
                <?php foreach ($filtered_donations as $donation): ?>
                <tr>
                    <td><?php echo formatDate($donation['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($donation['donor_name'] ?? 'N/A'); ?></td>
                    <td><?php echo formatPeso($donation['amount']); ?></td>
                    <td><?php echo formatPaymentMethod($donation['payment_method'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($donation['reference_number'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="status-<?php echo $donation['status']; ?>">
                            <?php echo ucfirst($donation['status']); ?>
                        </span>
                    </td>
                    <td><?php echo $donation['status'] === 'verified' ? htmlspecialchars($donation['verifier_name'] ?? 'N/A') : '-'; ?></td>
                    <td><?php echo $donation['verification_date'] ? formatDate($donation['verification_date']) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
        try {
            // Configure and render the payment methods chart
            const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
            new Chart(paymentMethodsCtx, {
                type: 'pie',
                data: {
                    labels: ['Bank Transfer', 'GCash'],
                    datasets: [{
                        data: [
                            <?php echo $payment_methods['bank_transfer']; ?>,
                            <?php echo $payment_methods['gcash']; ?>
                        ],
                        backgroundColor: [
                            '#4f46e5',
                            '#10b981'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error rendering chart:', error);
            document.getElementById('chartError').style.display = 'block';
        }
    </script>
</body>
</html> 