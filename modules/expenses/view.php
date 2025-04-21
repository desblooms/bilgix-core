<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No expense specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/expenses/list.php');
}

$expenseId = (int)$_GET['id'];

// Get expense details
$expense = $db->select("SELECT e.*, c.name as categoryName 
                      FROM expenses e 
                      LEFT JOIN expense_categories c ON e.categoryId = c.id 
                      WHERE e.id = :id", ['id' => $expenseId]);

if (empty($expense)) {
    $_SESSION['message'] = "Expense not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/expenses/list.php');
}

$expense = $expense[0];
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Expense Details</h2>
    </div>
    
    <!-- Expense Information -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= $expense['description'] ?></h3>
                <p class="text-sm text-gray-600 mt-1">
                    <span class="bg-gray-200 text-gray-800 text-xs px-2 py-0.5 rounded-full">
                        <?= $expense['categoryName'] ?? 'Uncategorized' ?>
                    </span>
                </p>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-calendar mr-1"></i> <?= date('F d, Y', strtotime($expense['expenseDate'])) ?>
                </p>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-credit-card mr-1"></i> <?= $expense['paymentMethod'] ?>
                </p>
                <?php if (!empty($expense['reference'])): ?>
                <p class="text-sm text-gray-600">
                    <i class="fas fa-hashtag mr-1"></i> Reference: <?= $expense['reference'] ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-red-600"><?= formatCurrency($expense['amount']) ?></p>
                <p class="text-xs text-gray-500">
                    Recorded on <?= date('M d, Y g:i A', strtotime($expense['createdAt'])) ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="grid grid-cols-2 gap-4">
        <a href="edit.php?id=<?= $expenseId ?>" class="bg-red-900 text-white py-2 px-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-edit mr-2"></i> Edit
        </a>
        <a href="#" class="bg-red-500 text-white py-2 px-4 rounded-lg flex items-center justify-center delete-expense" data-id="<?= $expenseId ?>">
            <i class="fas fa-trash mr-2"></i> Delete
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
    <a href="../reports/index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<script>
    // Delete expense confirmation
    const deleteButtons = document.querySelectorAll('.delete-expense');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const expenseId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this expense?')) {
                window.location.href = `delete.php?id=${expenseId}`;
            }
        });
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>