<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $address = sanitize($_POST['address']);
    $gstNumber = sanitize($_POST['gstNumber']);
    $openingBalance = !empty($_POST['openingBalance']) ? floatval($_POST['openingBalance']) : 0;
    $balanceType = sanitize($_POST['balanceType']);
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    
    // If no errors, insert into database
    if (empty($errors)) {
        $data = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'gstNumber' => $gstNumber,
            'openingBalance' => $openingBalance,
            'balanceType' => $balanceType,
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        $customerId = $db->insert('customers', $data);
        
        if ($customerId) {
            // Redirect to customer list with success message
            $_SESSION['message'] = "Customer added successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/customers/list.php');
        } else {
            $errors[] = "Failed to add customer. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Add New Customer</h2>
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
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <div class="mb-4">
            <label for="name" class="block text-gray-700 font-medium mb-2">Name *</label>
            <input type="text" id="name" name="name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required value="<?= isset($_POST['name']) ? $_POST['name'] : '' ?>">
        </div>
        
        <div class="mb-4">
            <label for="phone" class="block text-gray-700 font-medium mb-2">Phone *</label>
            <input type="tel" id="phone" name="phone" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required value="<?= isset($_POST['phone']) ? $_POST['phone'] : '' ?>">
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" id="email" name="email" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>">
        </div>
        
        <div class="mb-4">
            <label for="gstNumber" class="block text-gray-700 font-medium mb-2">GSTIN</label>
            <input type="text" id="gstNumber" name="gstNumber" maxlength="15" pattern="^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$" title="Please enter a valid GSTIN (e.g., 22AAAAA0000A1Z5)" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= isset($_POST['gstNumber']) ? $_POST['gstNumber'] : '' ?>">
            <p class="text-xs text-gray-500 mt-1">Format: 22AAAAA0000A1Z5</p>
        </div>

        <div class="mb-4">
            <label for="address" class="block text-gray-700 font-medium mb-2">Address</label>
            <textarea id="address" name="address" rows="3" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= isset($_POST['address']) ? $_POST['address'] : '' ?></textarea>
        </div>
        
        <!-- Opening Balance Section -->
        <div class="border-t border-gray-200 pt-4 mt-4 mb-4">
            <h3 class="text-md font-medium text-gray-700 mb-3">Opening Balance</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="openingBalance" class="block text-gray-700 font-medium mb-2">Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2"><?= CURRENCY ?></span>
                        <input type="number" id="openingBalance" name="openingBalance" step="0.01" min="0" class="w-full pl-8 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= isset($_POST['openingBalance']) ? $_POST['openingBalance'] : '0.00' ?>">
                    </div>
                </div>
                
                <div>
                    <label for="balanceType" class="block text-gray-700 font-medium mb-2">Type</label>
                    <select id="balanceType" name="balanceType" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Advance" <?= isset($_POST['balanceType']) && $_POST['balanceType'] == 'Advance' ? 'selected' : '' ?>>Advance Payment</option>
                        <option value="Due" <?= isset($_POST['balanceType']) && $_POST['balanceType'] == 'Due' ? 'selected' : '' ?>>Outstanding Balance</option>
                    </select>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <span class="text-yellow-600"><i class="fas fa-info-circle mr-1"></i></span>
                Select "Advance Payment" if customer has pre-paid, or "Outstanding Balance" if they owe you money.
            </p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i> Save Customer
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
    <a href="../customers/list.php" class="flex flex-col items-center p-2 text-slate-950">
        <i class="fas fa-users text-xl"></i>
        <span class="text-xs mt-1">Customers</span>
    </a>
    <a href="../reports/index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<?php
// Close the main div and add the footer

// Include footer (which contains ob_end_flush())
include $basePath . 'includes/footer.php';
?>