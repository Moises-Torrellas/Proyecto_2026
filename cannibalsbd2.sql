-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-06-2026 a las 19:43:01
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
-- Base de datos: `cannibalsbd2`
--
CREATE DATABASE IF NOT EXISTS `cannibalsbd2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE `cannibalsbd2`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos_inventario`
--

DROP TABLE IF EXISTS `articulos_inventario`;
CREATE TABLE `articulos_inventario` (
  `codigo_articulo` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_catalogo` int(11) NOT NULL,
  `codigo_club` int(11) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

DROP TABLE IF EXISTS `asignaciones`;
CREATE TABLE `asignaciones` (
  `id_asignacion` int(11) NOT NULL,
  `codigo_atleta` int(11) NOT NULL,
  `codigo_articulo` int(11) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atletas`
--

DROP TABLE IF EXISTS `atletas`;
CREATE TABLE `atletas` (
  `codigo_atleta` int(11) NOT NULL,
  `p_nombre` varchar(50) NOT NULL,
  `s_nombre` varchar(50) DEFAULT NULL,
  `p_apellidos` varchar(50) NOT NULL,
  `s_apellidos` varchar(50) DEFAULT NULL,
  `genero` enum('H','M') NOT NULL,
  `fecha_nac` date NOT NULL,
  `foto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `atletas`
--

INSERT INTO `atletas` (`codigo_atleta`, `p_nombre`, `s_nombre`, `p_apellidos`, `s_apellidos`, `genero`, `fecha_nac`, `foto`) VALUES
(2, 'Moises', 'Jesus', 'Torrellas', '', 'H', '2002-07-25', 'atleta_2002-07-25_1782057957.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atleta_representante`
--

DROP TABLE IF EXISTS `atleta_representante`;
CREATE TABLE `atleta_representante` (
  `codigo_at_re` int(11) NOT NULL,
  `codigo_atleta` int(11) NOT NULL,
  `codigo_representante` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargos`
--

DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `codigo_cargo` int(11) NOT NULL,
  `codigo_concepto` int(11) NOT NULL,
  `codigo_atleta` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cargos`
--

INSERT INTO `cargos` (`codigo_cargo`, `codigo_concepto`, `codigo_atleta`, `monto_total`, `fecha_emision`, `estatus`) VALUES
(1, 1, 2, 30.00, '2026-06-22', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo`
--

DROP TABLE IF EXISTS `catalogo`;
CREATE TABLE `catalogo` (
  `id_catalogo` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `stock_minimo` int(11) NOT NULL,
  `Id_categoria` int(11) NOT NULL,
  `talla` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `codigo_categoria` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `edad_min` int(11) NOT NULL,
  `edad_max` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`codigo_categoria`, `nombre`, `edad_min`, `edad_max`) VALUES
(1, 'U-6', 5, 6),
(2, 'U-8', 7, 8),
(3, 'U-10', 9, 10),
(4, 'U-12', 11, 12),
(5, 'U-14', 13, 14),
(6, 'U-17', 15, 17),
(7, 'SENIOR', 18, 50);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_catalogo`
--

DROP TABLE IF EXISTS `categoria_catalogo`;
CREATE TABLE `categoria_catalogo` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos`
--

DROP TABLE IF EXISTS `conceptos`;
CREATE TABLE `conceptos` (
  `codigo_concepto` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `monto` decimal(10,0) NOT NULL,
  `frecuencia` enum('A','M','L','U') NOT NULL,
  `dias_gracia` int(11) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `conceptos`
--

INSERT INTO `conceptos` (`codigo_concepto`, `nombre`, `monto`, `frecuencia`, `dias_gracia`, `estatus`) VALUES
(1, 'Mensualidad', 30, 'M', 5, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contacto_atleta`
--

DROP TABLE IF EXISTS `contacto_atleta`;
CREATE TABLE `contacto_atleta` (
  `codigo_atleta` int(11) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `contacto_atleta`
--

INSERT INTO `contacto_atleta` (`codigo_atleta`, `direccion`, `telefono`) VALUES
(2, 'El Tocuyo', '0412-0565231');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_equipos`
--

DROP TABLE IF EXISTS `detalles_equipos`;
CREATE TABLE `detalles_equipos` (
  `codigo_detalle` int(11) NOT NULL,
  `codigo_equipo` int(11) NOT NULL,
  `codigo_atleta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `detalles_equipos`
--

INSERT INTO `detalles_equipos` (`codigo_detalle`, `codigo_equipo`, `codigo_atleta`) VALUES
(2, 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pagos`
--

DROP TABLE IF EXISTS `detalles_pagos`;
CREATE TABLE `detalles_pagos` (
  `codigo_detalles_pagos` int(11) NOT NULL,
  `codigo_pago` int(11) NOT NULL,
  `codigo_cargo` int(11) NOT NULL,
  `monto_abonado` decimal(10,0) NOT NULL,
  `tasa_cambio` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_participacion`
--

DROP TABLE IF EXISTS `detalles_participacion`;
CREATE TABLE `detalles_participacion` (
  `codigo_dtll_prtc` int(11) NOT NULL,
  `codigo_participacion` int(11) NOT NULL,
  `codigo_atleta` int(11) NOT NULL,
  `goles` int(11) NOT NULL DEFAULT 0,
  `asistencias` int(11) NOT NULL,
  `penalizaciones` int(11) NOT NULL,
  `goles_contra` int(11) NOT NULL,
  `partidos_jugados` int(11) NOT NULL,
  `average` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `detalles_participacion`
--

INSERT INTO `detalles_participacion` (`codigo_dtll_prtc`, `codigo_participacion`, `codigo_atleta`, `goles`, `asistencias`, `penalizaciones`, `goles_contra`, `partidos_jugados`, `average`) VALUES
(2, 1, 2, 10, 5, 1, 1, 12, 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

DROP TABLE IF EXISTS `devoluciones`;
CREATE TABLE `devoluciones` (
  `id_devolucion` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `fecha_devolucion` date NOT NULL,
  `observación` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

DROP TABLE IF EXISTS `equipos`;
CREATE TABLE `equipos` (
  `codigo_equipo` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`codigo_equipo`, `nombre`) VALUES
(1, 'Senior');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_fisico`
--

DROP TABLE IF EXISTS `estado_fisico`;
CREATE TABLE `estado_fisico` (
  `id_estado` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `nivel_estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `identidad_atleta`
--

DROP TABLE IF EXISTS `identidad_atleta`;
CREATE TABLE `identidad_atleta` (
  `codigo_atleta` int(11) NOT NULL,
  `tipo_doc` enum('V','E','P') NOT NULL,
  `numero_doc` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `identidad_atleta`
--

INSERT INTO `identidad_atleta` (`codigo_atleta`, `tipo_doc`, `numero_doc`) VALUES
(2, 'V', '29506932');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

DROP TABLE IF EXISTS `inscripciones`;
CREATE TABLE `inscripciones` (
  `codigo_inscripcion` int(11) NOT NULL,
  `codigo_atleta` int(11) NOT NULL,
  `codigo_categoria` int(11) NOT NULL,
  `codigo_posicion` int(11) NOT NULL,
  `dorsal` int(11) NOT NULL,
  `peso_kg` decimal(10,0) NOT NULL,
  `estatura_cm` int(11) NOT NULL,
  `fecha_inscripcion` date NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`codigo_inscripcion`, `codigo_atleta`, `codigo_categoria`, `codigo_posicion`, `dorsal`, `peso_kg`, `estatura_cm`, `fecha_inscripcion`, `estatus`) VALUES
(2, 2, 7, 1, 12, 90, 185, '2026-06-21', 2),
(3, 2, 7, 1, 12, 90, 185, '2026-06-21', 2),
(4, 2, 7, 1, 12, 90, 185, '2026-06-21', 2),
(5, 2, 7, 1, 12, 90, 185, '2026-06-21', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

DROP TABLE IF EXISTS `metodos_pago`;
CREATE TABLE `metodos_pago` (
  `codigo_metodo` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `nec_referencia` tinyint(4) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`codigo_metodo`, `nombre`, `nec_referencia`, `estatus`) VALUES
(2, 'Transferencia', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `monedas`
--

DROP TABLE IF EXISTS `monedas`;
CREATE TABLE `monedas` (
  `codigo_moneda` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `abreviatura` varchar(255) NOT NULL,
  `simbolo` varchar(255) NOT NULL,
  `base` tinyint(4) NOT NULL DEFAULT 2,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `monedas`
--

INSERT INTO `monedas` (`codigo_moneda`, `nombre`, `abreviatura`, `simbolo`, `base`, `estatus`) VALUES
(1, 'Bolivar', 'VES', 'Bs', 2, 1),
(2, 'Dolar', 'USD', '$', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

DROP TABLE IF EXISTS `pagos`;
CREATE TABLE `pagos` (
  `codigo_pago` int(11) NOT NULL,
  `codigo_metodo` int(11) NOT NULL,
  `codigo_moneda` int(11) NOT NULL,
  `monto_pago` decimal(10,0) NOT NULL,
  `fecha` date NOT NULL,
  `referencia` varchar(255) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `palmares_grupal`
--

DROP TABLE IF EXISTS `palmares_grupal`;
CREATE TABLE `palmares_grupal` (
  `codigo_grupal` int(11) NOT NULL,
  `codigo_participacion` int(11) NOT NULL,
  `codigo_premio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `palmares_grupal`
--

INSERT INTO `palmares_grupal` (`codigo_grupal`, `codigo_participacion`, `codigo_premio`) VALUES
(2, 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `palmares_individual`
--

DROP TABLE IF EXISTS `palmares_individual`;
CREATE TABLE `palmares_individual` (
  `codigo_individual` int(11) NOT NULL,
  `codigo_premio` int(11) NOT NULL,
  `codigo_dtll_prtc` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `palmares_individual`
--

INSERT INTO `palmares_individual` (`codigo_individual`, `codigo_premio`, `codigo_dtll_prtc`) VALUES
(2, 3, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participaciones`
--

DROP TABLE IF EXISTS `participaciones`;
CREATE TABLE `participaciones` (
  `codigo_participacion` int(11) NOT NULL,
  `codigo_torneo` int(11) NOT NULL,
  `codigo_equipo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `participaciones`
--

INSERT INTO `participaciones` (`codigo_participacion`, `codigo_torneo`, `codigo_equipo`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posiciones`
--

DROP TABLE IF EXISTS `posiciones`;
CREATE TABLE `posiciones` (
  `codigo_posicion` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `abreviatura` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `posiciones`
--

INSERT INTO `posiciones` (`codigo_posicion`, `nombre`, `abreviatura`, `descripcion`) VALUES
(1, 'Delantero', 'DC', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premios`
--

DROP TABLE IF EXISTS `premios`;
CREATE TABLE `premios` (
  `codigo_premio` int(11) NOT NULL,
  `tipo` enum('I','G') NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `premios`
--

INSERT INTO `premios` (`codigo_premio`, `tipo`, `nombre`) VALUES
(1, 'I', 'Mvp'),
(2, 'G', 'Primer Lugar'),
(3, 'I', 'Maximo Goleador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `representantes`
--

DROP TABLE IF EXISTS `representantes`;
CREATE TABLE `representantes` (
  `codigo_representante` int(11) NOT NULL,
  `cedula` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `tipo_doc` enum('V','E','P') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `representantes`
--

INSERT INTO `representantes` (`codigo_representante`, `cedula`, `telefono`, `direccion`, `nombre`, `apellido`, `tipo_doc`) VALUES
(2, '13197214', '0232-1334423', 'El Tocuyo', 'Jessica', 'Colmenarez', 'V');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `retiros`
--

DROP TABLE IF EXISTS `retiros`;
CREATE TABLE `retiros` (
  `codigo_retiro` int(11) NOT NULL,
  `codigo_inscripcion` int(11) NOT NULL,
  `fecha_retiro` date NOT NULL,
  `motivo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `retiros`
--

INSERT INTO `retiros` (`codigo_retiro`, `codigo_inscripcion`, `fecha_retiro`, `motivo`) VALUES
(2, 2, '2026-06-21', 'viaje largo'),
(3, 3, '2026-06-21', 'no le gusto el hockey'),
(4, 4, '2026-06-21', 'falta de pago');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasa_cambios`
--

DROP TABLE IF EXISTS `tasa_cambios`;
CREATE TABLE `tasa_cambios` (
  `codigo_tasa` int(11) NOT NULL,
  `codigo_moneda` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `valor_tasa` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `tasa_cambios`
--

INSERT INTO `tasa_cambios` (`codigo_tasa`, `codigo_moneda`, `fecha`, `valor_tasa`) VALUES
(1, 1, '2026-06-22', '612.4332'),
(2, 2, '2026-06-22', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

DROP TABLE IF EXISTS `torneos`;
CREATE TABLE `torneos` (
  `codigo_torneo` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `torneos`
--

INSERT INTO `torneos` (`codigo_torneo`, `nombre`, `fecha_inicio`, `fecha_fin`, `ubicacion`, `estatus`) VALUES
(1, 'BARQUISIMETO 2026', '2026-06-22', '2026-06-26', 'Barquisimeto', 1);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_atletas`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_atletas`;
CREATE TABLE `vista_atletas` (
`id_atleta` int(11)
,`nombres` varchar(101)
,`apellidos` varchar(101)
,`p_nombre` varchar(50)
,`s_nombre` varchar(50)
,`p_apellidos` varchar(50)
,`s_apellidos` varchar(50)
,`genero` enum('H','M')
,`fecha_nac` date
,`foto` varchar(255)
,`doc_identidad` varchar(255)
,`telefono` varchar(255)
,`direccion` varchar(255)
,`id_representante` int(11)
,`nombre_rep` varchar(255)
,`apellido_rep` varchar(255)
,`cedula_rep` varchar(255)
,`telefono_rep` varchar(255)
,`direccion_rep` varchar(255)
,`id_categoria` int(11)
,`nombre_categoria` varchar(255)
,`edad_min` int(11)
,`edad_max` int(11)
,`id_posicion` int(11)
,`nombre_posicion` varchar(255)
,`dorsal` int(11)
,`peso_kg` decimal(10,0)
,`estatura_cm` int(11)
,`estatus` int(4)
,`fecha_ingreso` date
,`fecha_reingreso` date
,`fecha_retiro` date
,`motivo_retiro` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_cargos`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_cargos`;
CREATE TABLE `vista_cargos` (
`id_cobrar` int(11)
,`id_atleta` int(11)
,`id_concepto` int(11)
,`fecha_emision` date
,`fecha_vencimiento` date
,`monto_total` decimal(10,2)
,`monto_personalizado` decimal(10,2)
,`monto_pendiente` decimal(35,2)
,`estatus` tinyint(4)
,`estatus_texto` varchar(11)
,`atleta_nombre` varchar(50)
,`atleta_apellido` varchar(50)
,`concepto_nombre` varchar(255)
,`moneda_nombre` varchar(255)
,`moneda_simbolo` varchar(255)
,`moneda_abreviatura` varchar(255)
,`deuda_moneda_atleta` decimal(57,2)
,`total_facturas_atleta` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vueltos`
--

DROP TABLE IF EXISTS `vueltos`;
CREATE TABLE `vueltos` (
  `codigo_vuelto` int(11) NOT NULL,
  `codigo_metodo` int(11) NOT NULL,
  `codigo_pago` int(11) NOT NULL,
  `codigo_moneda` int(11) NOT NULL,
  `monto_vuelto` decimal(10,0) NOT NULL,
  `fecha_vuelto` date NOT NULL,
  `referencia` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_atletas`
--
DROP TABLE IF EXISTS `vista_atletas`;

DROP VIEW IF EXISTS `vista_atletas`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_atletas`  AS SELECT `a`.`codigo_atleta` AS `id_atleta`, concat(`a`.`p_nombre`,if(`a`.`s_nombre` is not null and `a`.`s_nombre` <> '',concat(' ',`a`.`s_nombre`),'')) AS `nombres`, concat(`a`.`p_apellidos`,if(`a`.`s_apellidos` is not null and `a`.`s_apellidos` <> '',concat(' ',`a`.`s_apellidos`),'')) AS `apellidos`, `a`.`p_nombre` AS `p_nombre`, `a`.`s_nombre` AS `s_nombre`, `a`.`p_apellidos` AS `p_apellidos`, `a`.`s_apellidos` AS `s_apellidos`, `a`.`genero` AS `genero`, `a`.`fecha_nac` AS `fecha_nac`, `a`.`foto` AS `foto`, `ia`.`numero_doc` AS `doc_identidad`, `ca`.`telefono` AS `telefono`, `ca`.`direccion` AS `direccion`, `r`.`codigo_representante` AS `id_representante`, `r`.`nombre` AS `nombre_rep`, `r`.`apellido` AS `apellido_rep`, `r`.`cedula` AS `cedula_rep`, `r`.`telefono` AS `telefono_rep`, `r`.`direccion` AS `direccion_rep`, `i`.`codigo_categoria` AS `id_categoria`, `c`.`nombre` AS `nombre_categoria`, `c`.`edad_min` AS `edad_min`, `c`.`edad_max` AS `edad_max`, `i`.`codigo_posicion` AS `id_posicion`, `p`.`nombre` AS `nombre_posicion`, `i`.`dorsal` AS `dorsal`, `i`.`peso_kg` AS `peso_kg`, `i`.`estatura_cm` AS `estatura_cm`, ifnull(`i`.`estatus`,1) AS `estatus`, `primer_ingreso`.`fecha_inscripcion` AS `fecha_ingreso`, CASE WHEN `primer_ingreso`.`codigo_inscripcion` <> `i`.`codigo_inscripcion` THEN `i`.`fecha_inscripcion` ELSE NULL END AS `fecha_reingreso`, `ret`.`fecha_retiro` AS `fecha_retiro`, `ret`.`motivo` AS `motivo_retiro` FROM ((((((((((((`atletas` `a` left join `identidad_atleta` `ia` on(`a`.`codigo_atleta` = `ia`.`codigo_atleta`)) left join `contacto_atleta` `ca` on(`a`.`codigo_atleta` = `ca`.`codigo_atleta`)) left join `atleta_representante` `ar` on(`a`.`codigo_atleta` = `ar`.`codigo_atleta`)) left join `representantes` `r` on(`ar`.`codigo_representante` = `r`.`codigo_representante`)) left join (select `inscripciones`.`codigo_atleta` AS `codigo_atleta`,max(`inscripciones`.`codigo_inscripcion`) AS `max_id` from `inscripciones` group by `inscripciones`.`codigo_atleta`) `max_i` on(`a`.`codigo_atleta` = `max_i`.`codigo_atleta`)) left join `inscripciones` `i` on(`max_i`.`max_id` = `i`.`codigo_inscripcion`)) left join `categorias` `c` on(`i`.`codigo_categoria` = `c`.`codigo_categoria`)) left join `posiciones` `p` on(`i`.`codigo_posicion` = `p`.`codigo_posicion`)) left join (select `inscripciones`.`codigo_atleta` AS `codigo_atleta`,min(`inscripciones`.`codigo_inscripcion`) AS `min_id` from `inscripciones` group by `inscripciones`.`codigo_atleta`) `min_i` on(`a`.`codigo_atleta` = `min_i`.`codigo_atleta`)) left join `inscripciones` `primer_ingreso` on(`min_i`.`min_id` = `primer_ingreso`.`codigo_inscripcion`)) left join (select `ins`.`codigo_atleta` AS `codigo_atleta`,max(`r`.`codigo_inscripcion`) AS `ultima_inscripcion_retirada` from (`retiros` `r` join `inscripciones` `ins` on(`r`.`codigo_inscripcion` = `ins`.`codigo_inscripcion`)) group by `ins`.`codigo_atleta`) `ultimo_retiro_id` on(`a`.`codigo_atleta` = `ultimo_retiro_id`.`codigo_atleta`)) left join `retiros` `ret` on(`ultimo_retiro_id`.`ultima_inscripcion_retirada` = `ret`.`codigo_inscripcion`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_cargos`
--
DROP TABLE IF EXISTS `vista_cargos`;

DROP VIEW IF EXISTS `vista_cargos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_cargos`  AS SELECT `c`.`codigo_cargo` AS `id_cobrar`, `c`.`codigo_atleta` AS `id_atleta`, `c`.`codigo_concepto` AS `id_concepto`, `c`.`fecha_emision` AS `fecha_emision`, `c`.`fecha_emision`+ interval `co`.`dias_gracia` day AS `fecha_vencimiento`, `c`.`monto_total` AS `monto_total`, `c`.`monto_total` AS `monto_personalizado`, greatest(`c`.`monto_total` - coalesce(`abonos`.`total_abonado`,0),0) AS `monto_pendiente`, `c`.`estatus` AS `estatus`, CASE WHEN `c`.`estatus` = 3 THEN 'Anulado' WHEN `c`.`estatus` = 2 THEN 'Pagado' WHEN `c`.`estatus` = 1 THEN 'Pendiente' ELSE 'Desconocido' END AS `estatus_texto`, `a`.`p_nombre` AS `atleta_nombre`, `a`.`p_apellidos` AS `atleta_apellido`, `co`.`nombre` AS `concepto_nombre`, `m`.`nombre` AS `moneda_nombre`, `m`.`simbolo` AS `moneda_simbolo`, `m`.`abreviatura` AS `moneda_abreviatura`, sum(case when `c`.`estatus` = 1 then greatest(`c`.`monto_total` - coalesce(`abonos`.`total_abonado`,0),0) else 0 end) over ( partition by `c`.`codigo_atleta`,`m`.`codigo_moneda`) AS `deuda_moneda_atleta`, count(`c`.`codigo_cargo`) over ( partition by `c`.`codigo_atleta`) AS `total_facturas_atleta` FROM ((((`cargos` `c` join `atletas` `a` on(`c`.`codigo_atleta` = `a`.`codigo_atleta`)) join `conceptos` `co` on(`c`.`codigo_concepto` = `co`.`codigo_concepto`)) join `monedas` `m` on(`m`.`base` = 1)) left join (select `dp`.`codigo_cargo` AS `codigo_cargo`,sum(`dp`.`monto_abonado`) AS `total_abonado` from (`detalles_pagos` `dp` join `pagos` `p` on(`dp`.`codigo_pago` = `p`.`codigo_pago` and `p`.`estatus` = 1)) group by `dp`.`codigo_cargo`) `abonos` on(`abonos`.`codigo_cargo` = `c`.`codigo_cargo`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulos_inventario`
--
ALTER TABLE `articulos_inventario`
  ADD PRIMARY KEY (`codigo_articulo`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `id_catalogo` (`id_catalogo`);

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `codigo_atleta` (`codigo_atleta`),
  ADD KEY `codigo_articulo` (`codigo_articulo`);

--
-- Indices de la tabla `atletas`
--
ALTER TABLE `atletas`
  ADD PRIMARY KEY (`codigo_atleta`),
  ADD UNIQUE KEY `genero` (`genero`);

--
-- Indices de la tabla `atleta_representante`
--
ALTER TABLE `atleta_representante`
  ADD PRIMARY KEY (`codigo_at_re`),
  ADD KEY `codigo_atleta` (`codigo_atleta`),
  ADD KEY `codigo_representante` (`codigo_representante`);

--
-- Indices de la tabla `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`codigo_cargo`),
  ADD KEY `codigo_atleta` (`codigo_atleta`),
  ADD KEY `codigo_concepto` (`codigo_concepto`);

--
-- Indices de la tabla `catalogo`
--
ALTER TABLE `catalogo`
  ADD PRIMARY KEY (`id_catalogo`),
  ADD KEY `Id_categoria` (`Id_categoria`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`codigo_categoria`);

--
-- Indices de la tabla `categoria_catalogo`
--
ALTER TABLE `categoria_catalogo`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  ADD PRIMARY KEY (`codigo_concepto`),
  ADD UNIQUE KEY `frecuencia` (`frecuencia`);

--
-- Indices de la tabla `contacto_atleta`
--
ALTER TABLE `contacto_atleta`
  ADD PRIMARY KEY (`codigo_atleta`);

--
-- Indices de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  ADD PRIMARY KEY (`codigo_detalle`),
  ADD KEY `codigo_equipo` (`codigo_equipo`),
  ADD KEY `codigo_atleta` (`codigo_atleta`);

--
-- Indices de la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  ADD PRIMARY KEY (`codigo_detalles_pagos`),
  ADD KEY `codigo_pago` (`codigo_pago`),
  ADD KEY `codigo_cargo` (`codigo_cargo`);

--
-- Indices de la tabla `detalles_participacion`
--
ALTER TABLE `detalles_participacion`
  ADD PRIMARY KEY (`codigo_dtll_prtc`),
  ADD KEY `codigo_atleta` (`codigo_atleta`),
  ADD KEY `codigo_participacion` (`codigo_participacion`);

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id_devolucion`),
  ADD KEY `id_asignacion` (`id_asignacion`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`codigo_equipo`);

--
-- Indices de la tabla `estado_fisico`
--
ALTER TABLE `estado_fisico`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `identidad_atleta`
--
ALTER TABLE `identidad_atleta`
  ADD PRIMARY KEY (`codigo_atleta`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`codigo_inscripcion`),
  ADD KEY `codigo_atleta` (`codigo_atleta`),
  ADD KEY `codigo_categoria` (`codigo_categoria`),
  ADD KEY `codigo_posicion` (`codigo_posicion`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`codigo_metodo`);

--
-- Indices de la tabla `monedas`
--
ALTER TABLE `monedas`
  ADD PRIMARY KEY (`codigo_moneda`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`codigo_pago`),
  ADD KEY `codigo_metodo` (`codigo_metodo`),
  ADD KEY `codigo_moneda` (`codigo_moneda`);

--
-- Indices de la tabla `palmares_grupal`
--
ALTER TABLE `palmares_grupal`
  ADD PRIMARY KEY (`codigo_grupal`),
  ADD KEY `codigo_premio` (`codigo_premio`),
  ADD KEY `codigo_participacion` (`codigo_participacion`);

--
-- Indices de la tabla `palmares_individual`
--
ALTER TABLE `palmares_individual`
  ADD PRIMARY KEY (`codigo_individual`),
  ADD KEY `codigo_premio` (`codigo_premio`),
  ADD KEY `codigo_dtll_prtc` (`codigo_dtll_prtc`);

--
-- Indices de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  ADD PRIMARY KEY (`codigo_participacion`),
  ADD KEY `codigo_equipo` (`codigo_equipo`),
  ADD KEY `codigo_torneo` (`codigo_torneo`);

--
-- Indices de la tabla `posiciones`
--
ALTER TABLE `posiciones`
  ADD PRIMARY KEY (`codigo_posicion`);

--
-- Indices de la tabla `premios`
--
ALTER TABLE `premios`
  ADD PRIMARY KEY (`codigo_premio`);

--
-- Indices de la tabla `representantes`
--
ALTER TABLE `representantes`
  ADD PRIMARY KEY (`codigo_representante`);

--
-- Indices de la tabla `retiros`
--
ALTER TABLE `retiros`
  ADD PRIMARY KEY (`codigo_retiro`),
  ADD KEY `codigo_inscripcion` (`codigo_inscripcion`);

--
-- Indices de la tabla `tasa_cambios`
--
ALTER TABLE `tasa_cambios`
  ADD PRIMARY KEY (`codigo_tasa`),
  ADD KEY `codigo_moneda` (`codigo_moneda`);

--
-- Indices de la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`codigo_torneo`);

--
-- Indices de la tabla `vueltos`
--
ALTER TABLE `vueltos`
  ADD PRIMARY KEY (`codigo_vuelto`),
  ADD KEY `codigo_pago` (`codigo_pago`),
  ADD KEY `codigo_moneda` (`codigo_moneda`),
  ADD KEY `codigo_metodo` (`codigo_metodo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulos_inventario`
--
ALTER TABLE `articulos_inventario`
  MODIFY `codigo_articulo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `atletas`
--
ALTER TABLE `atletas`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `atleta_representante`
--
ALTER TABLE `atleta_representante`
  MODIFY `codigo_at_re` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cargos`
--
ALTER TABLE `cargos`
  MODIFY `codigo_cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `catalogo`
--
ALTER TABLE `catalogo`
  MODIFY `id_catalogo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `codigo_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `categoria_catalogo`
--
ALTER TABLE `categoria_catalogo`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  MODIFY `codigo_concepto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contacto_atleta`
--
ALTER TABLE `contacto_atleta`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  MODIFY `codigo_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  MODIFY `codigo_detalles_pagos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_participacion`
--
ALTER TABLE `detalles_participacion`
  MODIFY `codigo_dtll_prtc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id_devolucion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `codigo_equipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `estado_fisico`
--
ALTER TABLE `estado_fisico`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `identidad_atleta`
--
ALTER TABLE `identidad_atleta`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `codigo_inscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `codigo_metodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `monedas`
--
ALTER TABLE `monedas`
  MODIFY `codigo_moneda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `codigo_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `palmares_grupal`
--
ALTER TABLE `palmares_grupal`
  MODIFY `codigo_grupal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `palmares_individual`
--
ALTER TABLE `palmares_individual`
  MODIFY `codigo_individual` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  MODIFY `codigo_participacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `posiciones`
--
ALTER TABLE `posiciones`
  MODIFY `codigo_posicion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `premios`
--
ALTER TABLE `premios`
  MODIFY `codigo_premio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `representantes`
--
ALTER TABLE `representantes`
  MODIFY `codigo_representante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `retiros`
--
ALTER TABLE `retiros`
  MODIFY `codigo_retiro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tasa_cambios`
--
ALTER TABLE `tasa_cambios`
  MODIFY `codigo_tasa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `codigo_torneo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `vueltos`
--
ALTER TABLE `vueltos`
  MODIFY `codigo_vuelto` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulos_inventario`
--
ALTER TABLE `articulos_inventario`
  ADD CONSTRAINT `articulos_inventario_ibfk_1` FOREIGN KEY (`id_estado`) REFERENCES `estado_fisico` (`id_estado`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `articulos_inventario_ibfk_2` FOREIGN KEY (`id_catalogo`) REFERENCES `catalogo` (`id_catalogo`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`codigo_articulo`) REFERENCES `articulos_inventario` (`codigo_articulo`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `atleta_representante`
--
ALTER TABLE `atleta_representante`
  ADD CONSTRAINT `atleta_representante_ibfk_1` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`),
  ADD CONSTRAINT `atleta_representante_ibfk_2` FOREIGN KEY (`codigo_representante`) REFERENCES `representantes` (`codigo_representante`);

--
-- Filtros para la tabla `cargos`
--
ALTER TABLE `cargos`
  ADD CONSTRAINT `cargos_ibfk_1` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `cargos_ibfk_2` FOREIGN KEY (`codigo_concepto`) REFERENCES `conceptos` (`codigo_concepto`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `catalogo`
--
ALTER TABLE `catalogo`
  ADD CONSTRAINT `catalogo_ibfk_1` FOREIGN KEY (`Id_categoria`) REFERENCES `categoria_catalogo` (`id_categoria`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `contacto_atleta`
--
ALTER TABLE `contacto_atleta`
  ADD CONSTRAINT `contacto_atleta_ibfk_1` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`);

--
-- Filtros para la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  ADD CONSTRAINT `detalles_equipos_ibfk_1` FOREIGN KEY (`codigo_equipo`) REFERENCES `equipos` (`codigo_equipo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `detalles_equipos_ibfk_2` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  ADD CONSTRAINT `detalles_pagos_ibfk_1` FOREIGN KEY (`codigo_pago`) REFERENCES `pagos` (`codigo_pago`),
  ADD CONSTRAINT `detalles_pagos_ibfk_2` FOREIGN KEY (`codigo_cargo`) REFERENCES `cargos` (`codigo_cargo`);

--
-- Filtros para la tabla `detalles_participacion`
--
ALTER TABLE `detalles_participacion`
  ADD CONSTRAINT `detalles_participacion_ibfk_1` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `detalles_participacion_ibfk_2` FOREIGN KEY (`codigo_participacion`) REFERENCES `participaciones` (`codigo_participacion`);

--
-- Filtros para la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `devoluciones_ibfk_1` FOREIGN KEY (`id_asignacion`) REFERENCES `asignaciones` (`id_asignacion`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `devoluciones_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado_fisico` (`id_estado`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `identidad_atleta`
--
ALTER TABLE `identidad_atleta`
  ADD CONSTRAINT `identidad_atleta_ibfk_1` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`);

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`),
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`codigo_categoria`) REFERENCES `categorias` (`codigo_categoria`),
  ADD CONSTRAINT `inscripciones_ibfk_3` FOREIGN KEY (`codigo_posicion`) REFERENCES `posiciones` (`codigo_posicion`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`codigo_metodo`) REFERENCES `metodos_pago` (`codigo_metodo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`codigo_moneda`) REFERENCES `monedas` (`codigo_moneda`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `palmares_grupal`
--
ALTER TABLE `palmares_grupal`
  ADD CONSTRAINT `palmares_grupal_ibfk_1` FOREIGN KEY (`codigo_premio`) REFERENCES `premios` (`codigo_premio`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `palmares_grupal_ibfk_2` FOREIGN KEY (`codigo_participacion`) REFERENCES `participaciones` (`codigo_participacion`);

--
-- Filtros para la tabla `palmares_individual`
--
ALTER TABLE `palmares_individual`
  ADD CONSTRAINT `palmares_individual_ibfk_1` FOREIGN KEY (`codigo_premio`) REFERENCES `premios` (`codigo_premio`),
  ADD CONSTRAINT `palmares_individual_ibfk_2` FOREIGN KEY (`codigo_dtll_prtc`) REFERENCES `detalles_participacion` (`codigo_dtll_prtc`);

--
-- Filtros para la tabla `participaciones`
--
ALTER TABLE `participaciones`
  ADD CONSTRAINT `participaciones_ibfk_1` FOREIGN KEY (`codigo_equipo`) REFERENCES `equipos` (`codigo_equipo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `participaciones_ibfk_2` FOREIGN KEY (`codigo_torneo`) REFERENCES `torneos` (`codigo_torneo`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `retiros`
--
ALTER TABLE `retiros`
  ADD CONSTRAINT `retiros_ibfk_1` FOREIGN KEY (`codigo_inscripcion`) REFERENCES `inscripciones` (`codigo_inscripcion`);

--
-- Filtros para la tabla `tasa_cambios`
--
ALTER TABLE `tasa_cambios`
  ADD CONSTRAINT `tasa_cambios_ibfk_1` FOREIGN KEY (`codigo_moneda`) REFERENCES `monedas` (`codigo_moneda`);

--
-- Filtros para la tabla `vueltos`
--
ALTER TABLE `vueltos`
  ADD CONSTRAINT `vueltos_ibfk_1` FOREIGN KEY (`codigo_pago`) REFERENCES `pagos` (`codigo_pago`),
  ADD CONSTRAINT `vueltos_ibfk_2` FOREIGN KEY (`codigo_moneda`) REFERENCES `monedas` (`codigo_moneda`),
  ADD CONSTRAINT `vueltos_ibfk_3` FOREIGN KEY (`codigo_metodo`) REFERENCES `metodos_pago` (`codigo_metodo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
