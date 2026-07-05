-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-07-2026 a las 03:55:48
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bds2`
--
CREATE DATABASE IF NOT EXISTS `bds2` DEFAULT CHARACTER SET utf8 COLLATE utf8_spanish_ci;
USE `bds2`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
CREATE TABLE `bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `acciones` varchar(255) NOT NULL,
  `datos_previos` varchar(255) NOT NULL,
  `datos_nuevos` varchar(255) NOT NULL,
  `entorno` varchar(50) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `bitacora`
--

INSERT INTO `bitacora` (`id_bitacora`, `id_modulo`, `acciones`, `datos_previos`, `datos_nuevos`, `entorno`, `fecha_hora`, `idUsuario`) VALUES
(1, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:54:16', 1),
(2, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:54:32', 1),
(3, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:57:34', 1),
(4, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:57:44', 1),
(5, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:57:57', 1),
(6, 9, 'Modificó al representante: 13197214 Jessica Colmenarez', '{\"cedula\":\"13197214\",\"nacionalidad\":\"V\",\"nombre\":\"Jessica\",\"apellido\":\"Colmenarez\",\"telefono\":\"0232-1334423\",\"direccion\":\"El Tocuyo\"}', '{\"cedula\":\"13197214\",\"nacionalidad\":\"V\",\"nombre\":\"Jessica\",\"apellido\":\"Colmenarez\",\"telefono\":\"0232-1334423\",\"direccion\":\"El Tocuyo\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:58:00', 1),
(7, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:58:18', 1),
(8, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:58:37', 1),
(9, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 20:58:53', 1),
(10, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:18:00', 1),
(11, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:19:07', 1),
(12, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:20:45', 1),
(13, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:23:08', 1),
(14, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:23:17', 1),
(15, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:23:44', 1),
(16, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:23:54', 1),
(17, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:24:02', 1),
(18, 1, 'Modifico permisos al usuario: 3', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:24:09', 1),
(19, 1, 'Modifico permisos al usuario: 3', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:24:15', 1),
(20, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:24:17', 1),
(21, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:25:20', 1),
(22, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:27:37', 1),
(23, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:27:47', 1),
(24, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:27:52', 1),
(25, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:27:58', 1),
(26, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:28:04', 1),
(27, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:28:35', 1),
(28, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:32:34', 1),
(29, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:37:40', 1),
(30, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:37:54', 3),
(31, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:38:05', 3),
(32, 4, 'Usuario bloqueado por exceder límite de intentos', '', '', '', '2026-06-29 21:38:51', 3),
(33, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:39:06', 1),
(34, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:39:13', 1),
(35, 1, 'Desbloqueo al usuario: 3', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:39:18', 1),
(36, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:39:24', 1),
(37, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:39:36', 3),
(38, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-06-29 21:39:45', 3),
(39, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:32:04', 1),
(40, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:34:18', 1),
(41, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:34:21', 1),
(42, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:35:15', 1),
(43, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:42:16', 1),
(44, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:42:52', 1),
(45, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:43:33', 1),
(46, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:43:42', 1),
(47, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:45:18', 1),
(48, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:45:41', 1),
(49, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:45:45', 1),
(50, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:46:06', 1),
(51, 9, 'Modificó al representante: 13197214 Jessica Colmenarez', '{\"cedula\":\"13197214\",\"nacionalidad\":\"V\",\"nombre\":\"Jessica\",\"apellido\":\"Colmenarez\",\"telefono\":\"0232-1334423\",\"direccion\":\"El Tocuyo\"}', '{\"cedula\":\"13197214\",\"nacionalidad\":\"V\",\"nombre\":\"Jessica\",\"apellido\":\"Colmenarez\",\"telefono\":\"0232-1334423\",\"direccion\":\"El Tocuyo\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:46:38', 3),
(52, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:46:53', 3),
(53, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:52:49', 1),
(54, 1, 'Modifico permisos al usuario: 3', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:53:00', 1),
(55, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:53:03', 1),
(56, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-02 23:53:24', 1),
(57, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:00:20', 3),
(58, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:00:45', 1),
(59, 1, 'Modifico permisos al usuario: 3', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:00:58', 1),
(60, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:01:10', 1),
(61, 1, 'Modifico permisos al usuario: 3', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:03:37', 1),
(62, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:03:43', 1),
(63, 9, 'Generó reporte de representantes.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:04:12', 3),
(64, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 00:04:22', 3),
(65, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:02:50', 1),
(66, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:04:33', 1),
(67, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:04:36', 1),
(68, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:05:48', 1),
(69, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:08:56', 1),
(70, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:08:59', 1),
(71, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:02', 1),
(72, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:06', 1),
(73, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:08', 1),
(74, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:13', 1),
(75, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:15', 1),
(76, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:22', 1),
(77, 14, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:25', 1),
(78, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:27', 1),
(79, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:35', 1),
(80, 16, 'Ingreso al Modulo de Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:37', 1),
(81, 103, 'Ingreso al Modulo Categoría Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:41', 1),
(82, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:44', 1),
(83, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:47', 1),
(84, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:49', 1),
(85, 14, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:51', 1),
(86, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:09:54', 1),
(87, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:10:00', 1),
(88, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:13:41', 1),
(89, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:14:09', 1),
(90, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:14:16', 1),
(91, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:17:14', 1),
(92, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:20:12', 1),
(93, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:45:49', 1),
(94, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:46:03', 1),
(95, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:46:19', 1),
(96, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:46:58', 1),
(97, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:47:15', 1),
(98, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:49:46', 1),
(99, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:49:53', 1),
(100, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:51:07', 1),
(101, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 17:53:45', 1),
(102, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 19:52:47', 1),
(103, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:00:14', 1),
(104, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:01:00', 1),
(105, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:01:16', 1),
(106, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:03:11', 1),
(107, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:03:19', 1),
(108, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:03:28', 1),
(109, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:12:20', 1),
(110, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:24:19', 1),
(111, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-03 20:26:29', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `excepciones`
--

DROP TABLE IF EXISTS `excepciones`;
CREATE TABLE `excepciones` (
  `id_excepcion` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` tinyint(4) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `excepciones`
--

INSERT INTO `excepciones` (`id_excepcion`, `id_permiso`, `id_usuario`, `tipo`) VALUES
(5, 4, 3, 0),
(6, 5, 3, 0),
(7, 6, 3, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

DROP TABLE IF EXISTS `modulos`;
CREATE TABLE `modulos` (
  `id_modulo` int(11) NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `icono` varchar(25) NOT NULL DEFAULT 'circle-minus',
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id_modulo`, `nombre_modulo`, `descripcion`, `icono`, `estatus`) VALUES
(1, 'Usuarios', 'Gestión De Usuarios', 'users', 1),
(2, 'Roles', 'Gestión De Roles Y Perfiles', 'shield-user', 1),
(3, 'Bitacora', 'Registro de auditoría del sistema', 'notebook', 1),
(4, 'Inicio de Sesion', 'Módulo de acceso al sistema', 'circle-minus', 1),
(5, 'Cerrar Sesion', 'Módulo de salida del sistema', 'circle-minus', 1),
(8, 'Recuperacion De Contraseña', 'Gestión de recuperación de claves', 'circle-minus', 1),
(9, 'Representantes', 'Gestión de representantes', 'user-star', 1),
(10, 'Posiciones', 'Gestión de posiciones', 'land-plot', 1),
(11, 'Categorias', 'Gestión de categorías deportivas', 'bring-to-front', 1),
(12, 'Cuentas por Cobrar', 'Gestión de cuentas por cobrar', 'hand-coins', 1),
(13, 'Pagos', 'Gestión de pagos', 'banknote', 1),
(14, 'Metodos de Pago', 'Gestión de métodos de pago', 'wallet', 1),
(15, 'Equipamientos', 'Gestión de equipamiento', 'boxes', 1),
(16, 'Catalogo', 'Catálogo general', 'clipboard-pen-line', 1),
(17, 'Asignaciones', 'Gestión de asignaciones', 'list-plus', 1),
(18, 'Devoluciones', 'Gestión de devoluciones', 'list-restart', 1),
(19, 'Torneos', 'Gestión de torneos', 'hand-coins', 1),
(20, 'Equipos', 'Gestión de equipos', 'shield-half', 1),
(21, 'Premios', 'Gestión de premios', 'award', 1),
(22, 'Palmares', 'Gestión de palmarés', 'trophy', 1),
(23, 'Estadisticas', 'Gestión de estadísticas', 'chart-area', 1),
(99, 'IA', 'Módulo de Inteligencia Artificial', 'component', 1),
(100, 'Atletas', 'Gestión de atletas', 'circle-star', 1),
(101, 'Conceptos de Cuentas', 'Definición de conceptos contables', 'receipt', 1),
(102, 'Monedas', 'Gestión de tipos de moneda', 'coins', 1),
(103, 'Categoria de Equipamiento', 'Gestión de categorías de equipo', 'layers-plus', 1),
(104, 'Calidad', 'Gestión de control de calidad', 'badge-check', 1),
(105, 'Participaciones', 'Gestión de participaciones', 'shield-check', 1),
(106, 'Respaldo BD', 'Gestión de respaldos de base de datos', 'server-cog', 1),
(107, 'Reportes', 'Generación de reportes', 'chart-column-stacked', 1),
(108, 'Historial Deportivo', 'Registro de historial deportivo', 'book-user', 1),
(109, 'Permisos', 'Gestionar los permisos de los usuarios', 'user-key', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` tinyint(4) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `descripcion` varchar(100) NOT NULL DEFAULT 'Sin Descripción ',
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id_permiso`, `id_modulo`, `nombre`, `clave`, `descripcion`, `estatus`) VALUES
(1, 100, 'Registrar Atleta', 'incluir_atleta', 'Permitir El Registro Del Atleta En El Club', 1),
(2, 100, 'Retirar Atleta', 'retirar_atleta', 'Sin Descripción ', 1),
(3, 100, 'Ingresar Atletas', 'ingresar_atleta', 'Poder Ingresar Al Modulo De Atletas', 1),
(4, 9, 'Registrar Representante', 'registrar_representante', 'Sin Descripción ', 1),
(5, 9, 'Modificar Representante', 'modificar_representante', 'Permitir La Modificacion De Un Representante Ya Ex', 1),
(6, 9, 'Eliminar Representante', 'eliminar_representante', 'Permitir La Modificacion De Un Representante Ya Ex', 1),
(7, 9, 'Ingresar A Representantes', 'ingresar_representantes', 'Permitir El Ingreso Al Modulo De Representantes', 1),
(8, 9, 'Generar Reporte', 'generar_representante', 'Permitir Generar Un Reporte Sobre Los Representant', 1),
(9, 109, 'Ingresar A Permisos', 'ingresar_permisos', 'Permitir El Ingreso Al Modulo De Permisos', 1),
(10, 109, 'Registrar Permisos', 'registrar_permisos', 'Permitir El Registro De Un Nuevo Permiso', 1),
(11, 109, 'Modificar Permisos', 'modificar_permisos', 'Permitir La Modificacion De Los Permisos', 1),
(12, 109, 'Bloquear Permisos', 'bloquear_permisos', 'Permitir El Bloque De Los Permisos Para Que No Pueda Ser Accesible Por Ningun Usuario', 1),
(13, 10, 'Ingresar A Posiciones', 'ingresar_posiciones', 'Sin Descripción ', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_rol`
--

DROP TABLE IF EXISTS `permisos_rol`;
CREATE TABLE `permisos_rol` (
  `id_permiso_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `permisos_rol`
--

INSERT INTO `permisos_rol` (`id_permiso_rol`, `id_permiso`, `id_rol`) VALUES
(11, 4, 4),
(12, 5, 4),
(13, 6, 4),
(14, 7, 4),
(15, 8, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respaldos`
--

DROP TABLE IF EXISTS `respaldos`;
CREATE TABLE `respaldos` (
  `id_respaldo` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(100) DEFAULT NULL,
  `peso` varchar(20) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(35) NOT NULL,
  `descripcion` varchar(50) DEFAULT 'Sin Descripcin',
  `nivel_rol` tinyint(4) NOT NULL DEFAULT 3,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`, `nivel_rol`, `estatus`) VALUES
(1, 'Superusuario', 'Acceso A Todo El Sistema', 1, 1),
(4, 'Contador', 'Maneja El Area Contable Del Club', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `idUsuario` int(11) NOT NULL,
  `cedulaUsuario` varchar(10) NOT NULL,
  `nombreUsuario` varchar(35) NOT NULL,
  `apellidoUsuario` varchar(35) NOT NULL,
  `foto` varchar(255) NOT NULL DEFAULT 'default.png',
  `telefonoUsuario` varchar(15) NOT NULL,
  `pass_hash` varchar(255) NOT NULL,
  `correo` varchar(60) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `ultimo_ingreso` datetime DEFAULT NULL,
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `estatus` tinyint(4) NOT NULL DEFAULT 1,
  `bloqueo` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`idUsuario`, `cedulaUsuario`, `nombreUsuario`, `apellidoUsuario`, `foto`, `telefonoUsuario`, `pass_hash`, `correo`, `id_rol`, `ultimo_ingreso`, `intentos_fallidos`, `estatus`, `bloqueo`) VALUES
(1, '12345678', 'Admin', 'Admin', 'default.png', '1234-5678909', '$2y$10$jIi5Y2TlNk61pSslaz3QW.aBfFfqF0vT1aVSA5sjCDNvVpV/YiAMm', 'admin@gmail.com', 1, '2026-07-03 15:52:37', 0, 1, 1),
(3, '29506932', 'Moises', 'Torrellas', 'default.png', '0023-2323232', '$2y$10$5iUNe57cdVOn3lV23254OOyB3S0GEiqfeXgsPFUtaF/ZQ/kp8YlNS', 'moitcj@gmail.com', 4, '2026-07-02 20:03:59', 0, 1, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `idUsuario` (`idUsuario`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- Indices de la tabla `excepciones`
--
ALTER TABLE `excepciones`
  ADD PRIMARY KEY (`id_excepcion`),
  ADD KEY `id_permiso` (`id_permiso`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- Indices de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  ADD PRIMARY KEY (`id_permiso_rol`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `id_permiso` (`id_permiso`);

--
-- Indices de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  ADD PRIMARY KEY (`id_respaldo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `cedulaUsuario` (`cedulaUsuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT de la tabla `excepciones`
--
ALTER TABLE `excepciones`
  MODIFY `id_excepcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  MODIFY `id_respaldo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`),
  ADD CONSTRAINT `bitacora_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`);

--
-- Filtros para la tabla `excepciones`
--
ALTER TABLE `excepciones`
  ADD CONSTRAINT `excepciones_ibfk_1` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`),
  ADD CONSTRAINT `excepciones_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`idUsuario`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`idUsuario`);

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`);

--
-- Filtros para la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  ADD CONSTRAINT `permisos_rol_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  ADD CONSTRAINT `permisos_rol_ibfk_2` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`);

--
-- Filtros para la tabla `respaldos`
--
ALTER TABLE `respaldos`
  ADD CONSTRAINT `respaldos_ibfk_1` FOREIGN KEY (`id_respaldo`) REFERENCES `usuarios` (`idUsuario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
