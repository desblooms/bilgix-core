-- Complete Database Schema for Billgix Inventory Management System
-- Version 1.1.0 - With Financial Tracking & Additional Tables

-- Create database (if not already created)
CREATE DATABASE IF NOT EXISTS u345095192_krumzdat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE u345095192_krumzdat;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    lastLogin DATETIME,
    resetToken VARCHAR(100) NULL,
    resetExpiry DATETIME NULL,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME
);

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contactPerson VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    gstNumber VARCHAR(50),
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME
);

-- Products table for inventory items
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendorId INT,
    itemCode VARCHAR(50) NOT NULL UNIQUE,
    itemName VARCHAR(100) NOT NULL,
    hsn VARCHAR(50),
    priceUnit DECIMAL(10, 2) NOT NULL,
    qty DECIMAL(10, 2) NOT NULL DEFAULT 0,
    unitType VARCHAR(20) NOT NULL DEFAULT 'Meter',
    totalPrice DECIMAL(10, 2),
    expense DECIMAL(10, 2) DEFAULT 0,
    shippingCost DECIMAL(10, 2) DEFAULT 0,
    gst DECIMAL(5, 2) DEFAULT 0,
    totalProductCost DECIMAL(10, 2),
    salePrice DECIMAL(10, 2) NOT NULL,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME,
    FOREIGN KEY (vendorId) REFERENCES vendors(id) ON DELETE SET NULL
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    gstNumber VARCHAR(50),
    openingBalance DECIMAL(10, 2) DEFAULT 0,
    balanceType ENUM('Advance', 'Due') DEFAULT 'Due',
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME
);

-- Sales table - enhanced with financial tracking
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoiceNumber VARCHAR(50) NOT NULL UNIQUE,
    customerId INT,
    totalPrice DECIMAL(10, 2) NOT NULL,
    costPrice DECIMAL(10, 2) DEFAULT 0,
    profitAmount DECIMAL(10, 2) DEFAULT 0,
    taxAmount DECIMAL(10, 2) DEFAULT 0,
    discountAmount DECIMAL(10, 2) DEFAULT 0,
    financialAccountId INT,
    paymentMethod VARCHAR(50) DEFAULT 'Cash',
    paymentStatus ENUM('Paid', 'Partial', 'Unpaid') DEFAULT 'Paid',
    paidAmount DECIMAL(10, 2) DEFAULT 0,
    dueAmount DECIMAL(10, 2) DEFAULT 0,
    invoicePath VARCHAR(255),
    notes TEXT,
    createdBy INT,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME,
    FOREIGN KEY (customerId) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (createdBy) REFERENCES users(id) ON DELETE SET NULL
);

-- Sale items table
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    saleId INT NOT NULL,
    productId INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (saleId) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE RESTRICT
);

-- Purchases table
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchaseNumber VARCHAR(50) NOT NULL UNIQUE,
    vendorId INT,
    totalAmount DECIMAL(10, 2) NOT NULL,
    paymentStatus ENUM('Paid', 'Partial', 'Unpaid') DEFAULT 'Paid',
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME,
    FOREIGN KEY (vendorId) REFERENCES vendors(id) ON DELETE SET NULL
);

-- Purchase items table
CREATE TABLE IF NOT EXISTS purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchaseId INT NOT NULL,
    productId INT NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (purchaseId) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE RESTRICT
);

-- Expense categories table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Expenses table - enhanced
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoryId INT,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    expenseDate DATE NOT NULL,
    paymentMethod VARCHAR(50) DEFAULT 'Cash',
    financialAccountId INT,
    reference VARCHAR(100),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    attachmentPath VARCHAR(255),
    createdBy INT,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME,
    FOREIGN KEY (categoryId) REFERENCES expense_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (createdBy) REFERENCES users(id) ON DELETE SET NULL
);

-- Inventory log table to track stock changes
CREATE TABLE IF NOT EXISTS inventory_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    productId INT NOT NULL,
    adjustmentType ENUM('add', 'remove') NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    previousQty DECIMAL(10, 2) NOT NULL,
    newQty DECIMAL(10, 2) NOT NULL,
    reason VARCHAR(100) NOT NULL,
    userId INT,
    createdAt DATETIME NOT NULL,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL
);

-- Settings table for application configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settingKey VARCHAR(50) NOT NULL UNIQUE,
    settingValue TEXT,
    settingGroup VARCHAR(50) DEFAULT 'general',
    updatedAt DATETIME
);

-- Financial accounts table
CREATE TABLE IF NOT EXISTS financial_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accountName VARCHAR(100) NOT NULL,
    accountType ENUM('cash', 'bank', 'credit', 'online') NOT NULL,
    description TEXT,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME
);

-- Opening balance table to track company funds at the start
CREATE TABLE IF NOT EXISTS opening_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    financialAccountId INT NOT NULL,
    balanceAmount DECIMAL(15, 2) NOT NULL,
    asOfDate DATE NOT NULL,
    notes TEXT,
    createdAt DATETIME NOT NULL,
    updatedAt DATETIME,
    FOREIGN KEY (financialAccountId) REFERENCES financial_accounts(id) ON DELETE RESTRICT
);

-- Transactions table for all financial movement
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transactionType ENUM('sale', 'purchase', 'expense', 'deposit', 'withdrawal', 'transfer') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    financialAccountId INT NOT NULL,
    referenceType VARCHAR(50) NOT NULL,
    referenceId INT NOT NULL,
    description TEXT,
    transactionDate DATETIME NOT NULL,
    createdBy INT,
    createdAt DATETIME NOT NULL,
    FOREIGN KEY (financialAccountId) REFERENCES financial_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (createdBy) REFERENCES users(id) ON DELETE SET NULL
);

-- Company finances table
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
);

-- Financial transactions table
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
);

-- Financial periods table
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
);

-- Profit tracking for each sale item
CREATE TABLE IF NOT EXISTS profit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    saleId INT NOT NULL,
    productId INT NOT NULL,
    saleItemId INT NOT NULL,
    quantitySold DECIMAL(10, 2) NOT NULL,
    costPrice DECIMAL(10, 2) NOT NULL,
    sellingPrice DECIMAL(10, 2) NOT NULL,
    totalCost DECIMAL(10, 2) NOT NULL,
    totalRevenue DECIMAL(10, 2) NOT NULL,
    grossProfit DECIMAL(10, 2) NOT NULL,
    profitMargin DECIMAL(5, 2) NOT NULL,
    saleDate DATETIME NOT NULL,
    createdAt DATETIME NOT NULL,
    FOREIGN KEY (saleId) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (saleItemId) REFERENCES sale_items(id) ON DELETE CASCADE
);

-- Enhanced expense tracking table
CREATE TABLE IF NOT EXISTS expense_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expenseId INT NOT NULL,
    itemDescription VARCHAR(255) NOT NULL,
    quantity DECIMAL(10, 2) DEFAULT 1,
    unitPrice DECIMAL(10, 2) NOT NULL,
    totalAmount DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (expenseId) REFERENCES expenses(id) ON DELETE CASCADE
);

-- Add indexes to improve query performance
CREATE INDEX IF NOT EXISTS idx_products_vendorid ON products(vendorId);
CREATE INDEX IF NOT EXISTS idx_products_itemcode ON products(itemCode);
CREATE INDEX IF NOT EXISTS idx_sales_customerid ON sales(customerId);
CREATE INDEX IF NOT EXISTS idx_sales_createdat ON sales(createdAt);
CREATE INDEX IF NOT EXISTS idx_sale_items_productid ON sale_items(productId);
CREATE INDEX IF NOT EXISTS idx_purchases_vendorid ON purchases(vendorId);
CREATE INDEX IF NOT EXISTS idx_purchases_createdat ON purchases(createdAt);
CREATE INDEX IF NOT EXISTS idx_purchase_items_productid ON purchase_items(productId);
CREATE INDEX IF NOT EXISTS idx_expenses_categoryid ON expenses(categoryId);
CREATE INDEX IF NOT EXISTS idx_expenses_expensedate ON expenses(expenseDate);
CREATE INDEX IF NOT EXISTS idx_inventory_log_productid ON inventory_log(productId);
CREATE INDEX IF NOT EXISTS idx_inventory_log_createdat ON inventory_log(createdAt);
CREATE INDEX IF NOT EXISTS idx_inventory_log_reason ON inventory_log(reason);
CREATE INDEX IF NOT EXISTS idx_settings_group ON settings(settingGroup);

-- Indexes for financial tables
CREATE INDEX IF NOT EXISTS idx_fin_transactions_date ON financial_transactions(transaction_date);
CREATE INDEX IF NOT EXISTS idx_fin_transactions_type ON financial_transactions(transaction_type, reference_type);
CREATE INDEX IF NOT EXISTS idx_fin_transactions_reference ON financial_transactions(reference_type, reference_id);
CREATE INDEX IF NOT EXISTS idx_opening_balances_account ON opening_balances(financialAccountId);
CREATE INDEX IF NOT EXISTS idx_opening_balances_date ON opening_balances(asOfDate);
CREATE INDEX IF NOT EXISTS idx_transactions_account ON transactions(financialAccountId);
CREATE INDEX IF NOT EXISTS idx_transactions_type ON transactions(transactionType);
CREATE INDEX IF NOT EXISTS idx_transactions_reference ON transactions(referenceType, referenceId);
CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(transactionDate);
CREATE INDEX IF NOT EXISTS idx_profit_log_sale ON profit_log(saleId);
CREATE INDEX IF NOT EXISTS idx_profit_log_product ON profit_log(productId);
CREATE INDEX IF NOT EXISTS idx_profit_log_date ON profit_log(saleDate);
CREATE INDEX IF NOT EXISTS idx_expense_details_expense ON expense_details(expenseId);

-- Create views for financial reporting
CREATE OR REPLACE VIEW vw_daily_revenue AS
SELECT 
    DATE(s.createdAt) AS saleDate,
    COUNT(s.id) AS transactionCount,
    SUM(s.totalPrice) AS totalRevenue
FROM 
    sales s
GROUP BY 
    DATE(s.createdAt)
ORDER BY 
    saleDate DESC;

CREATE OR REPLACE VIEW vw_daily_profit AS
SELECT 
    DATE(pl.saleDate) AS saleDate,
    SUM(pl.totalRevenue) AS totalRevenue,
    SUM(pl.totalCost) AS totalCost,
    SUM(pl.grossProfit) AS grossProfit,
    (SUM(pl.grossProfit) / NULLIF(SUM(pl.totalRevenue), 0)) * 100 AS profitMarginPercentage
FROM 
    profit_log pl
GROUP BY 
    DATE(pl.saleDate)
ORDER BY 
    saleDate DESC;

CREATE OR REPLACE VIEW vw_product_profitability AS
SELECT 
    p.id AS productId,
    p.itemName,
    p.itemCode,
    SUM(pl.quantitySold) AS totalQuantitySold,
    SUM(pl.totalRevenue) AS totalRevenue, 
    SUM(pl.totalCost) AS totalCost,
    SUM(pl.grossProfit) AS totalProfit,
    AVG(pl.profitMargin) AS avgProfitMargin
FROM 
    products p
LEFT JOIN 
    profit_log pl ON p.id = pl.productId
GROUP BY 
    p.id, p.itemName, p.itemCode
ORDER BY 
    totalProfit DESC;

-- Insert default expense categories
INSERT IGNORE INTO expense_categories (name, description) VALUES
('Rent', 'Office or store rent payments'),
('Utilities', 'Water, electricity, internet bills'),
('Salaries', 'Employee salaries and wages'),
('Transportation', 'Delivery and transportation costs'),
('Supplies', 'Office supplies and non-inventory items'),
('Marketing', 'Advertising and promotional expenses'),
('Maintenance', 'Equipment and premises maintenance'),
('Miscellaneous', 'Other uncategorized expenses');

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (username, password, name, email, role, active, createdAt) VALUES
('admin', '$2y$10$rBnPNiZCffwOm5l2dAZhTe8QvFojjP4/YpbZH1lz4XSx9ywbZz4m.', 'Admin User', 'admin@example.com', 'admin', TRUE, NOW());

-- Insert default settings
INSERT IGNORE INTO settings (settingKey, settingValue, settingGroup, updatedAt) VALUES
('company_name', 'Krums Foods', 'company', NOW()),
('company_email', 'krumsfoods@gmail.com', 'company', NOW()),
('company_phone', '+917994588288', 'company', NOW()),
('company_address', '123 Business Street, City, Country', 'company', NOW()),
('currency', '₹', 'regional', NOW()),
('date_format', 'Y-m-d', 'regional', NOW()),
('low_stock_threshold', '5', 'inventory', NOW()),
('invoice_prefix', 'INV-', 'invoice', NOW()),
('purchase_prefix', 'PO-', 'invoice', NOW()),
('enable_sms', '0', 'notification', NOW()),
('enable_pdf', '1', 'invoice', NOW()),
('financial_year_start', '04-01', 'financial', NOW()),
('tax_rate', '18', 'financial', NOW()),
('enable_profit_tracking', '1', 'financial', NOW());

-- Insert default financial accounts
INSERT IGNORE INTO financial_accounts (accountName, accountType, description, createdAt) VALUES
('Cash Register', 'cash', 'Primary cash register', NOW()),
('Main Bank Account', 'bank', 'Primary business bank account', NOW()),
('Online Payments', 'online', 'For UPI and other online payments', NOW());

-- Initialize company finances with default data
INSERT IGNORE INTO company_finances (
    opening_balance, current_balance, total_revenue, 
    total_expenses, total_profit, last_updated,
    financial_year_start, financial_year_end, notes
) VALUES (
    0, 0, 0, 
    0, 0, NOW(),
    CONCAT(YEAR(CURRENT_DATE), '-04-01'), 
    CONCAT(YEAR(CURRENT_DATE) + 1, '-03-31'), 
    'Initial setup'
);

-- Helpful message
SELECT 'Installation completed successfully!' AS Message;