<?php 
// modules/finances/add_transaction.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 
include $basePath . 'includes/finance_handler.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $transactionType = sanitize($_POST['transaction_type']);
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description']);
    $transactionDate = sanitize($_POST['transaction_date']);
    
    // Validation
    $errors = [];
    
    if (!in_array($transactionType, ['income', 'expense', 'adjustment'])) {
        $errors[] = "Invalid transaction type";
    }
    
    if (empty($amount) || $amount == 0) {
        $errors[] = "Amount is required and cannot be zero";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($transactionDate)) {
        $errors[] = "Transaction date is required";
    }
    
    if ($transactionType == 'expense' && $amount > 0) {
        // Convert expense to negative amount
        $amount = -$amount;
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Record the transaction
        $result = recordFinancialTransaction(
            $transactionType,
            'manual',
            0, // No specific reference ID for manual transactions
            $amount,
            $description,
            $_SESSION['user_id'] ?? null
        );
        
        if ($result) {
            $_SESSION['message'] = "Transaction recorded successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/reports/financial_transactions.php');
        } else {
            $errors[] = "Failed to record transaction. Please try again.";
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="../reports/financial_transactions.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Add Manual Transaction</h2>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <ul class="list-disc list-inside">
            <?php foreach($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="bg-white rounded-lg shadow p-4">
        <div class="mb-4">
            <label for="transaction_type" class="block text-gray-700 font-medium mb-2">Transaction Type *</label>
            <select id="transaction_type" name="transaction_type" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="income" <?= isset($_POST['transaction_type']) && $_POST['transaction_type'] == 'income' ? 'selected' : '' ?>>Income</option>
                <option value="expense" <?= isset($_POST['transaction_type']) && $_POST['transaction_type'] == 'expense' ? 'selected' : '' ?>>Expense</option>
                <option value="adjustment" <?= isset($_POST['transaction_type']) && $_POST['transaction_type'] == 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
            </select>
            <p class="text-sm text-gray-600 mt-1" id="type-help-text">Record additional income not from sales</p>
        </div>
        
        <div class="mb-4">
            <label for="amount" class="block text-gray-700 font-medium mb-2">Amount *</label>
            <div class="relative">
                <span class="absolute left-3 top-2"><?= CURRENCY ?></span>
                <input type="number" id="amount" name="amount" step="0.01" class="w-full pl-8 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required value="<?= isset($_POST['amount']) ? abs($_POST['amount']) : '' ?>">
            </div>
            <p class="text-sm text-gray-600 mt-1" id="amount-help-text">Enter the transaction amount</p>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-medium mb-2">Description *</label>
            <textarea id="description" name="description" rows="3" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required><?= isset($_POST['description']) ? $_POST['description'] : '' ?></textarea>
            <p class="text-sm text-gray-600 mt-1">Provide details about this transaction</p>
        </div>
        
        <div class="mb-4">
            <label for="transaction_date" class="block text-gray-700 font-medium mb-2">Transaction Date *</label>
            <input type="date" id="transaction_date" name="transaction_date" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required value="<?= isset($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d') ?>">
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i> Record Transaction
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

<script>
    // Dynamic help text based on transaction type
    document.getElementById('transaction_type').addEventListener('change', function() {
        const typeHelpText = document.getElementById('type-help-text');
        const amountHelpText = document.getElementById('amount-help-text');
        
        switch(this.value) {
            case 'income':
                typeHelpText.textContent = 'Record additional income not from sales';
                amountHelpText.textContent = 'Enter the income amount (positive)';
                break;
            case 'expense':
                typeHelpText.textContent = 'Record additional expenses not from purchases';
                amountHelpText.textContent = 'Enter the expense amount (will be recorded as negative)';
                break;
            case 'adjustment':
                typeHelpText.textContent = 'Make corrections to your financial records';
                amountHelpText.textContent = 'Enter positive amount to increase balance, negative to decrease';
                break;
        }
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>