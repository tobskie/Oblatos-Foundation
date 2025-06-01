<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Donation.php';
require_once '../utils/debug_log.php';

// Check if user is logged in and is a donor
if (!isLoggedIn() || !hasRole('donor')) {
    redirect('auth/login.php');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';
    $reference_number = $_POST['reference_number'] ?? '';
    
    // Validate form data
    $errors = [];
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Please enter a valid donation amount';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'Please select a payment method';
    }
    
    if (empty($reference_number)) {
        $errors[] = 'Please enter a reference number';
    }
    
    // Handle file upload
    $receipt_image = '';
    
    if (isset($_FILES['receipt_image'])) {
        // Log upload debug information
        debug_log('File upload attempt', [
            'filename' => $_FILES['receipt_image']['name'] ?? 'Unknown',
            'error_code' => $_FILES['receipt_image']['error'] ?? 'Unknown',
            'tmp_name' => $_FILES['receipt_image']['tmp_name'] ?? 'Not set',
            'size' => $_FILES['receipt_image']['size'] ?? 0
        ]);
        
        // Check for upload errors and provide specific error messages
        if ($_FILES['receipt_image']['error'] !== UPLOAD_ERR_OK) {
            // Log the specific error
            log_upload_error($_FILES['receipt_image']);
            
            switch ($_FILES['receipt_image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'The receipt image is too large. Please upload a smaller file (max 2MB).';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'The receipt image was only partially uploaded. Please try again.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'Please upload a receipt image.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errors[] = 'Server error: Missing a temporary folder. Please contact support.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = 'Server error: Failed to write file to disk. Please contact support.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errors[] = 'Server error: A PHP extension stopped the file upload. Please contact support.';
                    break;
                default:
                    $errors[] = 'There was an error uploading your receipt image. Please try again. (Error code: ' . $_FILES['receipt_image']['error'] . ')';
            }
        } else {
            // File was successfully uploaded to temporary location
            $file_tmp = $_FILES['receipt_image']['tmp_name'];
            $file_name = $_FILES['receipt_image']['name'];
            $file_size = $_FILES['receipt_image']['size'];
            $file_type = $_FILES['receipt_image']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Check file extension
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $errors[] = 'Please upload a valid image file (JPG, JPEG, PNG, GIF)';
                debug_log('Invalid file extension', $file_ext);
            } 
            // Check file size (2MB max)
            elseif ($file_size > 2097152) {
                $errors[] = 'The receipt image is too large. Please upload a smaller file (max 2MB).';
                debug_log('File too large', $file_size);
            } 
            // Check if file is actually an image
            elseif (!getimagesize($file_tmp)) {
                $errors[] = 'The uploaded file is not a valid image. Please upload a proper image file.';
                debug_log('Invalid image file', ['tmp_name' => $file_tmp, 'type' => $file_type]);
            } else {
                // Generate unique filename
                $new_file_name = generateRandomString(10) . '_' . time() . '.' . $file_ext;
                
                // Ensure upload directory exists with proper path
                $upload_dir = dirname(__DIR__) . '/' . UPLOAD_DIR;
                debug_log('Using upload directory', $upload_dir);
                
                // Check if directory exists, if not create it
                if (!file_exists($upload_dir)) {
                    debug_log('Upload directory does not exist, creating it', $upload_dir);
                    if (!mkdir($upload_dir, 0777, true)) {
                        $error_msg = 'Failed to create upload directory. Please contact support.';
                        $errors[] = $error_msg;
                        debug_log($error_msg, ['dir' => $upload_dir, 'error' => error_get_last()]);
                    }
                }
                
                // Check directory permissions
                if (!is_writable($upload_dir)) {
                    debug_log('Upload directory is not writable', [
                        'dir' => $upload_dir, 
                        'permissions' => substr(sprintf('%o', fileperms($upload_dir)), -4)
                    ]);
                    
                    // Try to fix permissions
                    @chmod($upload_dir, 0777);
                    
                    if (!is_writable($upload_dir)) {
                        $error_msg = 'Upload directory permissions issue. Please contact support.';
                        $errors[] = $error_msg;
                        debug_log($error_msg, $upload_dir);
                    }
                }
                
                if (empty($errors)) {
                    $upload_path = $upload_dir . $new_file_name;
                    debug_log('Attempting to move uploaded file', ['from' => $file_tmp, 'to' => $upload_path]);
                    
                    // Try to move the uploaded file
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $receipt_image = $new_file_name;
                        debug_log('File uploaded successfully', $new_file_name);
                    } else {
                        $error_msg = 'Failed to move uploaded file. Please try again.';
                        $errors[] = $error_msg;
                        debug_log($error_msg, [
                            'from' => $file_tmp, 
                            'to' => $upload_path, 
                            'last_error' => error_get_last()
                        ]);
                    }
                }
            }
        }
    } else {
        $errors[] = 'Please upload a receipt image';
        debug_log('No file uploaded', ['$_FILES' => isset($_FILES) ? 'Set' : 'Not set']);
    }
    
    // If no errors, save donation to database
    if (empty($errors)) {
        // Get database connection
        $database = new Database();
        $db = $database->getConnection();
        
        // Create donation object
        $donation = new Donation($db);
        
        // Set donation properties
        $donation->donor_id = $_SESSION['user_id']; // Changed from user_id to donor_id to match the model
        $donation->amount = $amount;
        $donation->payment_method = $payment_method;
        $donation->reference_number = $reference_number;
        $donation->receipt_image = $receipt_image;
        $donation->status = 'pending';
        
        // Create donation
        try {
            $donation->create();
            
            // Set detailed success message with confirmation
            $_SESSION['success_message'] = '<div class="donation-confirmation">
                <h3>Thank you for your generous donation!</h3>
                <p>Your donation of ₱' . number_format($amount, 2) . ' has been submitted successfully.</p>
                <p>Donation reference number: ' . $reference_number . '</p>
                <p>Status: <span class="pending-status">Pending Verification</span></p>
                <p>Our cashier team has been notified and will verify your donation soon.</p>
                <p>You can track the status of your donation in your donation history.</p>
            </div>';
            
            // Set flag for new donation notification
            $_SESSION['new_donation'] = true;
            
            // Redirect to dashboard
            redirect('donor/dashboard.php');
        } catch (PDOException $e) {
            // Log the error for debugging
            debug_log('Donation creation error', [
                'error' => $e->getMessage(),
                'donor_id' => $donation->donor_id,
                'amount' => $donation->amount,
                'reference_number' => $donation->reference_number
            ]);
            
            // Set error message
            $_SESSION['error_message'] = 'Failed to submit donation. Please try again.';
            
            // Redirect to dashboard
            redirect('donor/dashboard.php');
        }
    } else {
        // Set error message
        $_SESSION['error_message'] = implode('<br>', $errors);
        
        // Redirect to dashboard
        redirect('donor/dashboard.php');
    }
} else {
    // Redirect to dashboard
    redirect('donor/dashboard.php');
}
?>