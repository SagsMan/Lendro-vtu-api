/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.10-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: tracsmda_lendro
-- ------------------------------------------------------
-- Server version	11.4.10-MariaDB-cll-lve-log

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
-- Table structure for table `apicache`
--

DROP TABLE IF EXISTS `apicache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `apicache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cachekey` varchar(100) NOT NULL,
  `cachegroup` varchar(50) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cachekey` (`cachekey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apicache`
--

LOCK TABLES `apicache` WRITE;
/*!40000 ALTER TABLE `apicache` DISABLE KEYS */;
/*!40000 ALTER TABLE `apicache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commissions`
--

DROP TABLE IF EXISTS `commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `requestid` varchar(100) DEFAULT NULL,
  `prodtype` varchar(50) DEFAULT NULL,
  `sprice` decimal(10,2) DEFAULT NULL COMMENT 'selling price (what user paid)',
  `cprice` decimal(10,2) DEFAULT NULL COMMENT 'cost price (what provider charged)',
  `commission_rate` decimal(5,4) DEFAULT NULL COMMENT 'e.g. 0.15 for 15%',
  `supplier_cost` decimal(10,2) DEFAULT NULL,
  `commission` decimal(10,2) DEFAULT NULL COMMENT 'profit on this transaction',
  `sprofit` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_commissions_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commissions`
--

LOCK TABLES `commissions` WRITE;
/*!40000 ALTER TABLE `commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provider_callbacks`
--

DROP TABLE IF EXISTS `provider_callbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_callbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `payload` longtext NOT NULL COMMENT 'raw JSON body received from provider',
  `status` varchar(50) DEFAULT 'received',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cb_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_callbacks`
--

LOCK TABLES `provider_callbacks` WRITE;
/*!40000 ALTER TABLE `provider_callbacks` DISABLE KEYS */;
/*!40000 ALTER TABLE `provider_callbacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provider_services`
--

DROP TABLE IF EXISTS `provider_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `provider_code` varchar(100) DEFAULT NULL COMMENT 'provider''s internal plan ID or SKU',
  `cost_price` decimal(10,2) DEFAULT NULL COMMENT 'provider''s price before our markup',
  `priority` int(11) DEFAULT 1 COMMENT 'lower = tried first when routing',
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_service` (`provider_id`,`service_id`),
  KEY `fk_ps_service` (`service_id`),
  CONSTRAINT `fk_ps_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`),
  CONSTRAINT `fk_ps_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provider_services`
--

LOCK TABLES `provider_services` WRITE;
/*!40000 ALTER TABLE `provider_services` DISABLE KEYS */;
INSERT INTO `provider_services` VALUES
(1,1,1,'70',295.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(2,1,2,'13',490.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(3,1,3,'69',500.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(4,1,4,'66',599.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(5,1,5,'15',785.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(6,1,6,'17',1470.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(7,1,7,'52',1570.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(8,1,8,'18',1960.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(9,1,9,'22',2455.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(10,1,10,'19',2570.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(11,1,11,'20',2999.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(12,1,12,'21',4070.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(13,1,13,'42',92.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(14,1,14,'35',225.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(15,1,15,'68',300.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(16,1,16,'36',425.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(17,1,17,'41',485.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(18,1,18,'40',850.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(19,1,19,'37',1300.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(20,1,20,'54',1699.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(21,1,21,'38',2250.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(22,1,22,'39',4390.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(23,1,23,'59',5300.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(24,1,24,'58',19300.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(25,1,25,'43',99.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(26,1,26,'74',200.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(27,1,27,'76',250.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(28,1,28,'78',280.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(29,1,29,'44',350.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(30,1,30,'77',399.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(31,1,31,'45',450.00,1,1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(32,1,32,'46',570.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(33,1,33,'79',600.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(34,1,34,'27',900.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(35,1,35,'71',900.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(36,1,36,'47',930.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(37,1,37,'60',980.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(38,1,38,'48',1150.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(39,1,39,'61',1175.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(40,1,40,'80',1299.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(41,1,41,'49',1370.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(42,1,42,'50',2050.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(43,1,43,'53',2495.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(44,1,44,'55',3430.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(45,1,45,'33',3499.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(46,1,46,'67',4470.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(47,1,47,'57',10800.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(48,1,48,'51',17990.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(49,1,49,'6',6000.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(55,1,50,'15',16800.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(61,1,51,'24',3300.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(73,1,52,'1',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(74,1,53,'2',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(75,1,54,'3',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(76,1,55,'4',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(77,1,56,'5',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(78,1,57,'6',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(79,1,58,'7',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(80,1,59,'8',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(81,1,60,'9',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(82,1,61,'10',0.00,1,1,'2026-06-15 11:34:38','2026-06-15 11:34:38');
/*!40000 ALTER TABLE `provider_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(50) DEFAULT NULL COMMENT 'unique short name used in code',
  `base_url` varchar(255) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT NULL COMMENT 'used to verify incoming webhooks',
  `priority` int(11) DEFAULT 1 COMMENT 'lower = tried first',
  `status` tinyint(1) DEFAULT 1 COMMENT '1=active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `providers`
--

LOCK TABLES `providers` WRITE;
/*!40000 ALTER TABLE `providers` DISABLE KEYS */;
INSERT INTO `providers` VALUES
(1,'CheapDataHub','cheapdatahub','https://www.cheapdatahub.ng/api/v1/resellers','f52b40797b65e1036b42cccbf7e4da248f27e5d3',NULL,1,1,'2026-06-15 11:30:36'),
(2,'ConnectBridge','connectbridge','https://connectbridge.com.ng/api','vYERKrBgLZp9J1hl0F0qOdt5yLizZk6vHB6iqAWH',NULL,2,1,'2026-06-15 11:30:36');
/*!40000 ALTER TABLE `providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_key` varchar(120) DEFAULT NULL COMMENT 'e.g. mtn_data_1gb_7day_sme',
  `name` varchar(150) NOT NULL COMMENT 'human-readable label shown in UI',
  `network` varchar(50) DEFAULT NULL COMMENT 'mtn, glo, airtel, 9mobile, AEDC, …',
  `type` enum('airtime','data','bill') DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL COMMENT 'airtime|data|electricity|cable|education|betting',
  `price` decimal(10,2) DEFAULT NULL COMMENT 'our selling price (provider cost + markup)',
  `duration` int(11) DEFAULT NULL COMMENT 'validity in days/weeks/months',
  `validity_unit` enum('day','week','month') DEFAULT 'day',
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_key` (`service_key`),
  KEY `idx_services_type` (`type`),
  KEY `idx_services_network` (`network`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES
(1,'airtel_data_1gb_3day_gifting','AIRTEL DATA 1GB (3 Days) GIFTING','airtel','data','data',339.25,3,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(2,'airtel_data_500mb_7day_gifting','AIRTEL DATA 500MB (7 Days) GIFTING','airtel','data','data',563.50,7,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(3,'airtel_data_1_5gb_1day_gifting','AIRTEL DATA 1.5GB (1 Day) GIFTING','airtel','data','data',575.00,1,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(4,'airtel_data_1_5gb_2day_gifting','AIRTEL DATA 1.5GB (2 Days) GIFTING','airtel','data','data',688.85,2,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(5,'airtel_data_1gb_7day_gifting','AIRTEL DATA 1GB (7 Days) GIFTING','airtel','data','data',902.75,7,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(6,'airtel_data_2gb_30day_gifting','AIRTEL DATA 2GB (30 Days) GIFTING','airtel','data','data',1690.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(7,'airtel_data_5gb_7day_gifting','AIRTEL DATA 5GB (7 Days) GIFTING','airtel','data','data',1805.50,7,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(8,'airtel_data_3gb_30day_gifting','AIRTEL DATA 3GB (30 Days) GIFTING','airtel','data','data',2254.00,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(9,'airtel_data_6gb_7day_sme','AIRTEL DATA 6GB (7 Days) SME','airtel','data','data',2823.25,7,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(10,'airtel_data_4gb_30day_gifting','AIRTEL DATA 4GB (30 Days) GIFTING','airtel','data','data',2955.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(11,'airtel_data_8gb_30day_gifting','AIRTEL DATA 8GB (30 Days) GIFTING','airtel','data','data',3448.85,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(12,'airtel_data_10gb_30day_gifting','AIRTEL DATA 10GB (30 Days) GIFTING','airtel','data','data',4680.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(13,'glo_data_200_mb_1day_gifting','GLO DATA 200 MB (1 Day) GIFTING','glo','data','data',105.80,1,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(14,'glo_data_500mb_30day_gifting','GLO DATA 500MB (30 Days) GIFTING','glo','data','data',258.75,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(15,'glo_data_1gb_3day_gifting','GLO DATA 1GB (3 Days) GIFTING','glo','data','data',345.00,3,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(16,'glo_data_1gb_30day_gifting','GLO DATA 1GB (30 Days) GIFTING','glo','data','data',488.75,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(17,'glo_data_1gb_14day_gifting','GLO DATA 1GB (14 Days) GIFTING','glo','data','data',557.75,14,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(18,'glo_data_2gb_30day_gifting','GLO DATA 2GB (30 Days) GIFTING','glo','data','data',977.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(19,'glo_data_3gb_30day_gifting','GLO DATA 3GB (30 Days) GIFTING','glo','data','data',1495.00,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(20,'glo_data_5gb_7day_gifting','GLO DATA 5GB (7 Days) GIFTING','glo','data','data',1953.85,7,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(21,'glo_data_5gb_30day_gifting','GLO DATA 5GB (30 Days) GIFTING','glo','data','data',2587.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(22,'glo_data_10gb_30day_gifting','GLO DATA 10GB (30 Days) GIFTING','glo','data','data',5048.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(23,'glo_data_20_5gb_30day_gifting','GLO DATA 20.5GB (30 Days) GIFTING','glo','data','data',6095.00,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(24,'glo_data_107gb_30day_gifting','GLO DATA 107GB (30 Days) GIFTING','glo','data','data',22195.00,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(25,'mtn_data_110mb_1day_gifting','MTN DATA 110MB (1 Day) GIFTING','mtn','data','data',113.85,1,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(26,'mtn_data_230mb_1day_gifting','MTN DATA 230MB (1 Day) GIFTING','mtn','data','data',230.00,1,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(27,'mtn_data_500mb_2day_sme','MTN DATA 500MB (2 Days) SME','mtn','data','data',287.50,2,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(28,'mtn_data_1gb_1day_sme','MTN DATA 1GB (1 Day) SME','mtn','data','data',322.00,1,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(29,'mtn_data_500mb_30day_sme','MTN DATA 500MB (30 Days) SME','mtn','data','data',402.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(30,'mtn_data_1gb_2day_sme','MTN DATA 1GB (2 Days) SME','mtn','data','data',458.85,2,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(31,'mtn_data_1gb_7day_sme','MTN DATA 1GB (7 Days) SME','mtn','data','data',517.50,7,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(32,'mtn_data_1gb_30day_sme','MTN DATA 1GB (30 Days) SME','mtn','data','data',655.50,30,'day',1,'2026-06-15 11:34:37','2026-06-15 11:34:37'),
(33,'mtn_data_2_5gb_1day_sme','MTN DATA 2.5GB (1 Day) SME','mtn','data','data',690.00,1,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(34,'mtn_data_2_5gb_2day_gifting','MTN DATA 2.5GB (2 Days) GIFTING','mtn','data','data',1035.00,2,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(35,'mtn_data_2gb_7day_gifting','MTN DATA 2GB (7 Days) GIFTING','mtn','data','data',1035.00,7,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(36,'mtn_data_2gb_7day_sme','MTN DATA 2GB (7 Days) SME','mtn','data','data',1069.50,7,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(37,'mtn_data_3_5gb_1day_gifting','MTN DATA 3.5GB (1 Day) GIFTING','mtn','data','data',1127.00,1,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(38,'mtn_data_2gb_30day_sme','MTN DATA 2GB (30 Days) SME','mtn','data','data',1322.50,30,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(39,'mtn_data_4gb_2day_gifting','MTN DATA 4GB (2 Days) GIFTING','mtn','data','data',1351.25,2,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(40,'mtn_data_5gb_14day_gifting','MTN DATA 5GB (14 Days) GIFTING','mtn','data','data',1493.85,14,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(41,'mtn_data_3gb_30day_sme','MTN DATA 3GB (30 Days) SME','mtn','data','data',1575.50,30,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(42,'mtn_data_5gb_30day_sme','MTN DATA 5GB (30 Days) SME','mtn','data','data',2357.50,30,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(43,'mtn_data_6gb_7day_gifting','MTN DATA 6GB (7 Days) GIFTING','mtn','data','data',2869.25,7,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(44,'mtn_data_11gb_7day_gifting','MTN DATA 11GB (7 Days) GIFTING','mtn','data','data',3944.50,7,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(45,'mtn_data_7gb_30day_gifting','MTN DATA 7GB (30 Days) GIFTING','mtn','data','data',4023.85,30,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(46,'mtn_data_10gb_30day_gifting','MTN DATA 10GB (30 Days) GIFTING','mtn','data','data',5140.50,30,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(47,'mtn_data_36gb_30day_gifting','MTN DATA 36GB (30 Days) GIFTING','mtn','data','data',12420.00,30,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(48,'mtn_data_75gb_30day_sme','MTN DATA 75GB (30 Days) SME','mtn','data','data',20688.50,30,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(49,'dstv_bill_bundle','DSTV BILL BUNDLE','dstv','bill','bundle',51175.00,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(50,'gotv_bill_bundle','GOTV BILL BUNDLE','gotv','bill','bundle',19320.00,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(51,'startimes_bill_bundle','STARTIMES BILL BUNDLE','startimes','bill','bundle',10925.00,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(52,'aedc_bill_electricity','AEDC BILL ELECTRICITY','aedc','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(53,'ekedc_bill_electricity','EKEDC BILL ELECTRICITY','ekedc','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(54,'ibedc_bill_electricity','IBEDC BILL ELECTRICITY','ibedc','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(55,'ikedc_bill_electricity','IKEDC BILL ELECTRICITY','ikedc','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(56,'kedco_bill_electricity','KEDCO BILL ELECTRICITY','kedco','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(57,'phed_bill_electricity','PHED BILL ELECTRICITY','phed','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(58,'jed_bill_electricity','JED BILL ELECTRICITY','jed','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(59,'eedc_bill_electricity','EEDC BILL ELECTRICITY','eedc','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(60,'yedc_bill_electricity','YEDC BILL ELECTRICITY','yedc','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(61,'bedc_bill_electricity','BEDC BILL ELECTRICITY','bedc','bill','electricity',NULL,NULL,'day',1,'2026-06-15 11:34:38','2026-06-15 11:34:38'),
(62,'mtn_airtime','MTN Airtime','mtn','airtime','airtime',NULL,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(63,'glo_airtime','GLO Airtime','glo','airtime','airtime',NULL,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(64,'airtel_airtime','Airtel Airtime','airtel','airtime','airtime',NULL,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(65,'9mobile_airtime','9mobile Airtime','9mobile','airtime','airtime',NULL,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(66,'waec_result_checker','WAEC Result Checker','waec','bill','education',3550.00,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(67,'waec_gce_registration','WAEC GCE Registration','waec','bill','education',22500.00,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(68,'jamb_utme_epin','JAMB UTME e-PIN','jamb','bill','education',6200.00,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(69,'jamb_de_epin','JAMB Direct Entry e-PIN','jamb','bill','education',4700.00,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(70,'neco_result_checker','NECO Result Checker','neco','bill','education',1500.00,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(71,'neco_gce_registration','NECO GCE Registration','neco','bill','education',16800.00,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(72,'nabteb_result_checker','NABTEB Result Checker','nabteb','bill','education',1000.00,NULL,NULL,1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(73,'dstv_padi','DSTV Padi','dstv','bill','cable',2950.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(74,'dstv_yanga','DSTV Yanga','dstv','bill','cable',4615.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(75,'dstv_confam','DSTV Confam','dstv','bill','cable',9315.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(76,'dstv_compact','DSTV Compact','dstv','bill','cable',15700.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(77,'dstv_premium','DSTV Premium','dstv','bill','cable',37000.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(78,'gotv_supa','GOtv Supa','gotv','bill','cable',6400.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(79,'gotv_max','GOtv Max','gotv','bill','cable',4850.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(80,'gotv_jolli','GOtv Jolli','gotv','bill','cable',3300.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(81,'gotv_jinja','GOtv Jinja','gotv','bill','cable',2460.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(82,'startimes_nova','Startimes Nova','startimes','bill','cable',1200.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(83,'startimes_basic','Startimes Basic','startimes','bill','cable',2100.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47'),
(84,'startimes_smart','Startimes Smart','startimes','bill','cable',2750.00,30,'day',1,'2026-06-15 13:39:47','2026-06-15 13:39:47');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_queue`
--

DROP TABLE IF EXISTS `transaction_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `status` enum('pending','processing','awaiting_reconciliation','awaiting_callback','completed','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0 COMMENT 'how many times the worker has tried this job',
  `next_retry_at` datetime DEFAULT NULL COMMENT 'earliest time for the next retry',
  `locked_at` datetime DEFAULT NULL COMMENT 'when a worker claimed this job',
  `worker_token` varchar(50) DEFAULT NULL COMMENT 'which worker instance is processing this',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_queue_status` (`status`),
  KEY `idx_queue_next_retry` (`next_retry_at`),
  KEY `idx_queue_tx_id` (`transaction_id`),
  CONSTRAINT `fk_queue_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_queue`
--

LOCK TABLES `transaction_queue` WRITE;
/*!40000 ALTER TABLE `transaction_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaction_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL COMMENT 'filled in after the worker picks a provider',
  `amount` decimal(10,2) NOT NULL,
  `phone` varchar(20) DEFAULT NULL COMMENT 'recipient phone number',
  `transtype` enum('debit','credit') DEFAULT 'debit',
  `refno` varchar(100) NOT NULL COMMENT 'our reference: LDR-timestamp-uid',
  `idempotency_key` varchar(100) DEFAULT NULL,
  `request_hash` varchar(64) DEFAULT NULL COMMENT 'sha256 of userid+service_id+phone',
  `transtitle` varchar(150) DEFAULT NULL COMMENT 'service name at time of purchase',
  `transdesc` varchar(255) DEFAULT NULL,
  `service_type` varchar(50) DEFAULT NULL COMMENT 'airtime|data|electricity|cable|education',
  `status` enum('pending','processing','success','failed','reversed','timeout') DEFAULT 'pending',
  `provider_status` varchar(50) DEFAULT NULL COMMENT 'raw status string from the provider',
  `provider_reference` varchar(100) DEFAULT NULL COMMENT 'provider''s own transaction ID',
  `provider_response` longtext DEFAULT NULL COMMENT 'raw JSON from provider',
  `callback_data` longtext DEFAULT NULL COMMENT 'raw webhook payload from provider',
  `reconciled` tinyint(1) DEFAULT 0 COMMENT '1 = reconciliation has finalised this tx',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_refno` (`refno`),
  KEY `idx_tx_userid` (`userid`),
  KEY `idx_tx_status` (`status`),
  KEY `idx_tx_idempotency` (`idempotency_key`),
  CONSTRAINT `fk_tx_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=active, 0=disabled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Test User','test@lendro.com','08012345678','$2y$10$8Z7ZZUVIzDsapbSxnsLGh.oYzrwR00hWweBopkfA7AktEa7kSqTMe',1,'2026-06-15 11:31:25','2026-06-15 11:31:25'),
(2,'Sagiru Garba','sagirugarba24@gmail.com','08065488451','$2y$10$jktr1DEKNndXfeSz/OhlgOGUB85oJZTn1g9FNW2c/EygqlfY/Drti',1,'2026-06-15 11:37:22','2026-06-15 11:37:22'),
(3,'Progmatech','favoursdot@gmail.com','07087677644','$2y$10$VxQSH4fWrd6zWd6LVpcvH.xVJG.3LGKmcb/axx5VEyS7HMaoioCze',1,'2026-06-15 13:49:21','2026-06-15 13:49:21');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_logs`
--

DROP TABLE IF EXISTS `wallet_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `type` enum('debit','credit') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wallet_logs_userid` (`userid`),
  KEY `idx_wallet_logs_ref` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_logs`
--

LOCK TABLES `wallet_logs` WRITE;
/*!40000 ALTER TABLE `wallet_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallet_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bucbalance` decimal(12,2) DEFAULT 0.00 COMMENT 'bucket/bonus balance',
  `loanlimit` decimal(12,2) DEFAULT 0.00,
  `loancount` int(11) DEFAULT 0,
  `totalscore` int(11) DEFAULT 0,
  `upoint` int(11) DEFAULT 0 COMMENT 'total usage points ever',
  `usage_recent` int(11) DEFAULT 0 COMMENT 'usage points this period',
  `vscore` int(11) DEFAULT 0 COMMENT 'verification score',
  `repayscore` int(11) DEFAULT 0 COMMENT 'repayment score',
  `ctpoint` int(11) DEFAULT 0 COMMENT 'community trust points',
  `plan` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_userid` (`userid`),
  CONSTRAINT `fk_wallets_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES
(1,1,0.00,0.00,0.00,0,0,0,0,0,0,0,NULL,'2026-06-15 11:31:25','2026-06-15 11:31:25'),
(2,2,0.00,0.00,0.00,0,0,0,0,0,0,0,NULL,'2026-06-15 11:37:22','2026-06-15 11:37:22'),
(3,3,0.00,0.00,0.00,0,0,0,0,0,0,0,NULL,'2026-06-15 13:49:21','2026-06-15 13:49:21');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-06-16 22:49:13
