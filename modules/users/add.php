<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username already exists
        $existingUser = $db->select("SELECT id FROM users WHERE username = :username", 
                                    ['username' => $username]);
        
        if (!empty($existingUser)) {
            $errors[] = "Username already exists";
        }
    }
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $existingEmail = $db->select("SELECT id FROM users WHERE email = :email", 
                                     ['email' => $email]);
        
        if (!empty($existingEmail)) {
            $errors[] = "Email already exists";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (!in_array($role, ['admin', 'manager', 'staff'])) {
        $errors[] = "Invalid role selected";
    }
    
    // If no errors, insert user
    if (empty($errors)) {
        $userData = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'active' => $active,
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        $userId = $db->insert('users', $userData);
        
        if ($userId) {
            $_SESSION['message'] = "User added successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/users/list.php');
        } else {
            $errors[] = "Failed to add user. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Add New User</h2>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <ul class="list-disc list-inside">
            <?php foreach($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <!-- Username -->
        <div class="mb-4">
            <label for="username" class="block text-gray-700 font-medium mb-2">Username *</label>
            <input type="text" id="username" name="username" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['username']) ? $_POST['username'] : '' ?>">
        </div>
        
        <!-- Name -->
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-medium mb-2">Full Name *</label>
            <input type="text" id="name" name="name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['name']) ? $_POST['name'] : '' ?>">
        </div>
        
        <!-- Email -->
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-medium mb-2">Email *</label>
            <input type="email" id="email" name="email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>">
        </div>
        
        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-gray-700 font-medium mb-2">Password *</label>
            <div class="relative">
                <input type="password" id="password" name="password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                <button type="button" class="absolute right-2 top-2 text-gray-500" onclick="togglePasswordVisibility('password')">
                    <i id="password-icon" class="fas fa-eye"></i>
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
        </div>
        
        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password *</label>
            <div class="relative">
                <input type="password" id="confirm_password" name="confirm_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                <button type="button" class="absolute right-2 top-2 text-gray-500" onclick="togglePasswordVisibility('confirm_password')">
                    <i id="confirm_password-icon" class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        
        <!-- Role -->
        <div class="mb-4">
            <label for="role" class="block text-gray-700 font-medium mb-2">Role *</label>
            <select id="role" name="role" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required>
                <option value="staff" <?= isset($_POST['role']) && $_POST['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="manager" <?= isset($_POST['role']) && $_POST['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                <option value="admin" <?= isset($_POST['role']) && $_POST['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
        </div>
        
        <!-- Active Status -->
        <div class="mb-4">
            <div class="flex items-center">
                <input type="checkbox" id="active" name="active" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= !isset($_POST['active']) || isset($_POST['active']) && $_POST['active'] ? 'checked' : '' ?>>
                <label for="active" class="ml-2 block text-gray-700">Active Account</label>
            </div>
            <p class="text-xs text-gray-500 mt-1">Inactive users cannot log in to the system</p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Create User
            </button>
        </div>
    </form>
</div>

<!-- Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t flex justify-between items-center p-2 bottom-nav">
    <a href="../../index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-home text-xl"></i>
        <span class="text-xs mt-1">Home</span>
    </a>
    <a href="../products/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-box text-xl"></i>
        <span class="text-xs mt-1">Products</span>
    </a>
    <a href="../sales/add.php" class="flex flex-col items-center p-2 text-gray-600">
        <div class="bg-red-900 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </div>
        <span class="text-xs mt-1">New Sale</span>
    </a>
    <a href="../customers/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-users text-xl"></i>
        <span class="text-xs mt-1">Customers</span>
    </a>
    <a href="../reports/index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<script>
    function togglePasswordVisibility(inputId) {
        const passwordInput = document.getElementById(inputId);
        const icon = document.getElementById(inputId + '-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            passwordInput.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>