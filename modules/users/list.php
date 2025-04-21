<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Get all users
$users = $db->select("SELECT * FROM users ORDER BY username ASC");

// Handle user status toggle (activate/deactivate)
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $currentUser = getCurrentUserId();
    
    // Prevent deactivating your own account
    if ($userId == $currentUser) {
        $_SESSION['message'] = "You cannot deactivate your own account!";
        $_SESSION['message_type'] = "error";
    } else {
        // Get current status
        $user = $db->select("SELECT active FROM users WHERE id = :id", ['id' => $userId]);
        
        if (!empty($user)) {
            $newStatus = $user[0]['active'] ? 0 : 1;
            $updated = $db->update('users', 
                                   ['active' => $newStatus], 
                                   'id = :id', 
                                   ['id' => $userId]);
            
            if ($updated) {
                $_SESSION['message'] = "User status updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to update user status!";
                $_SESSION['message_type'] = "error";
            }
        }
    }
    
    // Redirect to refresh page
    redirect($basePath . 'modules/users/list.php');
}
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">User Management</h2>
        <a href="add.php" class="bg-red-900 text-white py-2 px-4 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> Add New User
        </a>
    </div>
    
    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative">
            <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="Search users...">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>
    
    <!-- Users List -->
    <?php if (count($users) > 0): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Username
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Role
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Last Login
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="usersList">
                    <?php foreach($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?= $user['username'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?= $user['name'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500"><?= $user['email'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                   ($user['role'] === 'manager' ? 'bg-blue-100 text-blue-800' : 
                                    'bg-gray-100 text-gray-800') ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $user['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $user['active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">
                                <?= !empty($user['lastLogin']) ? date('M d, Y g:i A', strtotime($user['lastLogin'])) : 'Never' ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <a href="edit.php?id=<?= $user['id'] ?>" class="text-red-900 hover:text-blue-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($user['id'] != getCurrentUserId()): ?>
                                <a href="?toggle=status&id=<?= $user['id'] ?>" class="<?= $user['active'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' ?>">
                                    <i class="fas <?= $user['active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($user['id'] != getCurrentUserId()): ?>
                                <a href="reset.php?id=<?= $user['id'] ?>" class="text-yellow-600 hover:text-yellow-900">
                                    <i class="fas fa-key"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500 mb-4">No users found</p>
        <a href="add.php" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">Add Your First User</a>
    </div>
    <?php endif; ?>
</div>

<!-- Role Legend -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <h3 class="text-md font-medium text-gray-800 mb-2">User Roles</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="flex items-center">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 mr-2">
                Admin
            </span>
            <span class="text-sm text-gray-600">Full access to all features</span>
        </div>
        <div class="flex items-center">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 mr-2">
                Manager
            </span>
            <span class="text-sm text-gray-600">Access to all except user management</span>
        </div>
        <div class="flex items-center">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 mr-2">
                Staff
            </span>
            <span class="text-sm text-gray-600">Limited access to basic operations</span>
        </div>
    </div>
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
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const usersList = document.getElementById('usersList');
    const userRows = usersList ? Array.from(usersList.querySelectorAll('tr')) : [];
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        userRows.forEach(row => {
            const username = row.cells[0].textContent.toLowerCase();
            const name = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const role = row.cells[3].textContent.toLowerCase();
            
            if (username.includes(searchTerm) || name.includes(searchTerm) || 
                email.includes(searchTerm) || role.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>