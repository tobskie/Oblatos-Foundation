<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Donation.php';
require_once '../includes/header.php';

// Check if user is logged in and is a donor
if (!isLoggedIn() || !hasRole('donor')) {
    redirect('auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create donation object
$donation = new Donation($db);

// Get all donations for the current user
$user_id = $_SESSION['user_id'];
$donations = $donation->read_all($user_id);

// Get total donations (no year filtering since we simplified the model)
$lifetime_total = $donation->get_user_total($user_id);
$yearly_total = $lifetime_total; // Since we can't filter by year, just use the same total
?>

<div class="py-10">
    <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold leading-tight text-gray-900">My Donation History</h1>
        </div>
    </header>
    <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Current Donations</dt>
                                <dd class="mt-1 text-3xl font-semibold text-gray-900">₱<?php echo number_format($yearly_total, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Lifetime Donations</dt>
                                <dd class="mt-1 text-3xl font-semibold text-gray-900">₱<?php echo number_format($lifetime_total, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Donation History Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Donation History</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">A complete record of your donations</p>
                    </div>
                    
                    <div class="border-t border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donation #</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ($donations->rowCount() > 0): ?>
                                        <?php while ($row = $donations->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    Donation #<?php echo $row['id']; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    ₱<?php echo number_format($row['amount'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <a href="view_donation.php?id=<?php echo $row['id']; ?>" class="text-green-600 hover:text-green-900">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                No donations found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- New Donation Button -->
                <div class="mt-6 flex justify-end">
                    <a href="make_donation.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Make a New Donation
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
