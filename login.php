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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Include database connection
        require_once 'includes/db.php';
        $db = new Database();
        
        // Get user by username
        $user = $db->select("SELECT * FROM users WHERE username = :username AND active = TRUE", 
                            ['username' => $username]);
        
        if (!empty($user) && password_verify($password, $user[0]['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user[0]['id'];
            $_SESSION['username'] = $user[0]['username'];
            $_SESSION['user_role'] = $user[0]['role'];
            $_SESSION['user_name'] = $user[0]['name'];
            
            // Update last login time
            $db->update('users', 
                        ['lastLogin' => date('Y-m-d H:i:s')], 
                        'id = :id', 
                        ['id' => $user[0]['id']]);
            
            // Redirect to dashboard
            redirect('index.php');
        } else {
            // Login failed
            $error = "Invalid username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - <?= APP_NAME ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#bb0620">
    <meta name="description" content="Mobile inventory management system for small businesses">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= APP_NAME ?>">
    
    <!-- PWA Icons and Splash Screens -->
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
            -webkit-touch-callout: none;
            overscroll-behavior: none;
        }
        .login-container {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }


        @keyframes fadeInDowm {
    0% { opacity: 0; transform: translateY(-10px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fadeInDown {
    animation: fadeInDown 0.4s ease-out;
}

    </style>
</head>



<body class="bg-gradient-to-b from-red-900 to-red-900 min-h-screen flex flex-col items-center justify-center p-4">
<?php if (!empty($error)): ?>
<div class="fixed top-6 left-1/2 transform -translate-x-1/2 z-50 bg-red-100 border border-red-400 text-red-900 px-6 py-4 rounded-lg shadow-lg animate-fadeInDown">
    <div class="flex items-center space-x-2">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= $error ?></span>
    </div>
</div>
<script>
    // Auto-hide toast after 4 seconds
    setTimeout(() => {
        const toast = document.querySelector('.fixed.top-6');
        if (toast) toast.style.display = 'none';
    }, 4000);
</script>
<?php endif; ?>

    <div class="login-container bg-white rounded-xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <?php if (file_exists('assets/images/bilgix-logo.png')): ?>
                <img src="assets/images/bilgix-logo.png" alt="<?= COMPANY_NAME ?>" class="h-16 mx-auto mb-4 rounded-lg">

            <?php else: ?>
            <h1 class="text-3xl font-bold text-red-900 mb-2"><?= APP_NAME ?></h1>
            <?php endif; ?>
            <p class="text-gray-600"><?= COMPANY_NAME ?></p>
        </div>
        


        
        <form method="POST" action="login.php" class="space-y-6">
            <div>
                <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" id="username" name="username" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required autocomplete="username">
                </div>
            </div>
            
            <div>
                <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" class="w-full pl-10 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required autocomplete="current-password">
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                </div>
                
                <a href="forgot-password.php" class="text-sm text-red-900 hover:underline">Forgot password?</a>
            </div>
            
            <button type="submit" class="w-full bg-red-900 text-white py-3 px-4 rounded-lg hover:bg-red-900 transition flex items-center justify-center">
                <i class="fas fa-sign-in-alt mr-2"></i> Login
            </button>
        </form>
    </div>
    
    <p class="text-white text-center mt-8 text-sm">
        &copy; <?= date('Y') ?> <?= COMPANY_NAME ?> | <?= APP_NAME ?> v<?= APP_VERSION ?>
    </p>
    
    <script src="assets/js/pwa.js"></script>
</body>
</html>