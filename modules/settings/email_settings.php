<?php 
// modules/settings/email_settings.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Get existing settings
$emailSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'email'");
$settings = [];

// Convert to associative array for easier access
if (!empty($emailSettings)) {
    foreach ($emailSettings as $setting) {
        $settings[$setting['settingKey']] = $setting['settingValue'];
    }
}

// Default values
$defaults = [
    'smtp_enabled' => '0',
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@example.com',
    'from_name' => COMPANY_NAME,
    'admin_email' => '',
    'email_footer' => 'Sent from ' . APP_NAME . ' - ' . COMPANY_NAME,
    'notify_low_stock' => '0',
    'notify_new_sale' => '0',
    'notify_payment' => '0'
];

// Apply defaults for any missing settings
foreach ($defaults as $key => $value) {
    if (!isset($settings['email_' . $key])) {
        $settings['email_' . $key] = $value;
    }
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $smtpEnabled = isset($_POST['smtp_enabled']) ? '1' : '0';
    $smtpHost = sanitize($_POST['smtp_host']);
    $smtpPort = sanitize($_POST['smtp_port']);
    $smtpUsername = sanitize($_POST['smtp_username']);
    $smtpPassword = $_POST['smtp_password']; // Don't sanitize password
    $smtpEncryption = sanitize($_POST['smtp_encryption']);
    $fromEmail = sanitize($_POST['from_email']);
    $fromName = sanitize($_POST['from_name']);
    $adminEmail = sanitize($_POST['admin_email']);
    $emailFooter = sanitize($_POST['email_footer']);
    $notifyLowStock = isset($_POST['notify_low_stock']) ? '1' : '0';
    $notifyNewSale = isset($_POST['notify_new_sale']) ? '1' : '0';
    $notifyPayment = isset($_POST['notify_payment']) ? '1' : '0';
    
    // Validation
    $errors = [];
    
    if (empty($fromEmail)) {
        $errors[] = "From Email is required";
    } elseif (!isValidEmail($fromEmail)) {
        $errors[] = "Invalid From Email format";
    }
    
    if (empty($fromName)) {
        $errors[] = "From Name is required";
    }
    
    if (!empty($adminEmail) && !isValidEmail($adminEmail)) {
        $errors[] = "Invalid Admin Email format";
    }
    
    if ($smtpEnabled == '1') {
        if (empty($smtpHost)) {
            $errors[] = "SMTP Host is required when SMTP is enabled";
        }
        
        if (empty($smtpPort)) {
            $errors[] = "SMTP Port is required when SMTP is enabled";
        } elseif (!is_numeric($smtpPort)) {
            $errors[] = "SMTP Port must be a number";
        }
        
        if (empty($smtpUsername)) {
            $errors[] = "SMTP Username is required when SMTP is enabled";
        }
    }
    
    // If no errors, save settings
    if (empty($errors)) {
        // Settings to save
        $settingsToSave = [
            'email_smtp_enabled' => $smtpEnabled,
            'email_smtp_host' => $smtpHost,
            'email_smtp_port' => $smtpPort,
            'email_smtp_username' => $smtpUsername,
            'email_smtp_encryption' => $smtpEncryption,
            'email_from_email' => $fromEmail,
            'email_from_name' => $fromName,
            'email_admin_email' => $adminEmail,
            'email_email_footer' => $emailFooter,
            'email_notify_low_stock' => $notifyLowStock,
            'email_notify_new_sale' => $notifyNewSale,
            'email_notify_payment' => $notifyPayment
        ];
        
        // Only update password if provided
        if (!empty($smtpPassword)) {
            $settingsToSave['email_smtp_password'] = $smtpPassword;
        }
        
        // Start transaction
        $db->getConnection()->beginTransaction();
        
        try {
            // Save each setting
            foreach ($settingsToSave as $key => $value) {
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
                        'settingGroup' => 'email',
                        'updatedAt' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Commit transaction
            $db->getConnection()->commit();
            
            // Send test email if requested
            if (isset($_POST['send_test'])) {
                $testResult = sendTestEmail($fromEmail, $fromName, $adminEmail ?: $fromEmail);
                if ($testResult === true) {
                    $message = "Settings saved successfully! Test email sent.";
                } else {
                    $message = "Settings saved successfully! But test email failed: " . $testResult;
                }
            } else {
                $message = "Email settings saved successfully!";
            }
            
            // Refresh settings
            $emailSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'email'");
            $settings = [];
            foreach ($emailSettings as $setting) {
                $settings[$setting['settingKey']] = $setting['settingValue'];
            }
        } catch (Exception $e) {
            // Rollback on error
            $db->getConnection()->rollBack();
            $error = "Failed to save settings: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Function to send test email
function sendTestEmail($fromEmail, $fromName, $toEmail) {
    global $settings;
    
    // Use PHPMailer
    require_once '../../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        if ($settings['email_smtp_enabled'] == '1') {
            $mail->isSMTP();
            $mail->Host = $settings['email_smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['email_smtp_username'];
            $mail->Password = $settings['email_smtp_password'];
            
            if ($settings['email_smtp_encryption'] == 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($settings['email_smtp_encryption'] == 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }
            
            $mail->Port = $settings['email_smtp_port'];
        }
        
        // Recipients
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from ' . APP_NAME;
        $mail->Body = 'This is a test email from ' . APP_NAME . ' to verify your email settings are working correctly.<br><br>' . 
                     $settings['email_email_footer'];
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Email & SMTP Settings</h2>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
        <p><i class="fas fa-check-circle mr-2"></i> <?= $message ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <p><i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?></p>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <!-- SMTP Server Settings -->
        <div class="border-b pb-4 mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">SMTP Server Settings</h3>
            
            <div class="mb-4">
                <div class="flex items-center">
                    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['email_smtp_enabled'] == '1' ? 'checked' : '' ?>>
                    <label for="smtp_enabled" class="ml-2 block text-gray-700">Enable SMTP Server</label>
                </div>
                <p class="text-xs text-gray-500 mt-1">When disabled, the system will use PHP's mail() function</p>
            </div>
            
            <div id="smtp_settings" class="<?= $settings['email_smtp_enabled'] == '0' ? 'opacity-50' : '' ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="smtp_host" class="block text-gray-700 font-medium mb-2">SMTP Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['email_smtp_host'] ?>">
                    </div>
                    
                    <div>
                        <label for="smtp_port" class="block text-gray-700 font-medium mb-2">SMTP Port</label>
                        <input type="text" id="smtp_port" name="smtp_port" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['email_smtp_port'] ?>">
                        <p class="text-xs text-gray-500 mt-1">Common ports: 25, 465, 587</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="smtp_username" class="block text-gray-700 font-medium mb-2">SMTP Username</label>
                        <input type="text" id="smtp_username" name="smtp_username" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['email_smtp_username'] ?>">
                    </div>
                    
                    <div>
                        <label for="smtp_password" class="block text-gray-700 font-medium mb-2">SMTP Password</label>
                        <input type="password" id="smtp_password" name="smtp_password" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to keep existing password</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="smtp_encryption" class="block text-gray-700 font-medium mb-2">Encryption</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="smtp_encryption" value="tls" class="text-red-900 focus:ring-red-900" <?= $settings['email_smtp_encryption'] == 'tls' ? 'checked' : '' ?>>
                            <span class="ml-2">TLS</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="smtp_encryption" value="ssl" class="text-red-900 focus:ring-red-900" <?= $settings['email_smtp_encryption'] == 'ssl' ? 'checked' : '' ?>>
                            <span class="ml-2">SSL</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="smtp_encryption" value="none" class="text-red-900 focus:ring-red-900" <?= $settings['email_smtp_encryption'] == 'none' ? 'checked' : '' ?>>
                            <span class="ml-2">None</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div class="border-b pb-4 mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Email Settings</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="from_email" class="block text-gray-700 font-medium mb-2">From Email *</label>
                    <input type="email" id="from_email" name="from_email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $settings['email_from_email'] ?>">
                </div>
                
                <div>
                    <label for="from_name" class="block text-gray-700 font-medium mb-2">From Name *</label>
                    <input type="text" id="from_name" name="from_name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $settings['email_from_name'] ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="admin_email" class="block text-gray-700 font-medium mb-2">Admin Notification Email</label>
                <input type="email" id="admin_email" name="admin_email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $settings['email_admin_email'] ?>">
                <p class="text-xs text-gray-500 mt-1">Email address for admin notifications (leave empty to use From Email)</p>
            </div>
            
            <div class="mb-4">
                <label for="email_footer" class="block text-gray-700 font-medium mb-2">Email Footer Text</label>
                <textarea id="email_footer" name="email_footer" rows="2" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $settings['email_email_footer'] ?></textarea>
            </div>
        </div>
        
        <!-- Notification Settings -->
        <div class="border-b pb-4 mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Notification Settings</h3>
            
            <div class="space-y-3">
                <div class="flex items-center">
                    <input type="checkbox" id="notify_low_stock" name="notify_low_stock" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['email_notify_low_stock'] == '1' ? 'checked' : '' ?>>
                    <label for="notify_low_stock" class="ml-2 block text-gray-700">Email notification when products are low in stock</label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="notify_new_sale" name="notify_new_sale" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['email_notify_new_sale'] == '1' ? 'checked' : '' ?>>
                    <label for="notify_new_sale" class="ml-2 block text-gray-700">Email notification for new sales</label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="notify_payment" name="notify_payment" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded" <?= $settings['email_notify_payment'] == '1' ? 'checked' : '' ?>>
                    <label for="notify_payment" class="ml-2 block text-gray-700">Email notification when payments are received</label>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="send_test" name="send_test" class="h-4 w-4 text-red-900 focus:ring-red-900 border-gray-300 rounded">
                    <label for="send_test" class="ml-2 block text-gray-700">Send test email after saving</label>
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
        </div>
    </form>
    
    <!-- Email Templates -->
    <div class="bg-white rounded-lg shadow p-4 mt-6">
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">New Sale</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Email sent to customers after a sale is created</td>
                        <td class="px-6 py-4 text-sm text-gray-500">After sale completion</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Invoice</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Email with invoice attachment</td>
                        <td class="px-6 py-4 text-sm text-gray-500">When manually sent from invoice page</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Low Stock Alert</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Notification when products reach low stock threshold</td>
                        <td class="px-6 py-4 text-sm text-gray-500">When inventory falls below threshold</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Payment Receipt</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Receipt for customer payments</td>
                        <td class="px-6 py-4 text-sm text-gray-500">When payment status changes to "Paid"</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <p class="text-sm text-gray-500 mt-4">Email templates are stored in the `templates/emails/` directory. You can customize them by editing the HTML files.</p>
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
    // Toggle SMTP settings when checkbox is clicked
    const smtpEnabled = document.getElementById('smtp_enabled');
    const smtpSettings = document.getElementById('smtp_settings');
    
    smtpEnabled.addEventListener('change', function() {
        if (this.checked) {
            smtpSettings.classList.remove('opacity-50');
        } else {
            smtpSettings.classList.add('opacity-50');
        }
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>