-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-06-2026 a las 04:24:41
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
CREATE DATABASE IF NOT EXISTS `cannibalsbd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci;
USE `cannibalsbd`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

DROP TABLE IF EXISTS `asignaciones`;
CREATE TABLE `asignaciones` (
  `id_asignacion` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `id_equipamiento` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1,
  `anulado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atletas`
--

DROP TABLE IF EXISTS `atletas`;
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
(13, 'Jose Jose', 'Perez Perez', '32323232', NULL, NULL, 'H', '2008-05-18', 'atleta_2012-05-18_1779417290.png', 5, 8, 2, 1),
(14, 'Mario Mario', 'Bros Bros', '34324324', NULL, NULL, 'H', '2009-05-19', 'atleta_2009-05-19_1779417273.png', 5, 8, 2, 1),
(15, 'Moises Jesus', 'Torrellas Colmenarez', '29506932', '0412-0565231', 'El Tocuyo', 'H', '2002-07-25', 'atleta_2002-07-25_1779417262.png', 6, 4, NULL, 2),
(16, 'Maria Jose', 'Perez Yepez', NULL, NULL, NULL, 'M', '2021-07-08', 'atleta_2021-07-08_1779417253.png', 5, 3, 6, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogos`
--

DROP TABLE IF EXISTS `catalogos`;
CREATE TABLE `catalogos` (
  `id_catalogo` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `stock_minimo` varchar(10) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `talla` varchar(10) DEFAULT NULL,
  `id_posicion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `catalogos`
--

INSERT INTO `catalogos` (`id_catalogo`, `nombre`, `stock_minimo`, `id_categoria`, `talla`, `id_posicion`) VALUES
(3, 'Stick', '14', 4, '', NULL),
(4, 'Stick De Carbono', '10', 4, '6', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
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
(3, 'U-6', 5, 6),
(4, 'SENIOR', 18, 60),
(5, 'U-8', 7, 8),
(6, 'U-10', 9, 10),
(7, 'U-14', 13, 14),
(8, 'U-17', 15, 17);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_catalogo`
--

DROP TABLE IF EXISTS `categoria_catalogo`;
CREATE TABLE `categoria_catalogo` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `descripcion` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

--
-- Volcado de datos para la tabla `categoria_catalogo`
--

INSERT INTO `categoria_catalogo` (`id_categoria`, `nombre`, `descripcion`) VALUES
(3, 'POW', 'moises'),
(4, 'STICK', 'Palo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos`
--

DROP TABLE IF EXISTS `conceptos`;
CREATE TABLE `conceptos` (
  `id_conceptos` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1,
  `regla` enum('A','M','L','U') NOT NULL DEFAULT 'L'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `conceptos`
--

INSERT INTO `conceptos` (`id_conceptos`, `nombre`, `monto`, `estatus`, `regla`) VALUES
(2, 'Mensualidad', 30.00, 1, 'M'),
(3, 'Viaticos', 0.00, 1, 'L'),
(4, 'Inscripcion', 25.00, 1, 'A'),
(7, 'Equipamineto', 30.00, 1, 'A'),
(8, 'Equipamiento Especial', 50.00, 1, 'U');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cuentas_cobrar`
--

DROP TABLE IF EXISTS `cuentas_cobrar`;
CREATE TABLE `cuentas_cobrar` (
  `id_cobrar` int(11) NOT NULL,
  `id_concepto` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL,
  `id_moneda` int(100) NOT NULL,
  `monto_personalizado` decimal(10,2) DEFAULT NULL,
  `monto_pendiente` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estatus` tinyint(4) NOT NULL,
  `anulado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `cuentas_cobrar`
--

INSERT INTO `cuentas_cobrar` (`id_cobrar`, `id_concepto`, `id_atleta`, `id_moneda`, `monto_personalizado`, `monto_pendiente`, `fecha_emision`, `fecha_vencimiento`, `estatus`, `anulado`) VALUES
(12, 2, 14, 4, 30.00, 30.00, '2026-06-03', '2026-07-10', 0, 0),
(13, 3, 14, 5, 10000.00, 10000.00, '2026-06-03', '2026-07-17', 0, 0),
(14, 2, 14, 4, 30.00, 30.00, '2026-06-03', '2026-07-03', 0, 1),
(15, 2, 14, 4, 30.00, 30.00, '2026-06-03', '2026-07-03', 0, 1),
(16, 2, 16, 4, 30.00, 0.00, '2026-06-03', '2026-07-03', 1, 0),
(17, 2, 15, 5, 30.00, 30.00, '2026-06-03', '2026-07-03', 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_equipos`
--

DROP TABLE IF EXISTS `detalles_equipos`;
CREATE TABLE `detalles_equipos` (
  `id_detalle` int(11) NOT NULL,
  `id_equipo` int(11) NOT NULL,
  `id_atleta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pagos`
--

DROP TABLE IF EXISTS `detalles_pagos`;
CREATE TABLE `detalles_pagos` (
  `id_detalle_pago` int(255) NOT NULL,
  `id_pago` int(100) NOT NULL,
  `id_cobrar` int(100) NOT NULL,
  `monto_abonado` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `detalles_pagos`
--

INSERT INTO `detalles_pagos` (`id_detalle_pago`, `id_pago`, `id_cobrar`, `monto_abonado`) VALUES
(12, 16, 13, 10000.00),
(13, 16, 12, 30.00),
(14, 17, 16, 0.05),
(15, 18, 16, 30.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_palmares`
--

DROP TABLE IF EXISTS `detalles_palmares`;
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

DROP TABLE IF EXISTS `devoluciones`;
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

DROP TABLE IF EXISTS `equipamientos`;
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

DROP TABLE IF EXISTS `equipos`;
CREATE TABLE `equipos` (
  `id_equipos` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estadisticas`
--

DROP TABLE IF EXISTS `estadisticas`;
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

DROP TABLE IF EXISTS `estado_equipamiento`;
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

DROP TABLE IF EXISTS `historial`;
CREATE TABLE `historial` (
  `id_historial` int(100) NOT NULL,
  `id_atleta` int(100) NOT NULL,
  `fecha_emision` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

DROP TABLE IF EXISTS `metodos_pago`;
CREATE TABLE `metodos_pago` (
  `id_metodos` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `nec_referencia` tinyint(4) NOT NULL DEFAULT 0,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`id_metodos`, `nombre`, `nec_referencia`, `estatus`) VALUES
(1, 'Efectivo', 2, 1),
(2, 'Transferencia', 1, 1),
(3, 'Zelle', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monedas`
--

DROP TABLE IF EXISTS `monedas`;
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
(5, 'Bolivares', 'VES', 'Bs', 1),
(6, 'Euro', 'EUR', '€', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

DROP TABLE IF EXISTS `pagos`;
CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_metodo` int(11) NOT NULL,
  `id_moneda` int(11) NOT NULL,
  `monto_pago` decimal(10,2) NOT NULL,
  `tasa_cambio` decimal(10,4) NOT NULL,
  `monto_vuelto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `referencia` varchar(40) NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_metodo`, `id_moneda`, `monto_pago`, `tasa_cambio`, `monto_vuelto`, `fecha`, `referencia`, `estatus`) VALUES
(16, 1, 4, 50.00, 0.0018, 2.15, '2026-06-03', '', 2),
(17, 1, 5, 30.00, 560.3753, 0.00, '2026-06-03', '', 2),
(18, 1, 4, 30.00, 1.0000, 0.00, '2026-06-03', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `palmares`
--

DROP TABLE IF EXISTS `palmares`;
CREATE TABLE `palmares` (
  `id_palmares` int(100) NOT NULL,
  `id_torneo` int(100) NOT NULL,
  `fecha_registro` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participaciones`
--

DROP TABLE IF EXISTS `participaciones`;
CREATE TABLE `participaciones` (
  `id_participacion` int(11) NOT NULL,
  `id_torneo` int(11) NOT NULL,
  `id_equipo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posiciones`
--

DROP TABLE IF EXISTS `posiciones`;
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

DROP TABLE IF EXISTS `premios`;
CREATE TABLE `premios` (
  `id_premio` int(11) NOT NULL,
  `tipo` enum('I','G') NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `premios`
--

INSERT INTO `premios` (`id_premio`, `tipo`, `nombre`) VALUES
(1, 'I', 'Maximo Goleador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `representantes`
--

DROP TABLE IF EXISTS `representantes`;
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
(2, 'Moises', 'Martinez', '12345678', 'V', 'Barquisimeto', '3333-3333333'),
(6, 'Maria', 'Martinez', '25065254', 'V', 'Barquisimeto', '2342-3423423');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

DROP TABLE IF EXISTS `torneos`;
CREATE TABLE `torneos` (
  `id_torneo` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `estatus` tinyint(3) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `torneos`
--

INSERT INTO `torneos` (`id_torneo`, `nombre`, `fecha_inicio`, `fecha_fin`, `ubicacion`, `estatus`) VALUES
(2, 'MUNDIAL BARQUISIMETO 2026', '2026-07-16', '2026-09-24', 'Estado Lara', 1);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_atletas`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_atletas`;
CREATE TABLE `vista_atletas` (
`id_atleta` int(11)
,`nombres` varchar(60)
,`apellidos` varchar(60)
,`estatus` tinyint(1)
,`doc_identidad` varchar(15)
,`id_representante` int(11)
,`id_posicion` int(11)
,`id_categoria` int(11)
,`genero` enum('H','M')
,`fecha_nac` date
,`foto` varchar(100)
,`telefono` varchar(15)
,`direccion` varchar(150)
,`nombre_rep` varchar(30)
,`apellido_rep` varchar(30)
,`cedula_rep` varchar(13)
,`nombre_posicion` varchar(30)
,`abrev_posicion` varchar(4)
,`nombre_categoria` varchar(50)
,`edad_min` int(2)
,`edad_max` int(2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_cuentas_cobrar`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_cuentas_cobrar`;
CREATE TABLE `vista_cuentas_cobrar` (
`id_cobrar` int(11)
,`id_atleta` int(11)
,`id_concepto` int(11)
,`id_moneda` int(100)
,`fecha_emision` date
,`fecha_vencimiento` date
,`monto_total` decimal(10,2)
,`monto_pendiente` decimal(10,2)
,`anulado` tinyint(1)
,`estatus` tinyint(4)
,`estatus_texto` varchar(11)
,`atleta_nombre` varchar(60)
,`atleta_apellido` varchar(60)
,`concepto_nombre` varchar(50)
,`moneda_nombre` varchar(40)
,`moneda_simbolo` varchar(3)
,`moneda_abreviatura` varchar(4)
,`deuda_moneda_atleta` decimal(32,2)
,`total_facturas_atleta` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_atletas`
--
DROP TABLE IF EXISTS `vista_atletas`;

DROP VIEW IF EXISTS `vista_atletas`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_atletas`  AS SELECT `a`.`id_atleta` AS `id_atleta`, `a`.`nombres` AS `nombres`, `a`.`apellidos` AS `apellidos`, `a`.`estatus` AS `estatus`, CASE WHEN `a`.`doc_identidad` is not null AND `a`.`doc_identidad` <> '' THEN concat('A-',`a`.`doc_identidad`) WHEN `r`.`cedula` is not null AND `r`.`cedula` <> '' THEN concat('R-',`r`.`cedula`) ELSE '' END AS `doc_identidad`, `a`.`id_representante` AS `id_representante`, `a`.`id_posicion` AS `id_posicion`, `a`.`id_categoria` AS `id_categoria`, `a`.`genero` AS `genero`, `a`.`fecha_nac` AS `fecha_nac`, `a`.`foto` AS `foto`, coalesce(`a`.`telefono`,`r`.`telefono`) AS `telefono`, coalesce(`a`.`direccion`,`r`.`direccion`) AS `direccion`, `r`.`nombre` AS `nombre_rep`, `r`.`apellido` AS `apellido_rep`, `r`.`cedula` AS `cedula_rep`, `p`.`nombre` AS `nombre_posicion`, `p`.`abreviatura` AS `abrev_posicion`, `c`.`nombre` AS `nombre_categoria`, `c`.`edad_min` AS `edad_min`, `c`.`edad_max` AS `edad_max` FROM (((`atletas` `a` left join `representantes` `r` on(`a`.`id_representante` = `r`.`id_representante`)) left join `posiciones` `p` on(`a`.`id_posicion` = `p`.`id_posicion`)) left join `categorias` `c` on(`a`.`id_categoria` = `c`.`id_categorias`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_cuentas_cobrar`
--
DROP TABLE IF EXISTS `vista_cuentas_cobrar`;

DROP VIEW IF EXISTS `vista_cuentas_cobrar`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_cuentas_cobrar`  AS SELECT `c`.`id_cobrar` AS `id_cobrar`, `c`.`id_atleta` AS `id_atleta`, `c`.`id_concepto` AS `id_concepto`, `c`.`id_moneda` AS `id_moneda`, `c`.`fecha_emision` AS `fecha_emision`, `c`.`fecha_vencimiento` AS `fecha_vencimiento`, `c`.`monto_personalizado` AS `monto_total`, `c`.`monto_pendiente` AS `monto_pendiente`, `c`.`anulado` AS `anulado`, `c`.`estatus` AS `estatus`, CASE WHEN `c`.`anulado` = 1 THEN 'Anulado' WHEN `c`.`estatus` = 0 THEN 'Pendiente' WHEN `c`.`estatus` = 1 THEN 'Pagado' ELSE 'Desconocido' END AS `estatus_texto`, `a`.`nombres` AS `atleta_nombre`, `a`.`apellidos` AS `atleta_apellido`, `co`.`nombre` AS `concepto_nombre`, `m`.`nombre` AS `moneda_nombre`, `m`.`simbolo` AS `moneda_simbolo`, `m`.`abreviatura` AS `moneda_abreviatura`, sum(case when `c`.`estatus` = 0 and `c`.`anulado` = 0 then `c`.`monto_pendiente` else 0 end) over ( partition by `c`.`id_atleta`,`m`.`id_moneda`) AS `deuda_moneda_atleta`, count(`c`.`id_cobrar`) over ( partition by `c`.`id_atleta`) AS `total_facturas_atleta` FROM (((`cuentas_cobrar` `c` join `atletas` `a` on(`c`.`id_atleta` = `a`.`id_atleta`)) join `conceptos` `co` on(`c`.`id_concepto` = `co`.`id_conceptos`)) join `monedas` `m` on(`c`.`id_moneda` = `m`.`id_moneda`)) ;

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
  ADD UNIQUE KEY `doc_identidad_2` (`doc_identidad`),
  ADD UNIQUE KEY `telefono` (`telefono`),
  ADD KEY `id_representante` (`id_representante`),
  ADD KEY `id_posicion` (`id_posicion`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `doc_identidad` (`doc_identidad`),
  ADD KEY `telefono_2` (`telefono`);

--
-- Indices de la tabla `catalogos`
--
ALTER TABLE `catalogos`
  ADD PRIMARY KEY (`id_catalogo`),
  ADD KEY `id_posicion` (`id_posicion`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `nombre` (`nombre`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categorias`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `id_categorias` (`id_categorias`);

--
-- Indices de la tabla `categoria_catalogo`
--
ALTER TABLE `categoria_catalogo`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  ADD PRIMARY KEY (`id_conceptos`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `id_conceptos` (`id_conceptos`);

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
-- Indices de la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  ADD PRIMARY KEY (`id_detalle_pago`),
  ADD KEY `id_pago` (`id_pago`,`id_cobrar`),
  ADD KEY `id_cobrar` (`id_cobrar`);

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
  ADD UNIQUE KEY `nombre` (`nombre`),
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
  ADD PRIMARY KEY (`id_estado`),
  ADD UNIQUE KEY `nombre` (`nombre`);

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
  ADD PRIMARY KEY (`id_metodos`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `monedas`
--
ALTER TABLE `monedas`
  ADD PRIMARY KEY (`id_moneda`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `abreviatura` (`abreviatura`),
  ADD UNIQUE KEY `simbolo` (`simbolo`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
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
  ADD PRIMARY KEY (`id_posicion`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `abreviatura` (`abreviatura`);

--
-- Indices de la tabla `premios`
--
ALTER TABLE `premios`
  ADD PRIMARY KEY (`id_premio`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `representantes`
--
ALTER TABLE `representantes`
  ADD PRIMARY KEY (`id_representante`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `telefono` (`telefono`);

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
  MODIFY `id_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `catalogos`
--
ALTER TABLE `catalogos`
  MODIFY `id_catalogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categorias` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `categoria_catalogo`
--
ALTER TABLE `categoria_catalogo`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  MODIFY `id_conceptos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `cuentas_cobrar`
--
ALTER TABLE `cuentas_cobrar`
  MODIFY `id_cobrar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  MODIFY `id_detalle_pago` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `id_equipos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id_metodos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `monedas`
--
ALTER TABLE `monedas`
  MODIFY `id_moneda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `palmares`
--
ALTER TABLE `palmares`
  MODIFY `id_palmares` int(100) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id_premio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `representantes`
--
ALTER TABLE `representantes`
  MODIFY `id_representante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id_torneo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Filtros para la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  ADD CONSTRAINT `detalles_pagos_ibfk_1` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id_pago`),
  ADD CONSTRAINT `detalles_pagos_ibfk_2` FOREIGN KEY (`id_cobrar`) REFERENCES `cuentas_cobrar` (`id_cobrar`);

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
