<?php
require_once "config/config.php";
require_once "config/database.php";
require_once "models/User.php";

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->full_name = "Toby Olimpo";
$user->email = "toby@example.com";
$user->password = "password123";
$user->role = "donor";
$user->username = "toby";

if ($user->create()) {
    echo "Donor created successfully\n";
} else {
    echo "Failed to create donor\n";
} 