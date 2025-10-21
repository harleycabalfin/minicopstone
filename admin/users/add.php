<?php
require_once '../../config/database.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireAdmin();
checkSessionTimeout();

$page_title = "Add New User";
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitizeInput($_POST['role']);
    $phone = sanitizeInput($_POST['phone']);
    
    // Validation
    if (empty($username) || empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters.";
    } elseif (!isValidEmail($email)) {
        $error = "Invalid email format.";
    } else {
        // Check if username exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            // Check if email exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already exists.";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $db->prepare("INSERT INTO users (username, password_hash, full_name, email, role, phone) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $username, $password_hash, $full_name, $email, $role, $phone);
                
                if ($stmt->execute()) {
                    $new_user_id = $db->insert_id;
                    logActivity($_SESSION['user_id'], "Added new user: $username", "users", $new_user_id);
                    
                    $success = "User added successfully!";
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Failed to add user. Please try again.";
                }
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <nav class="text-sm">
            <a href="index.php" class="text-green-600 hover:underline">User Management</a>
            <span class="mx-2">/</span>
            <span class="text-gray-600">Add New User</span>
        </nav>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New User</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6" data-validate>
            <!-- Username -->
            <div>
                <label for="username" class="form-label">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="username" 
                       id="username" 
                       required
                       class="form-control"
                       placeholder="Enter username">
            </div>

            <!-- Full Name -->
            <div>
                <label for="full_name" class="form-label">
                    Full Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="full_name" 
                       id="full_name" 
                       required
                       class="form-control"
                       placeholder="Enter full name">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="form-label">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" 
                       name="email" 
                       id="email" 
                       required
                       class="form-control"
                       placeholder="Enter email address">
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="form-label">
                    Phone Number
                </label>
                <input type="text" 
                       name="phone" 
                       id="phone" 
                       class="form-control"
                       placeholder="09XX XXX XXXX">
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="form-label">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role" id="role" required class="form-control">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    Admins have full access, Users have limited access
                </p>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="form-label">
                    Password <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       required
                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                       class="form-control"
                       placeholder="Enter password">
                <p class="text-sm text-gray-500 mt-1">
                    Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters
                </p>
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="confirm_password" class="form-label">
                    Confirm Password <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       name="confirm_password" 
                       id="confirm_password" 
                       required
                       class="form-control"
                       placeholder="Confirm password">
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="index.php" class="btn bg-gray-500 text-white">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>