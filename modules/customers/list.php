<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get all customers
$customers = $db->select("SELECT * FROM customers ORDER BY name ASC");
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Customers</h2>
        <a href="add.php" class="bg-red-900 text-white py-2 px-4 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> Add New
        </a>
    </div>
    
    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative">
            <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Search customers...">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>
    
    <!-- Customer List -->
    <?php if (count($customers) > 0): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y" id="customerList">
            <?php foreach($customers as $customer): ?>
            <li class="p-4">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium"><?= $customer['name'] ?></p>
                        <p class="text-sm text-gray-600"><?= $customer['phone'] ?></p>
                        <?php if (!empty($customer['gstNumber'])): ?>
                        <p class="text-sm text-gray-600">GSTIN: <?= $customer['gstNumber'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <?php if ($customer['openingBalance'] > 0): ?>
                            <p class="font-bold <?= $customer['balanceType'] == 'Advance' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $customer['balanceType'] == 'Advance' ? '+' : '-' ?><?= formatCurrency($customer['openingBalance']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="flex space-x-3 mt-1">
                            <a href="view.php?id=<?= $customer['id'] ?>" class="text-red-900">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?= $customer['id'] ?>" class="text-slate-950">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="text-red-600 delete-customer" data-id="<?= $customer['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500 mb-4">No customers found</p>
        <a href="add.php" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">Add Your First Customer</a>
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
    <a href="../customers/list.php" class="flex flex-col items-center p-2 text-slate-950">
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
    const customerList = document.getElementById('customerList');
    const customerItems = customerList ? Array.from(customerList.getElementsByTagName('li')) : [];
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        customerItems.forEach(item => {
            const customerInfo = item.textContent.toLowerCase();
            
            if (customerInfo.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Delete customer confirmation
    const deleteButtons = document.querySelectorAll('.delete-customer');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const customerId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this customer?')) {
                window.location.href = `delete.php?id=${customerId}`;
            }
        });
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>