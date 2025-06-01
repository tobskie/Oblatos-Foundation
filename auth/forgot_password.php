<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/Mailer.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('');
}

$errorMessage = '';
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $errorMessage = 'Please enter your email address';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        // Check if email exists
        $user->email = $email;
        if ($user->emailExists()) {
            // Generate reset token
            $token = $user->generate_reset_token($email);
            
            if ($token) {
                // Get user details for email
                $user->read_one();
                
                // Create reset link
                $reset_link = APP_URL . "/auth/reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token);
                
                // Send reset email
                $email_sent = Mailer::sendPasswordResetEmail(
                    $email,
                    $user->full_name,
                    $token,
                    $reset_link
                );
                
                if ($email_sent) {
                    $successMessage = 'Password reset instructions have been sent to your email address';
                } else {
                    $errorMessage = 'Failed to send reset email. Please try again later.';
                }
            } else {
                $errorMessage = 'There was a problem processing your request. Please try again later.';
            }
        } else {
            // Don't reveal that email doesn't exist for security
            $successMessage = 'If your email is registered in our system, you will receive password reset instructions shortly';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    <span class="text-green-600">Oblatos Foundation</span> <span class="text-gray-500">by OSJ</span>
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Forgot Your Password?
                </p>
            </div>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?php echo $errorMessage; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p><?php echo $successMessage; ?></p>
                </div>
            <?php endif; ?>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p class="mb-4 text-gray-600">
                    Enter your email address below and we'll send you a link to reset your password.
                </p>
                
                <form class="mt-4 space-y-6" action="forgot_password.php" method="POST">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Enter your email">
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                            Send Reset Link
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <div class="text-sm">
                        <a href="login.php" class="font-medium text-green-600 hover:text-green-500">
                            Back to login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
