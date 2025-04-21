






<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php'; // Adjusted path

use Twilio\Rest\Client;

include_once '../../includes/db.php'; // your DB connection
include_once '../../includes/config.php'; // optional

// Get the sale ID
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing sale ID']);
    exit;
}

$saleId = (int) $_GET['id'];

// Fetch sale + customer phone
$sale = $db->select("SELECT s.*, c.name as customerName, c.phone as customerPhone 
                     FROM sales s 
                     LEFT JOIN customers c ON s.customerId = c.id 
                     WHERE s.id = :id", ['id' => $saleId]);

if (empty($sale)) {
    echo json_encode(['success' => false, 'error' => 'Sale not found']);
    exit;
}

$sale = $sale[0];

// Twilio credentials (use env or config securely)
$sid    = 'AC38a6a38a7d0171b3f66d33c26f9f9879';
$token  = '3ef574170e77450077a3b97db1748678';
$from   = '+16503183253';
$to     = $sale['customerPhone'];
$invoiceUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/print_invoice.php?id=' . $saleId;

// Compose message
$message = "Hi " . $sale['customerName'] . ",\nHere is your invoice:\n" . $invoiceUrl;

try {
    $twilio = new Client($sid, $token);
    $twilio->messages->create($to, [
        'from' => $from,
        'body' => $message
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
