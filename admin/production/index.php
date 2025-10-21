<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

// Fetch all production records
$stmt = $db->query("
    SELECT production_id, batch_number, production_date, eggs_collected, damaged_eggs, egg_weight_kg, notes
    FROM egg_production
    ORDER BY production_date DESC
");
$productions = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Records - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - Production</h1>
        <div class="space-x-4">
            <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
            <a href="add.php" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold">Add Record</a>
        </div>
    </div>

    <div class="container mx-auto p-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Egg Production Records</h2>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Batch</th>
                        <th class="px-6 py-3">Eggs Collected</th>
                        <th class="px-6 py-3">Damaged</th>
                        <th class="px-6 py-3">Weight (kg)</th>
                        <th class="px-6 py-3">Notes</th>
                        <th class="px-6 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($productions) > 0): ?>
                        <?php foreach ($productions as $row): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-6 py-3"><?php echo htmlspecialchars($row['production_date']); ?></td>
                                <td class="px-6 py-3"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                <td class="px-6 py-3"><?php echo $row['eggs_collected']; ?></td>
                                <td class="px-6 py-3 text-red-600"><?php echo $row['damaged_eggs']; ?></td>
                                <td class="px-6 py-3"><?php echo number_format($row['egg_weight_kg'], 2); ?></td>
                                <td class="px-6 py-3 text-gray-600"><?php echo htmlspecialchars($row['notes']); ?></td>
                                <td class="px-6 py-3 text-center">
                                    <a href="edit.php?id=<?php echo $row['production_id']; ?>" class="text-blue-600 hover:underline">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-6 text-gray-500">No production records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
