-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-05-2026 a las 06:19:48
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
CREATE DATABASE IF NOT EXISTS `bds` DEFAULT CHARACTER SET utf8 COLLATE utf8_spanish_ci;
USE `bds`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bitacora`
--

DROP TABLE IF EXISTS `bitacora`;
CREATE TABLE `bitacora` (
  `id_bitacora` int(255) NOT NULL,
  `id_modulo` int(255) DEFAULT NULL,
  `acciones` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `idUsuario` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo`
--

DROP TABLE IF EXISTS `modulo`;
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
(9, 'Representantes', 1),
(10, 'Posiciones', 1),
(11, 'Categorias', 1),
(12, 'Cuentas por Cobrar', 1),
(13, 'Pagos', 1),
(14, 'Metodos de Pago', 1),
(15, 'Equipamientos', 1),
(16, 'Catalogo', 1),
(17, 'Asignaciones', 1),
(18, 'Devoluciones', 1),
(19, 'Torneos', 1),
(20, 'Equipos', 1),
(21, 'Premios', 1),
(22, 'Palmares', 1),
(23, 'Estadisticas', 1),
(99, 'IA', 1),
(100, 'Atletas', 1),
(101, 'Conceptos de Cuentas', 1),
(102, 'Monedas', 1),
(103, 'Categoria de Equipamiento', 1),
(104, 'Calidad', 1),
(105, 'Participaciones', 1),
(106, 'Respaldo BD', 1),
(107, 'Reportes', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id_notificacion` int(255) NOT NULL,
  `id_usuario` int(255) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('cumpleaños','torneos','sistema','cuentas') NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_roles`
--

DROP TABLE IF EXISTS `permisos_roles`;
CREATE TABLE `permisos_roles` (
  `id_permiso_rol` int(255) NOT NULL,
  `id_rol` int(255) NOT NULL,
  `id_modulo` int(255) NOT NULL,
  `ingresar` tinyint(1) DEFAULT 0,
  `registrar` tinyint(1) NOT NULL DEFAULT 0,
  `eliminar` tinyint(1) NOT NULL DEFAULT 0,
  `modificar` tinyint(1) NOT NULL DEFAULT 0,
  `reporte` tinyint(1) NOT NULL DEFAULT 0,
  `otros` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `permisos_roles`
--

INSERT INTO `permisos_roles` (`id_permiso_rol`, `id_rol`, `id_modulo`, `ingresar`, `registrar`, `eliminar`, `modificar`, `reporte`, `otros`) VALUES
(139, 9, 9, 1, 0, 0, 0, 0, 0),
(140, 9, 10, 1, 0, 0, 0, 0, 0),
(141, 9, 11, 1, 0, 0, 0, 0, 0),
(142, 9, 12, 1, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_usuarios`
--

DROP TABLE IF EXISTS `permisos_usuarios`;
CREATE TABLE `permisos_usuarios` (
  `id_permiso_usuario` int(100) NOT NULL,
  `idUsuario` int(100) NOT NULL,
  `id_modulo` int(100) NOT NULL,
  `ingresar` tinyint(1) NOT NULL DEFAULT 0,
  `registrar` tinyint(1) NOT NULL DEFAULT 0,
  `eliminar` tinyint(1) NOT NULL DEFAULT 0,
  `modificar` tinyint(1) NOT NULL DEFAULT 0,
  `reporte` tinyint(1) NOT NULL DEFAULT 0,
  `otros` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `permisos_usuarios`
--

INSERT INTO `permisos_usuarios` (`id_permiso_usuario`, `idUsuario`, `id_modulo`, `ingresar`, `registrar`, `eliminar`, `modificar`, `reporte`, `otros`) VALUES
(54, 25, 9, 1, 1, 1, 1, 0, 0),
(55, 25, 10, 1, 0, 1, 1, 0, 0),
(56, 25, 11, 1, 0, 0, 0, 0, 0),
(57, 25, 12, 1, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respaldos`
--

DROP TABLE IF EXISTS `respaldos`;
CREATE TABLE `respaldos` (
  `id_respaldo` int(11) NOT NULL,
  `nombre_archivo` varchar(150) NOT NULL,
  `peso` varchar(20) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `idUsuario` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
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
(2, 'Administrador', 2, 1),
(9, 'Entrenador', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `idUsuario` int(255) NOT NULL,
  `cedulaUsuario` varchar(10) NOT NULL,
  `nombreUsuario` varchar(35) NOT NULL,
  `apellidoUsuario` varchar(35) NOT NULL,
  `foto` varchar(255) DEFAULT 'default.png',
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

INSERT INTO `usuarios` (`idUsuario`, `cedulaUsuario`, `nombreUsuario`, `apellidoUsuario`, `foto`, `telefonoUsuario`, `contraseña`, `correo`, `id_rol`, `estatus`, `bloqueo`) VALUES
(1, '12345678', 'Admin', 'Admin', 'default.png', '0000-0000000', '$2y$10$z9rD8xGPyg4.JegVpLgfi.WEi2HPKEKGvOQYRDZfZPqwlzxRqS.y.', 'admin@gmail.com', 1, 1, 1),
(25, '29506932', 'Moises', 'Torrellas', 'default.png', '0415-6548585', '$2y$10$nbHVdqm0D.1BxZfSNqSCgu3xJIZKG6ZqTuea6nW6DLpcALyW8jAw6', 'moitcj@gmail.com', 9, 1, 1);

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
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `permisos_roles`
--
ALTER TABLE `permisos_roles`
  ADD PRIMARY KEY (`id_permiso_rol`) USING BTREE,
  ADD KEY `id_modulo` (`id_modulo`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `permisos_usuarios`
--
ALTER TABLE `permisos_usuarios`
  ADD PRIMARY KEY (`id_permiso_usuario`),
  ADD KEY `idUsuario` (`idUsuario`,`id_modulo`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- Indices de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  ADD PRIMARY KEY (`id_respaldo`),
  ADD KEY `idUsuario` (`idUsuario`);

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
  MODIFY `id_bitacora` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulo`
--
ALTER TABLE `modulo`
  MODIFY `id_modulo` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos_roles`
--
ALTER TABLE `permisos_roles`
  MODIFY `id_permiso_rol` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT de la tabla `permisos_usuarios`
--
ALTER TABLE `permisos_usuarios`
  MODIFY `id_permiso_usuario` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  MODIFY `id_respaldo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD CONSTRAINT `bitacora_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`),
  ADD CONSTRAINT `bitacora_ibfk_3` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`idUsuario`);

--
-- Filtros para la tabla `permisos_roles`
--
ALTER TABLE `permisos_roles`
  ADD CONSTRAINT `permisos_roles_ibfk_1` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permisos_roles_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `permisos_usuarios`
--
ALTER TABLE `permisos_usuarios`
  ADD CONSTRAINT `permisos_usuarios_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`),
  ADD CONSTRAINT `permisos_usuarios_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`);

--
-- Filtros para la tabla `respaldos`
--
ALTER TABLE `respaldos`
  ADD CONSTRAINT `respaldos_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
