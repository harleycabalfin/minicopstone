<?php
require_once '../../includes/db.php';
requireUser();
$db = getDB();

// Fetch all users including inactive
$stmt = $db->query("
    SELECT user_id, full_name, username, role, status, created_at
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - <?php echo SITE_NAME; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- Header -->
  <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold"><?php echo SITE_NAME; ?> - User Management</h1>
    <div class="flex gap-4">
      <a href="../dashboard.php" class="hover:text-gray-300">Dashboard</a>
      <a href="add.php" class="bg-orange-500 hover:bg-orange-600 px-4 py-2 rounded-lg text-white font-semibold">Add User</a>
    </div>
  </div>

  <!-- User Table -->
  <div class="container mx-auto px-6 py-8">
    <h2 class="text-xl font-semibold mb-4 text-gray-700">User List</h2>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
          <tr>
            <th class="px-6 py-3">Name</th>
            <th class="px-6 py-3">Username</th>
            <th class="px-6 py-3">Role</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Created</th>
            <th class="px-6 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($users) > 0): ?>
            <?php foreach ($users as $u): ?>
              <tr class="border-t hover:bg-gray-50">
                <td class="px-6 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($u['full_name']); ?></td>
                <td class="px-6 py-3 text-gray-700"><?php echo htmlspecialchars($u['username']); ?></td>
                <td class="px-6 py-3 capitalize text-gray-600"><?php echo htmlspecialchars($u['role']); ?></td>
                
                <!-- Status Badge -->
                <td class="px-6 py-3">
                  <?php if ($u['status'] === 'active'): ?>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                      🟢 Active
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                      🔴 Inactive
                    </span>
                  <?php endif; ?>
                </td>

                <td class="px-6 py-3 text-gray-500 text-sm"><?php echo htmlspecialchars($u['created_at']); ?></td>

                <!-- Actions -->
                <td class="px-6 py-3 text-center space-x-2">
                  <a href="edit.php?id=<?php echo $u['user_id']; ?>" 
                     class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>

                  <?php if ($u['status'] === 'active'): ?>
                    <a href="delete.php?id=<?php echo $u['user_id']; ?>" 
                       onclick="return confirm('Are you sure you want to archive this user?');"
                       class="text-red-600 hover:text-red-800 font-medium">Archive</a>
                  <?php else: ?>
                    <a href="restore.php?id=<?php echo $u['user_id']; ?>" 
                       onclick="return confirm('Restore this user account?');"
                       class="text-green-600 hover:text-green-800 font-medium">Restore</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center py-6 text-gray-500">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
