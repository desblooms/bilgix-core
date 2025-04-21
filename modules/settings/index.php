<?php 
// modules/settings/index.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check if user has permission for settings page
if (!isManagerOrAdmin()) {
    $_SESSION['message'] = "You don't have permission to access settings!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'index.php');
}

// Get settings from database (if needed)
$settings = $db->select("SELECT * FROM settings");
$settingsMap = [];
foreach ($settings as $setting) {
    $settingsMap[$setting['settingKey']] = $setting['settingValue'];
}
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Settings</h2>
    
    <!-- Settings Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Company Settings -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-red-900 text-white">
                <h3 class="font-medium"><i class="fas fa-building mr-2"></i> Company</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">Manage company information, logo, and business details</p>
                <a href="company.php" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-cog mr-2"></i> Manage
                </a>
            </div>
        </div>
        
        <!-- Financial Settings -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-blue-600 text-white">
                <h3 class="font-medium"><i class="fas fa-money-bill-wave mr-2"></i> Financial</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">Configure financial year, opening balances, and tax settings</p>
                <a href="company_finances.php" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-cog mr-2"></i> Manage
                </a>
            </div>
        </div>
        
        <!-- Invoice Settings -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-green-600 text-white">
                <h3 class="font-medium"><i class="fas fa-file-invoice mr-2"></i> Invoice</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">Configure invoice numbering, templates, and printing options</p>
                <a href="invoice.php" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-cog mr-2"></i> Manage
                </a>
            </div>
        </div>
        
        <!-- User Management -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-purple-600 text-white">
                <h3 class="font-medium"><i class="fas fa-users-cog mr-2"></i> Users</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">Manage user accounts, permissions, and access control</p>
                <a href="../users/list.php" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-cog mr-2"></i> Manage
                </a>
            </div>
        </div>
        
        <!-- Backup & Restore -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-yellow-600 text-white">
                <h3 class="font-medium"><i class="fas fa-database mr-2"></i> Backup & Restore</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">Backup your data and restore from previous backups</p>
                <a href="backup.php" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-cog mr-2"></i> Manage
                </a>
            </div>
        </div>
        
        <!-- System Settings -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-gray-700 text-white">
                <h3 class="font-medium"><i class="fas fa-cogs mr-2"></i> System</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 mb-4">Configure general system settings and preferences</p>
                <a href="system.php" class="block text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-lg transition">
                    <i class="fas fa-cog mr-2"></i> Manage
                </a>
            </div>
        </div>
    </div>
    
    <!-- Current Settings Summary -->
    <div class="mt-6 bg-white rounded-lg shadow p-4">
        <h3 class="text-md font-medium text-gray-800 border-b pb-2 mb-4">System Information</h3>
        
        <div class="grid grid-cols-2 gap-y-2">
            <div class="text-sm text-gray-600">App Version:</div>
            <div class="text-sm font-medium"><?= APP_VERSION ?></div>
            
            <div class="text-sm text-gray-600">Company Name:</div>
            <div class="text-sm font-medium"><?= COMPANY_NAME ?></div>
            
            <div class="text-sm text-gray-600">Currency:</div>
            <div class="text-sm font-medium"><?= CURRENCY ?></div>
            
            <div class="text-sm text-gray-600">Financial Year:</div>
            <div class="text-sm font-medium">
                <?php 
                // Get financial year from company_finances table
                $finances = $db->select("SELECT financial_year_start, financial_year_end FROM company_finances LIMIT 1");
                if (!empty($finances)) {
                    echo date('d M Y', strtotime($finances[0]['financial_year_start'])) . ' - ' . 
                         date('d M Y', strtotime($finances[0]['financial_year_end']));
                } else {
                    echo 'Not set';
                }
                ?>
            </div>
            
            <div class="text-sm text-gray-600">Total Products:</div>
            <div class="text-sm font-medium">
                <?php 
                $productsCount = $db->select("SELECT COUNT(*) as count FROM products")[0]['count'];
                echo $productsCount;
                ?>
            </div>
            
            <div class="text-sm text-gray-600">Total Users:</div>
            <div class="text-sm font-medium">
                <?php 
                $usersCount = $db->select("SELECT COUNT(*) as count FROM users")[0]['count'];
                echo $usersCount;
                ?>
            </div>
            
            <div class="text-sm text-gray-600">PHP Version:</div>
            <div class="text-sm font-medium"><?= phpversion() ?></div>
            
            <div class="text-sm text-gray-600">Database:</div>
            <div class="text-sm font-medium">MySQL</div>
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

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>