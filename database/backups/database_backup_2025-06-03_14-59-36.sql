-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: oblatos_foundation
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `donation_history`
--

DROP TABLE IF EXISTS `donation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donation_date` date NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `donation_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_old` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donation_history`
--

LOCK TABLES `donation_history` WRITE;
/*!40000 ALTER TABLE `donation_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `donation_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donation_receipts`
--

DROP TABLE IF EXISTS `donation_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation_receipts` (
  `donation_id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`donation_id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  CONSTRAINT `donation_receipts_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donation_receipts`
--

LOCK TABLES `donation_receipts` WRITE;
/*!40000 ALTER TABLE `donation_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `donation_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donation_status_history`
--

DROP TABLE IF EXISTS `donation_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `donation_id` (`donation_id`),
  KEY `status_id` (`status_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `donation_status_history_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `donation_status_history_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `donation_statuses` (`id`),
  CONSTRAINT `donation_status_history_ibfk_3` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donation_status_history`
--

LOCK TABLES `donation_status_history` WRITE;
/*!40000 ALTER TABLE `donation_status_history` DISABLE KEYS */;
INSERT INTO `donation_status_history` VALUES (1,1,1,1,'2025-06-01 11:29:13',NULL),(2,2,1,1,'2025-06-01 12:09:15',NULL),(3,3,1,1,'2025-06-01 12:10:48',NULL),(4,4,1,1,'2025-06-01 12:11:35',NULL),(5,5,3,5,'2025-06-01 12:29:10',NULL),(6,6,2,1,'2025-06-01 12:13:53',NULL),(7,7,2,5,'2025-06-01 12:31:39',NULL),(8,8,1,1,'2025-06-02 04:14:27',NULL),(9,9,1,2,'2025-06-02 20:46:09',NULL),(10,10,1,2,'2025-06-02 20:49:39',NULL),(11,10,2,5,'2025-06-03 03:43:45',NULL),(12,9,2,5,'2025-06-03 03:45:41',NULL);
/*!40000 ALTER TABLE `donation_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donation_statuses`
--

DROP TABLE IF EXISTS `donation_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donation_statuses`
--

LOCK TABLES `donation_statuses` WRITE;
/*!40000 ALTER TABLE `donation_statuses` DISABLE KEYS */;
INSERT INTO `donation_statuses` VALUES (1,'pending','Donation is pending verification'),(2,'verified','Donation has been verified'),(3,'rejected','Donation has been rejected');
/*!40000 ALTER TABLE `donation_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `donor_id` (`donor_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
INSERT INTO `donations` VALUES (1,10,1000.00,2,'TEST123','2025-06-01 11:29:13'),(2,10,1000.00,2,'TEST123','2025-06-01 12:09:15'),(3,10,1000.00,2,'TEST123','2025-06-01 12:10:48'),(4,10,1000.00,2,'TEST123','2025-06-01 12:11:35'),(5,10,1000.00,2,'TEST123','2025-06-01 12:12:44'),(6,10,1000.00,2,'TEST123','2025-06-01 12:13:53'),(7,2,5000.00,1,'123','2025-06-01 12:31:06'),(8,2,800.00,2,'8080','2025-06-02 04:14:27'),(9,2,200.00,2,'123','2025-06-02 20:46:09'),(10,2,400.00,2,'3180','2025-06-02 20:49:39');
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations_old`
--

DROP TABLE IF EXISTS `donations_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donations_old` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('bank_transfer','gcash') NOT NULL,
  `reference_number` varchar(50) NOT NULL,
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
  CONSTRAINT `donations_old_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users_old` (`id`),
  CONSTRAINT `donations_old_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users_old` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations_old`
--

LOCK TABLES `donations_old` WRITE;
/*!40000 ALTER TABLE `donations_old` DISABLE KEYS */;
INSERT INTO `donations_old` VALUES (1,10,1000.00,'gcash','TEST123','no_receipt.jpg','pending',NULL,NULL,'2025-06-01 11:29:13'),(2,10,1000.00,'gcash','TEST123','no_receipt.jpg','pending',NULL,NULL,'2025-06-01 12:09:15'),(3,10,1000.00,'gcash','TEST123','no_receipt.jpg','pending',NULL,NULL,'2025-06-01 12:10:48'),(4,10,1000.00,'gcash','TEST123','no_receipt.jpg','pending',NULL,NULL,'2025-06-01 12:11:35'),(5,10,1000.00,'gcash','TEST123','no_receipt.jpg','rejected',5,'2025-06-01 12:29:10','2025-06-01 12:12:44'),(6,10,1000.00,'gcash','TEST123','no_receipt.jpg','verified',1,'2025-06-01 12:13:53','2025-06-01 12:13:53'),(7,2,5000.00,'bank_transfer','123','','verified',5,'2025-06-01 12:31:39','2025-06-01 12:31:06'),(8,2,800.00,'gcash','8080','','pending',NULL,NULL,'2025-06-02 04:14:27');
/*!40000 ALTER TABLE `donations_old` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donor_profiles`
--

DROP TABLE IF EXISTS `donor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donor_profiles` (
  `user_id` int(11) NOT NULL,
  `tier_id` int(11) NOT NULL,
  `total_donations` decimal(10,2) DEFAULT 0.00,
  `last_donation_date` date DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `tier_id` (`tier_id`),
  CONSTRAINT `donor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `donor_profiles_ibfk_2` FOREIGN KEY (`tier_id`) REFERENCES `donor_tiers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donor_profiles`
--

LOCK TABLES `donor_profiles` WRITE;
/*!40000 ALTER TABLE `donor_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `donor_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donor_tiers`
--

DROP TABLE IF EXISTS `donor_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donor_tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `minimum_donation` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donor_tiers`
--

LOCK TABLES `donor_tiers` WRITE;
/*!40000 ALTER TABLE `donor_tiers` DISABLE KEYS */;
INSERT INTO `donor_tiers` VALUES (1,'blue',0.00,'Basic donor tier'),(2,'bronze',1000.00,'Bronze tier donor'),(3,'silver',5000.00,'Silver tier donor'),(4,'gold',10000.00,'Gold tier donor');
/*!40000 ALTER TABLE `donor_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donors_old`
--

DROP TABLE IF EXISTS `donors_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donors_old` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tier` enum('blue','bronze','silver','gold') DEFAULT 'blue',
  `total_donations` decimal(10,2) DEFAULT 0.00,
  `last_donation_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `donors_old_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_old` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donors_old`
--

LOCK TABLES `donors_old` WRITE;
/*!40000 ALTER TABLE `donors_old` DISABLE KEYS */;
/*!40000 ALTER TABLE `donors_old` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `token` (`token`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (2,'261765c928c1eab02c9c94c264124c44482c7297398828deb88a0b315a915475','2025-06-02 14:48:33','2025-06-03 02:29:59'),(9,'14d42df1948b519af32fcc8f46775764','2025-05-31 02:20:15','2025-06-03 02:29:59');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (1,'bank_transfer','Bank transfer payment',1),(2,'gcash','GCash mobile payment',1);
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','System administrator with full access'),(2,'cashier','Manages donations and verifications'),(3,'donor','Can make donations');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
INSERT INTO `user_profiles` VALUES (1,'Administrator','Admin User','active'),(2,'Toby Olimpo','Toby Olimpo','active'),(4,'Admin2','Admin2','active'),(5,'Cashier','Cashier','active'),(6,'Jashia Deveza','Jashia Deveza','active'),(7,'Raven Belen','Raven Belen','active'),(8,'Ella May','Ella May','active'),(9,'Oblatos Cashier','Oblatos Cashier','active'),(10,'Toby Olimpo','Toby Olimpo','active');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,1,'2025-06-03 02:29:59'),(2,3,'2025-06-03 02:33:41'),(4,1,'2025-06-03 02:29:59'),(5,2,'2025-06-03 02:29:59'),(6,3,'2025-06-03 02:29:59'),(7,1,'2025-06-03 02:29:59'),(8,3,'2025-06-03 02:29:59'),(9,3,'2025-06-03 02:29:59'),(10,3,'2025-06-03 02:29:59');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'5fa1f89a-b838-47fa-b80e-e27bb7170906','admin','admin@oblatos.org','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2025-05-18 07:17:41'),(2,'2fcf4ed2-b7f6-47c5-841e-601fdf25d717','antonphilippeolimpo08','antonphilippeolimpo08@gmail.com','$2y$10$G.fumlYsJDsNux4.7Aib4O3ZNXitIbuqlu6ixayvnFNnUbBD.LBiW','2025-05-18 10:09:55'),(4,'4737cdac-0ae1-4984-bbef-cb68cd6e5f41','admin2','admin2@email.com','$2y$10$2JXB7OG36Yyp0wBzsebtc.09EwclEWaiLRqQKHQkPg.F9Bj8AbRzS','2025-05-18 10:39:34'),(5,'8f7ad27c-32ef-441a-a38e-21de13dbe0f5','cashier','cashier@email.com','$2y$10$SmUUc8lbBskA3tFYID.Ocu3IG371R98v2M6r4F6h08KpayJQmYw9S','2025-05-18 11:54:55'),(6,'90364276-5cd7-4b39-9e3c-a0f0afa86bf4','jashia','jashia@email.com','$2y$10$l1Q14TNDYkbjTfPg4Uikm.3rC5ud.TCC9wlAvkNc8SQ9PA6RVDteu','2025-05-20 01:19:49'),(7,'1342f8e3-4e95-406b-8ac0-a819693e7351','belenraven2005','belenraven2005@gmail.com','$2y$10$w/9M7pAyB/P27CuyY0bL0uH75Ueq9xoqCyTJ3Evz.ef9ei5/4KXuK','2025-05-21 04:00:35'),(8,'7f11e28a-7df8-438a-92b3-ffc66f424b31','ellamay','ellamay@gmail.com','$2y$10$EJdi2oZgzd7kGiv4K5RaEOLFjMJHrO413nTYPHK4TCRFH7xb73cr6','2025-05-21 04:03:21'),(9,'0a16b8d2-94d8-41bd-9b75-ad088e3f1346','oblatosfoundation','oblatosfoundation@gmail.com','$2y$10$p6OiZN4B79et3JwVMvPM/eNVqtSB/dqycN5AmDbeBQJ8nZOseTSPS','2025-05-30 00:09:27'),(10,'5437a731-e2be-4b28-b35b-b35ab52ee672','toby','toby@example.com','$2y$10$lVTdedyH3SI4F5Ao1MiDXOwrrgn5KkU0nsXKUlM/dHYZo15NVX02W','2025-06-01 11:27:24');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_old`
--

DROP TABLE IF EXISTS `users_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_old` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_old`
--

LOCK TABLES `users_old` WRITE;
/*!40000 ALTER TABLE `users_old` DISABLE KEYS */;
INSERT INTO `users_old` VALUES (1,'5fa1f89a-b838-47fa-b80e-e27bb7170906','Admin User','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin@oblatos.org','Administrator','admin','active','2025-05-18 07:17:41',NULL,NULL),(2,'2fcf4ed2-b7f6-47c5-841e-601fdf25d717',NULL,'antonphilippeolimpo08','$2y$10$G.fumlYsJDsNux4.7Aib4O3ZNXitIbuqlu6ixayvnFNnUbBD.LBiW','antonphilippeolimpo08@gmail.com','Toby Olimpo','donor','active','2025-05-18 10:09:55','261765c928c1eab02c9c94c264124c44482c7297398828deb88a0b315a915475','2025-06-02 14:48:33'),(4,'4737cdac-0ae1-4984-bbef-cb68cd6e5f41',NULL,'admin2','$2y$10$2JXB7OG36Yyp0wBzsebtc.09EwclEWaiLRqQKHQkPg.F9Bj8AbRzS','admin2@email.com','Admin2','admin','active','2025-05-18 10:39:34',NULL,NULL),(5,'8f7ad27c-32ef-441a-a38e-21de13dbe0f5',NULL,'cashier','$2y$10$SmUUc8lbBskA3tFYID.Ocu3IG371R98v2M6r4F6h08KpayJQmYw9S','cashier@email.com','Cashier','cashier','active','2025-05-18 11:54:55',NULL,NULL),(6,'90364276-5cd7-4b39-9e3c-a0f0afa86bf4',NULL,'jashia','$2y$10$l1Q14TNDYkbjTfPg4Uikm.3rC5ud.TCC9wlAvkNc8SQ9PA6RVDteu','jashia@email.com','Jashia Deveza','donor','active','2025-05-20 01:19:49',NULL,NULL),(7,'1342f8e3-4e95-406b-8ac0-a819693e7351',NULL,'belenraven2005','$2y$10$w/9M7pAyB/P27CuyY0bL0uH75Ueq9xoqCyTJ3Evz.ef9ei5/4KXuK','belenraven2005@gmail.com','Raven Belen','admin','active','2025-05-21 04:00:35',NULL,NULL),(8,'7f11e28a-7df8-438a-92b3-ffc66f424b31',NULL,'ellamay','$2y$10$EJdi2oZgzd7kGiv4K5RaEOLFjMJHrO413nTYPHK4TCRFH7xb73cr6','ellamay@gmail.com','Ella May','donor','active','2025-05-21 04:03:21',NULL,NULL),(9,'0a16b8d2-94d8-41bd-9b75-ad088e3f1346',NULL,'oblatosfoundation','$2y$10$p6OiZN4B79et3JwVMvPM/eNVqtSB/dqycN5AmDbeBQJ8nZOseTSPS','oblatosfoundation@gmail.com','Oblatos Cashier','donor','active','2025-05-30 00:09:27','14d42df1948b519af32fcc8f46775764','2025-05-31 02:20:15'),(10,'5437a731-e2be-4b28-b35b-b35ab52ee672',NULL,'toby','$2y$10$lVTdedyH3SI4F5Ao1MiDXOwrrgn5KkU0nsXKUlM/dHYZo15NVX02W','toby@example.com','Toby Olimpo','donor','active','2025-06-01 11:27:24',NULL,NULL);
/*!40000 ALTER TABLE `users_old` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-03 14:59:37
