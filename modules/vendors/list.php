<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Get all vendors
$vendors = $db->select("SELECT * FROM vendors ORDER BY name ASC");
?>

<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Vendors</h2>
        <a href="add.php" class="bg-red-900 text-white py-2 px-4 rounded-lg text-sm">
            <i class="fas fa-plus mr-1"></i> Add New
        </a>
    </div>
    
    <!-- Search Bar -->
    <div class="mb-4">
        <div class="relative">
            <input type="text" id="searchInput" class="w-full pl-10 pr-4 py-2 rounded-lg border focus:outline-none focus:ring-2 focus:ring-red-900" placeholder="Search vendors...">
            <div class="absolute left-3 top-2.5 text-gray-400">
                <i class="fas fa-search"></i>
            </div>
        </div>
    </div>
    
    <!-- Vendor List -->
    <?php if (count($vendors) > 0): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <ul class="divide-y" id="vendorList">
            <?php foreach($vendors as $vendor): ?>
            <li class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= $vendor['name'] ?></p>
                        <p class="text-sm text-gray-600">
                            <?= $vendor['contactPerson'] ? $vendor['contactPerson'] . ' • ' : '' ?>
                            <?= $vendor['phone'] ?>
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="edit.php?id=<?= $vendor['id'] ?>" class="text-slate-950">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="#" class="text-red-600 delete-vendor" data-id="<?= $vendor['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <p class="text-gray-500 mb-4">No vendors found</p>
        <a href="add.php" class="inline-block bg-red-900 text-white py-2 px-6 rounded-lg">Add Your First Vendor</a>
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
    const vendorList = document.getElementById('vendorList');
    const vendorItems = vendorList ? Array.from(vendorList.getElementsByTagName('li')) : [];
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        vendorItems.forEach(item => {
            const vendorName = item.querySelector('.font-medium').textContent.toLowerCase();
            const vendorDetails = item.querySelector('.text-gray-600').textContent.toLowerCase();
            
            if (vendorName.includes(searchTerm) || vendorDetails.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Delete vendor confirmation
    const deleteButtons = document.querySelectorAll('.delete-vendor');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const vendorId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this vendor?')) {
                window.location.href = `delete.php?id=${vendorId}`;
            }
        });
    });
</script>

<?php
// Close the main div and add the footer

// Include footer (which contains ob_end_flush())
include $basePath . 'includes/footer.php';
?>