<?php
/**
 * Debug Logging Utility
 * 
 * This file contains functions for logging debug information,
 * particularly useful for tracking file upload issues.
 */

/**
 * Log debug information to a file
 * 
 * @param string $message Message to log
 * @param array|string $data Additional data to log (optional)
 * @return bool Whether the log was written successfully
 */
function debug_log($message, $data = null) {
    $log_dir = dirname(__DIR__) . '/logs';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            return false;
        }
    }
    
    $log_file = $log_dir . '/debug_' . date('Y-m-d') . '.log';
    
    // Format the log message
    $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    
    // Add data if provided
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_message .= PHP_EOL . json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $log_message .= PHP_EOL . $data;
        }
    }
    
    $log_message .= PHP_EOL . '-----------------------------' . PHP_EOL;
    
    // Write to log file
    return file_put_contents($log_file, $log_message, FILE_APPEND) !== false;
}

/**
 * Log file upload errors
 * 
 * @param array $file $_FILES array entry
 * @return void
 */
function log_upload_error($file) {
    $error_messages = [
        UPLOAD_ERR_OK => 'No error, file uploaded successfully',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];
    
    $error_code = $file['error'];
    $error_message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'Unknown upload error';
    
    $log_data = [
        'error_code' => $error_code,
        'error_message' => $error_message,
        'file_name' => $file['name'] ?? 'Unknown',
        'file_size' => $file['size'] ?? 'Unknown',
        'file_tmp' => $file['tmp_name'] ?? 'Unknown',
        'file_type' => $file['type'] ?? 'Unknown'
    ];
    
    debug_log('File Upload Error', $log_data);
}
?>
