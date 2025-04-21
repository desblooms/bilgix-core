<?php
if (!isset($basePath)) {
    $basePath = '';  // Or set a default value based on your project structure
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'includes/config.php';

require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current user
$userId = getCurrentUserId();
$currentUser = getCurrentUser();

// Include database connection
require_once 'includes/db.php';
$db = new Database();

// Handle profile update
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Invalid email format";
    } else if ($email !== $currentUser['email']) {
        // Check if email already exists for other users
        $existingEmail = $db->select("SELECT id FROM users WHERE email = :email AND id != :id", 
                                    ['email' => $email, 'id' => $userId]);
        
        if (!empty($existingEmail)) {
            $errors[] = "Email is already in use by another account";
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $userData = [
            'name' => $name,
            'email' => $email,
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        $updated = $db->update('users', $userData, 'id = :id', ['id' => $userId]);
        
        if ($updated) {
            $message = "Profile updated successfully!";
            
            // Update session data
            $_SESSION['user_name'] = $name;
            
            // Refresh user data
            $currentUser = getCurrentUser();
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Fetch current password hash
    $user = $db->select("SELECT password FROM users WHERE id = :id", ['id' => $userId]);
    
    // Validation
    $errors = [];
    
    if (empty($currentPassword)) {
        $errors[] = "Current password is required";
    } elseif (!password_verify($currentPassword, $user[0]['password'])) {
        $errors[] = "Current password is incorrect";
    }
    
    if (empty($newPassword)) {
        $errors[] = "New password is required";
    } elseif (strlen($newPassword) < 8) {
        $errors[] = "New password must be at least 8 characters long";
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = "New passwords do not match";
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updated = $db->update('users', 
                              ['password' => $hashedPassword, 'updatedAt' => date('Y-m-d H:i:s')], 
                              'id = :id', 
                              ['id' => $userId]);
        
        if ($updated) {
            $message = "Password changed successfully!";
        } else {
            $error = "Failed to change password. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Include header
include 'includes/header.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">My Profile</h2>
    
    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
        <p><i class="fas fa-check-circle mr-2"></i> <?= $message ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <p><i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?></p>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Profile Information</h3>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-gray-700 font-medium mb-1">Username</label>
                    <input type="text" id="username" class="w-full p-2 border rounded-lg bg-gray-100" value="<?= $currentUser['username'] ?>" disabled readonly>
                    <p class="text-xs text-gray-500 mt-1">Usernames cannot be changed</p>
                </div>
                
                <div>
                    <label for="name" class="block text-gray-700 font-medium mb-1">Full Name *</label>
                    <input type="text" id="name" name="name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $currentUser['name'] ?>">
                </div>
                
                <div>
                    <label for="email" class="block text-gray-700 font-medium mb-1">Email *</label>
                    <input type="email" id="email" name="email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $currentUser['email'] ?>">
                </div>
                
                <div>
                    <label for="role" class="block text-gray-700 font-medium mb-1">Role</label>
                    <input type="text" id="role" class="w-full p-2 border rounded-lg bg-gray-100" value="<?= ucfirst($currentUser['role']) ?>" disabled readonly>
                </div>
                
                <div class="pt-4 border-t">
                    <button type="submit" name="update_profile" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                        <i class="fas fa-save mr-2"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Change Password</h3>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label for="current_password" class="block text-gray-700 font-medium mb-1">Current Password *</label>
                    <div class="relative">
                        <input type="password" id="current_password" name="current_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required>
                        <button type="button" class="absolute right-2 top-2 text-gray-500" onclick="togglePasswordVisibility('current_password')">
                            <i id="current_password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label for="new_password" class="block text-gray-700 font-medium mb-1">New Password *</label>
                    <div class="relative">
                        <input type="password" id="new_password" name="new_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                        <button type="button" class="absolute right-2 top-2 text-gray-500" onclick="togglePasswordVisibility('new_password')">
                            <i id="new_password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-1">Confirm New Password *</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                        <button type="button" class="absolute right-2 top-2 text-gray-500" onclick="togglePasswordVisibility('confirm_password')">
                            <i id="confirm_password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="pt-4 border-t">
                    <button type="submit" name="change_password" class="w-full bg-yellow-600 text-white py-2 px-4 rounded-lg hover:bg-yellow-700 transition">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Account Info -->
    <div class="bg-white rounded-lg shadow p-4 mt-4">
        <h3 class="text-lg font-medium border-b pb-2 mb-4">Account Information</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm"><strong>Last Login:</strong> 
                    <?php
                    $lastLogin = $db->select("SELECT lastLogin FROM users WHERE id = :id", ['id' => $userId]);
                    echo !empty($lastLogin[0]['lastLogin']) ? date('M d, Y g:i A', strtotime($lastLogin[0]['lastLogin'])) : 'Never';
                    ?>
                </p>
            </div>
            <div>
                <p class="text-sm"><strong>Account Created:</strong> 
                    <?php
                    $createdAt = $db->select("SELECT createdAt FROM users WHERE id = :id", ['id' => $userId]);
                    echo date('M d, Y', strtotime($createdAt[0]['createdAt']));
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t flex justify-between items-center p-2 bottom-nav">
    <a href="index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-home text-xl"></i>
        <span class="text-xs mt-1">Home</span>
    </a>
    <a href="modules/products/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-box text-xl"></i>
        <span class="text-xs mt-1">Products</span>
    </a>
    <a href="modules/sales/add.php" class="flex flex-col items-center p-2 text-gray-600">
        <div class="bg-red-900 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </div>
        <span class="text-xs mt-1">New Sale</span>
    </a>
    <a href="modules/customers/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-users text-xl"></i>
        <span class="text-xs mt-1">Customers</span>
    </a>
    <a href="modules/reports/index.php" class="flex flex-col items-center p-2 text-gray-600">
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