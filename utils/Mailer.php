<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Mailer Utility Class
 * 
 * This class provides email functionality for the Oblatos Foundation system
 */
class Mailer {
    // Default sender information
    private static $fromEmail;
    private static $fromName;
    
    // Log file path
    private static $logFile = __DIR__ . '/../logs/mail.log';
    
    /**
     * Initialize static properties
     */
    public static function init() {
        self::$fromEmail = APP_EMAIL;
        self::$fromName = APP_NAME;
    }
    
    /**
     * Log email sending attempts
     */
    private static function log($message, $data = []) {
        try {
            $logDir = dirname(self::$logFile);
            
            // Create logs directory if it doesn't exist
            if (!is_dir($logDir)) {
                if (!mkdir($logDir, 0755, true)) {
                    error_log("Failed to create logs directory: $logDir");
                    return;
                }
                
                // Set proper permissions on the logs directory
                chmod($logDir, 0755);
            }
            
            // Ensure log file exists and is writable
            if (!file_exists(self::$logFile)) {
                touch(self::$logFile);
                chmod(self::$logFile, 0644);
            }
            
            if (!is_writable(self::$logFile)) {
                error_log("Log file is not writable: " . self::$logFile);
                return;
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] $message";
            
            if (!empty($data)) {
                $logMessage .= "\nData: " . json_encode($data, JSON_PRETTY_PRINT);
            }
            
            $logMessage .= "\n" . str_repeat('-', 50) . "\n";
            
            if (!error_log($logMessage, 3, self::$logFile)) {
                error_log("Failed to write to log file: " . self::$logFile);
            }
        } catch (Exception $e) {
            error_log("Logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Configure and return a PHPMailer instance
     */
    private static function getMailer() {
        // Load email configuration
        $config = require_once __DIR__ . '/../config/email_config.php';
        
        // Validate configuration
        if (empty($config['smtp']['host']) || empty($config['smtp']['username']) || 
            empty($config['smtp']['password']) || $config['smtp']['password'] === 'ENTER_YOUR_16_CHAR_APP_PASSWORD') {
            self::log("Invalid SMTP configuration: Missing required fields");
            throw new Exception("Invalid SMTP configuration: Please check your email settings");
        }
        
        // Create PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            if ($config['debug']) {
                $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) {
                    self::log("DEBUG [$level]: $str");
                };
            }
            
            $mail->isSMTP();
            $mail->Host = $config['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp']['username'];
            $mail->Password = $config['smtp']['password'];
            $mail->SMTPSecure = $config['smtp']['encryption'];
            $mail->Port = $config['smtp']['port'];
            
            // Set timeout
            $mail->Timeout = 30; // 30 seconds timeout
            
            // Enable UTF-8 support
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Default sender
            $mail->setFrom(
                $config['smtp']['from_email'] ?? self::$fromEmail,
                $config['smtp']['from_name'] ?? self::$fromName
            );
            
            return $mail;
        } catch (Exception $e) {
            self::log("Failed to configure mailer: " . $e->getMessage());
            throw new Exception("Failed to configure email system: " . $e->getMessage());
        }
    }
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param string $from Sender email (optional)
     * @param string $from_name Sender name (optional)
     * @return bool True if email sent successfully, false otherwise
     * @throws Exception If email sending fails
     */
    public static function send($to, $subject, $message, $from = null, $from_name = null) {
        if (empty($to) || empty($subject) || empty($message)) {
            self::log("Invalid email parameters: Missing required fields");
            throw new Exception("Invalid email parameters: recipient, subject, and message are required");
        }

        try {
            $mail = self::getMailer();
            
            // Override sender if provided
            if ($from && $from_name) {
                $mail->setFrom($from, $from_name);
            }
            
            // Validate recipient email
            if (!PHPMailer\PHPMailer\PHPMailer::validateAddress($to)) {
                throw new Exception("Invalid recipient email address: $to");
            }
            
            // Recipient
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            
            // Enable debug mode temporarily
            $mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                self::log("SMTP Debug [$level]: $str");
            };
            
            // Log attempt
            self::log("Attempting to send email", [
                'to' => $to,
                'subject' => $subject,
                'from' => $from_name ? "$from_name <$from>" : self::$fromName . " <" . self::$fromEmail . ">",
                'smtp_host' => $mail->Host,
                'smtp_user' => $mail->Username
            ]);
            
            // Send email
            $result = $mail->send();
            
            if ($result) {
                self::log("Email sent successfully to: $to");
            } else {
                throw new Exception($mail->ErrorInfo);
            }
            
            return $result;
        } catch (Exception $e) {
            $errorMessage = "Error sending email: " . $e->getMessage();
            self::log($errorMessage);
            throw new Exception($errorMessage);
        }
    }
    
    /**
     * Send donation verification email to donor
     * 
     * @param string $to Donor email
     * @param string $donor_name Donor name
     * @param string $donation_id Donation ID
     * @param float $amount Donation amount
     * @param string $date Donation date
     * @param string $status Donation status (verified/rejected)
     * @param string $cashier_name Name of cashier who verified the donation
     * @return bool True if email sent successfully, false otherwise
     */
    public static function sendDonationStatusEmail($to, $donor_name, $donation_id, $amount, $date, $status, $cashier_name) {
        $subject = "Donation #$donation_id " . ucfirst($status);
        
        // Format amount
        $formatted_amount = number_format($amount, 2, '.', ',');
        
        // Email content based on status
        if ($status === 'verified') {
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Donation Verified</h2>
                    </div>
                    <div class='content'>
                        <p>Dear $donor_name,</p>
                        <p>We are pleased to inform you that your donation has been verified and processed. Thank you for your generous contribution to Oblatos Foundation!</p>
                        <p><strong>Donation Details:</strong></p>
                        <ul>
                            <li>Donation ID: #$donation_id</li>
                            <li>Amount: ₱$formatted_amount</li>
                            <li>Date: $date</li>
                            <li>Status: Verified</li>
                        </ul>
                        <p>Your donation will help us continue our mission and make a difference in the lives of those we serve.</p>
                        <p>If you have any questions, please reply to this email or contact our office.</p>
                        <p>Sincerely,<br>$cashier_name<br>Oblatos Foundation</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply directly to this message.</p>
                        <p>&copy; " . date('Y') . " Oblatos Foundation. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
        } else {
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #F44336; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Donation Rejected</h2>
                    </div>
                    <div class='content'>
                        <p>Dear $donor_name,</p>
                        <p>We regret to inform you that your recent donation could not be processed due to verification issues.</p>
                        <p><strong>Donation Details:</strong></p>
                        <ul>
                            <li>Donation ID: #$donation_id</li>
                            <li>Amount: ₱$formatted_amount</li>
                            <li>Date: $date</li>
                            <li>Status: Rejected</li>
                        </ul>
                        <p>This may be due to incomplete payment information, unmatched reference number, or other verification issues. Please contact our office for more information and assistance in resolving this matter.</p>
                        <p>Sincerely,<br>$cashier_name<br>Oblatos Foundation</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply directly to this message.</p>
                        <p>&copy; " . date('Y') . " Oblatos Foundation. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
        }
        
        return self::send($to, $subject, $message);
    }
    
    /**
     * Send a custom email to a donor
     * 
     * @param string $to Donor email
     * @param string $donor_name Donor name
     * @param string $subject Email subject
     * @param string $message Email body
     * @param string $cashier_name Name of cashier sending the email
     * @return bool True if email sent successfully, false otherwise
     */
    public static function sendCustomDonorEmail($to, $donor_name, $subject, $message, $cashier_name) {
        $html_message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2196F3; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Message from Oblatos Foundation</h2>
                </div>
                <div class='content'>
                    <p>Dear $donor_name,</p>
                    <div>" . nl2br(htmlspecialchars($message)) . "</div>
                    <p>Sincerely,<br>$cashier_name<br>Oblatos Foundation</p>
                </div>
                <div class='footer'>
                    <p>If you have any questions, please contact our office.</p>
                    <p>&copy; " . date('Y') . " Oblatos Foundation. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($to, $subject, $html_message);
    }
    
    /**
     * Send password reset email
     * 
     * @param string $to User email
     * @param string $user_name User's full name
     * @param string $reset_token Password reset token
     * @param string $reset_link Full password reset link
     * @return bool True if email sent successfully, false otherwise
     */
    public static function sendPasswordResetEmail($to, $user_name, $reset_token, $reset_link) {
        $subject = "Password Reset Request - Oblatos Foundation";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #3F51B5; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .button { display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
                .token { background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; font-family: monospace; word-break: break-all; }
                .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Dear $user_name,</p>
                    <p>We received a request to reset the password for your account at Oblatos Foundation. To reset your password, please click the button below:</p>
                    <p style='text-align: center;'>
                        <a href='$reset_link' class='button' style='color: white; text-decoration: none;'>Reset Password</a>
                    </p>
                    <p>If the button doesn't work, please copy and paste the following URL into your browser:</p>
                    <p class='token'>$reset_link</p>
                    <p>This link will expire in 24 hours for security reasons.</p>
                    <p>If you did not request a password reset, please ignore this email or contact us if you have concerns about your account security.</p>
                    <p>Sincerely,<br>Oblatos Foundation Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply directly to this message.</p>
                    <p>&copy; " . date('Y') . " Oblatos Foundation. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return self::send($to, $subject, $message);
    }
}
?>
