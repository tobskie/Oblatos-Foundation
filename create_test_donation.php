<?php
require_once "config/config.php";
require_once "config/database.php";
require_once "models/User.php";
require_once "models/Donation.php";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable PDO error mode
$GLOBALS['PDO_ERROR_MODE'] = PDO::ERRMODE_EXCEPTION;

$database = new Database();
$db = $database->getConnection();

// Get the donor ID
$user = new User($db);
$user->email = "toby@example.com";
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$user->email]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($donor) {
    echo "Found donor with ID: " . $donor['id'] . "\n";
    
    $donation = new Donation($db);
    $donation->donor_id = $donor['id'];
    $donation->amount = 1000.00;
    $donation->payment_method = "gcash";
    $donation->reference_number = "TEST123";
    $donation->payment_proof = "no_receipt.jpg"; // Default value
    $donation->status = "pending"; // Start as pending
    
    try {
        // Check database connection
        echo "Database connection status: " . ($db ? "Connected" : "Not connected") . "\n";
        
        // Print donation properties
        echo "Donation properties:\n";
        echo "- donor_id: " . $donation->donor_id . "\n";
        echo "- amount: " . $donation->amount . "\n";
        echo "- payment_method: " . $donation->payment_method . "\n";
        echo "- reference_number: " . $donation->reference_number . "\n";
        echo "- payment_proof: " . $donation->payment_proof . "\n";
        echo "- status: " . $donation->status . "\n";
        
        // Try to create the donation
        $donationId = $donation->create();
        if ($donationId) {
            echo "Donation created successfully with ID: " . $donationId . "\n";
            
            // Set the donation ID
            $donation->id = $donationId;
            
            // Now verify the donation
            $donation->status = "verified";
            $donation->verified_by = 1; // Admin user ID
            if ($donation->verify()) {
                echo "Donation verified successfully\n";
            } else {
                echo "Failed to verify donation\n";
            }
        } else {
            echo "Failed to create donation\n";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    } catch (Exception $e) {
        echo "General error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "Donor not found\n";
} 