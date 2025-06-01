<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate models
$donation = new Donation($db);

// Get current date info
$current_year = date('Y');
$current_month = date('m');

// Set default filters
$year_filter = $_GET['year'] ?? $current_year;
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

// Initialize monthly donations array
$monthly_donations = array_fill(0, 12, 0);

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
$months_data = [];
$monthly_counts = [];

while ($row = $donations->fetch(PDO::FETCH_ASSOC)) {
    $transaction_count++;
    $amount = $row['amount'];
    
    // Update monthly donations array
    $donation_month = intval(date('n', strtotime($row['created_at']))) - 1; // 0-based index
    $monthly_donations[$donation_month] += $amount;
    
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
    
    // Update months data for trend chart
    $month = date('M Y', strtotime($row['created_at']));
    if (!isset($months_data[$month])) {
        $months_data[$month] = 0;
        $monthly_counts[$month] = 0;
    }
    $months_data[$month] += $amount;
    $monthly_counts[$month]++;
    
    $filtered_donations[] = $row;
}

// Calculate donor tiers
$donor_tiers = ['Blue' => 0, 'Bronze' => 0, 'Silver' => 0, 'Gold' => 0];
$user = new User($db);
$donors = $user->read_all('donor');

if ($donors) {
    while ($donor = $donors->fetch(PDO::FETCH_ASSOC)) {
        // Get monthly average for the current year
        $annual_total = $donation->get_user_total($donor['id'], $current_year);
        $monthly_average = $annual_total / 12;
        
        // Determine tier based on monthly average
        if ($monthly_average >= GOLD_TIER_MIN) {
            $donor_tiers['Gold']++;
        } elseif ($monthly_average >= SILVER_TIER_MIN) {
            $donor_tiers['Silver']++;
        } elseif ($monthly_average >= BRONZE_TIER_MIN) {
            $donor_tiers['Bronze']++;
        } elseif ($monthly_average >= BLUE_TIER_MIN) {
            $donor_tiers['Blue']++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<main class="flex-1 overflow-y-auto p-5">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Donation Reports</h1>
        <div class="flex items-center space-x-2">
            <button onclick="printReport()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Report
            </button>
            <a href="export_pdf.php?year=<?php echo $year_filter; ?>&month=<?php echo $month_filter; ?>&status=<?php echo $status_filter; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition duration-150 ease-in-out" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                Export as PDF
            </a>
            <button onclick="exportCSV()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="p-5">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Filter Reports</h2>
            <form action="reports.php" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="w-full md:w-auto">
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <select id="year" name="year" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <?php 
                        for ($year = $current_year; $year >= $current_year - 5; $year--) {
                            $selected = $year == $year_filter ? 'selected' : '';
                            echo "<option value=\"$year\" $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                    <select id="month" name="month" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <option value="all" <?php echo $month_filter === 'all' ? 'selected' : ''; ?>>All Months</option>
                        <?php 
                        $months = [
                            '01' => 'January',
                            '02' => 'February',
                            '03' => 'March',
                            '04' => 'April',
                            '05' => 'May',
                            '06' => 'June',
                            '07' => 'July',
                            '08' => 'August',
                            '09' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December'
                        ];
                        
                        foreach ($months as $value => $label) {
                            $selected = $value === $month_filter ? 'selected' : '';
                            echo "<option value=\"$value\" $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="w-full md:w-auto">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Header -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8" id="printable-report">
        <div class="p-5 border-b">
            <div class="text-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Oblatos Foundation by OSJ</h2>
                <p class="text-gray-600">Donation Report</p>
                <p class="text-gray-600">
                    <?php
                    if ($month_filter !== 'all') {
                        echo $months[$month_filter] . ' ' . $year_filter;
                    } else {
                        echo 'Year ' . $year_filter;
                    }
                    
                    if ($status_filter !== 'all') {
                        echo ' - ' . ucfirst($status_filter) . ' Donations';
                    }
                    ?>
                </p>
            </div>
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Total Donations</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900"><?php echo formatPeso($total_amount); ?></p>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Transactions</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900"><?php echo $transaction_count; ?></p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900"><?php echo formatPeso($pending_amount); ?></p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <p class="text-sm font-medium text-gray-500">Rejected</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900"><?php echo formatPeso($rejected_amount); ?></p>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Payment Methods Chart -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Payment Methods Distribution</h3>
                        <div style="height: 300px;">
                            <canvas id="paymentMethodsChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Status Distribution Chart -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Donation Status Distribution</h3>
                        <div style="height: 300px;">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Trend Chart -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Monthly Donation Trend</h3>
                        <div style="height: 300px;">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Average Donation Chart -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Average Donation by Month</h3>
                        <canvas id="avgDonationChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Donations Chart -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="p-5 border-b">
                    <h2 class="text-lg font-semibold text-gray-700">Monthly Donations (<?php echo $year_filter; ?>)</h2>
                </div>
                <div class="p-5">
                    <div style="height: 300px;">
                        <canvas id="monthlyDonationsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Donor Tier Distribution -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="p-5 border-b">
                    <h2 class="text-lg font-semibold text-gray-700">Donor Tier Distribution</h2>
                </div>
                <div class="p-5">
                    <div style="height: 300px;">
                        <canvas id="donorTierChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Donation List -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Donation List</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Donor
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payment Method
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Reference #
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            if (count($filtered_donations) > 0) {
                                foreach ($filtered_donations as $row) {
                                    $status_color = 'gray';
                                    $status_text = 'Unknown';
                                    
                                    switch ($row['status']) {
                                        case 'pending':
                                            $status_color = 'yellow';
                                            $status_text = 'Pending';
                                            break;
                                        case 'verified':
                                            $status_color = 'green';
                                            $status_text = 'Verified';
                                            break;
                                        case 'rejected':
                                            $status_color = 'red';
                                            $status_text = 'Rejected';
                                            break;
                                    }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php 
                                        // Check if donor_name exists, otherwise try to get user information
                                        if (isset($row['donor_name'])) {
                                            echo htmlspecialchars($row['donor_name']);
                                        } else if (isset($row['donor_id'])) {
                                            // Try to get user information if donor_id is available
                                            $donor_user = new User($db);
                                            $donor_user->id = $row['donor_id'];
                                            if ($donor_user->read_one()) {
                                                echo htmlspecialchars($donor_user->full_name);
                                            } else {
                                                echo 'Unknown Donor';
                                            }
                                        } else {
                                            echo 'Unknown Donor';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo formatPeso($row['amount']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    echo $row['payment_method'] === 'bank_transfer' ? 'Bank Transfer' : 'GCash';
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $row['reference_number']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                            ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No donations found for the selected filters.
                                </td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // Common chart options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };

    try {
        // Payment Methods Chart
        const paymentMethodsCtx = document.getElementById('paymentMethodsChart');
        if (!paymentMethodsCtx) {
            console.error('Payment Methods Chart canvas not found');
        } else {
            console.log('Payment Methods data:', <?php echo json_encode($payment_methods); ?>);
            new Chart(paymentMethodsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Bank Transfer', 'GCash'],
                    datasets: [{
                        data: [<?php echo $payment_methods['bank_transfer']; ?>, <?php echo $payment_methods['gcash']; ?>],
                        backgroundColor: ['rgba(59, 130, 246, 0.5)', 'rgba(16, 185, 129, 0.5)'],
                        borderColor: ['rgb(59, 130, 246)', 'rgb(16, 185, 129)'],
                        borderWidth: 1
                    }]
                },
                options: commonOptions
            });
        }

        // Status Distribution Chart
        const statusDistributionCtx = document.getElementById('statusDistributionChart');
        if (!statusDistributionCtx) {
            console.error('Status Distribution Chart canvas not found');
        } else {
            console.log('Status Distribution data:', {
                verified: <?php echo $verified_amount; ?>,
                pending: <?php echo $pending_amount; ?>,
                rejected: <?php echo $rejected_amount; ?>
            });
            new Chart(statusDistributionCtx, {
                type: 'pie',
                data: {
                    labels: ['Verified', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [
                            <?php echo $verified_amount; ?>,
                            <?php echo $pending_amount; ?>,
                            <?php echo $rejected_amount; ?>
                        ],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.5)',
                            'rgba(245, 158, 11, 0.5)',
                            'rgba(239, 68, 68, 0.5)'
                        ],
                        borderColor: [
                            'rgb(16, 185, 129)',
                            'rgb(245, 158, 11)',
                            'rgb(239, 68, 68)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: commonOptions
            });
        }

        // Monthly Trend Chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart');
        if (!monthlyTrendCtx) {
            console.error('Monthly Trend Chart canvas not found');
        } else {
            console.log('Monthly Trend data:', <?php echo json_encode($months_data); ?>);
            new Chart(monthlyTrendCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo "'" . implode("', '", array_keys($months_data)) . "'"; ?>],
                    datasets: [{
                        label: 'Total Donations',
                        data: [<?php echo implode(', ', array_values($months_data)); ?>],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: commonOptions
            });
        }

        // Monthly Donations Chart
        const monthlyDonationsCtx = document.getElementById('monthlyDonationsChart');
        if (!monthlyDonationsCtx) {
            console.error('Monthly Donations Chart canvas not found');
        } else {
            console.log('Monthly Donations data:', <?php echo json_encode($monthly_donations); ?>);
            new Chart(monthlyDonationsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Monthly Donations',
                        data: <?php echo json_encode(array_values($monthly_donations)); ?>,
                        backgroundColor: 'rgba(34, 197, 94, 0.2)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    }]
                },
                options: commonOptions
            });
        }

        // Donor Tier Chart
        const donorTierCtx = document.getElementById('donorTierChart');
        if (!donorTierCtx) {
            console.error('Donor Tier Chart canvas not found');
        } else {
            console.log('Donor Tier data:', <?php echo json_encode($donor_tiers); ?>);
            new Chart(donorTierCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        'Blue (₱100 - ₱990)',
                        'Bronze (₱1,000 - ₱4,990)',
                        'Silver (₱5,000 - ₱9,990)',
                        'Gold (₱10,000+)'
                    ],
                    datasets: [{
                        data: <?php echo json_encode(array_values($donor_tiers)); ?>,
                        backgroundColor: [
                            'rgb(59, 130, 246)',   // Blue
                            'rgb(180, 83, 9)',     // Bronze
                            'rgb(156, 163, 175)',  // Silver
                            'rgb(234, 179, 8)'     // Gold
                        ]
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
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
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} donors (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
});

// Print report function
function printReport() {
    // First, render the charts to images
    const paymentMethodsCanvas = document.getElementById('paymentMethodsChart');
    const statusDistributionCanvas = document.getElementById('statusDistributionChart');
    
    // Convert charts to images
    const paymentMethodsImg = document.createElement('img');
    paymentMethodsImg.src = paymentMethodsCanvas.toDataURL('image/png');
    paymentMethodsImg.style.width = '100%';
    paymentMethodsImg.style.maxHeight = '300px';
    
    const statusDistributionImg = document.createElement('img');
    statusDistributionImg.src = statusDistributionCanvas.toDataURL('image/png');
    statusDistributionImg.style.width = '100%';
    statusDistributionImg.style.maxHeight = '300px';
    
    // Get the chart containers
    const paymentMethodsContainer = paymentMethodsCanvas.parentNode;
    const statusDistributionContainer = statusDistributionCanvas.parentNode;
    
    // Temporarily replace canvas with images for printing
    paymentMethodsCanvas.style.display = 'none';
    statusDistributionCanvas.style.display = 'none';
    paymentMethodsContainer.appendChild(paymentMethodsImg);
    statusDistributionContainer.appendChild(statusDistributionImg);
    
    // Get the content to print
    const printContents = document.getElementById('printable-report').innerHTML;
    const originalContents = document.body.innerHTML;
    
    // Create a styled print layout
    document.body.innerHTML = `
        <div class="p-5">
            <div class="mb-4 text-center">
                <h1 class="text-2xl font-bold">Oblatos Foundation by OSJ</h1>
                <p>Donation Report - Generated on ${new Date().toLocaleDateString()}</p>
            </div>
            ${printContents}
            <div class="mt-4 text-center text-sm text-gray-500">
                <p>© ${new Date().getFullYear()} Oblatos Foundation by OSJ. All rights reserved.</p>
            </div>
        </div>
    `;
    
    // Print the document
    window.print();
    
    // Restore original content
    document.body.innerHTML = originalContents;
    
    // Reinitialize charts after printing
    initializeCharts();
}

// Export to CSV function
function exportCSV() {
    // Prepare CSV content
    let csvContent = "Date,Donor,Amount,Payment Method,Reference Number,Status\n";
    
    <?php foreach ($filtered_donations as $row) { ?>
        csvContent += "<?php echo date('Y-m-d', strtotime($row['created_at'])); ?>,";
        csvContent += "\"<?php echo $row['donor_name']; ?>\",";
        csvContent += "<?php echo $row['amount']; ?>,";
        csvContent += "\"<?php echo $row['payment_method'] === 'bank_transfer' ? 'Bank Transfer' : 'GCash'; ?>\",";
        csvContent += "\"<?php echo $row['reference_number']; ?>\",";
        csvContent += "\"<?php echo $row['status']; ?>\"\n";
    <?php } ?>
    
    // Create download link
    const encodedUri = encodeURI("data:text/csv;charset=utf-8," + csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "oblatos_donations_report_<?php echo $year_filter; ?>.csv");
    document.body.appendChild(link);
    
    // Trigger download
    link.click();
}
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
</body>
</html>