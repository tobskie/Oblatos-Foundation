<?php
// Script to run the migration to add UUID to users table
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>Running Migration: Add UUID to Users</h1>";
echo "<p>This script will add a UUID field to the users table and generate UUIDs for existing users.</p>";

// Include the migration file
require_once __DIR__ . '/migrations/add_uuid_to_users.php';

echo "<p>Migration completed. <a href='index.php'>Return to homepage</a></p>";
?>
