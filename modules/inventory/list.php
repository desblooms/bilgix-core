<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get inventory data
$inventoryData = $db->select("SELECT * FROM products ORDER BY itemName ASC");

// Get filter param
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Filter inventory based on selection
if ($filter === 'low') {
    $filteredInventory = array_filter($inventoryData, function($item) {
        return ($item['qty'] <= LOW_STOCK_THRESHOLD && $item['qty'] > 0);
    });
} elseif ($filter === 'out') {
    $filteredInventory = array_filter($inventoryData, function($item) {
        return $item['qty'] <= 0;
    });
} else {
    $filteredInventory = $inventoryData;
}
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Inventory</h2>
        <a href="../products/add.php" class="bg-red-900 text-white py-2 px-4 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> Add Product
        </a>
    </div>
    
    <!-- Filter Options -->
    <div class="flex overflow-x-auto mb-4 bg-white rounded-lg shadow p-2 no-scrollbar">
        <a href="?filter=all" class="whitespace-nowrap px-3 py-2 rounded-lg mr-2 <?= $filter === 'all' ? 'bg-red-900 text-white' : 'bg-gray-200 text-gray-700' ?>">
            All Items
        </a>
        <a href="?filter=low" class="whitespace-nowrap px-3 py-2 rounded-lg mr-2 <?= $filter === 'low' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700' ?>">
            Low Stock
        </a>
        <a href="?filter=out" class="whitespace-nowrap px-3 py-2 rounded-lg <?= $filter === 'out' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
            Out of Stock
        </a>
    </div>
    
    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative">
            <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="Search inventory...">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>
    
    <!-- Inventory List -->
    <?php if (count($filteredInventory) > 0): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y" id="inventoryList">
            <?php foreach($filteredInventory as $item): ?>
            <li class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= $item['itemName'] ?></p>
                        <p class="text-sm text-gray-600">Code: <?= $item['itemCode'] ?> (<?= $item['unitType'] ?>)</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold"><?= formatCurrency($item['priceUnit']) ?></p>
                        <p class="text-sm 
                            <?= $item['qty'] == 0 ? 'text-red-600 font-bold' : 
                               ($item['qty'] <= LOW_STOCK_THRESHOLD ? 'text-yellow-600 font-bold' : 'text-gray-600') ?>">
                            <?= $item['qty'] ?> in stock
                        </p>
                    </div>
                </div>
                <div class="flex justify-end mt-2 space-x-2">
                    <a href="adjust.php?id=<?= $item['id'] ?>" class="text-slate-950 text-sm">
                        <i class="fas fa-edit mr-1"></i> Adjust Stock
                    </a>
                    <a href="../products/edit.php?id=<?= $item['id'] ?>" class="text-gray-600 text-sm">
                        <i class="fas fa-cog mr-1"></i> Edit Details
                    </a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500 mb-4">No inventory items found for this filter</p>
        <?php if ($filter !== 'all'): ?>
        <a href="?filter=all" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">View All Items</a>
        <?php else: ?>
        <a href="../products/add.php" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">Add Your First Product</a>
        <?php endif; ?>
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

<script>
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const inventoryList = document.getElementById('inventoryList');
    const inventoryItems = inventoryList ? Array.from(inventoryList.getElementsByTagName('li')) : [];
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        inventoryItems.forEach(item => {
            const itemName = item.querySelector('.font-medium').textContent.toLowerCase();
            const itemCode = item.querySelector('.text-gray-600').textContent.toLowerCase();
            
            if (itemName.includes(searchTerm) || itemCode.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>

<?php
// Close the main div and add the footer

// Include footer (which contains ob_end_flush())
include $basePath . 'includes/footer.php';
?>