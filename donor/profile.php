<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Create user object
$user = new User($db);
$user->id = $_SESSION['user_id'];

// Get user details
$user->read_one();

// Handle form submission for profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Set user properties
    $user->full_name = htmlspecialchars(strip_tags($_POST['full_name']));
    $user->email = htmlspecialchars(strip_tags($_POST['email']));
    
    // Update user
    if ($user->update()) {
        // Update session variables
        $_SESSION['name'] = $user->full_name;
        $_SESSION['email'] = $user->email;
        
        $success_message = 'Profile updated successfully';
    } else {
        $error_message = 'Failed to update profile';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match';
    } else {
        // Verify current password
        if ($user->login($_SESSION['email'], $current_password)) {
            if ($user->update_password($new_password)) {
                $success_message = 'Password changed successfully';
            } else {
                $error_message = 'Failed to change password';
            }
        } else {
            $error_message = 'Current password is incorrect';
        }
    }
}
?>

<div class="py-10">
    <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold leading-tight text-gray-900">My Profile</h1>
        </div>
    </header>
    <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Profile Update Form -->
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Information</h3>
                                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Update your personal details</p>
                                </div>
                                <div class="border-t border-gray-200">
                                    <form method="POST" class="p-6">
                                        <div class="mb-4">
                                            <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                            <input type="text" name="full_name" id="full_name" 
                                                class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" 
                                                value="<?php echo htmlspecialchars($user->full_name); ?>" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                            <input type="email" name="email" id="email" 
                                                class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" 
                                                value="<?php echo htmlspecialchars($user->email); ?>" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                                            <input type="text" id="role" 
                                                class="mt-1 bg-gray-100 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" 
                                                value="<?php echo ucfirst(htmlspecialchars($user->role)); ?>" readonly>
                                        </div>
                                        
                                        <div class="flex justify-end">
                                            <button type="submit" name="update_profile" 
                                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Password Change Form -->
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">Change Password</h3>
                                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Update your account password</p>
                                </div>
                                <div class="border-t border-gray-200">
                                    <form method="POST" class="p-6">
                                        <div class="mb-4">
                                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                            <input type="password" name="current_password" id="current_password" 
                                                class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                            <input type="password" name="new_password" id="new_password" 
                                                class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                            <input type="password" name="confirm_password" id="confirm_password" 
                                                class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                                        </div>
                                        
                                        <div class="flex justify-end">
                                            <button type="submit" name="change_password" 
                                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                Change Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
