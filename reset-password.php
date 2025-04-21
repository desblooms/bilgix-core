<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$validToken = false;
$userId = null;

// Check if token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    // Include database connection
    require_once 'includes/db.php';
    $db = new Database();
    
    // Check if token exists and is not expired
    $user = $db->select("SELECT id FROM users 
                        WHERE resetToken = :token 
                        AND resetExpiry > NOW() 
                        AND active = TRUE", 
                        ['token' => $token]);
    
    if (!empty($user)) {
        $validToken = true;
        $userId = $user[0]['id'];
    } else {
        $error = "Invalid or expired token. Please request a new password reset link.";
    }
} else {
    $error = "Token not provided. Please use the reset link from your email.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate input
    if (empty($password) || empty($confirmPassword)) {
        $error = "Both password fields are required";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match";
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user's password and clear reset token
        $updated = $db->update('users', 
                              ['password' => $hashedPassword, 'resetToken' => null, 'resetExpiry' => null], 
                              'id = :id', 
                              ['id' => $userId]);
        
        if ($updated) {
            $success = "Your password has been reset successfully. You can now login with your new password.";
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Reset Password - <?= APP_NAME ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="description" content="Mobile inventory management system for small businesses">
    
    <!-- PWA Icons -->
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/assets/icons/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">
    
    <!-- Stylesheets -->
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          red: {
            900: '#bb0620' // override red-900
          }
        }
      }
    }
  }
</script>
    
    <style>
        body {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        .password-container {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6B7280;
        }
        .form-container {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-b from-red-900 to-red-600 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="form-container bg-white rounded-xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-red-900 mb-2">Reset Password</h1>
            <p class="text-gray-600">Enter your new password below</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <p><i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <p><i class="fas fa-check-circle mr-2"></i> <?= $success ?></p>
            <div class="mt-4 text-center">
                <a href="login.php" class="bg-green-600 text-white py-2 px-4 rounded-lg inline-block">
                    <i class="fas fa-sign-in-alt mr-2"></i> Go to Login
                </a>
            </div>
        </div>
        <?php elseif ($validToken): ?>
        <form method="POST" class="space-y-6">
            <div>
                <label for="password" class="block text-gray-700 font-medium mb-2">New Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                    <span class="password-toggle" id="password-toggle" onclick="togglePasswordVisibility('password', 'password-toggle')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
            </div>
            
            <div>
                <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                    <span class="password-toggle" id="confirm-password-toggle" onclick="togglePasswordVisibility('confirm_password', 'confirm-password-toggle')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-red-900 text-white py-3 px-4 rounded-lg hover:bg-red-900 transition flex items-center justify-center">
                <i class="fas fa-lock mr-2"></i> Reset Password
            </button>
            
            <div class="text-center">
                <a href="login.php" class="text-red-900 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <p class="text-white text-center mt-8 text-sm">
        &copy; <?= date('Y') ?> <?= COMPANY_NAME ?> | <?= APP_NAME ?> v<?= APP_VERSION ?>
    </p>
    
    <script>
        function togglePasswordVisibility(inputId, toggleId) {
            const passwordInput = document.getElementById(inputId);
            const toggleBtn = document.getElementById(toggleId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
    </script>
</body>
</html>