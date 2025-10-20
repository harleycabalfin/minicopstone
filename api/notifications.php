<?php
/**
 * Real-time Notifications API
 * Fetches unread notifications for the current user
 */

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

startSecureSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Fetch notifications
if ($is_admin) {
    // Admins see all notifications
    $query = "SELECT * FROM notifications 
              WHERE is_read = 0 
              ORDER BY created_at DESC 
              LIMIT 20";
    $result = $db->query($query);
} else {
    // Regular users see only their notifications
    $stmt = $db->prepare("SELECT * FROM notifications 
                          WHERE target_user = ? AND is_read = 0 
                          ORDER BY created_at DESC 
                          LIMIT 20");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['notification_id'],
        'type' => $row['notification_type'],
        'title' => $row['title'],
        'message' => $row['message'],
        'severity' => $row['severity'],
        'created_at' => date('M d, Y h:i A', strtotime($row['created_at'])),
        'time_ago' => getTimeAgo($row['created_at'])
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications
]);

// Helper function to calculate time ago
function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>