-- phpMyAdmin SQL Dump
-- version 4.4.0
-- http://www.phpmyadmin.net
--
-- Servidor: localhost
-- Tiempo de generación: 31-08-2015 a las 19:26:58
-- Versión del servidor: 5.5.42
-- Versión de PHP: 5.6.7

DROP DATABASE IF EXISTS `test006a`;
CREATE DATABASE `test006a`;

DROP DATABASE IF EXISTS `test006b`;
CREATE DATABASE `test006b`;

USE `test006a`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `my_test_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `my_table`
--

CREATE TABLE IF NOT EXISTS `my_table` (
  `id` int(11) NOT NULL,
  `name` varchar(300) DEFAULT NULL,
  `lastname` varchar(300) DEFAULT NULL,
  `username` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `my_view`
--
CREATE TABLE IF NOT EXISTS `my_view` (
`id` int(11)
,`name` varchar(300)
,`lastname` varchar(300)
,`username` varchar(300)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `view_of_my_table`
--
CREATE TABLE IF NOT EXISTS `view_of_my_table` (
`id` int(11)
,`name` varchar(300)
,`lastname` varchar(300)
,`username` varchar(300)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `my_view`
--
DROP TABLE IF EXISTS `my_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`travis`@`localhost` SQL SECURITY DEFINER VIEW `my_view` AS select `view_of_my_table`.`id` AS `id`,`view_of_my_table`.`name` AS `name`,`view_of_my_table`.`lastname` AS `lastname`,`view_of_my_table`.`username` AS `username` from `view_of_my_table`;

-- --------------------------------------------------------

--
-- Estructura para la vista `view_of_my_table`
--
DROP TABLE IF EXISTS `view_of_my_table`;

CREATE ALGORITHM=UNDEFINED DEFINER=`travis`@`localhost` SQL SECURITY DEFINER VIEW `view_of_my_table` AS select `my_table`.`id` AS `id`,`my_table`.`name` AS `name`,`my_table`.`lastname` AS `lastname`,`my_table`.`username` AS `username` from `my_table`;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `my_table`
--
ALTER TABLE `my_table`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `my_table`
--
ALTER TABLE `my_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
