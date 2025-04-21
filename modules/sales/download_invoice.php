<?php
/**
 * Direct Download Script for PDF Invoices
 * 
 * This script is specifically designed to help with downloads on mobile devices
 * that may have issues with standard PDF download methods.
 */

// Adjust path for includes
$basePath = '../../';
require_once $basePath . 'includes/functions.php';
require_once $basePath . 'includes/db.php';
require_once $basePath . 'includes/config.php';

// Initialize database connection
$db = new Database();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No sale specified!");
}

$saleId = (int)$_GET['id'];

// Get sale details to get the invoice number
$sale = $db->select("SELECT invoiceNumber FROM sales WHERE id = :id", ['id' => $saleId]);

if (empty($sale)) {
    die("Sale not found!");
}

// Create PDF filename
$filename = 'Invoice_' . $sale[0]['invoiceNumber'] . '.pdf';

// Path to generate_pdf_invoice.php
$pdfGeneratorPath = __DIR__ . '/generate_pdf_invoice.php';

// Set headers for forced download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Description: File Transfer');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Start output buffering
ob_start();

// Include the PDF generator - because we've already set download headers,
// this should force the PDF to download rather than display
include($pdfGeneratorPath);

// Capture and clean any output
$output = ob_get_clean();

// Output the PDF data
echo $output;
exit;
?>