<?php
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No product specified for deletion!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/products/list.php');
}

$productId = (int)$_GET['id'];

// Check if product exists
$product = getProduct($productId);
if (!$product) {
    $_SESSION['message'] = "Product not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/products/list.php');
}

// Check if product is used in sales or purchases
$salesCheck = $db->select("SELECT COUNT(*) as count FROM sale_items WHERE productId = :id", ['id' => $productId]);
$purchasesCheck = $db->select("SELECT COUNT(*) as count FROM purchase_items WHERE productId = :id", ['id' => $productId]);

if ($salesCheck[0]['count'] > 0 || $purchasesCheck[0]['count'] > 0) {
    $_SESSION['message'] = "This product cannot be deleted because it has associated sales or purchase records.";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/products/list.php');
}

// Start transaction
$db->getConnection()->beginTransaction();

try {
    // Delete inventory logs for this product
    $db->delete('inventory_log', 'productId = :id', ['id' => $productId]);
    
    // Delete the product
    $deleted = $db->delete('products', 'id = :id', ['id' => $productId]);
    
    // Commit transaction
    $db->getConnection()->commit();
    
    if ($deleted) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete product!";
        $_SESSION['message_type'] = "error";
    }
} catch (Exception $e) {
    // Rollback transaction on error
    $db->getConnection()->rollBack();
    
    $_SESSION['message'] = "Error deleting product: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

redirect($basePath . 'modules/products/list.php');

// Include footer (this won't be reached due to the redirect, but included for consistency)
include $basePath . 'includes/footer.php';
?>