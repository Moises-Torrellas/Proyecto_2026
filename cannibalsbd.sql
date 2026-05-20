-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-05-2026 a las 20:59:39
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
-- Base de datos: `cannibalsbd`
--
CREATE DATABASE IF NOT EXISTS `cannibalsbd` DEFAULT CHARACTER SET utf32 COLLATE utf32_spanish_ci;
USE `cannibalsbd`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id_asignacion` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `id_equipamiento` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atletas`
--

CREATE TABLE `atletas` (
  `id_atleta` int(11) NOT NULL,
  `nombres` varchar(60) NOT NULL,
  `apellidos` varchar(60) NOT NULL,
  `doc_identidad` varchar(13) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `genero` enum('H','M') NOT NULL,
  `fecha_nac` date NOT NULL,
  `foto` varchar(100) NOT NULL DEFAULT 'default.png',
  `id_posicion` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_representante` int(11) DEFAULT NULL,
  `estatus` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `atletas`
--

INSERT INTO `atletas` (`id_atleta`, `nombres`, `apellidos`, `doc_identidad`, `telefono`, `direccion`, `genero`, `fecha_nac`, `foto`, `id_posicion`, `id_categoria`, `id_representante`, `estatus`) VALUES
(13, 'Jose Jose', 'Perez Perez', '32323232', NULL, NULL, 'H', '2012-05-18', 'atleta_2012-05-18_1779198735.png', 5, 7, 2, 1),
(14, 'Mario Mario', 'Bros Bros', '34324324', NULL, NULL, 'H', '2009-05-19', 'atleta_2009-05-19_1779198715.png', 5, 8, 2, 1),
(15, 'Moises Jesus', 'Torrellas Colmenarez', '29506932', '0412-0565231', 'El Tocuyo', 'H', '2002-07-25', 'atleta_2002-07-25_1779226550.png', 6, 4, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogos`
--

CREATE TABLE `catalogos` (
  `id_catalogo` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `stock_minimo` varchar(10) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `id_posicion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categorias` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `edad_min` int(2) NOT NULL,
  `edad_max` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categorias`, `nombre`, `edad_min`, `edad_max`) VALUES
(1, 'U-12', 11, 12),
(3, 'U-06', 5, 6),
(4, 'SENIOR', 18, 60),
(5, 'U-08', 7, 8),
(6, 'U-10', 9, 10),
(7, 'U-14', 13, 14),
(8, 'U-17', 15, 17);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_catalogo`
--

CREATE TABLE `categoria_catalogo` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `descripcion` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos`
--

CREATE TABLE `conceptos` (
  `id_conceptos` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `conceptos`
--

INSERT INTO `conceptos` (`id_conceptos`, `nombre`, `monto`, `estatus`) VALUES
(2, 'Mensualidad', 30.00, 1),
(3, 'Viaticos', 30.30, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_cobrar`
--

CREATE TABLE `cuentas_cobrar` (
  `id_cobrar` int(11) NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `id_moneda` int(100) NOT NULL,
  `monto_personalizado` decimal(10,0) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_equipos`
--

CREATE TABLE `detalles_equipos` (
  `id_detalle` int(11) NOT NULL,
  `id_equipo` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_palmares`
--

CREATE TABLE `detalles_palmares` (
  `id_detalle_palmares` int(100) NOT NULL,
  `id_palmares` int(100) NOT NULL,
  `id_premio` int(100) NOT NULL,
  `id_atleta` int(100) DEFAULT NULL,
  `id_equipo` int(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

CREATE TABLE `devoluciones` (
  `id_devolución` int(100) NOT NULL,
  `id_asignacion` int(100) NOT NULL,
  `id_estado` int(100) NOT NULL,
  `fecha devolución` date NOT NULL,
  `observación` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipamientos`
--

CREATE TABLE `equipamientos` (
  `id_equipamiento` int(11) NOT NULL,
  `id_catalogo` int(11) NOT NULL,
  `id_estados` int(11) NOT NULL,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id_equipos` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadisticas`
--

CREATE TABLE `estadisticas` (
  `id_estadisticas` int(11) NOT NULL,
  `id_torneo` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `goles` int(10) NOT NULL,
  `asistencias` int(10) NOT NULL,
  `penalizaciones` int(10) NOT NULL,
  `goles_contra` int(10) NOT NULL,
  `average` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_equipamiento`
--

CREATE TABLE `estado_equipamiento` (
  `id_estado` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `nivel_estado` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `estado_equipamiento`
--

INSERT INTO `estado_equipamiento` (`id_estado`, `nombre`, `nivel_estado`) VALUES
(1, 'Exelente', 1),
(3, 'Mas O Menos', 2),
(4, 'Mala', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial`
--

CREATE TABLE `historial` (
  `id_historial` int(100) NOT NULL,
  `id_atleta` int(100) NOT NULL,
  `fecha_emision` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id_metodos` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `nec_referencia` tinyint(4) NOT NULL DEFAULT 0,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monedas`
--

CREATE TABLE `monedas` (
  `id_moneda` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `abreviatura` varchar(4) NOT NULL,
  `simbolo` varchar(3) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `monedas`
--

INSERT INTO `monedas` (`id_moneda`, `nombre`, `abreviatura`, `simbolo`, `estatus`) VALUES
(4, 'Dolar', 'USD', '$', 1),
(5, 'Bolivares', 'VES', 'Bs', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_cobrar` int(11) NOT NULL,
  `id_metodo` int(11) NOT NULL,
  `id_moneda` int(11) NOT NULL,
  `monto_pago` decimal(10,0) NOT NULL,
  `tasa_cambio` decimal(10,0) NOT NULL,
  `fecha_tasa` date NOT NULL,
  `referencia` varchar(40) NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `palmares`
--

CREATE TABLE `palmares` (
  `id_palmares` int(100) NOT NULL,
  `id_torneo` int(100) NOT NULL,
  `fecha_registro` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participaciones`
--

CREATE TABLE `participaciones` (
  `id_participacion` int(11) NOT NULL,
  `id_torneo` int(11) NOT NULL,
  `id_equipo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posiciones`
--

CREATE TABLE `posiciones` (
  `id_posicion` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `abreviatura` varchar(4) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `posiciones`
--

INSERT INTO `posiciones` (`id_posicion`, `nombre`, `abreviatura`, `descripcion`) VALUES
(1, 'Delantero', 'DC', ''),
(5, 'Defensa', 'DF', ''),
(6, 'Portero', 'PR', ''),
(8, 'Medio', 'MD', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premios`
--

CREATE TABLE `premios` (
  `id_premio` int(11) NOT NULL,
  `tipo` enum('I','G') NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `representantes`
--

CREATE TABLE `representantes` (
  `id_representante` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `apellido` varchar(30) NOT NULL,
  `cedula` varchar(13) NOT NULL,
  `nacionalidad` enum('V','E','P') NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `telefono` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `representantes`
--

INSERT INTO `representantes` (`id_representante`, `nombre`, `apellido`, `cedula`, `nacionalidad`, `direccion`, `telefono`) VALUES
(2, 'Moises', 'Martinez', '12345678', 'V', 'Barquisimeto', '3333-3333333');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

CREATE TABLE `torneos` (
  `id_torneo` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `estatus` tinyint(3) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `id_atleta` (`id_atleta`),
  ADD KEY `id_equipamiento` (`id_equipamiento`);

--
-- Indices de la tabla `atletas`
--
ALTER TABLE `atletas`
  ADD PRIMARY KEY (`id_atleta`),
  ADD KEY `id_representante` (`id_representante`),
  ADD KEY `id_posicion` (`id_posicion`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `catalogos`
--
ALTER TABLE `catalogos`
  ADD PRIMARY KEY (`id_catalogo`),
  ADD KEY `id_posicion` (`id_posicion`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categorias`);

--
-- Indices de la tabla `categoria_catalogo`
--
ALTER TABLE `categoria_catalogo`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  ADD PRIMARY KEY (`id_conceptos`);

--
-- Indices de la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  ADD PRIMARY KEY (`id_cobrar`),
  ADD KEY `id_concepto` (`id_concepto`),
  ADD KEY `id_atleta` (`id_atleta`),
  ADD KEY `id_moneda` (`id_moneda`);

--
-- Indices de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_atleta` (`id_atleta`),
  ADD KEY `id_equipo` (`id_equipo`);

--
-- Indices de la tabla `detalles_palmares`
--
ALTER TABLE `detalles_palmares`
  ADD PRIMARY KEY (`id_detalle_palmares`),
  ADD KEY `id_palmares` (`id_palmares`,`id_premio`,`id_atleta`,`id_equipo`),
  ADD KEY `id_premio` (`id_premio`),
  ADD KEY `id_atleta` (`id_atleta`),
  ADD KEY `id_equipo` (`id_equipo`);

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id_devolución`),
  ADD KEY `id_asignacion` (`id_asignacion`,`id_estado`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `equipamientos`
--
ALTER TABLE `equipamientos`
  ADD PRIMARY KEY (`id_equipamiento`),
  ADD KEY `id_estados` (`id_estados`),
  ADD KEY `id_catalogo` (`id_catalogo`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id_equipos`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  ADD PRIMARY KEY (`id_estadisticas`),
  ADD KEY `id_torneo` (`id_torneo`),
  ADD KEY `id_atleta` (`id_atleta`);

--
-- Indices de la tabla `estado_equipamiento`
--
ALTER TABLE `estado_equipamiento`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `historial`
--
ALTER TABLE `historial`
  ADD PRIMARY KEY (`id_historial`),
  ADD UNIQUE KEY `id_atleta` (`id_atleta`),
  ADD UNIQUE KEY `id_atleta_2` (`id_atleta`),
  ADD KEY `id_atleta_3` (`id_atleta`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id_metodos`);

--
-- Indices de la tabla `monedas`
--
ALTER TABLE `monedas`
  ADD PRIMARY KEY (`id_moneda`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_cobrar` (`id_cobrar`),
  ADD KEY `id_metodo` (`id_metodo`),
  ADD KEY `id_moneda` (`id_moneda`);

--
-- Indices de la tabla `palmares`
--
ALTER TABLE `palmares`
  ADD PRIMARY KEY (`id_palmares`),
  ADD UNIQUE KEY `id_torneo` (`id_torneo`),
  ADD KEY `id_torneo_2` (`id_torneo`);

--
-- Indices de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  ADD PRIMARY KEY (`id_participacion`),
  ADD KEY `id_equipo` (`id_equipo`),
  ADD KEY `id_torneo` (`id_torneo`);

--
-- Indices de la tabla `posiciones`
--
ALTER TABLE `posiciones`
  ADD PRIMARY KEY (`id_posicion`);

--
-- Indices de la tabla `premios`
--
ALTER TABLE `premios`
  ADD PRIMARY KEY (`id_premio`);

--
-- Indices de la tabla `representantes`
--
ALTER TABLE `representantes`
  ADD PRIMARY KEY (`id_representante`);

--
-- Indices de la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`id_torneo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `atletas`
--
ALTER TABLE `atletas`
  MODIFY `id_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `catalogos`
--
ALTER TABLE `catalogos`
  MODIFY `id_catalogo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categorias` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  MODIFY `id_conceptos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  MODIFY `id_cobrar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_palmares`
--
ALTER TABLE `detalles_palmares`
  MODIFY `id_detalle_palmares` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id_devolución` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipamientos`
--
ALTER TABLE `equipamientos`
  MODIFY `id_equipamiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id_equipos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  MODIFY `id_estadisticas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado_equipamiento`
--
ALTER TABLE `estado_equipamiento`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `historial`
--
ALTER TABLE `historial`
  MODIFY `id_historial` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id_metodos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `monedas`
--
ALTER TABLE `monedas`
  MODIFY `id_moneda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  MODIFY `id_participacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `posiciones`
--
ALTER TABLE `posiciones`
  MODIFY `id_posicion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `premios`
--
ALTER TABLE `premios`
  MODIFY `id_premio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `representantes`
--
ALTER TABLE `representantes`
  MODIFY `id_representante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id_torneo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`id_equipamiento`) REFERENCES `equipamientos` (`id_equipamiento`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `atletas`
--
ALTER TABLE `atletas`
  ADD CONSTRAINT `atletas_ibfk_1` FOREIGN KEY (`id_representante`) REFERENCES `representantes` (`id_representante`) ON UPDATE CASCADE,
  ADD CONSTRAINT `atletas_ibfk_2` FOREIGN KEY (`id_posicion`) REFERENCES `posiciones` (`id_posicion`),
  ADD CONSTRAINT `atletas_ibfk_3` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categorias`);

--
-- Filtros para la tabla `catalogos`
--
ALTER TABLE `catalogos`
  ADD CONSTRAINT `catalogos_ibfk_1` FOREIGN KEY (`id_posicion`) REFERENCES `posiciones` (`id_posicion`),
  ADD CONSTRAINT `catalogos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_catalogo` (`id_categoria`);

--
-- Filtros para la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  ADD CONSTRAINT `cuentas_cobrar_ibfk_1` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos` (`id_conceptos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cuentas_cobrar_ibfk_2` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cuentas_cobrar_ibfk_3` FOREIGN KEY (`id_moneda`) REFERENCES `monedas` (`id_moneda`);

--
-- Filtros para la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  ADD CONSTRAINT `detalles_equipos_ibfk_1` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalles_equipos_ibfk_2` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipos`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_palmares`
--
ALTER TABLE `detalles_palmares`
  ADD CONSTRAINT `detalles_palmares_ibfk_1` FOREIGN KEY (`id_premio`) REFERENCES `premios` (`id_premio`),
  ADD CONSTRAINT `detalles_palmares_ibfk_2` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`),
  ADD CONSTRAINT `detalles_palmares_ibfk_3` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipos`),
  ADD CONSTRAINT `detalles_palmares_ibfk_4` FOREIGN KEY (`id_palmares`) REFERENCES `palmares` (`id_palmares`);

--
-- Filtros para la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `devoluciones_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignaciones` (`id_asignacion`),
  ADD CONSTRAINT `devoluciones_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado_equipamiento` (`id_estado`);

--
-- Filtros para la tabla `equipamientos`
--
ALTER TABLE `equipamientos`
  ADD CONSTRAINT `equipamientos_ibfk_1` FOREIGN KEY (`id_estados`) REFERENCES `estado_equipamiento` (`id_estado`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `equipamientos_ibfk_2` FOREIGN KEY (`id_catalogo`) REFERENCES `catalogos` (`id_catalogo`);

--
-- Filtros para la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categorias`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  ADD CONSTRAINT `estadisticas_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `estadisticas_ibfk_2` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial`
--
ALTER TABLE `historial`
  ADD CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cobrar`) REFERENCES `cuentas_cobrar` (`id_cobrar`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`id_metodo`) REFERENCES `metodos_pago` (`id_metodos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`id_moneda`) REFERENCES `monedas` (`id_moneda`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `palmares`
--
ALTER TABLE `palmares`
  ADD CONSTRAINT `palmares_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`);

--
-- Filtros para la tabla `participaciones`
--
ALTER TABLE `participaciones`
  ADD CONSTRAINT `participaciones_ibfk_1` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `participaciones_ibfk_2` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
