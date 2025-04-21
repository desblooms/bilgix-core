<?php
// generate_pdf_invoice.php - Generate PDF Invoice using TCPDF
// Adjust path for includes
$basePath = '../../';
// Use require_once instead of include or require
require_once $basePath . 'includes/functions.php';
require_once $basePath . 'includes/db.php';
require_once $basePath . 'includes/config.php';

// Check if TCPDF library exists, if not, provide instructions to download
if (!file_exists($basePath . 'vendor/tcpdf/tcpdf.php')) {
    die('TCPDF library not found. Please download TCPDF from https://github.com/tecnickcom/TCPDF and extract it to vendor/tcpdf/ folder.');
}

// Include TCPDF library
require_once($basePath . 'vendor/tcpdf/tcpdf.php');

// Initialize database connection
$db = new Database();

// Load invoice settings from database
$invoiceSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'invoice'");
$settings = [];

// Convert array of settings to associative array for easier access
foreach ($invoiceSettings as $setting) {
    $settings[$setting['settingKey']] = $setting['settingValue'];
}

// Set defaults for missing settings
$defaults = [
    'invoice_prefix' => 'INV-',
    'invoice_next_number' => '1001',
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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No sale specified!");
}

$saleId = (int)$_GET['id'];

// Determine if this is a sample preview
$isSample = isset($_GET['sample']) && $_GET['sample'] === 'true';

// Get sale details or create sample data
if ($isSample) {
    // Create sample sale data for preview
    $sale = [
        'id' => 0,
        'invoiceNumber' => $settings['invoice_prefix'] . $settings['invoice_next_number'],
        'customerId' => 1,
        'customerName' => 'Sample Customer',
        'customerPhone' => '1234567890',
        'customerEmail' => 'sample@example.com',
        'customerAddress' => '123 Sample Street, Sample City',
        'subtotal' => 1000.00,
        'taxAmount' => floatval($settings['invoice_default_tax_rate']) * 10,
        'discountAmount' => 50.00,
        'total' => 1000.00 + (floatval($settings['invoice_default_tax_rate']) * 10) - 50.00,
        'paymentMethod' => 'Cash',
        'paymentStatus' => 'Paid',
        'createdAt' => date('Y-m-d H:i:s'),
        'dueDate' => date('Y-m-d', strtotime('+' . $settings['invoice_due_days'] . ' days'))
    ];
    
    // Sample items
    $saleItems = [
        [
            'productId' => 1,
            'itemName' => 'Sample Product 1',
            'itemCode' => 'SP001',
            'hsn' => '1234',
            'unitType' => 'pcs',
            'quantity' => 2,
            'price' => 300.00,
            'total' => 600.00
        ],
        [
            'productId' => 2,
            'itemName' => 'Sample Product 2',
            'itemCode' => 'SP002',
            'hsn' => '5678',
            'unitType' => 'kg',
            'quantity' => 4,
            'price' => 100.00,
            'total' => 400.00
        ]
    ];
} else {
    // Get real sale details
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

// Helper function to format currency according to settings
function formatCurrencyWithSettings($amount, $settings) {
    $symbol = CURRENCY;
    if ($settings['invoice_currency_symbol_position'] === 'before') {
        return $symbol . number_format($amount, 2);
    } else {
        return number_format($amount, 2) . $symbol;
    }
}

// Create custom PDF class
class MYPDF extends TCPDF {
    protected $settings;
    protected $footerText;
    
    public function setSettings($settings) {
        $this->settings = $settings;
        $this->footerText = $settings['invoice_footer_text'];
    }
    
    // Page header
    public function Header() {
        // Check if logo should be included
        if ($this->settings['invoice_include_logo'] === '1') {
            // Logo
            $image_file = '../../assets/images/logo.png';
            if (file_exists($image_file)) {
                $this->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
        }
        
        // Set font
        $this->SetFont('helvetica', 'B', 16);
        
        // Company info
        $this->SetY(10);
        $this->SetX(50);
        $this->Cell(0, 10, COMPANY_NAME, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        
        $this->SetFont('helvetica', '', 9);
        $this->SetY(15);
        $this->SetX(50);
        $this->Cell(0, 10, COMPANY_ADDRESS, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        
        $this->SetY(20);
        $this->SetX(50);
        $this->Cell(0, 10, 'Phone: ' . COMPANY_PHONE . ' | Email: ' . COMPANY_EMAIL, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        
        // Title
        $this->SetFont('helvetica', 'B', 18);
        $this->SetY(30);
        $this->Cell(0, 10, 'INVOICE', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Line break
        $this->Ln(20);
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        
        // Footer text from settings
        $this->SetY(-20);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 10, $this->footerText, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setSettings($settings);

// Set document information
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor(COMPANY_NAME);
$pdf->SetTitle('Invoice #' . $sale['invoiceNumber']);
$pdf->SetSubject('Invoice #' . $sale['invoiceNumber']);
$pdf->SetKeywords('Invoice, Sale, ' . COMPANY_NAME);

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 50, 15);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 11);

// Invoice info
$pdf->SetY(40);
$pdf->SetX(15);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 10, 'Invoice #: ' . $sale['invoiceNumber'], 0, 0, 'L');
$pdf->Cell(90, 10, 'Date: ' . date('F d, Y', strtotime($sale['createdAt'])), 0, 1, 'R');

// Add due date if configured
if (isset($sale['dueDate']) && !empty($sale['dueDate'])) {
    $pdf->Cell(90, 5, 'Payment Method: ' . $sale['paymentMethod'], 0, 0, 'L');
    $pdf->Cell(90, 5, 'Due Date: ' . date('F d, Y', strtotime($sale['dueDate'])), 0, 1, 'R');
} else {
    $pdf->Cell(90, 5, 'Payment Method: ' . $sale['paymentMethod'], 0, 0, 'L');
    $pdf->Cell(90, 5, 'Payment Status: ' . $sale['paymentStatus'], 0, 1, 'R');
}

// Customer info
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(180, 8, 'Bill To:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10);
if (!empty($sale['customerId'])) {
    $pdf->Cell(180, 5, $sale['customerName'], 0, 1, 'L');
    
    if (!empty($sale['customerPhone'])) {
        $pdf->Cell(180, 5, 'Phone: ' . $sale['customerPhone'], 0, 1, 'L');
    }
    
    if (!empty($sale['customerEmail'])) {
        $pdf->Cell(180, 5, 'Email: ' . $sale['customerEmail'], 0, 1, 'L');
    }
    
    if (!empty($sale['customerAddress'])) {
        $pdf->Cell(180, 5, 'Address: ' . $sale['customerAddress'], 0, 1, 'L');
    }
} else {
    $pdf->Cell(180, 5, 'Walk-in Customer', 0, 1, 'L');
}

// Items table
$pdf->Ln(10);

// Table header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(10, 8, '#', 1, 0, 'C', 1);
$pdf->Cell(65, 8, 'Item', 1, 0, 'L', 1);
$pdf->Cell(25, 8, 'Code/HSN', 1, 0, 'C', 1);
$pdf->Cell(20, 8, 'Qty', 1, 0, 'C', 1);
$pdf->Cell(30, 8, 'Unit Price', 1, 0, 'R', 1);
$pdf->Cell(30, 8, 'Total', 1, 1, 'R', 1);

// Table rows
$pdf->SetFont('helvetica', '', 9);
$totalAmount = 0;

foreach ($saleItems as $key => $item) {
    $totalAmount += $item['total'];
    
    $pdf->Cell(10, 7, $key + 1, 1, 0, 'C');
    $pdf->Cell(65, 7, $item['itemName'], 1, 0, 'L');
    $pdf->Cell(25, 7, $item['itemCode'] . (!empty($item['hsn']) ? '/' . $item['hsn'] : ''), 1, 0, 'C');
    $pdf->Cell(20, 7, $item['quantity'] . ' ' . $item['unitType'], 1, 0, 'C');
    $pdf->Cell(30, 7, formatCurrencyWithSettings($item['price'], $settings), 1, 0, 'R');
    $pdf->Cell(30, 7, formatCurrencyWithSettings($item['total'], $settings), 1, 1, 'R');
}

// Subtotal, Tax, Discount, Total section
if (isset($sale['subtotal']) && isset($sale['taxAmount'])) {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(150, 7, 'Subtotal:', 1, 0, 'R');
    $pdf->Cell(30, 7, formatCurrencyWithSettings($sale['subtotal'], $settings), 1, 1, 'R');
    
    $taxLabel = $settings['invoice_tax_label'] . ' (' . $settings['invoice_default_tax_rate'] . '%)';
    $pdf->Cell(150, 7, $taxLabel . ':', 1, 0, 'R');
    $pdf->Cell(30, 7, formatCurrencyWithSettings($sale['taxAmount'], $settings), 1, 1, 'R');
    
    if (isset($sale['discountAmount']) && $sale['discountAmount'] > 0) {
        $pdf->Cell(150, 7, 'Discount:', 1, 0, 'R');
        $pdf->Cell(30, 7, formatCurrencyWithSettings($sale['discountAmount'], $settings), 1, 1, 'R');
    }
}

// Total row
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(150, 8, 'Total:', 1, 0, 'R', 1);
$pdf->Cell(30, 8, formatCurrencyWithSettings(isset($sale['total']) ? $sale['total'] : $totalAmount, $settings), 1, 1, 'R', 1);

// Terms and Conditions
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(180, 8, 'Terms & Conditions:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(180, 5, $settings['invoice_terms'], 0, 'L', 0, 1);

// Signatures
$pdf->Ln(15);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(90, 5, 'For ' . COMPANY_NAME, 0, 0, 'L');
$pdf->Cell(90, 5, 'Customer Signature', 0, 1, 'R');

$pdf->Ln(15);
$pdf->Cell(90, 5, 'Authorized Signatory', 0, 0, 'L');
$pdf->Cell(90, 5, '', 0, 1, 'R');

// ---------------------------------------------------------

// Generate PDF filename
$pdf_filename = 'Invoice_' . $sale['invoiceNumber'] . '.pdf';

// Check if direct output or download
$action = isset($_GET['action']) ? $_GET['action'] : 'download';

// Clear any previous output
if (ob_get_length()) ob_clean();

// Set appropriate headers based on action
if ($action === 'view') {
    // Output PDF to browser for viewing
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdf_filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    $pdf->Output($pdf_filename, 'I');
} else {
    // Force download headers - improved for mobile compatibility
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $pdf_filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Description: File Transfer');
    header('Cache-Control: public, must-revalidate, max-age=0');
    header('Pragma: public');
    header('Expires: 0');
    $pdf->Output($pdf_filename, 'D');
}
exit();
?>