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

// Get inventory logs for this product
$inventoryLogs = $db->select("SELECT il.*, u.username 
                            FROM inventory_log il 
                            LEFT JOIN users u ON il.userId = u.id 
                            WHERE il.productId = :productId 
                            ORDER BY il.createdAt DESC 
                            LIMIT 5", 
                            ['productId' => $productId]);

// Get sales history for this product
$salesHistory = $db->select("SELECT si.*, s.invoiceNumber, s.createdAt, c.name as customerName  
                           FROM sale_items si 
                           JOIN sales s ON si.saleId = s.id 
                           LEFT JOIN customers c ON s.customerId = c.id 
                           WHERE si.productId = :productId 
                           ORDER BY s.createdAt DESC 
                           LIMIT 5", 
                           ['productId' => $productId]);
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Product Details</h2>
    </div>
    
    <!-- Product Information -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= $product['itemName'] ?></h3>
                <p class="text-sm text-gray-600 mt-1">Code: <?= $product['itemCode'] ?></p>
                <?php if (!empty($product['hsn'])): ?>
                <p class="text-sm text-gray-600">HSN: <?= $product['hsn'] ?></p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-slate-950"><?= formatCurrency($product['priceUnit']) ?></p>
                <p class="text-sm <?= $product['qty'] <= LOW_STOCK_THRESHOLD ? 'text-red-600 font-bold' : 'text-gray-600' ?>">
                    <?= $product['qty'] ?> <?= $product['unitType'] ?> in stock
                </p>
            </div>
        </div>
        <div class="mt-4 flex justify-between">
            <span class="text-sm text-gray-500">
                <?= $product['createdAt'] ? 'Created: ' . date('M d, Y', strtotime($product['createdAt'])) : '' ?>
                <?= $product['updatedAt'] ? ' • Updated: ' . date('M d, Y', strtotime($product['updatedAt'])) : '' ?>
            </span>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <a href="edit.php?id=<?= $productId ?>" class="bg-red-900 text-white py-2 px-4 rounded-lg text-center">
            <i class="fas fa-edit mr-2"></i> Edit Product
        </a>
        <a href="../inventory/adjust.php?id=<?= $productId ?>" class="bg-green-600 text-white py-2 px-4 rounded-lg text-center">
            <i class="fas fa-warehouse mr-2"></i> Adjust Stock
        </a>
    </div>
    
    <!-- Stock History -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Recent Stock Changes</h3>
            <a href="../inventory/history.php?product=<?= $productId ?>" class="text-sm text-slate-950">View All</a>
        </div>
        
        <?php if (count($inventoryLogs) > 0): ?>
        <ul class="divide-y">
            <?php foreach($inventoryLogs as $log): ?>
            <li class="p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium">
                            <?= $log['adjustmentType'] == 'add' ? 'Added' : 'Removed' ?> 
                            <?= $log['quantity'] ?> <?= $product['unitType'] ?>
                        </p>
                        <p class="text-xs text-gray-600">
                            <?= date('M d, Y h:i A', strtotime($log['createdAt'])) ?> • 
                            <?= $log['username'] ?? 'System' ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Reason: <?= $log['reason'] ?></p>
                        <p class="text-xs <?= $log['adjustmentType'] == 'add' ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $log['previousQty'] ?> → <?= $log['newQty'] ?>
                        </p>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            No stock history found
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sales History -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Recent Sales</h3>
        </div>
        
        <?php if (count($salesHistory) > 0): ?>
        <ul class="divide-y">
            <?php foreach($salesHistory as $sale): ?>
            <li class="p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium"><?= $sale['invoiceNumber'] ?></p>
                        <p class="text-xs text-gray-600">
                            <?= date('M d, Y', strtotime($sale['createdAt'])) ?> • 
                            <?= $sale['customerName'] ?? 'Walk-in Customer' ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold"><?= formatCurrency($sale['total']) ?></p>
                        <p class="text-xs text-gray-600">
                            <?= $sale['quantity'] ?> × <?= formatCurrency($sale['price']) ?>
                        </p>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            No sales history found
        </div>
        <?php endif; ?>
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