<?php
/**
 * TCPDF Installer Script
 * 
 * This script helps download and install TCPDF library for the invoice system.
 */

// Set appropriate error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Display header
echo "===========================================\n";
echo "TCPDF Installer for Inventory Management\n";
echo "===========================================\n\n";

// Check if we're running from the command line or browser
$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    echo "<pre>";
}

// Define paths
$basePath = dirname(__DIR__) . '/';
$vendorPath = $basePath . 'vendor';
$tcpdfPath = $vendorPath . '/tcpdf';

echo "Base path: " . $basePath . "\n";
echo "Checking environment...\n";

// Check if vendor directory exists, create if not
if (!is_dir($vendorPath)) {
    echo "Creating vendor directory...\n";
    if (!mkdir($vendorPath, 0755, true)) {
        die("ERROR: Failed to create vendor directory. Please check permissions.\n");
    }
}

// Check if TCPDF is already installed
if (is_dir($tcpdfPath) && file_exists($tcpdfPath . '/tcpdf.php')) {
    echo "TCPDF is already installed!\n";
    echo "Location: " . $tcpdfPath . "\n";
    echo "You can now use the PDF invoice feature.\n";
    
    // Exit success
    if (!$isCLI) {
        echo "</pre>";
        echo "<p style='color:green;font-weight:bold;'>TCPDF is already installed and ready to use.</p>";
    }
    exit(0);
}

// Try to download TCPDF
echo "TCPDF not found. Attempting to download...\n";

// Check for required extensions
if (!extension_loaded('zip')) {
    die("ERROR: The ZIP extension is required but not enabled on this PHP installation.\n");
}

if (!function_exists('curl_init')) {
    die("ERROR: The cURL extension is required but not enabled on this PHP installation.\n");
}

// Define TCPDF download URL
$downloadUrl = 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.2.zip';
$zipFile = $vendorPath . '/tcpdf.zip';

// Download the file
echo "Downloading TCPDF from {$downloadUrl}...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $downloadUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($ch);

if (curl_errno($ch)) {
    die("ERROR: Failed to download TCPDF: " . curl_error($ch) . "\n");
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($httpCode != 200) {
    die("ERROR: Failed to download TCPDF. HTTP code: " . $httpCode . "\n");
}

curl_close($ch);

// Save the file
file_put_contents($zipFile, $data);
echo "Download completed!\n";

// Extract the ZIP file
echo "Extracting TCPDF...\n";
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($vendorPath);
    $zip->close();
    
    // Rename the folder to 'tcpdf'
    $extractedDir = $vendorPath . '/TCPDF-6.6.2';
    if (is_dir($extractedDir)) {
        rename($extractedDir, $tcpdfPath);
    }
    
    // Delete the ZIP file
    unlink($zipFile);
    
    echo "TCPDF installed successfully!\n";
} else {
    die("ERROR: Failed to extract TCPDF. The ZIP file may be corrupted.\n");
}

// Verify installation
if (is_dir($tcpdfPath) && file_exists($tcpdfPath . '/tcpdf.php')) {
    echo "\nTCPDF has been successfully installed.\n";
    echo "Location: " . $tcpdfPath . "\n";
    echo "You can now use the PDF invoice feature.\n";
} else {
    echo "\nWARNING: TCPDF installation may have completed but the expected files were not found.\n";
    echo "Please verify the installation manually or try again.\n";
}

// Show browser-friendly success message
if (!$isCLI) {
    echo "</pre>";
    echo "<p style='color:green;font-weight:bold;'>TCPDF has been successfully installed. You can now use the PDF invoice feature.</p>";
    echo "<p><a href='../index.php' style='color:blue;'>Return to Dashboard</a></p>";
}
?>