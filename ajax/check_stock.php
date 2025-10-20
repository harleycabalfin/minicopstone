<?php
/**
 * AJAX Stock Checker
 * Returns real-time stock availability
 */

require_once '../config/database.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
startSecureSession();
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = getDB();

// Get feed_id from GET parameter
$feed_id = isset($_GET['feed_id']) ? (int)$_GET['feed_id'] : 0;

if ($feed_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid feed ID'
    ]);
    exit();
}

// Fetch current stock information
$stmt = $db->prepare("SELECT feed_type, quantity_kg, reorder_level, unit_price 
                      FROM feed_inventory 
                      WHERE feed_id = ?");
$stmt->bind_param("i", $feed_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Feed type not found'
    ]);
    exit();
}

$feedData = $result->fetch_assoc();

// Determine stock status
$stockStatus = 'normal';
$statusMessage = 'Stock level is adequate';

if ($feedData['quantity_kg'] < FEED_CRITICAL_STOCK_THRESHOLD) {
    $stockStatus = 'critical';
    $statusMessage = 'Critical stock level - immediate reorder required';
} elseif ($feedData['quantity_kg'] < $feedData['reorder_level']) {
    $stockStatus = 'low';
    $statusMessage = 'Stock below reorder level';
}

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => [
        'feed_type' => $feedData['feed_type'],
        'current_stock' => (float)$feedData['quantity_kg'],
        'reorder_level' => (float)$feedData['reorder_level'],
        'unit_price' => (float)$feedData['unit_price'],
        'stock_status' => $stockStatus,
        'status_message' => $statusMessage,
        'is_available' => $feedData['quantity_kg'] > 0
    ]
]);
?>