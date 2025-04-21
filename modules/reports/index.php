<?php 
// modules/reports/index.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 
include $basePath . 'includes/finance_handler.php';

// Get financial summary
$financialSummary = getFinancialSummary();
?>

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Reports & Analytics</h2>
    
    <!-- Financial Summary -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-medium text-gray-800 mb-2">Financial Overview</h3>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Current Balance</p>
                <p class="text-xl font-bold text-blue-600"><?= formatCurrency($financialSummary['current_balance']) ?></p>
            </div>
            <div class="bg-green-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Total Profit</p>
                <p class="text-xl font-bold <?= $financialSummary['total_profit'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= formatCurrency($financialSummary['total_profit']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Available Reports -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Available Reports</h3>
        </div>
        
        <div class="divide-y">
            <!-- Financial Reports -->
            <div class="p-4">
                <h4 class="font-medium text-gray-800 mb-3">Financial Reports</h4>
                <div class="grid grid-cols-1 gap-2">
                    <a href="financial_transactions.php" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-money-bill-wave text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Financial Transactions</p>
                            <p class="text-xs text-gray-600">View all financial transactions and account history</p>
                        </div>
                    </a>
                    
                    <a href="../finances/add_transaction.php" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-plus text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Add Manual Transaction</p>
                            <p class="text-xs text-gray-600">Record manual income, expenses or adjustments</p>
                        </div>
                    </a>
                    
                    <a href="../settings/company_finances.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                            <i class="fas fa-cog text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Financial Settings</p>
                            <p class="text-xs text-gray-600">Configure financial year and opening balance</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Sales Reports -->
            <div class="p-4">
                <h4 class="font-medium text-gray-800 mb-3">Sales Reports</h4>
                <div class="grid grid-cols-1 gap-2">
                    <a href="sales.php" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                            <i class="fas fa-chart-line text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Sales Analysis</p>
                            <p class="text-xs text-gray-600">Analytics and trends for sales performance</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Inventory Reports -->
            <div class="p-4">
                <h4 class="font-medium text-gray-800 mb-3">Inventory Reports</h4>
                <div class="grid grid-cols-1 gap-2">
                    <a href="inventory.php" class="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                            <i class="fas fa-boxes text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Inventory Status</p>
                            <p class="text-xs text-gray-600">Current stock levels and valuation</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Expense Reports -->
            <div class="p-4">
                <h4 class="font-medium text-gray-800 mb-3">Expense Reports</h4>
                <div class="grid grid-cols-1 gap-2">
                    <a href="expenses.php" class="flex items-center p-3 bg-red-50 rounded-lg hover:bg-red-100">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                            <i class="fas fa-file-invoice-dollar text-red-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Expense Analysis</p>
                            <p class="text-xs text-gray-600">Track and analyze business expenses</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
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
        <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
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

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>