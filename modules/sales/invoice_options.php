<?php 
// Adjust path for includes
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "No sale specified!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/sales/list.php');
}

$saleId = (int)$_GET['id'];

// Get sale details
$sale = $db->select("SELECT s.*, c.name as customerName, c.phone as customerPhone
                    FROM sales s 
                    LEFT JOIN customers c ON s.customerId = c.id 
                    WHERE s.id = :id", ['id' => $saleId]);

if (empty($sale)) {
    $_SESSION['message'] = "Sale not found!";
    $_SESSION['message_type'] = "error";
    redirect($basePath . 'modules/sales/list.php');
}

$sale = $sale[0];

// Check if the user is on iOS
$isIOS = strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false 
       || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false 
       || strpos($_SERVER['HTTP_USER_AGENT'], 'iPod') !== false;

// Check if the system has WhatsApp functionality
$hasWhatsApp = true; // Set this based on device capability

// Build sharing links
$invoiceUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/print_invoice.php?id=' . $saleId;
$pdfUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/generate_pdf_invoice.php?id=' . $saleId;

// WhatsApp message
$customer = empty($sale['customerName']) ? 'Customer' : $sale['customerName'];
$whatsappMsg = urlencode("Hi $customer,\n\nThanks for your purchase! Here's your invoice from " . COMPANY_NAME . ":\n\n$invoiceUrl\n\nRegards,\n" . COMPANY_NAME);

// Generate a unique filename for the invoice based on invoice number
$invoiceFilename = 'Invoice_' . $sale['invoiceNumber'] . '.pdf';
$invoiceZipFilename = 'Invoice_' . $sale['invoiceNumber'] . '.zip';
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="view.php?id=<?= $saleId ?>" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Invoice Options</h2>
    </div>
    
    <!-- Sale Info Summary -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-lg font-bold text-gray-800"><?= $sale['invoiceNumber'] ?></h3>
                <p class="text-sm text-gray-600"><?= date('F d, Y', strtotime($sale['createdAt'])) ?></p>
                <p class="text-sm text-gray-600 mt-1">
                    <?= empty($sale['customerName']) ? 'Walk-in Customer' : $sale['customerName'] ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold text-green-600"><?= formatCurrency($sale['totalPrice']) ?></p>
                <span class="text-xs px-2 py-1 rounded-full <?= getPaymentStatusClass($sale['paymentStatus']) ?>">
                    <?= $sale['paymentStatus'] ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- View Options -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">View & Print Invoice</h3>
        </div>
        
        <div class="p-4 space-y-3">
            <a href="print_invoice.php?id=<?= $saleId ?>" target="_blank" 
               class="bg-blue-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-alt mr-2"></i> View HTML Invoice
            </a>
            
            <a href="generate_pdf_invoice.php?id=<?= $saleId ?>&action=view" target="_blank" 
               class="bg-red-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-eye mr-2"></i> View PDF Invoice
            </a>

            <a href="#" onclick="sharePdfInvoice('<?= $saleId ?>')" 
   class="bg-red-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
    <i class="fas fa-share-alt mr-2"></i> Share PDF Invoice
</a>
            
            <?php if ($isIOS): ?>
            <!-- iOS-specific download option (ZIP file) -->
            <a href="ios_pdf_download.php?id=<?= $saleId ?>" 
               class="bg-yellow-500 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fab fa-apple mr-2"></i> Download for iOS (ZIP)
            </a>
            
            <!-- iOS Save Instructions -->
            <div class="bg-gray-100 p-3 rounded-lg text-sm">
                <p class="font-medium mb-1">To save on iPhone/iPad:</p>
                <ol class="list-decimal list-inside text-gray-700">
                    <li>Tap "View PDF Invoice" above</li>
                    <li>Tap the share icon <i class="fas fa-share-square"></i></li>
                    <li>Select "Save to Files" or "Save PDF to Books"</li>
                </ol>
            </div>
            <?php else: ?>
            <!-- Direct download link for PDF -->
            <a href="generate_pdf_invoice.php?id=<?= $saleId ?>" 
               class="bg-indigo-600 text-white py-3 px-4 rounded-lg flex items-center justify-center" 
               download="<?= $invoiceFilename ?>">
                <i class="fas fa-download mr-2"></i> Download PDF Invoice
            </a>
            
            <!-- Alternative download method for mobile devices -->
            <a href="javascript:void(0)" id="mobilePdfDownloadBtn"
               class="bg-purple-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-mobile-alt mr-2"></i> Mobile Download PDF
            </a>
            
            <!-- Direct download link that should work on most devices -->
            <a href="download_invoice.php?id=<?= $saleId ?>"
               class="bg-teal-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-file-download mr-2"></i> Direct Download
            </a>
            <?php endif; ?>
            
            <button id="printButton" 
                   class="w-full bg-gray-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-print mr-2"></i> Print Current Page
            </button>
        </div>
    </div>
    
    <!-- Share Options -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Share Invoice</h3>
        </div>
        
        <div class="p-4 space-y-3">
            <?php if ($hasWhatsApp): ?>
            <a href="whatsapp://send?text=<?= $whatsappMsg ?>" 
               class="bg-green-600 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fab fa-whatsapp mr-2"></i> Share via WhatsApp
            </a>
            <?php endif; ?>
            
            <a href="mailto:?subject=Invoice from <?= COMPANY_NAME ?>&body=<?= urlencode("Hi,\n\nThanks for your purchase! Please find your invoice attached or view it online at:\n$invoiceUrl\n\nRegards,\n" . COMPANY_NAME) ?>" 
               class="bg-blue-500 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-envelope mr-2"></i> Share via Email
            </a>
            
            <button id="copyLink" data-link="<?= $invoiceUrl ?>"
                   class="w-full bg-gray-700 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-link mr-2"></i> Copy Invoice Link
            </button>
            <?php if (!empty($sale['customerPhone'])): ?>
            <button id="sendSmsBtn" data-sale-id="<?= $saleId ?>"
                    class="w-full bg-green-700 text-white py-3 px-4 rounded-lg flex items-center justify-center">
                <i class="fas fa-sms mr-2"></i> Send SMS to <?= $sale['customerPhone'] ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Return to Sale -->
    <a href="view.php?id=<?= $saleId ?>" class="block text-center text-blue-600 mt-4">
        <i class="fas fa-arrow-left mr-1"></i> Back to Sale Details
    </a>
</div>

<!-- Bottom Navigation -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t flex justify-between items-center p-2 bottom-nav">
    <a href="../../index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-home text-xl"></i>
        <span class="text-xs mt-1">Home</span>
    </a>
    <a href="../products/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-box text-xl"></i>
        <span class="text-xs mt-1">Products</span>
    </a>
    <a href="../sales/add.php" class="flex flex-col items-center p-2 text-gray-600">
        <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
            <i class="fas fa-plus text-xl"></i>
        </div>
        <span class="text-xs mt-1">New Sale</span>
    </a>
    <a href="../customers/list.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-users text-xl"></i>
        <span class="text-xs mt-1">Customers</span>
    </a>
    <a href="../reports/index.php" class="flex flex-col items-center p-2 text-gray-600">
        <i class="fas fa-chart-bar text-xl"></i>
        <span class="text-xs mt-1">Reports</span>
    </a>
</nav>

<script>
    // Print functionality
    document.getElementById('printButton').addEventListener('click', function() {
        window.print();
    });
    
    // Copy link functionality
    document.getElementById('copyLink').addEventListener('click', function() {
        const link = this.getAttribute('data-link');
        
        // Use modern clipboard API if available
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(link).then(() => {
                showCopySuccess(this);
            }).catch(err => {
                console.error('Could not copy text: ', err);
                fallbackCopy(link, this);
            });
        } else {
            fallbackCopy(link, this);
        }
    });
    
    // Fallback copy method for older browsers
    function fallbackCopy(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(button);
            } else {
                console.error('Failed to copy');
                alert('Failed to copy link. Please try again or copy manually.');
            }
        } catch (err) {
            console.error('Error copying text: ', err);
            alert('Failed to copy link. Please try again or copy manually.');
        }
        
        document.body.removeChild(textArea);
    }
    
    // Show success message
    function showCopySuccess(button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-2"></i> Link Copied!';
        
        // Reset back to original after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalText;
        }, 2000);
    }
    
    <?php if (!$isIOS): ?>
    // Load PDF Downloader utility
    document.addEventListener('DOMContentLoaded', function() {
        // Add script only if not already loaded
        if (!window.PDFDownloader) {
            const script = document.createElement('script');
            script.src = '<?= $basePath ?>assets/js/pdfDownloader.js';
            script.onload = function() {
                initPDFDownloader();
            };
            document.head.appendChild(script);
        } else {
            initPDFDownloader();
        }
    });
    
    function initPDFDownloader() {
        // Setup mobile download button using our utility
        if (window.PDFDownloader) {
            PDFDownloader.setupDownloadButton(
                '<?= $pdfUrl ?>', 
                '<?= $invoiceFilename ?>', 
                'mobilePdfDownloadBtn'
            );
        } else {
            // Fallback if PDFDownloader hasn't loaded
            document.getElementById('mobilePdfDownloadBtn').addEventListener('click', function() {
                const pdfUrl = '<?= $pdfUrl ?>';
                
                // Simple fallback: open in new tab/window
                window.open(pdfUrl, '_blank');
                
                // Show feedback to the user
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Downloading...';
                
                // Reset button text after a delay
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Download Complete!';
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 2000);
                }, 3000);
            });
        }
    }
    <?php endif; ?>
</script>

<script>
    document.getElementById('sendSmsBtn')?.addEventListener('click', function () {
        const button = this;
        const saleId = button.getAttribute('data-sale-id');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';

        fetch('send_invoice_sms.php?id=' + saleId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.innerHTML = '<i class="fas fa-check-circle mr-2"></i> SMS Sent!';
                } else {
                    button.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Failed to send SMS';
                    console.error(data.error);
                }
            })
            .catch(error => {
                button.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Error sending SMS';
                console.error(error);
            })
            .finally(() => {
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-sms mr-2"></i> Send SMS';
                    button.disabled = false;
                }, 3000);
            });
    });
</script>
<script>
    async function sharePdfInvoice(saleId) {
    try {
        // First, fetch the PDF file
        const pdfUrl = `${window.location.origin}/modules/sales/generate_pdf_invoice.php?id=${saleId}`;
        const response = await fetch(pdfUrl);
        const pdfBlob = await response.blob();
        
        // Create a File object from the Blob
        const fileName = `Invoice-${saleId}.pdf`;
        const pdfFile = new File([pdfBlob], fileName, { type: 'application/pdf' });
        
        // Check if Web Share API supports sharing files
        if (navigator.share && navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
            await navigator.share({
                files: [pdfFile],
                title: 'Invoice PDF',
                text: 'Here is your invoice PDF'
            });
            console.log('PDF shared successfully');
        } else {
            // Fallback: trigger download if sharing not supported
            const downloadUrl = URL.createObjectURL(pdfBlob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(downloadUrl);
        }
    } catch (error) {
        console.error('Error sharing PDF:', error);
        alert('Failed to share PDF. Please try downloading it instead.');
        // Fallback to direct link
        window.open(`${window.location.origin}/modules/sales/generate_pdf_invoice.php?id=${saleId}`, '_blank');
    }
}
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>