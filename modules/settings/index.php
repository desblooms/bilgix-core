<?php 
$basePath = '../../';
include $basePath . 'includes/header.php'; 

if (!isManagerOrAdmin()) {
    $_SESSION['message'] = "You don't have permission to access settings!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'index.php');
}

$settings = $db->select("SELECT * FROM settings");
$settingsMap = [];
foreach ($settings as $setting) {
    $settingsMap[$setting['settingKey']] = $setting['settingValue'];
}
?>


    <!-- Header with search -->
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Settings</h2>
      
    </div>

    <!-- Settings Category Header -->
    <div class="mb-6">
        <span class="text-sm font-medium text-red-600 uppercase tracking-wider">System Preferences</span>
    </div>

    <!-- Settings Grid - Modern Card Style -->
    <div class="space-y-2">
        <?php 
        $cards = [
            ["Company", "fas fa-building", "Manage company information and business details", "company.php", "bg-gradient-to-br from-red-500 to-red-900", "border-red-200", "text-red-700", "bg-red-50"],
            ["Financial", "fas fa-money-bill-wave", "Configure financial year and tax settings", "company_finances.php", "bg-gradient-to-br from-orange-500 to-red-600", "border-orange-200", "text-orange-700", "bg-orange-50"],
            ["Invoice", "fas fa-file-invoice", "Configure invoice numbering and templates", "invoice.php", "bg-gradient-to-br from-green-500 to-green-800", "border-green-200", "text-green-700", "bg-green-50"],
            ["Users", "fas fa-users-cog", "Manage users, permissions, and roles", "../users/list.php", "bg-gradient-to-br from-purple-500 to-purple-800", "border-purple-200", "text-purple-700", "bg-purple-50"],
            ["Email", "fas fa-cogs", "Configure core system settings", "email_settings.php", "bg-gradient-to-br from-gray-500 to-gray-800", "border-gray-200", "text-gray-700", "bg-gray-50"],
            ["Notification", "fas fa-cogs", "Configure core system settings", "notification.php", "bg-gradient-to-br from-gray-500 to-gray-800", "border-gray-200", "text-gray-700", "bg-gray-50"],
            ["Backup", "fas fa-database", "Backup your data and restore backups", "backup.php", "bg-gradient-to-br from-yellow-500 to-yellow-800", "border-yellow-200", "text-yellow-700", "bg-yellow-50"],
            ["System", "fas fa-cogs", "Configure core system settings", "system.php", "bg-gradient-to-br from-gray-500 to-gray-800", "border-gray-200", "text-gray-700", "bg-gray-50"]
        
        ];
        foreach ($cards as [$title, $icon, $desc, $link, $iconBg, $borderColor, $textColor, $bgColor]) :
        ?>
        <a href="<?= $link ?>" class="block">
            <div class="bg-white rounded-2xl shadow-sm border <?= $borderColor ?> p-2 flex items-center space-x-2 hover:shadow-md transition-all transform hover:-translate-y-1">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-xl <?= $iconBg ?> text-white flex items-center justify-center shadow-sm">
                        <i class="<?= $icon ?> text-lg"></i>
                    </div>
                </div>
                <div class="flex-grow">
                    <h3 class="text-md font-medium text-gray-900"><?= $title ?></h3>
                    <p class="text-xs text-gray-500"><?= $desc ?></p>
                </div>
                <div class="flex-shrink-0">
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- System Info Section -->
    <div class="mt-10 mb-24">
        <div class="mb-4">
            <span class="text-sm font-medium text-red-600 uppercase tracking-wider">System Overview</span>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm p-3 border border-gray-200">
            <div class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-gray-100 p-3 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">App Version</span>
                        <span class="font-medium text-gray-900"><?= APP_VERSION ?></span>
                    </div>
                    <div class="bg-gray-100 p-2 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">Company</span>
                        <span class="font-medium text-gray-900"><?= COMPANY_NAME ?></span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-gray-100 p-2 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">Currency</span>
                        <span class="font-medium text-gray-900"><?= CURRENCY ?></span>
                    </div>
                    <div class="bg-gray-100 p-2 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">Financial Year</span>
                        <span class="font-medium text-gray-900 text-xs">
                        <?php 
                            $finances = $db->select("SELECT financial_year_start, financial_year_end FROM company_finances LIMIT 1");
                            echo !empty($finances) 
                                ? date('d M Y', strtotime($finances[0]['financial_year_start'])) . ' - ' . date('d M Y', strtotime($finances[0]['financial_year_end']))
                                : 'Not set';
                        ?>
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-gray-100 p-2 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">Products</span>
                        <span class="font-medium text-gray-900"><?= $db->select("SELECT COUNT(*) as count FROM products")[0]['count'] ?></span>
                    </div>
                    <div class="bg-gray-100 p-2 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">Users</span>
                        <span class="font-medium text-gray-900"><?= $db->select("SELECT COUNT(*) as count FROM users")[0]['count'] ?></span>
                    </div>
                </div>
                
                <!-- <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-100 p-3 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">PHP Version</span>
                        <span class="font-medium text-gray-900"><?= phpversion() ?></span>
                    </div>
                    <div class="bg-gray-100 p-3 rounded-xl">
                        <span class="text-xs text-gray-500 block mb-1">Database</span>
                        <span class="font-medium text-gray-900">MySQL</span>
                    </div>
                </div> -->
            </div>
        </div>
    </div>




<?php include $basePath . 'includes/footer.php'; ?>