<?php
/**
 * Notification Helper
 * 
 * This file contains functions to handle email notifications throughout the application
 */

// Require PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Initialize and configure the PHPMailer instance
 * 
 * @return PHPMailer Configured PHPMailer instance
 */
function initializeMailer() {
    global $db;
    
    // Get email settings from database
    $emailSettings = $db->select("SELECT * FROM settings WHERE settingGroup = 'notification'");
    $settings = [];
    
    // Convert to associative array for easier access
    if (!empty($emailSettings)) {
        foreach ($emailSettings as $setting) {
            $settings[$setting['settingKey']] = $setting['settingValue'];
        }
    }
    
    // Default values if settings don't exist
    $defaults = [
        'smtp_enabled' => '0',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'email_from' => COMPANY_EMAIL,
        'email_from_name' => COMPANY_NAME,
        'email_footer' => 'Thank you for using ' . APP_NAME,
    ];
    
    // Merge defaults with existing settings
    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        if ($settings['smtp_enabled'] === '1') {
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->Port = $settings['smtp_port'];
            
            if (!empty($settings['smtp_username'])) {
                $mail->SMTPAuth = true;
                $mail->Username = $settings['smtp_username'];
                $mail->Password = $settings['smtp_password'];
            }
            
            if ($settings['smtp_encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($settings['smtp_encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
        }
        
        // Set the from address
        $mail->setFrom($settings['email_from'], $settings['email_from_name']);
        
        // Set defaults
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        return $mail;
    } catch (Exception $e) {
        // Log error
        error_log('Mailer initialization failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Send an email notification
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $altBody Plain text version (optional)
 * @param array $attachments Array of attachment paths (optional)
 * @return bool True on success, false on failure
 */
function sendEmailNotification($to, $subject, $body, $altBody = '', $attachments = []) {
    global $db;
    
    // Get email footer
    $emailSettings = $db->select("SELECT settingValue FROM settings WHERE settingKey = 'email_footer'");
    $emailFooter = !empty($emailSettings) ? $emailSettings[0]['settingValue'] : 'Thank you for using ' . APP_NAME;
    
    // Add footer to email body
    $body .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px;">' . $emailFooter . '</div>';
    
    try {
        $mail = initializeMailer();
        
        if (!$mail) {
            return false;
        }
        
        // Add recipient
        $mail->addAddress($to);
        
        // Set subject and body
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Set alternative plain text body if provided
        if (!empty($altBody)) {
            $mail->AltBody = $altBody;
        } else {
            // Generate plain text from HTML
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br />', '<br/>', '</p>'], "\n", $body));
        }
        
        // Add attachments if any
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        // Send the email
        return $mail->send();
    } catch (Exception $e) {
        // Log error
        error_log('Email sending failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send a sale notification email to the customer
 * 
 * @param int $saleId The sale ID
 * @return bool True on success, false on failure
 */
function sendSaleNotification($saleId) {
    global $db;
    
    // Check if new order notifications are enabled
    $notifySettings = $db->select("SELECT settingValue FROM settings WHERE settingKey = 'notify_new_order'");
    if (empty($notifySettings) || $notifySettings[0]['settingValue'] !== '1') {
        return false;
    }
    
    // Get sale details
    $sale = $db->select("SELECT s.*, c.name as customerName, c.email as customerEmail 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customerId = c.id 
                        WHERE s.id = :id", ['id' => $saleId]);
    
    // If no sale found or no customer email, return false
    if (empty($sale) || empty($sale[0]['customerEmail'])) {
        return false;
    }
    
    $sale = $sale[0];
    
    // Get sale items
    $saleItems = $db->select("SELECT si.*, p.itemName, p.itemCode, p.unitType 
                             FROM sale_items si 
                             JOIN products p ON si.productId = p.id 
                             WHERE si.saleId = :saleId", 
                             ['saleId' => $saleId]);
    
    // Prepare email subject and body
    $subject = 'Your Order Confirmation - ' . $sale['invoiceNumber'];
    
    // Build email body with HTML
    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #bb0620; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Order Confirmation</h1>
            <p style="margin: 5px 0 0 0;">Invoice #' . $sale['invoiceNumber'] . '</p>
        </div>
        
        <div style="padding: 20px; border: 1px solid #eee; border-top: none;">
            <p>Dear ' . $sale['customerName'] . ',</p>
            
            <p>Thank you for your order! We\'re pleased to confirm that your order has been received and is being processed.</p>
            
            <h3 style="margin-top: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Order Summary</h3>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="background-color: #f3f4f6;">
                        <th style="padding: 10px; text-align: left; border-bottom: 1px solid #eee;">Item</th>
                        <th style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;">Quantity</th>
                        <th style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">Price</th>
                        <th style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">Total</th>
                    </tr>
                </thead>
                <tbody>';
    
    // Add sale items to the email
    foreach ($saleItems as $item) {
        $body .= '
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">' . $item['itemName'] . ' (' . $item['itemCode'] . ')</td>
                        <td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;">' . $item['quantity'] . ' ' . $item['unitType'] . '</td>
                        <td style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">' . formatCurrency($item['price']) . '</td>
                        <td style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">' . formatCurrency($item['total']) . '</td>
                    </tr>';
    }
    
    // Add total and payment details
    $body .= '
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td colspan="3" style="padding: 10px; text-align: right; border-top: 2px solid #eee;">Grand Total:</td>
                        <td style="padding: 10px; text-align: right; border-top: 2px solid #eee;">' . formatCurrency($sale['totalPrice']) . '</td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 20px; background-color: #f9fafb; padding: 15px; border-radius: 5px;">
                <h4 style="margin-top: 0;">Payment Information</h4>
                <p><strong>Payment Method:</strong> ' . $sale['paymentMethod'] . '</p>
                <p><strong>Status:</strong> ' . $sale['paymentStatus'] . '</p>
            </div>
            
            <p style="margin-top: 20px;">If you have any questions about your order, please contact us.</p>
            
            <p>Best regards,<br>' . COMPANY_NAME . '</p>
        </div>
    </div>';
    
    // Send the email
    return sendEmailNotification($sale['customerEmail'], $subject, $body);
}

/**
 * Send a low stock notification to admin
 * 
 * @param int $productId The product ID
 * @return bool True on success, false on failure
 */
function sendLowStockNotification($productId) {
    global $db;
    
    // Check if low stock notifications are enabled
    $notifySettings = $db->select("SELECT settingValue FROM settings WHERE settingKey = 'notify_low_stock'");
    if (empty($notifySettings) || $notifySettings[0]['settingValue'] !== '1') {
        return false;
    }
    
    // Get admin email
    $adminEmailSetting = $db->select("SELECT settingValue FROM settings WHERE settingKey = 'admin_email'");
    $fromEmailSetting = $db->select("SELECT settingValue FROM settings WHERE settingKey = 'email_from'");
    
    $adminEmail = !empty($adminEmailSetting) ? $adminEmailSetting[0]['settingValue'] : '';
    
    // If admin email is not set, use the from email
    if (empty($adminEmail) && !empty($fromEmailSetting)) {
        $adminEmail = $fromEmailSetting[0]['settingValue'];
    }
    
    // If still no email, return false
    if (empty($adminEmail)) {
        return false;
    }
    
    // Get product details
    $product = getProduct($productId);
    
    // If no product found, return false
    if (!$product) {
        return false;
    }
    
    // Prepare email subject and body
    $subject = 'Low Stock Alert - ' . $product['itemName'];
    
    // Build email body with HTML
    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #f44336; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Low Stock Alert</h1>
        </div>
        
        <div style="padding: 20px; border: 1px solid #eee; border-top: none;">
            <p>This is an automated notification to inform you that the following product has reached the low stock threshold:</p>
            
            <div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border-left: 4px solid #f44336;">
                <h3 style="margin-top: 0;">' . $product['itemName'] . '</h3>
                <p><strong>Code:</strong> ' . $product['itemCode'] . '</p>
                <p><strong>Current Stock:</strong> ' . $product['qty'] . ' ' . $product['unitType'] . '</p>
                <p><strong>Low Stock Threshold:</strong> ' . LOW_STOCK_THRESHOLD . ' ' . $product['unitType'] . '</p>
            </div>
            
            <p>Please take action to replenish the inventory as soon as possible.</p>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/modules/products/edit.php?id=' . $product['id'] . '" style="background-color: #bb0620; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">View Product</a>
            </div>
        </div>
    </div>';
    
    // Send the email
    return sendEmailNotification($adminEmail, $subject, $body);
}

/**
 * Send an invoice email to the customer
 * 
 * @param int $saleId The sale ID
 * @return bool True on success, false on failure
 */
function sendInvoiceEmail($saleId) {
    global $db, $basePath;
    
    // Get sale details
    $sale = $db->select("SELECT s.*, c.name as customerName, c.email as customerEmail 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customerId = c.id 
                        WHERE s.id = :id", ['id' => $saleId]);
    
    // If no sale found or no customer email, return false
    if (empty($sale) || empty($sale[0]['customerEmail'])) {
        return false;
    }
    
    $sale = $sale[0];
    
    // Generate PDF invoice if TCPDF is available
    $attachments = [];
    $pdfPath = '';
    
    if (file_exists($basePath . 'vendor/tcpdf/tcpdf.php')) {
        // Path to generate PDF invoice temporarily
        $pdfPath = sys_get_temp_dir() . '/Invoice_' . $sale['invoiceNumber'] . '.pdf';
        
        // Capture PDF generation output
        ob_start();
        include($basePath . 'modules/sales/generate_pdf_invoice.php');
        $pdfContent = ob_get_clean();
        
        // Save PDF to temp file
        file_put_contents($pdfPath, $pdfContent);
        
        // Add to attachments
        $attachments[] = $pdfPath;
    }
    
    // Prepare email subject and body
    $subject = 'Invoice #' . $sale['invoiceNumber'] . ' - ' . COMPANY_NAME;
    
    // Build email body with HTML
    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #bb0620; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Invoice</h1>
            <p style="margin: 5px 0 0 0;">#' . $sale['invoiceNumber'] . '</p>
        </div>
        
        <div style="padding: 20px; border: 1px solid #eee; border-top: none;">
            <p>Dear ' . $sale['customerName'] . ',</p>
            
            <p>Please find attached your invoice for your recent purchase from ' . COMPANY_NAME . '.</p>';
            
    // If PDF attachment couldn't be created, provide a link to view online
    if (empty($attachments)) {
        $invoiceUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/modules/sales/print_invoice.php?id=' . $saleId;
        
        $body .= '
            <p>You can view your invoice online by clicking the button below:</p>
            
            <div style="margin: 20px 0; text-align: center;">
                <a href="' . $invoiceUrl . '" style="background-color: #bb0620; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">View Invoice</a>
            </div>';
    } else {
        $body .= '
            <p>Your invoice is attached to this email as a PDF document.</p>';
    }
    
    $body .= '
            <p>If you have any questions regarding your invoice, please don\'t hesitate to contact us.</p>
            
            <p>Thank you for your business!</p>
            
            <p>Best regards,<br>' . COMPANY_NAME . '</p>
        </div>
    </div>';
    
    // Send the email
    $result = sendEmailNotification($sale['customerEmail'], $subject, $body, '', $attachments);
    
    // Delete temporary PDF file if it was created
    if (!empty($pdfPath) && file_exists($pdfPath)) {
        unlink($pdfPath);
    }
    
    return $result;
}

/**
 * Send a payment confirmation email to the customer
 * 
 * @param int $saleId The sale ID
 * @return bool True on success, false on failure
 */
function sendPaymentConfirmationEmail($saleId) {
    global $db;
    
    // Check if payment notifications are enabled
    $notifySettings = $db->select("SELECT settingValue FROM settings WHERE settingKey = 'notify_payment_received'");
    if (empty($notifySettings) || $notifySettings[0]['settingValue'] !== '1') {
        return false;
    }
    
    // Get sale details
    $sale = $db->select("SELECT s.*, c.name as customerName, c.email as customerEmail 
                        FROM sales s 
                        LEFT JOIN customers c ON s.customerId = c.id 
                        WHERE s.id = :id", ['id' => $saleId]);
    
    // If no sale found or no customer email, return false
    if (empty($sale) || empty($sale[0]['customerEmail'])) {
        return false;
    }
    
    $sale = $sale[0];
    
    // Prepare email subject and body
    $subject = 'Payment Confirmation - Invoice #' . $sale['invoiceNumber'];
    
    // Build email body with HTML
    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background-color: #4caf50; color: white; padding: 20px; text-align: center;">
            <h1 style="margin: 0;">Payment Confirmation</h1>
            <p style="margin: 5px 0 0 0;">Invoice #' . $sale['invoiceNumber'] . '</p>
        </div>
        
        <div style="padding: 20px; border: 1px solid #eee; border-top: none;">
            <p>Dear ' . $sale['customerName'] . ',</p>
            
            <p>Thank you for your payment of <strong>' . formatCurrency($sale['totalPrice']) . '</strong> for Invoice #' . $sale['invoiceNumber'] . '.</p>
            
            <div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border-left: 4px solid #4caf50;">
                <p style="margin: 0;"><strong>Payment Method:</strong> ' . $sale['paymentMethod'] . '</p>
                <p style="margin: 5px 0 0 0;"><strong>Date:</strong> ' . date('F d, Y') . '</p>
            </div>
            
            <p>Your payment has been successfully processed and recorded in our system.</p>
            
            <p> If you have any questions, please dont hesitate to contact us. </p>
            
            <p>Best regards,<br>' . COMPANY_NAME . '</p>
        </div>
    </div>';
    
    // Send the email
    return sendEmailNotification($sale['customerEmail'], $subject, $body);
}