<?php
/**
 * Configuration file for Inventory Management Mobile App
 * 
 * This file contains all the configuration settings for the application
 * including database connection, application settings, and global constants.
 */

// Database configuration - Keep these constant
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');    
if (!defined('DB_USER')) define('DB_USER', 'u345095192_krumzuse');          
if (!defined('DB_PASS')) define('DB_PASS', 'Krumz@788');             
if (!defined('DB_NAME')) define('DB_NAME', 'u345095192_krumzdat'); 

// File paths - Keep these constant
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('INCLUDES_PATH')) define('INCLUDES_PATH', ROOT_PATH . 'includes/');
if (!defined('MODULES_PATH')) define('MODULES_PATH', ROOT_PATH . 'modules/');
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Session settings
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Error reporting (set to 0 for production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('UTC');

// Security settings
if (!defined('HASH_COST')) define('HASH_COST', 10); // For password hashing

// Low stock threshold
if (!defined('LOW_STOCK_THRESHOLD')) define('LOW_STOCK_THRESHOLD', 5);

// Connect to database and load dynamic settings
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get company settings from database
    $stmt = $pdo->query("SELECT settingKey, settingValue FROM settings");
    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Define dynamic constants based on settings from DB, with fallbacks
    if (!defined('APP_NAME')) define('APP_NAME', 'Billgix');
    if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');
    if (!defined('CURRENCY')) define('CURRENCY', $dbSettings['CURRENCY'] ?? '₹');
    if (!defined('COMPANY_NAME')) define('COMPANY_NAME', $dbSettings['company_name'] ?? 'Krumz Foods');
    if (!defined('COMPANY_EMAIL')) define('COMPANY_EMAIL', $dbSettings['company_email'] ?? 'krumsbakery@gmail.com');
    if (!defined('COMPANY_PHONE')) define('COMPANY_PHONE', $dbSettings['company_phone'] ?? '+91 7994 588 288');
    if (!defined('COMPANY_ADDRESS')) define('COMPANY_ADDRESS', $dbSettings['company_address'] ?? 'Valiyakunnu, Valanchery, Kerala;');
    
    // Close the connection
    $pdo = null;
    
} catch (PDOException $e) {
    // If DB connection fails, use fallback values
    if (!defined('APP_NAME')) define('APP_NAME', 'Billgix');
    if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');
    if (!defined('CURRENCY')) define('CURRENCY',  '₹');
    if (!defined('COMPANY_NAME')) define('COMPANY_NAME', 'Krumz Foods');
    if (!defined('COMPANY_EMAIL')) define('COMPANY_EMAIL', 'krumsbakery@gmail.com');
    if (!defined('COMPANY_PHONE')) define('COMPANY_PHONE', '+91 7994 588 288');
    if (!defined('COMPANY_ADDRESS')) define('COMPANY_ADDRESS', 'Valiyakunnu, Valanchery, Kerala;');
    
    // Log the error
    error_log('Database connection failed in config.php: ' . $e->getMessage());
}
?>