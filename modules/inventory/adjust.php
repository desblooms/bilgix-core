<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No product specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/inventory/list.php');
}

$productId = (int)$_GET['id'];

// Get product details
$product = getProduct($productId);

// If product not found
if (!$product) {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/inventory/list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adjustmentType = sanitize($_POST['adjustmentType']);
    $adjustmentQty = (int)$_POST['adjustmentQty'];
    $reason = sanitize($_POST['reason']);
    
    // Validation
    $errors = [];
    if ($adjustmentQty <= 0) {
        $errors[] = "Quantity must be greater than zero";
    }
    
    if ($adjustmentType === 'remove' && $adjustmentQty > $product['qty']) {
        $errors[] = "Cannot remove more than current stock (" . $product['qty'] . " items)";
    }
    
    // If no errors, process adjustment
    if (empty($errors)) {
        // Calculate new quantity
        $newQty = ($adjustmentType === 'add') 
                ? $product['qty'] + $adjustmentQty 
                : $product['qty'] - $adjustmentQty;
        
        // Update product quantity
        $updated = $db->update('products', 
                            ['qty' => $newQty, 'updatedAt' => date('Y-m-d H:i:s')], 
                            'id = :id', 
                            ['id' => $productId]);
        
        // Log the inventory adjustment
        $logData = [
            'productId' => $productId,
            'adjustmentType' => $adjustmentType,
            'quantity' => $adjustmentQty,
            'previousQty' => $product['qty'],
            'newQty' => $newQty,
            'reason' => $reason,
            'userId' => $_SESSION['user_id'] ?? 1, // Default to 1 if not logged in
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('inventory_log', $logData);
        
        if ($updated) {
            // Redirect to inventory list with success message
            $_SESSION['message'] = "Inventory adjusted successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/inventory/list.php');
        } else {
            $errors[] = "Failed to adjust inventory. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Adjust Inventory</h2>
    </div>
    
    <!-- Product Info -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="font-bold text-lg"><?= $product['itemName'] ?></h3>
        <div class="flex justify-between mt-2">
            <div>
                <p class="text-sm text-gray-600">Code: <?= $product['itemCode'] ?></p>
                <p class="text-sm text-gray-600">Unit Type: <?= $product['unitType'] ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600">Current Stock:</p>
                <p class="text-xl font-bold 
                    <?= $product['qty'] == 0 ? 'text-red-600' : 
                       ($product['qty'] <= LOW_STOCK_THRESHOLD ? 'text-yellow-600' : 'text-green-600') ?>">
                    <?= $product['qty'] ?> <?= $product['unitType'] ?>
                </p>
            </div>
        </div>
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
    
    <!-- Adjustment Form -->
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Adjustment Type</label>
            <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="adjustmentType" value="add" class="mr-2" checked>
                    <div>
                        <span class="block font-medium">Add Stock</span>
                        <span class="text-xs text-gray-600">Increase inventory</span>
                    </div>
                </label>
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="adjustmentType" value="remove" class="mr-2">
                    <div>
                        <span class="block font-medium">Remove Stock</span>
                        <span class="text-xs text-gray-600">Decrease inventory</span>
                    </div>
                </label>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="adjustmentQty" class="block text-gray-700 font-medium mb-2">Quantity</label>
            <input type="number" id="adjustmentQty" name="adjustmentQty" min="1" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="1">
            <p class="text-sm text-gray-600 mt-1">Enter the number of <?= $product['unitType'] ?> to add/remove</p>
        </div>
        
        <div class="mb-4">
            <label for="reason" class="block text-gray-700 font-medium mb-2">Reason</label>
            <select id="reason" name="reason" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                <option value="Purchase">New Purchase</option>
                <option value="Return">Customer Return</option>
                <option value="Adjustment">Manual Adjustment</option>
                <option value="Damaged">Damaged/Defective</option>
                <option value="Lost">Lost/Stolen</option>
                <option value="Initial">Initial Stock Entry</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-sync-alt mr-2"></i> Adjust Inventory
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
    <a href="../reports/inventory.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<?php
// Close the main div and add the footer

// Include footer (which contains ob_end_flush())
include $basePath . 'includes/footer.php';
?>