<?php
require_once '../../config/database.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireAdmin();
checkSessionTimeout();

$page_title = "User Management";
$db = getDB();

// Fetch all users
$query = "SELECT user_id, username, full_name, email, role, is_active, created_at, last_login 
          FROM users 
          ORDER BY created_at DESC";
$result = $db->query($query);
$users = $result->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-800">User Management</h2>
        <a href="add.php" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New User
        </a>
    </div>

    <!-- Search Bar -->
    <div class="bg-black rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input type="text" 
                   id="searchInput" 
                   placeholder="Search users..." 
                   class="w-full border-none focus:outline-none"
                   onkeyup="tableUtils.searchTable('searchInput', 'usersTable')">
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="w-full" id="usersTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <span class="text-green-600 font-semibold">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                    <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-info' : 'badge-success'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="edit.php?id=<?php echo $user['user_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                Edit
                            </a>
                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <a href="delete.php?id=<?php echo $user['user_id']; ?>" 
                                   class="text-red-600 hover:text-red-900 btn-delete"
                                   data-item-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                                    Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Export Button -->
    <div class="mt-6 flex justify-end">
        <button onclick="tableUtils.exportTableToCSV('usersTable', 'users_export.csv')" 
                class="btn bg-gray-600 text-white">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export to CSV
        </button>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>