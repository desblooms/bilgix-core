<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No product specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/products/list.php');
}

$productId = (int)$_GET['id'];
$product = getProduct($productId);

// If product not found
if (!$product) {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/products/list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemCode = sanitize($_POST['itemCode']);
    $itemName = sanitize($_POST['itemName']);
    $hsn = sanitize($_POST['hsn']);
    $priceUnit = floatval($_POST['priceUnit']);
    $qty = intval($_POST['qty']);
    $unitType = sanitize($_POST['unitType']);
    
    // Validation
    $errors = [];
    if (empty($itemCode)) $errors[] = "Item Code is required";
    if (empty($itemName)) $errors[] = "Item Name is required";
    if ($priceUnit <= 0) $errors[] = "Price must be greater than zero";
    
    // Check if item code already exists for other products
    $existingItem = $db->select("SELECT id FROM products WHERE itemCode = :itemCode AND id != :id", 
                               ['itemCode' => $itemCode, 'id' => $productId]);
    if (!empty($existingItem)) {
        $errors[] = "Item code already exists. Please use a different code.";
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $data = [
            'itemCode' => $itemCode,
            'itemName' => $itemName,
            'hsn' => $hsn,
            'priceUnit' => $priceUnit,
            'unitType' => $unitType,
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        // Only update quantity if it changed
        if ($qty !== $product['qty']) {
            $data['qty'] = $qty;
            
            // Log the inventory change
            $adjustmentType = ($qty > $product['qty']) ? 'add' : 'remove';
            $adjustmentQty = abs($qty - $product['qty']);
            
            $logData = [
                'productId' => $productId,
                'adjustmentType' => $adjustmentType,
                'quantity' => $adjustmentQty,
                'previousQty' => $product['qty'],
                'newQty' => $qty,
                'reason' => 'Adjustment',
                'userId' => $_SESSION['user_id'] ?? 1,
                'createdAt' => date('Y-m-d H:i:s')
            ];
            
            $db->insert('inventory_log', $logData);
        }
        
        $updated = $db->update('products', $data, 'id = :id', ['id' => $productId]);
        
        if ($updated) {
            // Redirect to product list with success message
            $_SESSION['message'] = "Product updated successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/products/list.php');
        } else {
            $errors[] = "Failed to update product. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Edit Product</h2>
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
            <label for="itemCode" class="block text-gray-700 font-medium mb-2">Item Code *</label>
            <input type="text" id="itemCode" name="itemCode" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $product['itemCode'] ?>">
        </div>
        
        <div class="mb-4">
            <label for="itemName" class="block text-gray-700 font-medium mb-2">Item Name *</label>
            <input type="text" id="itemName" name="itemName" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $product['itemName'] ?>">
        </div>
        
        <div class="mb-4">
            <label for="hsn" class="block text-gray-700 font-medium mb-2">HSN Code</label>
            <input type="text" id="hsn" name="hsn" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $product['hsn'] ?>">
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="priceUnit" class="block text-gray-700 font-medium mb-2">Unit Price *</label>
                <input type="number" id="priceUnit" name="priceUnit" step="0.01" min="0" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $product['priceUnit'] ?>">
            </div>
            
            <div>
                <label for="qty" class="block text-gray-700 font-medium mb-2">Quantity</label>
                <input type="number" id="qty" name="qty" min="0" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $product['qty'] ?>">
                <p class="text-xs text-gray-500 mt-1">Use "Adjust Inventory" for detailed stock changes.</p>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="unitType" class="block text-gray-700 font-medium mb-2">Unit Type</label>
            <select id="unitType" name="unitType" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                <option value="Meter" <?= $product['unitType'] == 'Meter' ? 'selected' : '' ?>>Meter</option>
                <option value="Piece" <?= $product['unitType'] == 'Piece' ? 'selected' : '' ?>>Piece</option>
                <option value="Kg" <?= $product['unitType'] == 'Kg' ? 'selected' : '' ?>>Kg</option>
                <option value="Liter" <?= $product['unitType'] == 'Liter' ? 'selected' : '' ?>>Liter</option>
            </select>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Update Product
            </button>
        </div>
    </form>
    
    <!-- Quick Actions -->
    <div class="mt-4 grid grid-cols-2 gap-4">
        <a href="../inventory/adjust.php?id=<?= $productId ?>" class="bg-green-600 text-white py-2 px-4 rounded-lg text-center">
            <i class="fas fa-warehouse mr-2"></i> Adjust Stock
        </a>
        <a href="../inventory/history.php?product=<?= $productId ?>" class="bg-gray-700 text-white py-2 px-4 rounded-lg text-center">
            <i class="fas fa-history mr-2"></i> Stock History
        </a>
    </div>
</div>

<!-- Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t flex justify-between items-center p-2 bottom-nav">
    <a href="../../index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-home text-xl"></i>
        <span class="text-xs mt-1">Home</span>
    </a>
    <a href="../products/list.php" class="flex flex-col items-center p-2 text-slate-950">
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

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>