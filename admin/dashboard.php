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
$user = new User($db);
$donation = new Donation($db);

// Get counts
$donor_count = $user->read_all('donor')->rowCount();
$donation_count = $donation->read_all()->rowCount();

// Get pending and verified donations with the enhanced methods
$pending_stmt = $donation->get_pending_donations();
$pending_count = $pending_stmt->rowCount();

$verified_stmt = $donation->get_verified_donations();
$verified_count = $verified_stmt->rowCount();

// Get total donations
$total_donations = 0;
$verified_donations = $donation->read_all(null, 'verified');
while ($row = $verified_donations->fetch(PDO::FETCH_ASSOC)) {
    $total_donations += $row['amount'];
}

// Get recent donations
$recent_donations = $donation->read_all(null, null, date('Y-m-d', strtotime('-30 days')));

// Get monthly donation data for the current year
$current_year = date('Y');
$monthly_totals = array_fill(0, 12, 0); // Initialize array with 0s
$monthly_donations = $donation->get_monthly_totals(null, $current_year);
foreach ($monthly_donations as $month => $total) {
    $monthly_totals[$month - 1] = $total;
}

// Get donor tier distribution
$donor_tiers = [
    'blue' => 0,
    'bronze' => 0,
    'silver' => 0,
    'gold' => 0
];

$donors = $user->read_all('donor');
while ($donor = $donors->fetch(PDO::FETCH_ASSOC)) {
    $monthly_total = $donation->get_user_monthly_total($donor['id']);
    if ($monthly_total >= GOLD_TIER_MIN) {
        $donor_tiers['gold']++;
    } elseif ($monthly_total >= SILVER_TIER_MIN) {
        $donor_tiers['silver']++;
    } elseif ($monthly_total >= BRONZE_TIER_MIN) {
        $donor_tiers['bronze']++;
    } elseif ($monthly_total >= BLUE_TIER_MIN) {
        $donor_tiers['blue']++;
    } else {
        $donor_tiers['blue']++;  // New donors start at blue tier
    }
}
?>

<main class="flex-1 overflow-y-auto p-5">
    <!-- Welcome Banner -->
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-md">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-lg font-medium">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                <p class="text-sm">You are logged in as an Administrator. You have full access to manage users, donations, and system settings.</p>
            </div>
        </div>
    </div>
    
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
    </div>
    
    <!-- Success and Error Messages -->
    <?php
    // Success and error messages
    $success_message = $_SESSION['success_message'] ?? '';
    $error_message = $_SESSION['error_message'] ?? '';
    
    // Clear session messages
    unset($_SESSION['success_message']);
    unset($_SESSION['error_message']);
    ?>
    
    <?php if (!empty($success_message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?php echo $error_message; ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Verified Donations Highlight -->
    <div class="bg-green-50 border border-green-200 rounded-lg shadow-sm p-6 mb-8">
        <div class="flex flex-col md:flex-row items-center justify-between">
            <div class="flex items-center mb-4 md:mb-0">
                <div class="bg-green-100 rounded-full p-3 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Verified Donations</h2>
                    <p class="text-gray-600">Total of <?php echo $verified_count; ?> successful donations worth <?php echo formatPeso($total_donations); ?></p>
                </div>
            </div>
            <a href="donations.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                View All Verified Donations
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Donations Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm font-medium">Total Donations</p>
                        <p class="text-gray-900 text-2xl font-semibold"><?php echo formatPeso($total_donations); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Total Donors Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm font-medium">Total Donors</p>
                        <p class="text-gray-900 text-2xl font-semibold"><?php echo $donor_count; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Donation Count Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm font-medium">Total Transactions</p>
                        <p class="text-gray-900 text-2xl font-semibold"><?php echo $donation_count; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Verifications Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm font-medium">Pending Verifications</p>
                        <p class="text-gray-900 text-2xl font-semibold"><?php echo $pending_count; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Donations Chart -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b">
                <h2 class="text-lg font-semibold text-gray-700">Monthly Donations (<?php echo $current_year; ?>)</h2>
            </div>
            <div class="p-5">
                <canvas id="monthlyDonationsChart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Donor Tiers Chart -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b">
                <h2 class="text-lg font-semibold text-gray-700">Donor Tier Distribution</h2>
            </div>
            <div class="p-5">
                <canvas id="donorTiersChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Donations -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Recent Donations</h2>
            <a href="donations.php" class="text-sm font-medium text-green-600 hover:text-green-500">
                View All
            </a>
        </div>
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
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    if ($recent_donations->rowCount() > 0) {
                        while ($row = $recent_donations->fetch(PDO::FETCH_ASSOC)) {
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
                            <div class="text-sm font-medium text-gray-900"><?php echo $row['donor_name']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo formatPeso($row['amount']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php 
                            echo $row['payment_method'] === 'bank_transfer' ? 'Bank Transfer' : 'GCash';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="view_donation.php?id=<?php echo $row['id']; ?>" class="text-green-600 hover:text-green-900">View</a>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                    ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No recent donations found.
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
// Monthly donations chart
const monthlyCtx = document.getElementById('monthlyDonationsChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Monthly Donations (PHP)',
            data: <?php echo json_encode($monthly_totals); ?>,
            backgroundColor: 'rgba(34, 197, 94, 0.2)',
            borderColor: 'rgb(34, 197, 94)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '₱' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});

// Donor tiers chart
const tiersCtx = document.getElementById('donorTiersChart').getContext('2d');
new Chart(tiersCtx, {
    type: 'doughnut',
    data: {
        labels: [
            'Blue (₱' + BLUE_TIER_MIN.toLocaleString() + ' - ₱' + BLUE_TIER_MAX.toLocaleString() + ')',
            'Bronze (₱' + BRONZE_TIER_MIN.toLocaleString() + ' - ₱' + BRONZE_TIER_MAX.toLocaleString() + ')',
            'Silver (₱' + SILVER_TIER_MIN.toLocaleString() + ' - ₱' + SILVER_TIER_MAX.toLocaleString() + ')',
            'Gold (₱' + GOLD_TIER_MIN.toLocaleString() + '+)'
        ],
        datasets: [{
            data: <?php echo json_encode(array_values($donor_tiers)); ?>,
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',   // Blue
                'rgba(205, 127, 50, 0.8)',   // Bronze
                'rgba(192, 192, 192, 0.8)',  // Silver
                'rgba(255, 215, 0, 0.8)'     // Gold
            ],
            borderColor: [
                'rgb(59, 130, 246)',
                'rgb(205, 127, 50)',
                'rgb(192, 192, 192)',
                'rgb(255, 215, 0)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${label}: ${value} donors (${percentage}%)`;
                    }
                }
            }
        }
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>