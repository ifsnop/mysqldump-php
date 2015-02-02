DROP DATABASE IF EXISTS `test002`;
CREATE DATABASE `test002`;
USE `test002`;

DROP TABLE IF EXISTS `test201`;
CREATE TABLE `test201` (
  `col` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `test201` VALUES ('áéíóú');
INSERT INTO `test201` VALUES ('🎲');
INSERT INTO `test201` VALUES ('🎭');
INSERT INTO `test201` VALUES ('💩');
