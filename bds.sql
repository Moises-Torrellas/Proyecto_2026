-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2026 a las 20:57:03
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
-- Base de datos: `bds`
--
CREATE DATABASE IF NOT EXISTS `bds` DEFAULT CHARACTER SET utf8 COLLATE utf8_spanish2_ci;
USE `bds`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

CREATE TABLE `bitacora` (
  `id_bitacora` int(255) NOT NULL,
  `id_modulo` int(255) DEFAULT NULL,
  `acciones` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `idUsuario` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `bitacora`
--

INSERT INTO `bitacora` (`id_bitacora`, `id_modulo`, `acciones`, `fecha`, `hora`, `idUsuario`) VALUES
(67, 1, 'Accedió al módulo', '2026-04-22', '14:23:00', 1),
(68, 5, 'Cierre de sesión.', '2026-04-22', '14:26:28', 1),
(69, 4, 'Inicio de sesión exitoso', '2026-04-22', '14:35:10', 1),
(70, 1, 'Modificó un usuario ID: 1', '2026-04-22', '14:35:32', 1),
(71, 5, 'Cierre de sesión.', '2026-04-22', '14:36:37', 1),
(72, 4, 'Inicio de sesión exitoso', '2026-04-22', '14:36:55', 21),
(73, 5, 'Cierre de sesión.', '2026-04-22', '14:37:19', 21),
(74, 4, 'Inicio de sesión exitoso', '2026-04-22', '14:39:00', 1),
(75, 4, 'Inicio de sesión exitoso', '2026-04-22', '16:38:09', 1),
(76, 8, 'Registró al representante: 1223345', '2026-04-22', '16:50:21', 1),
(77, 8, 'Registró al representante: 1223345', '2026-04-22', '16:51:36', 1),
(78, 8, 'Registró al representante: 00000000', '2026-04-22', '16:51:56', 1),
(79, 4, 'Inicio de sesión exitoso', '2026-04-22', '19:51:30', 1),
(80, 8, 'Registró al representante: 87654321', '2026-04-22', '20:20:36', 1),
(81, 5, 'Cierre de sesión.', '2026-04-22', '23:12:36', 1),
(82, 4, 'Inicio de sesión exitoso', '2026-04-23', '10:15:43', 1),
(83, 8, 'Eliminó al representante: 2', '2026-04-23', '10:48:51', 1),
(84, 8, 'Eliminó al representante: 1', '2026-04-23', '11:33:18', 1),
(85, 8, 'Registró al representante: 87654321', '2026-04-23', '11:33:48', 1),
(86, 8, 'Registró al representante: 12345678', '2026-04-23', '11:35:59', 1),
(87, 8, 'Registró al representante: 87654321', '2026-04-23', '14:01:57', 1),
(88, 8, 'Eliminó al representante: 1', '2026-04-23', '14:05:36', 1),
(89, 8, 'Registró al representante: 12345678', '2026-04-23', '14:07:37', 1),
(90, 4, 'Inicio de sesión exitoso', '2026-04-23', '14:46:21', 1),
(91, 8, 'Registró al representante: 87654321', '2026-04-23', '14:52:44', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo`
--

CREATE TABLE `modulo` (
  `id_modulo` int(255) NOT NULL,
  `nombre_modulo` varchar(100) NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `modulo`
--

INSERT INTO `modulo` (`id_modulo`, `nombre_modulo`, `estatus`) VALUES
(1, 'Usuarios', 1),
(2, 'Roles', 1),
(3, 'Bitacora', 1),
(4, 'Inicio de Sesion', 1),
(5, 'Cerrar Sesion', 1),
(8, 'Recuperación De Contraseña', 1),
(9, 'Representantes', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `id_permiso` int(255) NOT NULL,
  `id_rol` int(255) NOT NULL,
  `id_modulo` int(255) NOT NULL,
  `eliminar` tinyint(1) NOT NULL DEFAULT 0,
  `modificar` tinyint(1) NOT NULL DEFAULT 0,
  `incluir` tinyint(1) NOT NULL DEFAULT 0,
  `reporte` tinyint(1) NOT NULL DEFAULT 0,
  `otros` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(255) NOT NULL,
  `nombre_rol` varchar(35) NOT NULL,
  `nivel_rol` tinyint(1) NOT NULL DEFAULT 3,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `nivel_rol`, `estatus`) VALUES
(1, 'Super Usuario', 1, 1),
(2, 'Administrador', 2, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `idUsuario` int(255) NOT NULL,
  `cedulaUsuario` varchar(10) NOT NULL,
  `nombreUsuario` varchar(35) NOT NULL,
  `apellidoUsuario` varchar(35) NOT NULL,
  `telefonoUsuario` varchar(15) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `correo` varchar(60) NOT NULL,
  `id_rol` int(255) NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1,
  `bloqueo` tinyint(2) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`idUsuario`, `cedulaUsuario`, `nombreUsuario`, `apellidoUsuario`, `telefonoUsuario`, `contraseña`, `correo`, `id_rol`, `estatus`, `bloqueo`) VALUES
(1, '12345678', 'Admin', 'Admin', '0000-0000000', '$2y$10$z9rD8xGPyg4.JegVpLgfi.WEi2HPKEKGvOQYRDZfZPqwlzxRqS.y.', 'admin@gmail.com', 1, 1, 1),
(21, '29506932', 'Moises', 'Torrellas', '0000-0000000', '$2y$10$iT6mg6QOCrcz9dSL/JFi.eieRvOc0c4z8I7ObS75m04ZWuhTxr9lS', 'moitcj@gmail.com', 2, 1, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id_bitacora`) USING BTREE,
  ADD KEY `idUsuario` (`idUsuario`),
  ADD KEY `modulo` (`id_modulo`);

--
-- Indices de la tabla `modulo`
--
ALTER TABLE `modulo`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`id_permiso`) USING BTREE,
  ADD KEY `id_modulo` (`id_modulo`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idUsuario`) USING BTREE,
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
  MODIFY `id_bitacora` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT de la tabla `modulo`
--
ALTER TABLE `modulo`
  MODIFY `id_modulo` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id_permiso` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `bitacora_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`),
  ADD CONSTRAINT `bitacora_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`);

--
-- Filtros para la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD CONSTRAINT `permiso_ibfk_1` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permiso_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
