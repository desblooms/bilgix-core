<?php 
// modules/settings/settings_invoice.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin or manager only)
checkAuthorization('manager');

// Get current settings
$invoiceSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'invoice'");
$settings = [];

// Convert array of settings to associative array for easier access
foreach ($invoiceSettings as $setting) {
    $settings[$setting['settingKey']] = $setting['settingValue'];
}

// Set defaults for missing settings
$defaults = [
    'invoice_prefix' => 'INV-',
    'invoice_next_number' => '1001',
    'invoice_footer_text' => 'Thank you for your business!',
    'invoice_terms' => "1. Goods once sold will not be taken back or exchanged.\n2. All disputes are subject to local jurisdiction.\n3. E. & O.E.: Errors and Omissions Excepted.",
    'invoice_include_logo' => '1',
    'invoice_tax_label' => 'GST',
    'invoice_default_tax_rate' => '18',
    'invoice_due_days' => '30',
    'invoice_currency_symbol_position' => 'before', // 'before' or 'after'
    'enable_invoice_email' => '0',
    'enable_invoice_sms' => '0'
];

// Fill in defaults for any missing settings
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $newSettings = [
        'invoice_prefix' => sanitize($_POST['invoice_prefix']),
        'invoice_next_number' => intval($_POST['invoice_next_number']),
        'invoice_footer_text' => sanitize($_POST['invoice_footer_text']),
        'invoice_terms' => sanitize($_POST['invoice_terms']),
        'invoice_include_logo' => isset($_POST['invoice_include_logo']) ? '1' : '0',
        'invoice_tax_label' => sanitize($_POST['invoice_tax_label']),
        'invoice_default_tax_rate' => floatval($_POST['invoice_default_tax_rate']),
        'invoice_due_days' => intval($_POST['invoice_due_days']),
        'invoice_currency_symbol_position' => sanitize($_POST['invoice_currency_symbol_position']),
        'enable_invoice_email' => isset($_POST['enable_invoice_email']) ? '1' : '0',
        'enable_invoice_sms' => isset($_POST['enable_invoice_sms']) ? '1' : '0'
    ];
    
    // Validation
    $errors = [];
    
    if (empty($newSettings['invoice_prefix'])) {
        $errors[] = "Invoice prefix cannot be empty";
    }
    
    if ($newSettings['invoice_next_number'] <= 0) {
        $errors[] = "Next invoice number must be greater than zero";
    }
    
    if ($newSettings['invoice_default_tax_rate'] < 0 || $newSettings['invoice_default_tax_rate'] > 100) {
        $errors[] = "Tax rate must be between 0 and 100";
    }
    
    if ($newSettings['invoice_due_days'] < 0) {
        $errors[] = "Due days must be 0 or greater";
    }
    
    // If no errors, update settings
    if (empty($errors)) {
        // Begin transaction
        $db->getConnection()->beginTransaction();
        $success = true;
        
        try {
            foreach ($newSettings as $key => $value) {
                // Check if setting exists
                $existingSetting = $db->select("SELECT id FROM settings WHERE settingKey = :key", ['key' => $key]);
                
                if (!empty($existingSetting)) {
                    // Update existing setting
                    $updated = $db->update('settings', 
                                        ['settingValue' => $value, 'updatedAt' => date('Y-m-d H:i:s')], 
                                        'settingKey = :key', 
                                        ['key' => $key]);
                    
                    if (!$updated) {
                        $success = false;
                        break;
                    }
                } else {
                    // Insert new setting
                    $data = [
                        'settingKey' => $key,
                        'settingValue' => $value,
                        'settingGroup' => 'invoice',
                        'updatedAt' => date('Y-m-d H:i:s')
                    ];
                    
                    $inserted = $db->insert('settings', $data);
                    
                    if (!$inserted) {
                        $success = false;
                        break;
                    }
                }
            }
            
            if ($success) {
                $db->getConnection()->commit();
                $_SESSION['message'] = "Invoice settings updated successfully!";
                $_SESSION['message_type'] = "success";
                
                // Update settings array with new values
                $settings = $newSettings;
            } else {
                throw new Exception("Failed to update one or more settings");
            }
        } catch (Exception $e) {
            $db->getConnection()->rollBack();
            $errors[] = "Error updating settings: " . $e->getMessage();
        }
    }
}

// Handle logo upload
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    $fileType = $_FILES['logo']['type'];
    $fileSize = $_FILES['logo']['size'];
    
    if (!in_array($fileType, $allowedTypes)) {
        $errors[] = "Logo must be an image file (JPG, PNG, or GIF)";
    } elseif ($fileSize > $maxSize) {
        $errors[] = "Logo file size must be less than 2MB";
    } else {
        // Upload directory
        $uploadDir = $basePath . 'assets/images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Save the file
        $destination = $uploadDir . 'logo.png';
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
            $_SESSION['message'] = "Logo uploaded successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $errors[] = "Failed to upload logo. Please check file permissions.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Invoice Settings</h2>
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
    
    <!-- Settings Tabs -->
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="flex overflow-x-auto">
            <a href="#general" class="px-4 py-3 font-medium text-slate-950 border-b-2 border-red-900 whitespace-nowrap">
                General
            </a>
            <a href="#content" class="px-4 py-3 font-medium text-gray-600 hover:text-slate-950 whitespace-nowrap">
                Content
            </a>
            <a href="#appearance" class="px-4 py-3 font-medium text-gray-600 hover:text-slate-950 whitespace-nowrap">
                Appearance
            </a>
            <a href="#notifications" class="px-4 py-3 font-medium text-gray-600 hover:text-slate-950 whitespace-nowrap">
                Notifications
            </a>
        </div>
    </div>
    
    <!-- Settings Form -->
    <form method="POST" class="bg-white rounded-lg shadow p-4" enctype="multipart/form-data">
        <!-- General Settings Section -->
        <div id="general" class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4 pb-2 border-b">General Settings</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="invoice_prefix" class="block text-gray-700 font-medium mb-2">Invoice Prefix</label>
                    <input type="text" id="invoice_prefix" name="invoice_prefix" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['invoice_prefix'] ?>">
                    <p class="text-xs text-gray-500 mt-1">Prefix for invoice numbers (e.g., INV-)</p>
                </div>
                
                <div>
                    <label for="invoice_next_number" class="block text-gray-700 font-medium mb-2">Next Invoice Number</label>
                    <input type="number" id="invoice_next_number" name="invoice_next_number" min="1" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['invoice_next_number'] ?>">
                    <p class="text-xs text-gray-500 mt-1">Next sequential number to use</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="invoice_tax_label" class="block text-gray-700 font-medium mb-2">Tax Label</label>
                    <input type="text" id="invoice_tax_label" name="invoice_tax_label" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['invoice_tax_label'] ?>">
                    <p class="text-xs text-gray-500 mt-1">Label for tax (e.g., GST, VAT, Sales Tax)</p>
                </div>
                
                <div>
                    <label for="invoice_default_tax_rate" class="block text-gray-700 font-medium mb-2">Default Tax Rate (%)</label>
                    <input type="number" id="invoice_default_tax_rate" name="invoice_default_tax_rate" min="0" max="100" step="0.01" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['invoice_default_tax_rate'] ?>">
                    <p class="text-xs text-gray-500 mt-1">Default tax percentage to apply</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="invoice_due_days" class="block text-gray-700 font-medium mb-2">Default Due Days</label>
                    <input type="number" id="invoice_due_days" name="invoice_due_days" min="0" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['invoice_due_days'] ?>">
                    <p class="text-xs text-gray-500 mt-1">Days until payment is due (0 for immediate)</p>
                </div>
                
                <div>
                    <label for="invoice_currency_symbol_position" class="block text-gray-700 font-medium mb-2">Currency Symbol Position</label>
                    <select id="invoice_currency_symbol_position" name="invoice_currency_symbol_position" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                        <option value="before" <?= $settings['invoice_currency_symbol_position'] === 'before' ? 'selected' : '' ?>>Before amount (<?= CURRENCY ?>100.00)</option>
                        <option value="after" <?= $settings['invoice_currency_symbol_position'] === 'after' ? 'selected' : '' ?>>After amount (100.00<?= CURRENCY ?>)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Position of currency symbol relative to amount</p>
                </div>
            </div>
        </div>
        
        <!-- Content Settings Section -->
        <div id="content" class="mb-6 hidden">
            <h3 class="text-lg font-medium text-gray-800 mb-4 pb-2 border-b">Invoice Content</h3>
            
            <div class="mb-4">
                <label for="invoice_footer_text" class="block text-gray-700 font-medium mb-2">Footer Text</label>
                <textarea id="invoice_footer_text" name="invoice_footer_text" rows="2" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $settings['invoice_footer_text'] ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Text to display at the bottom of each invoice</p>
            </div>
            
            <div class="mb-4">
                <label for="invoice_terms" class="block text-gray-700 font-medium mb-2">Terms & Conditions</label>
                <textarea id="invoice_terms" name="invoice_terms" rows="4" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $settings['invoice_terms'] ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Terms and conditions to include on invoices</p>
            </div>
        </div>
        
        <!-- Appearance Settings Section -->
        <div id="appearance" class="mb-6 hidden">
            <h3 class="text-lg font-medium text-gray-800 mb-4 pb-2 border-b">Invoice Appearance</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="invoice_include_logo" name="invoice_include_logo" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['invoice_include_logo'] ? 'checked' : '' ?>>
                    <label for="invoice_include_logo" class="ml-2 block text-gray-700">Include Company Logo</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Display your company logo on invoices</p>
            </div>
            
            <div class="mb-4">
                <label for="logo" class="block text-gray-700 font-medium mb-2">Upload Logo</label>
                <input type="file" id="logo" name="logo" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                <p class="text-xs text-gray-500 mt-1">Upload a logo image (PNG, JPG, or GIF, max 2MB)</p>
                
                <?php if (file_exists($basePath . 'assets/images/logo.png')): ?>
                <div class="mt-2 p-2 border rounded-lg bg-gray-50">
                    <p class="text-sm text-gray-700 mb-2">Current Logo:</p>
                    <img src="<?= $basePath ?>assets/images/logo.png" alt="Company Logo" class="max-h-20">
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Notification Settings Section -->
        <div id="notifications" class="mb-6 hidden">
            <h3 class="text-lg font-medium text-gray-800 mb-4 pb-2 border-b">Invoice Notifications</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="enable_invoice_email" name="enable_invoice_email" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['enable_invoice_email'] ? 'checked' : '' ?>>
                    <label for="enable_invoice_email" class="ml-2 block text-gray-700">Enable Email Invoices</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Allow sending invoices via email to customers</p>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="enable_invoice_sms" name="enable_invoice_sms" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['enable_invoice_sms'] ? 'checked' : '' ?>>
                    <label for="enable_invoice_sms" class="ml-2 block text-gray-700">Enable SMS Notifications</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Allow sending invoice links via SMS to customers</p>
                <p class="text-xs text-amber-600 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Requires Twilio integration. Configure in <a href="settings_api.php" class="underline">API Settings</a>.
                </p>
            </div>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
        </div>
    </form>
    
    <!-- Invoice Preview -->
    <div class="mt-4">
        <a href="../sales/print_invoice.php?sample=true" target="_blank" class="block w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-lg text-center hover:bg-gray-300 transition">
            <i class="fas fa-eye mr-2"></i> Preview Sample Invoice
        </a>
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
    // Tab navigation
    document.addEventListener('DOMContentLoaded', function() {
        const tabLinks = document.querySelectorAll('[href^="#"]');
        const tabContents = document.querySelectorAll('[id^="general"], [id^="content"], [id^="appearance"], [id^="notifications"]');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show selected tab content
                const targetId = this.getAttribute('href').substring(1);
                document.getElementById(targetId).classList.remove('hidden');
                
                // Update active tab styling
                tabLinks.forEach(tabLink => {
                    tabLink.classList.remove('border-b-2', 'border-red-900', 'text-slate-950');
                    tabLink.classList.add('text-gray-600');
                });
                
                this.classList.add('border-b-2', 'border-red-900', 'text-slate-950');
                this.classList.remove('text-gray-600');
            });
        });
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>