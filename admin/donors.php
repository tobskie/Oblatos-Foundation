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

// Instantiate User object
$user = new User($db);
$donation = new Donation($db);

// Get all donors
$donors = $user->read_all('donor');

// Get current year for donation calculations
$current_year = date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donors - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>

<main class="flex-1 overflow-y-auto p-5">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Donor Management</h1>
    </div>

    <!-- Donor List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Donors List</h2>
        </div>
        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Total</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        if ($donors && $donors->rowCount() > 0) {
                            while ($row = $donors->fetch(PDO::FETCH_ASSOC)) {
                                // Debug: Log donor info
                                error_log("Processing donor: " . $row['full_name'] . " (ID: " . $row['id'] . ")");
                                
                                // Get monthly total
                                $monthly_total = $donation->get_user_monthly_total($row['id']);
                                
                                // Get donor tier based on annual total
                                $annual_total = $donation->get_user_total($row['id'], date('Y'));
                                $donor_tier = getDonorTier($annual_total);
                                
                                // Debug: Log calculations
                                error_log("Annual total: " . $annual_total);
                                error_log("Monthly total: " . $monthly_total);
                                
                                // Get tier color based on tier
                                $tier_color = getTierColor($donor_tier);
                                
                                // Debug: Log final tier
                                error_log("Assigned tier: " . $donor_tier);
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo formatPeso($monthly_total); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $tier_color; ?>-100 text-<?php echo $tier_color; ?>-800">
                                    <?php echo $donor_tier; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo ($row['status'] ?? 'active') === 'active' ? 'green' : 'red'; ?>-100 text-<?php echo ($row['status'] ?? 'active') === 'active' ? 'green' : 'red'; ?>-800">
                                    <?php echo ucfirst($row['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="view_donor.php?id=<?php echo $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Edit</a>
                                <button onclick="toggleDonorStatus(<?php echo $row['id']; ?>, '<?php echo $row['status'] ?? 'active'; ?>')" 
                                        class="text-<?php echo ($row['status'] ?? 'active') === 'active' ? 'red' : 'green'; ?>-600 hover:text-<?php echo ($row['status'] ?? 'active') === 'active' ? 'red' : 'green'; ?>-900">
                                    <?php echo ($row['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No donors found.</td>
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
function toggleDonorStatus(donorId, currentStatus) {
    if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'deactivate' : 'activate') + ' this donor?')) {
        window.location.href = `toggle_donor_status.php?id=${donorId}`;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html> 