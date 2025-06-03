<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';
require_once '../includes/header.php';

// Check if user is logged in and is a donor
if (!isLoggedIn() || !hasRole('donor')) {
    redirect('auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate models
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_one();

$donation = new Donation($db);

// Get donor's donations
$donations = $donation->read_all($_SESSION['user_id']);

// Get total verified donations for all time
$query = "SELECT SUM(d.amount) as total
          FROM donations d
          JOIN donation_status_history dsh ON d.id = dsh.donation_id
          JOIN donation_statuses ds ON dsh.status_id = ds.id
          WHERE d.donor_id = :donor_id 
          AND ds.name = 'verified'
          AND dsh.id = (
              SELECT id FROM donation_status_history 
              WHERE donation_id = d.id 
              ORDER BY changed_at DESC 
              LIMIT 1
          )";
          
$stmt = $db->prepare($query);
$stmt->execute(['donor_id' => $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_donations = $result['total'] ?? 0;

// Determine donor tier based on total verified donations
$donor_tier = getDonorTier($total_donations);
$tier_color = getTierColorClass($donor_tier);

// Get next tier threshold
$next_tier_amount = 0;
$next_tier_name = '';

switch ($donor_tier) {
    case 'Blue':
        $next_tier_amount = BRONZE_TIER_MIN - $total_donations;
        $next_tier_name = 'Bronze';
        break;
    case 'Bronze':
        $next_tier_amount = SILVER_TIER_MIN - $total_donations;
        $next_tier_name = 'Silver';
        break;
    case 'Silver':
        $next_tier_amount = GOLD_TIER_MIN - $total_donations;
        $next_tier_name = 'Gold';
        break;
}

// Calculate progress percentage for progress bar
$progress = 0;
switch ($donor_tier) {
    case 'Blue':
        $progress = ($total_donations / BRONZE_TIER_MIN) * 100;
        break;
    case 'Bronze':
        $progress = (($total_donations - BRONZE_TIER_MIN) / (SILVER_TIER_MIN - BRONZE_TIER_MIN)) * 100;
        break;
    case 'Silver':
        $progress = (($total_donations - SILVER_TIER_MIN) / (GOLD_TIER_MIN - SILVER_TIER_MIN)) * 100;
        break;
    case 'Gold':
        $progress = 100;
        break;
}
?>

<main class="flex-1 overflow-y-auto p-5">
    <!-- Welcome Banner -->
    <div class="bg-purple-50 border-l-4 border-purple-500 text-purple-700 p-4 mb-6 rounded shadow-md">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-purple-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-lg font-medium">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
                <p class="text-sm">You are logged in as a Donor. Thank you for your continued support to the Oblatos Foundation.</p>
            </div>
        </div>
    </div>
    
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Donor Dashboard</h1>
    </div>
    
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
    
    <style>
    .donation-confirmation {
        text-align: center;
        padding: 10px;
    }
    .donation-confirmation h3 {
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: #047857;
    }
    .pending-status {
        background-color: #fef3c7;
        color: #92400e;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.9rem;
    }
    </style>
    
    <!-- Donor Tier Card -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Your Donor Status</h2>
        </div>
        <div class="p-5">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center h-12 w-12 rounded-full <?php echo $tier_color; ?> text-white text-lg font-semibold">
                        <?php echo substr($donor_tier, 0, 1); ?>
                    </span>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-700"><?php echo $donor_tier; ?> Tier Donor</h3>
                    <p class="text-sm text-gray-500">
                        Total Contribution: <?php echo formatPeso($total_donations); ?>
                    </p>
                </div>
            </div>
            
            <?php if ($donor_tier !== 'Gold'): ?>
            <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-600 mb-2">
                    Donate <?php echo formatPeso($next_tier_amount); ?> more to reach <?php echo $next_tier_name; ?> Tier
                </h4>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo min(100, $progress); ?>%"></div>
                </div>
            </div>
            <?php else: ?>
            <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Congratulations! You've reached the Gold Tier, our highest donor recognition level.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Donate Now Card -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Make a Donation</h2>
        </div>
        <div class="p-5">
            <div class="flex flex-col md:flex-row items-start gap-6">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Donation Methods</h3>
                    <p class="text-gray-600 mb-4">
                        Support our mission by making a donation through one of our payment methods.
                    </p>
                    
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-700 mb-2">Bank Transfer</h4>
                        <div class="bg-gray-50 p-3 rounded border mb-2">
                            <p class="text-sm text-gray-600">Account Name: <span class="font-medium">Oblatos Foundation by OSJ</span></p>
                            <p class="text-sm text-gray-600">Account Number: <span class="font-medium">1234-5678-9012-3456</span></p>
                            <p class="text-sm text-gray-600">Bank: <span class="font-medium">BDO</span></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-700 mb-2">GCash</h4>
                        <div class="flex items-center bg-gray-50 p-3 rounded border">
                            <div class="flex-shrink-0 mr-3">
                                <img src="../assets/img/gcash-qr.png" alt="GCash QR Code" class="w-24 h-24 object-cover">
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">GCash Name: <span class="font-medium">Oblatos Foundation</span></p>
                                <p class="text-sm text-gray-600">Number: <span class="font-medium">09123456789</span></p>
                                <a href="../assets/img/gcash-qr.png" download class="text-sm text-green-600 font-medium hover:text-green-700 inline-flex items-center mt-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    Download QR Code
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 w-full">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Record Your Donation</h3>
                    <form action="process_donation.php" method="post" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Donation Amount (PHP)</label>
                            <input type="number" id="amount" name="amount" required min="1" step="any" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                        </div>
                        
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                            <select id="payment_method" name="payment_method" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                                <option value="">Select payment method</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="gcash">GCash</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                            <input type="text" id="reference_number" name="reference_number" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                            <p class="text-xs text-gray-500 mt-1">Transaction or reference number from your payment receipt.</p>
                        </div>
                        
                        <div>
                            <label for="receipt_image" class="block text-sm font-medium text-gray-700 mb-1">Receipt Screenshot</label>
                            <input type="file" id="receipt_image" name="receipt_image" required accept="image/*"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                            <p class="text-xs text-gray-500 mt-1">Upload a screenshot of your payment receipt/confirmation.</p>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                                Submit Donation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Donation History -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Donation History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    if ($donations->rowCount() > 0) {
                        while ($row = $donations->fetch(PDO::FETCH_ASSOC)) {
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
                            No donations found. Make your first donation!
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

<?php
// Include footer
require_once '../includes/footer.php';
?>