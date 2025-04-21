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
$user = $db->select("SELECT * FROM users WHERE id = :id", ['id' => $userId]);

if (empty($user)) {
    $_SESSION['message'] = "User not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/users/list.php');
}

$user = $user[0];

// Prevent editing of the main admin if you're not the main admin
if ($user['username'] === 'admin' && getCurrentUserId() != $user['id']) {
    $_SESSION['message'] = "You cannot edit the main administrator account!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/users/list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Don't allow deactivating your own account
    if ($userId == getCurrentUserId() && !$active) {
        $active = 1; // Force active
        $_SESSION['message'] = "You cannot deactivate your own account!";
        $_SESSION['message_type'] = "error";
    }
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif ($username !== $user['username']) {
        // Check if username already exists
        $existingUser = $db->select("SELECT id FROM users WHERE username = :username AND id != :id", 
                                   ['username' => $username, 'id' => $userId]);
        
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
    } elseif ($email !== $user['email']) {
        // Check if email already exists
        $existingEmail = $db->select("SELECT id FROM users WHERE email = :email AND id != :id", 
                                    ['email' => $email, 'id' => $userId]);
        
        if (!empty($existingEmail)) {
            $errors[] = "Email already exists";
        }
    }
    
    if (!in_array($role, ['admin', 'manager', 'staff'])) {
        $errors[] = "Invalid role selected";
    }
    
    // If main admin, don't allow changing role
    if ($user['username'] === 'admin' && $role !== 'admin') {
        $errors[] = "Cannot change role of the main administrator";
        $role = 'admin'; // Force admin role
    }
    
    // If no errors, update user
    if (empty($errors)) {
        $userData = [
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'active' => $active,
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        $updated = $db->update('users', $userData, 'id = :id', ['id' => $userId]);
        
        if ($updated) {
            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/users/list.php');
        } else {
            $errors[] = "Failed to update user. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Edit User</h2>
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
            <input type="text" id="username" name="username" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $user['username'] ?>">
        </div>
        
        <!-- Name -->
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-medium mb-2">Full Name *</label>
            <input type="text" id="name" name="name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $user['name'] ?>">
        </div>
        
        <!-- Email -->
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-medium mb-2">Email *</label>
            <input type="email" id="email" name="email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $user['email'] ?>">
        </div>
        
        <!-- Role -->
        <div class="mb-4">
            <label for="role" class="block text-gray-700 font-medium mb-2">Role *</label>
            <select id="role" name="role" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required <?= $user['username'] === 'admin' ? 'disabled' : '' ?>>
                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
            <?php if ($user['username'] === 'admin'): ?>
            <input type="hidden" name="role" value="admin">
            <p class="text-xs text-gray-500 mt-1">The main administrator role cannot be changed</p>
            <?php endif; ?>
        </div>
        
        <!-- Active Status -->
        <div class="mb-4">
            <div class="flex items-center">
                <input type="checkbox" id="active" name="active" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $user['active'] ? 'checked' : '' ?> <?= $userId == getCurrentUserId() ? 'disabled' : '' ?>>
                <label for="active" class="ml-2 block text-gray-700">Active Account</label>
            </div>
            <?php if ($userId == getCurrentUserId()): ?>
            <input type="hidden" name="active" value="1">
            <p class="text-xs text-gray-500 mt-1">You cannot deactivate your own account</p>
            <?php else: ?>
            <p class="text-xs text-gray-500 mt-1">Inactive users cannot log in to the system</p>
            <?php endif; ?>
        </div>
        
        <!-- Last Login Info -->
        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">
                <strong>Last Login:</strong> 
                <?= !empty($user['lastLogin']) ? date('M d, Y g:i A', strtotime($user['lastLogin'])) : 'Never' ?>
            </p>
            <p class="text-sm text-gray-600">
                <strong>Created:</strong> 
                <?= date('M d, Y', strtotime($user['createdAt'])) ?>
                <?php if (!empty($user['updatedAt'])): ?>
                | <strong>Updated:</strong> 
                <?= date('M d, Y', strtotime($user['updatedAt'])) ?>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="mt-6 flex space-x-3">
            <button type="submit" class="flex-1 bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Update User
            </button>
            
            <a href="reset.php?id=<?= $userId ?>" class="flex-1 bg-yellow-600 text-white py-2 px-4 rounded-lg hover:bg-yellow-700 transition text-center">
                <i class="fas fa-key mr-2"></i> Reset Password
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

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>