-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-07-2026 a las 19:36:37
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

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `RegistrarAtletaCompleto`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegistrarAtletaCompleto` (IN `p_doc_identidad` VARCHAR(20), IN `p_p_nombre` VARCHAR(50), IN `p_s_nombre` VARCHAR(50), IN `p_p_apellidos` VARCHAR(50), IN `p_s_apellidos` VARCHAR(50), IN `p_genero` CHAR(1), IN `p_fecha_nac` DATE, IN `p_telefono` VARCHAR(20), IN `p_direccion` VARCHAR(255), IN `p_representante` INT, IN `p_categoria` INT, IN `p_posicion` INT, IN `p_dorsal` INT, IN `p_peso_kg` DECIMAL(5,2), IN `p_estatura_cm` DECIMAL(5,2), IN `p_foto` VARCHAR(255), OUT `p_resultado` INT)   BEGIN
    DECLARE v_codigo_atleta INT;

    -- Manejador de errores: Si algo falla a nivel de SQL, hace ROLLBACK
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
    END;

    -- Validaciones previas de duplicados
    SET p_resultado = NULL;

    IF p_doc_identidad IS NOT NULL AND p_doc_identidad != '' THEN
        IF (SELECT COUNT(*) FROM identidad_atleta WHERE numero_doc = p_doc_identidad) > 0 THEN
            SET p_resultado = -1; -- Código para cédula duplicada
        END IF;
    END IF;

    IF p_resultado IS NULL AND p_telefono IS NOT NULL AND p_telefono != '' THEN
        IF (SELECT COUNT(*) FROM contacto_atleta WHERE telefono = p_telefono) > 0 THEN
            SET p_resultado = -2; -- Código para teléfono duplicado
        END IF;
    END IF;

    -- Si no hubo duplicados, procedemos con la transacción
    IF p_resultado IS NULL THEN
        START TRANSACTION;

        -- 1. Insertar atleta
        INSERT INTO atletas (p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, foto) 
        VALUES (p_p_nombre, p_s_nombre, p_p_apellidos, p_s_apellidos, p_genero, p_fecha_nac, p_foto);
        
        -- Obtener el ID del atleta recién insertado
        SET v_codigo_atleta = LAST_INSERT_ID();

        -- 2. Insertar contacto (solo si hay datos)
        IF p_telefono != '' OR p_direccion != '' THEN
            INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono) 
            VALUES (v_codigo_atleta, IFNULL(p_direccion, ''), IFNULL(p_telefono, ''));
        END IF;

        -- 3. Insertar identidad
        IF p_doc_identidad != '' THEN
            INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) 
            VALUES (v_codigo_atleta, 'V', p_doc_identidad);
        END IF;

        -- 4. Insertar representante (si es válido)
        IF p_representante IS NOT NULL AND p_representante != 0 THEN
            INSERT INTO atleta_representante (codigo_atleta, codigo_representante) 
            VALUES (v_codigo_atleta, p_representante);
        END IF;

        -- 5. Insertar inscripción
        INSERT INTO inscripciones (codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) 
        VALUES (v_codigo_atleta, p_categoria, p_posicion, p_dorsal, p_peso_kg, p_estatura_cm, CURDATE(), 1);

        -- Guardar los cambios
        COMMIT;
        SET p_resultado = 1; -- Código de éxito
    END IF;
END$$

--
-- Funciones
--
DROP FUNCTION IF EXISTS `ObtenerMontoAbonado`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `ObtenerMontoAbonado` (`p_codigo_cargo` INT) RETURNS DECIMAL(10,2) READS SQL DATA BEGIN
    DECLARE total DECIMAL(10,2);
    
    SELECT COALESCE(SUM(dp.monto_abonado), 0.00) INTO total
    FROM detalles_pagos dp
    INNER JOIN pagos p ON dp.codigo_pago = p.codigo_pago
    WHERE dp.codigo_cargo = p_codigo_cargo 
    AND p.estatus = 1;
    
    RETURN total;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos_inventario`
--

DROP TABLE IF EXISTS `articulos_inventario`;
CREATE TABLE `articulos_inventario` (
  `codigo_articulo` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `id_catalogo` int(11) NOT NULL,
  `codigo_club` varchar(20) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `articulos_inventario`
--

INSERT INTO `articulos_inventario` (`codigo_articulo`, `id_estado`, `id_catalogo`, `codigo_club`, `estatus`) VALUES
(4, 1, 1, 'CL-0001', 2);

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

--
-- Volcado de datos para la tabla `asignaciones`
--

INSERT INTO `asignaciones` (`id_asignacion`, `codigo_atleta`, `codigo_articulo`, `fecha_asignacion`, `estatus`) VALUES
(2, 2, 4, '2026-07-07', 3),
(3, 3, 4, '2026-07-07', 3),
(4, 3, 4, '2026-07-08', 2),
(5, 2, 4, '2026-07-08', 3),
(6, 7, 4, '2026-07-10', 1);

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
(2, 'Moises', 'Jesus', 'Torrellas', '', 'H', '2002-07-25', 'atleta_2002-07-25_1782057957.png'),
(3, 'Maria', 'J', 'Perez', 'Perez', 'M', '2019-02-22', 'atleta_2019-02-22_1783802489.jpg'),
(7, 'Jose', 'Jose', 'Perez', 'Perez', 'H', '2020-06-09', 'atleta_2020-06-09_1783571646.png'),
(8, 'Rosa', 'Maria', 'Lopez', 'Perez', 'M', '2017-06-07', 'atleta_2017-06-07_1783821293.jpg');

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

--
-- Volcado de datos para la tabla `atleta_representante`
--

INSERT INTO `atleta_representante` (`codigo_at_re`, `codigo_atleta`, `codigo_representante`) VALUES
(2, 3, 2),
(3, 7, 2),
(4, 8, 2);

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
  `estatus` tinyint(4) NOT NULL DEFAULT 1,
  `codigo_moneda` int(11) NOT NULL DEFAULT 2,
  `multado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `cargos`
--

INSERT INTO `cargos` (`codigo_cargo`, `codigo_concepto`, `codigo_atleta`, `monto_total`, `fecha_emision`, `estatus`, `codigo_moneda`, `multado`) VALUES
(8, 1, 2, 30.00, '2026-07-08', 2, 2, 0),
(9, 2, 2, 25.00, '2026-07-08', 2, 2, 0),
(10, 2, 3, 25.00, '2026-07-08', 1, 2, 0),
(11, 1, 3, 30.00, '2026-07-08', 2, 2, 1),
(12, 5, 3, 5.00, '2026-07-14', 2, 2, 0),
(13, 2, 7, 25.00, '2026-07-09', 1, 2, 0),
(14, 1, 7, 30.00, '2026-07-09', 2, 2, 0),
(15, 2, 8, 25.00, '2026-07-10', 1, 2, 0),
(16, 1, 8, 30.00, '2026-07-10', 1, 2, 0),
(17, 3, 8, 25.00, '2004-03-18', 1, 2, 1),
(18, 3, 2, 25.00, '1987-06-24', 2, 2, 1),
(19, 5, 8, 5.00, '2026-07-10', 1, 2, 0),
(20, 5, 2, 5.00, '2026-07-10', 2, 2, 0);

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

--
-- Volcado de datos para la tabla `catalogo`
--

INSERT INTO `catalogo` (`id_catalogo`, `nombre`, `stock_minimo`, `Id_categoria`, `talla`) VALUES
(1, 'Casco Tiplex', 10, 1, '10');

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

--
-- Volcado de datos para la tabla `categoria_catalogo`
--

INSERT INTO `categoria_catalogo` (`id_categoria`, `nombre`, `descripcion`) VALUES
(1, 'Cascos', 'proteccion anti caidas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos`
--

DROP TABLE IF EXISTS `conceptos`;
CREATE TABLE `conceptos` (
  `codigo_concepto` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `monto` decimal(10,0) NOT NULL,
  `frecuencia` enum('A','M','L','U','T') NOT NULL,
  `dias_gracia` int(11) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `conceptos`
--

INSERT INTO `conceptos` (`codigo_concepto`, `nombre`, `monto`, `frecuencia`, `dias_gracia`, `estatus`) VALUES
(1, 'Mensualidad', 30, 'M', 5, 1),
(2, 'Inscripcion', 25, 'A', 10, 1),
(3, 'Viaticos', 25, 'L', 10, 1),
(5, 'Multa Por Demora', 5, 'T', 0, 1);

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
(5, 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pagos`
--

DROP TABLE IF EXISTS `detalles_pagos`;
CREATE TABLE `detalles_pagos` (
  `codigo_detalles_pagos` int(11) NOT NULL,
  `codigo_pago` int(11) NOT NULL,
  `codigo_cargo` int(11) NOT NULL,
  `monto_abonado` decimal(10,2) NOT NULL,
  `tasa_cambio` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `detalles_pagos`
--

INSERT INTO `detalles_pagos` (`codigo_detalles_pagos`, `codigo_pago`, `codigo_cargo`, `monto_abonado`, `tasa_cambio`) VALUES
(27, 35, 8, 30.00, 1.0000),
(28, 36, 9, 25.00, 1.0000),
(29, 37, 10, 0.02, 709.6935),
(30, 38, 11, 30.00, 1.0000),
(31, 39, 14, 30.00, 1.0000),
(32, 40, 12, 5.00, 1.0000),
(33, 41, 13, 21.14, 709.6935),
(34, 44, 18, 25.00, 1.0000),
(35, 44, 20, 5.00, 1.0000);

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
  `average` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `detalles_participacion`
--

INSERT INTO `detalles_participacion` (`codigo_dtll_prtc`, `codigo_participacion`, `codigo_atleta`, `goles`, `asistencias`, `penalizaciones`, `goles_contra`, `partidos_jugados`, `average`) VALUES
(3, 1, 2, 3, 0, 2, 4, 2, 1.50);

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
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `devoluciones`
--

INSERT INTO `devoluciones` (`id_devolucion`, `id_asignacion`, `id_estado`, `fecha_devolucion`, `observacion`) VALUES
(2, 4, 1, '2026-07-08', '');

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

--
-- Volcado de datos para la tabla `estado_fisico`
--

INSERT INTO `estado_fisico` (`id_estado`, `nombre`, `nivel_estado`) VALUES
(1, 'Exelente', 1),
(2, 'Dañado', 3),
(3, 'Mas O Menos', 2);

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
(2, 'V', '29506932'),
(8, 'V', '32847654');

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
(5, 2, 7, 1, 12, 90, 185, '2026-06-21', 1),
(6, 3, 2, 1, 34, 60, 150, '2026-06-23', 2),
(7, 3, 2, 1, 34, 60, 150, '2026-06-27', 2),
(8, 3, 2, 1, 34, 60, 150, '2026-07-06', 2),
(9, 3, 2, 1, 34, 60, 150, '2026-07-07', 1),
(10, 7, 1, 1, 19, 60, 160, '2026-07-09', 2),
(11, 7, 1, 1, 19, 60, 160, '2026-07-09', 1),
(12, 8, 3, 1, 45, 50, 150, '2026-07-10', 1);

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
  `monto_pago` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `referencia` varchar(255) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`codigo_pago`, `codigo_metodo`, `codigo_moneda`, `monto_pago`, `fecha`, `referencia`, `estatus`) VALUES
(35, 2, 2, 30.00, '2026-07-08', '2323', 1),
(36, 2, 2, 30.00, '2026-07-09', '2345', 1),
(37, 2, 1, 12.32, '2026-07-09', '1212', 1),
(38, 2, 2, 50.00, '2026-07-08', '1212', 1),
(39, 2, 2, 32.00, '2026-07-09', '2323', 1),
(40, 2, 2, 6.00, '2026-07-09', '456', 1),
(41, 2, 1, 15000.00, '2026-07-09', '1212', 1),
(44, 2, 2, 30.00, '2026-07-10', '2222', 1);

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
(2, 'G', 'Primer Lugar');

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
(2, '13197214', '0232-1334423', 'El Tocuyo', 'Jessica', 'Aguilar', 'V');

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
(4, 4, '2026-06-21', 'falta de pago'),
(5, 6, '2026-06-27', 'Viaje Largo'),
(6, 7, '2026-07-06', 'ASDASD'),
(7, 8, '2026-07-07', 'viaje'),
(8, 10, '2026-07-09', 'Viaje');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasa_cambios`
--

DROP TABLE IF EXISTS `tasa_cambios`;
CREATE TABLE `tasa_cambios` (
  `codigo_tasa` int(11) NOT NULL,
  `codigo_moneda` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `valor_tasa` varchar(255) NOT NULL,
  `tipo` enum('automatica','manual') NOT NULL DEFAULT 'manual'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `tasa_cambios`
--

INSERT INTO `tasa_cambios` (`codigo_tasa`, `codigo_moneda`, `fecha`, `valor_tasa`, `tipo`) VALUES
(4, 1, '2026-06-23', '612.4332', 'manual'),
(5, 2, '2026-06-23', '1', 'manual'),
(6, 1, '2026-07-06', '1', 'automatica'),
(7, 2, '2026-07-06', '1', 'automatica'),
(8, 2, '2026-07-08', '1', 'automatica'),
(9, 1, '2026-07-08', '685.9427', 'automatica'),
(10, 1, '2026-07-09', '709.6935', 'automatica'),
(11, 2, '2026-07-09', '1', 'automatica'),
(12, 2, '2026-07-10', '1', 'manual');

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
(1, 'BARQUISIMETO 2026', '2026-06-22', '2026-06-24', 'Barquisimeto', 3),
(2, 'TOCUYO 2026', '2026-07-06', '2026-07-10', 'El Tocuyo Estado Lara', 3),
(3, 'QUIBOR 2026', '2026-07-15', '2026-07-18', 'Quibor Estado Lara', 1);

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
,`monto_pendiente` decimal(33,2)
,`estatus` tinyint(4)
,`multado` tinyint(1)
,`estatus_texto` varchar(12)
,`atleta_nombre` varchar(50)
,`atleta_apellido` varchar(50)
,`concepto_nombre` varchar(255)
,`moneda_nombre` varchar(255)
,`moneda_simbolo` varchar(255)
,`moneda_abreviatura` varchar(255)
,`deuda_moneda_atleta` decimal(55,2)
,`total_facturas_atleta` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_pagos`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_pagos`;
CREATE TABLE `vista_pagos` (
`id_pago` int(11)
,`fecha_pago` date
,`monto_pagado` decimal(10,2)
,`monto_vuelto` decimal(32,2)
,`referencia` varchar(255)
,`estatus` tinyint(4)
,`nombre_metodo_pago` varchar(255)
,`simbolo` varchar(255)
,`abre` varchar(255)
,`moneda` varchar(255)
,`id_detalle_pago` int(11)
,`monto_abonado` decimal(10,2)
,`tasa_cambio` decimal(10,4)
,`concepto_pago` varchar(255)
,`nombre_atleta` varchar(50)
,`nombre_apellido` varchar(50)
,`simbolo_cuenta` varchar(255)
,`abre_cuenta` varchar(255)
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
  `monto_vuelto` decimal(10,2) NOT NULL,
  `fecha_vuelto` date NOT NULL,
  `referencia` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `vueltos`
--

INSERT INTO `vueltos` (`codigo_vuelto`, `codigo_metodo`, `codigo_pago`, `codigo_moneda`, `monto_vuelto`, `fecha_vuelto`, `referencia`) VALUES
(4, 2, 38, 1, 0.03, '2026-07-09', '2323'),
(5, 2, 39, 1, 1419.39, '2026-07-09', '2323');

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
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_cargos`  AS SELECT `c`.`codigo_cargo` AS `id_cobrar`, `c`.`codigo_atleta` AS `id_atleta`, `c`.`codigo_concepto` AS `id_concepto`, `c`.`fecha_emision` AS `fecha_emision`, `c`.`fecha_emision`+ interval `co`.`dias_gracia` day AS `fecha_vencimiento`, `c`.`monto_total` AS `monto_total`, `c`.`monto_total` AS `monto_personalizado`, greatest(`c`.`monto_total` - coalesce(`abonos`.`total_abonado`,0),0) AS `monto_pendiente`, `c`.`estatus` AS `estatus`, `c`.`multado` AS `multado`, CASE WHEN `c`.`estatus` = 3 THEN 'Anulado' WHEN `c`.`estatus` = 2 THEN 'Pagado' WHEN `c`.`estatus` = 1 THEN 'Pendiente' ELSE 'Abonado/Otro' END AS `estatus_texto`, `a`.`p_nombre` AS `atleta_nombre`, `a`.`p_apellidos` AS `atleta_apellido`, `co`.`nombre` AS `concepto_nombre`, `m`.`nombre` AS `moneda_nombre`, `m`.`simbolo` AS `moneda_simbolo`, `m`.`abreviatura` AS `moneda_abreviatura`, sum(case when `c`.`estatus` not in (2,3) then greatest(`c`.`monto_total` - coalesce(`abonos`.`total_abonado`,0),0) else 0 end) over ( partition by `c`.`codigo_atleta`,`m`.`codigo_moneda`) AS `deuda_moneda_atleta`, count(`c`.`codigo_cargo`) over ( partition by `c`.`codigo_atleta`) AS `total_facturas_atleta` FROM ((((`cargos` `c` join `atletas` `a` on(`c`.`codigo_atleta` = `a`.`codigo_atleta`)) join `conceptos` `co` on(`c`.`codigo_concepto` = `co`.`codigo_concepto`)) join `monedas` `m` on(`m`.`codigo_moneda` = `c`.`codigo_moneda`)) left join (select `dp`.`codigo_cargo` AS `codigo_cargo`,sum(`dp`.`monto_abonado`) AS `total_abonado` from (`detalles_pagos` `dp` join `pagos` `p` on(`dp`.`codigo_pago` = `p`.`codigo_pago` and `p`.`estatus` = 1)) group by `dp`.`codigo_cargo`) `abonos` on(`abonos`.`codigo_cargo` = `c`.`codigo_cargo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_pagos`
--
DROP TABLE IF EXISTS `vista_pagos`;

DROP VIEW IF EXISTS `vista_pagos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_pagos`  AS SELECT `p`.`codigo_pago` AS `id_pago`, `p`.`fecha` AS `fecha_pago`, `p`.`monto_pago` AS `monto_pagado`, ifnull((select sum(`v`.`monto_vuelto`) from `vueltos` `v` where `v`.`codigo_pago` = `p`.`codigo_pago`),0) AS `monto_vuelto`, `p`.`referencia` AS `referencia`, `p`.`estatus` AS `estatus`, `mp`.`nombre` AS `nombre_metodo_pago`, `m`.`simbolo` AS `simbolo`, `m`.`abreviatura` AS `abre`, `m`.`nombre` AS `moneda`, `dp`.`codigo_detalles_pagos` AS `id_detalle_pago`, `dp`.`monto_abonado` AS `monto_abonado`, `dp`.`tasa_cambio` AS `tasa_cambio`, `con`.`nombre` AS `concepto_pago`, `a`.`p_nombre` AS `nombre_atleta`, `a`.`p_apellidos` AS `nombre_apellido`, `mb`.`simbolo` AS `simbolo_cuenta`, `mb`.`abreviatura` AS `abre_cuenta` FROM (((((((`pagos` `p` left join `metodos_pago` `mp` on(`p`.`codigo_metodo` = `mp`.`codigo_metodo`)) left join `monedas` `m` on(`p`.`codigo_moneda` = `m`.`codigo_moneda`)) left join `detalles_pagos` `dp` on(`p`.`codigo_pago` = `dp`.`codigo_pago`)) left join `cargos` `car` on(`dp`.`codigo_cargo` = `car`.`codigo_cargo`)) left join `conceptos` `con` on(`car`.`codigo_concepto` = `con`.`codigo_concepto`)) left join `atletas` `a` on(`car`.`codigo_atleta` = `a`.`codigo_atleta`)) join (select `monedas`.`simbolo` AS `simbolo`,`monedas`.`abreviatura` AS `abreviatura` from `monedas` where `monedas`.`base` = 1 limit 1) `mb`) ;

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
  ADD PRIMARY KEY (`codigo_atleta`);

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
  ADD KEY `codigo_concepto` (`codigo_concepto`),
  ADD KEY `cargos_ibfk_3` (`codigo_moneda`);

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
  MODIFY `codigo_articulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `atletas`
--
ALTER TABLE `atletas`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `atleta_representante`
--
ALTER TABLE `atleta_representante`
  MODIFY `codigo_at_re` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cargos`
--
ALTER TABLE `cargos`
  MODIFY `codigo_cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `catalogo`
--
ALTER TABLE `catalogo`
  MODIFY `id_catalogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `codigo_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `categoria_catalogo`
--
ALTER TABLE `categoria_catalogo`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  MODIFY `codigo_concepto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `contacto_atleta`
--
ALTER TABLE `contacto_atleta`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  MODIFY `codigo_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  MODIFY `codigo_detalles_pagos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `detalles_participacion`
--
ALTER TABLE `detalles_participacion`
  MODIFY `codigo_dtll_prtc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id_devolucion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `codigo_equipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `estado_fisico`
--
ALTER TABLE `estado_fisico`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `identidad_atleta`
--
ALTER TABLE `identidad_atleta`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `codigo_inscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `codigo_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

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
  MODIFY `codigo_participacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `codigo_representante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `retiros`
--
ALTER TABLE `retiros`
  MODIFY `codigo_retiro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tasa_cambios`
--
ALTER TABLE `tasa_cambios`
  MODIFY `codigo_tasa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `codigo_torneo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `vueltos`
--
ALTER TABLE `vueltos`
  MODIFY `codigo_vuelto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  ADD CONSTRAINT `cargos_ibfk_2` FOREIGN KEY (`codigo_concepto`) REFERENCES `conceptos` (`codigo_concepto`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `cargos_ibfk_3` FOREIGN KEY (`codigo_moneda`) REFERENCES `monedas` (`codigo_moneda`);

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
