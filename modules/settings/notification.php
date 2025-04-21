<?php 
// modules/settings/settings_notification.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check authorization - only admin and manager can access settings
checkAuthorization('manager');

// Retrieve current notification settings from database
$notificationSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'notification'");
$settings = [];

// Organize settings in a more accessible format
foreach ($notificationSettings as $setting) {
    $settings[$setting['settingKey']] = $setting['settingValue'];
}

// Default values if settings don't exist
$defaultSettings = [
    'low_stock_alert' => '1',
    'low_stock_threshold' => '5',
    'payment_reminder' => '0',
    'payment_reminder_days' => '3',
    'stock_movement_notification' => '0',
    'enable_email_notifications' => '0',
    'enable_sms_notifications' => '0',
    'notification_recipients' => '',
    'daily_report_summary' => '0',
    'out_of_stock_notification' => '1'
];

// Merge with defaults
foreach ($defaultSettings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $updatedSettings = [
        'low_stock_alert' => isset($_POST['low_stock_alert']) ? '1' : '0',
        'low_stock_threshold' => intval($_POST['low_stock_threshold']),
        'payment_reminder' => isset($_POST['payment_reminder']) ? '1' : '0',
        'payment_reminder_days' => intval($_POST['payment_reminder_days']),
        'stock_movement_notification' => isset($_POST['stock_movement_notification']) ? '1' : '0',
        'enable_email_notifications' => isset($_POST['enable_email_notifications']) ? '1' : '0',
        'enable_sms_notifications' => isset($_POST['enable_sms_notifications']) ? '1' : '0',
        'notification_recipients' => sanitize($_POST['notification_recipients']),
        'daily_report_summary' => isset($_POST['daily_report_summary']) ? '1' : '0',
        'out_of_stock_notification' => isset($_POST['out_of_stock_notification']) ? '1' : '0'
    ];
    
    // Ensure low stock threshold is valid
    if ($updatedSettings['low_stock_threshold'] < 1) {
        $updatedSettings['low_stock_threshold'] = 5;
    }
    
    // Ensure payment reminder days is valid
    if ($updatedSettings['payment_reminder_days'] < 1) {
        $updatedSettings['payment_reminder_days'] = 3;
    }
    
    // Update settings in database
    foreach ($updatedSettings as $key => $value) {
        // Check if setting exists
        $existingSetting = $db->select("SELECT id FROM settings WHERE settingKey = :key", ['key' => $key]);
        
        if (!empty($existingSetting)) {
            // Update existing setting
            $db->update('settings', 
                       ['settingValue' => $value, 'updatedAt' => date('Y-m-d H:i:s')], 
                       'settingKey = :key', 
                       ['key' => $key]);
        } else {
            // Insert new setting
            $db->insert('settings', [
                'settingKey' => $key,
                'settingValue' => $value,
                'settingGroup' => 'notification',
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    // Update the global LOW_STOCK_THRESHOLD constant
    $db->update('settings', 
               ['settingValue' => $updatedSettings['low_stock_threshold'], 'updatedAt' => date('Y-m-d H:i:s')], 
               'settingKey = :key', 
               ['key' => 'low_stock_threshold']);
    
    // Show success message
    $_SESSION['message'] = "Notification settings updated successfully!";
    $_SESSION['message_type'] = "success";
    
    // Redirect to refresh page
    redirect($basePath . 'modules/settings/settings_notification.php');
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Notification Settings</h2>
    </div>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <!-- Inventory Notifications Section -->
        <div class="mb-6">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Inventory Notifications</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="low_stock_alert" name="low_stock_alert" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['low_stock_alert'] == '1' ? 'checked' : '' ?>>
                    <label for="low_stock_alert" class="ml-2 block text-gray-700">Enable Low Stock Alerts</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Get notified when products reach the low stock threshold</p>
            </div>
            
            <div class="mb-4 pl-6">
                <label for="low_stock_threshold" class="block text-gray-700 font-medium mb-1">Low Stock Threshold</label>
                <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="1" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['low_stock_threshold'] ?>">
                <p class="text-xs text-gray-500 mt-1">Alert when stock reaches or falls below this quantity</p>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="out_of_stock_notification" name="out_of_stock_notification" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['out_of_stock_notification'] == '1' ? 'checked' : '' ?>>
                    <label for="out_of_stock_notification" class="ml-2 block text-gray-700">Out of Stock Notifications</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Get notified when products are completely out of stock</p>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="stock_movement_notification" name="stock_movement_notification" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['stock_movement_notification'] == '1' ? 'checked' : '' ?>>
                    <label for="stock_movement_notification" class="ml-2 block text-gray-700">Stock Movement Notifications</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Get notified for significant inventory movements (large purchases/sales)</p>
            </div>
        </div>
        
        <!-- Payment Notifications Section -->
        <div class="mb-6">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Payment Notifications</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="payment_reminder" name="payment_reminder" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['payment_reminder'] == '1' ? 'checked' : '' ?>>
                    <label for="payment_reminder" class="ml-2 block text-gray-700">Payment Reminders</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Send reminders for unpaid invoices</p>
            </div>
            
            <div class="mb-4 pl-6">
                <label for="payment_reminder_days" class="block text-gray-700 font-medium mb-1">Reminder Days</label>
                <input type="number" id="payment_reminder_days" name="payment_reminder_days" min="1" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['payment_reminder_days'] ?>">
                <p class="text-xs text-gray-500 mt-1">Send reminder after these many days of unpaid invoice</p>
            </div>
        </div>
        
        <!-- Report Notifications Section -->
        <div class="mb-6">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Report Notifications</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="daily_report_summary" name="daily_report_summary" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['daily_report_summary'] == '1' ? 'checked' : '' ?>>
                    <label for="daily_report_summary" class="ml-2 block text-gray-700">Daily Business Summary</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Receive daily summary of sales, purchases, and expenses</p>
            </div>
        </div>
        
        <!-- Notification Delivery Section -->
        <div class="mb-6">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Notification Delivery</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="enable_email_notifications" name="enable_email_notifications" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['enable_email_notifications'] == '1' ? 'checked' : '' ?>>
                    <label for="enable_email_notifications" class="ml-2 block text-gray-700">Enable Email Notifications</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Send notifications via email</p>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="enable_sms_notifications" name="enable_sms_notifications" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['enable_sms_notifications'] == '1' ? 'checked' : '' ?>>
                    <label for="enable_sms_notifications" class="ml-2 block text-gray-700">Enable SMS Notifications</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Send notifications via SMS (requires Twilio setup)</p>
            </div>
            
            <div class="mb-4">
                <label for="notification_recipients" class="block text-gray-700 font-medium mb-1">Notification Recipients</label>
                <textarea id="notification_recipients" name="notification_recipients" rows="2" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $settings['notification_recipients'] ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Comma-separated list of email addresses or phone numbers (with country code)</p>
            </div>
        </div>
        
        <!-- Note about additional setup -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg text-blue-700 text-sm">
            <p class="font-medium">Note:</p>
            <p>Email and SMS notifications require additional setup in the system configuration. Contact your administrator if you need assistance.</p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Notification Settings
            </button>
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
    // Toggle dependent settings
    document.addEventListener('DOMContentLoaded', function() {
        const lowStockAlert = document.getElementById('low_stock_alert');
        const lowStockThreshold = document.getElementById('low_stock_threshold');
        
        const paymentReminder = document.getElementById('payment_reminder');
        const paymentReminderDays = document.getElementById('payment_reminder_days');
        
        function toggleRelatedFields() {
            // Low stock settings
            if (lowStockAlert && lowStockThreshold) {
                lowStockThreshold.disabled = !lowStockAlert.checked;
                lowStockThreshold.parentNode.classList.toggle('opacity-50', !lowStockAlert.checked);
            }
            
            // Payment reminder settings
            if (paymentReminder && paymentReminderDays) {
                paymentReminderDays.disabled = !paymentReminder.checked;
                paymentReminderDays.parentNode.classList.toggle('opacity-50', !paymentReminder.checked);
            }
        }
        
        // Initial toggle
        toggleRelatedFields();
        
        // Event listeners
        if (lowStockAlert) {
            lowStockAlert.addEventListener('change', toggleRelatedFields);
        }
        
        if (paymentReminder) {
            paymentReminder.addEventListener('change', toggleRelatedFields);
        }
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>