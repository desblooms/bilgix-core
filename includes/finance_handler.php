<?php
// includes/finance_handler.php
require_once 'db.php';
require_once 'config.php';
require_once 'functions.php';

/**
 * Finance Handler Class
 * Manages company financial records and transactions
 */
class FinanceHandler {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Record a financial transaction
     * 
     * @param string $transactionType Type of transaction (sale, purchase, expense, income, adjustment)
     * @param string $referenceType The source of transaction (sale, purchase, expense)
     * @param int $referenceId ID of the source transaction
     * @param float $amount Transaction amount (positive for income, negative for expenses)
     * @param string $description Transaction description
     * @param int $userId User who created the transaction
     * @return int|false Transaction ID if successful, false otherwise
     */
    public function recordTransaction($transactionType, $referenceType, $referenceId, $amount, $description = '', $userId = null) {
        // Get current balance
        $companyFinances = $this->db->select("SELECT current_balance FROM company_finances ORDER BY id DESC LIMIT 1");
        
        if (empty($companyFinances)) {
            // Create default record if none exists
            $this->initializeCompanyFinances();
            $currentBalance = 0;
        } else {
            $currentBalance = $companyFinances[0]['current_balance'];
        }
        
        // Calculate new balance
        $newBalance = $currentBalance + $amount;
        
        // Record transaction
        $transactionData = [
            'transaction_date' => date('Y-m-d H:i:s'),
            'transaction_type' => $transactionType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'amount' => $amount,
            'current_balance' => $newBalance,
            'description' => $description,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $transactionId = $this->db->insert('financial_transactions', $transactionData);
        
        if ($transactionId) {
            // Update company finances
            $this->updateCompanyFinances($amount, $transactionType);
            return $transactionId;
        }
        
        return false;
    }
    
    /**
     * Update company finances based on transaction
     * 
     * @param float $amount Transaction amount
     * @param string $transactionType Type of transaction
     * @return bool Success status
     */
    private function updateCompanyFinances($amount, $transactionType) {
        // Get current finances
        $companyFinances = $this->db->select("SELECT * FROM company_finances ORDER BY id DESC LIMIT 1");
        
        if (empty($companyFinances)) {
            $this->initializeCompanyFinances();
            $companyFinances = $this->db->select("SELECT * FROM company_finances ORDER BY id DESC LIMIT 1");
        }
        
        $finances = $companyFinances[0];
        
        // Update totals based on transaction type
        $newCurrentBalance = $finances['current_balance'] + $amount;
        $newTotalRevenue = $finances['total_revenue'];
        $newTotalExpenses = $finances['total_expenses'];
        
        if ($transactionType == 'sale' || $transactionType == 'income') {
            $newTotalRevenue += $amount;
        } else if ($transactionType == 'purchase' || $transactionType == 'expense') {
            $newTotalExpenses += abs($amount);
        }
        
        // Calculate profit
        $newTotalProfit = $newTotalRevenue - $newTotalExpenses;
        
        // Update company finances
        $updateData = [
            'current_balance' => $newCurrentBalance,
            'total_revenue' => $newTotalRevenue,
            'total_expenses' => $newTotalExpenses,
            'total_profit' => $newTotalProfit,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('company_finances', $updateData, 'id = :id', ['id' => $finances['id']]);
    }
    
    /**
     * Initialize company finances with default values
     * 
     * @return int|false ID of the created record or false on failure
     */
    public function initializeCompanyFinances() {
        // Set default financial year (April to March in many countries)
        $currentYear = date('Y');
        $financialYearStart = date('Y-04-01'); // April 1st
        $financialYearEnd = date('Y-03-31', strtotime('+1 year')); // March 31st next year
        
        $data = [
            'opening_balance' => 0,
            'current_balance' => 0,
            'total_revenue' => 0,
            'total_expenses' => 0,
            'total_profit' => 0,
            'last_updated' => date('Y-m-d H:i:s'),
            'financial_year_start' => $financialYearStart,
            'financial_year_end' => $financialYearEnd,
            'notes' => 'Initial setup'
        ];
        
        return $this->db->insert('company_finances', $data);
    }
    
    /**
     * Get financial transactions with filtering options
     * 
     * @param array $filters Filtering options
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array Transactions
     */
    public function getTransactions($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT ft.*, u.username 
                FROM financial_transactions ft 
                LEFT JOIN users u ON ft.created_by = u.id ";
        
        $params = [];
        $whereClauses = [];
        
        // Apply filters
        if (!empty($filters['transaction_type'])) {
            $whereClauses[] = "ft.transaction_type = :transaction_type";
            $params['transaction_type'] = $filters['transaction_type'];
        }
        
        if (!empty($filters['reference_type'])) {
            $whereClauses[] = "ft.reference_type = :reference_type";
            $params['reference_type'] = $filters['reference_type'];
        }
        
        if (!empty($filters['reference_id'])) {
            $whereClauses[] = "ft.reference_id = :reference_id";
            $params['reference_id'] = $filters['reference_id'];
        }
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $whereClauses[] = "DATE(ft.transaction_date) BETWEEN :start_date AND :end_date";
            $params['start_date'] = $filters['start_date'];
            $params['end_date'] = $filters['end_date'];
        } else if (!empty($filters['start_date'])) {
            $whereClauses[] = "DATE(ft.transaction_date) >= :start_date";
            $params['start_date'] = $filters['start_date'];
        } else if (!empty($filters['end_date'])) {
            $whereClauses[] = "DATE(ft.transaction_date) <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        
        // Combine WHERE clauses
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Order and limit
        $sql .= " ORDER BY ft.transaction_date DESC";
        
        if ($limit > 0) {
            // Direct integer interpolation for LIMIT clause instead of using parameter binding
            $sql .= " LIMIT " . (int)$offset . ", " . (int)$limit;
            // Parameters removed as they're no longer needed
        }
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Get financial summary for dashboard
     * 
     * @return array Financial summary
     */
    public function getFinancialSummary() {
        // Get company finances
        $companyFinances = $this->db->select("SELECT * FROM company_finances ORDER BY id DESC LIMIT 1");
        
        if (empty($companyFinances)) {
            $this->initializeCompanyFinances();
            $companyFinances = $this->db->select("SELECT * FROM company_finances ORDER BY id DESC LIMIT 1");
        }
        
        $finances = $companyFinances[0];
        
        // Get today's figures
        $todaySales = $this->db->select("SELECT SUM(amount) as total FROM financial_transactions 
                                       WHERE transaction_type = 'sale' 
                                       AND DATE(transaction_date) = CURDATE()");
        
        $todayExpenses = $this->db->select("SELECT SUM(ABS(amount)) as total FROM financial_transactions 
                                          WHERE (transaction_type = 'expense' OR transaction_type = 'purchase')
                                          AND DATE(transaction_date) = CURDATE()");
        
        $todayProfit = ($todaySales[0]['total'] ?? 0) - ($todayExpenses[0]['total'] ?? 0);
        
        // Get this month's figures
        $monthSales = $this->db->select("SELECT SUM(amount) as total FROM financial_transactions 
                                       WHERE transaction_type = 'sale' 
                                       AND MONTH(transaction_date) = MONTH(CURDATE())
                                       AND YEAR(transaction_date) = YEAR(CURDATE())");
        
        $monthExpenses = $this->db->select("SELECT SUM(ABS(amount)) as total FROM financial_transactions 
                                          WHERE (transaction_type = 'expense' OR transaction_type = 'purchase')
                                          AND MONTH(transaction_date) = MONTH(CURDATE())
                                          AND YEAR(transaction_date) = YEAR(CURDATE())");
        
        $monthProfit = ($monthSales[0]['total'] ?? 0) - ($monthExpenses[0]['total'] ?? 0);
        
        // Return summary data
        return [
            'opening_balance' => $finances['opening_balance'],
            'current_balance' => $finances['current_balance'],
            'total_revenue' => $finances['total_revenue'],
            'total_expenses' => $finances['total_expenses'],
            'total_profit' => $finances['total_profit'],
            'today_sales' => $todaySales[0]['total'] ?? 0,
            'today_expenses' => $todayExpenses[0]['total'] ?? 0,
            'today_profit' => $todayProfit,
            'month_sales' => $monthSales[0]['total'] ?? 0,
            'month_expenses' => $monthExpenses[0]['total'] ?? 0,
            'month_profit' => $monthProfit,
            'financial_year_start' => $finances['financial_year_start'],
            'financial_year_end' => $finances['financial_year_end'],
            'last_updated' => $finances['last_updated']
        ];
    }
}

// Create global instance
global $db;
$financeHandler = new FinanceHandler($db);

/**
 * Helper function to record financial transaction
 * 
 * @param string $transactionType Type of transaction
 * @param string $referenceType Reference type
 * @param int $referenceId Reference ID
 * @param float $amount Amount
 * @param string $description Description
 * @param int $userId User ID
 * @return int|false Transaction ID or false
 */
function recordFinancialTransaction($transactionType, $referenceType, $referenceId, $amount, $description = '', $userId = null) {
    global $financeHandler;
    
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    return $financeHandler->recordTransaction($transactionType, $referenceType, $referenceId, $amount, $description, $userId);
}

/**
 * Get financial summary for dashboard
 * 
 * @return array Financial summary
 */
function getFinancialSummary() {
    global $financeHandler;
    return $financeHandler->getFinancialSummary();
}
?>