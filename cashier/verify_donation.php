<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Donation.php';
require_once '../models/User.php';
require_once '../utils/Mailer.php';
require_once '../includes/header.php';

// Check if user is logged in and is a cashier
if (!isLoggedIn() || !hasRole('cashier')) {
    redirect('auth/login.php');
}

// Check if donation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('cashier/dashboard.php');
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
if (!$donation->read_one() || $donation->status !== 'pending') {
    redirect('cashier/dashboard.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    
    if (empty($status) || !in_array($status, ['verified', 'rejected'])) {
        $_SESSION['error_message'] = 'Please select a valid status';
        redirect('cashier/verify_donation.php?id=' . $donation_id);
    }
    
    // Update donation
    $donation->status = $status;
    $donation->verified_by = $_SESSION['user_id'];
    
    if ($donation->verify()) {
        // Get cashier name for the email
        $user = new User($db);
        $user->id = $_SESSION['user_id'];
        $user->read_one();
        $cashier_name = $user->full_name;
        
        // Format date for email
        $donation_date = date('F d, Y', strtotime($donation->created_at));
        
        // Send email notification to donor
        $email_sent = Mailer::sendDonationStatusEmail(
            $donation->donor_email,
            $donation->donor_name,
            $donation->id,
            $donation->amount,
            $donation_date,
            $status,
            $cashier_name
        );
        
        $email_message = $email_sent ? 'Email notification sent to donor.' : 'Failed to send email notification.';
        
        if ($status === 'verified') {
            $_SESSION['success_message'] = '<div class="verification-success">
                <h3><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg> Donation Successfully Verified!</h3>
                <p>Donation #' . $donation->id . ' from ' . $donation->donor_name . ' for ' . formatPeso($donation->amount) . ' has been verified.</p>
                <p class="email-status">' . $email_message . ' The donor has been notified of this verification.</p>
                <p>This donation will now appear in the admin\'s verified donations list.</p>
            </div>';
        } else {
            $_SESSION['success_message'] = '<div class="verification-rejected">
                <h3><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg> Donation Rejected</h3>
                <p>Donation #' . $donation->id . ' from ' . $donation->donor_name . ' for ' . formatPeso($donation->amount) . ' has been rejected.</p>
                <p class="email-status">' . $email_message . ' The donor has been notified of this rejection.</p>
            </div>';
        }
        redirect('cashier/dashboard.php');
    } else {
        $_SESSION['error_message'] = 'Failed to update donation status';
        redirect('cashier/verify_donation.php?id=' . $donation_id);
    }
}
?>

<main class="flex-1 overflow-y-auto p-5">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Verify Donation</h1>
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
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                Pending
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
                        <p class="text-sm font-medium text-gray-500">Donor</p>
                        <p class="mt-1 text-gray-900"><?php echo $donation->donor_name; ?></p>
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
                    
                    <form action="verify_donation.php?id=<?php echo $donation_id; ?>" method="post" class="mt-8">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Verification Action</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="status" value="verified" class="form-radio h-5 w-5 text-green-600">
                                    <span class="ml-2 text-gray-700">Verify Donation</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="status" value="rejected" class="form-radio h-5 w-5 text-red-600">
                                    <span class="ml-2 text-gray-700">Reject Donation</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                                Submit Verification
                            </button>
                            <a href="dashboard.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                                Cancel
                            </a>
                            <a href="send_email.php?donor_id=<?php echo $donation->donor_id; ?>" class="inline-flex justify-center py-2 px-4 border border-blue-300 shadow-sm text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Send Custom Email
                            </a>
                        </div>
                    </form>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Receipt Image</h3>
                    <div class="border rounded-lg overflow-hidden">
                        <img src="../<?php echo UPLOAD_DIR . $donation->receipt_image; ?>" alt="Receipt" class="w-full h-auto">
                    </div>
                    
                    <div class="mt-4">
                        <a href="../<?php echo UPLOAD_DIR . $donation->receipt_image; ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 ease-in-out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            View Full Size
                        </a>
                    </div>
                    
                    <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Verification Guidelines:</strong><br>
                                    1. Verify the reference number matches the payment records.<br>
                                    2. Check the amount matches what was paid.<br>
                                    3. Ensure the payment date is correct.<br>
                                    4. Verify the receipt image is clear and authentic.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Include footer
require_once '../includes/footer.php';
?>