<?php
// Define basePath for the root directory
$basePath = './';

// Include header
include $basePath . 'includes/header.php'; 

// Check if finance_handler.php exists before including it
if (file_exists($basePath . 'includes/finance_handler.php')) {
    include_once $basePath . 'includes/finance_handler.php';
    // Get financial summary
    $financialSummary = getFinancialSummary();
} else {
    // Fallback if finance system isn't installed yet
    $financialSummary = [
        'opening_balance' => 0,
        'current_balance' => 0,
        'total_revenue' => 0,
        'total_expenses' => 0,
        'total_profit' => 0,
        'today_sales' => getSalesStats()['today'],
        'today_expenses' => 0,
        'today_profit' => getSalesStats()['today'],
        'month_sales' => getSalesStats()['month'],
        'month_expenses' => 0,
        'month_profit' => getSalesStats()['month'],
        'financial_year_start' => date('Y-m-d'),
        'financial_year_end' => date('Y-m-d', strtotime('+1 year')),
        'last_updated' => date('Y-m-d H:i:s')
    ];
}
?>



    <!-- Top App Bar -->


    <!-- Dashboard Content -->
    <div class="mb-16">
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-orange-500 to-red-900 rounded-xl shadow-md p-4 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-medium mb-1">Welcome back!</h2>
                    <p class="text-blue-100 text-sm">Here's what's happening with your business today</p>
                </div>
                <div class="text-right">
                    <p class="text-blue-100 text-xs"><?= date('l, F j, Y') ?></p>
                    <p class="font-medium"><?= date('h:i A') ?></p>
                </div>
            </div>
        </div>
        
        <!-- Financial Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center mr-4">
                        <i class="fas fa-wallet text-blue-500 text-lg"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Current Balance</h3>
                        <div class="flex items-baseline mt-1">
                            <p class="text-2xl font-bold text-gray-800"><?= formatCurrency($financialSummary['current_balance']) ?></p>
                            <span class="ml-2 text-xs text-gray-500">from <?= formatCurrency($financialSummary['opening_balance']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center mr-4">
                        <i class="fas fa-chart-line text-green-500 text-lg"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Profit</h3>
                        <div class="flex items-baseline mt-1">
                            <p class="text-2xl font-bold <?= $financialSummary['total_profit'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= formatCurrency($financialSummary['total_profit']) ?></p>
                            <span class="ml-2 text-xs text-gray-500">revenue vs expenses</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                    <div>
                        <span class="text-xs text-gray-500 block mb-1">Revenue</span>
                        <span class="font-medium text-green-600"><?= formatCurrency($financialSummary['total_revenue']) ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block mb-1">Expenses</span>
                        <span class="font-medium text-red-600"><?= formatCurrency($financialSummary['total_expenses']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daily Performance -->
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Today's Performance</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mr-3">
                        <i class="fas fa-shopping-cart text-blue-500"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Sales</h3>
                        <p class="text-lg font-bold text-gray-800"><?= formatCurrency($financialSummary['today_sales']) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center mr-3">
                        <i class="fas fa-money-bill-wave text-red-500"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Expenses</h3>
                        <p class="text-lg font-bold text-red-600"><?= formatCurrency($financialSummary['today_expenses']) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center mr-3">
                        <i class="fas fa-piggy-bank text-green-500"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Profit</h3>
                        <p class="text-lg font-bold <?= $financialSummary['today_profit'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= formatCurrency($financialSummary['today_profit']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <a href="modules/sales/add.php" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center hover:shadow-md transition duration-200 transform hover:-translate-y-1">
                <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center mr-4">
                    <i class="fas fa-cart-plus text-blue-500 text-lg"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">New Sale</h4>
                    <p class="text-xs text-gray-500 mt-1">Create a new sales transaction</p>
                </div>
            </a>
            <a href="modules/products/add.php" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center hover:shadow-md transition duration-200 transform hover:-translate-y-1">
                <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center mr-4">
                    <i class="fas fa-plus-circle text-green-500 text-lg"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Add Product</h4>
                    <p class="text-xs text-gray-500 mt-1">Add new product to inventory</p>
                </div>
            </a>
            <a href="modules/reports/index.php" class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex items-center hover:shadow-md transition duration-200 transform hover:-translate-y-1">
                <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center mr-4">
                    <i class="fas fa-chart-bar text-purple-500 text-lg"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Reports</h4>
                    <p class="text-xs text-gray-500 mt-1">View sales and financial reports</p>
                </div>
            </a>
        </div>
        
        <!-- Low Stock Alert -->
        <?php $lowStockProducts = getLowStockProducts(); ?>
        <?php if (count($lowStockProducts) > 0): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
            <div class="bg-red-50 p-4 border-b border-red-100 flex items-center">
                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-triangle text-red-500"></i>
                </div>
                <h3 class="text-base font-medium text-red-600">Low Stock Alert</h3>
            </div>
            <ul class="divide-y divide-gray-100">
                <?php foreach($lowStockProducts as $product): ?>
                <li class="p-4 flex justify-between items-center hover:bg-gray-50">
                    <div class="pr-2">
                        <p class="font-medium text-gray-800 truncate"><?= $product['itemName'] ?></p>
                        <p class="text-sm text-gray-500">Code: <?= $product['itemCode'] ?></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-red-600"><?= $product['qty'] ?> left</p>
                        <a href="modules/products/edit.php?id=<?= $product['id'] ?>" class="text-xs text-blue-600 hover:underline">Update Stock</a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Recent Sales -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center mr-3">
                        <i class="fas fa-receipt text-blue-500"></i>
                    </div>
                    <h3 class="text-base font-medium text-gray-800">Recent Sales</h3>
                </div>
                <a href="modules/sales/list.php" class="text-sm text-blue-600 hover:underline flex items-center">
                    View All
                    <i class="fas fa-chevron-right text-xs ml-1"></i>
                </a>
            </div>
            <?php
            $recentSales = $db->select("SELECT s.*, c.name as customerName FROM sales s 
                                        LEFT JOIN customers c ON s.customerId = c.id 
                                        ORDER BY s.createdAt DESC LIMIT 5");
            ?>
            <?php if (count($recentSales) > 0): ?>
                <ul class="divide-y divide-gray-100">
                    <?php foreach($recentSales as $sale): ?>
                    <li class="p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-center mb-1">
                            <p class="font-medium text-gray-800 truncate pr-2"><?= $sale['customerName'] ?? 'Walk-in Customer' ?></p>
                            <p class="text-green-600 font-bold flex-shrink-0"><?= formatCurrency($sale['totalPrice']) ?></p>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:justify-between text-sm text-gray-500">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-1 text-gray-400"></i>
                                <span><?= date('M d, Y h:i A', strtotime($sale['createdAt'])) ?></span>
                            </div>
                            <div class="flex items-center mt-1 sm:mt-0">
                                <i class="far fa-file-alt mr-1 text-gray-400"></i>
                                <span>Invoice #<?= $sale['invoiceNumber'] ?></span>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="p-8 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500">No recent sales found</p>
                    <a href="modules/sales/add.php" class="mt-3 inline-block text-sm text-blue-600 hover:underline">Create your first sale</a>
                </div>
            <?php endif; ?>
        </div>
    </div>


<!-- Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t flex justify-between items-center p-2 bottom-nav">
    <a href="index.php" class="flex flex-col items-center p-2 text-slate-950">
        <i class="fas fa-home text-xl"></i>
        <span class="text-xs mt-1">Home</span>
    </a>
    <a href="modules/products/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-box text-xl"></i>
        <span class="text-xs mt-1">Products</span>
    </a>
    <a href="modules/sales/add.php" class="flex flex-col items-center p-2 text-gray-600">
        <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </div>
        <span class="text-xs mt-1">New Sale</span>
    </a>
    <a href="modules/customers/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-users text-xl"></i>
        <span class="text-xs mt-1">Customers</span>
    </a>
    <a href="modules/reports/index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<script>
    // Side menu functionality
    const menuButton = document.getElementById('menuButton');
    const closeMenu = document.getElementById('closeMenu');
    const sideMenu = document.getElementById('sideMenu');
    
    menuButton.addEventListener('click', () => {
        sideMenu.classList.remove('hidden');
        sideMenu.querySelector('div').classList.remove('-translate-x-full');
    });
    
    closeMenu.addEventListener('click', () => {
        sideMenu.querySelector('div').classList.add('-translate-x-full');
        setTimeout(() => {
            sideMenu.classList.add('hidden');
        }, 300);
    });
    
    sideMenu.addEventListener('click', (e) => {
        if (e.target === sideMenu) {
            sideMenu.querySelector('div').classList.add('-translate-x-full');
            setTimeout(() => {
                sideMenu.classList.add('hidden');
            }, 300);
        }
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>