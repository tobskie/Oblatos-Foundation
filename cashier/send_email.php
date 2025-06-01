<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/Mailer.php';
require_once '../includes/header.php';

// Initialize Mailer
Mailer::init();

// Check if user is logged in and is a cashier
if (!isLoggedIn() || !hasRole('cashier')) {
    redirect('auth/login.php');
}

// Check if donor ID is provided
if (!isset($_GET['donor_id']) || empty($_GET['donor_id'])) {
    $_SESSION['error_message'] = 'Invalid request: Donor ID is required';
    redirect('cashier/dashboard.php');
}

// Get donor ID
$donor_id = filter_var($_GET['donor_id'], FILTER_VALIDATE_INT);
if (!$donor_id) {
    $_SESSION['error_message'] = 'Invalid donor ID format';
    redirect('cashier/dashboard.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create user object for donor
$donor = new User($db);
$donor->id = $donor_id;

// Get donor details
if (!$donor->read_one() || $donor->role !== 'donor') {
    $_SESSION['error_message'] = 'Donor not found or invalid donor type';
    redirect('cashier/dashboard.php');
}

// Get cashier details
$cashier = new User($db);
$cashier->id = $_SESSION['user_id'];
if (!$cashier->read_one()) {
    $_SESSION['error_message'] = 'Error retrieving cashier information';
    redirect('cashier/dashboard.php');
}

// Variables for form
$subject = '';
$message = '';
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs using htmlspecialchars instead of FILTER_SANITIZE_STRING
    $subject = htmlspecialchars(trim($_POST['subject']), ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
    
    // Validate inputs
    if (empty($subject)) {
        $error_message = 'Email subject is required';
    } elseif (strlen($subject) > 255) {
        $error_message = 'Subject is too long (maximum 255 characters)';
    } elseif (empty($message)) {
        $error_message = 'Email message is required';
    } elseif (strlen($message) > 10000) {
        $error_message = 'Message is too long (maximum 10000 characters)';
    } else {
        try {
            // Additional headers
            $headers = array(
                'From' => APP_EMAIL,
                'Reply-To' => APP_EMAIL,
                'X-Mailer' => 'PHP/' . phpversion(),
                'Content-Type' => 'text/html; charset=UTF-8'
            );
            
            // Send email
            $email_sent = Mailer::sendCustomDonorEmail(
                $donor->email,
                $donor->full_name,
                $subject,
                $message,
                $cashier->full_name
            );
            
            if ($email_sent) {
                // Log successful email
                error_log(sprintf(
                    "Email sent successfully by cashier %s to donor %s (%s)",
                    $cashier->full_name,
                    $donor->full_name,
                    $donor->email
                ));
                
                // Success
                $_SESSION['success_message'] = 'Email sent successfully to ' . htmlspecialchars($donor->full_name);
                redirect('cashier/dashboard.php');
            }
        } catch (Exception $e) {
            // Log error
            error_log("Email sending failed: " . $e->getMessage());
            $error_message = 'Failed to send email. Please try again later or contact technical support.';
        }
    }
}
?>

<main class="flex-1 overflow-y-auto p-5">
    <div class="mb-8 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Send Email to Donor</h1>
        <a href="dashboard.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 ease-in-out">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>
    
    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p class="font-medium">Error</p>
        <p><?php echo htmlspecialchars($error_message); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p class="font-medium">Success</p>
        <p><?php echo htmlspecialchars($success_message); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b">
            <h2 class="text-lg font-semibold text-gray-700">Compose Email</h2>
            <div class="bg-blue-100 px-3 py-1 rounded-full">
                <span class="text-blue-800 text-sm font-medium">Sending as: <?php echo htmlspecialchars($cashier->full_name); ?></span>
            </div>
        </div>
        <div class="p-5">
            <div class="mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-md font-medium text-gray-700">Recipient Information</h3>
                    <div class="mt-2 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Name</p>
                            <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($donor->full_name); ?></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Email</p>
                            <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($donor->email); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="send_email.php?donor_id=<?php echo $donor_id; ?>" method="post" class="space-y-6">
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Email Subject</label>
                    <input type="text" name="subject" id="subject" 
                           value="<?php echo htmlspecialchars($subject); ?>" 
                           maxlength="255"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           placeholder="Enter email subject">
                    <p class="mt-1 text-sm text-gray-500">Maximum 255 characters</p>
                </div>
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Email Message</label>
                    <textarea name="message" id="message" 
                              rows="10" 
                              maxlength="10000"
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                              placeholder="Write your message here..."><?php echo htmlspecialchars($message); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">
                        Maximum 10000 characters. The message will be formatted with proper headers and footers.
                    </p>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" 
                            class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Send Email
                    </button>
                    <a href="dashboard.php" 
                       class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 ease-in-out">
                        Cancel
                    </a>
                </div>
            </form>
            
            <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-800">Email Guidelines:</h4>
                        <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                            <li>Be professional and courteous in all communications</li>
                            <li>Provide clear and accurate information</li>
                            <li>Respond to any questions or concerns promptly</li>
                            <li>Thank donors for their contributions and support</li>
                            <li>Avoid sharing sensitive or confidential information</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
