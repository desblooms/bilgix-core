<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 
include_once $basePath . 'includes/finance_handler.php';

// Get all products for dropdown
$products = getProducts();

// Get all customers for dropdown
$customers = $db->select("SELECT * FROM customers ORDER BY name ASC");

// Pre-select customer if provided in URL
$selectedCustomerId = isset($_GET['customer']) ? (int)$_GET['customer'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = !empty($_POST['customerId']) ? intval($_POST['customerId']) : null;
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $quantities = isset($_POST['quantities']) ? $_POST['quantities'] : [];
    $prices = isset($_POST['prices']) ? $_POST['prices'] : [];
    $totalAmount = floatval($_POST['totalAmount']);
    $paymentMethod = sanitize($_POST['paymentMethod']);
    $paymentStatus = sanitize($_POST['paymentStatus']);
    
    // Validation
    $errors = [];
    if (empty($items)) $errors[] = "No items selected";
    
    // If no errors, create sale
    if (empty($errors)) {
        // Generate invoice number
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Insert sale
        $saleData = [
            'invoiceNumber' => $invoiceNumber,
            'customerId' => $customerId,
            'totalPrice' => $totalAmount,
            'paymentMethod' => $paymentMethod,
            'paymentStatus' => $paymentStatus,
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        $saleId = $db->insert('sales', $saleData);
        
        if ($saleId) {
            // Insert sale items and update inventory
            for ($i = 0; $i < count($items); $i++) {
                $productId = intval($items[$i]);
                $quantity = floatval($quantities[$i]);
                $price = floatval($prices[$i]);
                
                // Insert sale item
                $saleItemData = [
                    'saleId' => $saleId,
                    'productId' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $price * $quantity
                ];
                
                $db->insert('sale_items', $saleItemData);
                
                // Get current product details
                $product = getProduct($productId);
                $newQty = $product['qty'] - $quantity;
                if ($newQty < 0) $newQty = 0; // Prevent negative inventory
                
                // Update product quantity
                $db->update('products', 
                           ['qty' => $newQty, 'updatedAt' => date('Y-m-d H:i:s')], 
                           'id = :id', 
                           ['id' => $productId]);
                
                // Log inventory change
                $logData = [
                    'productId' => $productId,
                    'adjustmentType' => 'remove',
                    'quantity' => $quantity,
                    'previousQty' => $product['qty'],
                    'newQty' => $newQty,
                    'reason' => 'Sale',
                    'userId' => $_SESSION['user_id'] ?? 1,
                    'createdAt' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('inventory_log', $logData);
            }
            $description = "Sale #" . $invoiceNumber;
            if (!empty($customerId)) {
                $customerName = "";
                foreach($customers as $c) {
                    if ($c['id'] == $customerId) {
                        $customerName = $c['name'];
                        break;
                    }
                }
                $description .= " - " . $customerName;
            } else {
                $description .= " - Walk-in Customer";
            }
            
            // Record positive amount for sales (revenue)
            recordFinancialTransaction(
                'sale',
                'sale',
                $saleId,
                $totalAmount,
                $description,
                $_SESSION['user_id'] ?? null
            );



            
            // Redirect to sales list with success message
            $_SESSION['message'] = "Sale completed successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/sales/list.php');
        } else {
            $errors[] = "Failed to create sale. Please try again.";
        }
        
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">New Sale</h2>
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
    
    <form method="POST" id="saleForm" class="bg-white rounded-lg shadow p-4">
        <!-- Customer Selection -->
        <div class="mb-4">
            <label for="customerId" class="block text-gray-700 font-medium mb-2">Customer</label>
            <select id="customerId" name="customerId" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Walk-in Customer</option>
                <?php foreach($customers as $customer): ?>
                    <option value="<?= $customer['id'] ?>" <?= $selectedCustomerId == $customer['id'] ? 'selected' : '' ?>>
                        <?= $customer['name'] ?> (<?= $customer['phone'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Item Selection -->
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <label class="text-gray-700 font-medium">Items</label>
                
            </div>
            
            <div id="itemsContainer" class="space-y-3">
                <!-- Items will be added here dynamically -->
            </div>
        </div>
        
        <!-- Payment Details -->
        <div class="border-t pt-4 mt-4">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="paymentMethod" class="block text-gray-700 font-medium mb-2">Payment Method</label>
                    <select id="paymentMethod" name="paymentMethod" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="UPI">UPI</option>
                        <option value="Check">Check</option>
                    </select>
                </div>
                <div>
                    <label for="paymentStatus" class="block text-gray-700 font-medium mb-2">Payment Status</label>
                    <select id="paymentStatus" name="paymentStatus" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Paid">Paid</option>
                        <option value="Partial">Partial</option>
                        <option value="Unpaid">Unpaid</option>
                    </select>
                </div>
            </div>
        </div>
        <button type="button" id="addItemBtn" class="w-full border border-red-600 text-red-600 hover:bg-red-50 font-medium py-2 px-4 rounded-lg text-sm transition duration-200 flex items-center justify-center">
    <i class="fas fa-plus-circle mr-2"></i> Add Item
</button>

        <!-- Summary -->
        <div class="border-t pt-4 mt-4">
            <div class="flex justify-between items-center text-lg font-bold mb-4">
                <span>Total Amount:</span>
                <span id="totalDisplay"><?= formatCurrency(0) ?></span>
                <input type="hidden" name="totalAmount" id="totalAmount" value="0">
            </div>
            
            <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-check-circle mr-2"></i> Complete Sale
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
    <a href="../sales/add.php" class="flex flex-col items-center p-2 text-slate-950">
        <div class="bg-rose-900 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
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
    // Products data for JavaScript
    const products = <?= json_encode($products) ?>;
    
    // Add item row function
    function addItemRow() {
        const container = document.getElementById('itemsContainer');
        const index = container.children.length;
        
        const itemRow = document.createElement('div');
        itemRow.className = 'bg-gray-50 p-3 rounded-lg';
        itemRow.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <div class="font-medium">Item #${index + 1}</div>
                ${index > 0 ? '<button type="button" class="remove-item text-red-500 text-sm"><i class="fas fa-trash"></i></button>' : ''}
            </div>
            <div class="space-y-3">
                <select name="items[]" class="product-select w-full p-2 border rounded-lg">
                    <option value="">Select Product</option>
                    ${products.map(product => `<option value="${product.id}" data-price="${product.salePrice}" data-stock="${product.qty}">${product.itemName} (${formatCurrency(product.salePrice)})</option>`).join('')}
                </select>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-gray-600">Quantity</label>
                        <input type="number" name="quantities[]" min="0.1" step="0.1" value="1" class="quantity w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Price</label>
                        <input type="number" name="prices[]" step="0.01" value="0.00" class="price w-full p-2 border rounded-lg">
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-gray-600">Subtotal: </span>
                    <span class="subtotal font-medium">${formatCurrency(0)}</span>
                </div>
            </div>
        `;
        
        container.appendChild(itemRow);
        
        // Add event listeners to the new row
        const productSelect = itemRow.querySelector('.product-select');
        const quantityInput = itemRow.querySelector('.quantity');
        const priceInput = itemRow.querySelector('.price');
        const subtotalElem = itemRow.querySelector('.subtotal');
        const removeBtn = itemRow.querySelector('.remove-item');
        
        // Product selection
        productSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const price = option.dataset.price || 0;
            const stock = option.dataset.stock || 0;
            
            // Update price input
            priceInput.value = price;
            
            // Limit quantity to available stock
            quantityInput.max = stock;
            if (parseFloat(quantityInput.value) > parseFloat(stock)) {
                quantityInput.value = stock;
            }
            
            // Update subtotal
            updateSubtotal();
        });
        
        // Quantity change
        quantityInput.addEventListener('input', function() {
            const option = productSelect.options[productSelect.selectedIndex];
            const stock = parseFloat(option.dataset.stock || 0);
            
            // Check if quantity exceeds stock
            if (parseFloat(this.value) > stock) {
                alert(`Only ${stock} items available in stock.`);
                this.value = stock;
            }
            
            updateSubtotal();
        });
        
        // Price change
        priceInput.addEventListener('input', updateSubtotal);
        
        // Remove button
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                itemRow.remove();
                updateTotalAmount();
                // Renumber items
                const items = container.querySelectorAll('.bg-gray-50');
                items.forEach((item, idx) => {
                    item.querySelector('.font-medium').textContent = `Item #${idx + 1}`;
                });
            });
        }
        
        // Update subtotal
        function updateSubtotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const subtotal = quantity * price;
            subtotalElem.textContent = formatCurrency(subtotal);
            updateTotalAmount();
        }
    }
    
    // Format currency for JavaScript
    function formatCurrency(amount) {
        return '<?= CURRENCY ?>' + parseFloat(amount).toFixed(2);
    }
    
    // Update total amount
    function updateTotalAmount() {
        const subtotals = document.querySelectorAll('.subtotal');
        let total = 0;
        
        subtotals.forEach(item => {
            const amount = parseFloat(item.textContent.replace('<?= CURRENCY ?>', '')) || 0;
            total += amount;
        });
        
        document.getElementById('totalDisplay').textContent = formatCurrency(total);
        document.getElementById('totalAmount').value = total;
    }
    
    // Add initial item row
    document.addEventListener('DOMContentLoaded', function() {
        addItemRow();
        
        // Add item button
        document.getElementById('addItemBtn').addEventListener('click', addItemRow);
        
        // Form submission validation
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            const items = document.querySelectorAll('.product-select');
            let valid = false;
            
            items.forEach(item => {
                if (item.value) valid = true;
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please select at least one product');
            }
            
            const total = parseFloat(document.getElementById('totalAmount').value);
            if (total <= 0) {
                e.preventDefault();
                alert('Total amount must be greater than zero');
            }
        });
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>