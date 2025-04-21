<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No user specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/users/list.php');
}

$userId = (int)$_GET['id'];

// Get user details
$user = $db->select("SELECT id, username, name, email FROM users WHERE id = :id", ['id' => $userId]);

if (empty($user)) {
    $_SESSION['message'] = "User not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/users/list.php');
}

$user = $user[0];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $updated = $db->update('users', 
                              ['password' => $hashedPassword, 'updatedAt' => date('Y-m-d H:i:s')], 
                              'id = :id', 
                              ['id' => $userId]);
        
        if ($updated) {
            $_SESSION['message'] = "Password reset successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/users/list.php');
        } else {
            $errors[] = "Failed to reset password. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Reset User Password</h2>
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
    
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= $user['name'] ?></h3>
                <p class="text-sm text-gray-600">Username: <?= $user['username'] ?></p>
                <p class="text-sm text-gray-600">Email: <?= $user['email'] ?></p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                    Password Reset
                </span>
            </div>
        </div>
    </div>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <div class="mb-4">
            <label for="password" class="block text-gray-700 font-medium mb-2">New Password *</label>
            <div class="relative">
                <input type="password" id="password" name="password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                <button type="button" class="absolute right-2 top-2 text-gray-500" onclick="togglePasswordVisibility('password')">
                    <i id="password-icon" class="fas fa-eye"></i>
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
        </div>
        
        <div class="mb-4">
            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password *</label>
            <div class="relative">
                <input type="password" id="confirm_password" name="confirm_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                <button type="button" class="absolute right-2 top-2 text-gray-500" onclick="togglePasswordVisibility('confirm_password')">
                    <i id="confirm_password-icon" class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        
        <!-- Password Generator -->
        <div class="mb-6">
            <label class="block text-gray-700 font-medium mb-2">Password Generator</label>
            <div class="flex space-x-2">
                <input type="text" id="generated_password" class="flex-1 p-2 border rounded-lg bg-gray-50" readonly>
                <button type="button" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-lg" onclick="generatePassword()">
                    <i class="fas fa-sync-alt mr-1"></i> Generate
                </button>
                <button type="button" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-lg" onclick="useGeneratedPassword()">
                    <i class="fas fa-check mr-1"></i> Use
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Generate a secure random password</p>
        </div>
        
        <div class="flex space-x-3">
            <button type="submit" class="flex-1 bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-key mr-2"></i> Reset Password
            </button>
            
            <a href="list.php" class="flex-1 bg-gray-300 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-400 transition text-center">
                <i class="fas fa-times mr-2"></i> Cancel
            </a>
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
    
    function generatePassword() {
        // Generate a random string for password
        const length = 12;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=";
        let password = "";
        
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset.charAt(randomIndex);
        }
        
        document.getElementById('generated_password').value = password;
    }
    
    function useGeneratedPassword() {
        const generatedPassword = document.getElementById('generated_password').value;
        
        if (generatedPassword) {
            document.getElementById('password').value = generatedPassword;
            document.getElementById('confirm_password').value = generatedPassword;
        } else {
            alert('Please generate a password first');
        }
    }
    
    // Generate a password on page load
    document.addEventListener('DOMContentLoaded', generatePassword);
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>