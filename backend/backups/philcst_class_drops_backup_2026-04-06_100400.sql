-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: philcst_class_drops
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Create Database
--

CREATE DATABASE IF NOT EXISTS `philcst_class_drops`;
USE `philcst_class_drops`;

--
-- Table structure for table `class_card_drops`
--

DROP TABLE IF EXISTS `class_card_drops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `class_card_drops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `subject_no` varchar(20) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `remarks` text DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `drop_date` datetime NOT NULL DEFAULT current_timestamp(),
  `deadline` datetime DEFAULT NULL,
  `drop_month` varchar(10) NOT NULL,
  `drop_year` int(11) NOT NULL,
  `cancelled_date` datetime DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `class_card_drops_approved_by_foreign` (`approved_by`),
  KEY `idx_teacher` (`teacher_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_month` (`drop_month`),
  KEY `idx_year` (`drop_year`),
  KEY `idx_status` (`status`),
  CONSTRAINT `class_card_drops_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `class_card_drops_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_card_drops_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_card_drops`
--

LOCK TABLES `class_card_drops` WRITE;
/*!40000 ALTER TABLE `class_card_drops` DISABLE KEYS */;
INSERT INTO `class_card_drops` VALUES (44,12,11,'IT201','Cybersecurity Fundamentals','',NULL,'Undropped','2026-04-02 17:33:07','2026-04-02 23:59:59','April 2026',2026,NULL,NULL,1,'2026-04-02 23:33:28',NULL,NULL),(46,3,9,'CS102','Data Structures','',NULL,'Undropped','2026-04-02 17:46:05','2026-04-02 23:59:59','April 2026',2026,NULL,NULL,1,'2026-04-02 23:46:18',NULL,NULL),(49,3,9,'IT201','Cybersecurity Fundamentals','dsadadasdasdas',NULL,'Cancelled','2026-04-03 18:52:36','2026-04-03 23:59:59','April 2026',2026,'2026-04-04 01:16:17','Request expired - not processed within the day',NULL,NULL,NULL,NULL),(50,12,9,'CS101','Introduction to Programming','',NULL,'Cancelled','2026-04-03 19:49:56','2026-04-03 23:59:59','April 2026',2026,'2026-04-04 01:50:09','Request expired - not processed within the day',NULL,NULL,NULL,NULL),(51,12,9,'IT201','Cybersecurity Fundamentals','',NULL,'Cancelled','2026-04-03 19:50:33','2026-04-03 23:59:59','April 2026',2026,'2026-04-04 01:50:49','Request expired - not processed within the day',NULL,NULL,NULL,NULL),(52,3,9,'CS102','Data Structures','dsafsadasdsadasdasdasaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',NULL,'Undropped','2026-04-05 09:30:55','2026-04-05 23:59:59','April 2026',2026,NULL,NULL,1,'2026-04-05 15:39:38',NULL,NULL),(53,3,9,'IT201','Cybersecurity Fundamentals','aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',NULL,'Undropped','2026-04-05 10:37:46','2026-04-05 23:59:59','April 2026',2026,NULL,NULL,1,'2026-04-05 16:38:07',NULL,NULL);
/*!40000 ALTER TABLE `class_card_drops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_name` (`course_name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'BS IN COMPUTER ENGINEERING','ENGINEERING','2026-04-05 05:08:55'),(2,'BS IN ELECTRICAL ENGINEERING','ENGINEERING','2026-04-05 05:08:55'),(3,'BS IN ELECTRONICS ENGINEERING','ENGINEERING','2026-04-05 05:08:55'),(4,'BS IN MECHANICAL ENGINEERING','ENGINEERING','2026-04-05 05:08:55'),(5,'BS IN CIVIL ENGINEERING','ENGINEERING','2026-04-05 05:08:55'),(6,'BS IN ACCOUNTANCY','ACCOUNTANCY AND BUSINESS EDUCATION','2026-04-05 05:08:55'),(7,'BS IN BUSINESS ADMINISTRATION (MAJOR IN MANAGEMENT)','ACCOUNTANCY AND BUSINESS EDUCATION','2026-04-05 05:08:55'),(8,'BS IN ELEMENTARY EDUCATION','EDUCATION','2026-04-05 05:08:55'),(9,'BS IN SECONDARY EDUCATION (MAJOR IN GENERAL SCIENCE)','EDUCATION','2026-04-05 05:08:55'),(10,'BS IN CRIMINOLOGY','CRIMINAL JUSTICE EDUCATION','2026-04-05 05:08:55'),(11,'BS IN MARINE ENGINEERING','MARITIME STUDIES','2026-04-05 05:08:55'),(12,'BS IN MARINE TRANSPORTATION','MARITIME STUDIES','2026-04-05 05:08:55'),(13,'BS IN HOSPITALITY MANAGEMENT','HOSPITALITY AND TOURISM MANAGEMENT','2026-04-05 05:08:55'),(14,'BS IN TOURISM MANAGEMENT','HOSPITALITY AND TOURISM MANAGEMENT','2026-04-05 05:08:55'),(15,'BS IN COMPUTER SCIENCE','COMPUTER STUDIES','2026-04-05 05:08:55'),(16,'BS IN INFORMATION TECHNOLOGY','COMPUTER STUDIES','2026-04-05 05:08:55');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `philcst_undrop_records`
--

DROP TABLE IF EXISTS `philcst_undrop_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `philcst_undrop_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `drop_id` bigint(20) unsigned NOT NULL COMMENT 'Reference to class_card_drops',
  `student_id` bigint(20) unsigned NOT NULL,
  `subject_no` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `retrieve_date` datetime NOT NULL COMMENT 'When class card was retrieved',
  `undrop_remarks` longtext DEFAULT NULL COMMENT 'Admin remarks for undrop',
  `undrop_certificates` varchar(255) DEFAULT NULL COMMENT 'Certificate information',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_drop_record` (`drop_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_subject_no` (`subject_no`),
  KEY `idx_drop_id` (`drop_id`),
  CONSTRAINT `philcst_undrop_records_drop_id_foreign` FOREIGN KEY (`drop_id`) REFERENCES `class_card_drops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `philcst_undrop_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `philcst_undrop_records_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `philcst_undrop_records`
--

LOCK TABLES `philcst_undrop_records` WRITE;
/*!40000 ALTER TABLE `philcst_undrop_records` DISABLE KEYS */;
INSERT INTO `philcst_undrop_records` VALUES (1,46,9,'CS102','Data Structures',3,'2026-04-02 23:47:28','dasd','Medical Certificate','2026-04-02 15:47:28','2026-04-02 15:47:28'),(2,44,11,'IT201','Cybersecurity Fundamentals',12,'2026-04-02 23:48:16','dsadsadas','Parents Letter','2026-04-02 15:48:16','2026-04-02 15:48:16'),(3,52,9,'CS102','Data Structures',3,'2026-04-05 16:34:39','dsadsadasdasdasdasdasdsadsad','Other: dsadsa','2026-04-05 08:34:39','2026-04-05 08:34:39'),(4,53,9,'IT201','Cybersecurity Fundamentals',3,'2026-04-05 16:38:27','aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa','Medical Certificate','2026-04-05 08:38:27','2026-04-05 08:38:27');
/*!40000 ALTER TABLE `philcst_undrop_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `course` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_id_unique` (`student_id`),
  UNIQUE KEY `students_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (9,'00038494','Fernandez, Marc Tommy, Cardozo','marctommythegod@gmail.com','315 dsadasfqwrqwe','Tommy A. Fernandez','f.tommy1927@gmail.com','BS in Information Technology (BSIT)',3,'active','2026-03-02 15:31:00','2026-03-12 16:07:47'),(11,'00038495','CARDOZO, FERNANDEZ, QWEQWT','MARCTOMMY@GMAIL.COM','315 adong cardozo auto repair shop','DASDCACDQWDCQWDQW',NULL,'BS in Information Technology (BSIT)',1,'active','2026-03-23 06:44:54','2026-03-23 06:44:54'),(12,'24312312','DSADSADASDASDASDAS, DSADSADSADASDADDAS, DASDASDASDSADASDAS','dassdsadsadada@dksadksad.com','dasdsadasdas1231243','DASDSADASDASD12312321SDASD11',NULL,'BS IN TOURISM MANAGEMENT',1,'active','2026-04-05 10:06:18','2026-04-05 10:06:18'),(13,'12312312','ASDSADASDASD, DASDSADASD, ASDASDASDASDAS','dadadadadadada@gmail.com','dasdasdada123123adsdasdasdas','DSADADSADASDASDASD',NULL,'BS IN HOSPITALITY MANAGEMENT',1,'active','2026-04-05 10:19:20','2026-04-05 10:19:20');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_no` varchar(20) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_subject_no_unique` (`subject_no`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'CS101','Introduction to Programming','2026-03-02 04:49:00','2026-03-02 04:49:00'),(2,'CS102','Data Structures','2026-03-02 04:49:00','2026-03-02 04:49:00'),(3,'CS201','Web Development','2026-03-02 04:49:00','2026-03-02 04:49:00'),(4,'CS202','Database Design','2026-03-02 04:49:00','2026-03-02 04:49:00'),(5,'CS301','Software Engineering','2026-03-02 04:49:00','2026-03-02 04:49:00'),(6,'IT101','Network Basics','2026-03-02 04:49:00','2026-03-02 04:49:00'),(7,'IT102','System Administration','2026-03-02 04:49:00','2026-03-02 04:49:00'),(8,'IT201','Cybersecurity Fundamentals','2026-03-02 04:49:00','2026-03-02 04:49:00');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_expiry` datetime DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expiry` datetime DEFAULT NULL,
  `role` enum('teacher','admin') NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `password_changed` tinyint(1) DEFAULT 0,
  `first_login` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `idx_password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'GARCIA, DULCE, EWAN','PhilcstGuidance@gmail.com','$2y$12$c9NloWiyiVQXddCl3Gab4.6DBqiyCmSnwdcs86PMe6Z6xlb.E2Im.',NULL,NULL,'500977','2026-04-03 18:02:48','admin',NULL,NULL,'Head, Guidance Office','active','2026-03-02 04:49:00','2026-03-02 04:49:00',0,1),(3,'test, teacher, sample','fmarctommy@gmail.com','$2y$10$fRx2ObcD1rilx8lIMG6aHutTNI2mLOdnMB1Hkxm/sLPL24n/xobX.',NULL,NULL,NULL,NULL,'teacher','123412341234','3123213sadasdasdagdsagcxc','BSIT','active',NULL,NULL,1,1),(12,'MARQUEZ, KIM KYLE, CEWAN','charlzaquino1218@gmail.com','$2y$10$P/e2cMHDbMMPhy.n9tz6s.nUYtrMss6vgb5ds039NkwBVo9sfkjdy',NULL,NULL,NULL,NULL,'teacher','1234566','32154 LKDNASJKNDADUIBSADCF CITY','BS in Information Technology (BSIT)','active',NULL,NULL,1,1),(13,'DSADSA, DSADSA, DASDSA','marctommythegod@gmail.com','$2y$10$821chCg4RgtTFCvFJIiGTO5iv6uTbShEOIfcAbP8sHjehoT/2yZxu',NULL,NULL,NULL,NULL,'teacher','123412341234555','dasdasda1312ddsadasd','Bachelor of Secondary Education (BSEd)','active',NULL,NULL,1,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-06 10:04:00
