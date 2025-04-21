<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No sale specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/sales/list.php');
}

$saleId = (int)$_GET['id'];

// Get sale details
$sale = $db->select("SELECT s.*, c.name as customerName, c.phone as customerPhone, c.email as customerEmail 
                    FROM sales s 
                    LEFT JOIN customers c ON s.customerId = c.id 
                    WHERE s.id = :id", ['id' => $saleId]);

if (empty($sale)) {
    $_SESSION['message'] = "Sale not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/sales/list.php');
}

$sale = $sale[0];

// Get sale items
$saleItems = $db->select("SELECT si.*, p.itemName, p.itemCode, p.unitType 
                         FROM sale_items si 
                         JOIN products p ON si.productId = p.id 
                         WHERE si.saleId = :saleId", 
                         ['saleId' => $saleId]);
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Sale Details</h2>
    </div>
    
    <!-- Invoice Header -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= $sale['invoiceNumber'] ?></h3>
                <p class="text-sm text-gray-600"><?= date('F d, Y h:i A', strtotime($sale['createdAt'])) ?></p>
                <p class="text-sm text-gray-600 mt-1">Payment: <?= $sale['paymentMethod'] ?></p>
            </div>
            <div class="text-right">
                <span class="text-xs px-2 py-1 rounded-full <?= getPaymentStatusClass($sale['paymentStatus']) ?>">
                    <?= $sale['paymentStatus'] ?>
                </span>
                <p class="text-2xl font-bold text-green-600 mt-1"><?= formatCurrency($sale['totalPrice']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Customer Info -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-md font-medium text-gray-800 mb-2">Customer Information</h3>
        <?php if (!empty($sale['customerId'])): ?>
            <p class="font-medium"><?= $sale['customerName'] ?></p>
            <?php if (!empty($sale['customerPhone'])): ?>
                <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i> <?= $sale['customerPhone'] ?></p>
            <?php endif; ?>
            <?php if (!empty($sale['customerEmail'])): ?>
                <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-1"></i> <?= $sale['customerEmail'] ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-gray-600">Walk-in Customer</p>
        <?php endif; ?>
    </div>
    
    <!-- Items Purchased -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Items Purchased</h3>
        </div>
        
        <ul class="divide-y">
            <?php foreach($saleItems as $item): ?>
            <li class="p-4">
                <div class="flex justify-between">
                    <div>
                        <p class="font-medium"><?= $item['itemName'] ?></p>
                        <p class="text-sm text-gray-600">Code: <?= $item['itemCode'] ?></p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold"><?= formatCurrency($item['total']) ?></p>
                        <p class="text-sm text-gray-600">
                            <?= $item['quantity'] ?> <?= $item['unitType'] ?> × <?= formatCurrency($item['price']) ?>
                        </p>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <div class="p-4 border-t bg-gray-50">
            <div class="flex justify-between font-bold">
                <span>Total:</span>
                <span><?= formatCurrency($sale['totalPrice']) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        <a href="invoice_options.php?id=<?= $saleId ?>" class="bg-purple-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-file-invoice mr-2"></i> Invoice Options
        </a>
        <button id="printButton" class="bg-red-900 text-white py-3 px-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-print mr-2"></i> Print This Page
        </button>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <a href="edit.php?id=<?= $saleId ?>" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-edit mr-2"></i> Edit
        </a>
        <a href="#" class="bg-red-500 text-white py-2 px-4 rounded-lg flex items-center justify-center delete-sale" data-id="<?= $saleId ?>">
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
    // Print functionality
    document.getElementById('printButton').addEventListener('click', function() {
        window.print();
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
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>