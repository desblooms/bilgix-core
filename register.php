<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user registration is enabled
define('ALLOW_REGISTRATION', true); // Set this to false to disable public registration

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Check if registration is allowed
if (!ALLOW_REGISTRATION) {
    $_SESSION['message'] = "Registration is currently disabled. Please contact the administrator.";
    $_SESSION['message_type'] = "error";
    redirect('login.php');
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize($_POST['username']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Include database connection
        require_once 'includes/db.php';
        $db = new Database();
        
        // Check if username already exists
        $existingUser = $db->select("SELECT id FROM users WHERE username = :username", 
                                    ['username' => $username]);
        
        if (!empty($existingUser)) {
            $errors[] = "Username is already taken";
        }
    }
    
    if (empty($name)) {
        $errors[] = "Full name is required";
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
            $errors[] = "Email is already registered";
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
    
    // If no errors, register user
    if (empty($errors)) {
        // Set default role for new registrations
        $role = 'staff'; // You can change this to a different default role
        
        // Set account to inactive by default if admin approval is required
        // or active if no approval needed
        $requireApproval = false; // Set to true to require admin approval
        $active = $requireApproval ? 0 : 1;
        
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
            if ($requireApproval) {
                $success = "Registration successful! Your account is pending approval by an administrator.";
            } else {
                $success = "Registration successful! You can now login with your credentials.";
                
                // Auto login user if desired
                $autoLogin = false; // Set to true to auto-login after registration
                if ($autoLogin) {
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_name'] = $name;
                    
                    // Redirect to dashboard
                    redirect('index.php');
                }
            }
        } else {
            $error = "Failed to register user. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Register - <?= APP_NAME ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="description" content="Mobile inventory management system for small businesses">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= APP_NAME ?>">
    
    <!-- PWA Icons and Splash Screens -->
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/assets/icons/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            overscroll-behavior: none;
        }
        .register-container {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
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
    </style>
</head>
<body class="bg-gradient-to-b from-red-900 to-red-900 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="register-container bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mb-8">
        <div class="text-center mb-8">
            <?php if (file_exists('assets/images/logo.png')): ?>
            <img src="assets/images/logo.png" alt="<?= COMPANY_NAME ?>" class="h-16 mx-auto mb-4">
            <?php else: ?>
            <h1 class="text-3xl font-bold text-red-900 mb-2"><?= APP_NAME ?></h1>
            <?php endif; ?>
            <p class="text-gray-600"><?= COMPANY_NAME ?></p>
            <h2 class="text-2xl font-bold mt-4">Create an Account</h2>
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
        <?php else: ?>
        <form method="POST" action="register.php" class="space-y-4">
            <!-- Username -->
            <div>
                <label for="username" class="block text-gray-700 font-medium mb-1">Username *</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" id="username" name="username" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['username']) ? $_POST['username'] : '' ?>">
                </div>
                <p class="text-xs text-gray-500 mt-1">Usernames must be unique and cannot be changed later</p>
            </div>
            
            <!-- Full Name -->
            <div>
                <label for="name" class="block text-gray-700 font-medium mb-1">Full Name *</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-user-circle"></i>
                    </span>
                    <input type="text" id="name" name="name" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['name']) ? $_POST['name'] : '' ?>">
                </div>
            </div>
            
            <!-- Email -->
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-1">Email *</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>">
                </div>
            </div>
            
            <!-- Password -->
            <div>
                <label for="password" class="block text-gray-700 font-medium mb-1">Password *</label>
                <div class="password-container">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                    <span class="password-toggle" onclick="togglePasswordVisibility('password', 'password-icon')">
                        <i id="password-icon" class="fas fa-eye"></i>
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
            </div>
            
            <!-- Confirm Password -->
            <div>
                <label for="confirm_password" class="block text-gray-700 font-medium mb-1">Confirm Password *</label>
                <div class="password-container">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="confirm_password" name="confirm_password" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required minlength="8">
                    <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password', 'confirm-password-icon')">
                        <i id="confirm-password-icon" class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div>
                <button type="submit" class="w-full bg-red-900 text-white py-3 px-4 rounded-lg hover:bg-red-900 transition flex items-center justify-center">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </button>
            </div>
            
            <div class="text-center text-sm">
                <p>Already have an account? <a href="login.php" class="text-red-900 hover:underline">Login</a></p>
            </div>
        </form>
        <?php endif; ?>
    </div>
    
    <p class="text-white text-center text-sm mb-4">
        &copy; <?= date('Y') ?> <?= COMPANY_NAME ?> | <?= APP_NAME ?> v<?= APP_VERSION ?>
    </p>
    
    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
    
    <script src="assets/js/pwa.js"></script>
</body>
</html>