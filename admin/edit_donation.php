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

// Check if id parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Donation ID is required";
    redirect('admin/donations.php');
}

// Get donation ID
$donation->id = $_GET['id'];

// Read the donation details
if (!$donation->read_one()) {
    $_SESSION['error_message'] = "Donation not found";
    redirect('admin/donations.php');
}

// Get all donors for the dropdown
$donors = $user->read_all('donor');

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set donation properties
    $donation->donor_id = $_POST['donor_id'];
    $donation->amount = $_POST['amount'];
    $donation->payment_method = $_POST['payment_method'];
    $donation->reference_number = $_POST['reference_number'];
    $donation->status = $_POST['status'];
    
    // If status is changed to verified, update verification details
    if ($donation->status === 'verified' && empty($donation->verified_by)) {
        $donation->verified_by = $_SESSION['user_id'];
        // verified_at will be set in the update method
    }
    
    // Handle receipt image upload if provided
    if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/receipts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['receipt_image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $target_file)) {
            $donation->receipt_image = $file_name;
        } else {
            $_SESSION['error_message'] = "Failed to upload receipt image";
        }
    }
    
    // Update donation
    if ($donation->update()) {
        $_SESSION['success_message'] = "Donation updated successfully";
        redirect('admin/donations.php');
    } else {
        $_SESSION['error_message'] = "Failed to update donation";
    }
}
?>

<main class="flex-1 overflow-y-auto p-5">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Edit Donation</h1>
            <p class="text-gray-600">Update information for donation #<?php echo $donation->id; ?></p>
        </div>
        <div>
            <a href="donations.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Donations
            </a>
        </div>
    </div>
    
    <?php
    // Display error message if any
    if (isset($_SESSION['error_message'])) {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">';
        echo '<p>' . $_SESSION['error_message'] . '</p>';
        echo '</div>';
        unset($_SESSION['error_message']);
    }
    ?>
    
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form method="POST" enctype="multipart/form-data">
            <div class="px-4 py-5 sm:p-6">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-3">
                        <label for="donor_id" class="block text-sm font-medium text-gray-700">Donor</label>
                        <select id="donor_id" name="donor_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <?php while ($row = $donors->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo ($row['id'] == $donation->donor_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['full_name']); ?> (<?php echo htmlspecialchars($row['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-span-6 sm:col-span-3">
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (PHP)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">₱</span>
                            </div>
                            <input type="number" step="0.01" name="amount" id="amount" value="<?php echo $donation->amount; ?>" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="col-span-6 sm:col-span-3">
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select id="payment_method" name="payment_method" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="bank_transfer" <?php echo ($donation->payment_method == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="gcash" <?php echo ($donation->payment_method == 'gcash') ? 'selected' : ''; ?>>GCash</option>
                            <option value="cash" <?php echo ($donation->payment_method == 'cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="check" <?php echo ($donation->payment_method == 'check') ? 'selected' : ''; ?>>Check</option>
                        </select>
                    </div>
                    
                    <div class="col-span-6 sm:col-span-3">
                        <label for="reference_number" class="block text-sm font-medium text-gray-700">Reference Number</label>
                        <input type="text" name="reference_number" id="reference_number" value="<?php echo $donation->reference_number; ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <div class="col-span-6 sm:col-span-3">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="pending" <?php echo ($donation->status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="verified" <?php echo ($donation->status == 'verified') ? 'selected' : ''; ?>>Verified</option>
                            <option value="rejected" <?php echo ($donation->status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="col-span-6">
                        <label for="receipt_image" class="block text-sm font-medium text-gray-700">Receipt Image</label>
                        <div class="mt-2 flex items-center">
                            <?php if (!empty($donation->receipt_image)): ?>
                                <div class="mr-4">
                                    <img src="../uploads/receipts/<?php echo htmlspecialchars($donation->receipt_image); ?>" alt="Current Receipt" class="h-32 w-auto border border-gray-200 rounded">
                                    <p class="text-xs text-gray-500 mt-1">Current receipt</p>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <input type="file" name="receipt_image" id="receipt_image" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300">
                                <p class="mt-1 text-sm text-gray-500">Upload a new receipt image (optional)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Donation
                </button>
            </div>
        </form>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
