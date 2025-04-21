<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get product ID if provided
$productId = isset($_GET['product']) ? (int)$_GET['product'] : null;

// Query build based on whether we're filtering by product
if ($productId) {
    $product = getProduct($productId);
    if (!$product) {
        $_SESSION['message'] = "Product not found!";
        $_SESSION['message_type'] = "error";
        redirect($basePath . 'modules/inventory/list.php');
    }
    
    // Get inventory logs for this product
    $logQuery = "SELECT l.*, p.itemName, p.itemCode, p.unitType, u.username 
                FROM inventory_log l 
                JOIN products p ON l.productId = p.id 
                LEFT JOIN users u ON l.userId = u.id
                WHERE l.productId = :productId
                ORDER BY l.createdAt DESC
                LIMIT :offset, :limit";
    
    $logs = $db->select($logQuery, [
        'productId' => $productId,
        'offset' => (int)$offset,  // Cast to integer explicitly
        'limit' => (int)$perPage   // Cast to integer explicitly
    ]);
    
    // Count total logs for pagination
    $totalLogs = $db->select("SELECT COUNT(*) as count FROM inventory_log WHERE productId = :productId", 
                            ['productId' => $productId]);
    $totalPages = ceil($totalLogs[0]['count'] / $perPage);
                            
    $pageTitle = "Inventory History: " . $product['itemName'];
} else {
    // Get all inventory logs
    $logQuery = "SELECT l.*, p.itemName, p.itemCode, p.unitType, u.username 
                FROM inventory_log l 
                JOIN products p ON l.productId = p.id 
                LEFT JOIN users u ON l.userId = u.id
                ORDER BY l.createdAt DESC
                LIMIT :offset, :limit";
    
    $logs = $db->select($logQuery, [
        'offset' => (int)$offset,  // Cast to integer explicitly
        'limit' => (int)$perPage   // Cast to integer explicitly
    ]);
    
    // Count total logs for pagination
    $totalLogs = $db->select("SELECT COUNT(*) as count FROM inventory_log");
    $totalPages = ceil($totalLogs[0]['count'] / $perPage);
    
    $pageTitle = "Inventory History";
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800"><?= $pageTitle ?></h2>
    </div>
    
    <?php if (isset($product)): ?>
    <!-- Product Info if filtering -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="font-bold"><?= $product['itemName'] ?></h3>
                <p class="text-sm text-gray-600">Code: <?= $product['itemCode'] ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600">Current Stock:</p>
                <p class="font-bold <?= $product['qty'] <= LOW_STOCK_THRESHOLD ? 'text-yellow-600' : 'text-green-600' ?>">
                    <?= $product['qty'] ?> <?= $product['unitType'] ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Inventory History List -->
    <?php if (count($logs) > 0): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y">
            <?php foreach($logs as $log): ?>
            <li class="p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <?php if (!isset($product)): ?>
                        <p class="font-medium"><?= $log['itemName'] ?></p>
                        <p class="text-sm text-gray-600">Code: <?= $log['itemCode'] ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-gray-600">
                            <?= date('M d, Y h:i A', strtotime($log['createdAt'])) ?> by 
                            <?= $log['username'] ?? 'System' ?>
                        </p>
                        <p class="text-sm text-gray-600 mt-1">Reason: <?= $log['reason'] ?></p>
                    </div>
                    <div class="text-right">
                        <?php if ($log['adjustmentType'] == 'add'): ?>
                        <p class="text-green-600 font-medium">
                            +<?= $log['quantity'] ?> <?= $log['unitType'] ?>
                        </p>
                        <?php else: ?>
                        <p class="text-red-600 font-medium">
                            -<?= $log['quantity'] ?> <?= $log['unitType'] ?>
                        </p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-600">
                            <?= $log['previousQty'] ?> → <?= $log['newQty'] ?> <?= $log['unitType'] ?>
                        </p>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center mt-4">
        <div class="inline-flex rounded-md shadow">
            <?php if ($page > 1): ?>
            <a href="?<?= isset($productId) ? 'product=' . $productId . '&' : '' ?>page=<?= $page - 1 ?>" class="py-2 px-4 bg-white rounded-l-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <span class="py-2 px-4 bg-gray-100 border-t border-b border-gray-300 text-gray-700">
                Page <?= $page ?> of <?= $totalPages ?>
            </span>
            
            <?php if ($page < $totalPages): ?>
            <a href="?<?= isset($productId) ? 'product=' . $productId . '&' : '' ?>page=<?= $page + 1 ?>" class="py-2 px-4 bg-white rounded-r-md border border-gray-300 text-gray-700 hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500 mb-4">No inventory history found</p>
        <a href="list.php" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">Back to Inventory</a>
    </div>
    <?php endif; ?>
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