<?php
require_once 'config/config.php';

// Show all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fixing Donation Model</h1>";

// Get the content of the Donation model
$file = 'models/Donation.php';
$content = file_get_contents($file);

if ($content === false) {
    echo "<p>Could not read the file: $file</p>";
    exit;
}

// Replace all occurrences of user_id with donor_id
$content = str_replace('user_id', 'donor_id', $content);

// Replace all occurrences of name as donor_name with full_name as donor_name
$content = str_replace('name as donor_name', 'full_name as donor_name', $content);
$content = str_replace('name as verifier_name', 'full_name as verifier_name', $content);

// Write the updated content back to the file
if (file_put_contents($file, $content) === false) {
    echo "<p>Could not write to the file: $file</p>";
    exit;
}

echo "<p>Success! The Donation model has been updated to use 'donor_id' instead of 'user_id' and 'full_name' instead of 'name'.</p>";
echo "<p>Now let's check the donor/make_donation.php file to ensure it's using donor_id:</p>";

// Update donor/make_donation.php
$donor_file = 'donor/make_donation.php';
if (file_exists($donor_file)) {
    $donor_content = file_get_contents($donor_file);
    if ($donor_content !== false) {
        $donor_content = str_replace('$donation->user_id', '$donation->donor_id', $donor_content);
        file_put_contents($donor_file, $donor_content);
        echo "<p>Updated $donor_file to use donor_id.</p>";
    }
}

// Also update any other files that might be using user_id
$files_to_check = [
    'donor/donation_history.php',
    'donor/view_donation.php',
    'admin/donations.php',
    'cashier/verify_donation.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $file_content = file_get_contents($file);
        if ($file_content !== false) {
            $updated_content = str_replace('$donation->user_id', '$donation->donor_id', $file_content);
            file_put_contents($file, $updated_content);
            echo "<p>Checked and updated: $file</p>";
        }
    }
}

echo "<p>Done! Your donation system should now work correctly.</p>";
echo "<p><a href='donor/donation_history.php'>Click here to test the donation history page</a></p>";
?>
