<?php 
// modules/reports/financial_transactions.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 
include $basePath . 'includes/finance_handler.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter parameters
$filters = [];

if (isset($_GET['transaction_type']) && !empty($_GET['transaction_type'])) {
    $filters['transaction_type'] = sanitize($_GET['transaction_type']);
}

if (isset($_GET['reference_type']) && !empty($_GET['reference_type'])) {
    $filters['reference_type'] = sanitize($_GET['reference_type']);
}

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $filters['start_date'] = sanitize($_GET['start_date']);
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $filters['end_date'] = sanitize($_GET['end_date']);
}

// Default date range (current month) if not specified
if (empty($filters['start_date']) && empty($filters['end_date'])) {
    $filters['start_date'] = date('Y-m-01'); // First day of current month
    $filters['end_date'] = date('Y-m-t');    // Last day of current month
}

// Get transactions
$transactions = $financeHandler->getTransactions($filters, $perPage, $offset);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as count FROM financial_transactions";
$whereConditions = [];
$countParams = [];

if (!empty($filters['transaction_type'])) {
    $whereConditions[] = "transaction_type = :transaction_type";
    $countParams['transaction_type'] = $filters['transaction_type'];
}

if (!empty($filters['reference_type'])) {
    $whereConditions[] = "reference_type = :reference_type";
    $countParams['reference_type'] = $filters['reference_type'];
}

if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
    $whereConditions[] = "DATE(transaction_date) BETWEEN :start_date AND :end_date";
    $countParams['start_date'] = $filters['start_date'];
    $countParams['end_date'] = $filters['end_date'];
} else if (!empty($filters['start_date'])) {
    $whereConditions[] = "DATE(transaction_date) >= :start_date";
    $countParams['start_date'] = $filters['start_date'];
} else if (!empty($filters['end_date'])) {
    $whereConditions[] = "DATE(transaction_date) <= :end_date";
    $countParams['end_date'] = $filters['end_date'];
}

if (!empty($whereConditions)) {
    $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
}

$totalTransactions = $db->select($countQuery, $countParams);
$totalPages = ceil($totalTransactions[0]['count'] / $perPage);

// Calculate totals for the filtered transactions
$summaryQuery = "SELECT 
                  SUM(CASE WHEN transaction_type = 'sale' OR transaction_type = 'income' THEN amount ELSE 0 END) as total_income,
                  SUM(CASE WHEN transaction_type = 'purchase' OR transaction_type = 'expense' THEN ABS(amount) ELSE 0 END) as total_expenses,
                  SUM(amount) as net_change
                FROM financial_transactions";

if (!empty($whereConditions)) {
    $summaryQuery .= " WHERE " . implode(" AND ", $whereConditions);
}

$summary = $db->select($summaryQuery, $countParams);
$summary = $summary[0];
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="../reports/index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Financial Transactions</h2>
    </div>
    
    <!-- Filter Options -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form action="" method="GET" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="w-full p-2 border rounded-lg" value="<?= $filters['start_date'] ?? '' ?>">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="w-full p-2 border rounded-lg" value="<?= $filters['end_date'] ?? '' ?>">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                    <select id="transaction_type" name="transaction_type" class="w-full p-2 border rounded-lg">
                        <option value="">All Types</option>
                        <option value="sale" <?= ($filters['transaction_type'] ?? '') == 'sale' ? 'selected' : '' ?>>Sales</option>
                        <option value="purchase" <?= ($filters['transaction_type'] ?? '') == 'purchase' ? 'selected' : '' ?>>Purchases</option>
                        <option value="expense" <?= ($filters['transaction_type'] ?? '') == 'expense' ? 'selected' : '' ?>>Expenses</option>
                        <option value="income" <?= ($filters['transaction_type'] ?? '') == 'income' ? 'selected' : '' ?>>Other Income</option>
                        <option value="adjustment" <?= ($filters['transaction_type'] ?? '') == 'adjustment' ? 'selected' : '' ?>>Adjustments</option>
                    </select>
                </div>
                <div>
                    <label for="reference_type" class="block text-sm font-medium text-gray-700 mb-1">Reference Type</label>
                    <select id="reference_type" name="reference_type" class="w-full p-2 border rounded-lg">
                        <option value="">All References</option>
                        <option value="sale" <?= ($filters['reference_type'] ?? '') == 'sale' ? 'selected' : '' ?>>Sale</option>
                        <option value="purchase" <?= ($filters['reference_type'] ?? '') == 'purchase' ? 'selected' : '' ?>>Purchase</option>
                        <option value="expense" <?= ($filters['reference_type'] ?? '') == 'expense' ? 'selected' : '' ?>>Expense</option>
                        <option value="manual" <?= ($filters['reference_type'] ?? '') == 'manual' ? 'selected' : '' ?>>Manual</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-blue-50 p-4 rounded-lg shadow">
            <h3 class="text-sm text-gray-600 mb-1">Total Income</h3>
            <p class="text-xl font-bold text-blue-600"><?= formatCurrency($summary['total_income'] ?? 0) ?></p>
        </div>
        <div class="bg-red-50 p-4 rounded-lg shadow">
            <h3 class="text-sm text-gray-600 mb-1">Total Expenses</h3>
            <p class="text-xl font-bold text-red-600"><?= formatCurrency($summary['total_expenses'] ?? 0) ?></p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg shadow">
            <h3 class="text-sm text-gray-600 mb-1">Net Change</h3>
            <p class="text-xl font-bold <?= ($summary['net_change'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                <?= formatCurrency($summary['net_change'] ?? 0) ?>
            </p>
        </div>
    </div>
    
    <!-- Transactions List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Transaction History</h3>
        </div>
        
        <?php if (count($transactions) > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('M d, Y H:i', strtotime($transaction['transaction_date'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php
                                switch ($transaction['transaction_type']) {
                                    case 'sale':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'purchase':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'expense':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                    case 'income':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?= ucfirst($transaction['transaction_type']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= ucfirst($transaction['reference_type']) ?> #<?= $transaction['reference_id'] ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= $transaction['description'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium <?= $transaction['amount'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= formatCurrency($transaction['amount']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium <?= $transaction['current_balance'] >= 0 ? 'text-gray-900' : 'text-red-600' ?>">
                            <?= formatCurrency($transaction['current_balance']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="p-4 border-t flex justify-center">
            <div class="flex rounded-md">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&start_date=<?= $filters['start_date'] ?? '' ?>&end_date=<?= $filters['end_date'] ?? '' ?>&transaction_type=<?= $filters['transaction_type'] ?? '' ?>&reference_type=<?= $filters['reference_type'] ?? '' ?>" class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>
                
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-700">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&start_date=<?= $filters['start_date'] ?? '' ?>&end_date=<?= $filters['end_date'] ?? '' ?>&transaction_type=<?= $filters['transaction_type'] ?? '' ?>&reference_type=<?= $filters['reference_type'] ?? '' ?>" class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            No transactions found for the selected period.
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