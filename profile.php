<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate User model
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_one();

// Include header
require_once 'includes/header.php';
?>

<main class="flex-1 overflow-y-auto p-5">
    <!-- Profile Banner -->
    <div class="bg-white border-l-4 border-green-500 p-4 mb-6 rounded shadow-md">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-lg font-medium">User Profile</p>
                <p class="text-sm">View and manage your account information</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Account Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <div class="text-gray-900 font-medium"><?php echo htmlspecialchars($user->full_name); ?></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <div class="text-gray-900 font-medium"><?php echo htmlspecialchars($user->username); ?></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="text-gray-900 font-medium"><?php echo htmlspecialchars($user->email); ?></div>
                    </div>
                </div>
                
                <div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?php echo $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                  ($user->role === 'cashier' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); ?>">
                            <?php echo ucfirst(htmlspecialchars($user->role)); ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?php echo $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst(htmlspecialchars($user->status ?? 'active')); ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Created</label>
                        <div class="text-gray-900 font-medium">
                            <?php echo date('F j, Y', strtotime($user->created_at)); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Unique Identifier</h3>
                <div class="bg-gray-50 p-4 rounded border border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-1">User UUID</label>
                    <div class="font-mono text-sm break-all"><?php echo htmlspecialchars($user->uuid ?? 'Not available'); ?></div>
                    <p class="mt-2 text-xs text-gray-500">This is your unique identifier in our system. It follows database normalization rules and is used for external references.</p>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <a href="change_password.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Change Password
                </a>
            </div>
        </div>
    </div>
</main>

<?php
// Include footer
require_once 'includes/footer.php';
?>
