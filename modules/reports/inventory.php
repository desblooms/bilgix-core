<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get inventory data
$inventoryData = $db->select("SELECT * FROM products ORDER BY qty ASC");

// Calculate inventory statistics
$totalItems = count($inventoryData);
$totalValue = 0;
$lowStockCount = 0;
$outOfStockCount = 0;

foreach ($inventoryData as $item) {
    $totalValue += ($item['priceUnit'] * $item['qty']);
    
    if ($item['qty'] <= 5 && $item['qty'] > 0) {
        $lowStockCount++;
    } else if ($item['qty'] == 0) {
        $outOfStockCount++;
    }
}

// Group products by category for chart
$categoryCounts = [];
foreach ($inventoryData as $item) {
    $unitType = $item['unitType'];
    if (!isset($categoryCounts[$unitType])) {
        $categoryCounts[$unitType] = 0;
    }
    $categoryCounts[$unitType]++;
}
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Inventory Report</h2>
    
    <!-- Inventory Summary -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Total Products</h3>
            <p class="text-2xl font-bold text-slate-950"><?= $totalItems ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Inventory Value</h3>
            <p class="text-2xl font-bold text-green-600"><?= formatCurrency($totalValue) ?></p>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Low Stock</h3>
            <p class="text-2xl font-bold text-yellow-600"><?= $lowStockCount ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Out of Stock</h3>
            <p class="text-2xl font-bold text-red-600"><?= $outOfStockCount ?></p>
        </div>
    </div>
    
    <!-- Category Distribution Chart -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-md font-medium text-gray-800 mb-4">Product Categories</h3>
        <div class="w-full h-64 overflow-hidden">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <!-- Inventory List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Inventory List</h3>
            <a href="../products/list.php" class="text-sm text-slate-950">View All</a>
        </div>
        
        <div class="p-4">
            <div class="relative">
                <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="Search inventory...">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
        
        <ul class="divide-y" id="inventoryList">
            <?php foreach($inventoryData as $item): ?>
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
                               ($item['qty'] <= 5 ? 'text-yellow-600 font-bold' : 'text-gray-600') ?>">
                            <?= $item['qty'] ?> in stock
                        </p>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
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
    <a href="../reports/index.php" class="flex flex-col items-center p-2 text-slate-950">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
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
    
    // Chart data
    const categoryData = <?= json_encode($categoryCounts) ?>;
    
    // Prepare data for chart
    const categories = Object.keys(categoryData);
    const counts = Object.values(categoryData);
    
    // Colors for chart
    const backgroundColors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)'
    ];
    
    // Create chart
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: categories,
            datasets: [{
                data: counts,
                backgroundColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((total, num) => total + num, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
</script>

<?php
// Close the main div and add the footer

// Include footer (which contains ob_end_flush())
include $basePath . 'includes/footer.php';
?>