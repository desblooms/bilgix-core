<?php
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No vendor specified for deletion!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/vendors/list.php');
}

$vendorId = (int)$_GET['id'];

// Check if vendor has associated purchases
$relatedPurchases = $db->select("SELECT COUNT(*) as count FROM purchases WHERE vendorId = :id", ['id' => $vendorId]);

if ($relatedPurchases[0]['count'] > 0) {
    $_SESSION['message'] = "This vendor has purchase records and cannot be deleted!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/vendors/list.php');
}

// Delete the vendor
$deleted = $db->delete('vendors', 'id = :id', ['id' => $vendorId]);

if ($deleted) {
    $_SESSION['message'] = "Vendor deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to delete vendor!";
    $_SESSION['message_type'] = "error";
}

redirect($basePath . 'modules/vendors/list.php');
?>