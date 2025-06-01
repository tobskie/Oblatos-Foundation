<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Donation.php';
require_once '../models/User.php';
require_once '../includes/header.php';

// Check if user is logged in and is a donor
if (!isLoggedIn() || !hasRole('donor')) {
    redirect('auth/login.php');
}

// Check if donation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('donor/dashboard.php');
}

// Get donation ID
$donation_id = $_GET['id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create donation object
$donation = new Donation($db);
$donation->id = $donation_id;

// Get donation details
if (!$donation->read_one() || $donation->donor_id != $_SESSION['user_id']) {
    redirect('donor/dashboard.php');
}

// Get verifier information if available
$verifier_name = '';
if (!empty($donation->verified_by)) {
    $user = new User($db);
    $user->id = $donation->verified_by;
    if ($user->read_one()) {
        $verifier_name = $user->full_name;
    }
}

// Get status information
$status_color = 'gray';
$status_text = 'Unknown';

switch ($donation->status) {
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

<main class="flex-1 overflow-y-auto p-5">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Donation Details</h1>
        <a href="dashboard.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 ease-in-out">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Donation #<?php echo $donation->id; ?></h2>
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                <?php echo $status_text; ?>
            </span>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Donation Information</h3>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Amount</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo formatPeso($donation->amount); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Date</p>
                            <p class="mt-1 text-gray-900"><?php echo date('M d, Y', strtotime($donation->created_at)); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-500">Payment Method</p>
                        <p class="mt-1 text-gray-900">
                            <?php echo $donation->payment_method === 'bank_transfer' ? 'Bank Transfer' : 'GCash'; ?>
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-500">Reference Number</p>
                        <p class="mt-1 text-gray-900"><?php echo $donation->reference_number; ?></p>
                    </div>
                    
                    <?php if ($donation->status === 'verified'): ?>
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-500">Verified By</p>
                        <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($verifier_name); ?></p>
                    </div>
                    
                    <div class="mb-6">
                        <p class="text-sm font-medium text-gray-500">Verified At</p>
                        <p class="mt-1 text-gray-900"><?php echo !empty($donation->verification_date) ? date('M d, Y h:i A', strtotime($donation->verification_date)) : 'N/A'; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Receipt Image</h3>
                    <?php if (!empty($donation->payment_proof) && file_exists("../uploads/receipts/" . $donation->payment_proof)): ?>
                    <div class="border rounded-lg overflow-hidden">
                        <img src="../uploads/receipts/<?php echo htmlspecialchars($donation->payment_proof); ?>" alt="Receipt" class="w-full h-auto">
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500">No receipt image available</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($donation->status === 'pending'): ?>
            <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Your donation is pending verification by our cashier. This usually takes 1-2 business days.
                        </p>
                    </div>
                </div>
            </div>
            <?php elseif ($donation->status === 'verified'): ?>
            <div class="mt-8 bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            Thank you! Your donation has been verified and recorded. We appreciate your support.
                        </p>
                    </div>
                </div>
            </div>
            <?php elseif ($donation->status === 'rejected'): ?>
            <div class="mt-8 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            Your donation has been rejected. This may be due to payment verification issues. Please contact us for more information or submit a new donation.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>