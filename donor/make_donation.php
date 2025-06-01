<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Donation.php';
require_once '../includes/header.php';

// Check if user is logged in and is a donor
if (!isLoggedIn() || !hasRole('donor')) {
    redirect('auth/login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create donation object
    $donation = new Donation($db);
    
    // Validate amount
    if (!isset($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
        $error_message = 'Please enter a valid donation amount';
    } else {
        // Set donation properties
        $donation->donor_id = $_SESSION['user_id'];
        $donation->amount = $_POST['amount'];
        $donation->payment_method = $_POST['payment_method'];
        $donation->reference_number = $_POST['reference_number'];
        $donation->status = 'pending';
        
        // Handle receipt image upload
        if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/receipts/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_name = uniqid('receipt_') . '_' . basename($_FILES['receipt_image']['name']);
            $upload_file = $upload_dir . $file_name;
            
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['receipt_image']['type'], $allowed_types)) {
                $error_message = 'Only JPG, JPEG, and PNG files are allowed';
            } 
            // Check file size (max 5MB)
            elseif ($_FILES['receipt_image']['size'] > 5242880) {
                $error_message = 'File size must be less than 5MB';
            } 
            // Upload file
            elseif (move_uploaded_file($_FILES['receipt_image']['tmp_name'], $upload_file)) {
                $donation->receipt_image = $file_name;
                
                // Create donation
                if ($donation->create()) {
                    $success_message = 'Donation submitted successfully! Your donation will be verified by our staff.';
                } else {
                    $error_message = 'Failed to submit donation. Please try again.';
                }
            } else {
                $error_message = 'Failed to upload receipt image. Please try again.';
            }
        } else {
            $error_message = 'Receipt image is required';
        }
    }
}
?>

<div class="py-10">
    <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Make a Donation</h1>
        </div>
    </header>
    <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $success_message; ?></p>
                        <p class="mt-2">
                            <a href="donation_history.php" class="font-bold text-green-700 hover:underline">
                                View your donation history
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>

                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h2 class="text-lg leading-6 font-medium text-gray-900">Donation Information</h2>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Please fill out the form below to make a donation. All fields are required.
                        </p>
                    </div>
                    
                    <div class="border-t border-gray-200">
                        <form method="POST" enctype="multipart/form-data" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Amount -->
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700">
                                        Donation Amount (PHP)
                                    </label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">₱</span>
                                        </div>
                                        <input type="number" name="amount" id="amount" min="1" step="any"
                                            class="focus:ring-green-500 focus:border-green-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
                                            placeholder="0.00" required>
                                    </div>
                                </div>
                                
                                <!-- Payment Method -->
                                <div>
                                    <label for="payment_method" class="block text-sm font-medium text-gray-700">
                                        Payment Method
                                    </label>
                                    <select id="payment_method" name="payment_method"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        required>
                                        <option value="">Select payment method</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="gcash">GCash</option>
                                    </select>
                                </div>
                                
                                <!-- Bank Transfer Details -->
                                <div id="bank_details" class="md:col-span-2 bg-gray-50 p-4 rounded-md hidden">
                                    <h3 class="text-md font-medium text-gray-900 mb-2">Bank Transfer Details</h3>
                                    <p class="text-sm text-gray-700">
                                        Please transfer your donation to the following bank account:
                                    </p>
                                    <div class="mt-2 text-sm text-gray-700">
                                        <p><strong>Bank:</strong> Philippine National Bank (PNB)</p>
                                        <p><strong>Account Name:</strong> Oblatos Foundation Inc.</p>
                                        <p><strong>Account Number:</strong> 1234-5678-9012</p>
                                    </div>
                                </div>
                                
                                <!-- GCash Details -->
                                <div id="gcash_details" class="md:col-span-2 bg-gray-50 p-4 rounded-md hidden">
                                    <h3 class="text-md font-medium text-gray-900 mb-2">GCash Details</h3>
                                    <p class="text-sm text-gray-700">
                                        Please send your donation to the following GCash account:
                                    </p>
                                    <div class="mt-2 text-sm text-gray-700">
                                        <p><strong>Account Name:</strong> Oblatos Foundation Inc.</p>
                                        <p><strong>GCash Number:</strong> 0917-123-4567</p>
                                    </div>
                                </div>
                                
                                <!-- Reference Number -->
                                <div>
                                    <label for="reference_number" class="block text-sm font-medium text-gray-700">
                                        Reference Number
                                    </label>
                                    <input type="text" name="reference_number" id="reference_number"
                                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        placeholder="Transaction reference number" required>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Enter the reference number from your bank transfer or GCash transaction
                                    </p>
                                </div>
                                
                                <!-- Receipt Upload -->
                                <div>
                                    <label for="receipt_image" class="block text-sm font-medium text-gray-700">
                                        Receipt Image
                                    </label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                        <div class="space-y-1 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4h-12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-green-600 hover:text-green-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-green-500">
                                                    <span>Upload a file</span>
                                                    <input id="file-upload" name="receipt_image" type="file" class="sr-only" accept=".jpg,.jpeg,.png" required>
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                PNG, JPG, JPEG up to 5MB
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end">
                                <a href="donation_history.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-3">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Submit Donation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodSelect = document.getElementById('payment_method');
    const bankDetails = document.getElementById('bank_details');
    const gcashDetails = document.getElementById('gcash_details');
    
    paymentMethodSelect.addEventListener('change', function() {
        bankDetails.classList.add('hidden');
        gcashDetails.classList.add('hidden');
        
        if (this.value === 'bank_transfer') {
            bankDetails.classList.remove('hidden');
        } else if (this.value === 'gcash') {
            gcashDetails.classList.remove('hidden');
        }
    });
    
    // File upload preview
    const fileUpload = document.getElementById('file-upload');
    
    fileUpload.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.createElement('img');
                preview.src = e.target.result;
                preview.alt = 'Receipt preview';
                preview.classList.add('mt-2', 'h-40', 'rounded-md', 'mx-auto');
                
                const container = fileUpload.closest('div').parentNode;
                const existingPreview = container.querySelector('img');
                
                if (existingPreview) {
                    container.replaceChild(preview, existingPreview);
                } else {
                    container.appendChild(preview);
                }
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
