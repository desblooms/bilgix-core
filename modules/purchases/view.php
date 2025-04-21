<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No purchase specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/purchases/list.php');
}

$purchaseId = (int)$_GET['id'];

// Get purchase details
$purchase = $db->select("SELECT p.*, v.name as vendorName, v.phone as vendorPhone, v.email as vendorEmail, 
                       v.contactPerson, v.address as vendorAddress
                       FROM purchases p 
                       LEFT JOIN vendors v ON p.vendorId = v.id 
                       WHERE p.id = :id", ['id' => $purchaseId]);

if (empty($purchase)) {
    $_SESSION['message'] = "Purchase not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/purchases/list.php');
}

$purchase = $purchase[0];

// Get purchase items
$purchaseItems = $db->select("SELECT pi.*, p.itemName, p.itemCode, p.unitType 
                            FROM purchase_items pi 
                            JOIN products p ON pi.productId = p.id 
                            WHERE pi.purchaseId = :purchaseId", 
                            ['purchaseId' => $purchaseId]);
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="list.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Purchase Details</h2>
    </div>
    
    <!-- Purchase Header -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= $purchase['purchaseNumber'] ?></h3>
                <p class="text-sm text-gray-600"><?= date('F d, Y h:i A', strtotime($purchase['createdAt'])) ?></p>
            </div>
            <div class="text-right">
                <span class="text-xs px-2 py-1 rounded-full <?= getPaymentStatusClass($purchase['paymentStatus']) ?>">
                    <?= $purchase['paymentStatus'] ?>
                </span>
                <p class="text-2xl font-bold text-slate-950 mt-1"><?= formatCurrency($purchase['totalAmount']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Vendor Info -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-md font-medium text-gray-800 mb-2">Vendor Information</h3>
        <p class="font-medium"><?= $purchase['vendorName'] ?? 'Unknown Vendor' ?></p>
        <?php if (!empty($purchase['contactPerson'])): ?>
            <p class="text-sm text-gray-600"><i class="fas fa-user mr-1"></i> <?= $purchase['contactPerson'] ?></p>
        <?php endif; ?>
        <?php if (!empty($purchase['vendorPhone'])): ?>
            <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1"></i> <?= $purchase['vendorPhone'] ?></p>
        <?php endif; ?>
        <?php if (!empty($purchase['vendorEmail'])): ?>
            <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-1"></i> <?= $purchase['vendorEmail'] ?></p>
        <?php endif; ?>
        <?php if (!empty($purchase['vendorAddress'])): ?>
            <p class="text-sm text-gray-600 mt-1"><i class="fas fa-map-marker-alt mr-1"></i> <?= $purchase['vendorAddress'] ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Items Purchased -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Items Purchased</h3>
        </div>
        
        <ul class="divide-y">
            <?php foreach($purchaseItems as $item): ?>
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
                <span><?= formatCurrency($purchase['totalAmount']) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="grid grid-cols-2 gap-4">
        <button id="printButton" class="bg-red-900 text-white py-2 px-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-print mr-2"></i> Print Purchase Order
        </button>
        <a href="edit.php?id=<?= $purchaseId ?>" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-lg flex items-center justify-center">
            <i class="fas fa-edit mr-2"></i> Edit
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
    // Print functionality
    document.getElementById('printButton').addEventListener('click', function() {
        window.print();
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>