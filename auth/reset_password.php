<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('');
}

$errorMessage = '';
$successMessage = '';

// Check if email and token are provided
if (!isset($_GET['email']) || !isset($_GET['token']) || empty($_GET['email']) || empty($_GET['token'])) {
    $errorMessage = 'Invalid password reset link. Please request a new one.';
} else {
    $email = $_GET['email'];
    $token = $_GET['token'];
    
    // Validate token
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    if (!$user->verify_reset_token($email, $token)) {
        $errorMessage = 'This password reset link is invalid or has expired. Please request a new one.';
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errorMessage)) {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($password)) {
            $errorMessage = 'Please enter a password';
        } elseif (strlen($password) < 6) {
            $errorMessage = 'Password must be at least 6 characters';
        } elseif ($password !== $confirm_password) {
            $errorMessage = 'Passwords do not match';
        } else {
            // Reset password
            if ($user->reset_password_by_token($email, $token, $password)) {
                $successMessage = 'Your password has been reset successfully. You can now <a href="login.php" class="text-green-600 font-medium">login</a> with your new password.';
            } else {
                $errorMessage = 'Failed to reset password. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
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
                    Reset Your Password
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
            <?php else: ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <p class="mb-4 text-gray-600">
                        Please enter your new password below.
                    </p>
                    
                    <?php if (empty($errorMessage) || strpos($errorMessage, 'do not match') !== false || strpos($errorMessage, 'at least 6') !== false): ?>
                    <form class="mt-4 space-y-6" action="reset_password.php?email=<?php echo urlencode($email); ?>&token=<?php echo urlencode($token); ?>" method="POST">
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                                   placeholder="Enter your new password">
                            <p class="mt-1 text-xs text-gray-500">Must be at least 6 characters long</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                                   placeholder="Confirm your new password">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <button type="submit" 
                                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                                Reset Password
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <div class="mt-6 text-center">
                        <div class="text-sm">
                            <a href="login.php" class="font-medium text-green-600 hover:text-green-500">
                                Back to login
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>
