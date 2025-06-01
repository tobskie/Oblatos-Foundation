<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Donation.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate models
$user = new User($db);
$donation = new Donation($db);

// Check if donor ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'Donor ID is required';
    redirect('manage_donors.php');
}

$donor_id = intval($_GET['id']);
$user->id = $donor_id;

// Verify the donor exists and is a donor
if (!$user->read_one() || $user->role !== 'donor') {
    $_SESSION['error_message'] = 'Invalid donor';
    redirect('manage_donors.php');
}

// Get all donations by this donor
$donor_donations = $donation->read_all($donor_id);

// Success and error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<main class="flex-1 overflow-y-auto p-5">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Donor Donations - <?php echo htmlspecialchars($user->full_name); ?></h1>
            <p class="text-gray-600">Donation history and verification status</p>
        </div>
        <div class="flex space-x-4">
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <?php echo htmlspecialchars($user->username); ?>
            </span>
            <a href="manage_donors.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Donors
            </a>
        </div>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?php echo $success_message; ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?php echo $error_message; ?></p>
    </div>
    <?php endif; ?>

    <!-- Donor Information Card -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Donor Information</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Personal details and status</p>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Full name</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($user->full_name); ?></dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Email address</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($user->email); ?></dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo isset($user->status) && $user->status === 'inactive' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo ucfirst($user->status ?? 'active'); ?>
                        </span>
                    </dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Joined on</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo date('F j, Y', strtotime($user->created_at)); ?></dd>
                </div>
            </dl>
        </div>
    </div>
    
    <!-- Donation History -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Donation History</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">All donations and verification status</p>
            </div>
            <div>
                <span class="bg-green-100 text-green-800 text-xs font-medium px-3 py-1 rounded-full">
                    Total: ₱<?php echo number_format($donation->get_user_total($donor_id), 2); ?>
                </span>
            </div>
        </div>
        <div class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified By</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($donor_donations && $donor_donations->rowCount() > 0): ?>
                        <?php while ($row = $donor_donations->fetch(PDO::FETCH_ASSOC)): ?>
                            <?php
                            // Get verifier information if verified
                            $verifier_name = "N/A";
                            if (!empty($row['verified_by'])) {
                                $verifier = new User($db);
                                $verifier->id = $row['verified_by'];
                                if ($verifier->read_one()) {
                                    $verifier_name = htmlspecialchars($verifier->full_name);
                                }
                            }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $row['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($row['created_at'] ?? date('Y-m-d'))); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-700">
                                    ₱<?php echo number_format($row['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo ucwords(str_replace('_', ' ', $row['payment_method'] ?? '')); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($row['reference_number'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                            if ($row['status'] === 'verified') {
                                                echo 'bg-green-100 text-green-800';
                                            } elseif ($row['status'] === 'rejected') {
                                                echo 'bg-red-100 text-red-800';
                                            } else {
                                                echo 'bg-yellow-100 text-yellow-800';
                                            }
                                        ?>">
                                        <?php echo ucfirst($row['status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $verifier_name; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_donation.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">View</a>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <a href="../cashier/verify_donation.php?id=<?php echo $row['id']; ?>" class="text-green-600 hover:text-green-900">Verify</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                No donations found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
