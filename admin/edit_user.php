<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user model
$user = new User($db);

// Check if ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user->id = intval($_GET['id']);
    $success = $user->read_one();

    if (!$success) {
        $_SESSION['error_message'] = "User not found: ID " . $user->id;
        redirect('manage_users.php');
    }
    
    // User found, use $user for data (not $user_data)
    $user_data = $user; // For backward compatibility with existing code
} else {
    $_SESSION['error_message'] = "User ID not provided.";
    redirect('manage_users.php');
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the ID from the form
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id > 0) {
        // Get fresh user data
        $user = new User($db);
        $user->id = $id;
        
        if ($user->read_one()) {
            // Debug the POST values
            error_log('POST data for user ID ' . $id . ': ' . print_r($_POST, true));
            
            // Set values that need to be updated
            if (!empty($_POST['username'])) {
                $user->username = $_POST['username'];
            }
            
            if (!empty($_POST['full_name'])) {
                $user->full_name = $_POST['full_name'];
            }
            
            if (!empty($_POST['email'])) {
                $user->email = $_POST['email'];
            }
            
            // IMPORTANT: Always explicitly set the role from the form
            if (isset($_POST['role']) && in_array($_POST['role'], ['admin', 'cashier', 'donor'])) {
                $user->role = $_POST['role'];
                error_log('Setting role to: ' . $user->role);
            } else {
                error_log('No valid role found in POST data');
            }
            
            // Only update password if a new one is provided
            $new_password = $_POST['password'] ?? '';
            if (!empty($new_password)) {
                $user->password = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            // Debug: Store the role value for verification
            $_SESSION['debug_role'] = $user->role;
            $_SESSION['debug_username'] = $user->username;
            $_SESSION['debug_full_name'] = $user->full_name;
            $_SESSION['debug_email'] = $user->email;
            $_SESSION['debug_id'] = $user->id;
            
            // Update the user
            if ($user->update()) {
                // Create a clear success message
                $_SESSION['success_message'] = "User updated successfully."; 
                
                // Always redirect to the manage users page regardless of role
                redirect('manage_users.php');
            } else {
                $_SESSION['error_message'] = "Failed to update user.";
            }
        } else {
            $_SESSION['error_message'] = "User not found.";
            redirect('manage_users.php');
        }
    } else {
        $_SESSION['error_message'] = "Invalid user ID.";
        redirect('manage_users.php');
    }
}

// Success and error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<main class="flex-1 overflow-y-auto p-5">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?php echo $success_message; ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?php echo $error_message; ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Edit User Form -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <form action="edit_user.php?id=<?php echo isset($user_data->id) ? $user_data->id : ''; ?>" method="POST">
                <input type="hidden" name="id" value="<?php echo isset($user_data->id) ? $user_data->id : ''; ?>">
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" id="username"
                               value="<?php echo htmlspecialchars($user_data->username ?? ''); ?>"
                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="Leave unchanged to keep current username">
                    </div>
                    
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" id="full_name"
                               value="<?php echo htmlspecialchars($user_data->full_name ?? ''); ?>"
                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="Leave unchanged to keep current name">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email"
                               value="<?php echo htmlspecialchars($user_data->email ?? ''); ?>"
                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="Leave unchanged to keep current email">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="password"
                               class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="role" required 
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                            <option value="admin" <?php echo (isset($user_data->role) && $user_data->role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="cashier" <?php echo (isset($user_data->role) && $user_data->role === 'cashier') ? 'selected' : ''; ?>>Cashier</option>
                            <option value="donor" <?php echo (isset($user_data->role) && $user_data->role === 'donor') ? 'selected' : ''; ?>>Donor</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end">
                    <a href="<?php echo (isset($user_data->role) && $user_data->role === 'donor') ? 'manage_donors.php' : 'manage_users.php'; ?>"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mr-3">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
