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

// Check if donor ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Donor ID is required';
    redirect('admin/donors.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate User and Donation objects
$user = new User($db);
$donation = new Donation($db);

// Get donor information
$user->id = $_GET['id'];
if (!$user->read_one() || $user->role !== 'donor') {
    $_SESSION['error_message'] = 'Donor not found';
    redirect('admin/donors.php');
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

// Calculate total lifetime donations
$lifetime_total = 0;
$donation_count = 0;
$payment_methods = ['bank_transfer' => 0, 'gcash' => 0];
$monthly_donations = array_fill(0, 12, 0);

$filtered_donations = [];
while ($row = $donations->fetch(PDO::FETCH_ASSOC)) {
    $filtered_donations[] = $row;
    $lifetime_total += $row['amount'];
    $donation_count++;
    $payment_methods[$row['payment_method']]++;
    
    // Update monthly donations array for current year
    if (date('Y', strtotime($row['created_at'])) == $current_year) {
        $month_index = intval(date('n', strtotime($row['created_at']))) - 1;
        $monthly_donations[$month_index] += $row['amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Donor - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<main class="flex-1 overflow-y-auto p-5">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Donor Profile</h1>
            <p class="text-gray-600">Detailed information about <?php echo htmlspecialchars($user->full_name); ?></p>
        </div>
        <div class="flex space-x-3">
            <a href="export_donor_pdf.php?id=<?php echo $user->id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                Export as PDF
            </a>
            <a href="edit_donor.php?id=<?php echo $user->id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Profile
            </a>
            <button onclick="toggleDonorStatus(<?php echo $user->id; ?>, '<?php echo $user->status; ?>')" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-<?php echo $user->status === 'active' ? 'red' : 'green'; ?>-600 hover:bg-<?php echo $user->status === 'active' ? 'red' : 'green'; ?>-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-<?php echo $user->status === 'active' ? 'red' : 'green'; ?>-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <?php echo $user->status === 'active' ? 'Deactivate' : 'Activate'; ?> Account
            </button>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Profile Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Full Name</label>
                            <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($user->full_name); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email</label>
                            <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($user->email); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                            <p class="mt-1 text-lg text-gray-900"><?php echo isset($user->phone_number) && $user->phone_number ? htmlspecialchars($user->phone_number) : 'Not provided'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Account Status</label>
                            <span class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($user->status); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Donation Statistics</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Donor Tier</label>
                            <span class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium" style="background-color: <?php echo $tier_color; ?>20; color: <?php echo $tier_color; ?>">
                                <?php echo $tier; ?>
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Monthly Average (<?php echo $current_year; ?>)</label>
                            <p class="mt-1 text-lg text-gray-900"><?php echo formatPeso($monthly_average); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Total Donations (<?php echo $current_year; ?>)</label>
                            <p class="mt-1 text-lg text-gray-900"><?php echo formatPeso($annual_total); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Lifetime Total</label>
                            <p class="mt-1 text-lg text-gray-900"><?php echo formatPeso($lifetime_total); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Total Transactions</label>
                            <p class="mt-1 text-lg text-gray-900"><?php echo $donation_count; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Donations Chart -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Monthly Donations (<?php echo $current_year; ?>)</h3>
                <div style="height: 300px;">
                    <canvas id="monthlyDonationsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Methods Chart -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Payment Methods Distribution</h3>
                <div style="height: 300px;">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Donation History -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Donation History</h2>
        </div>
        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference #</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified By</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified At</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        if (count($filtered_donations) > 0) {
                            foreach ($filtered_donations as $row) {
                                $status_color = 'gray';
                                switch ($row['status']) {
                                    case 'pending':
                                        $status_color = 'yellow';
                                        break;
                                    case 'verified':
                                        $status_color = 'green';
                                        break;
                                    case 'rejected':
                                        $status_color = 'red';
                                        break;
                                }
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo formatPeso($row['amount']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $row['payment_method'] === 'bank_transfer' ? 'Bank Transfer' : 'GCash'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $row['reference_number']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo isset($row['verifier_name']) && !empty($row['verifier_name']) ? htmlspecialchars($row['verifier_name']) : 'Not verified'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                if (isset($row['verified_at']) && !empty($row['verified_at'])) {
                                    echo date('M d, Y h:i A', strtotime($row['verified_at']));
                                } else {
                                    echo 'Not verified';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No donations found.</td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
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

    // Monthly Donations Chart
    const monthlyDonationsCtx = document.getElementById('monthlyDonationsChart');
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

    // Payment Methods Chart
    const paymentMethodsCtx = document.getElementById('paymentMethodsChart');
    new Chart(paymentMethodsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Bank Transfer', 'GCash'],
            datasets: [{
                data: [
                    <?php echo $payment_methods['bank_transfer']; ?>,
                    <?php echo $payment_methods['gcash']; ?>
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.5)',
                    'rgba(16, 185, 129, 0.5)'
                ],
                borderColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)'
                ],
                borderWidth: 1
            }]
        },
        options: commonOptions
    });
});

function toggleDonorStatus(donorId, currentStatus) {
    if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'deactivate' : 'activate') + ' this donor?')) {
        window.location.href = `toggle_donor_status.php?id=${donorId}`;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
