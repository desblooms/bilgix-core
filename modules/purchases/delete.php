<?php
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php';
include_once $basePath . 'includes/finance_handler.php';
// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No purchase specified for deletion!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/purchases/list.php');
}

$purchaseId = (int)$_GET['id'];

// Get purchase details
$purchase = $db->select("SELECT * FROM purchases WHERE id = :id", ['id' => $purchaseId]);

if (empty($purchase)) {
    $_SESSION['message'] = "Purchase not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/purchases/list.php');
}

// Get purchase items
$purchaseItems = $db->select("SELECT * FROM purchase_items WHERE purchaseId = :purchaseId", ['purchaseId' => $purchaseId]);

// Start a transaction
$db->getConnection()->beginTransaction();

try {
    // Return items from inventory (remove items added by this purchase)
    foreach ($purchaseItems as $item) {
        // Get current product details
        $product = getProduct($item['productId']);
        
        if ($product) {
            // Calculate new quantity (remove purchased quantity)
            $newQty = $product['qty'] - $item['quantity'];
            if ($newQty < 0) $newQty = 0; // Prevent negative inventory


             // Record financial transaction to reverse the purchase
    $description = "Purchase #" . $purchase[0]['purchaseNumber'] . " cancelled";
    recordFinancialTransaction(
        'adjustment',
        'purchase',
        $purchaseId,
        $purchase[0]['totalAmount'], // Positive amount to reverse the purchase expense
        $description,
        $_SESSION['user_id'] ?? null
    );
            // Update product quantity
            $db->update('products', 
                       ['qty' => $newQty, 'updatedAt' => date('Y-m-d H:i:s')], 
                       'id = :id', 
                       ['id' => $product['id']]);
            
            // Log inventory change
            $logData = [
                'productId' => $product['id'],
                'adjustmentType' => 'remove',
                'quantity' => $item['quantity'],
                'previousQty' => $product['qty'],
                'newQty' => $newQty,
                'reason' => 'Purchase Cancelled',
                'userId' => $_SESSION['user_id'] ?? 1,
                'createdAt' => date('Y-m-d H:i:s')
            ];
            
            $db->insert('inventory_log', $logData);
        }
    }
    
    // Delete purchase items first (due to foreign key constraint)
    $db->delete('purchase_items', 'purchaseId = :purchaseId', ['purchaseId' => $purchaseId]);
    
    // Delete the purchase
    $db->delete('purchases', 'id = :id', ['id' => $purchaseId]);
    
    // Commit transaction
    $db->getConnection()->commit();
    
    $_SESSION['message'] = "Purchase deleted successfully and inventory adjusted!";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    // Rollback transaction on error
    $db->getConnection()->rollBack();
    
    $_SESSION['message'] = "Failed to delete purchase: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

redirect($basePath . 'modules/purchases/list.php');

// Include footer (never reached due to redirect, but included for consistency)
include $basePath . 'includes/footer.php';
?>