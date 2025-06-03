-- Create and use database
CREATE DATABASE IF NOT EXISTS `oblatos_foundation`;
USE `oblatos_foundation`;


-- Table structure for table `donations`
DROP TABLE IF EXISTS `donations`;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('bank_transfer','gcash') NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_donor_verified` (`donor_id`,`verified_at`),
  KEY `idx_amount` (`amount`),
  CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `donations`
INSERT INTO `donations` (`id`, `donor_id`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `payment_proof`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES ('1', '10', '1000.00', 'gcash', 'TEST123', 'TEST123', 'no_receipt.jpg', 'pending', NULL, NULL, '2025-06-01 19:29:13');
INSERT INTO `donations` (`id`, `donor_id`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `payment_proof`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES ('2', '10', '1000.00', 'gcash', 'TEST123', 'TEST123', 'no_receipt.jpg', 'pending', NULL, NULL, '2025-06-01 20:09:15');
INSERT INTO `donations` (`id`, `donor_id`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `payment_proof`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES ('3', '10', '1000.00', 'gcash', 'TEST123', 'TEST123', 'no_receipt.jpg', 'pending', NULL, NULL, '2025-06-01 20:10:48');
INSERT INTO `donations` (`id`, `donor_id`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `payment_proof`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES ('4', '10', '1000.00', 'gcash', 'TEST123', 'TEST123', 'no_receipt.jpg', 'pending', NULL, NULL, '2025-06-01 20:11:35');
INSERT INTO `donations` (`id`, `donor_id`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `payment_proof`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES ('5', '10', '1000.00', 'gcash', 'TEST123', 'TEST123', 'no_receipt.jpg', 'rejected', '5', '2025-06-01 20:29:10', '2025-06-01 20:12:44');
INSERT INTO `donations` (`id`, `donor_id`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `payment_proof`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES ('6', '10', '1000.00', 'gcash', 'TEST123', 'TEST123', 'no_receipt.jpg', 'verified', '1', '2025-06-01 20:13:53', '2025-06-01 20:13:53');
INSERT INTO `donations` (`id`, `donor_id`, `amount`, `payment_method`, `reference_number`, `receipt_number`, `payment_proof`, `status`, `verified_by`, `verified_at`, `created_at`) VALUES ('7', '2', '5000.00', 'bank_transfer', '123', '', '', 'verified', '5', '2025-06-01 20:31:39', '2025-06-01 20:31:06');


-- Table structure for table `donors`
DROP TABLE IF EXISTS `donors`;
CREATE TABLE `donors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tier` enum('blue','bronze','silver','gold') DEFAULT 'blue',
  `total_donations` decimal(10,2) DEFAULT 0.00,
  `last_donation_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `donors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','cashier','donor') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username_unique` (`username`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('1', '5fa1f89a-b838-47fa-b80e-e27bb7170906', 'Admin User', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@oblatos.org', 'Administrator', 'admin', 'active', '2025-05-18 15:17:41', NULL, NULL);
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('2', '2fcf4ed2-b7f6-47c5-841e-601fdf25d717', NULL, 'antonphilippeolimpo08', '$2y$10$G.fumlYsJDsNux4.7Aib4O3ZNXitIbuqlu6ixayvnFNnUbBD.LBiW', 'antonphilippeolimpo08@gmail.com', 'Toby Olimpo', 'donor', 'active', '2025-05-18 18:09:55', '261765c928c1eab02c9c94c264124c44482c7297398828deb88a0b315a915475', '2025-06-02 14:48:33');
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('4', '4737cdac-0ae1-4984-bbef-cb68cd6e5f41', NULL, 'admin2', '$2y$10$2JXB7OG36Yyp0wBzsebtc.09EwclEWaiLRqQKHQkPg.F9Bj8AbRzS', 'admin2@email.com', 'Admin2', 'admin', 'active', '2025-05-18 18:39:34', NULL, NULL);
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('5', '8f7ad27c-32ef-441a-a38e-21de13dbe0f5', NULL, 'cashier', '$2y$10$SmUUc8lbBskA3tFYID.Ocu3IG371R98v2M6r4F6h08KpayJQmYw9S', 'cashier@email.com', 'Cashier', 'cashier', 'active', '2025-05-18 19:54:55', NULL, NULL);
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('6', '90364276-5cd7-4b39-9e3c-a0f0afa86bf4', NULL, 'jashia', '$2y$10$l1Q14TNDYkbjTfPg4Uikm.3rC5ud.TCC9wlAvkNc8SQ9PA6RVDteu', 'jashia@email.com', 'Jashia Deveza', 'donor', 'active', '2025-05-20 09:19:49', NULL, NULL);
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('7', '1342f8e3-4e95-406b-8ac0-a819693e7351', NULL, 'belenraven2005', '$2y$10$w/9M7pAyB/P27CuyY0bL0uH75Ueq9xoqCyTJ3Evz.ef9ei5/4KXuK', 'belenraven2005@gmail.com', 'Raven Belen', 'admin', 'active', '2025-05-21 12:00:35', NULL, NULL);
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('8', '7f11e28a-7df8-438a-92b3-ffc66f424b31', NULL, 'ellamay', '$2y$10$EJdi2oZgzd7kGiv4K5RaEOLFjMJHrO413nTYPHK4TCRFH7xb73cr6', 'ellamay@gmail.com', 'Ella May', 'donor', 'active', '2025-05-21 12:03:21', NULL, NULL);
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('9', '0a16b8d2-94d8-41bd-9b75-ad088e3f1346', NULL, 'oblatosfoundation', '$2y$10$p6OiZN4B79et3JwVMvPM/eNVqtSB/dqycN5AmDbeBQJ8nZOseTSPS', 'oblatosfoundation@gmail.com', 'Oblatos Cashier', 'donor', 'active', '2025-05-30 08:09:27', '14d42df1948b519af32fcc8f46775764', '2025-05-31 02:20:15');
INSERT INTO `users` (`id`, `uuid`, `name`, `username`, `password`, `email`, `full_name`, `role`, `status`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES ('10', '5437a731-e2be-4b28-b35b-b35ab52ee672', NULL, 'toby', '$2y$10$lVTdedyH3SI4F5Ao1MiDXOwrrgn5KkU0nsXKUlM/dHYZo15NVX02W', 'toby@example.com', 'Toby Olimpo', 'donor', 'active', '2025-06-01 19:27:24', NULL, NULL);

