<?php 
// modules/settings/email_templates.php
$basePath = '../../';
include $basePath . 'includes/header.php'; 

// Check user authorization (admin or manager only)
checkAuthorization('manager');

// Get all email templates from database
$templates = $db->select("SELECT * FROM email_templates ORDER BY name ASC");

// If table doesn't exist, create it and add default templates
if (empty($templates) && $db->query("SHOW TABLES LIKE 'email_templates'")->rowCount() === 0) {
    // Create email_templates table if not exists
    $db->query("CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        variables TEXT,
        createdAt DATETIME NOT NULL,
        updatedAt DATETIME
    )");
    
    // Add default templates
    $defaultTemplates = [
        [
            'name' => 'invoice',
            'subject' => 'Your Invoice from ' . COMPANY_NAME,
            'body' => "Dear {{customer_name}},\n\nThank you for your purchase!\n\nPlease find attached your invoice {{invoice_number}} dated {{invoice_date}}.\n\nTotal Amount: {{currency}}{{total_amount}}\n\nIf you have any questions, please don't hesitate to contact us.\n\nRegards,\n" . COMPANY_NAME,
            'variables' => json_encode(['customer_name', 'invoice_number', 'invoice_date', 'currency', 'total_amount']),
            'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'payment_reminder',
            'subject' => 'Payment Reminder for Invoice #{{invoice_number}}',
            'body' => "Dear {{customer_name}},\n\nThis is a friendly reminder that payment for invoice #{{invoice_number}} in the amount of {{currency}}{{total_amount}} is due.\n\nOriginal Invoice Date: {{invoice_date}}\n\nPlease process this payment at your earliest convenience.\n\nRegards,\n" . COMPANY_NAME,
            'variables' => json_encode(['customer_name', 'invoice_number', 'invoice_date', 'currency', 'total_amount', 'due_date']),
            'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'welcome',
            'subject' => 'Welcome to ' . COMPANY_NAME,
            'body' => "Dear {{customer_name}},\n\nWelcome to " . COMPANY_NAME . "!\n\nWe're delighted to have you as our customer and look forward to serving you.\n\nYour account has been successfully created. You can now enjoy all the benefits of being our customer.\n\nIf you have any questions or need assistance, please feel free to contact us.\n\nRegards,\n" . COMPANY_NAME,
            'variables' => json_encode(['customer_name']),
            'createdAt' => date('Y-m-d H:i:s')
        ],
        [
            'name' => 'order_confirmation',
            'subject' => 'Order Confirmation #{{order_number}}',
            'body' => "Dear {{customer_name}},\n\nThank you for your order!\n\nYour order #{{order_number}} has been confirmed and is being processed.\n\nOrder Date: {{order_date}}\nTotal Amount: {{currency}}{{total_amount}}\n\nYou will receive another notification when your order ships.\n\nRegards,\n" . COMPANY_NAME,
            'variables' => json_encode(['customer_name', 'order_number', 'order_date', 'currency', 'total_amount']),
            'createdAt' => date('Y-m-d H:i:s')
        ]
    ];
    
    foreach ($defaultTemplates as $template) {
        $db->insert('email_templates', $template);
    }
    
    // Fetch again
    $templates = $db->select("SELECT * FROM email_templates ORDER BY name ASC");
}

// Handle template edit/add
$editTemplate = null;
$errors = [];

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $templateId = (int)$_GET['edit'];
    $result = $db->select("SELECT * FROM email_templates WHERE id = :id", ['id' => $templateId]);
    
    if (!empty($result)) {
        $editTemplate = $result[0];
        // Decode variables
        $editTemplate['variables'] = json_decode($editTemplate['variables'], true);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateId = isset($_POST['template_id']) ? (int)$_POST['template_id'] : null;
    $name = sanitize($_POST['name']);
    $subject = sanitize($_POST['subject']);
    $body = $_POST['body']; // Don't sanitize to preserve formatting
    $variablesInput = $_POST['variables'];
    
    // Convert variables to array
    $variables = explode(',', $variablesInput);
    $variables = array_map('trim', $variables);
    $variables = array_filter($variables);
    
    // Validation
    if (empty($name)) $errors[] = "Template name is required";
    if (empty($subject)) $errors[] = "Email subject is required";
    if (empty($body)) $errors[] = "Email body is required";
    
    // If no errors, update or insert
    if (empty($errors)) {
        $data = [
            'name' => $name,
            'subject' => $subject,
            'body' => $body,
            'variables' => json_encode($variables),
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        if ($templateId) {
            // Update existing template
            $updated = $db->update('email_templates', $data, 'id = :id', ['id' => $templateId]);
            
            if ($updated) {
                $_SESSION['message'] = "Email template updated successfully!";
                $_SESSION['message_type'] = "success";
                redirect($basePath . 'modules/settings/email_templates.php');
            } else {
                $errors[] = "Failed to update template";
            }
        } else {
            // Add new template
            $data['createdAt'] = date('Y-m-d H:i:s');
            $newId = $db->insert('email_templates', $data);
            
            if ($newId) {
                $_SESSION['message'] = "New email template added successfully!";
                $_SESSION['message_type'] = "success";
                redirect($basePath . 'modules/settings/email_templates.php');
            } else {
                $errors[] = "Failed to add template";
            }
        }
    }
}

// Handle delete action
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $templateId = (int)$_GET['delete'];
    
    $deleted = $db->delete('email_templates', 'id = :id', ['id' => $templateId]);
    
    if ($deleted) {
        $_SESSION['message'] = "Email template deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete template";
        $_SESSION['message_type'] = "error";
    }
    
    redirect($basePath . 'modules/settings/email_templates.php');
}
?>

<div class="mb-6">
    <div class="flex items-center mb-4">
        <a href="<?= $basePath ?>index.php" class="mr-2 text-slate-950">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-xl font-bold text-gray-800">Email Templates</h2>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
        <ul class="list-disc list-inside">
            <?php foreach($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Template Form -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-medium text-gray-800 mb-4">
            <?= $editTemplate ? 'Edit Template: ' . $editTemplate['name'] : 'Add New Template' ?>
        </h3>
        
        <form method="POST" class="space-y-4">
            <?php if ($editTemplate): ?>
            <input type="hidden" name="template_id" value="<?= $editTemplate['id'] ?>">
            <?php endif; ?>
            
            <div>
                <label for="name" class="block text-gray-700 font-medium mb-2">Template Name *</label>
                <input type="text" id="name" name="name" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $editTemplate ? $editTemplate['name'] : '' ?>">
                <p class="text-sm text-gray-500 mt-1">Use a descriptive name like "invoice" or "payment_reminder"</p>
            </div>
            
            <div>
                <label for="subject" class="block text-gray-700 font-medium mb-2">Email Subject *</label>
                <input type="text" id="subject" name="subject" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" required value="<?= $editTemplate ? $editTemplate['subject'] : '' ?>">
            </div>
            
            <div>
                <label for="body" class="block text-gray-700 font-medium mb-2">Email Body *</label>
                <textarea id="body" name="body" rows="10" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900 font-mono text-sm" required><?= $editTemplate ? $editTemplate['body'] : '' ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Use {{variable_name}} for dynamic content</p>
            </div>
            
            <div>
                <label for="variables" class="block text-gray-700 font-medium mb-2">Variables</label>
                <input type="text" id="variables" name="variables" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900" value="<?= $editTemplate ? implode(', ', $editTemplate['variables']) : '' ?>">
                <p class="text-sm text-gray-500 mt-1">Comma-separated list of variables used in template</p>
            </div>
            
            <div class="flex justify-end space-x-2">
                <?php if ($editTemplate): ?>
                <a href="email_templates.php" class="py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                    Cancel
                </a>
                <?php endif; ?>
                
                <button type="submit" class="bg-red-900 text-white py-2 px-4 rounded-lg hover:bg-red-900 transition">
                    <i class="fas fa-save mr-2"></i> <?= $editTemplate ? 'Update' : 'Add' ?> Template
                </button>
            </div>
        </form>
    </div>
    
    <!-- Templates List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-md font-medium text-gray-800">Available Templates</h3>
        </div>
        
        <?php if (count($templates) > 0): ?>
        <ul class="divide-y">
            <?php foreach($templates as $template): ?>
            <li class="p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= $template['name'] ?></p>
                        <p class="text-sm text-gray-600"><?= $template['subject'] ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            Variables: <?= implode(', ', json_decode($template['variables'], true) ?: []) ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="?edit=<?= $template['id'] ?>" class="text-slate-950">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="#" class="text-red-600 delete-template" data-id="<?= $template['id'] ?>" data-name="<?= $template['name'] ?>">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            No email templates found
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Email Settings Link -->
    <div class="mt-4">
        <a href="email_settings.php" class="block text-center text-blue-600">
            <i class="fas fa-cog mr-1"></i> Configure Email Settings
        </a>
    </div>
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
        <div class="bg-red-900 text-white rounded-full w-12 h-12 flex items-center justify-center -mt-6 shadow-lg">
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
    // Delete template confirmation
    const deleteButtons = document.querySelectorAll('.delete-template');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const templateId = this.getAttribute('data-id');
            const templateName = this.getAttribute('data-name');
            
            if (confirm(`Are you sure you want to delete the "${templateName}" template?`)) {
                window.location.href = `?delete=${templateId}`;
            }
        });
    });
    
    // Preview functionality
    const bodyInput = document.getElementById('body');
    const variablesInput = document.getElementById('variables');
    
    // This could be expanded to show a real-time preview
    // with sample data for the variables
    bodyInput.addEventListener('input', function() {
        // Update preview logic could go here
    });
</script>

<?php
// Include footer
include $basePath . 'includes/footer.php';
?>