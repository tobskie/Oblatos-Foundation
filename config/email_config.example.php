<?php
// Email Configuration Example
// Copy this file to email_config.php and update with your settings
return [
    'debug' => false,  // Set to true to enable debug output
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'username' => APP_EMAIL,
        'password' => 'your_app_password_here',  // Replace with your actual app password
        'encryption' => PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
        'port' => 587,
        'from_email' => APP_EMAIL,
        'from_name' => APP_NAME
    ]
]; 