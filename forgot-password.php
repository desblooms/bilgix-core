<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/email_helper.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    // Validate input
    if (empty($email)) {
        $error = "Email address is required";
    } else if (!isValidEmail($email)) {
        $error = "Invalid email format";
    } else {
        // Include database connection
        require_once 'includes/db.php';
        $db = new Database();
        
        // Get user by email
        $user = $db->select("SELECT * FROM users WHERE email = :email AND active = TRUE", 
                           ['email' => $email]);
        
        if (empty($user)) {
            // For security reasons, show a generic success message
            $message = "If an account with that email exists, password reset instructions have been sent.";
        } else {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token to database
            $updated = $db->update('users', 
                                  ['resetToken' => $token, 'resetExpiry' => $expiry], 
                                  'id = :id', 
                                  ['id' => $user[0]['id']]);
            
            if ($updated) {
                // Construct reset link
                $resetLink = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . 
                            '://' . $_SERVER['HTTP_HOST'] . 
                            dirname($_SERVER['PHP_SELF']) . 
                            '/reset-password.php?token=' . $token;
                
                // Send password reset email using our EmailHelper
                $emailSent = sendPasswordResetEmail($email, $user[0]['name'], $resetLink);
                
                if ($emailSent) {
                    $message = "Password reset instructions have been sent to your email address.";
                } else {
                    // Log the error for administrators
                    error_log("Failed to send password reset email to $email: " . getEmailError());
                    $message = "If an account with that email exists, password reset instructions have been sent.";
                }
            } else {
                $error = "An error occurred. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Forgot Password - <?= APP_NAME ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="description" content="Mobile inventory management system for small businesses">
    
    <!-- PWA Icons -->
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/assets/icons/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">
    
    <!-- Stylesheets -->
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
            <h1 class="text-3xl font-bold text-red-900 mb-2">Forgot Password</h1>
            <p class="text-gray-600">Enter your email to receive a password reset link</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <p><i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <p><i class="fas fa-check-circle mr-2"></i> <?= $message ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="forgot-password.php" class="space-y-6">
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-red-900 text-white py-3 px-4 rounded-lg hover:bg-red-900 transition flex items-center justify-center">
                <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
            </button>
            
            <div class="text-center">
                <a href="login.php" class="text-red-900 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </form>
    </div>
    
    <p class="text-white text-center mt-8 text-sm">
        &copy; <?= date('Y') ?> <?= COMPANY_NAME ?> | <?= APP_NAME ?> v<?= APP_VERSION ?>
    </p>
</body>
</html>