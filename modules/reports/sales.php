<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Default date range (current month)
$startDate = date('Y-m-01');
$endDate = date('Y-m-t');

// Handle filter changes
if (isset($_GET['start']) && isset($_GET['end'])) {
    $startDate = $_GET['start'];
    $endDate = $_GET['end'];
}

// Get sales data for the selected period
$salesData = $db->select("SELECT s.*, c.name as customerName 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customerId = c.id 
                        WHERE DATE(s.createdAt) BETWEEN :startDate AND :endDate 
                        ORDER BY s.createdAt DESC", 
                        ['startDate' => $startDate, 'endDate' => $endDate]);

// Calculate totals
$totalSales = 0;
$salesCount = count($salesData);

foreach ($salesData as $sale) {
    $totalSales += $sale['totalPrice'];
}

// Get daily sales for chart
$dailySales = $db->select("SELECT 
                         DATE(createdAt) as saleDate, 
                         SUM(totalPrice) as dailyTotal,
                         COUNT(*) as saleCount
                         FROM sales 
                         WHERE DATE(createdAt) BETWEEN :startDate AND :endDate 
                         GROUP BY DATE(createdAt) 
                         ORDER BY saleDate",
                         ['startDate' => $startDate, 'endDate' => $endDate]);
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Sales Report</h2>
    
    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form action="" method="GET" class="flex flex-col space-y-3">
            <div>
                <label for="start" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start" name="start" class="w-full p-2 border rounded-lg" value="<?= $startDate ?>">
            </div>
            <div>
                <label for="end" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end" name="end" class="w-full p-2 border rounded-lg" value="<?= $endDate ?>">
            </div>
            <button type="submit" class="bg-red-900 text-white py-2 px-4 rounded-lg">
                <i class="fas fa-filter mr-2"></i> Apply Filter
            </button>
        </form>
    </div>
    
    <!-- Sales Summary -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Total Sales</h3>
            <p class="text-2xl font-bold text-slate-950"><?= formatCurrency($totalSales) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Number of Sales</h3>
            <p class="text-2xl font-bold text-green-600"><?= $salesCount ?></p>
        </div>
    </div>
    
    <!-- Sales Chart -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-md font-medium text-gray-800 mb-4">Daily Sales</h3>
        <div class="w-full h-64 overflow-hidden">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    
    <!-- Sales List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Sales List</h3>
        </div>
        
        <?php if (count($salesData) > 0): ?>
        <ul class="divide-y">
            <?php foreach($salesData as $sale): ?>
            <li class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= $sale['invoiceNumber'] ?></p>
                        <p class="text-sm text-gray-600">
                            <?= $sale['customerName'] ?? 'Walk-in Customer' ?> • 
                            <?= date('M d, Y', strtotime($sale['createdAt'])) ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600"><?= formatCurrency($sale['totalPrice']) ?></p>
                        <a href="../sales/view.php?id=<?= $sale['id'] ?>" class="text-sm text-slate-950">View</a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            No sales found for the selected period
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
    // Chart data
    const salesData = <?= json_encode($dailySales) ?>;
    
    // Prepare data for chart
    const dates = salesData.map(item => item.saleDate);
    const amounts = salesData.map(item => item.dailyTotal);
    
    // Create chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Daily Sales',
                data: amounts,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.1,
                pointBackgroundColor: 'rgba(59, 130, 246, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Sales: $' + context.raw;
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