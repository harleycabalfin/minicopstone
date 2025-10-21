<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();

// Get all chicken batches
$query = "SELECT c.*, u.full_name as added_by_name 
          FROM chickens c 
          LEFT JOIN users u ON c.added_by = u.user_id 
          ORDER BY c.created_at DESC";
$result = $db->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livestock Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gray-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Livestock</h1>
            <div class="flex gap-4">
                <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
                <a href="../logout.php" class="hover:text-gray-300">Logout (<?php echo $_SESSION['username']; ?>)</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <!-- Top Actions -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Chicken Batches</h2>
            <div class="flex gap-3">
                <a href="mortality.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg font-semibold">
                    Record Mortality
                </a>
                <a href="add.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">
                    Add New Batch
                </a>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700">All Livestock Batches</h3>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age (weeks)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (kg)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($row['batch_number']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($row['breed']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo number_format($row['quantity']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo $row['age_in_weeks']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $statusColors = [
                                            'active' => 'bg-green-100 text-green-800',
                                            'sold' => 'bg-blue-100 text-blue-800',
                                            'deceased' => 'bg-red-100 text-red-800'
                                        ];
                                        $colorClass = $statusColors[$row['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $colorClass; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo date('M d, Y', strtotime($row['purchase_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo $row['current_weight'] ? number_format($row['current_weight'], 2) : 'N/A'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($row['added_by_name'] ?? 'Unknown'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="edit.php?id=<?php echo $row['chicken_id']; ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                                            Edit
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No livestock batches found</h3>
                    <p class="text-gray-500 mb-6">Start by adding your first chicken batch</p>
                    <a href="add.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold inline-block">
                        Add New Batch
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>