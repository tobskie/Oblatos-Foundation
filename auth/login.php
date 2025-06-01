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

// Create a default admin account if none exists
$database = new Database();
$db = $database->getConnection();


// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $errorMessage = 'Please enter both email and password';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        
        if ($user->login($email, $password)) {
            // Set session variables
            $_SESSION['user_id'] = $user->id;
            $_SESSION['name'] = $user->full_name;
            $_SESSION['email'] = $user->email;
            $_SESSION['role'] = $user->role;
            
            // Redirect based on role
            switch ($_SESSION['role']) {
                case 'admin':
                    redirect('admin/dashboard.php');
                    break;
                case 'cashier':
                    redirect('cashier/dashboard.php');
                    break;
                case 'donor':
                    redirect('donor/dashboard.php');
                    break;
                default:
                    redirect('');
            }
        } else {
            $errorMessage = 'Invalid credentials';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
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
                    Please sign in to your account
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
            
            <form class="mt-8 space-y-6" action="login.php" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                               placeholder="Email address">
                    </div>
                    <div class="relative">
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm pr-10" 
                               placeholder="Password">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer focus:outline-none">
                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" 
                               class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="register.php" class="font-medium text-green-600 hover:text-green-500">
                            Need an account?
                        </a>
                    </div>
                </div>
                
                <div class="text-center mt-2">
                    <div class="text-sm">
                        <a href="forgot_password.php" class="font-medium text-green-600 hover:text-green-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>