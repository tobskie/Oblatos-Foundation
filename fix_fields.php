<?php
// This is a helper script to update field references in the codebase
$donationFile = './models/Donation.php';
$content = file_get_contents($donationFile);

// Replace name references with full_name in the Donation model
$content = str_replace('u.name as donor_name', 'u.full_name as donor_name', $content);
$content = str_replace('v.name as verifier_name', 'v.full_name as verifier_name', $content);

file_put_contents($donationFile, $content);
echo "Donation model updated successfully!\n";
?>
