<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/header.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate User model
$user = new User($db);

// Initialize variables
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'] ?? 0;
    
    // Set user ID
    $user->id = $user_id;
    
    // Read user to check if they exist
    if ($user->read_one()) {
        // Cannot delete own account
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'You cannot delete your own account';
            redirect('admin/manage_users.php');
        }
        
        // Delete user
        if ($user->delete()) {
            $_SESSION['success_message'] = 'User deleted successfully';
        } else {
            $_SESSION['error_message'] = 'Failed to delete user';
        }
    } else {
        $_SESSION['error_message'] = 'User not found';
    }
    
    redirect('admin/manage_users.php');
}

// Handle user activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'] ?? 0;
    
    // Set user ID
    $user->id = $user_id;
    
    // Read user to check if they exist
    if ($user->read_one()) {
        // Cannot deactivate own account
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = 'You cannot deactivate your own account';
            redirect('admin/manage_users.php');
        }
        
        // Toggle user status
        if ($user->toggle_status()) {
            $status_message = ($user->status === 'active') ? 'activated' : 'deactivated';
            $_SESSION['success_message'] = 'User ' . $status_message . ' successfully';
        } else {
            $_SESSION['error_message'] = 'Failed to change user status';
        }
    } else {
        $_SESSION['error_message'] = 'User not found';
    }
    
    redirect('admin/manage_users.php');
}

// Get all users
$all_users = $user->read_all();
?>

<div class="py-10">
    <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Manage Users</h1>
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

                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between">
                        <div>
                            <h2 class="text-lg leading-6 font-medium text-gray-900">Users</h2>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                Manage all users in the system
                            </p>
                        </div>
                        <div>
                            <a href="add_user.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Add User
                            </a>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined On</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if ($all_users->rowCount() > 0): ?>
                                        <?php while ($row = $all_users->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $row['id']; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($row['username']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($row['email']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        <?php
                                                            if ($row['role'] === 'admin') {
                                                                echo 'bg-purple-100 text-purple-800';
                                                            } elseif ($row['role'] === 'cashier') {
                                                                echo 'bg-blue-100 text-blue-800';
                                                            } else {
                                                                echo 'bg-green-100 text-green-800';
                                                            }
                                                        ?>">
                                                        <?php echo ucfirst($row['role']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        <?php
                                                            if (isset($row['status']) && $row['status'] === 'inactive') {
                                                                echo 'bg-red-100 text-red-800';
                                                            } else {
                                                                echo 'bg-green-100 text-green-800';
                                                            }
                                                        ?>">
                                                        <?php echo ucfirst($row['status'] ?? 'active'); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2">Edit</a>
                                                    
                                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                                        <!-- Toggle Status Button -->
                                                        <form method="POST" class="inline mr-2">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" name="toggle_status" 
                                                                class="<?php echo isset($row['status']) && $row['status'] === 'inactive' ? 'text-green-600 hover:text-green-900' : 'text-orange-600 hover:text-orange-900'; ?>"
                                                                onclick="return confirm('Are you sure you want to <?php echo isset($row['status']) && $row['status'] === 'inactive' ? 'activate' : 'deactivate'; ?> this user?')">
                                                                <?php echo isset($row['status']) && $row['status'] === 'inactive' ? 'Activate' : 'Deactivate'; ?>
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Delete Button -->
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" name="delete_user" 
                                                                class="text-red-600 hover:text-red-900"
                                                                onclick="return confirm('Are you sure you want to delete this user?')">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                No users found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
