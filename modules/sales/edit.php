<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 
include_once $basePath . 'includes/finance_handler.php';
// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No sale specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/sales/list.php');
}

$saleId = (int)$_GET['id'];

// Get sale details
$sale = $db->select("SELECT s.*, c.name as customerName 
                     FROM sales s 
                     LEFT JOIN customers c ON s.customerId = c.id 
                     WHERE s.id = :id", ['id' => $saleId]);

if (empty($sale)) {
    $_SESSION['message'] = "Sale not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/sales/list.php');
}

$sale = $sale[0];

// Get all customers for dropdown
$customers = $db->select("SELECT * FROM customers ORDER BY name ASC");

// Get sale items
$saleItems = $db->select("SELECT si.*, p.itemName, p.itemCode 
                         FROM sale_items si 
                         JOIN products p ON si.productId = p.id 
                         WHERE si.saleId = :saleId", 
                         ['saleId' => $saleId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = !empty($_POST['customerId']) ? intval($_POST['customerId']) : null;
    $paymentMethod = sanitize($_POST['paymentMethod']);
    $paymentStatus = sanitize($_POST['paymentStatus']);
    
    // Update only customer and payment details (not allowed to edit items once sale is created)
    $data = [
        'customerId' => $customerId,
        'paymentMethod' => $paymentMethod,
        'paymentStatus' => $paymentStatus,
        'updatedAt' => date('Y-m-d H:i:s')
    ];
    
    $updated = $db->update('sales', $data, 'id = :id', ['id' => $saleId]);
    
    if ($updated) {
        $_SESSION['message'] = "Sale updated successfully!";
        $_SESSION['message_type'] = "success";
        if ($paymentStatus != $sale['paymentStatus']) {
            $description = "Payment status updated for Sale #" . $sale['invoiceNumber'] . 
                          " from " . $sale['paymentStatus'] . " to " . $paymentStatus;
            
            recordFinancialTransaction(
                'adjustment',
                'sale',
                $saleId,
                0, // No amount change, just recording the status change
                $description,
                $_SESSION['user_id'] ?? null
            );
        }


        redirect($basePath . 'modules/sales/view.php?id=' . $saleId);
    } else {
        $_SESSION['message'] = "Failed to update sale!";
        $_SESSION['message_type'] = "error";
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="view.php?id=<?= $saleId ?>" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Edit Sale</h2>
    </div>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <!-- Sale Info -->
        <div class="border-b pb-4 mb-4">
            <h3 class="text-lg font-medium text-gray-800 mb-2"><?= $sale['invoiceNumber'] ?></h3>
            <p class="text-sm text-gray-600"><?= date('F d, Y h:i A', strtotime($sale['createdAt'])) ?></p>
            <p class="text-lg font-bold text-green-600 mt-1"><?= formatCurrency($sale['totalPrice']) ?></p>
        </div>
        
        <!-- Customer Selection -->
        <div class="mb-4">
            <label for="customerId" class="block text-gray-700 font-medium mb-2">Customer</label>
            <select id="customerId" name="customerId" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Walk-in Customer</option>
                <?php foreach($customers as $customer): ?>
                    <option value="<?= $customer['id'] ?>" <?= $sale['customerId'] == $customer['id'] ? 'selected' : '' ?>>
                        <?= $customer['name'] ?> (<?= $customer['phone'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Payment Details -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="paymentMethod" class="block text-gray-700 font-medium mb-2">Payment Method</label>
                <select id="paymentMethod" name="paymentMethod" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Cash" <?= $sale['paymentMethod'] == 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="Card" <?= $sale['paymentMethod'] == 'Card' ? 'selected' : '' ?>>Card</option>
                    <option value="Bank Transfer" <?= $sale['paymentMethod'] == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="UPI" <?= $sale['paymentMethod'] == 'UPI' ? 'selected' : '' ?>>UPI</option>
                    <option value="Check" <?= $sale['paymentMethod'] == 'Check' ? 'selected' : '' ?>>Check</option>
                </select>
            </div>
            <div>
                <label for="paymentStatus" class="block text-gray-700 font-medium mb-2">Payment Status</label>
                <select id="paymentStatus" name="paymentStatus" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Paid" <?= $sale['paymentStatus'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Partial" <?= $sale['paymentStatus'] == 'Partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="Unpaid" <?= $sale['paymentStatus'] == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                </select>
            </div>
        </div>
        
        <!-- Items List (Read-only) -->
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Sale Items</label>
            <div class="bg-gray-50 p-4 rounded-lg">
                <ul class="divide-y divide-gray-200">
                    <?php foreach($saleItems as $item): ?>
                    <li class="py-2">
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium"><?= $item['itemName'] ?></p>
                                <p class="text-sm text-gray-600">Code: <?= $item['itemCode'] ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold"><?= formatCurrency($item['total']) ?></p>
                                <p class="text-sm text-gray-600"><?= $item['quantity'] ?> × <?= formatCurrency($item['price']) ?></p>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-3 flex justify-between border-t pt-3">
                    <span class="font-bold">Total:</span>
                    <span class="font-bold"><?= formatCurrency($sale['totalPrice']) ?></span>
                </div>
                <p class="text-xs text-gray-500 mt-2 italic">Note: Sale items cannot be modified after creation.</p>
            </div>
        </div>
        
        <div class="mt-4">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i> Update Sale
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
        <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
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