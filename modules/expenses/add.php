<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 
include_once $basePath . 'includes/finance_handler.php';
// Get all expense categories for dropdown
$categories = $db->select("SELECT * FROM expense_categories ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = !empty($_POST['categoryId']) ? intval($_POST['categoryId']) : null;
    $amount = floatval($_POST['amount']);
    $description = sanitize($_POST['description']);
    $expenseDate = sanitize($_POST['expenseDate']);
    $paymentMethod = sanitize($_POST['paymentMethod']);
    $reference = sanitize($_POST['reference']);
    
    // Validation
    $errors = [];
    if (empty($amount) || $amount <= 0) $errors[] = "Amount is required and must be greater than zero";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($expenseDate)) $errors[] = "Date is required";
    
    // If no errors, insert into database
    if (empty($errors)) {
        $data = [
            'categoryId' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'expenseDate' => $expenseDate,
            'paymentMethod' => $paymentMethod,
            'reference' => $reference,
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        $expenseId = $db->insert('expenses', $data);
        
        if ($expenseId) {
            // Redirect to expense list with success message
            $_SESSION['message'] = "Expense added successfully!";
            $_SESSION['message_type'] = "success";
            redirect($basePath . 'modules/expenses/list.php');
        } else {
            $errors[] = "Failed to add expense. Please try again.";
        }
    }
 // Record financial transaction (negative amount for expenses)
 $categoryName = "";
 if (!empty($categoryId)) {
     foreach($categories as $cat) {
         if ($cat['id'] == $categoryId) {
             $categoryName = $cat['name'];
             break;
         }
     }
 }
 
 $transactionDesc = "Expense: " . $description;
 if (!empty($categoryName)) {
     $transactionDesc .= " (Category: " . $categoryName . ")";
 }
 
 recordFinancialTransaction(
     'expense',
     'expense',
     $expenseId,
     -$amount, // Negative amount for expenses
     $transactionDesc,
     $_SESSION['user_id'] ?? null
 );
    
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Add New Expense</h2>
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
            <label for="categoryId" class="block text-gray-700 font-medium mb-2">Category</label>
            <select id="categoryId" name="categoryId" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                <option value="">-- Select Category --</option>
                <?php foreach($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= isset($_POST['categoryId']) && $_POST['categoryId'] == $category['id'] ? 'selected' : '' ?>>
                        <?= $category['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-4">
            <label for="amount" class="block text-gray-700 font-medium mb-2">Amount *</label>
            <div class="relative">
                <span class="absolute left-3 top-2"><?= CURRENCY ?></span>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" class="w-full pl-8 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['amount']) ? $_POST['amount'] : '' ?>">
            </div>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 font-medium mb-2">Description *</label>
            <input type="text" id="description" name="description" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['description']) ? $_POST['description'] : '' ?>">
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label for="expenseDate" class="block text-gray-700 font-medium mb-2">Date *</label>
                <input type="date" id="expenseDate" name="expenseDate" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= isset($_POST['expenseDate']) ? $_POST['expenseDate'] : date('Y-m-d') ?>">
            </div>
            
            <div>
                <label for="paymentMethod" class="block text-gray-700 font-medium mb-2">Payment Method</label>
                <select id="paymentMethod" name="paymentMethod" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                    <option value="Cash" <?= isset($_POST['paymentMethod']) && $_POST['paymentMethod'] == 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="Card" <?= isset($_POST['paymentMethod']) && $_POST['paymentMethod'] == 'Card' ? 'selected' : '' ?>>Card</option>
                    <option value="Bank Transfer" <?= isset($_POST['paymentMethod']) && $_POST['paymentMethod'] == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="UPI" <?= isset($_POST['paymentMethod']) && $_POST['paymentMethod'] == 'UPI' ? 'selected' : '' ?>>UPI</option>
                    <option value="Check" <?= isset($_POST['paymentMethod']) && $_POST['paymentMethod'] == 'Check' ? 'selected' : '' ?>>Check</option>
                </select>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="reference" class="block text-gray-700 font-medium mb-2">Reference Number (optional)</label>
            <input type="text" id="reference" name="reference" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= isset($_POST['reference']) ? $_POST['reference'] : '' ?>">
            <p class="text-sm text-gray-600 mt-1">e.g., Receipt number, invoice number, etc.</p>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                <i class="fas fa-save mr-2"></i> Save Expense
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
        <div class="bg-red-900 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
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