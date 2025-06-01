<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../utils/Auth.php';
require_once '../models/Donation.php';

// Initialize Auth and check if user is logged in
$auth = new Auth();
$auth->requireAuth(['admin', 'cashier']);

// Get donation ID from URL
$donation_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$donation_id) {
    $_SESSION['error'] = "No donation ID provided.";
    header('Location: dashboard.php');
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Initialize Donation object
    $donation = new Donation($db);
    $donation->id = $donation_id;

    // Get donation details
    if (!$donation->read_one()) {
        throw new Exception("Donation not found");
    }

    // Get verifier name if donation is verified
    $verifier_name = '';
    if ($donation->verified_by) {
        $stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$donation->verified_by]);
        $verifier = $stmt->fetch(PDO::FETCH_ASSOC);
        $verifier_name = $verifier ? $verifier['full_name'] : 'Unknown';
    }

    // Handle verification action
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'verify') {
            $donation->status = 'verified';
            $donation->verified_by = $auth->getUserId();
            
            if ($donation->verify()) {
                $_SESSION['success'] = "Donation successfully verified.";
                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $donation_id);
                exit();
            } else {
                throw new Exception("Failed to verify donation");
            }
        } elseif ($_POST['action'] === 'reject') {
            $donation->status = 'rejected';
            $donation->verified_by = $auth->getUserId();
            
            if ($donation->verify()) {
                $_SESSION['success'] = "Donation has been rejected.";
                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $donation_id);
                exit();
            } else {
                throw new Exception("Failed to reject donation");
            }
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    error_log("Error in view_donation.php: " . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Donation - Oblatos Foundation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back button -->
            <a href="dashboard.php" class="inline-flex items-center mb-6 text-blue-600 hover:text-blue-800">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Donation Details -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">Donation Details</h2>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-gray-600 text-sm font-semibold mb-2">Donor Information</h3>
                            <p class="text-gray-800"><strong>Name:</strong> <?php echo htmlspecialchars($donation->donor_name); ?></p>
                            <p class="text-gray-800"><strong>Email:</strong> <?php echo htmlspecialchars($donation->donor_email); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-gray-600 text-sm font-semibold mb-2">Donation Information</h3>
                            <p class="text-gray-800"><strong>Amount:</strong> ₱<?php echo number_format($donation->amount, 2); ?></p>
                            <p class="text-gray-800"><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $donation->payment_method)); ?></p>
                            <p class="text-gray-800"><strong>Reference Number:</strong> <?php echo htmlspecialchars($donation->reference_number); ?></p>
                            <p class="text-gray-800"><strong>Status:</strong> 
                                <span class="inline-block px-2 py-1 text-sm rounded <?php echo $donation->status === 'verified' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst($donation->status); ?>
                                </span>
                            </p>
                            <p class="text-gray-800"><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($donation->created_at)); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($donation->status === 'verified'): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-gray-600 text-sm font-semibold mb-2">Verification Details</h3>
                            <p class="text-gray-800"><strong>Verified By:</strong> <?php echo htmlspecialchars($verifier_name); ?></p>
                            <?php if (isset($donation->verified_at)): ?>
                                <p class="text-gray-800"><strong>Verified At:</strong> <?php echo date('F j, Y g:i A', strtotime($donation->verified_at)); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($donation->payment_proof): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-gray-600 text-sm font-semibold mb-2">Payment Receipt</h3>
                            <div class="mt-2">
                                <img src="../uploads/receipts/<?php echo htmlspecialchars($donation->payment_proof); ?>" 
                                     alt="Payment Receipt" 
                                     class="max-w-md rounded shadow-lg">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($donation->status === 'pending'): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex space-x-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="verify">
                                    <button type="submit" 
                                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50"
                                            onclick="return confirm('Are you sure you want to verify this donation?')">
                                        Verify Donation
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" 
                                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50"
                                            onclick="return confirm('Are you sure you want to reject this donation?')">
                                        Reject Donation
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>