<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Notification.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize notification object
$notification = new Notification($db);

// Check if user is logged in and is a donor
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: ../login.php');
    exit();
}

// Handle mark as read action
if (isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    $notification->mark_as_read($notification_id, $_SESSION['user_id']);
}

// Handle mark all as read action
if (isset($_POST['mark_all_read'])) {
    $notification->mark_all_as_read($_SESSION['user_id']);
}

// Get notifications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$notifications = $notification->get_user_notifications($_SESSION['user_id'], $limit, $offset);
$unread_count = $notification->get_unread_count($_SESSION['user_id']);

// Include header
include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Notifications</h1>
        <?php if ($unread_count > 0): ?>
        <form method="POST" class="inline">
            <button type="submit" name="mark_all_read" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Mark All as Read
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($notifications && $notifications->rowCount() > 0): ?>
        <div class="space-y-4">
            <?php while ($row = $notifications->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="bg-white rounded-lg shadow p-4 <?php echo !$row['is_read'] ? 'border-l-4 border-blue-500' : ''; ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-lg <?php echo !$row['is_read'] ? 'text-blue-600' : ''; ?>">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </h3>
                            <p class="text-gray-600 mt-1">
                                <?php echo htmlspecialchars($row['message']); ?>
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                <?php echo date('F j, Y g:i A', strtotime($row['created_at'])); ?>
                            </p>
                        </div>
                        <?php if (!$row['is_read']): ?>
                            <form method="POST" class="ml-4">
                                <input type="hidden" name="notification_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="mark_read" class="text-blue-500 hover:text-blue-600">
                                    Mark as Read
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-8">
            <p class="text-gray-600">No notifications yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?> 