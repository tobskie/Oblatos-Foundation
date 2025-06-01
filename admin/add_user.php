<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        $error_message = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address';
    } else {
        // Get database connection
        $database = new Database();
        $db = $database->getConnection();
        
        // Create user object
        $user = new User($db);
        
        // Set user properties
        $user->full_name = $full_name;
        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        $user->role = $role;
        
        // Check if email exists
        if ($user->emailExists()) {
            $error_message = 'Email already exists';
        } else {
            // Create user
            if ($user->create()) {
                $success_message = 'User created successfully';
            } else {
                $error_message = 'Failed to create user';
            }
        }
    }
}
?>

<div class="py-10">
    <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Add New User</h1>
        </div>
    </header>
    <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $success_message; ?></p>
                        <p class="mt-2">
                            <a href="manage_users.php" class="font-bold text-green-700 hover:underline">
                                Go back to user management
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
                        <h2 class="text-lg leading-6 font-medium text-gray-900">User Information</h2>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Create a new user account
                        </p>
                    </div>
                    
                    <div class="border-t border-gray-200">
                        <form method="POST" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Full Name -->
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">
                                        Full Name
                                    </label>
                                    <input type="text" name="full_name" id="full_name" 
                                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        required>
                                </div>
                                
                                <!-- Username -->
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700">
                                        Username
                                    </label>
                                    <input type="text" name="username" id="username" 
                                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        required>
                                </div>
                                
                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">
                                        Email
                                    </label>
                                    <input type="email" name="email" id="email" 
                                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        required>
                                </div>
                                
                                <!-- Role -->
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">
                                        Role
                                    </label>
                                    <select id="role" name="role"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                        required>
                                        <option value="">Select role</option>
                                        <option value="admin">Admin</option>
                                        <option value="cashier">Cashier</option>
                                        <option value="donor">Donor</option>
                                    </select>
                                </div>
                                
                                <!-- Password -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">
                                        Password
                                    </label>
                                    <input type="password" name="password" id="password" 
                                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        required>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                                        Confirm Password
                                    </label>
                                    <input type="password" name="confirm_password" id="confirm_password" 
                                        class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                        required>
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end">
                                <a href="manage_users.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-3">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
