<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get sales with customer info
$sales = $db->select("SELECT s.*, c.name as customerName 
                      FROM sales s 
                      LEFT JOIN customers c ON s.customerId = c.id 
                      ORDER BY s.createdAt DESC");
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Sales</h2>
        <a href="add.php" class="bg-red-900 text-white py-2 px-4 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> New Sale
        </a>
    </div>
    
    <!-- Filter Options -->
    <div class="bg-white rounded-lg shadow mb-4 p-3">
        <div class="flex items-center text-sm overflow-x-auto no-scrollbar">
            <span class="mr-2">Filter:</span>
            <button class="px-3 py-1 bg-red-900 text-white rounded-lg mr-2">All</button>
            <button class="px-3 py-1 bg-gray-200 rounded-lg mr-2">Today</button>
            <button class="px-3 py-1 bg-gray-200 rounded-lg mr-2">This Week</button>
            <button class="px-3 py-1 bg-gray-200 rounded-lg">This Month</button>
        </div>
    </div>
    
    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative">
            <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="Search invoice or customer...">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>
    
    <!-- Sales List -->
    <?php if (count($sales) > 0): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y" id="salesList">
            <?php foreach($sales as $sale): ?>
            <li class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= $sale['invoiceNumber'] ?></p>
                        <p class="text-sm text-gray-600">
                            <?= $sale['customerName'] ?? 'Walk-in Customer' ?> • 
                            <?= date('M d, Y g:i A', strtotime($sale['createdAt'])) ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600"><?= formatCurrency($sale['totalPrice']) ?></p>
                        <span class="text-xs px-2 py-1 rounded-full <?= getPaymentStatusClass($sale['paymentStatus']) ?>">
                            <?= $sale['paymentStatus'] ?>
                        </span>
                    </div>
                </div>
                <div class="flex mt-2 space-x-2 justify-end">
                    <a href="view.php?id=<?= $sale['id'] ?>" class="text-sm text-slate-950">
                        <i class="fas fa-eye mr-1"></i> View
                    </a>
                    <a href="edit.php?id=<?= $sale['id'] ?>" class="text-sm text-gray-600">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <a href="#" class="text-sm text-red-600 delete-sale" data-id="<?= $sale['id'] ?>">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500 mb-4">No sales found</p>
        <a href="add.php" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">Create Your First Sale</a>
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
    <a href="../sales/add.php" class="flex flex-col items-center p-2 text-slate-950">
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
    const salesList = document.getElementById('salesList');
    const salesItems = salesList ? Array.from(salesList.getElementsByTagName('li')) : [];
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        salesItems.forEach(item => {
            const invoiceNumber = item.querySelector('.font-medium').textContent.toLowerCase();
            const customerInfo = item.querySelector('.text-gray-600').textContent.toLowerCase();
            
            if (invoiceNumber.includes(searchTerm) || customerInfo.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Delete sale confirmation
    const deleteButtons = document.querySelectorAll('.delete-sale');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const saleId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this sale? This will return items to inventory.')) {
                window.location.href = `delete.php?id=${saleId}`;
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
                    btn.classList.remove('bg-red-900');
                    btn.classList.remove('text-white');
                    btn.classList.add('bg-gray-200');
                });
                
                // Highlight selected button
                this.classList.remove('bg-gray-200');
                this.classList.add('bg-red-900');
                this.classList.add('text-white');
                
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