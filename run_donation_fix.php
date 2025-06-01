<?php
// Script to run the migration to fix the donation foreign key constraint
require_once 'config/config.php';

echo "<h1>Fixing Donation Foreign Key Constraint</h1>";
echo "<p>This script will update the database structure to fix the foreign key constraint issue.</p>";

// Include the migration file
require_once __DIR__ . '/migrations/fix_donation_foreign_key.php';

echo "<p>Migration completed. <a href='index.php'>Return to homepage</a></p>";
?>
