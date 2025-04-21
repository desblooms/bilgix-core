<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $sale['invoiceNumber']; ?></title>
    <style>
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Header & Branding */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .invoice-logo {
            display: block;
            max-width: 150px;
            max-height: 70px;
        }
        
        .invoice-title {
            color: #2563EB;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .invoice-number {
            font-size: 16px;
            color: #555;
        }
        
        .company-info {
            text-align: right;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        /* Info Sections */
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .invoice-details-left, .invoice-details-right {
            flex: 1;
        }
        
        .invoice-details-right {
            text-align: right;
        }
        
        .detail-label {
            font-weight: bold;
            margin-right: 5px;
            color: #555;
        }
        
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid {
            background-color: #DCFCE7;
            color: #166534;
        }
        
        .status-partial {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-unpaid {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        
        /* Customer Section */
        .customer-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #F9FAFB;
            border-radius: 8px;
        }
        
        .section-title {
            font-size: 16px;
            margin-bottom: 10px;
            color: #4B5563;
            font-weight: bold;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background-color: #F3F4F6;
            text-align: left;
            padding: 12px;
            font-weight: 600;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .items-table tr:nth-child(even) {
            background-color: #F9FAFB;
        }
        
        .price-column {
            text-align: right;
        }
        
        /* Total */
        .total-section {
            margin-top: 20px;
            border-top: 2px solid #ddd;
            padding-top: 20px;
            text-align: right;
        }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 8px;
        }
        
        .total-label {
            width: 150px;
            text-align: right;
            margin-right: 20px;
            font-weight: bold;
        }
        
        .total-value {
            width: 150px;
            text-align: right;
            font-weight: bold;
        }
        
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #2563EB;
        }
        
        /* Terms & Notes */
        .terms-section {
            margin-top: 40px;
            font-size: 12px;
            color: #6B7280;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .terms-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 14px;
            color: #4B5563;
        }
        
        /* Footer */
        .invoice-footer {
            margin-top: 50px;
            text-align: center;
            color: #6B7280;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        /* Signatures */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        
        .signature-box {
            border-top: 1px solid #ddd;
            width: 45%;
            padding-top: 5px;
            text-align: center;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                margin: 0.5cm;
            }
        }
        
        /* Print Button */
        .print-button {
            background-color: #2563EB;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            margin: 20px auto;
        }
        
        .print-button:hover {
            background-color: #1D4ED8;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <?php if ($settings['invoice_include_logo'] === '1' && file_exists('../../assets/images/logo.png')): ?>
                <img src="../../assets/images/logo.png" alt="<?php echo COMPANY_NAME; ?>" class="invoice-logo">
            <?php else: ?>
                <div class="invoice-title"><?php echo COMPANY_NAME; ?></div>
            <?php endif; ?>
            <div class="invoice-number"><?php echo $sale['invoiceNumber']; ?></div>
        </div>
        <div class="company-info">
            <div class="company-name"><?php echo COMPANY_NAME; ?></div>
            <p><?php echo COMPANY_ADDRESS; ?></p>
            <p>Phone: <?php echo COMPANY_PHONE; ?></p>
            <p>Email: <?php echo COMPANY_EMAIL; ?></p>
        </div>
    </div>
    
    <div class="invoice-details">
        <div class="invoice-details-left">
            <p><span class="detail-label">Date:</span> <?php echo date('F d, Y', strtotime($sale['createdAt'])); ?></p>
            <?php if ($dueDate): ?>
                <p><span class="detail-label">Due Date:</span> <?php echo $dueDate->format('F d, Y'); ?></p>
            <?php endif; ?>
            <p><span class="detail-label">Payment Method:</span> <?php echo $sale['paymentMethod']; ?></p>
        </div>
        <div class="invoice-details-right">
            <p>
                <span class="status status-<?php echo strtolower($sale['paymentStatus']); ?>">
                    <?php echo $sale['paymentStatus']; ?>
                </span>
            </p>
        </div>
    </div>
    
    <div class="customer-section">
        <div class="section-title">Bill To:</div>
        <?php if (!empty($sale['customerId'])): ?>
            <p><strong><?php echo $sale['customerName']; ?></strong></p>
            <?php if (!empty($sale['customerPhone'])): ?>
                <p>Phone: <?php echo $sale['customerPhone']; ?></p>
            <?php endif; ?>
            <?php if (!empty($sale['customerEmail'])): ?>
                <p>Email: <?php echo $sale['customerEmail']; ?></p>
            <?php endif; ?>
            <?php if (!empty($sale['customerAddress'])): ?>
                <p>Address: <?php echo $sale['customerAddress']; ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p>Walk-in Customer</p>
        <?php endif; ?>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 35%;">Item Description</th>
                <th style="width: 15%;">Code</th>
                <th style="width: 15%;">Quantity</th>
                <th style="width: 15%;" class="price-column">Unit Price</th>
                <th style="width: 15%;" class="price-column">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            foreach($saleItems as $index => $item): 
                $subtotal += $item['total'];
            ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo $item['itemName']; ?></td>
                <td><?php echo $item['itemCode']; ?></td>
                <td><?php echo $item['quantity'] . ' ' . $item['unitType']; ?></td>
                <td class="price-column"><?php echo formatCurrencyWithSettings($item['price'], $settings); ?></td>
                <td class="price-column"><?php echo formatCurrencyWithSettings($item['total'], $settings); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="total-section">
        <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span class="total-value"><?php echo formatCurrencyWithSettings($subtotal, $settings); ?></span>
        </div>
        
        <?php
        // Calculate tax if applicable
        $taxRate = floatval($settings['invoice_default_tax_rate']);
        if ($taxRate > 0) {
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount;
        ?>
        <div class="total-row">
            <span class="total-label"><?php echo $settings['invoice_tax_label']; ?> (<?php echo $taxRate; ?>%):</span>
            <span class="total-value"><?php echo formatCurrencyWithSettings($taxAmount, $settings); ?></span>
        </div>
        <?php } else { $total = $subtotal; } ?>
        
        <div class="total-row">
            <span class="total-label">Grand Total:</span>
            <span class="total-value grand-total"><?php echo formatCurrencyWithSettings($sale['totalPrice'], $settings); ?></span>
        </div>
    </div>
    
    <div class="terms-section">
        <div class="terms-title">Terms & Conditions:</div>
        <ol style="padding-left: 20px;">
            <?php foreach($termsArray as $term): ?>
                <?php if(trim($term) !== ''): ?>
                    <li><?php echo htmlspecialchars($term); ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <p>For <?php echo COMPANY_NAME; ?></p>
            <p style="margin-top: 30px;">Authorized Signatory</p>
        </div>
        <div class="signature-box">
            <p>Customer Signature</p>
        </div>
    </div>
    
    <div class="invoice-footer">
        <p><?php echo htmlspecialchars($settings['invoice_footer_text']); ?></p>
        <p><?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?> &copy; <?php echo date('Y'); ?></p>
    </div>
    
    <button class="print-button no-print" onclick="window.print()">Print Invoice</button>
    
    <script>
        // Auto-print when the page loads if in print mode
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'true') {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        };
    </script>
</body>
</html>