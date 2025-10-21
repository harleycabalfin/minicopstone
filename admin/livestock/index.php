<?php
require_once '../../includes/db.php';
requireUser();

$db = getDB();

// --- Handle Add Batch Submission ---
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_batch'])) {
    $batch_number = trim($_POST['batch_number']);
    $breed = trim($_POST['breed']);
    $quantity = intval($_POST['quantity']);
    $age_in_weeks = intval($_POST['age_in_weeks']);
    $purchase_date = $_POST['purchase_date'];
    $weight = floatval($_POST['current_weight']);
    $added_by = $_SESSION['user_id'];
    $status = 'active';

    if (empty($batch_number) || empty($breed) || $quantity <= 0) {
        $error = "Please fill in all required fields correctly.";
    } else {
        $stmt = $db->prepare("INSERT INTO chickens (batch_number, breed, quantity, age_in_weeks, purchase_date, current_weight, added_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssiisdss", $batch_number, $breed, $quantity, $age_in_weeks, $purchase_date, $weight, $added_by, $status);

        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], "Added new batch: $batch_number", "chickens", $db->insert_id);
            $success = "New batch added successfully!";
        } else {
            $error = "Error adding batch: " . $db->error;
        }
    }
}

// --- Fetch All Batches ---
$query = "SELECT c.*, u.full_name AS added_by_name 
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

    <div class="container mx-auto p-6">
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-6"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Top Actions -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-800">Chicken Batches</h2>
            <div class="flex gap-3">
                <a href="mortality.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg font-semibold">
                    Record Mortality
                </a>
                <button id="openModal" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">
                    Add New Batch
                </button>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Breed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age (weeks)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchase Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight (kg)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Added By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['breed']); ?></td>
                                    <td class="px-6 py-4"><?php echo number_format($row['quantity']); ?></td>
                                    <td class="px-6 py-4"><?php echo $row['age_in_weeks']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php 
                                        $statusColors = [
                                            'active' => 'bg-green-100 text-green-800',
                                            'sold' => 'bg-blue-100 text-blue-800',
                                            'deceased' => 'bg-red-100 text-red-800'
                                        ];
                                        $colorClass = $statusColors[$row['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($row['purchase_date'])); ?></td>
                                    <td class="px-6 py-4"><?php echo $row['current_weight'] ? number_format($row['current_weight'], 2) : 'N/A'; ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['added_by_name'] ?? 'Unknown'); ?></td>
                                    <td class="px-6 py-4">
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
                <div class="text-center py-12 text-gray-500">No batches found.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="batchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg">
            <!-- Header -->
            <div class="flex justify-between items-center border-b px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800">Add New Chicken Batch</h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <!-- Form -->
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="add_batch" value="1">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch Number *</label>
                    <input type="text" name="batch_number" required 
                        placeholder="e.g., BATCH-2025-01" 
                        class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Breed *</label>
                    <input type="text" name="breed" required 
                        placeholder="e.g., Rhode Island Red, Layer, or Broiler" 
                        class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                        <input type="number" name="quantity" required min="1"
                            placeholder="e.g., 150"
                            class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age (weeks)</label>
                        <input type="number" name="age_in_weeks" min="0" 
                            placeholder="e.g., 2"
                            class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                    <input type="date" name="purchase_date" 
                        class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Average Weight (kg)</label>
                    <input type="number" name="current_weight" step="0.01"
                        placeholder="e.g., 0.85"
                        class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        placeholder="e.g., Purchased from ABC Hatchery, 5% mortality in first week."
                        class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" id="closeModalFooter" 
                        class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2 rounded-lg">Cancel</button>
                    <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold">
                        Save Batch
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script>
        const openBtn = document.getElementById('openModal');
        const closeBtn = document.getElementById('closeModal');
        const closeFooter = document.getElementById('closeModalFooter');
        const modal = document.getElementById('batchModal');

        openBtn.addEventListener('click', () => modal.classList.remove('hidden'));
        closeBtn.addEventListener('click', () => modal.classList.add('hidden'));
        closeFooter.addEventListener('click', () => modal.classList.add('hidden'));
        window.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });
    </script>
</body>
</html>
