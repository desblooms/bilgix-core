<?php 
// modules/settings/settings_system.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Get current settings
$settings = $db->select("SELECT * FROM settings ORDER BY settingGroup, settingKey");

// Group settings by category
$settingGroups = [];
foreach ($settings as $setting) {
    $group = $setting['settingGroup'];
    if (!isset($settingGroups[$group])) {
        $settingGroups[$group] = [];
    }
    $settingGroups[$group][] = $setting;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $updatedSettings = [];
    $errors = [];
    
    foreach ($_POST as $key => $value) {
        // Skip non-setting fields
        if (strpos($key, 'setting_') !== 0) {
            continue;
        }
        
        $settingKey = substr($key, 8); // Remove 'setting_' prefix
        $settingValue = sanitize($value);
        
        // Validation for specific settings
        if ($settingKey == 'low_stock_threshold') {
            if (!is_numeric($settingValue) || $settingValue < 0) {
                $errors[] = "Low stock threshold must be a positive number";
                continue;
            }
        }
        
        $updatedSettings[$settingKey] = $settingValue;
    }
    
    // If no errors, update settings
    if (empty($errors)) {
        $success = true;
        
        foreach ($updatedSettings as $settingKey => $settingValue) {
            $updated = $db->update(
                'settings', 
                ['settingValue' => $settingValue, 'updatedAt' => date('Y-m-d H:i:s')], 
                'settingKey = :settingKey', 
                ['settingKey' => $settingKey]
            );
            
            if (!$updated) {
                $success = false;
            }
        }
        
        if ($success) {
            $_SESSION['message'] = "Settings updated successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/settings/settings_system.php');
        } else {
            $errors[] = "Failed to update some settings. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">System Settings</h2>
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
        <p class="text-gray-600 text-sm">
            <i class="fas fa-info-circle mr-1"></i> These settings control various aspects of your Billgix application. Changes will take effect immediately.
        </p>
    </div>
    
    <form method="POST" class="space-y-6">
        <!-- Company Settings -->
        <?php if (isset($settingGroups['company'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Company Information</h3>
            
            <div class="space-y-4">
                <?php foreach($settingGroups['company'] as $setting): ?>
                <div>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                    </label>
                    <?php if ($setting['settingKey'] == 'company_address'): ?>
                    <textarea id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" rows="3" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $setting['settingValue'] ?></textarea>
                    <?php else: ?>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Regional Settings -->
        <?php if (isset($settingGroups['regional'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Regional Settings</h3>
            
            <div class="space-y-4">
                <?php foreach($settingGroups['regional'] as $setting): ?>
                <div>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                    </label>
                    <?php if ($setting['settingKey'] == 'currency'): ?>
                    <select id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                        <option value="₹" <?= $setting['settingValue'] == '₹' ? 'selected' : '' ?>>₹ - Indian Rupee</option>
                        <option value="$" <?= $setting['settingValue'] == '$' ? 'selected' : '' ?>>$ - US Dollar</option>
                        <option value="€" <?= $setting['settingValue'] == '€' ? 'selected' : '' ?>>€ - Euro</option>
                        <option value="£" <?= $setting['settingValue'] == '£' ? 'selected' : '' ?>>£ - British Pound</option>
                        <option value="¥" <?= $setting['settingValue'] == '¥' ? 'selected' : '' ?>>¥ - Japanese Yen</option>
                    </select>
                    <?php elseif ($setting['settingKey'] == 'date_format'): ?>
                    <select id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                        <option value="Y-m-d" <?= $setting['settingValue'] == 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD (e.g., 2023-12-31)</option>
                        <option value="m/d/Y" <?= $setting['settingValue'] == 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY (e.g., 12/31/2023)</option>
                        <option value="d/m/Y" <?= $setting['settingValue'] == 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY (e.g., 31/12/2023)</option>
                        <option value="d-m-Y" <?= $setting['settingValue'] == 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY (e.g., 31-12-2023)</option>
                    </select>
                    <?php else: ?>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Inventory Settings -->
        <?php if (isset($settingGroups['inventory'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Inventory Settings</h3>
            
            <div class="space-y-4">
                <?php foreach($settingGroups['inventory'] as $setting): ?>
                <div>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                    </label>
                    <?php if ($setting['settingKey'] == 'low_stock_threshold'): ?>
                    <input type="number" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" min="0" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <p class="text-xs text-gray-500 mt-1">Products with stock below this threshold will be marked as low stock</p>
                    <?php else: ?>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Invoice Settings -->
        <?php if (isset($settingGroups['invoice'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Invoice Settings</h3>
            
            <div class="space-y-4">
                <?php foreach($settingGroups['invoice'] as $setting): ?>
                <div>
                    <?php if ($setting['settingKey'] == 'enable_pdf'): ?>
                    <div class="flex items-center">
                        <input type="checkbox" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" value="1" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $setting['settingValue'] == '1' ? 'checked' : '' ?>>
                        <label for="setting_<?= $setting['settingKey'] ?>" class="ml-2 block text-gray-700 font-medium">
                            Enable PDF Invoices
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-6">Generate downloadable PDF invoices for sales</p>
                    <?php else: ?>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                    </label>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Notification Settings -->
        <?php if (isset($settingGroups['notification'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Notification Settings</h3>
            
            <div class="space-y-4">
                <?php foreach($settingGroups['notification'] as $setting): ?>
                <div>
                    <?php if ($setting['settingKey'] == 'enable_sms'): ?>
                    <div class="flex items-center">
                        <input type="checkbox" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" value="1" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $setting['settingValue'] == '1' ? 'checked' : '' ?>>
                        <label for="setting_<?= $setting['settingKey'] ?>" class="ml-2 block text-gray-700 font-medium">
                            Enable SMS Notifications
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-6">Send SMS notifications for invoices and updates</p>
                    <?php else: ?>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                    </label>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Financial Settings -->
        <?php if (isset($settingGroups['financial'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Financial Settings</h3>
            
            <div class="space-y-4">
                <?php foreach($settingGroups['financial'] as $setting): ?>
                <div>
                    <?php if ($setting['settingKey'] == 'enable_profit_tracking'): ?>
                    <div class="flex items-center">
                        <input type="checkbox" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" value="1" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $setting['settingValue'] == '1' ? 'checked' : '' ?>>
                        <label for="setting_<?= $setting['settingKey'] ?>" class="ml-2 block text-gray-700 font-medium">
                            Enable Profit Tracking
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 ml-6">Track profit margins for each product sold</p>
                    <?php elseif ($setting['settingKey'] == 'tax_rate'): ?>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        Tax Rate (%)
                    </label>
                    <input type="number" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" min="0" max="100" step="0.01" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <?php elseif ($setting['settingKey'] == 'financial_year_start'): ?>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        Financial Year Start (MM-DD)
                    </label>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" placeholder="04-01" pattern="[0-1][0-9]-[0-3][0-9]" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <p class="text-xs text-gray-500 mt-1">Format: MM-DD (e.g., 04-01 for April 1st)</p>
                    <?php else: ?>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                    </label>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- App Settings -->
        <?php if (isset($settingGroups['app'])): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Application Settings</h3>
            
            <div class="space-y-4">
                <?php foreach($settingGroups['app'] as $setting): ?>
                <div>
                    <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                        <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                    </label>
                    <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Any other setting groups not specifically handled -->
        <?php foreach($settingGroups as $groupName => $groupSettings): ?>
            <?php if (!in_array($groupName, ['company', 'regional', 'inventory', 'invoice', 'notification', 'financial', 'app'])): ?>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-medium border-b pb-2 mb-4"><?= ucwords($groupName) ?> Settings</h3>
                
                <div class="space-y-4">
                    <?php foreach($groupSettings as $setting): ?>
                    <div>
                        <label for="setting_<?= $setting['settingKey'] ?>" class="block text-gray-700 font-medium mb-2">
                            <?= ucwords(str_replace('_', ' ', $setting['settingKey'])) ?>
                        </label>
                        <input type="text" id="setting_<?= $setting['settingKey'] ?>" name="setting_<?= $setting['settingKey'] ?>" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $setting['settingValue'] ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
        </div>
    </form>
</div>



<?php
// Include footer
include $basePath . 'includes/footer.php';
?>