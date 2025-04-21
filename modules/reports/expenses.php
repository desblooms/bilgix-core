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

// Get expenses data for the selected period
$expensesData = $db->select("SELECT e.*, c.name as categoryName 
                          FROM expenses e 
                          LEFT JOIN expense_categories c ON e.categoryId = c.id 
                          WHERE DATE(e.expenseDate) BETWEEN :startDate AND :endDate 
                          ORDER BY e.expenseDate DESC", 
                          ['startDate' => $startDate, 'endDate' => $endDate]);

// Calculate totals
$totalExpenses = 0;
$expensesCount = count($expensesData);

foreach ($expensesData as $expense) {
    $totalExpenses += $expense['amount'];
}

// Get expenses by category for chart
$categoryExpenses = $db->select("SELECT 
                               c.name as categoryName, 
                               SUM(e.amount) as totalAmount 
                               FROM expenses e 
                               LEFT JOIN expense_categories c ON e.categoryId = c.id 
                               WHERE DATE(e.expenseDate) BETWEEN :startDate AND :endDate 
                               GROUP BY e.categoryId, c.name
                               ORDER BY totalAmount DESC",
                               ['startDate' => $startDate, 'endDate' => $endDate]);

// Get daily expenses for chart
$dailyExpenses = $db->select("SELECT 
                           DATE(expenseDate) as expenseDay, 
                           SUM(amount) as dailyTotal
                           FROM expenses 
                           WHERE DATE(expenseDate) BETWEEN :startDate AND :endDate 
                           GROUP BY DATE(expenseDate) 
                           ORDER BY expenseDay",
                           ['startDate' => $startDate, 'endDate' => $endDate]);
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Expense Report</h2>
    
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
    
    <!-- Expenses Summary -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Total Expenses</h3>
            <p class="text-2xl font-bold text-red-600"><?= formatCurrency($totalExpenses) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-sm text-gray-600 mb-1">Number of Expenses</h3>
            <p class="text-2xl font-bold text-slate-950"><?= $expensesCount ?></p>
        </div>
    </div>
    
    <!-- Category Expenses Chart -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-md font-medium text-gray-800 mb-4">Expenses by Category</h3>
        <div class="w-full h-64 overflow-hidden">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <!-- Daily Expenses Chart -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-md font-medium text-gray-800 mb-4">Daily Expenses</h3>
        <div class="w-full h-64 overflow-hidden">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>
    
    <!-- Expenses List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Expenses List</h3>
        </div>
        
        <div class="p-4">
            <div class="relative">
                <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="Search expenses...">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
        
        <?php if (count($expensesData) > 0): ?>
        <ul class="divide-y" id="expensesList">
            <?php foreach($expensesData as $expense): ?>
            <li class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= $expense['description'] ?></p>
                        <p class="text-sm text-gray-600">
                            <span class="bg-gray-200 text-gray-800 text-xs px-2 py-0.5 rounded-full"><?= $expense['categoryName'] ?? 'Uncategorized' ?></span> • 
                            <?= date('M d, Y', strtotime($expense['expenseDate'])) ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-red-600"><?= formatCurrency($expense['amount']) ?></p>
                        <a href="../expenses/view.php?id=<?= $expense['id'] ?>" class="text-sm text-slate-950">View</a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            No expenses found for the selected period
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Add Expense Button -->
    <div class="mt-4">
        <a href="../expenses/add.php" class="block w-full bg-red-900 text-white py-3 px-4 rounded-lg text-center shadow-lg">
            <i class="fas fa-plus-circle mr-2"></i> Add New Expense
        </a>
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

<!-- Load Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>

<!-- Search and Chart Functionality -->
<script>
// Search functionality
const searchInput = document.getElementById('searchInput');
const expensesList = document.getElementById('expensesList');
const expensesItems = expensesList ? Array.from(expensesList.getElementsByTagName('li')) : [];

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    
    expensesItems.forEach(item => {
        const description = item.querySelector('.font-medium').textContent.toLowerCase();
        const categoryInfo = item.querySelector('.text-gray-600').textContent.toLowerCase();
        
        if (description.includes(searchTerm) || categoryInfo.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// Format currency
function formatCurrency(amount) {
    return '<?= CURRENCY ?>' + parseFloat(amount).toFixed(2);
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Category Pie Chart
    if(document.getElementById('categoryChart')) {
        const categoryData = <?= json_encode($categoryExpenses) ?>;
        const categories = categoryData.map(item => item.categoryName || 'Uncategorized');
        const amounts = categoryData.map(item => parseFloat(item.totalAmount) || 0);
        
        const backgroundColors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
        ];
        
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'pie',
            data: {
                labels: categories,
                datasets: [{
                    data: amounts,
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
                                return `${label}: ${formatCurrency(value)}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Daily Bar Chart
    if(document.getElementById('dailyChart')) {
        const dailyData = <?= json_encode($dailyExpenses) ?>;
        const days = dailyData.map(item => item.expenseDay);
        const dailyAmounts = dailyData.map(item => parseFloat(item.dailyTotal) || 0);
        
        const ctxDaily = document.getElementById('dailyChart').getContext('2d');
        new Chart(ctxDaily, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [{
                    label: 'Daily Expenses',
                    data: dailyAmounts,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
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
                                return formatCurrency(value);
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
                                return 'Expenses: ' + formatCurrency(context.raw);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include $basePath . 'includes/footer.php'; ?>