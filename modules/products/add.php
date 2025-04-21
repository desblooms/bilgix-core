<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get all vendors for dropdown
$vendors = $db->select("SELECT * FROM vendors ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendorId = !empty($_POST['vendorId']) ? intval($_POST['vendorId']) : null;
    $itemCode = sanitize($_POST['itemCode']);
    $itemName = sanitize($_POST['itemName']);
    $hsn = sanitize($_POST['hsn']);
    $priceUnit = floatval($_POST['priceUnit']);
    $qty = intval($_POST['qty']);
    $unitType = sanitize($_POST['unitType']);
    $totalPrice = floatval($_POST['totalPrice']);
    $expense = floatval($_POST['expense']);
    $shippingCost = floatval($_POST['shippingCost']);
    $gst = floatval($_POST['gst']);
    $totalProductCost = floatval($_POST['totalProductCost']);
    $salePrice = floatval($_POST['salePrice']);
    
    // Validation
    $errors = [];
    if (empty($itemCode)) $errors[] = "Item Code is required";
    if (empty($itemName)) $errors[] = "Item Name is required";
    if ($priceUnit <= 0) $errors[] = "Price must be greater than zero";
    if ($salePrice <= 0) $errors[] = "Sale Price must be greater than zero";
    
    // Check if item code already exists
    $existingItem = $db->select("SELECT id FROM products WHERE itemCode = :itemCode", ['itemCode' => $itemCode]);
    if (!empty($existingItem)) {
        $errors[] = "Item code already exists. Please use a different code.";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $data = [
            'vendorId' => $vendorId,
            'itemCode' => $itemCode,
            'itemName' => $itemName,
            'hsn' => $hsn,
            'priceUnit' => $priceUnit,
            'qty' => $qty,
            'unitType' => $unitType,
            'totalPrice' => $totalPrice,
            'expense' => $expense,
            'shippingCost' => $shippingCost,
            'gst' => $gst,
            'totalProductCost' => $totalProductCost,
            'salePrice' => $salePrice,
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        $productId = $db->insert('products', $data);
        
        if ($productId) {
            // If initial stock is added, log it in inventory_log
            if ($qty > 0) {
                $logData = [
                    'productId' => $productId,
                    'adjustmentType' => 'add',
                    'quantity' => $qty,
                    'previousQty' => 0,
                    'newQty' => $qty,
                    'reason' => 'Initial',
                    'userId' => $_SESSION['user_id'] ?? 1,
                    'createdAt' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('inventory_log', $logData);
            }
            
            // Redirect to product list with success message
            $_SESSION['message'] = "Product added successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/products/list.php');
        } else {
            $errors[] = "Failed to add product. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Add New Product</h2>
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
    
    <form method="POST" id="productForm" class="bg-white rounded-lg shadow p-4">
        <!-- Vendor Selection -->
        <div class="mb-4">
            <label for="vendorId" class="block text-gray-700 font-medium mb-2">Select Vendor</label>
            <select id="vendorId" name="vendorId" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                <option value="">-- Select Vendor --</option>
                <?php foreach($vendors as $vendor): ?>
                    <option value="<?= $vendor['id'] ?>" <?= isset($_POST['vendorId']) && $_POST['vendorId'] == $vendor['id'] ? 'selected' : '' ?>>
                        <?= $vendor['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Basic Product Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="itemCode" class="block text-gray-700 font-medium mb-2">Item Code *</label>
                <input type="text" id="itemCode" name="itemCode" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['itemCode']) ? $_POST['itemCode'] : '' ?>">
            </div>
            
            <div>
                <label for="itemName" class="block text-gray-700 font-medium mb-2">Item Name *</label>
                <input type="text" id="itemName" name="itemName" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['itemName']) ? $_POST['itemName'] : '' ?>">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="hsn" class="block text-gray-700 font-medium mb-2">HSN Code</label>
                <input type="text" id="hsn" name="hsn" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= isset($_POST['hsn']) ? $_POST['hsn'] : '' ?>">
            </div>
            
            <div>
                <label for="unitType" class="block text-gray-700 font-medium mb-2">Unit Type</label>
                <select id="unitType" name="unitType" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                    <option value="Meter" <?= isset($_POST['unitType']) && $_POST['unitType'] == 'Meter' ? 'selected' : '' ?>>Meter</option>
                    <option value="Piece" <?= isset($_POST['unitType']) && $_POST['unitType'] == 'Piece' ? 'selected' : '' ?>>Piece</option>
                    <option value="Kg" <?= isset($_POST['unitType']) && $_POST['unitType'] == 'Kg' ? 'selected' : '' ?>>Kg</option>
                    <option value="Liter" <?= isset($_POST['unitType']) && $_POST['unitType'] == 'Liter' ? 'selected' : '' ?>>Liter</option>
                </select>
            </div>
        </div>
        
        <!-- Cost and Pricing Section -->
        <div class="border-t pt-4 mt-2 mb-4">
            <h3 class="text-lg font-medium mb-3">Cost Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="priceUnit" class="block text-gray-700 font-medium mb-2">Price/Unit (Purchase Price) *</label>
                    <input type="number" id="priceUnit" name="priceUnit" step="0.01" min="0" class="calculation-input w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['priceUnit']) ? $_POST['priceUnit'] : '' ?>">
                </div>
                
                <div>
                    <label for="qty" class="block text-gray-700 font-medium mb-2">Quantity</label>
                    <input type="number" id="qty" name="qty" min="0" class="calculation-input w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= isset($_POST['qty']) ? $_POST['qty'] : '0' ?>">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="totalPrice" class="block text-gray-700 font-medium mb-2">Total Price</label>
                    <input type="number" id="totalPrice" name="totalPrice" step="0.01" min="0" class="calculation-input w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900 bg-gray-100" readonly value="<?= isset($_POST['totalPrice']) ? $_POST['totalPrice'] : '0' ?>">
                </div>
                
                <div>
                    <label for="expense" class="block text-gray-700 font-medium mb-2">Expense</label>
                    <input type="number" id="expense" name="expense" step="0.01" min="0" class="calculation-input w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= isset($_POST['expense']) ? $_POST['expense'] : '0' ?>">
                </div>
                
                <div>
                    <label for="shippingCost" class="block text-gray-700 font-medium mb-2">Shipping Cost</label>
                    <input type="number" id="shippingCost" name="shippingCost" step="0.01" min="0" class="calculation-input w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= isset($_POST['shippingCost']) ? $_POST['shippingCost'] : '0' ?>">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="gst" class="block text-gray-700 font-medium mb-2">GST (%)</label>
                    <input type="number" id="gst" name="gst" step="0.01" min="0" class="calculation-input w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= isset($_POST['gst']) ? $_POST['gst'] : '0' ?>">
                </div>
                
                <div>
                    <label for="totalProductCost" class="block text-gray-700 font-medium mb-2">Total Product Cost</label>
                    <input type="number" id="totalProductCost" name="totalProductCost" step="0.01" min="0" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900 bg-gray-100" readonly value="<?= isset($_POST['totalProductCost']) ? $_POST['totalProductCost'] : '0' ?>">
                </div>
                
                <div>
                    <label for="salePrice" class="block text-gray-700 font-medium mb-2">Sale Price (per <span id="unitTypeLabel">Meter</span>) *</label>
                    <input type="number" id="salePrice" name="salePrice" step="0.01" min="0" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['salePrice']) ? $_POST['salePrice'] : '' ?>">
                </div>
            </div>
            
            <!-- Cost Analysis -->
            <div class="mt-4 mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="text-gray-700">Per Unit Cost:</span>
                    <span id="perUnitCost" class="font-bold text-slate-950">0.00</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                    <span class="text-gray-700">Profit Margin:</span>
                    <span id="profitMargin" class="font-bold text-green-600">0.00%</span>
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Product
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

<script>
// Format currency function
function formatCurrency(amount) {
    return '<?= CURRENCY ?>' + parseFloat(amount).toFixed(2);
}

// Calculate total price
function calculateTotalPrice() {
    const priceUnit = parseFloat(document.getElementById('priceUnit').value) || 0;
    const qty = parseFloat(document.getElementById('qty').value) || 0;
    
    const totalPrice = priceUnit * qty;
    document.getElementById('totalPrice').value = totalPrice.toFixed(2);
    
    calculateTotalProductCost();
}

// Calculate total product cost
function calculateTotalProductCost() {
    const totalPrice = parseFloat(document.getElementById('totalPrice').value) || 0;
    const expense = parseFloat(document.getElementById('expense').value) || 0;
    const shippingCost = parseFloat(document.getElementById('shippingCost').value) || 0;
    const gstPercent = parseFloat(document.getElementById('gst').value) || 0;
    const qty = parseFloat(document.getElementById('qty').value) || 1; // Prevent division by zero
    
    const gstAmount = (totalPrice * gstPercent) / 100;
    const totalProductCost = totalPrice + expense + shippingCost + gstAmount;
    
    document.getElementById('totalProductCost').value = totalProductCost.toFixed(2);
    
    // Calculate per unit cost
    const perUnitCost = qty > 0 ? totalProductCost / qty : 0;
    document.getElementById('perUnitCost').textContent = formatCurrency(perUnitCost);
    
    calculateProfitMargin(perUnitCost);
}

// Calculate profit margin
function calculateProfitMargin(perUnitCost) {
    const salePrice = parseFloat(document.getElementById('salePrice').value) || 0;
    
    if (perUnitCost > 0 && salePrice > 0) {
        const profit = salePrice - perUnitCost;
        const profitMargin = (profit / perUnitCost) * 100;
        document.getElementById('profitMargin').textContent = profitMargin.toFixed(2) + '%';
        
        // Change color based on margin
        if (profitMargin < 0) {
            document.getElementById('profitMargin').className = 'font-bold text-red-600';
        } else if (profitMargin < 10) {
            document.getElementById('profitMargin').className = 'font-bold text-yellow-600';
        } else {
            document.getElementById('profitMargin').className = 'font-bold text-green-600';
        }
    } else {
        document.getElementById('profitMargin').textContent = '0.00%';
        document.getElementById('profitMargin').className = 'font-bold text-gray-600';
    }
}

// Update unit type label
function updateUnitTypeLabel() {
    const unitType = document.getElementById('unitType').value;
    document.getElementById('unitTypeLabel').textContent = unitType;
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all calculation inputs
    document.querySelectorAll('.calculation-input').forEach(input => {
        input.addEventListener('input', calculateTotalPrice);
    });
    
    // Add event listener to sale price for profit margin
    document.getElementById('salePrice').addEventListener('input', function() {
        calculateTotalProductCost(); // This will trigger profit margin calculation
    });
    
    // Add event listener to unit type for updating label
    document.getElementById('unitType').addEventListener('change', updateUnitTypeLabel);
    
    // Calculate initial values
    calculateTotalPrice();
    updateUnitTypeLabel();
});
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>