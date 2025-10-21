<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

// --- Handle optional log clear ---
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $db->query("TRUNCATE TABLE system_logs");
    // Optional: add this only if you have logAction() defined elsewhere
    if (function_exists('logAction')) {
        logAction($_SESSION['user_id'], "Cleared all system logs", "system_logs", 0);
    }
    header("Location: index.php");
    exit;
}

// --- Filters & Search ---
$search = $_GET['search'] ?? '';
$filter_user = $_GET['user'] ?? '';

$query = "SELECT l.log_id, l.user_id, u.full_name, l.action, l.table_affected, l.record_id, l.ip_address, l.created_at 
          FROM system_logs l 
          LEFT JOIN users u ON l.user_id = u.user_id 
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (l.action LIKE CONCAT('%', ?, '%') OR l.table_affected LIKE CONCAT('%', ?, '%'))";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

if (!empty($filter_user)) {
    $query .= " AND l.user_id = ?";
    $params[] = $filter_user;
    $types .= "i";
}

$query .= " ORDER BY l.created_at DESC LIMIT 200";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch users for filter dropdown
$users = $db->query("SELECT user_id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>System Logs - <?php echo SITE_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- Header -->
  <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - System Logs</h1>
    <div class="flex gap-4">
      <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
      <a href="?clear=1" 
         onclick="return confirm('Are you sure you want to clear all logs?');"
         class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold">
         Clear Logs
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="container mx-auto px-6 py-8">
    <h2 class="text-xl font-semibold mb-6 text-gray-700">System Activity Logs</h2>

    <!-- Filters -->
    <form method="GET" class="bg-white p-4 rounded-lg shadow-md mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-gray-700 text-sm font-medium mb-1">Search</label>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500"
               placeholder="Search by action or table name">
      </div>

      <div>
        <label class="block text-gray-700 text-sm font-medium mb-1">Filter by User</label>
        <select name="user" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500">
          <option value="">All Users</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo $u['user_id']; ?>" 
              <?php echo $filter_user == $u['user_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($u['full_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="flex items-end">
        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-semibold w-full">
          Apply Filters
        </button>
      </div>
    </form>

    <!-- Logs Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
          <tr>
            <th class="px-6 py-3">Date</th>
            <th class="px-6 py-3">User</th>
            <th class="px-6 py-3">Action</th>
            <th class="px-6 py-3">Table</th>
            <th class="px-6 py-3">Record ID</th>
            <th class="px-6 py-3">IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($logs) > 0): ?>
            <?php foreach ($logs as $log): ?>
              <tr class="border-t hover:bg-gray-50">
                <td class="px-6 py-3 text-gray-500"><?php echo htmlspecialchars($log['created_at']); ?></td>
                <td class="px-6 py-3 text-gray-800">
                  <?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?>
                </td>
                <td class="px-6 py-3 text-gray-900"><?php echo htmlspecialchars($log['action']); ?></td>
                <td class="px-6 py-3 text-gray-700"><?php echo htmlspecialchars($log['table_affected']); ?></td>
                <td class="px-6 py-3 text-gray-500"><?php echo htmlspecialchars($log['record_id']); ?></td>
                <td class="px-6 py-3 text-gray-500"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center py-6 text-gray-500">No logs found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
