<?php
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No customer specified for deletion!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/customers/list.php');
}

$customerId = (int)$_GET['id'];

// Check if customer has associated sales
$relatedSales = $db->select("SELECT COUNT(*) as count FROM sales WHERE customerId = :id", ['id' => $customerId]);

if ($relatedSales[0]['count'] > 0) {
    $_SESSION['message'] = "This customer has sales records and cannot be deleted!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/customers/list.php');
}

// Get customer details for logging
$customer = $db->select("SELECT * FROM customers WHERE id = :id", ['id' => $customerId]);
if (empty($customer)) {
    $_SESSION['message'] = "Customer not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/customers/list.php');
}

// Delete the customer
$deleted = $db->delete('customers', 'id = :id', ['id' => $customerId]);

if ($deleted) {
    // Log deletion for audit purposes
    $logData = [
        'event' => 'Customer Deleted',
        'customerId' => $customerId,
        'customerName' => $customer[0]['name'],
        'deletedBy' => $_SESSION['user_id'] ?? 'Unknown',
        'deletedAt' => date('Y-m-d H:i:s')
    ];
    
    // You could store this in a system_log table if needed
    // $db->insert('system_log', $logData);
    
    $_SESSION['message'] = "Customer deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to delete customer!";
    $_SESSION['message_type'] = "error";
}

redirect($basePath . 'modules/customers/list.php');
?>