<?php
/**
 * Finance Tables Installation Script
 * 
 * This script adds the required finance-related tables to the database
 * It should be run after the initial database setup
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
$basePath = '../';
require_once $basePath . 'includes/config.php';
require_once $basePath . 'includes/db.php';

// Banner
echo "=================================================\n";
echo "  Financial Tables Installation\n";
echo "=================================================\n\n";

// Check if running from browser or command line
$isCLI = (php_sapi_name() === 'cli');
if (!$isCLI) {
    echo "<pre>";
}

// Function to execute SQL query and handle errors
function executeSQL($db, $sql, $description) {
    global $isCLI;
    
    try {
        $db->query($sql);
        echo "✓ $description - Success\n";
        return true;
    } catch (Exception $e) {
        echo "✗ $description - Failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Start installation
echo "Starting installation of financial tables...\n\n";

// Create company_finances table
$sql = "
CREATE TABLE IF NOT EXISTS company_finances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opening_balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
    current_balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total_revenue DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total_expenses DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total_profit DECIMAL(10, 2) NOT NULL DEFAULT 0,
    last_updated DATETIME NOT NULL,
    financial_year_start DATE NOT NULL,
    financial_year_end DATE NOT NULL,
    notes TEXT
)";
executeSQL($db, $sql, "Creating company_finances table");

// Create financial_periods table
$sql = "
CREATE TABLE IF NOT EXISTS financial_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_type ENUM('monthly', 'quarterly', 'yearly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    opening_balance DECIMAL(10, 2) NOT NULL,
    closing_balance DECIMAL(10, 2) NOT NULL,
    revenue DECIMAL(10, 2) NOT NULL DEFAULT 0,
    expenses DECIMAL(10, 2) NOT NULL DEFAULT 0, 
    profit DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at DATETIME NOT NULL,
    closed_at DATETIME
)";
executeSQL($db, $sql, "Creating financial_periods table");

// Create financial_transactions table
$sql = "
CREATE TABLE IF NOT EXISTS financial_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATETIME NOT NULL,
    transaction_type ENUM('sale', 'purchase', 'expense', 'income', 'adjustment') NOT NULL,
    reference_type VARCHAR(50) NOT NULL,
    reference_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    current_balance DECIMAL(10, 2) NOT NULL,
    description TEXT,
    created_by INT,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
)";
executeSQL($db, $sql, "Creating financial_transactions table");

// Add indexes for better performance
$sql = "CREATE INDEX IF NOT EXISTS idx_fin_transactions_date ON financial_transactions(transaction_date)";
executeSQL($db, $sql, "Creating transaction date index");

$sql = "CREATE INDEX IF NOT EXISTS idx_fin_transactions_type ON financial_transactions(transaction_type, reference_type)";
executeSQL($db, $sql, "Creating transaction type index");

$sql = "CREATE INDEX IF NOT EXISTS idx_fin_transactions_reference ON financial_transactions(reference_type, reference_id)";
executeSQL($db, $sql, "Creating reference index");

// Initialize company finances with default data
$sql = "SELECT COUNT(*) as count FROM company_finances";
$result = $db->select($sql);

if ($result[0]['count'] == 0) {
    // Set default financial year (April to March in many countries)
    $currentYear = date('Y');
    $financialYearStart = date('Y-04-01'); // April 1st
    $financialYearEnd = date('Y-03-31', strtotime('+1 year')); // March 31st next year
    
    $sql = "INSERT INTO company_finances (
                opening_balance, current_balance, total_revenue, 
                total_expenses, total_profit, last_updated,
                financial_year_start, financial_year_end, notes
            ) VALUES (
                0, 0, 0, 
                0, 0, NOW(),
                '$financialYearStart', '$financialYearEnd', 'Initial setup'
            )";
    executeSQL($db, $sql, "Initializing company finances with default data");
}

// Create directory structure if needed
$financesDir = $basePath . 'modules/finances';
if (!is_dir($financesDir)) {
    if (mkdir($financesDir, 0755, true)) {
        echo "✓ Created finances module directory\n";
    } else {
        echo "✗ Failed to create finances module directory\n";
    }
}

// Installation complete
echo "\nFinancial tables installation complete!\n";
echo "=================================================\n";

// Instructions
echo "\nNext steps:\n";
echo "1. Copy the financial handler to: {$basePath}includes/finance_handler.php\n";
echo "2. Add financial transaction recording to the sales, expenses, and purchase modules\n";
echo "3. Create the financial transaction report page\n";
echo "4. Update the dashboard to show financial metrics\n\n";

if (!$isCLI) {
    echo "</pre>";
    echo '<p><a href="../index.php" class="btn">Return to Dashboard</a></p>';
}
?>