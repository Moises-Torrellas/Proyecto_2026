-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-07-2026 a las 01:53:47
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

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
  `datos_previos` varchar(255) NOT NULL DEFAULT 'No Aplica',
  `datos_nuevos` varchar(255) NOT NULL DEFAULT 'No Aplica',
  `entorno` varchar(50) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `bitacora`
--

INSERT INTO `bitacora` (`id_bitacora`, `id_modulo`, `acciones`, `datos_previos`, `datos_nuevos`, `entorno`, `fecha_hora`, `idUsuario`) VALUES
(551, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:32:04', 4),
(552, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:32:11', 4),
(553, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:34:28', 4),
(554, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:34:30', 4),
(555, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:34:30', 4),
(556, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:34:51', 4),
(557, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:41:19', 4),
(558, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:41:36', 4),
(559, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:41:50', 4),
(560, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:44:44', 4),
(561, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:47:42', 4),
(562, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:47:58', 4),
(563, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:48:08', 4),
(564, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:57:40', 4),
(565, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:57:44', 4),
(566, 112, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 21:57:48', 4),
(567, 112, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:04:19', 4),
(568, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:04:31', 4),
(569, 16, 'Ingreso al Modulo de Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:04:35', 4),
(570, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:04:40', 4),
(571, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:06:50', 4),
(572, 104, 'Ingreso al Modulo de Estado Físico', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:06:54', 4),
(573, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:07:47', 4),
(574, 103, 'Ingreso al Modulo Categoría Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:07:50', 4),
(575, 16, 'Ingreso al Modulo de Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:07:55', 4),
(576, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:08:09', 4),
(577, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:08:12', 4),
(578, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:08:16', 4),
(579, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:53:02', 4),
(580, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:53:33', 4),
(581, 19, 'Registró el torneo: Tocuyo 2026', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:54:21', 4),
(582, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:54:31', 4),
(583, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:58:19', 4),
(584, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 22:59:54', 4),
(585, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:00:25', 4),
(586, 22, 'Modificó el palmarés individual ID: 2', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:00:43', 4),
(587, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:14:47', 4),
(588, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:14:51', 4),
(589, 1, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:16:39', 4),
(590, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:17:24', 4),
(591, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:17:38', 4),
(592, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:17:41', 4),
(593, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:17:44', 4),
(594, 17, 'Modificó asignación ID: 1', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:17:50', 4),
(595, 17, 'Generó reporte de Asignaciones.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:17:56', 4),
(596, 104, 'Ingreso al Modulo de Estado Físico', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:18:18', 4),
(597, 104, 'Modificó el estado físico: Exelente', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:18:41', 4),
(598, 23, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:18:59', 4),
(599, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:19:16', 4),
(600, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:25:06', 4),
(601, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:14', 4),
(602, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:21', 4),
(603, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:23', 4),
(604, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:24', 4),
(605, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:26', 4),
(606, 16, 'Ingreso al Modulo de Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:28', 4),
(607, 103, 'Ingreso al Modulo Categoría Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:37', 4),
(608, 104, 'Ingreso al Modulo de Estado Físico', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:40', 4),
(609, 103, 'Ingreso al Modulo Categoría Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:26:44', 4),
(610, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:28:23', 4),
(611, 104, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:40:49', 4),
(612, 104, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:40:54', 4),
(613, 104, 'Modificó el estado físico: Exelente', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:40:59', 4),
(614, 16, 'Ingreso al Modulo de Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:41:21', 4),
(615, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:41:25', 4),
(616, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:41:26', 4),
(617, 103, 'Ingreso al Modulo Categoría Catálogo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:41:48', 4),
(618, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:43:50', 4),
(619, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:47:55', 4),
(620, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:47:57', 4),
(621, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:48:09', 4),
(622, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:48:22', 4),
(623, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:48:42', 4),
(624, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:48:53', 4),
(625, 15, 'Eliminó artículo Código: 3', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:49:00', 4),
(626, 15, 'Eliminó artículo Código: 2', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:49:03', 4),
(627, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:49:14', 4),
(628, 17, 'Anuló asignación ID: 1', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:49:18', 4),
(629, 104, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:49:27', 4),
(630, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:49:34', 4),
(631, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:51:02', 4),
(632, 15, 'Eliminó artículo Código: 1', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:51:06', 4),
(633, 15, 'Registró artículo en inventario.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:51:11', 4),
(634, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:51:26', 4),
(635, 17, 'Asignó el artículo ID: 4', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:51:39', 4),
(636, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:51:43', 4),
(637, 15, 'Modificó artículo Código: 4', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:51:49', 4),
(638, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:52:10', 4),
(639, 18, 'Registró devolución ID Asig: 2', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:52:26', 4),
(640, 15, 'Ingreso al Modulo de Artículos Inventario', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:52:33', 4),
(641, 17, 'Ingreso al Modulo de Asignaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:52:36', 4),
(642, 17, 'Asignó el artículo ID: 4', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-07-07 23:52:57', 4);

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
(12, 'Cargos', 'Gestión de cargos', 'hand-coins', 1),
(13, 'Pagos', 'Gestión de pagos', 'banknote', 1),
(14, 'Metodos de Pago', 'Gestión de métodos de pago', 'wallet', 1),
(15, 'Inventario de Articulos', 'Gestión de equipamiento', 'boxes', 1),
(16, 'Catalogo', 'Catálogo general', 'clipboard-pen-line', 1),
(17, 'Asignaciones', 'Gestión de asignaciones', 'list-plus', 1),
(18, 'Devoluciones', 'Gestión de devoluciones', 'list-restart', 1),
(19, 'Torneos', 'Gestión de torneos', 'trophy', 1),
(20, 'Equipos', 'Gestión de equipos', 'shield-half', 1),
(21, 'Premios', 'Gestión de premios', 'medal', 1),
(22, 'Palmares', 'Gestión de palmarés', 'podium', 1),
(23, 'Estadisticas', 'Gestión de estadísticas', 'chart-area', 1),
(99, 'IA', 'Módulo de Inteligencia Artificial', 'bot-message-square', 1),
(100, 'Atletas', 'Gestión de atletas', 'circle-star', 1),
(101, 'Conceptos de Cargos', 'Definición de conceptos contables', 'receipt', 1),
(102, 'Monedas', 'Gestión de tipos de moneda', 'coins', 1),
(103, 'Categoria de Catalogo', 'Gestión de categorías de equipo', 'layers-plus', 1),
(104, 'Estado Fisico', 'Gestión de control de calidad', 'badge-check', 1),
(105, 'Participaciones', 'Gestión de participaciones', 'shield-check', 1),
(106, 'Respaldo de Base de Datos', 'Gestión de respaldos de base de datos', 'server-cog', 1),
(107, 'Reportes Estadisticos', 'Generación de reportes', 'chart-column-stacked', 1),
(109, 'Permisos', 'Gestionar los permisos de los usuarios', 'user-key', 1),
(110, 'Tasa de Cambio', 'Gestionar las tasa de cambios', 'arrow-left-right', 1),
(112, 'Modulos', 'Gestion de modulos del sistema', 'component', 1);

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

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `tipo`, `creado_en`, `estatus`) VALUES
(25, 1, 'Cumpleaños Feliz', 'Hoy está de cumpleaños el atleta: Moises Torrellas.', 1, '2026-07-06 23:23:55', 2),
(26, 3, 'Cumpleaños Feliz', 'Hoy está de cumpleaños el atleta: Moises Torrellas.', 1, '2026-07-06 23:23:55', 2),
(27, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-06-22.', 2, '2026-07-06 23:23:55', 2),
(28, 3, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-06-22.', 2, '2026-07-06 23:23:55', 2),
(29, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-06-23.', 2, '2026-07-06 23:23:55', 2),
(30, 3, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-06-23.', 2, '2026-07-06 23:23:55', 2),
(31, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-06-23.', 2, '2026-07-06 23:23:55', 2),
(32, 3, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-06-23.', 2, '2026-07-06 23:23:55', 2),
(33, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-07-06.', 2, '2026-07-07 04:01:41', 2),
(34, 3, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-07-06.', 2, '2026-07-07 04:01:41', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
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
(3, 100, 'Ingresar A Atletas', 'ingresar_atleta', 'Poder Ingresar Al Modulo De Atletas', 1),
(4, 9, 'Registrar Representante', 'registrar_representante', 'Sin Descripción ', 1),
(5, 9, 'Modificar Representante', 'modificar_representante', 'Permitir La Modificacion De Un Representante Ya Ex', 1),
(6, 9, 'Eliminar Representante', 'eliminar_representante', 'Permitir La Modificacion De Un Representante Ya Ex', 1),
(7, 9, 'Ingresar A Representantes', 'ingresar_representantes', 'Permitir El Ingreso Al Modulo De Representantes', 1),
(8, 9, 'Generar Reporte De Representantes', 'generar_representante', 'Permitir Generar Un Reporte Sobre Los Representant', 1),
(9, 109, 'Ingresar A Permisos', 'ingresar_permisos', 'Permitir El Ingreso Al Modulo De Permisos', 1),
(10, 109, 'Registrar Permisos', 'registrar_permisos', 'Permitir El Registro De Un Nuevo Permiso', 1),
(11, 109, 'Modificar Permisos', 'modificar_permisos', 'Permitir La Modificacion De Los Permisos', 1),
(12, 109, 'Bloquear Permisos', 'bloquear_permisos', 'Permitir El Bloque De Los Permisos Para Que No Pueda Ser Accesible Por Ningun Usuario', 1),
(13, 10, 'Ingresar A Posiciones', 'ingresar_posiciones', 'Sin Descripción ', 1),
(14, 100, 'Modificar Atletas', 'modificar_atleta', 'Sin Descripción ', 1),
(15, 100, 'Generar Corriculum', 'curriculum_atleta', 'Sin Descripción ', 1),
(16, 100, 'Generar Reporte De Atletas', 'generar_atletas', 'Sin Descripción', 1),
(17, 100, 'Reinscribir Atletas', 'reinscribir_atleta', 'Sin Descripción ', 1),
(18, 10, 'Registrar Posiciones', 'registrar_posicion', 'Sin Descripción ', 1),
(19, 10, 'Eliminar Posiciones', 'eliminar_posicion', 'Sin Descripción ', 1),
(20, 10, 'Modificar Posiciones', 'modificar_posicion', 'Sin Descripción ', 1),
(21, 10, 'Generar Reporte De Posiciones', 'generar_posiciones', 'Sin Descripción ', 1),
(22, 12, 'Ingresar A Cargos', 'ingresar_cargo', 'Sin Descripción ', 1),
(23, 12, 'Registrar Cargos', 'registrar_cargo', 'Sin Descripción ', 1),
(24, 12, 'Modificar Cargos', 'modificar_cargo', 'Sin Descripción ', 1),
(25, 12, 'Anular Cargos', 'anular_cargo', 'Sin Descripción', 1),
(26, 12, 'Generar Reporte De Cargos', 'generar_cargo', 'Sin Descripción ', 1),
(27, 13, 'Ingresar A Pagos', 'ingresar_pago', 'Sin Descripción ', 1),
(28, 13, 'Registrar Pagos', 'registrar_pago', 'Sin Descripción ', 1),
(29, 13, 'Anular Pagos', 'anular_pago', 'Sin Descripción ', 1),
(30, 13, 'Generar Reporte De Pagos', 'generar_pago', 'Sin Descripción ', 1),
(31, 14, 'Ingresar A Metodos De Pago', 'ingresar_metodop', 'Sin Descripción ', 1),
(32, 14, 'Registrar Metodos De Pago', 'registrar_metodosp', 'Sin Descripción ', 1),
(33, 14, 'Modificar Metodo De Pago', 'modificar_metodop', 'Sin Descripción ', 1),
(34, 14, 'Eliminar Metodos De Pago', 'eliminar_metodop', 'Sin Descripción ', 1),
(35, 14, 'Bloquear Metodos De Pago', 'bloquear_metodop', 'Sin Descripción ', 1),
(36, 14, 'Generar Reportes De Metodos De Pago', 'generar_metodop', 'Sin Descripción ', 1),
(37, 102, 'Ingresar A Monedas', 'ingresar_moneda', 'Sin Descripción ', 1),
(38, 102, 'Registrar Monedas', 'registrar_moneda', 'Sin Descripción ', 1),
(39, 102, 'Modificar Monedas', 'modificar_moneda', 'Sin Descripción ', 1),
(40, 102, 'Eliminar Monedas', 'elimina_moneda', 'Sin Descripción ', 1),
(41, 102, 'Bloquear Monedas', 'bloquear_moneda', 'Sin Descripción ', 1),
(42, 102, 'Asignar Moneda Base', 'asignar_moneda', 'Sin Descripción ', 1),
(43, 102, 'Generar Reportes De Moneda', 'generar_moneda', 'Sin Descripción ', 1),
(44, 110, 'Ingresar A Tasa De Cambio', 'ingresar_tasa', 'Permitir El Ingreso Al Modulo De Tasa De Cambio', 1),
(45, 110, 'Sincronizar Tasa De Cambio', 'sincronizar_tasa', 'Permitir Sincronizar La Tasa De Cambio Del Dia De Forma Automatica', 1),
(46, 110, 'Registrar Tasa De Cambio', 'registrar_tasa', 'Permitir El Registro De Una Tasa Personalizada', 1),
(47, 11, 'Ingresar A Categorias', 'ingresar_categorias', 'Ingreso A Categorias', 1),
(48, 11, 'Registrar Categorias', 'registrar_categoria', 'Sin Descripción ', 1),
(49, 11, 'Modificar Categorias', 'modificar_categoria', 'Sin Descripción ', 1),
(50, 11, 'Eliminar Categorias', 'eliminar_categoria', 'Sin Descripción ', 1),
(51, 11, 'Generar Reportar', 'generar_categoria', 'Sin Descripción ', 1),
(52, 19, 'Ingresar A Torneos', 'ingresar_torneos', 'Permiso Para Poder Ingresar A Torneos', 1),
(53, 19, 'Registrar Torneo', 'registrar_torneo', 'Registrar Los Torneos', 1),
(54, 19, 'Modificar Torneo', 'modificar_torneo', 'Modificar Los Torneos Registrados', 1),
(55, 19, 'Eliminar Torneo', 'eliminar_torneo', 'Eliminar Los Torneos Registrados', 1),
(56, 101, 'Ingresar A Conceptos', 'ingresar_conceptos', 'Sin Descripción ', 1),
(57, 101, 'Registrar Conceptos', 'registrar_concepto', 'Sin Descripción ', 1),
(58, 101, 'Modificar Conceptos', 'modificar_concepto', 'Sin Descripción ', 1),
(59, 101, 'Eliminar Conceptos', 'eliminar_concepto', 'Sin Descripción', 1),
(60, 101, 'Generar Reporte De Conceptos De Cargo', 'generar_concepto', 'Sin Descripción ', 1),
(61, 19, 'Generar Reportes De Torneos', 'generar_torneos', 'Sin Descripción ', 1),
(62, 101, 'Bloquear Concepto', 'bloquear_concepto', 'Sin Descripción ', 1),
(63, 20, 'Ingresar A Equipos', 'ingresar_equipo', 'Sin Descripción ', 1),
(64, 20, 'Registrar Equipos', 'registrar_equipo', 'Sin Descripción ', 1),
(65, 20, 'Modificar Equipos', 'modificar_equipo', 'Sin Descripción ', 1),
(66, 20, 'Eliminar Equipos', 'eliminar_equipo', 'Sin Descripción ', 1),
(67, 20, 'Generar Reportes De Equipos', 'generar_equipo', 'Sin Descripción ', 1),
(68, 105, 'Ingresar A Participaciones', 'ingresar_partici', 'Sin Descripción ', 1),
(69, 105, 'Registrar Participaciones', 'registrar_partici', 'Sin Descripción ', 1),
(70, 105, 'Modificar Participacion', 'modificar_partici', 'Sin Descripción ', 1),
(71, 105, 'Eliminar Perticipaciones', 'eliminar_partici', 'Sin Descripción ', 1),
(72, 105, 'Generar Reporte De Participaciones', 'generar_partici', 'Sin Descripción ', 1),
(73, 21, 'Ingresar A Premios', 'ingresar_premio', 'Sin Descripción ', 1),
(74, 21, 'Registrar Premios', 'registrar_premio', 'Sin Descripción ', 1),
(75, 21, 'Modificar Premios', 'modificar_premio', 'Sin Descripción ', 1),
(76, 21, 'Eliminar Premios', 'eliminar_premio', 'Sin Descripción ', 1),
(77, 21, 'Generar Reporte De Premios', 'generar_premio', 'Sin Descripción ', 1),
(78, 22, 'Ingresar A Palmares', 'ingresar_palmares', 'Sin Descripción ', 1),
(79, 22, 'Registrar Palmares', 'registrar_palmares', 'Sin Descripción ', 1),
(80, 22, 'Modificar Palamares', 'modificar_palmares', 'Sin Descripción ', 1),
(81, 22, 'Eliminar Palmares', 'eliminar_palmares', 'Sin Descripción ', 1),
(82, 22, 'Generar Reporte De Palmares', 'generar_palmares', 'Sin Descripción ', 1),
(83, 23, 'Ingresar A Estadisticas', 'ingresar_estadistica', 'Sin Descripción ', 1),
(84, 23, 'Registrar Estadisticas', 'registrar_estadistica', 'Sin Descripción ', 1),
(85, 23, 'Modificar Estadisticas', 'modificar_estadistica', 'Sin Descripción ', 1),
(86, 23, 'Eliminar Estadisticas', 'eliminar_estadistica', 'Sin Descripción ', 1),
(87, 23, 'Generar Reporte De Estadisticas', 'generar_estadistica', 'Sin Descripción ', 1),
(88, 107, 'Ingresar A Reportes Estadisticos', 'ingresar_reportes', 'Sin Descripción ', 1),
(89, 1, 'Ingresar A Usuarios', 'ingresar_usuarios', 'Sin Descripción ', 1),
(90, 1, 'Registrar Usuarios', 'registrar_usuario', 'Sin Descripción ', 1),
(91, 1, 'Modificar Usuarios', 'modificar_usuario', 'Sin Descripción ', 1),
(92, 1, 'Eliminar Usuarios', 'eliminar_usuario', 'Sin Descripción ', 1),
(93, 1, 'Bloquear Usuarios', 'bloquear_usuario', 'Sin Descripción ', 1),
(94, 1, 'Editar Permisos De Los Usuarios', 'permisos_usuario', 'Sin Descripción ', 1),
(95, 1, 'Generar Reportes De Usuarios', 'generar_usuarios', 'Sin Descripción ', 1),
(96, 2, 'Ingresar A Roles', 'ingresar_rol', 'Sin Descripción ', 1),
(97, 2, 'Registrar Roles', 'registrar_rol', 'Sin Descripción ', 1),
(98, 2, 'Eliminar Roles', 'eliminar_rol', 'Sin Descripción ', 1),
(99, 2, 'Modificar Roles', 'modificar_rol', 'Sin Descripción ', 1),
(100, 2, 'Editar Permisos De Los Roles', 'permisos_rol', 'Sin Descripción ', 1),
(101, 2, 'Generar Reportes De Roles', 'generar_rol', 'Sin Descripción ', 1),
(102, 112, 'Ingresar A Modulos', 'ingresar_modulos', 'Sin Descripción ', 1),
(103, 112, 'Modificar Modulos', 'modificar_modulo', 'Sin Descripción ', 1),
(104, 3, 'Ingreso A Bitacora', 'ingresar_bitacora', 'Sin Descripción ', 1),
(105, 3, 'Generar Reportes De Bitacora', 'generar_bitacora', 'Sin Descripción ', 1),
(106, 18, 'Ingresar A Devoluciones', 'ingresar_devoluciones', 'Sin Descripción', 1),
(107, 18, 'Registrar Devoluciones', 'registrar_devoluciones', 'Sin Descripción', 1),
(108, 18, 'Modificar Devoluciones', 'modificar_devoluciones', 'Sin Descripción', 1),
(109, 18, 'Anular Devoluciones', 'eliminar_devoluciones', 'Sin Descripción', 1),
(110, 18, 'Generar Reporte De Devoluciones', 'reporte_devoluciones', 'Sin Descripción', 1),
(111, 16, 'Ingresar A Catalogos', 'ingresar_catalogos', 'Sin Descripción ', 1),
(112, 16, 'Registrar Catalogo', 'registrar_catalogo', 'Sin Descripción ', 1),
(113, 16, 'Modificar Catalogo', 'modificar_catalogo', 'Sin Descripción ', 1),
(114, 16, 'Eliminar Catalogo', 'eliminar_catalogo', 'Sin Descripción ', 1),
(115, 16, 'Generar Catalogo', 'generar_catalogo', 'Sin Descripción ', 1),
(116, 15, 'Ingresar Al Inventario De Articulos', 'ingresar_articulos', 'Sin Descripción ', 1),
(117, 15, 'Registrar Articulo', 'registrar_articulo', 'Sin Descripción ', 1),
(118, 15, 'Modificar Articulo', 'modificar_articulo', 'Sin Descripción ', 1),
(119, 15, 'Eliminar Articulo', 'eliminar_articulo', 'Sin Descripción ', 1),
(120, 15, 'Generar Reporte De Articulo', 'generar_articulo', 'Sin Descripción', 1),
(121, 104, 'Ingresar A Estado Fisico', 'ingresar_estfisico', 'Sin Descripción', 1),
(122, 104, 'Registrar Estado Fisico', 'registrar_estfisico', 'Sin Descripción', 1),
(123, 104, 'Modificar Estado Fisico', 'modificar_estfisico', 'Sin Descripción', 1),
(124, 104, 'Eliminar Estado Fisico', 'eliminar_estfisico', 'Sin Descripción', 1),
(125, 104, 'Generar Reporte de Estado fisico', 'generar_estfisico', 'Sin Descripción ', 1),
(126, 103, 'Ingresar A Categorias Catalogo', 'ingresar_catcatalogos', 'Sin Descripción ', 1),
(127, 103, 'Registrar Categoria Catalogos', 'registrar_catcatalogo', 'Sin Descripción ', 1),
(128, 103, 'Modificar Categoria Catalogo', 'modificar_catcatalogo', 'Sin Descripción ', 1),
(129, 103, 'Eliminar Categoria Catalogo', 'eliminar_catcatalogo', 'Sin Descripción ', 1),
(130, 103, 'Generar Reporte', 'generar_catcatalogo', 'Sin Descripción ', 1),
(131, 17, 'Ingresar A Asignaciones', 'ingresar_asignaciones', 'Sin Descripción ', 1),
(132, 17, 'Registrar Asignaciones', 'registrar_asignacion', 'Sin Descripción ', 1),
(133, 17, 'Modificar Asignaciones', 'modificar_asignacion', 'Sin Descripción ', 1),
(134, 17, 'Anular Asignacion', 'anular_asignacion', 'Sin Descripción ', 1),
(135, 17, 'Generar Reporte Asignacion', 'generar_asignaciones', 'Sin Descripción ', 1);

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
(16, 4, 4),
(17, 5, 4),
(18, 6, 4),
(19, 7, 4),
(20, 8, 4),
(21, 13, 4),
(22, 18, 4),
(23, 19, 4),
(24, 20, 4),
(25, 21, 4),
(26, 47, 4),
(27, 48, 4),
(28, 49, 4),
(29, 50, 4),
(30, 51, 4),
(31, 1, 4),
(32, 2, 4),
(33, 3, 4),
(34, 14, 4),
(35, 15, 4),
(36, 16, 4),
(37, 17, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respaldos`
--

DROP TABLE IF EXISTS `respaldos`;
CREATE TABLE `respaldos` (
  `id_respaldo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_archivo` varchar(100) NOT NULL,
  `peso` varchar(20) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estatus` tinyint(2) NOT NULL DEFAULT 1
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
(1, 'Superusuario', 'Acceso A Todo El Sistema', 2, 1),
(4, 'Contador', 'Maneja El Area Contable Del Club', 3, 1),
(5, 'Soporte', 'Rol Para Soporte', 1, 1);

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
(1, '12345678', 'Moises', 'Admin', 'user_12345678_1783394677.png', '1234-5678909', '$2y$10$jIi5Y2TlNk61pSslaz3QW.aBfFfqF0vT1aVSA5sjCDNvVpV/YiAMm', 'admin@gmail.com', 1, '2026-07-07 16:40:53', 0, 1, 1),
(3, '29506932', 'Moises', 'Torrellas', 'default.png', '0023-2323232', '$2y$10$5iUNe57cdVOn3lV23254OOyB3S0GEiqfeXgsPFUtaF/ZQ/kp8YlNS', 'moitcj@gmail.com', 4, '2026-07-07 10:16:45', 0, 1, 1),
(4, '87654321', 'Soporte', 'Soporte', 'default.png', '0575-5555555', '$2y$10$aGsNgc1FDztpxbfMRuEHZeCSGVlbvT6r6/kDbIRDHWYwthsVb.uK6', 'soporte@gmail.com', 5, '2026-07-07 19:17:00', 0, 1, 1);

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
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=643;

--
-- AUTO_INCREMENT de la tabla `excepciones`
--
ALTER TABLE `excepciones`
  MODIFY `id_excepcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  MODIFY `id_respaldo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
