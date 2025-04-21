<?php 
// modules/settings/settings_notification.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Get current email settings from database
$emailSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'notification'");
$settings = [];

// Convert to associative array for easier access
if (!empty($emailSettings)) {
    foreach ($emailSettings as $setting) {
        $settings[$setting['settingKey']] = $setting['settingValue'];
    }
}

// Default values if settings don't exist
$defaults = [
    'smtp_enabled' => '0',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'email_from' => COMPANY_EMAIL,
    'email_from_name' => COMPANY_NAME,
    'notify_low_stock' => '0',
    'notify_new_order' => '1',
    'notify_payment_received' => '1',
    'admin_email' => '',
    'email_footer' => 'Thank you for using ' . APP_NAME,
];

// Merge defaults with existing settings
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $success = '';
    
    // Get form data
    $smtpEnabled = isset($_POST['smtp_enabled']) ? '1' : '0';
    $smtpHost = sanitize($_POST['smtp_host']);
    $smtpPort = sanitize($_POST['smtp_port']);
    $smtpUsername = sanitize($_POST['smtp_username']);
    $smtpPassword = $_POST['smtp_password']; // Only save if not empty
    $smtpEncryption = sanitize($_POST['smtp_encryption']);
    $emailFrom = sanitize($_POST['email_from']);
    $emailFromName = sanitize($_POST['email_from_name']);
    $notifyLowStock = isset($_POST['notify_low_stock']) ? '1' : '0';
    $notifyNewOrder = isset($_POST['notify_new_order']) ? '1' : '0';
    $notifyPaymentReceived = isset($_POST['notify_payment_received']) ? '1' : '0';
    $adminEmail = sanitize($_POST['admin_email']);
    $emailFooter = sanitize($_POST['email_footer']);
    
    // Validate email fields
    if ($smtpEnabled === '1') {
        if (empty($smtpHost)) {
            $error = "SMTP Host is required when SMTP is enabled";
        } else if (empty($smtpPort)) {
            $error = "SMTP Port is required when SMTP is enabled";
        } else if (empty($smtpUsername)) {
            $error = "SMTP Username is required when SMTP is enabled";
        } else if (empty($smtpPassword) && empty($settings['smtp_password'])) {
            $error = "SMTP Password is required when SMTP is enabled";
        }
    }
    
    if (empty($emailFrom) || !filter_var($emailFrom, FILTER_VALIDATE_EMAIL)) {
        $error = "A valid From Email address is required";
    }
    
    if (empty($emailFromName)) {
        $error = "From Name is required";
    }
    
    if (!empty($adminEmail) && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Admin Email must be a valid email address";
    }
    
    // If no errors, update settings
    if (empty($error)) {
        // Settings to update
        $settingsToUpdate = [
            'smtp_enabled' => $smtpEnabled,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_username' => $smtpUsername,
            'smtp_encryption' => $smtpEncryption,
            'email_from' => $emailFrom,
            'email_from_name' => $emailFromName,
            'notify_low_stock' => $notifyLowStock,
            'notify_new_order' => $notifyNewOrder,
            'notify_payment_received' => $notifyPaymentReceived,
            'admin_email' => $adminEmail,
            'email_footer' => $emailFooter,
        ];
        
        // Only update password if a new one is provided
        if (!empty($smtpPassword)) {
            $settingsToUpdate['smtp_password'] = $smtpPassword;
        }
        
        // Update or insert each setting
        foreach ($settingsToUpdate as $key => $value) {
            $existingSetting = $db->select("SELECT id FROM settings WHERE settingKey = :key", ['key' => $key]);
            
            if (!empty($existingSetting)) {
                // Update existing setting
                $db->update(
                    'settings', 
                    ['settingValue' => $value, 'updatedAt' => date('Y-m-d H:i:s')], 
                    'settingKey = :key', 
                    ['key' => $key]
                );
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
        
        // Test email if requested
        if (isset($_POST['test_email']) && $_POST['test_email'] === '1') {
            // Try to send a test email
            $testResult = sendTestEmail($emailFrom, $emailFromName, $adminEmail ?: $emailFrom, $smtpEnabled, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword ?: $settings['smtp_password'], $smtpEncryption);
            
            if ($testResult === true) {
                $success = "Email settings saved successfully and test email was sent!";
            } else {
                $success = "Email settings saved successfully!";
                $error = "Failed to send test email: " . $testResult;
            }
        } else {
            $success = "Email settings saved successfully!";
        }
        
        // Refresh settings
        $emailSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'notification'");
        foreach ($emailSettings as $setting) {
            $settings[$setting['settingKey']] = $setting['settingValue'];
        }
    }
}

/**
 * Send a test email using the configured settings
 * 
 * @param string $from From email address
 * @param string $fromName From name
 * @param string $to To email address
 * @param bool $smtpEnabled Whether SMTP is enabled
 * @param string $smtpHost SMTP host
 * @param string $smtpPort SMTP port
 * @param string $smtpUsername SMTP username
 * @param string $smtpPassword SMTP password
 * @param string $smtpEncryption SMTP encryption
 * @return bool|string True on success, error message on failure
 */
function sendTestEmail($from, $fromName, $to, $smtpEnabled, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption) {
    try {
        require_once $GLOBALS['basePath'] . 'vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        if ($smtpEnabled) {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            
            if (!empty($smtpUsername)) {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUsername;
                $mail->Password = $smtpPassword;
            }
            
            if ($smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
        }
        
        // Sender and recipient
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from ' . APP_NAME;
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #bb0620;">Test Email</h2>
                <p>This is a test email from ' . APP_NAME . ' to verify your email configuration.</p>
                <p>If you received this email, your email settings are configured correctly!</p>
                <hr style="border: 1px solid #eee; margin: 20px 0;">
                <p style="color: #666; font-size: 12px;">This email was sent from ' . APP_NAME . ' at ' . date('Y-m-d H:i:s') . '</p>
            </div>
        ';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Email & Notification Settings</h2>
    </div>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <p><i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
        <p><i class="fas fa-check-circle mr-2"></i> <?= $success ?></p>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4 mb-6">
        <!-- SMTP Settings -->
        <div class="border-b pb-4 mb-4">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Email Server Settings</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['smtp_enabled'] === '1' ? 'checked' : '' ?>>
                    <label for="smtp_enabled" class="ml-2 block text-gray-700 font-medium">Enable SMTP Server</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">When disabled, the system will use PHP's mail() function</p>
            </div>
            
            <div id="smtp_settings" class="<?= $settings['smtp_enabled'] === '1' ? '' : 'hidden' ?> space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="smtp_host" class="block text-gray-700 font-medium mb-2">SMTP Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['smtp_host'] ?>">
                    </div>
                    
                    <div>
                        <label for="smtp_port" class="block text-gray-700 font-medium mb-2">SMTP Port</label>
                        <input type="text" id="smtp_port" name="smtp_port" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['smtp_port'] ?>">
                        <p class="text-xs text-gray-500 mt-1">Common ports: 25, 465, 587</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="smtp_username" class="block text-gray-700 font-medium mb-2">SMTP Username</label>
                        <input type="text" id="smtp_username" name="smtp_username" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['smtp_username'] ?>">
                    </div>
                    
                    <div>
                        <label for="smtp_password" class="block text-gray-700 font-medium mb-2">SMTP Password</label>
                        <input type="password" id="smtp_password" name="smtp_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to keep existing password</p>
                    </div>
                </div>
                
                <div>
                    <label for="smtp_encryption" class="block text-gray-700 font-medium mb-2">Encryption</label>
                    <select id="smtp_encryption" name="smtp_encryption" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                        <option value="tls" <?= $settings['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= $settings['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="none" <?= $settings['smtp_encryption'] === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Email Settings</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="email_from" class="block text-gray-700 font-medium mb-2">From Email *</label>
                    <input type="email" id="email_from" name="email_from" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $settings['email_from'] ?>">
                </div>
                
                <div>
                    <label for="email_from_name" class="block text-gray-700 font-medium mb-2">From Name *</label>
                    <input type="text" id="email_from_name" name="email_from_name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $settings['email_from_name'] ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="admin_email" class="block text-gray-700 font-medium mb-2">Admin Notification Email</label>
                <input type="email" id="admin_email" name="admin_email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['admin_email'] ?>">
                <p class="text-xs text-gray-500 mt-1">Email address for admin notifications (leave empty to use From Email)</p>
            </div>
            
            <div class="mb-4">
                <label for="email_footer" class="block text-gray-700 font-medium mb-2">Email Footer Text</label>
                <textarea id="email_footer" name="email_footer" rows="2" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $settings['email_footer'] ?></textarea>
            </div>
        </div>
        
        <!-- Notification Settings -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Notification Settings</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="notify_low_stock" name="notify_low_stock" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['notify_low_stock'] === '1' ? 'checked' : '' ?>>
                    <label for="notify_low_stock" class="ml-2 block text-gray-700">Email notification when products are low in stock</label>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="notify_new_order" name="notify_new_order" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['notify_new_order'] === '1' ? 'checked' : '' ?>>
                    <label for="notify_new_order" class="ml-2 block text-gray-700">Email notification for new sales</label>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="notify_payment_received" name="notify_payment_received" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['notify_payment_received'] === '1' ? 'checked' : '' ?>>
                    <label for="notify_payment_received" class="ml-2 block text-gray-700">Email notification when payments are received</label>
                </div>
            </div>
        </div>
        
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <input type="checkbox" id="test_email" name="test_email" value="1" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded">
                <label for="test_email" class="ml-2 block text-gray-700">Send test email after saving</label>
            </div>
            
            <button type="submit" class="bg-red-900 text-white py-2 px-6 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
        </div>
    </form>
    
    <!-- Email Template Information -->
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-lg font-medium text-gray-800 mb-4">Available Email Templates</h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">When Sent</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">New Sale</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">Email sent to customers after a sale is created</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">After sale completion</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">Invoice</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">Email with invoice attachment</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">When manually sent from invoice page</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">Low Stock Alert</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">Notification when products reach low stock threshold</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">When inventory falls below threshold</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">Payment Receipt</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">Receipt for customer payments</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">When payment status changes to "Paid"</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle text-red-900 mr-2"></i>
                Email templates are stored in the <code>templates/emails/</code> directory. You can customize them by editing the HTML files.
            </p>
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
    // Toggle SMTP settings visibility
    document.getElementById('smtp_enabled').addEventListener('change', function() {
        const smtpSettings = document.getElementById('smtp_settings');
        if (this.checked) {
            smtpSettings.classList.remove('hidden');
        } else {
            smtpSettings.classList.add('hidden');
        }
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>