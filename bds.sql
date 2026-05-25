-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-05-2026 a las 05:08:54
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

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
(1, 4, 'Inicio de sesión exitoso', '2026-05-08', '07:48:11', 1),
(2, 102, 'Bloqueo la moneda: 5', '2026-05-08', '07:54:12', 1),
(3, 102, 'Desbloqueo la moneda: 5', '2026-05-08', '07:54:15', 1),
(4, 100, 'Registro al Atleta: jose jose perez perez', '2026-05-08', '08:16:17', 1),
(5, 100, 'Modifico al Atleta: Jose Jose Perez Perez', '2026-05-08', '08:16:51', 1),
(6, 101, 'Actualizó el estatus del concepto de pago 3', '2026-05-08', '08:18:44', 1),
(7, 101, 'Actualizó el estatus del concepto de pago 3', '2026-05-08', '08:18:46', 1),
(8, 101, 'Modifico el proceso de pago: Viaticos 30.30', '2026-05-08', '08:18:49', 1),
(9, 4, 'Inicio de sesión exitoso', '2026-05-08', '08:42:46', 1),
(10, 99, 'Consultó información al asistente Cani sobre Categorías/Torneos.', '2026-05-08', '08:43:05', 1),
(11, 99, 'Consultó información al asistente Cani sobre Categorías/Torneos.', '2026-05-08', '08:43:43', 1),
(12, 99, 'Consultó información al asistente Cani sobre Categorías/Torneos.', '2026-05-08', '08:44:05', 1),
(13, 4, 'Inicio de sesión exitoso', '2026-05-08', '08:56:53', 1),
(14, 2, 'Modifico el Rol: Entrenador', '2026-05-08', '09:54:53', 1),
(15, 4, 'Inicio de sesión exitoso', '2026-05-12', '15:22:54', 1),
(16, 4, 'Inicio de sesión exitoso', '2026-05-13', '16:33:03', 1),
(17, 4, 'Inicio de sesión exitoso', '2026-05-13', '18:43:26', 1),
(18, 102, 'Bloqueo la moneda: 4', '2026-05-13', '20:47:09', 1),
(19, 102, 'Desbloqueo la moneda: 4', '2026-05-13', '20:47:12', 1),
(20, 101, 'Actualizó el estatus del concepto de pago 2', '2026-05-13', '20:49:00', 1),
(21, 101, 'Actualizó el estatus del concepto de pago 2', '2026-05-13', '20:49:03', 1),
(22, 5, 'Cierre de sesión.', '2026-05-14', '04:02:12', 1),
(23, 4, 'Inicio de sesión exitoso', '2026-05-14', '12:19:17', 1),
(24, 4, 'Inicio de sesión exitoso', '2026-05-14', '22:05:53', 1),
(25, 4, 'Inicio de sesión exitoso', '2026-05-17', '23:47:21', 1),
(26, 100, 'Modifico al Atleta: Jose Jose Perez Perez', '2026-05-17', '23:57:46', 1),
(27, 5, 'Cierre de sesión.', '2026-05-17', '23:57:54', 1),
(28, 4, 'Inicio de sesión exitoso', '2026-05-18', '00:05:30', 1),
(29, 5, 'Cierre de sesión.', '2026-05-18', '00:37:46', 1),
(30, 4, 'Inicio de sesión exitoso', '2026-05-18', '00:39:52', 1),
(31, 4, 'Inicio de sesión exitoso', '2026-05-18', '00:40:11', 1),
(32, 4, 'Inicio de sesión exitoso', '2026-05-18', '00:42:51', 1),
(33, 4, 'Inicio de sesión exitoso', '2026-05-18', '00:53:13', 1),
(34, 5, 'Cierre de sesión.', '2026-05-18', '00:54:57', 1),
(35, 4, 'Inicio de sesión exitoso', '2026-05-18', '00:58:59', 1),
(36, 4, 'Inicio de sesión exitoso', '2026-05-18', '10:23:51', 1),
(37, 100, 'Modifico al Atleta: Jose Jose Perez Perez', '2026-05-18', '10:24:22', 1),
(38, 5, 'Cierre de sesión.', '2026-05-18', '10:24:36', 1),
(39, 4, 'Inicio de sesión exitoso', '2026-05-18', '10:24:57', 1),
(40, 4, 'Inicio de sesión exitoso', '2026-05-18', '10:26:26', 1),
(41, 4, 'Inicio de sesión exitoso', '2026-05-18', '10:35:22', 1),
(42, 4, 'Inicio de sesión exitoso', '2026-05-18', '12:18:29', 1),
(43, 4, 'Inicio de sesión exitoso', '2026-05-18', '12:26:26', 1),
(44, 5, 'Cierre de sesión.', '2026-05-18', '12:45:35', 1),
(45, 4, 'Inicio de sesión exitoso', '2026-05-18', '12:45:59', 1),
(46, 5, 'Cierre de sesión.', '2026-05-18', '12:48:40', 1),
(47, 4, 'Inicio de sesión exitoso', '2026-05-18', '12:59:25', 1),
(48, 5, 'Cierre de sesión.', '2026-05-18', '12:59:37', 1),
(49, 4, 'Inicio de sesión exitoso', '2026-05-18', '13:08:17', 1),
(50, 4, 'Inicio de sesión exitoso', '2026-05-18', '16:30:24', 1),
(51, 5, 'Cierre de sesión.', '2026-05-18', '16:31:19', 1),
(52, 4, 'Inicio de sesión exitoso', '2026-05-18', '16:31:32', 1),
(53, 5, 'Cierre de sesión.', '2026-05-18', '16:39:34', 1),
(54, 4, 'Inicio de sesión exitoso', '2026-05-18', '16:39:52', 1),
(55, 5, 'Cierre de sesión.', '2026-05-18', '17:15:13', 1),
(56, 4, 'Inicio de sesión exitoso', '2026-05-18', '17:15:28', 1),
(57, 5, 'Cierre de sesión.', '2026-05-18', '17:35:20', 1),
(58, 4, 'Inicio de sesión exitoso', '2026-05-18', '17:35:28', 1),
(59, 4, 'Inicio de sesión exitoso', '2026-05-19', '09:47:30', 1),
(60, 11, 'Registró la categoría: U-17', '2026-05-19', '09:50:27', 1),
(61, 100, 'Registro al Atleta: MARIO MARIO BROS BROS', '2026-05-19', '09:51:55', 1),
(62, 100, 'Modifico al Atleta: Jose Jose Perez Perez', '2026-05-19', '09:52:15', 1),
(63, 5, 'Cierre de sesión.', '2026-05-19', '09:52:34', 1),
(64, 4, 'Inicio de sesión exitoso', '2026-05-19', '09:54:44', 1),
(65, 5, 'Cierre de sesión.', '2026-05-19', '10:05:18', 1),
(66, 4, 'Inicio de sesión exitoso', '2026-05-19', '10:08:24', 1),
(67, 5, 'Cierre de sesión.', '2026-05-19', '10:10:44', 1),
(68, 4, 'Inicio de sesión exitoso', '2026-05-19', '14:53:14', 1),
(69, 5, 'Cierre de sesión.', '2026-05-19', '15:27:59', 1),
(70, 4, 'Inicio de sesión exitoso', '2026-05-19', '15:30:08', 1),
(71, 5, 'Cierre de sesión.', '2026-05-19', '15:32:02', 1),
(72, 4, 'Inicio de sesión exitoso', '2026-05-19', '15:35:21', 1),
(73, 5, 'Cierre de sesión.', '2026-05-19', '15:51:30', 1),
(74, 4, 'Inicio de sesión exitoso', '2026-05-19', '15:52:05', 1),
(75, 5, 'Cierre de sesión.', '2026-05-19', '17:15:59', 1),
(76, 4, 'Inicio de sesión exitoso', '2026-05-19', '17:30:47', 1),
(77, 100, 'Registro al Atleta: Moises Jesus Torrellas colmenarez', '2026-05-19', '17:35:50', 1),
(78, 5, 'Cierre de sesión.', '2026-05-19', '23:15:06', 1),
(79, 4, 'Inicio de sesión exitoso', '2026-05-20', '13:17:01', 1),
(80, 5, 'Cierre de sesión.', '2026-05-20', '13:54:52', 1),
(81, 4, 'Inicio de sesión exitoso', '2026-05-20', '21:40:35', 1),
(82, 12, 'Generó un cargo de 30 al atleta ID: 13', '2026-05-20', '22:54:51', 1),
(83, 12, 'Generó un cargo de 30 al atleta ID: 13', '2026-05-20', '22:58:18', 1),
(84, 12, 'Generó un cargo de 30 al atleta ID: 13', '2026-05-20', '23:01:08', 1),
(85, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:01:18', 1),
(86, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:01:41', 1),
(87, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:03:44', 1),
(88, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:06:55', 1),
(89, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:12:05', 1),
(90, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:16:09', 1),
(91, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:19:57', 1),
(92, 12, 'Anuló la cuenta por cobrar ID: 1', '2026-05-20', '23:35:13', 1),
(93, 12, 'Generó un cargo de 13000 al atleta ID: 14', '2026-05-20', '23:36:02', 1),
(94, 12, 'Modificó la cuenta por cobrar ID: 2', '2026-05-20', '23:40:44', 1),
(95, 12, 'Modificó la cuenta por cobrar ID: 2', '2026-05-20', '23:47:03', 1),
(96, 12, 'Modificó la cuenta por cobrar ID: 2', '2026-05-20', '23:47:56', 1),
(97, 12, 'Modificó la cuenta por cobrar ID: 2', '2026-05-20', '23:52:00', 1),
(98, 4, 'Inicio de sesión exitoso', '2026-05-22', '21:23:28', 1),
(100, 106, 'Eliminó el archivo de respaldo: backup_cannibalsbd_2026-05-22_22-10-53.sql', '2026-05-22', '22:24:44', 1),
(101, 106, 'Eliminó el archivo de respaldo: backup_cannibalsbd_2026-05-22_21-37-46.sql', '2026-05-22', '22:24:49', 1),
(102, 106, 'Generó el respaldo: backup_cannibalsbd_2026-05-22_22-24-59.sql', '2026-05-22', '22:25:04', 1),
(103, 106, 'Generó el respaldo: backup_cannibalsbd_2026-05-22_23-03-34.sql', '2026-05-22', '23:03:37', 1),
(104, 106, 'Restauró el sistema usando: backup_cannibalsbd_2026-05-22_23-03-34.sql', '2026-05-22', '23:06:37', 1);

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
(106, 'Respaldo BD', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` int(255) NOT NULL,
  `id_usuario` int(255) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('cumpleaños','torneos','sistema','cuentas') NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id_notificacion`, `id_usuario`, `titulo`, `mensaje`, `tipo`, `creado_en`) VALUES
(4, 1, 'Cumpleaños Feliz', 'Hoy está de cumpleaños el atleta: Jose Jose Perez Perez.', 'cumpleaños', '2026-05-18 21:35:28'),
(5, 1, 'Cumpleaños Feliz', 'Hoy está de cumpleaños el atleta: Mario Mario Bros Bros.', 'cumpleaños', '2026-05-19 13:54:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_roles`
--

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
(91, 9, 10, 0, 0, 1, 1, 0, 0),
(92, 9, 13, 0, 1, 1, 1, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_usuarios`
--

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respaldos`
--

CREATE TABLE `respaldos` (
  `id_respaldo` int(11) NOT NULL,
  `nombre_archivo` varchar(150) NOT NULL,
  `peso` varchar(20) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `idUsuario` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `respaldos`
--

INSERT INTO `respaldos` (`id_respaldo`, `nombre_archivo`, `peso`, `fecha_creacion`, `idUsuario`) VALUES
(1, 'backup_cannibalsbd_2026-05-22_23-03-34.sql', '26.15 KB', '2026-05-22 23:03:37', 1);

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
(2, 'Administrador', 2, 1),
(9, 'Entrenador', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

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
(1, '12345678', 'Admin', 'Admin', 'default.png', '0000-0000000', '$2y$10$z9rD8xGPyg4.JegVpLgfi.WEi2HPKEKGvOQYRDZfZPqwlzxRqS.y.', 'admin@gmail.com', 1, 1, 1);

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
  MODIFY `id_bitacora` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de la tabla `modulo`
--
ALTER TABLE `modulo`
  MODIFY `id_modulo` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `permisos_roles`
--
ALTER TABLE `permisos_roles`
  MODIFY `id_permiso_rol` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  MODIFY `id_respaldo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
