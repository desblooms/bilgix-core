<?php 
// modules/settings/company_finances.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get company finance settings
$companyFinances = $db->select("SELECT * FROM company_finances ORDER BY id DESC LIMIT 1");

// If no record exists, create default
if (empty($companyFinances)) {
    // Set default financial year (April to March in many countries)
    $currentYear = date('Y');
    $financialYearStart = date('Y-04-01'); // April 1st
    $financialYearEnd = date('Y-03-31', strtotime('+1 year')); // March 31st next year
    
    $data = [
        'opening_balance' => 0,
        'current_balance' => 0,
        'total_revenue' => 0,
        'total_expenses' => 0,
        'total_profit' => 0,
        'last_updated' => date('Y-m-d H:i:s'),
        'financial_year_start' => $financialYearStart,
        'financial_year_end' => $financialYearEnd,
        'notes' => 'Initial setup'
    ];
    
    $db->insert('company_finances', $data);
    $companyFinances = $db->select("SELECT * FROM company_finances ORDER BY id DESC LIMIT 1");
}

$companyFinances = $companyFinances[0];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $openingBalance = floatval($_POST['opening_balance']);
    $financialYearStart = sanitize($_POST['financial_year_start']);
    $financialYearEnd = sanitize($_POST['financial_year_end']);
    $notes = sanitize($_POST['notes']);
    
    // Calculate current balance (opening balance + revenue - expenses)
    $currentBalance = $openingBalance + $companyFinances['total_revenue'] - $companyFinances['total_expenses'];
    
    // Update finances
    $data = [
        'opening_balance' => $openingBalance,
        'current_balance' => $currentBalance,
        'financial_year_start' => $financialYearStart,
        'financial_year_end' => $financialYearEnd,
        'notes' => $notes,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    $updated = $db->update('company_finances', $data, 'id = :id', ['id' => $companyFinances['id']]);
    
    if ($updated) {
        $_SESSION['message'] = "Company financial settings updated successfully!";
        $_SESSION['message_type'] = "success";
        redirect($basePath . 'modules/settings/company_finances.php');
    } else {
        $_SESSION['message'] = "Failed to update financial settings.";
        $_SESSION['message_type'] = "error";
    }
}

// Get statistics
$totalSales = $db->select("SELECT SUM(totalPrice) as total FROM sales")[0]['total'] ?? 0;
$totalExpenses = $db->select("SELECT SUM(amount) as total FROM expenses")[0]['total'] ?? 0;
$totalPurchases = $db->select("SELECT SUM(totalAmount) as total FROM purchases")[0]['total'] ?? 0;
$totalProfit = $totalSales - $totalExpenses - $totalPurchases;
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Company Financial Settings</h2>
    </div>
    
    <!-- Financial Overview -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-medium text-gray-800 mb-2">Financial Overview</h3>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Opening Balance</p>
                <p class="text-xl font-bold text-blue-600"><?= formatCurrency($companyFinances['opening_balance']) ?></p>
            </div>
            <div class="bg-green-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Current Balance</p>
                <p class="text-xl font-bold text-green-600"><?= formatCurrency($companyFinances['current_balance']) ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-indigo-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Total Revenue</p>
                <p class="text-lg font-bold text-indigo-600"><?= formatCurrency($companyFinances['total_revenue']) ?></p>
            </div>
            <div class="bg-red-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Total Expenses</p>
                <p class="text-lg font-bold text-red-600"><?= formatCurrency($companyFinances['total_expenses']) ?></p>
            </div>
            <div class="bg-purple-50 p-3 rounded-lg">
                <p class="text-sm text-gray-600">Total Profit</p>
                <p class="text-lg font-bold text-purple-600"><?= formatCurrency($companyFinances['total_profit']) ?></p>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">Financial Year: <?= date('d M Y', strtotime($companyFinances['financial_year_start'])) ?> to <?= date('d M Y', strtotime($companyFinances['financial_year_end'])) ?></p>
            <p class="text-sm text-gray-600">Last Updated: <?= date('d M Y H:i', strtotime($companyFinances['last_updated'])) ?></p>
        </div>
    </div>
    
    <!-- Settings Form -->
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <div class="mb-4">
            <label for="opening_balance" class="block text-gray-700 font-medium mb-2">Opening Balance</label>
            <div class="relative">
                <span class="absolute left-3 top-2"><?= CURRENCY ?></span>
                <input type="number" id="opening_balance" name="opening_balance" step="0.01" min="0" class="w-full pl-8 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $companyFinances['opening_balance'] ?>">
            </div>
            <p class="text-sm text-gray-600 mt-1">The initial balance at the start of the financial year.</p>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="financial_year_start" class="block text-gray-700 font-medium mb-2">Financial Year Start</label>
                <input type="date" id="financial_year_start" name="financial_year_start" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $companyFinances['financial_year_start'] ?>">
            </div>
            
            <div>
                <label for="financial_year_end" class="block text-gray-700 font-medium mb-2">Financial Year End</label>
                <input type="date" id="financial_year_end" name="financial_year_end" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= $companyFinances['financial_year_end'] ?>">
            </div>
        </div>
        
        <div class="mb-4">
            <label for="notes" class="block text-gray-700 font-medium mb-2">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= $companyFinances['notes'] ?></textarea>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i> Update Financial Settings
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
        <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
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