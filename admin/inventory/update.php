<?php
/**
 * Automated Inventory Deduction Module
 * Records feed consumption and automatically deducts from inventory
 */

require_once '../../config/database.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

requireAdmin();
checkSessionTimeout();

$db = getDB();
$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Fetch available feed types
$feedQuery = "SELECT feed_id, feed_type, quantity_kg FROM feed_inventory WHERE quantity_kg > 0 ORDER BY feed_type";
$feedResult = $db->query($feedQuery);
$availableFeeds = $feedResult->fetch_all(MYSQLI_ASSOC);

// Fetch active batches
$batchQuery = "SELECT DISTINCT batch_number FROM chickens WHERE status = 'active' ORDER BY batch_number";
$batchResult = $db->query($batchQuery);
$activeBatches = $batchResult->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_consumption'])) {
    $feed_id = (int)$_POST['feed_id'];
    $batch_number = sanitizeInput($_POST['batch_number']);
    $quantity_used = (float)$_POST['quantity_used'];
    $consumption_date = sanitizeInput($_POST['consumption_date']);
    $notes = sanitizeInput($_POST['notes']);
    
    // Validation
    if (empty($feed_id) || empty($batch_number) || $quantity_used <= 0) {
        $error = "Please fill all required fields with valid values.";
    } else {
        // Start transaction for data integrity
        $db->begin_transaction();
        
        try {
            // Check current feed stock
            $checkStmt = $db->prepare("SELECT feed_type, quantity_kg, reorder_level FROM feed_inventory WHERE feed_id = ? FOR UPDATE");
            $checkStmt->bind_param("i", $feed_id);
            $checkStmt->execute();
            $feedData = $checkStmt->get_result()->fetch_assoc();
            
            if (!$feedData) {
                throw new Exception("Feed type not found.");
            }
            
            if ($feedData['quantity_kg'] < $quantity_used) {
                throw new Exception("Insufficient stock. Available: " . number_format($feedData['quantity_kg'], 2) . " kg");
            }
            
            // Record consumption
            $insertStmt = $db->prepare("INSERT INTO feed_consumption (feed_id, batch_number, quantity_used, consumption_date, recorded_by, notes) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("isdsds", $feed_id, $batch_number, $quantity_used, $consumption_date, $user_id, $notes);
            
            if (!$insertStmt->execute()) {
                throw new Exception("Failed to record consumption.");
            }
            
            // Automatically deduct from inventory
            $newQuantity = $feedData['quantity_kg'] - $quantity_used;
            $updateStmt = $db->prepare("UPDATE feed_inventory SET quantity_kg = ? WHERE feed_id = ?");
            $updateStmt->bind_param("di", $newQuantity, $feed_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update inventory.");
            }
            
            // Log the action
            logActivity($user_id, "Recorded feed consumption: {$quantity_used}kg of {$feedData['feed_type']}", "feed_consumption", $db->insert_id);
            
            // Check if stock is below reorder level and create notification
            if ($newQuantity < $feedData['reorder_level']) {
                $severity = $newQuantity < (FEED_CRITICAL_STOCK_THRESHOLD) ? 'critical' : 'warning';
                $notifTitle = "Low Stock Alert: {$feedData['feed_type']}";
                $notifMessage = "Current stock: " . number_format($newQuantity, 2) . " kg. Reorder level: " . number_format($feedData['reorder_level'], 2) . " kg.";
                
                $notifStmt = $db->prepare("INSERT INTO notifications (notification_type, title, message, severity) 
                                           VALUES ('low_stock', ?, ?, ?)");
                $notifStmt->bind_param("sss", $notifTitle, $notifMessage, $severity);
                $notifStmt->execute();
                
                logActivity($user_id, "Low stock notification created for {$feedData['feed_type']}", "notifications", $db->insert_id);
            }
            
            // Commit transaction
            $db->commit();
            
            $success = "Feed consumption recorded successfully. Inventory automatically updated. New stock: " . number_format($newQuantity, 2) . " kg";
            
            // Refresh feed list
            $feedResult = $db->query($feedQuery);
            $availableFeeds = $feedResult->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed Consumption - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../dashboard.php" class="text-xl font-bold">🐔 Poultry Farm System</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-8">
        <!-- Breadcrumb -->
        <div class="mb-6">
            <nav class="text-sm">
                <a href="../dashboard.php" class="text-green-600 hover:underline">Dashboard</a>
                <span class="mx-2">/</span>
                <a href="index.php" class="text-green-600 hover:underline">Inventory</a>
                <span class="mx-2">/</span>
                <span class="text-gray-600">Record Consumption</span>
            </nav>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Record Feed Consumption</h2>
            <p class="text-gray-600 mb-6">Enter feed consumption details. Inventory will be automatically deducted.</p>

            <!-- Success/Error Messages -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <p class="font-medium"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    <p class="font-medium"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <!-- Consumption Form -->
            <form method="POST" action="" class="space-y-6" id="consumptionForm">
                <!-- Feed Type Selection -->
                <div>
                    <label for="feed_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Feed Type <span class="text-red-500">*</span>
                    </label>
                    <select name="feed_id" 
                            id="feed_id" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            onchange="updateStockInfo()">
                        <option value="">-- Select Feed Type --</option>
                        <?php foreach ($availableFeeds as $feed): ?>
                            <option value="<?php echo $feed['feed_id']; ?>" 
                                    data-stock="<?php echo $feed['quantity_kg']; ?>">
                                <?php echo htmlspecialchars($feed['feed_type']); ?> 
                                (Available: <?php echo number_format($feed['quantity_kg'], 2); ?> kg)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="stockInfo" class="text-sm text-gray-500 mt-2"></p>
                </div>

                <!-- Batch Number -->
                <div>
                    <label for="batch_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Batch Number <span class="text-red-500">*</span>
                    </label>
                    <select name="batch_number" 
                            id="batch_number" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">-- Select Batch --</option>
                        <?php foreach ($activeBatches as $batch): ?>
                            <option value="<?php echo htmlspecialchars($batch['batch_number']); ?>">
                                <?php echo htmlspecialchars($batch['batch_number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quantity Used -->
                <div>
                    <label for="quantity_used" class="block text-sm font-medium text-gray-700 mb-2">
                        Quantity Used (kg) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="quantity_used" 
                           id="quantity_used" 
                           step="0.01" 
                           min="0.01"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Enter quantity in kg"
                           onchange="validateQuantity()">
                    <p id="quantityWarning" class="text-sm text-red-500 mt-2 hidden"></p>
                </div>

                <!-- Consumption Date -->
                <div>
                    <label for="consumption_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Consumption Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           name="consumption_date" 
                           id="consumption_date" 
                           required
                           value="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes (Optional)
                    </label>
                    <textarea name="notes" 
                              id="notes" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              placeholder="Additional notes about consumption..."></textarea>
                </div>

                <!-- Warning Box -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">Automated Inventory Deduction</p>
                            <p class="text-sm text-yellow-700 mt-1">
                                The system will automatically deduct the specified quantity from inventory and notify you if stock falls below the reorder level.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="index.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Cancel
                    </a>
                    <button type="submit" 
                            name="record_consumption"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Record Consumption & Update Inventory
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Update stock information when feed type changes
        function updateStockInfo() {
            const select = document.getElementById('feed_id');
            const stockInfo = document.getElementById('stockInfo');
            const selected = select.options[select.selectedIndex];
            
            if (selected.value) {
                const stock = parseFloat(selected.dataset.stock);
                stockInfo.innerHTML = `<strong>Current Stock:</strong> ${stock.toFixed(2)} kg`;
                stockInfo.className = stock < <?php echo FEED_LOW_STOCK_THRESHOLD; ?> 
                    ? 'text-sm text-red-500 mt-2' 
                    : 'text-sm text-gray-500 mt-2';
            } else {
                stockInfo.innerHTML = '';
            }
        }

        // Validate quantity against available stock
        function validateQuantity() {
            const select = document.getElementById('feed_id');
            const quantityInput = document.getElementById('quantity_used');
            const warning = document.getElementById('quantityWarning');
            
            if (!select.value) {
                warning.textContent = 'Please select a feed type first';
                warning.classList.remove('hidden');
                return;
            }
            
            const selected = select.options[select.selectedIndex];
            const availableStock = parseFloat(selected.dataset.stock);
            const requestedQty = parseFloat(quantityInput.value);
            
            if (requestedQty > availableStock) {
                warning.textContent = `Insufficient stock! Available: ${availableStock.toFixed(2)} kg`;
                warning.classList.remove('hidden');
                quantityInput.setCustomValidity('Insufficient stock');
            } else {
                warning.classList.add('hidden');
                quantityInput.setCustomValidity('');
            }
        }

        // Real-time stock checking via AJAX
        document.getElementById('feed_id').addEventListener('change', function() {
            const feedId = this.value;
            if (feedId) {
                fetch('../../ajax/check_stock.php?feed_id=' + feedId)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Stock check:', data);
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    </script>
</body>
</html>