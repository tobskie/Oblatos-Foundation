<?php
require_once __DIR__ . '/../models/Notification.php';

// Get unread notifications count
$notification = new Notification($db);
$unread_count = $notification->get_unread_count($_SESSION['user_id']);
?>

<nav class="bg-white shadow">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex">
                <a href="dashboard.php" class="flex-shrink-0 flex items-center">
                    <img class="h-8 w-auto" src="../assets/img/logo.png" alt="Oblatos Foundation">
                </a>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="donate.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'donate.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Donate
                    </a>
                    <a href="history.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'history.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        History
                    </a>
                    <a href="notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Notifications
                        <?php if ($unread_count > 0): ?>
                            <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-y-[-0.5rem] bg-blue-600 rounded-full">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <div class="ml-3 relative">
                    <div class="flex items-center">
                        <span class="text-gray-700 text-sm font-medium mr-4">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                        <a href="../logout.php" class="text-gray-500 hover:text-gray-700">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav> 