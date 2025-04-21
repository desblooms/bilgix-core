<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 
include_once $basePath . 'includes/finance_handler.php';
// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No purchase specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/purchases/list.php');
}

$purchaseId = (int)$_GET['id'];

// Get purchase details
$purchase = $db->select("SELECT p.*, v.name as vendorName 
                       FROM purchases p 
                       LEFT JOIN vendors v ON p.vendorId = v.id 
                       WHERE p.id = :id", ['id' => $purchaseId]);

if (empty($purchase)) {
    $_SESSION['message'] = "Purchase not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/purchases/list.php');
}

$purchase = $purchase[0];

// Get all vendors for dropdown
$vendors = $db->select("SELECT * FROM vendors ORDER BY name ASC");

// Get purchase items
$purchaseItems = $db->select("SELECT pi.*, p.itemName, p.itemCode 
                            FROM purchase_items pi 
                            JOIN products p ON pi.productId = p.id 
                            WHERE pi.purchaseId = :purchaseId", 
                            ['purchaseId' => $purchaseId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendorId = !empty($_POST['vendorId']) ? intval($_POST['vendorId']) : null;
    $paymentStatus = sanitize($_POST['paymentStatus']);
    
    // Update only vendor and payment details (not allowed to edit items once purchase is created)
    $data = [
        'vendorId' => $vendorId,
        'paymentStatus' => $paymentStatus,
        'updatedAt' => date('Y-m-d H:i:s')
    ];
    
    $updated = $db->update('purchases', $data, 'id = :id', ['id' => $purchaseId]);
    
    if ($updated) {
        $_SESSION['message'] = "Purchase updated successfully!";
        $_SESSION['message_type'] = "success";
        if ($paymentStatus != $purchase['paymentStatus']) {
            $description = "Payment status updated for Purchase #" . $purchase['purchaseNumber'] . 
                          " from " . $purchase['paymentStatus'] . " to " . $paymentStatus;
            
            recordFinancialTransaction(
                'adjustment',
                'purchase',
                $purchaseId,
                0, // No amount change, just recording the status change
                $description,
                $_SESSION['user_id'] ?? null
            );
        }
        redirect($basePath . 'modules/purchases/view.php?id=' . $purchaseId);
    } else {
        $_SESSION['message'] = "Failed to update purchase!";
        $_SESSION['message_type'] = "error";
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="view.php?id=<?= $purchaseId ?>" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Edit Purchase</h2>
    </div>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <!-- Purchase Info -->
        <div class="border-b pb-4 mb-4">
            <h3 class="text-lg font-medium text-gray-800 mb-2"><?= $purchase['purchaseNumber'] ?></h3>
            <p class="text-sm text-gray-600"><?= date('F d, Y h:i A', strtotime($purchase['createdAt'])) ?></p>
            <p class="text-lg font-bold text-slate-950 mt-1"><?= formatCurrency($purchase['totalAmount']) ?></p>
        </div>
        
        <!-- Vendor Selection -->
        <div class="mb-4">
            <label for="vendorId" class="block text-gray-700 font-medium mb-2">Vendor</label>
            <select id="vendorId" name="vendorId" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                <option value="">-- Select Vendor --</option>
                <?php foreach($vendors as $vendor): ?>
                    <option value="<?= $vendor['id'] ?>" <?= $purchase['vendorId'] == $vendor['id'] ? 'selected' : '' ?>>
                        <?= $vendor['name'] ?> (<?= $vendor['phone'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Payment Status -->
        <div class="mb-4">
            <label for="paymentStatus" class="block text-gray-700 font-medium mb-2">Payment Status</label>
            <select id="paymentStatus" name="paymentStatus" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                <option value="Paid" <?= $purchase['paymentStatus'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                <option value="Partial" <?= $purchase['paymentStatus'] == 'Partial' ? 'selected' : '' ?>>Partial</option>
                <option value="Unpaid" <?= $purchase['paymentStatus'] == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
            </select>
        </div>
        
        <!-- Items List (Read-only) -->
        <div class="mb-4">
            <label class="block text-gray-700 font-medium mb-2">Purchase Items</label>
            <div class="bg-gray-50 p-4 rounded-lg">
                <ul class="divide-y divide-gray-200">
                    <?php foreach($purchaseItems as $item): ?>
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
                    <span class="font-bold"><?= formatCurrency($purchase['totalAmount']) ?></span>
                </div>
                <p class="text-xs text-gray-500 mt-2 italic">Note: Purchase items cannot be modified after creation.</p>
            </div>
        </div>
        
        <div class="mt-4">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Update Purchase
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
    <a href="../reports/index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>