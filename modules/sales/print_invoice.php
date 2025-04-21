<?php
// print_invoice.php - Clean invoice for printing

// Adjust path for includes
$basePath = '../../';
require_once $basePath . 'includes/functions.php';
require_once $basePath . 'includes/db.php';
require_once $basePath . 'includes/config.php';

// Initialize database connection
$db = new Database();

// Load invoice settings
$invoiceSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'invoice'");
$settings = [];

// Convert array of settings to associative array for easier access
foreach ($invoiceSettings as $setting) {
    $settings[$setting['settingKey']] = $setting['settingValue'];
}

// Set defaults for missing settings
$defaults = [
    'invoice_prefix' => 'INV-',
    'invoice_footer_text' => 'Thank you for your business!',
    'invoice_terms' => "1. Goods once sold will not be taken back or exchanged.\n2. All disputes are subject to local jurisdiction.\n3. E. & O.E.: Errors and Omissions Excepted.",
    'invoice_include_logo' => '1',
    'invoice_tax_label' => 'GST',
    'invoice_default_tax_rate' => '18',
    'invoice_due_days' => '30',
    'invoice_currency_symbol_position' => 'before'
];

// Fill in defaults for any missing settings
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Check if this is a sample preview
$isSample = isset($_GET['sample']) && $_GET['sample'] === 'true';

if ($isSample) {
    // Create sample data for preview
    $sale = [
        'id' => 0,
        'invoiceNumber' => $settings['invoice_prefix'] . $settings['invoice_next_number'],
        'createdAt' => date('Y-m-d H:i:s'),
        'paymentMethod' => 'Cash',
        'paymentStatus' => 'Paid',
        'totalPrice' => 1234.56,
        'customerId' => 1,
        'customerName' => 'Sample Customer',
        'customerPhone' => '9876543210',
        'customerEmail' => 'sample@example.com',
        'customerAddress' => '123 Sample Street, Sample City'
    ];
    
    $saleItems = [
        [
            'itemName' => 'Sample Product 1',
            'itemCode' => 'SP001',
            'quantity' => 2,
            'unitType' => 'pcs',
            'price' => 500.00,
            'total' => 1000.00
        ],
        [
            'itemName' => 'Sample Product 2',
            'itemCode' => 'SP002',
            'quantity' => 3,
            'unitType' => 'kg',
            'price' => 78.19,
            'total' => 234.56
        ]
    ];
} else {
    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        die("No sale specified!");
    }
    
    $saleId = (int)$_GET['id'];
    
    // Get sale details
    $sale = $db->select("SELECT s.*, c.name as customerName, c.phone as customerPhone, 
                       c.email as customerEmail, c.address as customerAddress
                      FROM sales s 
                       LEFT JOIN customers c ON s.customerId = c.id
                       WHERE s.id = :id", ['id' => $saleId]);
    
    if (empty($sale)) {
        die("Sale not found!");
    }
    
    $sale = $sale[0];
    
    // Get sale items
    $saleItems = $db->select("SELECT si.*, p.itemName, p.itemCode, p.unitType, p.hsn
                             FROM sale_items si
                             JOIN products p ON si.productId = p.id
                             WHERE si.saleId = :saleId",
                             ['saleId' => $saleId]);
}

// Function to format currency based on settings
function formatCurrencyWithSettings($amount, $settings) {
    $currencySymbol = CURRENCY;
    $formattedAmount = number_format($amount, 2);
    
    if ($settings['invoice_currency_symbol_position'] === 'before') {
        return $currencySymbol . $formattedAmount;
    } else {
        return $formattedAmount . $currencySymbol;
    }
}

// Calculate due date if needed
$dueDate = null;
if (!empty($settings['invoice_due_days']) && $settings['invoice_due_days'] > 0) {
    $createdDate = new DateTime($sale['createdAt']);
    $dueDate = clone $createdDate;
    $dueDate->add(new DateInterval('P' . $settings['invoice_due_days'] . 'D'));
}

// Parse terms into array
$termsArray = preg_split('/\r\n|\r|\n/', $settings['invoice_terms']);

// Include the modified invoice template
include 'invoice_template.php';
?>