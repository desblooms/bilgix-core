<?php 
// modules/settings/settings_branding.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin only)
checkAuthorization('admin');

// Define upload directory
$uploadDir = $basePath . 'assets/images/';

// Get current settings
$companySettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'company'");
$currentSettings = [];
foreach ($companySettings as $setting) {
    $currentSettings[$setting['settingKey']] = $setting['settingValue'];
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update company info
    $companyName = sanitize($_POST['company_name']);
    $companyEmail = sanitize($_POST['company_email']);
    $companyPhone = sanitize($_POST['company_phone']);
    $companyAddress = sanitize($_POST['company_address']);
    $currency = sanitize($_POST['currency']);
    
    // Logo upload handling
    $uploadedLogo = false;
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
        $fileName = $_FILES['company_logo']['name'];
        $fileSize = $_FILES['company_logo']['size'];
        $fileTmp = $_FILES['company_logo']['tmp_name'];
        $fileType = $_FILES['company_logo']['type'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        
        // Validate file
        if (in_array($fileExt, $allowedExts) && $fileSize < 2097152) { // 2MB max
            // Create a unique filename
            $newFileName = 'logo.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            
            // If old logo exists, delete it (only if different extension)
            if (file_exists($uploadDir . 'logo.jpg')) unlink($uploadDir . 'logo.jpg');
            if (file_exists($uploadDir . 'logo.jpeg')) unlink($uploadDir . 'logo.jpeg');
            if (file_exists($uploadDir . 'logo.png')) unlink($uploadDir . 'logo.png');
            if (file_exists($uploadDir . 'logo.gif')) unlink($uploadDir . 'logo.gif');
            if (file_exists($uploadDir . 'logo.svg')) unlink($uploadDir . 'logo.svg');
            
            // Move uploaded file
            if (move_uploaded_file($fileTmp, $destination)) {
                // Update logo path setting
                updateSetting('company_logo', 'assets/images/' . $newFileName, 'company');
                $uploadedLogo = true;
            } else {
                $error = "Failed to upload logo. Check directory permissions.";
            }
        } else {
            $error = "Invalid file. Please upload an image file (JPG, PNG, GIF, SVG) under 2MB.";
        }
    }

    // Update settings in database
    $updateSuccess = true;
    $updateSuccess &= updateSetting('company_name', $companyName, 'company');
    $updateSuccess &= updateSetting('company_email', $companyEmail, 'company');
    $updateSuccess &= updateSetting('company_phone', $companyPhone, 'company');
    $updateSuccess &= updateSetting('company_address', $companyAddress, 'company');
    $updateSuccess &= updateSetting('currency', $currency, 'regional');
    
    if ($updateSuccess) {
        $message = $uploadedLogo ? "Company settings updated and logo uploaded successfully!" : "Company settings updated successfully!";
        // Update constants
        define('COMPANY_NAME', $companyName);
        define('COMPANY_EMAIL', $companyEmail);
        define('COMPANY_PHONE', $companyPhone);
        define('COMPANY_ADDRESS', $companyAddress);
        define('CURRENCY', $currency);
    } else {
        $error = "Failed to update settings.";
    }

    // Refresh settings
    $companySettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'company'");
    $currentSettings = [];
    foreach ($companySettings as $setting) {
        $currentSettings[$setting['settingKey']] = $setting['settingValue'];
    }
    
    // Get currency setting
    $currencySetting = $db->select("SELECT settingValue FROM settings WHERE settingKey = 'currency'");
    if (!empty($currencySetting)) {
        $currentSettings['currency'] = $currencySetting[0]['settingValue'];
    }
}

// Helper function to update a setting
function updateSetting($key, $value, $group) {
    global $db;
    
    // Check if setting exists
    $existing = $db->select("SELECT id FROM settings WHERE settingKey = :key", ['key' => $key]);
    
    if (!empty($existing)) {
        // Update existing setting
        return $db->update('settings', 
                          ['settingValue' => $value, 'updatedAt' => date('Y-m-d H:i:s')], 
                          'settingKey = :key', 
                          ['key' => $key]);
    } else {
        // Insert new setting
        $data = [
            'settingKey' => $key,
            'settingValue' => $value,
            'settingGroup' => $group,
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        return $db->insert('settings', $data) ? true : false;
    }
}

// Define available currency symbols
$currencySymbols = [
    '₹' => 'Indian Rupee (₹)',
    '$' => 'US Dollar ($)',
    '€' => 'Euro (€)',
    '£' => 'British Pound (£)',
    '¥' => 'Japanese Yen (¥)',
    '₩' => 'Korean Won (₩)',
    '₽' => 'Russian Ruble (₽)',
    '฿' => 'Thai Baht (฿)',
    'R' => 'South African Rand (R)',
    'kr' => 'Swedish Krona (kr)',
    'CHF' => 'Swiss Franc (CHF)',
    'A$' => 'Australian Dollar (A$)',
    'CA$' => 'Canadian Dollar (CA$)',
    '৳' => 'Bangladeshi Taka (৳)',
    '₺' => 'Turkish Lira (₺)',
    'SAR' => 'Saudi Riyal (SAR)',
    'AED' => 'UAE Dirham (AED)',
    '₦' => 'Nigerian Naira (₦)',
    '₱' => 'Philippine Peso (₱)',
    'RM' => 'Malaysian Ringgit (RM)'
];
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Branding Settings</h2>
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
    
    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-4">
        <!-- Company Logo -->
        <div class="mb-6">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Company Logo</h3>
            
            <div class="flex items-center mb-4">
                <div class="w-32 h-32 border rounded-lg flex items-center justify-center overflow-hidden bg-gray-50 mr-6">
                    <?php 
                    $logoPath = !empty($currentSettings['company_logo']) ? $basePath . $currentSettings['company_logo'] : '';
                    if (!empty($logoPath) && file_exists($logoPath)):
                    ?>
                        <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Company Logo" class="max-w-full max-h-full">
                    <?php else: ?>
                        <div class="text-gray-400"><i class="fas fa-image text-4xl"></i></div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Upload Logo</label>
                    <input type="file" name="company_logo" id="company_logo" accept="image/*" class="block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-red-50 file:text-red-700
                        hover:file:bg-red-100
                    ">
                    <p class="text-xs text-gray-500 mt-1">Recommended size: 200×200 pixels. Max 2MB. Formats: JPG, PNG, GIF, SVG</p>
                </div>
            </div>
        </div>
        
        <!-- Company Information -->
        <div class="mb-6">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Company Information</h3>
            
            <div class="mb-4">
                <label for="company_name" class="block text-gray-700 font-medium mb-2">Company Name</label>
                <input type="text" id="company_name" name="company_name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $currentSettings['company_name'] ?? COMPANY_NAME ?>">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="company_email" class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" id="company_email" name="company_email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $currentSettings['company_email'] ?? COMPANY_EMAIL ?>">
                </div>
                
                <div>
                    <label for="company_phone" class="block text-gray-700 font-medium mb-2">Phone</label>
                    <input type="tel" id="company_phone" name="company_phone" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $currentSettings['company_phone'] ?? COMPANY_PHONE ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="company_address" class="block text-gray-700 font-medium mb-2">Address</label>
                <textarea id="company_address" name="company_address" rows="3" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900"><?= $currentSettings['company_address'] ?? COMPANY_ADDRESS ?></textarea>
            </div>
        </div>
        
        <!-- Regional Settings -->
        <div class="mb-6">
            <h3 class="text-lg font-medium border-b pb-2 mb-4">Regional Settings</h3>
            
            <div class="mb-4">
                <label for="currency" class="block text-gray-700 font-medium mb-2">Currency Symbol</label>
                <select id="currency" name="currency" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                    <?php foreach ($currencySymbols as $symbol => $description): ?>
                        <option value="<?= $symbol ?>" <?= ($currentSettings['currency'] ?? CURRENCY) === $symbol ? 'selected' : '' ?>>
                            <?= $description ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Select the currency symbol that will be used throughout the application.</p>
            </div>
        </div>
        
        <!-- Preview Section -->
        <div class="mb-6 p-4 border border-dashed border-gray-300 rounded-lg bg-gray-50">
            <h3 class="text-md font-medium mb-3">Preview</h3>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-start">
                    <?php if (!empty($logoPath) && file_exists($logoPath)): ?>
                        <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Company Logo" class="h-12 mr-4">
                    <?php endif; ?>
                    
                    <div>
                        <h4 class="font-bold text-slate-950"><?= $currentSettings['company_name'] ?? COMPANY_NAME ?></h4>
                        <p class="text-sm text-gray-600"><?= $currentSettings['company_address'] ?? COMPANY_ADDRESS ?></p>
                        <p class="text-sm text-gray-600">
                            <?= $currentSettings['company_phone'] ?? COMPANY_PHONE ?> • 
                            <?= $currentSettings['company_email'] ?? COMPANY_EMAIL ?>
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 p-2 border-t pt-4">
                    <p class="font-bold">Sample Product: <span class="text-gray-700">Wooden Chair</span></p>
                    <p class="text-lg font-bold text-green-600"><?= $currentSettings['currency'] ?? CURRENCY ?> 1,299.00</p>
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Settings
            </button>
        </div>
    </form>
    
    <!-- Restore Default Settings -->
    <div class="mt-4">
        <button id="restoreDefaultsBtn" class="w-full bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-undo mr-2"></i> Restore Default Settings
        </button>
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
    // Preview updates as user types
    const companyNameInput = document.getElementById('company_name');
    const companyAddressInput = document.getElementById('company_address');
    const companyPhoneInput = document.getElementById('company_phone');
    const companyEmailInput = document.getElementById('company_email');
    const currencySelect = document.getElementById('currency');
    const companyLogoInput = document.getElementById('company_logo');
    
    // Get preview elements
    const previewName = document.querySelector('.preview-name');
    const previewAddress = document.querySelector('.preview-address');
    const previewContact = document.querySelector('.preview-contact');
    const previewPrice = document.querySelector('.preview-price');
    
    // Update preview when inputs change
    function updatePreview() {
        // Update company name in preview
        if (previewName) {
            previewName.textContent = companyNameInput.value;
        }
        
        // Update company address in preview
        if (previewAddress) {
            previewAddress.textContent = companyAddressInput.value;
        }
        
        // Update contact info in preview
        if (previewContact) {
            previewContact.textContent = `${companyPhoneInput.value} • ${companyEmailInput.value}`;
        }
        
        // Update price with currency in preview
        if (previewPrice) {
            previewPrice.textContent = `${currencySelect.value} 1,299.00`;
        }
    }
    
    // Add input event listeners
    [companyNameInput, companyAddressInput, companyPhoneInput, companyEmailInput, currencySelect].forEach(input => {
        if (input) {
            input.addEventListener('input', updatePreview);
        }
    });
    
    // Preview logo when selected
    if (companyLogoInput) {
        companyLogoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.querySelector('.logo-preview');
                    if (previewContainer) {
                        previewContainer.innerHTML = `<img src="${e.target.result}" alt="Logo Preview" class="max-w-full max-h-full">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Restore defaults confirmation
    const restoreDefaultsBtn = document.getElementById('restoreDefaultsBtn');
    if (restoreDefaultsBtn) {
        restoreDefaultsBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to restore default settings? This will reset all company information to default values.')) {
                // Set inputs to default values
                companyNameInput.value = 'Avoak Furnitures';
                companyEmailInput.value = 'avoakfabrics@gmail.com';
                companyPhoneInput.value = '+1234567890';
                companyAddressInput.value = '123 Business Street, City, Country';
                currencySelect.value = '₹';
                
                // Update preview
                updatePreview();
                
                // Submit form
                document.querySelector('form').submit();
            }
        });
    }
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>