<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get all expenses with category names
$expenses = $db->select("SELECT e.*, c.name as categoryName 
                       FROM expenses e 
                       LEFT JOIN expense_categories c ON e.categoryId = c.id 
                       ORDER BY e.expenseDate DESC");

// Get expense categories for filter dropdown
$categories = $db->select("SELECT * FROM expense_categories ORDER BY name ASC");
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Expenses</h2>
        <a href="add.php" class="bg-red-900 text-white py-2 px-4 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> Add New
        </a>
    </div>
    
    <!-- Filter Options -->
    <div class="bg-white rounded-lg shadow mb-4 p-3">
        <div class="flex items-center text-sm overflow-x-auto no-scrollbar">
            <span class="mr-2">Filter:</span>
            <button class="px-3 py-1 bg-red-900 text-white rounded-lg mr-2">All</button>
            <button class="px-3 py-1 bg-gray-200 rounded-lg mr-2">Today</button>
            <button class="px-3 py-1 bg-gray-200 rounded-lg mr-2">This Week</button>
            <button class="px-3 py-1 bg-gray-200 rounded-lg mr-2">This Month</button>
            <select id="categoryFilter" class="px-3 py-1 bg-gray-200 rounded-lg">
                <option value="">All Categories</option>
                <?php foreach($categories as $category): ?>
                <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative">
            <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="Search expenses...">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>
    
    <!-- Expenses List -->
    <?php if (count($expenses) > 0): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y" id="expensesList">
            <?php foreach($expenses as $expense): ?>
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
                        <p class="text-xs text-gray-600"><?= $expense['paymentMethod'] ?></p>
                    </div>
                </div>
                <div class="flex mt-2 space-x-2 justify-end">
                    <a href="view.php?id=<?= $expense['id'] ?>" class="text-sm text-slate-950">
                        <i class="fas fa-eye mr-1"></i> View
                    </a>
                    <a href="edit.php?id=<?= $expense['id'] ?>" class="text-sm text-gray-600">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <a href="#" class="text-sm text-red-600 delete-expense" data-id="<?= $expense['id'] ?>">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500 mb-4">No expenses found</p>
        <a href="add.php" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">Record Your First Expense</a>
    </div>
    <?php endif; ?>
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
    
    // Category filter functionality
    const categoryFilter = document.getElementById('categoryFilter');
    
    categoryFilter.addEventListener('change', function() {
        const categoryId = this.value;
        
        expensesItems.forEach(item => {
            // If no category selected, show all
            if (!categoryId) {
                item.style.display = '';
                return;
            }
            
            // Get the category element and extract its text
            const categorySpan = item.querySelector('.bg-gray-200');
            const categoryName = categorySpan ? categorySpan.textContent.trim() : '';
            
            // Get corresponding option text for comparison
            const selectedOption = this.options[this.selectedIndex];
            const selectedCategoryName = selectedOption ? selectedOption.textContent.trim() : '';
            
            if (categoryName === selectedCategoryName) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
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
    
    // Filter functionality (for demonstration)
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.bg-gray-200, .bg-red-900');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Reset all buttons
                filterButtons.forEach(btn => {
                    if (btn.tagName === 'BUTTON') {
                        btn.classList.remove('bg-red-900');
                        btn.classList.remove('text-white');
                        btn.classList.add('bg-gray-200');
                    }
                });
                
                // Highlight selected button
                if (this.tagName === 'BUTTON') {
                    this.classList.remove('bg-gray-200');
                    this.classList.add('bg-red-900');
                    this.classList.add('text-white');
                }
                
                // Reset category filter when filter buttons are clicked
                categoryFilter.value = '';
                
                // Here you would implement actual filtering based on selection
                // For now, just a visual change
            });
        });
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>