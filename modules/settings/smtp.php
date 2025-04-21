<?php 
// modules/settings/smtp.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Get current SMTP settings
$settings = $db->select("SELECT * FROM settings WHERE settingGroup = 'email'");

// Transform settings into key-value format for easier access
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['settingKey']] = $setting['settingValue'];
}

// Save settings if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateCount = 0;
    $errors = [];

    // SMTP Settings
    $smtpEnabled = isset($_POST['smtp_enabled']) ? 1 : 0;
    $smtpHost = sanitize($_POST['smtp_host']);
    $smtpPort = (int)$_POST['smtp_port'];
    $smtpUsername = sanitize($_POST['smtp_username']);
    $smtpPassword = $_POST['smtp_password']; // Will be updated only if not empty
    $smtpFromEmail = sanitize($_POST['smtp_from_email']);
    $smtpFromName = sanitize($_POST['smtp_from_name']);
    $smtpEncryption = sanitize($_POST['smtp_encryption']);
    
    // Validation
    if ($smtpEnabled) {
        if (empty($smtpHost)) $errors[] = "SMTP Host is required when SMTP is enabled";
        if (empty($smtpPort)) $errors[] = "SMTP Port is required when SMTP is enabled";
        if (empty($smtpFromEmail)) $errors[] = "From Email is required when SMTP is enabled";
        if (empty($smtpFromName)) $errors[] = "From Name is required when SMTP is enabled";
        
        // Test SMTP connection if requested
        if (isset($_POST['test_connection']) && empty($errors)) {
            require_once $basePath . 'vendor/autoload.php';
            
            $testEmail = $settingsArray['company_email'] ?? $_SESSION['user_email'] ?? $smtpFromEmail;
            
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->Port = $smtpPort;
                
                if (!empty($smtpUsername) && (!empty($smtpPassword) || (!empty($settingsArray['smtp_password']) && empty($_POST['smtp_password'])))) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUsername;
                    
                    // If password field is empty, use the stored password
                    if (empty($_POST['smtp_password']) && !empty($settingsArray['smtp_password'])) {
                        $mail->Password = $settingsArray['smtp_password'];
                    } else {
                        $mail->Password = $smtpPassword;
                    }
                }
                
                if ($smtpEncryption !== 'none') {
                    $mail->SMTPSecure = $smtpEncryption;
                }
                
                $mail->SMTPDebug = 0; // Disable debug output
                $mail->Timeout = 10; // Set a timeout of 10 seconds
                
                // Set sender and recipient for test
                $mail->setFrom($smtpFromEmail, $smtpFromName);
                $mail->addAddress($testEmail);
                
                // Set test email content
                $mail->isHTML(true);
                $mail->Subject = 'SMTP Test from ' . APP_NAME;
                $mail->Body = 'This is a test email from ' . APP_NAME . ' to verify your SMTP settings.';
                
                // Attempt to send the email
                $mail->send();
                
                $_SESSION['message'] = "SMTP connection test successful! A test email was sent to $testEmail.";
                $_SESSION['message_type'] = "success";
            } catch (Exception $e) {
                $errors[] = "SMTP Connection Test Failed: " . $mail->ErrorInfo;
            }
        }
    }
    
    // Only update settings if there are no errors
    if (empty($errors)) {
        // Settings to update
        $settingsToUpdate = [
            'smtp_enabled' => $smtpEnabled,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_username' => $smtpUsername,
            'smtp_from_email' => $smtpFromEmail,
            'smtp_from_name' => $smtpFromName,
            'smtp_encryption' => $smtpEncryption
        ];
        
        // Only update password if provided
        if (!empty($smtpPassword)) {
            $settingsToUpdate['smtp_password'] = $smtpPassword;
        }
        
        foreach ($settingsToUpdate as $key => $value) {
            // Check if setting exists
            $existingSetting = $db->select("SELECT id FROM settings WHERE settingKey = :key", ['key' => $key]);
            
            if (!empty($existingSetting)) {
                // Update existing setting
                $updated = $db->update('settings', 
                                      ['settingValue' => $value, 'updatedAt' => date('Y-m-d H:i:s')], 
                                      'settingKey = :key', 
                                      ['key' => $key]);
                if ($updated) {
                    $updateCount++;
                }
            } else {
                // Insert new setting
                $inserted = $db->insert('settings', [
                    'settingKey' => $key,
                    'settingValue' => $value,
                    'settingGroup' => 'email',
                    'updatedAt' => date('Y-m-d H:i:s')
                ]);
                if ($inserted) {
                    $updateCount++;
                }
            }
        }
        
        if (!isset($_POST['test_connection'])) {
            if ($updateCount > 0) {
                $_SESSION['message'] = "Email settings updated successfully!";
                $_SESSION['message_type'] = "success";
                
                // Redirect to refresh the page with new settings
                redirect($basePath . 'modules/settings/smtp.php');
            } else {
                $errors[] = "No settings were updated.";
            }
        }
    }
}
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Email Settings</h2>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <ul class="list-disc list-inside">
            <?php foreach($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Settings Navigation -->
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="flex overflow-x-auto no-scrollbar">
            <a href="index.php" class="px-4 py-3 border-b-2 border-transparent font-medium text-gray-600 hover:text-gray-800 hover:border-gray-300 whitespace-nowrap">
                Company Details
            </a>
            <a href="smtp.php" class="px-4 py-3 border-b-2 border-red-900 font-medium text-red-900 whitespace-nowrap">
                Email Settings
            </a>
            <a href="invoice.php" class="px-4 py-3 border-b-2 border-transparent font-medium text-gray-600 hover:text-gray-800 hover:border-gray-300 whitespace-nowrap">
                Invoice Settings
            </a>
            <a href="backup.php" class="px-4 py-3 border-b-2 border-transparent font-medium text-gray-600 hover:text-gray-800 hover:border-gray-300 whitespace-nowrap">
                Backup & Restore
            </a>
        </div>
    </div>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <!-- SMTP Configuration -->
        <div class="mb-4">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">SMTP Configuration</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= ($settingsArray['smtp_enabled'] ?? 0) ? 'checked' : '' ?>>
                    <label for="smtp_enabled" class="ml-2 block text-gray-700 font-medium">Enable SMTP</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Enable to send emails using SMTP server</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="smtp_host" class="block text-gray-700 font-medium mb-2">SMTP Host</label>
                    <input type="text" id="smtp_host" name="smtp_host" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settingsArray['smtp_host'] ?? '' ?>">
                    <p class="text-xs text-gray-500 mt-1">e.g., smtp.gmail.com</p>
                </div>
                
                <div>
                    <label for="smtp_port" class="block text-gray-700 font-medium mb-2">SMTP Port</label>
                    <input type="number" id="smtp_port" name="smtp_port" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settingsArray['smtp_port'] ?? '587' ?>">
                    <p class="text-xs text-gray-500 mt-1">Common ports: 587 (TLS), 465 (SSL), 25 (None)</p>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="smtp_encryption" class="block text-gray-700 font-medium mb-2">Encryption</label>
                <select id="smtp_encryption" name="smtp_encryption" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                    <option value="tls" <?= ($settingsArray['smtp_encryption'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= ($settingsArray['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="none" <?= ($settingsArray['smtp_encryption'] ?? '') == 'none' ? 'selected' : '' ?>>None</option>
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="smtp_username" class="block text-gray-700 font-medium mb-2">SMTP Username</label>
                    <input type="text" id="smtp_username" name="smtp_username" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settingsArray['smtp_username'] ?? '' ?>">
                    <p class="text-xs text-gray-500 mt-1">Usually your email address</p>
                </div>
                
                <div>
                    <label for="smtp_password" class="block text-gray-700 font-medium mb-2">SMTP Password</label>
                    <input type="password" id="smtp_password" name="smtp_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="<?= !empty($settingsArray['smtp_password']) ? '••••••••' : '' ?>">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="smtp_from_email" class="block text-gray-700 font-medium mb-2">From Email</label>
                    <input type="email" id="smtp_from_email" name="smtp_from_email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settingsArray['smtp_from_email'] ?? '' ?>">
                </div>
                
                <div>
                    <label for="smtp_from_name" class="block text-gray-700 font-medium mb-2">From Name</label>
                    <input type="text" id="smtp_from_name" name="smtp_from_name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settingsArray['smtp_from_name'] ?? COMPANY_NAME ?>">
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex space-x-4">
            <button type="submit" name="save_settings" class="flex-1 bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
            
            <button type="submit" name="test_connection" class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-vial mr-2"></i> Test Connection
            </button>
        </div>
    </form>
    
    <!-- Email Templates Section -->
    <div class="bg-white rounded-lg shadow p-4 mt-4">
        <h3 class="text-lg font-medium border-b pb-2 mb-4">Email Templates</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="email_templates.php?type=invoice" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                <div class="text-center">
                    <i class="fas fa-file-invoice text-3xl text-slate-950 mb-2"></i>
                    <h4 class="font-medium">Invoice Email</h4>
                    <p class="text-sm text-gray-600">Edit template for sending invoices</p>
                </div>
            </a>
            
            <a href="email_templates.php?type=receipt" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                <div class="text-center">
                    <i class="fas fa-receipt text-3xl text-green-600 mb-2"></i>
                    <h4 class="font-medium">Receipt Email</h4>
                    <p class="text-sm text-gray-600">Edit template for receipts</p>
                </div>
            </a>
            
            <a href="email_templates.php?type=reminder" class="p-4 border rounded-lg hover:bg-gray-50 transition">
                <div class="text-center">
                    <i class="fas fa-bell text-3xl text-yellow-600 mb-2"></i>
                    <h4 class="font-medium">Payment Reminder</h4>
                    <p class="text-sm text-gray-600">Edit template for payment reminders</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Bottom Navigation -->

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
        <span class="text-xs mt-1">New Sale