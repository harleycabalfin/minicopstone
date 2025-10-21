<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();

// Get all feed inventory with stock status
$query = "SELECT f.*, u.full_name as added_by_name,
          CASE 
            WHEN f.quantity_kg <= f.reorder_level THEN 'low'
            WHEN f.quantity_kg <= (f.reorder_level * 2) THEN 'medium'
            ELSE 'good'
          END as stock_status,
          CASE 
            WHEN f.expiry_date IS NOT NULL AND f.expiry_date <= CURDATE() THEN 'expired'
            WHEN f.expiry_date IS NOT NULL AND f.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
            ELSE 'good'
          END as expiry_status
          FROM feed_inventory f 
          LEFT JOIN users u ON f.added_by = u.user_id 
          ORDER BY f.created_at DESC";
$result = $db->query($query);

// Calculate total inventory value
$total_value_query = "SELECT SUM(quantity_kg * unit_price) as total_value FROM feed_inventory";
$total_value_result = $db->query($total_value_query);
$total_value = $total_value_result->fetch_assoc()['total_value'] ?? 0;

// Count low stock items
$low_stock_query = "SELECT COUNT(*) as low_stock_count FROM feed_inventory WHERE quantity_kg <= reorder_level";
$low_stock_result = $db->query($low_stock_query);
$low_stock_count = $low_stock_result->fetch_assoc()['low_stock_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed Inventory - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gray-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Feed Inventory</h1>
            <div class="flex gap-4">
                <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
                <a href="../logout.php" class="hover:text-gray-300">Logout (<?php echo $_SESSION['username']; ?>)</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm">Total Inventory Value</p>
                        <p class="text-2xl font-bold text-gray-900">₱<?php echo number_format($total_value, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm">Low Stock Alerts</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $low_stock_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm">Total Feed Types</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $result->num_rows; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Actions -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Feed Stock</h2>
            <div class="flex gap-3">
                <a href="consumption.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-lg font-semibold">
                    View Consumption
                </a>
                <a href="update.php" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-semibold">
                    Update Stock
                </a>
                <a href="add.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">
                    Add Feed Stock
                </a>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700">All Feed Inventory</h3>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feed Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity (kg)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $result->data_seek(0); // Reset pointer
                            while ($row = $result->fetch_assoc()): 
                                $stock_colors = [
                                    'low' => 'bg-red-100 text-red-800',
                                    'medium' => 'bg-yellow-100 text-yellow-800',
                                    'good' => 'bg-green-100 text-green-800'
                                ];
                                $stock_text = [
                                    'low' => 'Low Stock',
                                    'medium' => 'Medium',
                                    'good' => 'Good Stock'
                                ];
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($row['feed_type']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        <span class="font-medium"><?php echo number_format($row['quantity_kg'], 2); ?> kg</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $stock_colors[$row['stock_status']]; ?>">
                                            <?php echo $stock_text[$row['stock_status']]; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">₱<?php echo number_format($row['unit_price'], 2); ?></td>
                                    <td class="px-6 py-4 text-gray-700">₱<?php echo number_format($row['quantity_kg'] * $row['unit_price'], 2); ?></td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($row['supplier'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($row['expiry_date']): ?>
                                            <?php if ($row['expiry_status'] === 'expired'): ?>
                                                <span class="text-red-600 font-semibold"><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?> (Expired)</span>
                                            <?php elseif ($row['expiry_status'] === 'expiring'): ?>
                                                <span class="text-orange-600 font-semibold"><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?> (Soon)</span>
                                            <?php else: ?>
                                                <span class="text-gray-700"><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700"><?php echo number_format($row['reorder_level'], 2); ?> kg</td>
                                    <td class="px-6 py-4">
                                        <a href="update.php?id=<?php echo $row['feed_id']; ?>" class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm mr-2">
                                            Update
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No feed inventory found</h3>
                    <p class="text-gray-500 mb-6">Start by adding your first feed stock</p>
                    <a href="add.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-block">
                        Add Feed Stock
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>