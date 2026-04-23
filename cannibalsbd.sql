-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2026 a las 20:57:32
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
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atletas`
--

CREATE TABLE `atletas` (
  `id_atleta` int(11) NOT NULL,
  `nombres` varchar(60) NOT NULL,
  `apellidos` varchar(60) NOT NULL,
  `cedula` varchar(13) NOT NULL,
  `genero` enum('H','M') NOT NULL,
  `fecha_nac` date NOT NULL,
  `nacionalidad` enum('V','E','P') NOT NULL,
  `foto` varchar(100) NOT NULL,
  `id_posicion` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_representante` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `atletas`
--

INSERT INTO `atletas` (`id_atleta`, `nombres`, `apellidos`, `cedula`, `genero`, `fecha_nac`, `nacionalidad`, `foto`, `id_posicion`, `id_categoria`, `id_representante`) VALUES
(2, 'dffd', 'dfdf', '12345678', 'H', '2004-05-12', 'V', 'FOTO', 1, 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atleta_premios`
--

CREATE TABLE `atleta_premios` (
  `id_a_premio` int(11) NOT NULL,
  `id_torneo` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `id_premio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogos`
--

CREATE TABLE `catalogos` (
  `id_catalogo` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `stock_minimo` varchar(10) NOT NULL,
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
(1, 'U-12', 12, 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos`
--

CREATE TABLE `conceptos` (
  `id_conceptos` int(11) NOT NULL,
  `conceptos` varchar(100) NOT NULL,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_cobrar`
--

CREATE TABLE `cuentas_cobrar` (
  `id_cobrar` int(11) NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `monto_total` decimal(10,0) NOT NULL,
  `monto_pendiente` decimal(10,0) NOT NULL,
  `fecha_emision` date NOT NULL,
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
-- Estructura de tabla para la tabla `devoluciones`
--

CREATE TABLE `devoluciones` (
  `id_devoluciones` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `fecha_devolucion` date NOT NULL,
  `id_equipamiento` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipamientos`
--

CREATE TABLE `equipamientos` (
  `id_equipamiento` int(11) NOT NULL,
  `id_catalogo` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `stock` varchar(10) NOT NULL,
  `stock_minimo` varchar(10) NOT NULL,
  `talla` varchar(150) NOT NULL,
  `id_estados` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id_equipos` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos_premios`
--

CREATE TABLE `equipos_premios` (
  `id_e_premios` int(11) NOT NULL,
  `id_torneos` int(11) NOT NULL,
  `id_equipo` int(11) NOT NULL,
  `id_premio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadisticas`
--

CREATE TABLE `estadisticas` (
  `id_estadisticas` int(11) NOT NULL,
  `id_torneo` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `goles` varchar(150) NOT NULL,
  `asistencias` varchar(150) NOT NULL,
  `penalizaciones` varchar(150) NOT NULL,
  `goles_contra` varchar(150) NOT NULL,
  `average` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_equipamiento`
--

CREATE TABLE `estado_equipamiento` (
  `id_estado` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id_metodos` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monedas`
--

CREATE TABLE `monedas` (
  `id_monedas` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `estatus` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pagos` int(11) NOT NULL,
  `id_cobrar` int(11) NOT NULL,
  `id_metodo` int(11) NOT NULL,
  `id_moneda` int(11) NOT NULL,
  `monto_pago` decimal(10,0) NOT NULL,
  `tasa_cambio` decimal(10,0) NOT NULL,
  `fecha_tasa` date NOT NULL,
  `referencia` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

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
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `posiciones`
--

INSERT INTO `posiciones` (`id_posicion`, `nombre`) VALUES
(1, 'Delantero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premios`
--

CREATE TABLE `premios` (
  `id_premio` int(11) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `nombre` int(11) NOT NULL
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
(2, 'Jose', 'Martinez', '12345678', 'V', 'Barquisimeto', '3333-3333333'),
(3, 'Maria', 'Perez', '87654321', 'E', 'Tocuyo', '2222-2222222');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_premios`
--

CREATE TABLE `tipos_premios` (
  `id_tipo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

CREATE TABLE `torneos` (
  `id_torneo` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `estatus` tinyint(4) NOT NULL
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
  ADD KEY `id_equipamiento` (`id_equipamiento`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `atletas`
--
ALTER TABLE `atletas`
  ADD PRIMARY KEY (`id_atleta`),
  ADD KEY `id_representante` (`id_representante`),
  ADD KEY `id_posicion` (`id_posicion`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `atleta_premios`
--
ALTER TABLE `atleta_premios`
  ADD PRIMARY KEY (`id_a_premio`),
  ADD KEY `id_atleta` (`id_atleta`),
  ADD KEY `id_premio` (`id_premio`),
  ADD KEY `id_torneo` (`id_torneo`);

--
-- Indices de la tabla `catalogos`
--
ALTER TABLE `catalogos`
  ADD PRIMARY KEY (`id_catalogo`),
  ADD KEY `id_posicion` (`id_posicion`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categorias`);

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
  ADD KEY `id_atleta` (`id_atleta`);

--
-- Indices de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_atleta` (`id_atleta`),
  ADD KEY `id_equipo` (`id_equipo`);

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id_devoluciones`),
  ADD KEY `id_atleta` (`id_atleta`),
  ADD KEY `id_equipamiento` (`id_equipamiento`),
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
-- Indices de la tabla `equipos_premios`
--
ALTER TABLE `equipos_premios`
  ADD PRIMARY KEY (`id_e_premios`),
  ADD KEY `id_equipo` (`id_equipo`),
  ADD KEY `id_premio` (`id_premio`),
  ADD KEY `id_torneos` (`id_torneos`);

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
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id_metodos`);

--
-- Indices de la tabla `monedas`
--
ALTER TABLE `monedas`
  ADD PRIMARY KEY (`id_monedas`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pagos`),
  ADD KEY `id_cobrar` (`id_cobrar`),
  ADD KEY `id_metodo` (`id_metodo`),
  ADD KEY `id_moneda` (`id_moneda`);

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
  ADD PRIMARY KEY (`id_premio`),
  ADD KEY `id_tipo` (`id_tipo`);

--
-- Indices de la tabla `representantes`
--
ALTER TABLE `representantes`
  ADD PRIMARY KEY (`id_representante`);

--
-- Indices de la tabla `tipos_premios`
--
ALTER TABLE `tipos_premios`
  ADD PRIMARY KEY (`id_tipo`);

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
  MODIFY `id_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `atleta_premios`
--
ALTER TABLE `atleta_premios`
  MODIFY `id_a_premio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `catalogos`
--
ALTER TABLE `catalogos`
  MODIFY `id_catalogo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categorias` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  MODIFY `id_conceptos` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT de la tabla `equipos_premios`
--
ALTER TABLE `equipos_premios`
  MODIFY `id_e_premios` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  MODIFY `id_estadisticas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado_equipamiento`
--
ALTER TABLE `estado_equipamiento`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id_metodos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `monedas`
--
ALTER TABLE `monedas`
  MODIFY `id_monedas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pagos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  MODIFY `id_participacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `posiciones`
--
ALTER TABLE `posiciones`
  MODIFY `id_posicion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `premios`
--
ALTER TABLE `premios`
  MODIFY `id_premio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `representantes`
--
ALTER TABLE `representantes`
  MODIFY `id_representante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_premios`
--
ALTER TABLE `tipos_premios`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id_torneo` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`id_equipamiento`) REFERENCES `equipamientos` (`id_equipamiento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `asignaciones_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado_equipamiento` (`id_estado`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `atletas`
--
ALTER TABLE `atletas`
  ADD CONSTRAINT `atletas_ibfk_1` FOREIGN KEY (`id_representante`) REFERENCES `representantes` (`id_representante`) ON UPDATE CASCADE,
  ADD CONSTRAINT `atletas_ibfk_2` FOREIGN KEY (`id_posicion`) REFERENCES `posiciones` (`id_posicion`),
  ADD CONSTRAINT `atletas_ibfk_3` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categorias`);

--
-- Filtros para la tabla `atleta_premios`
--
ALTER TABLE `atleta_premios`
  ADD CONSTRAINT `atleta_premios_ibfk_1` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `atleta_premios_ibfk_2` FOREIGN KEY (`id_premio`) REFERENCES `premios` (`id_premio`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `atleta_premios_ibfk_3` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `catalogos`
--
ALTER TABLE `catalogos`
  ADD CONSTRAINT `catalogos_ibfk_1` FOREIGN KEY (`id_posicion`) REFERENCES `posiciones` (`id_posicion`);

--
-- Filtros para la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  ADD CONSTRAINT `cuentas_cobrar_ibfk_1` FOREIGN KEY (`id_concepto`) REFERENCES `conceptos` (`id_conceptos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cuentas_cobrar_ibfk_2` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  ADD CONSTRAINT `detalles_equipos_ibfk_1` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detalles_equipos_ibfk_2` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipos`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `devoluciones_ibfk_1` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `devoluciones_ibfk_2` FOREIGN KEY (`id_equipamiento`) REFERENCES `equipamientos` (`id_equipamiento`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `devoluciones_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado_equipamiento` (`id_estado`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Filtros para la tabla `equipos_premios`
--
ALTER TABLE `equipos_premios`
  ADD CONSTRAINT `equipos_premios_ibfk_1` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `equipos_premios_ibfk_2` FOREIGN KEY (`id_premio`) REFERENCES `premios` (`id_premio`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `equipos_premios_ibfk_3` FOREIGN KEY (`id_torneos`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estadisticas`
--
ALTER TABLE `estadisticas`
  ADD CONSTRAINT `estadisticas_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `estadisticas_ibfk_2` FOREIGN KEY (`id_atleta`) REFERENCES `atletas` (`id_atleta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_cobrar`) REFERENCES `cuentas_cobrar` (`id_cobrar`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`id_metodo`) REFERENCES `metodos_pago` (`id_metodos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`id_moneda`) REFERENCES `monedas` (`id_monedas`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `participaciones`
--
ALTER TABLE `participaciones`
  ADD CONSTRAINT `participaciones_ibfk_1` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `participaciones_ibfk_2` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `premios`
--
ALTER TABLE `premios`
  ADD CONSTRAINT `premios_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_premios` (`id_tipo`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
