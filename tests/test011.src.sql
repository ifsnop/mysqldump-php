DROP DATABASE IF EXISTS `test011`;
CREATE DATABASE `test011`;
USE `test011`;

-- MySQL dump 10.13  Distrib 5.7.29, for Linux (x86_64)
--
-- Host: localhost    Database: test011
-- ------------------------------------------------------
-- Server version	5.7.29-0ubuntu0.18.04.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `test011`
--

DROP TABLE IF EXISTS `test011`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test011` (
  `id` int(11) NOT NULL,
  `hash_stored` char(32) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (md5(`id`)) STORED NOT NULL,
  `hash_virtual` char(32) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (md5(`id`)) VIRTUAL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test011`
--

LOCK TABLES `test011` WRITE;
/*!40000 ALTER TABLE `test011` DISABLE KEYS */;
INSERT INTO `test011` (`id`) VALUES (159413),(294775);
/*!40000 ALTER TABLE `test011` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test012`
--

DROP TABLE IF EXISTS `test012`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test012` (
  `id` int(11) NOT NULL,
  `hash_stored` char(32) CHARACTER SET ascii COLLATE ascii_bin GENERATED ALWAYS AS (md5(`id`)) STORED NOT NULL,
  `hash_virtual` blob GENERATED ALWAYS AS (md5(`id`)) VIRTUAL NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test012`
--

LOCK TABLES `test012` WRITE;
/*!40000 ALTER TABLE `test012` DISABLE KEYS */;
INSERT INTO `test012` (`id`) VALUES (159413),(294775);
/*!40000 ALTER TABLE `test012` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-02-08 23:27:41
