/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.7-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: glpi
-- ------------------------------------------------------
-- Server version	11.4.7-MariaDB-0ubuntu0.25.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `glpi_plugin_deliveryterms_audit`
--

DROP TABLE IF EXISTS `glpi_plugin_deliveryterms_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_deliveryterms_audit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(100) NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `ts` datetime NOT NULL DEFAULT current_timestamp(),
  `protocol_id` int(11) unsigned DEFAULT NULL,
  `details` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_deliveryterms_audit`
--

LOCK TABLES `glpi_plugin_deliveryterms_audit` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_deliveryterms_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `glpi_plugin_deliveryterms_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_deliveryterms_sequence`
--

DROP TABLE IF EXISTS `glpi_plugin_deliveryterms_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_deliveryterms_sequence` (
  `year` int(11) NOT NULL,
  `last` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_deliveryterms_sequence`
--

LOCK TABLES `glpi_plugin_deliveryterms_sequence` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_deliveryterms_sequence` DISABLE KEYS */;
INSERT INTO `glpi_plugin_deliveryterms_sequence` VALUES
(2026,9);
/*!40000 ALTER TABLE `glpi_plugin_deliveryterms_sequence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `glpi_plugin_deliveryterms_protocols`
--

DROP TABLE IF EXISTS `glpi_plugin_deliveryterms_protocols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `glpi_plugin_deliveryterms_protocols` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `protocol_number` varchar(20) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `gen_date` datetime DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `document_id` int(11) unsigned DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_protocol_number` (`protocol_number`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `glpi_plugin_deliveryterms_protocols`
--

LOCK TABLES `glpi_plugin_deliveryterms_protocols` WRITE;
/*!40000 ALTER TABLE `glpi_plugin_deliveryterms_protocols` DISABLE KEYS */;
INSERT INTO `glpi_plugin_deliveryterms_protocols` VALUES
(1,'2026-0005','Termo_de_Entrega-01012026.pdf',0,'2026-01-01 00:31:10','',22,'Termo de Entrega'),
(2,'2026-0006','Termo_de_Entrega-01012026.pdf',0,'2026-01-01 00:32:24','',23,'Termo de Entrega'),
(3,'2026-0007','Termo_de_Entrega-01012026.pdf',0,'2026-01-01 00:33:17','',24,'Termo de Entrega'),
(4,'2026-0008','Termo_de_Entrega-01012026.pdf',2,'2026-01-01 00:39:38','glpi',27,'Termo de Entrega'),
(5,'2026-0009','Termo_de_Entrega-01012026.pdf',2,'2026-01-01 00:44:10','glpi',28,'Termo de Entrega'),
(6,'2026-1029','Termo_de_Entrega-01012026.pdf',2,'2026-01-01 00:54:09','glpi',30,'Termo de Entrega'),
(7,'2026-0002','Termo_de_Entrega-01012026.pdf',2,'2026-01-01 00:55:16','glpi',31,'Termo de Entrega'),
(8,'2026-0004','TestProtocol.pdf',1,'2026-01-01 00:56:40','tester',9999,'Test');
/*!40000 ALTER TABLE `glpi_plugin_deliveryterms_protocols` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-01-01  1:06:04
