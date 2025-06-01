<?php
// Application configuration
define('APP_NAME', 'Oblatos Foundation');
define('APP_URL', 'http://localhost/oblatos-foundation');
define('APP_EMAIL', 'anton_philippe_olimpo@dlsl.edu.ph');

// Upload directory configuration
define('UPLOAD_DIR', 'uploads/');
define('RECEIPTS_DIR', UPLOAD_DIR . 'receipts/');

// Define donation tiers with PHP Peso amounts (monthly)
define('BLUE_TIER_MIN', 100);    // ₱100 - ₱990 monthly
define('BLUE_TIER_MAX', 990);
define('BRONZE_TIER_MIN', 1000);  // ₱1,000 - ₱4,990 monthly
define('BRONZE_TIER_MAX', 4990);
define('SILVER_TIER_MIN', 5000);  // ₱5,000 - ₱9,990 monthly
define('SILVER_TIER_MAX', 9990);
define('GOLD_TIER_MIN', 10000);   // ₱10,000+ monthly

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to format currency in Philippine Peso
function formatPeso($amount) {
    return '₱' . number_format($amount, 2);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Function to redirect
function redirect($path) {
    // Clean any existing output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Redirect using header
    header('Location: ' . APP_URL . '/' . $path);
    exit;
}

// Function to generate a random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Function to get donor tier based on monthly donation amount
function getDonorTier($monthlyAmount) {
    if ($monthlyAmount >= GOLD_TIER_MIN) {
        return 'Gold';
    } else if ($monthlyAmount >= SILVER_TIER_MIN) {
        return 'Silver';
    } else if ($monthlyAmount >= BRONZE_TIER_MIN) {
        return 'Bronze';
    } else if ($monthlyAmount >= BLUE_TIER_MIN) {
        return 'Blue';
    } else {
        return 'New Donor';
    }
}

// Function to get tier color class
function getTierColorClass($tier) {
    switch ($tier) {
        case 'Gold':
            return 'bg-yellow-500';
        case 'Silver':
            return 'bg-gray-300';
        case 'Bronze':
            return 'bg-amber-700';
        case 'Blue':
            return 'bg-blue-500';
        default:
            return 'bg-gray-400';
    }
}

// Function to get tier color name for Tailwind
function getTierColor($tier) {
    switch ($tier) {
        case 'Gold':
            return 'yellow';
        case 'Silver':
            return 'gray';
        case 'Bronze':
            return 'amber';
        case 'Blue':
            return 'blue';
        default:
            return 'gray';
    }
}

// Function to get tier description
function getTierDescription($tier) {
    switch ($tier) {
        case 'Gold':
            return '₱10,000 and above monthly';
        case 'Silver':
            return '₱5,000 - ₱9,990 monthly';
        case 'Bronze':
            return '₱1,000 - ₱4,990 monthly';
        case 'Blue':
            return '₱100 - ₱990 monthly';
        default:
            return 'Less than ₱100 monthly';
    }
}
?>