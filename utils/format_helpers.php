<?php
/**
 * Helper functions for formatting data
 */

/**
 * Format a number as Philippine Peso
 * @param float $amount The amount to format
 * @return string Formatted peso amount
 */
function formatPeso($amount) {
    return '₱' . number_format($amount, 2, '.', ',');
}

/**
 * Format a date to a readable string
 * @param string $date The date to format
 * @param string $format Optional format string
 * @return string Formatted date
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format payment method for display
 * @param string $method The payment method
 * @return string Formatted payment method
 */
function formatPaymentMethod($method) {
    $methods = [
        'bank_transfer' => 'Bank Transfer',
        'gcash' => 'GCash'
    ];
    return $methods[$method] ?? ucfirst($method);
} 