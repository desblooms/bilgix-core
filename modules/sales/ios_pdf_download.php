<?php
/**
 * iOS PDF Download Helper
 * 
 * This script specifically handles PDF downloads for iOS devices
 * by creating a zip file containing the PDF which forces iOS to download
 * rather than open in the browser.
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

// Get sale details
$sale = $db->select("SELECT * FROM sales WHERE id = :id", ['id' => $saleId]);

if (empty($sale)) {
    die("Sale not found!");
}

$sale = $sale[0];

// Generate PDF filename
$pdfFilename = 'Invoice_' . $sale['invoiceNumber'] . '.pdf';
$zipFilename = 'Invoice_' . $sale['invoiceNumber'] . '.zip';

// Create temporary file paths
$tempPdfPath = tempnam(sys_get_temp_dir(), 'pdf_');
$tempZipPath = tempnam(sys_get_temp_dir(), 'zip_');

// Generate the PDF content
ob_start();
include('generate_pdf_invoice.php');
$pdfContent = ob_get_clean();

// Write PDF content to temporary file
file_put_contents($tempPdfPath, $pdfContent);

// Create a ZIP archive containing the PDF
$zip = new ZipArchive();
if ($zip->open($tempZipPath, ZipArchive::CREATE) !== TRUE) {
    die("Cannot create ZIP archive");
}

$zip->addFile($tempPdfPath, $pdfFilename);
$zip->close();

// Read the ZIP content
$zipContent = file_get_contents($tempZipPath);

// Delete temporary files
@unlink($tempPdfPath);
@unlink($tempZipPath);

// Set headers for ZIP download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . strlen($zipContent));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Output ZIP content
echo $zipContent;
exit;
?>