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
    if (isset($_POST['status'])) {
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        
        // Update donation status
        if ($donation->update_status($donation_id, $status, $_SESSION['user_id'], $notes)) {
            // Send email notification to donor
            try {
                $mailer = new Mailer();
                $subject = $status === 'verified' ? 'Donation Verified' : 'Donation Rejected';
                $message = $status === 'verified' 
                    ? "Your donation of ₱" . number_format($donation->amount, 2) . " has been verified. Thank you for your generosity!"
                    : "Your donation of ₱" . number_format($donation->amount, 2) . " has been rejected. Reason: " . $notes;
                
                $email_sent = Mailer::send($donation->donor_email, $subject, $message);
                $email_message = $email_sent ? "Email notification sent successfully." : "Failed to send email notification.";
            } catch (Exception $e) {
                $email_message = "Failed to send email notification: " . $e->getMessage();
            }
            
            if ($status === 'verified') {
                $_SESSION['success_message'] = '<div class="verification-success">
                    <h3><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg> Donation Successfully Verified!</h3>
                    <p>Donation #' . $donation->id . ' from ' . $donation->donor_name . ' for ₱' . number_format($donation->amount, 2) . ' has been verified.</p>
                    <p class="email-status">' . $email_message . ' The donor has been notified of this verification.</p>
                    <p>This donation will now appear in the verified donations list.</p>
                </div>';
            } else {
                $_SESSION['success_message'] = '<div class="verification-rejected">
                    <h3><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg> Donation Rejected</h3>
                    <p>Donation #' . $donation->id . ' from ' . $donation->donor_name . ' for ₱' . number_format($donation->amount, 2) . ' has been rejected.</p>
                    <p class="email-status">' . $email_message . ' The donor has been notified of this rejection.</p>
                </div>';
            }
            redirect('cashier/dashboard.php');
        } else {
            $_SESSION['error_message'] = 'Failed to update donation status';
            redirect('cashier/verify_donation.php?id=' . $donation_id);
        }
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
                    
                    <div class="border-t border-gray-200">
                        <dl>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Donor Name
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <?php echo htmlspecialchars($donation->donor_name); ?>
                                </dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Amount
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    ₱<?php echo number_format($donation->amount, 2); ?>
                                </dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Payment Method
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <?php echo ucfirst(str_replace('_', ' ', $donation->payment_method)); ?>
                                </dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Reference Number
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <?php echo htmlspecialchars($donation->reference_number); ?>
                                </dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Date Created
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <?php echo date('M d, Y h:i A', strtotime($donation->created_at)); ?>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <?php if ($donation->payment_proof): ?>
                    <div class="border-t border-gray-200 px-4 py-5">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Receipt</h3>
                        <div class="mt-2">
                            <img src="../uploads/receipts/<?php echo htmlspecialchars($donation->payment_proof); ?>" 
                                 alt="Payment Receipt" 
                                 class="max-w-2xl rounded-lg shadow-lg">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="border-t border-gray-200 px-4 py-5">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Verification Action</h3>
                        <form method="POST" class="space-y-6">
                            <div class="space-y-4">
                                <div class="flex items-center space-x-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="status" value="verified" class="form-radio h-5 w-5 text-green-600" required>
                                        <span class="ml-2 text-gray-700">Verify Donation</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="status" value="rejected" class="form-radio h-5 w-5 text-red-600">
                                        <span class="ml-2 text-gray-700">Reject Donation</span>
                                    </label>
                                </div>
                                
                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes (required for rejection)</label>
                                    <div class="mt-1">
                                        <textarea id="notes" name="notes" rows="3" 
                                                class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Please provide a reason if rejecting the donation. This will be included in the notification to the donor.
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <a href="dashboard.php" 
                                   class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Submit Verification
                                </button>
                            </div>
                        </form>
                    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const notesField = document.getElementById('notes');
    const statusRadios = document.querySelectorAll('input[name="status"]');

    // Function to validate form
    function validateForm(e) {
        const selectedStatus = document.querySelector('input[name="status"]:checked');
        
        if (!selectedStatus) {
            alert('Please select a verification action (Verify or Reject)');
            e.preventDefault();
            return false;
        }

        if (selectedStatus.value === 'rejected' && !notesField.value.trim()) {
            alert('Please provide a reason for rejecting the donation');
            notesField.focus();
            e.preventDefault();
            return false;
        }

        // Confirm action
        const action = selectedStatus.value === 'verified' ? 'verify' : 'reject';
        if (!confirm(`Are you sure you want to ${action} this donation?`)) {
            e.preventDefault();
            return false;
        }

        return true;
    }

    // Add form validation
    form.addEventListener('submit', validateForm);

    // Show/hide notes field based on selected status
    statusRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const notesContainer = notesField.closest('div').parentElement;
            if (this.value === 'rejected') {
                notesContainer.style.display = 'block';
                notesField.setAttribute('required', 'required');
            } else {
                notesContainer.style.display = 'none';
                notesField.removeAttribute('required');
            }
        });
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>