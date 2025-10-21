<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

// Fetch all sales
$stmt = $db->query("
    SELECT sale_id, sale_type, batch_number, quantity, unit_price, total_amount, customer_name, sale_date, payment_status
    FROM sales
    ORDER BY sale_date DESC
");
$sales = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Records - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Sales</h1>
        <div class="space-x-4">
            <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
            <a href="add.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold">Add Sale</a>
        </div>
    </div>

    <!-- Table -->
    <div class="container mx-auto p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Sales Records</h2>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Batch</th>
                        <th class="px-6 py-3">Quantity</th>
                        <th class="px-6 py-3">Total (₱)</th>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sales) > 0): ?>
                        <?php foreach ($sales as $row): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-6 py-3"><?php echo htmlspecialchars($row['sale_date']); ?></td>
                                <td class="px-6 py-3 capitalize"><?php echo htmlspecialchars($row['sale_type']); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                <td class="px-6 py-3"><?php echo $row['quantity']; ?></td>
                                <td class="px-6 py-3 font-semibold text-green-700">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded 
                                        <?php echo $row['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : ($row['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700'); ?>">
                                        <?php echo ucfirst($row['payment_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <a href="invoice.php?id=<?php echo $row['sale_id']; ?>" class="text-blue-600 hover:underline">Invoice</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-6 text-gray-500">No sales records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
