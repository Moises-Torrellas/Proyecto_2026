-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-08-2026 a las 23:25:03
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
DROP PROCEDURE IF EXISTS `EliminarCatalogoSeguro`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `EliminarCatalogoSeguro` (IN `p_id_catalogo` INT, OUT `p_resultado` INT)   BEGIN
    DECLARE v_existe INT;
    DECLARE v_en_uso INT;

    -- Si hay un fallo de integridad, se deshace todo
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0; 
    END;

    START TRANSACTION;

    -- Validar si el catálogo existe
    SELECT COUNT(*) INTO v_existe FROM catalogo WHERE id_catalogo = p_id_catalogo FOR UPDATE;
    
    -- Validar si tiene artículos físicos amarrados en el inventario
    SELECT COUNT(*) INTO v_en_uso FROM articulos_inventario WHERE id_catalogo = p_id_catalogo;

    IF v_existe = 0 THEN
        SET p_resultado = -1; -- -1: No existe
        ROLLBACK;
    ELSEIF v_en_uso > 0 THEN
        SET p_resultado = -2; -- -2: No se puede borrar, tiene artículos físicos
        ROLLBACK;
    ELSE
        -- Todo en orden, procedemos a eliminar
        DELETE FROM catalogo WHERE id_catalogo = p_id_catalogo;
        COMMIT;
        SET p_resultado = 1; -- 1: Éxito
    END IF;
END$$

DROP PROCEDURE IF EXISTS `ProcesarDevolucionSegura`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ProcesarDevolucionSegura` (IN `p_id_asignacion` INT, IN `p_id_estado` INT, IN `p_observacion` VARCHAR(255))   BEGIN
    DECLARE v_estatus INT;
    
    -- Manejador de errores para hacer rollback automático si algo falla
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL; -- Reenvía el error a PHP para que lo capture
    END;

    START TRANSACTION;
    
    -- 1. Consultamos el estatus actual bloqueando la fila (FOR UPDATE)
    SELECT estatus INTO v_estatus 
    FROM asignaciones 
    WHERE id_asignacion = p_id_asignacion 
    FOR UPDATE;
    
    -- 2. Validamos que la asignación siga estando activa (Estatus 1 = En Uso)
    IF v_estatus = 1 THEN
        
        -- Cambiamos el estatus (asumiendo que 2 significa "Devuelto" o inactivo)
        UPDATE asignaciones SET estatus = 2 WHERE id_asignacion = p_id_asignacion;
        
        -- Registramos la devolución
        INSERT INTO devoluciones (id_asignacion, id_estado, fecha_devolucion, observacion) 
        VALUES (p_id_asignacion, p_id_estado, CURDATE(), p_observacion);
        
        COMMIT;
        
    ELSE
        -- Si el estatus no es 1, abortamos lanzando una alerta que atrapará PHP
        ROLLBACK;
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error de Concurrencia: Esta asignación ya fue devuelta o procesada por otro usuario.';
    END IF;

END$$

DROP PROCEDURE IF EXISTS `ProcesarPalmaresGrupal`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ProcesarPalmaresGrupal` (IN `p_id_participacion` INT, IN `p_id_premio` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    INSERT INTO palmares_grupal (codigo_participacion, codigo_premio) 
    VALUES (p_id_participacion, p_id_premio);
    COMMIT;
END$$

DROP PROCEDURE IF EXISTS `RegistrarAtletaCompleto`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegistrarAtletaCompleto` (IN `p_doc_identidad` VARCHAR(20), IN `p_p_nombre` VARCHAR(50), IN `p_s_nombre` VARCHAR(50), IN `p_p_apellidos` VARCHAR(50), IN `p_s_apellidos` VARCHAR(50), IN `p_genero` CHAR(1), IN `p_fecha_nac` DATE, IN `p_telefono` VARCHAR(20), IN `p_direccion` VARCHAR(255), IN `p_representante` INT, IN `p_categoria` INT, IN `p_posicion` INT, IN `p_dorsal` INT, IN `p_peso_kg` DECIMAL(5,2), IN `p_estatura_cm` DECIMAL(5,2), IN `p_foto` VARCHAR(255), IN `p_lugar_nacimiento` VARCHAR(255), IN `p_correo` VARCHAR(255), IN `p_municipio` VARCHAR(255), IN `p_instagram` VARCHAR(255), IN `p_talla_pantalon` VARCHAR(10), IN `p_talla_franela` VARCHAR(10), IN `p_talla_calzado` VARCHAR(10), IN `p_tipo_sangre` VARCHAR(5), IN `p_es_alergico` TINYINT(1), IN `p_alergias_detalle` TEXT, OUT `p_resultado` INT)   BEGIN
    DECLARE v_codigo_atleta INT;

    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
    END;

    
    SET p_resultado = NULL;

    IF p_doc_identidad IS NOT NULL AND p_doc_identidad != '' THEN
        IF (SELECT COUNT(*) FROM identidad_atleta WHERE numero_doc = p_doc_identidad) > 0 THEN
            SET p_resultado = -1; 
        END IF;
    END IF;

    IF p_resultado IS NULL AND p_telefono IS NOT NULL AND p_telefono != '' THEN
        IF (SELECT COUNT(*) FROM contacto_atleta WHERE telefono = p_telefono) > 0 THEN
            SET p_resultado = -2; 
        END IF;
    END IF;

    
    IF p_resultado IS NULL THEN
        START TRANSACTION;

        
        INSERT INTO atletas (p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, foto, lugar_nacimiento) 
        VALUES (p_p_nombre, p_s_nombre, p_p_apellidos, p_s_apellidos, p_genero, p_fecha_nac, p_foto, p_lugar_nacimiento);
        
        
        SET v_codigo_atleta = LAST_INSERT_ID();

        
        IF p_telefono != '' OR p_direccion != '' OR p_correo != '' OR p_municipio != '' OR p_instagram != '' THEN
            INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono, correo, municipio, instagram) 
            VALUES (v_codigo_atleta, IFNULL(p_direccion, ''), IFNULL(p_telefono, ''), p_correo, p_municipio, p_instagram);
        END IF;

        
        IF p_doc_identidad != '' THEN
            INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) 
            VALUES (v_codigo_atleta, 'V', p_doc_identidad);
        END IF;

        
        IF p_representante IS NOT NULL AND p_representante != 0 THEN
            INSERT INTO atleta_representante (codigo_atleta, codigo_representante) 
            VALUES (v_codigo_atleta, p_representante);
        END IF;

        
        INSERT INTO inscripciones (codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
        VALUES (v_codigo_atleta, p_categoria, p_posicion, p_dorsal, p_peso_kg, p_estatura_cm, CURDATE(), 1, p_talla_pantalon, p_talla_franela, p_talla_calzado);

        
        INSERT INTO datos_medicos (codigo_atleta, tipo_sangre, es_alergico, alergias_detalle)
        VALUES (v_codigo_atleta, p_tipo_sangre, p_es_alergico, p_alergias_detalle);

        
        COMMIT;
        SET p_resultado = 1; 
    END IF;
END$$

DROP PROCEDURE IF EXISTS `RegistrarParticipacionSegura`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegistrarParticipacionSegura` (IN `p_id_equipo` INT, IN `p_id_torneo` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    INSERT INTO participaciones (codigo_equipo, codigo_torneo) 
    VALUES (p_id_equipo, p_id_torneo);
    COMMIT;
END$$

DROP PROCEDURE IF EXISTS `RegistrarPremioSeguro`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegistrarPremioSeguro` (IN `p_id_atleta` INT, IN `p_descripcion` VARCHAR(255), IN `p_monto_premio` DECIMAL(10,2))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    
    INSERT INTO premios (id_atleta, descripcion, monto, fecha_entrega) 
    VALUES (p_id_atleta, p_descripcion, p_monto_premio, CURDATE());
    
    COMMIT;
END$$

DROP PROCEDURE IF EXISTS `RegistrarVueltoSeguro`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegistrarVueltoSeguro` (IN `p_codigo_metodo` INT, IN `p_codigo_pago` INT, IN `p_codigo_moneda` INT, IN `p_monto_vuelto` DECIMAL(10,2), IN `p_fecha_vuelto` DATE, IN `p_referencia` VARCHAR(255), IN `p_monto_base` DECIMAL(10,2))   BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
        END;
    
        START TRANSACTION;
        INSERT INTO vueltos (codigo_metodo, codigo_pago, codigo_moneda, monto_vuelto, fecha_vuelto, referencia, monto_base) 
        VALUES (p_codigo_metodo, p_codigo_pago, p_codigo_moneda, p_monto_vuelto, p_fecha_vuelto, p_referencia, p_monto_base);
        COMMIT;
    END$$

DROP PROCEDURE IF EXISTS `RetirarArticuloSeguro`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RetirarArticuloSeguro` (IN `p_articulo` INT, OUT `p_resultado` INT)   BEGIN
    DECLARE v_estatus TINYINT;

    -- Manejador de errores: Si la base de datos falla, se hace ROLLBACK automático
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0; -- 0: Error de BD
    END;

    -- Iniciamos la transacción segura
    START TRANSACTION;

    -- Consultamos y bloqueamos el registro momentáneamente
    SELECT estatus INTO v_estatus FROM articulos_inventario WHERE codigo_articulo = p_articulo FOR UPDATE;

    IF v_estatus IS NULL THEN
        SET p_resultado = -1; -- -1: El equipo no existe
        ROLLBACK;
    ELSEIF v_estatus != 1 THEN
        SET p_resultado = -2; -- -2: El equipo está en uso o ya fue retirado
        ROLLBACK;
    ELSE
        -- Retiramos el artículo (estatus 3 según tu lógica)
        UPDATE articulos_inventario SET estatus = 3 WHERE codigo_articulo = p_articulo;
        
        COMMIT; -- Guardamos cambios
        SET p_resultado = 1; -- 1: Éxito
    END IF;
END$$

--
-- Funciones
--
DROP FUNCTION IF EXISTS `EsAptoParaUso`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `EsAptoParaUso` (`p_id_estado` INT) RETURNS TINYINT(4) READS SQL DATA BEGIN
    DECLARE v_nivel TINYINT;
    SELECT nivel_estado INTO v_nivel FROM estado_fisico WHERE id_estado = p_id_estado;
    -- Si el nivel del estado es 1 (Excelente), retorna 1 (Sí). Si no, retorna 0 (No).
    RETURN IF(v_nivel = 1, 1, 0);
END$$

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

DROP FUNCTION IF EXISTS `ObtenerTasaActual`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `ObtenerTasaActual` () RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    DECLARE v_tasa DECIMAL(10,2);
    SELECT valor_tasa INTO v_tasa FROM tasa_cambios ORDER BY fecha_actualizacion DESC LIMIT 1;
    RETURN COALESCE(v_tasa, 1.00);
END$$

DROP FUNCTION IF EXISTS `ObtenerTorneosAtleta`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `ObtenerTorneosAtleta` (`p_id_atleta` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_total INT;
    SELECT COUNT(DISTINCT part.codigo_torneo) INTO v_total 
    FROM detalles_participacion dp 
    INNER JOIN participaciones part ON dp.codigo_participacion = part.codigo_participacion 
    WHERE dp.codigo_atleta = p_id_atleta;
    RETURN COALESCE(v_total, 0);
END$$

DROP FUNCTION IF EXISTS `ObtenerTotalPremiosEquipo`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `ObtenerTotalPremiosEquipo` (`p_id_equipo` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_total INT;
    SELECT COUNT(*) INTO v_total 
    FROM palmares_grupal pg 
    INNER JOIN participaciones p ON pg.codigo_participacion = p.codigo_participacion 
    WHERE p.codigo_equipo = p_id_equipo;
    RETURN COALESCE(v_total, 0);
END$$

DROP FUNCTION IF EXISTS `StockDisponibleCatalogo`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `StockDisponibleCatalogo` (`p_id_catalogo` INT) RETURNS INT(11) READS SQL DATA BEGIN
    DECLARE v_total INT;
    -- Cuenta cuántos artículos físicos de ese catálogo están libres (estatus 1) y en excelente estado (id_estado 1)
    SELECT COUNT(*) INTO v_total FROM articulos_inventario 
    WHERE id_catalogo = p_id_catalogo AND estatus = 1 AND id_estado = 1;
    RETURN v_total;
END$$

DROP FUNCTION IF EXISTS `TotalDevolucionesMes`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `TotalDevolucionesMes` (`p_mes` INT, `p_anio` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_total INT;
    SELECT COUNT(*) INTO v_total FROM devoluciones WHERE MONTH(fecha_devolucion) = p_mes AND YEAR(fecha_devolucion) = p_anio;
    RETURN COALESCE(v_total, 0);
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
(4, 2, 1, 'CL-0001', 3),
(5, 2, 1, 'CL-0002', 3),
(6, 3, 1, 'CL-0003', 1);

--
-- Disparadores `articulos_inventario`
--
DROP TRIGGER IF EXISTS `trg_bloquear_articulos_danados`;
DELIMITER $$
CREATE TRIGGER `trg_bloquear_articulos_danados` BEFORE UPDATE ON `articulos_inventario` FOR EACH ROW BEGIN
    DECLARE v_nivel TINYINT;
    SELECT nivel_estado INTO v_nivel FROM estado_fisico WHERE id_estado = NEW.id_estado;
    IF v_nivel = 3 THEN
        SET NEW.estatus = 3;
    END IF;
END
$$
DELIMITER ;

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
(10, 2, 4, '2026-08-26', 2),
(11, 3, 5, '2026-08-26', 3),
(12, 3, 4, '2026-08-26', 2),
(13, 2, 4, '2026-08-26', 2),
(14, 7, 4, '2026-08-26', 2),
(15, 7, 5, '2026-08-26', 2),
(16, 8, 5, '2026-08-28', 2);

--
-- Disparadores `asignaciones`
--
DROP TRIGGER IF EXISTS `trg_after_insert_asignacion`;
DELIMITER $$
CREATE TRIGGER `trg_after_insert_asignacion` AFTER INSERT ON `asignaciones` FOR EACH ROW BEGIN
    UPDATE articulos_inventario 
    SET estatus = 2
    WHERE codigo_articulo = NEW.codigo_articulo;
END
$$
DELIMITER ;

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
  `foto` varchar(255) NOT NULL,
  `lugar_nacimiento` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `atletas`
--

INSERT INTO `atletas` (`codigo_atleta`, `p_nombre`, `s_nombre`, `p_apellidos`, `s_apellidos`, `genero`, `fecha_nac`, `foto`, `lugar_nacimiento`) VALUES
(2, 'Moises', 'Jesus', 'Torrellas', '', 'H', '2002-07-25', 'atleta_2002-07-25_1782057957.png', 'Moran, El Tocuyo'),
(3, 'Maria', 'Jose', 'Perez', 'Perez', 'M', '2019-02-22', 'atleta_2019-02-22_1783802489.jpg', 'Barquisimeto'),
(7, 'Jose', 'Jose', 'Perez', 'Perez', 'H', '2020-06-09', 'atleta_2020-06-09_1784584218.jpg', 'Barquisimeto'),
(8, 'Rosa', 'Maria', 'Lopez', 'Perez', 'M', '2017-06-07', 'atleta_2017-06-07_1783821293.jpg', 'Barquisimeto'),
(10, 'Jose', '', 'Lopez', '', 'H', '2006-07-20', 'atleta_2006-07-20_1787947857.jpg', 'El Tocuyo');

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
(10, 2, 3, 25.00, '2026-07-08', 2, 2, 1),
(11, 1, 3, 30.00, '2026-07-08', 2, 2, 1),
(12, 5, 3, 5.00, '2026-07-14', 2, 2, 0),
(13, 2, 7, 25.00, '2026-07-09', 1, 2, 1),
(14, 1, 7, 30.00, '2026-07-09', 2, 2, 0),
(15, 2, 8, 25.00, '2026-07-10', 2, 2, 1),
(16, 1, 8, 30.00, '2026-07-10', 2, 2, 1),
(17, 3, 8, 25.00, '2004-03-18', 2, 2, 1),
(18, 3, 2, 25.00, '1987-06-24', 2, 2, 1),
(19, 5, 8, 5.00, '2026-07-10', 3, 2, 0),
(20, 5, 2, 5.00, '2026-07-10', 2, 2, 0),
(21, 5, 8, 5.00, '2026-07-16', 2, 2, 0),
(22, 5, 3, 5.00, '2026-07-20', 2, 2, 0),
(23, 5, 7, 5.00, '2026-07-20', 2, 2, 0),
(24, 5, 8, 5.00, '2026-07-30', 2, 2, 0),
(25, 1, 2, 30.00, '2026-08-17', 2, 2, 1),
(26, 1, 3, 30.00, '2026-08-17', 2, 2, 1),
(27, 1, 7, 30.00, '2026-08-17', 2, 2, 1),
(28, 1, 8, 30.00, '2026-08-17', 2, 2, 1),
(29, 3, 3, 25.00, '2026-08-18', 0, 2, 0),
(30, 5, 2, 5.00, '2026-08-23', 0, 2, 0),
(31, 5, 3, 5.00, '2026-08-23', 3, 2, 0),
(32, 5, 7, 5.00, '2026-08-23', 2, 2, 0),
(33, 5, 8, 5.00, '2026-08-23', 2, 2, 0),
(34, 3, 7, 25.00, '2026-08-24', 1, 2, 0),
(35, 3, 2, 25.00, '2026-08-28', 3, 2, 0),
(36, 2, 10, 25.00, '2026-08-28', 3, 1, 0),
(37, 1, 10, 30.00, '2026-08-28', 3, 1, 0);

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
(1, 'Casco Tiplex', 1, 1, '10');

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
(7, 'SENIOR', 18, 50),
(8, 'U-17', 15, 17);

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
(1, 'Cascos', 'proteccion anti caidas'),
(2, 'Proteccion', 'protectores para los jugadores');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conceptos`
--

DROP TABLE IF EXISTS `conceptos`;
CREATE TABLE `conceptos` (
  `codigo_concepto` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `frecuencia` enum('A','M','L','U','T') NOT NULL,
  `dias_gracia` int(11) NOT NULL,
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `conceptos`
--

INSERT INTO `conceptos` (`codigo_concepto`, `nombre`, `monto`, `frecuencia`, `dias_gracia`, `estatus`) VALUES
(1, 'Mensualidad', 30.00, 'M', 5, 1),
(2, 'Inscripcion', 25.00, 'A', 10, 1),
(3, 'Viaticos', 25.00, 'L', 0, 1),
(5, 'Multa Por Demora', 5.00, 'T', 0, 1),
(9, 'Nuevo Monto', 30.50, 'L', 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contacto_atleta`
--

DROP TABLE IF EXISTS `contacto_atleta`;
CREATE TABLE `contacto_atleta` (
  `codigo_atleta` int(11) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono` varchar(255) NOT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `municipio` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `contacto_atleta`
--

INSERT INTO `contacto_atleta` (`codigo_atleta`, `direccion`, `telefono`, `correo`, `municipio`, `instagram`) VALUES
(2, 'Calle 8 Entre Carrera 14 Y Av. Circunvalacion', '0412-0565231', 'moitcj@gmail.com', 'Moran', 'moises'),
(3, '', '', 'maria@gmail.com', 'Iribarren', ''),
(7, '', '', 'jose@gmail.com', 'Iribarren', ''),
(8, '', '', 'rosa@gmail.com', 'Iribarren', ''),
(10, 'Calle 8', '0412-0565234', 'moicj@gmail.com', 'Moran', 'moisese');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_medicos`
--

DROP TABLE IF EXISTS `datos_medicos`;
CREATE TABLE `datos_medicos` (
  `codigo_atleta` int(11) NOT NULL,
  `tipo_sangre` varchar(5) DEFAULT NULL,
  `es_alergico` tinyint(1) NOT NULL DEFAULT 0,
  `alergias_detalle` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `datos_medicos`
--

INSERT INTO `datos_medicos` (`codigo_atleta`, `tipo_sangre`, `es_alergico`, `alergias_detalle`) VALUES
(2, 'B+', 1, 'Penicilina'),
(3, 'A-', 0, ''),
(7, 'AB-', 0, ''),
(8, 'B-', 0, ''),
(10, 'B-', 1, 'camarones');

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
(6, 1, 2),
(8, 4, 7);

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
(35, 44, 20, 5.00, 1.0000),
(36, 46, 25, 30.00, 1.0000),
(37, 48, 16, 30.00, 1.0000),
(38, 49, 10, 17.83, 785.0693),
(39, 50, 26, 30.00, 1.0000),
(40, 51, 27, 25.00, 1.0000),
(41, 52, 28, 30.00, 1.0000),
(42, 53, 27, 30.00, 791.6667),
(43, 54, 15, 25.00, 0.0013),
(44, 55, 34, 25.00, 0.0013),
(45, 56, 15, 25.00, 1.0000),
(46, 57, 24, 5.00, 794.9917),
(47, 58, 17, 25.00, 794.9900),
(48, 59, 10, 7.15, 1.0000),
(49, 60, 21, 5.00, 794.9900),
(50, 61, 23, 5.00, 794.9900),
(51, 62, 22, 5.00, 794.9900),
(52, 63, 32, 5.00, 794.9900),
(53, 64, 33, 5.00, 0.8600);

--
-- Disparadores `detalles_pagos`
--
DROP TRIGGER IF EXISTS `trg_despues_insertar_detalle_pago`;
DELIMITER $$
CREATE TRIGGER `trg_despues_insertar_detalle_pago` AFTER INSERT ON `detalles_pagos` FOR EACH ROW BEGIN
    DECLARE v_monto_total DECIMAL(10,2);
    DECLARE v_total_abonado DECIMAL(10,2);

    
    SELECT monto_total INTO v_monto_total 
    FROM cargos 
    WHERE codigo_cargo = NEW.codigo_cargo;

    
    SELECT COALESCE(SUM(monto_abonado), 0) INTO v_total_abonado 
    FROM detalles_pagos 
    WHERE codigo_cargo = NEW.codigo_cargo;

    
    IF v_total_abonado >= v_monto_total THEN
        UPDATE cargos 
        SET estatus = 2 
        WHERE codigo_cargo = NEW.codigo_cargo;
    END IF;
END
$$
DELIMITER ;

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
(5, 10, 1, '2026-08-26', ''),
(6, 12, 3, '2026-08-26', ''),
(7, 13, 1, '2026-08-26', ''),
(8, 15, 1, '2026-08-26', ''),
(9, 14, 2, '2026-08-28', ''),
(11, 16, 2, '2026-08-29', '');

--
-- Disparadores `devoluciones`
--
DROP TRIGGER IF EXISTS `trg_despues_insertar_devolucion`;
DELIMITER $$
CREATE TRIGGER `trg_despues_insertar_devolucion` AFTER INSERT ON `devoluciones` FOR EACH ROW BEGIN
    UPDATE articulos_inventario 
    SET estatus = 1, id_estado = NEW.id_estado
    WHERE codigo_articulo = (SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = NEW.id_asignacion);
END
$$
DELIMITER ;

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
(1, 'Senior'),
(4, 'U-12');

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
(8, 'V', '32847654'),
(10, 'V', '29506933');

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
  `estatus` tinyint(4) NOT NULL DEFAULT 1,
  `talla_pantalon` varchar(10) DEFAULT NULL,
  `talla_franela` varchar(10) DEFAULT NULL,
  `talla_calzado` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`codigo_inscripcion`, `codigo_atleta`, `codigo_categoria`, `codigo_posicion`, `dorsal`, `peso_kg`, `estatura_cm`, `fecha_inscripcion`, `estatus`, `talla_pantalon`, `talla_franela`, `talla_calzado`) VALUES
(2, 2, 7, 1, 12, 90, 185, '2026-06-21', 2, NULL, NULL, NULL),
(3, 2, 7, 1, 12, 90, 185, '2026-06-21', 2, NULL, NULL, NULL),
(4, 2, 7, 1, 12, 90, 185, '2026-06-21', 2, NULL, NULL, NULL),
(5, 2, 7, 1, 12, 90, 185, '2026-06-21', 1, 'L', 'L', '42'),
(6, 3, 2, 1, 34, 60, 150, '2026-06-23', 2, NULL, NULL, NULL),
(7, 3, 2, 1, 34, 60, 150, '2026-06-27', 2, NULL, NULL, NULL),
(8, 3, 2, 1, 34, 60, 150, '2026-07-06', 2, NULL, NULL, NULL),
(9, 3, 2, 1, 34, 60, 150, '2026-07-07', 1, 'S', 'S', '25'),
(10, 7, 1, 1, 19, 60, 160, '2026-07-09', 2, NULL, NULL, NULL),
(11, 7, 1, 1, 19, 60, 160, '2026-07-09', 1, 'M', 'M', '26'),
(12, 8, 3, 1, 45, 50, 150, '2026-07-10', 1, 'S', 'M', '25'),
(13, 2, 7, 1, 12, 85, 185, '2026-08-25', 1, 'L', 'L', '42'),
(16, 10, 7, 1, 15, 100, 185, '2026-08-28', 2, 'L', 'L', '40');

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
(2, 'Transferencia', 1, 1),
(3, 'Pago Movil', 1, 1),
(5, 'Efectivo', 2, 1);

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
(2, 'Dolar', 'USD', '$', 1, 1),
(3, 'Euro', 'EUR', '€', 2, 1);

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
(44, 2, 2, 30.00, '2026-07-10', '2222', 1),
(46, 2, 2, 30.00, '2026-08-25', '2526', 1),
(48, 2, 2, 30.00, '2026-08-25', '2526', 1),
(49, 2, 1, 14000.00, '2026-08-25', '2526', 1),
(50, 2, 2, 32.00, '2026-08-25', '2524', 1),
(51, 2, 2, 25.00, '2026-08-25', '2526', 2),
(52, 2, 2, 30.00, '2026-08-25', '2524', 1),
(53, 2, 1, 25000.00, '2026-08-28', '252624', 1),
(54, 5, 2, 26.00, '2026-08-29', '', 2),
(55, 5, 2, 30.00, '2026-08-29', '', 2),
(56, 5, 2, 30.00, '2026-08-29', '', 1),
(57, 5, 1, 4000.00, '2026-08-29', '', 1),
(58, 5, 1, 20000.00, '2026-08-29', '', 1),
(59, 5, 2, 8.00, '2026-08-29', '', 1),
(60, 5, 1, 4000.00, '2026-08-29', '', 1),
(61, 5, 1, 4000.00, '2026-08-29', '', 1),
(62, 5, 1, 4000.00, '2026-08-29', '', 1),
(63, 5, 1, 4000.00, '2026-08-29', '', 1),
(64, 5, 3, 5.00, '2026-08-29', '', 1);

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

--
-- Disparadores `palmares_grupal`
--
DROP TRIGGER IF EXISTS `trg_antes_insertar_palmares_grupal`;
DELIMITER $$
CREATE TRIGGER `trg_antes_insertar_palmares_grupal` BEFORE INSERT ON `palmares_grupal` FOR EACH ROW BEGIN
    DECLARE v_estatus TINYINT;
    
    SELECT t.estatus INTO v_estatus
    FROM torneos t
    INNER JOIN participaciones p ON t.codigo_torneo = p.codigo_torneo
    WHERE p.codigo_participacion = NEW.codigo_participacion;
    
    IF v_estatus != 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: No se puede registrar palmar├®s en un torneo que no ha finalizado.';
    END IF;
END
$$
DELIMITER ;

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
(3, 7, 3);

--
-- Disparadores `palmares_individual`
--
DROP TRIGGER IF EXISTS `trg_antes_insertar_palmares_individual`;
DELIMITER $$
CREATE TRIGGER `trg_antes_insertar_palmares_individual` BEFORE INSERT ON `palmares_individual` FOR EACH ROW BEGIN
    DECLARE v_estatus TINYINT;
    
    SELECT t.estatus INTO v_estatus
    FROM torneos t
    INNER JOIN participaciones p ON t.codigo_torneo = p.codigo_torneo
    INNER JOIN detalles_participacion dp ON p.codigo_participacion = dp.codigo_participacion
    WHERE dp.codigo_dtll_prtc = NEW.codigo_dtll_prtc;
    
    IF v_estatus != 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: No se puede registrar palmar├®s en un torneo que no ha finalizado.';
    END IF;
END
$$
DELIMITER ;

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
(1, 1, 1),
(3, 5, 1),
(7, 5, 4);

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
(1, 'Delantero', 'DC', ''),
(2, 'Defensa', 'DF', '');

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
(2, 'G', 'Primer Lugar'),
(4, 'G', 'Segundo Lugar'),
(5, 'I', 'Maximo Goleador'),
(6, 'I', 'Maximo Asistidor'),
(7, 'I', 'Mvp'),
(8, 'I', 'Mejor Portero');

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
  `tipo_doc` enum('V','E','P') NOT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `representantes`
--

INSERT INTO `representantes` (`codigo_representante`, `cedula`, `telefono`, `direccion`, `nombre`, `apellido`, `tipo_doc`, `correo`, `instagram`) VALUES
(2, '13197214', '0232-1334423', 'El Tocuyo', 'Jessica', 'Aguilar', 'V', 'jessica@gmail.com', ''),
(6, '29506932', '0412-0565231', 'El Tocuyo, Calle 8 Carrera 14', 'Moises', 'Torrellas', 'V', 'moises@gmail.com', '');

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
(8, 10, '2026-07-09', 'Viaje'),
(10, 16, '2026-08-29', 'Falta de Pago');

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
(25, 1, '2026-08-29', '794.99', 'automatica'),
(26, 2, '2026-08-29', '1.00', 'automatica'),
(27, 3, '2026-08-29', '0.86', 'automatica'),
(28, 1, '2026-08-30', '794.99', 'automatica'),
(29, 3, '2026-08-30', '0.86', 'automatica');

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
(3, 'QUIBOR 2026', '2026-07-15', '2026-07-18', 'Quibor Estado Lara', 3),
(4, 'PETARE 2026', '2026-08-24', '2026-08-27', 'Barquisimeto', 3),
(5, 'SUPER TORNEO', '2026-08-31', '2026-09-05', 'Colombia, Barranquilla', 1);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_asignaciones_general`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_asignaciones_general`;
CREATE TABLE `vista_asignaciones_general` (
`id_asignacion` int(11)
,`fecha_vista` varchar(10)
,`fecha_real` date
,`estatus_asignacion` tinyint(4)
,`codigo_atleta` int(11)
,`atleta` varchar(101)
,`doc_identidad` varchar(257)
,`articulo` varchar(255)
,`codigo_club` varchar(20)
,`codigo_articulo` int(11)
);

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
,`lugar_nacimiento` varchar(255)
,`doc_identidad` varchar(255)
,`telefono` varchar(255)
,`direccion` varchar(255)
,`correo` varchar(255)
,`instagram` varchar(255)
,`municipio` varchar(255)
,`tipo_sangre` varchar(5)
,`es_alergico` tinyint(1)
,`alergias_detalle` text
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
,`talla_pantalon` varchar(10)
,`talla_franela` varchar(10)
,`talla_calzado` varchar(10)
,`estatus` int(4)
,`fecha_ingreso` date
,`fecha_reingreso` date
,`fecha_retiro` date
,`motivo_retiro` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_atletas_asignacion`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_atletas_asignacion`;
CREATE TABLE `vista_atletas_asignacion` (
`id_atleta` int(11)
,`doc_identidad` varchar(255)
,`nombres` varchar(50)
,`apellidos` varchar(50)
,`nombre_categoria` varchar(255)
,`nombre_posicion` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_atletas_equipo`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_atletas_equipo`;
CREATE TABLE `vista_atletas_equipo` (
`id_atleta` int(11)
,`id_equipo` int(11)
,`doc_identidad` varchar(255)
,`nombres` varchar(50)
,`apellidos` varchar(50)
,`nombre_categoria` varchar(255)
,`nombre_posicion` varchar(255)
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
,`documento_identidad` varchar(257)
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
,`fecha_cargo` date
,`nombre_atleta` varchar(50)
,`nombre_apellido` varchar(50)
,`simbolo_cuenta` varchar(255)
,`abre_cuenta` varchar(255)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_palmares_grupal`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_palmares_grupal`;
CREATE TABLE `vista_palmares_grupal` (
`codigo_grupal` int(11)
,`id_premio` int(11)
,`id_equipo` int(11)
,`nombre_equipo` varchar(255)
,`nombre_premio` varchar(255)
,`tipo_premio` enum('I','G')
,`id_torneo` int(11)
,`nombre_torneo` varchar(255)
,`fecha_torneo` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_palmares_individual`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_palmares_individual`;
CREATE TABLE `vista_palmares_individual` (
`id_individual` int(11)
,`id_premio` int(11)
,`id_atleta` int(11)
,`atleta_nombres` varchar(50)
,`atleta_apellidos` varchar(50)
,`atleta_foto` varchar(255)
,`nombre_premio` varchar(255)
,`tipo_premio` enum('I','G')
,`id_torneo` int(11)
,`nombre_torneo` varchar(255)
,`fecha_torneo` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_resumen_devoluciones`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_resumen_devoluciones`;
CREATE TABLE `vista_resumen_devoluciones` (
`id_devolucion` int(11)
,`fecha_vista` varchar(10)
,`fecha_devolucion` date
,`id_asignacion` int(11)
,`id_estado` int(11)
,`observacion` varchar(255)
,`estado_fisico` varchar(255)
,`nivel_estado` tinyint(4)
,`codigo_atleta` int(11)
,`atleta_nombre` varchar(50)
,`atleta_apellido` varchar(50)
,`doc_identidad` varchar(257)
,`articulo_nombre` varchar(255)
,`codigo_club` varchar(20)
,`total_devoluciones_atleta` bigint(21)
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
  `referencia` varchar(255) NOT NULL,
  `monto_base` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `vueltos`
--

INSERT INTO `vueltos` (`codigo_vuelto`, `codigo_metodo`, `codigo_pago`, `codigo_moneda`, `monto_vuelto`, `fecha_vuelto`, `referencia`, `monto_base`) VALUES
(4, 2, 38, 1, 0.03, '2026-07-09', '2323', 0.00),
(5, 2, 39, 1, 1419.39, '2026-07-09', '2323', 0.00),
(6, 2, 50, 1, 1570.14, '2026-08-25', '2526', 0.00),
(7, 2, 49, 1, 1735.00, '2026-08-25', '2526', 0.00),
(8, 2, 53, 1, 1250.00, '2026-08-29', '', 0.00),
(9, 5, 57, 1, 25.04, '2026-08-29', '', 0.00),
(10, 2, 58, 1, 125.25, '2026-08-29', '25264', 0.00),
(11, 5, 63, 2, 0.03, '2026-08-29', '', 23.85),
(12, 5, 62, 1, 25.05, '2026-08-29', '', 25.05),
(13, 5, 59, 1, 675.74, '2026-08-29', '', 0.85);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_asignaciones_general`
--
DROP TABLE IF EXISTS `vista_asignaciones_general`;

DROP VIEW IF EXISTS `vista_asignaciones_general`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_asignaciones_general`  AS SELECT `a`.`id_asignacion` AS `id_asignacion`, date_format(`a`.`fecha_asignacion`,'%d/%m/%Y') AS `fecha_vista`, `a`.`fecha_asignacion` AS `fecha_real`, `a`.`estatus` AS `estatus_asignacion`, `a`.`codigo_atleta` AS `codigo_atleta`, concat(`at`.`p_nombre`,' ',`at`.`p_apellidos`) AS `atleta`, CASE WHEN `ia`.`numero_doc` is not null AND `ia`.`numero_doc` <> '' THEN `ia`.`numero_doc` ELSE concat('R-',`r`.`cedula`) END AS `doc_identidad`, `c`.`nombre` AS `articulo`, `e`.`codigo_club` AS `codigo_club`, `a`.`codigo_articulo` AS `codigo_articulo` FROM ((((((`asignaciones` `a` join `atletas` `at` on(`a`.`codigo_atleta` = `at`.`codigo_atleta`)) left join `identidad_atleta` `ia` on(`at`.`codigo_atleta` = `ia`.`codigo_atleta`)) left join `atleta_representante` `ar` on(`at`.`codigo_atleta` = `ar`.`codigo_atleta`)) left join `representantes` `r` on(`ar`.`codigo_representante` = `r`.`codigo_representante`)) join `articulos_inventario` `e` on(`a`.`codigo_articulo` = `e`.`codigo_articulo`)) join `catalogo` `c` on(`e`.`id_catalogo` = `c`.`id_catalogo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_atletas`
--
DROP TABLE IF EXISTS `vista_atletas`;

DROP VIEW IF EXISTS `vista_atletas`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_atletas`  AS SELECT `a`.`codigo_atleta` AS `id_atleta`, concat(`a`.`p_nombre`,if(`a`.`s_nombre` is not null and `a`.`s_nombre` <> '',concat(' ',`a`.`s_nombre`),'')) AS `nombres`, concat(`a`.`p_apellidos`,if(`a`.`s_apellidos` is not null and `a`.`s_apellidos` <> '',concat(' ',`a`.`s_apellidos`),'')) AS `apellidos`, `a`.`p_nombre` AS `p_nombre`, `a`.`s_nombre` AS `s_nombre`, `a`.`p_apellidos` AS `p_apellidos`, `a`.`s_apellidos` AS `s_apellidos`, `a`.`genero` AS `genero`, `a`.`fecha_nac` AS `fecha_nac`, `a`.`foto` AS `foto`, `a`.`lugar_nacimiento` AS `lugar_nacimiento`, `ia`.`numero_doc` AS `doc_identidad`, `ca`.`telefono` AS `telefono`, `ca`.`direccion` AS `direccion`, `ca`.`correo` AS `correo`, `ca`.`instagram` AS `instagram`, `ca`.`municipio` AS `municipio`, `dm`.`tipo_sangre` AS `tipo_sangre`, `dm`.`es_alergico` AS `es_alergico`, `dm`.`alergias_detalle` AS `alergias_detalle`, `r`.`codigo_representante` AS `id_representante`, `r`.`nombre` AS `nombre_rep`, `r`.`apellido` AS `apellido_rep`, `r`.`cedula` AS `cedula_rep`, `r`.`telefono` AS `telefono_rep`, `r`.`direccion` AS `direccion_rep`, `i`.`codigo_categoria` AS `id_categoria`, `c`.`nombre` AS `nombre_categoria`, `c`.`edad_min` AS `edad_min`, `c`.`edad_max` AS `edad_max`, `i`.`codigo_posicion` AS `id_posicion`, `p`.`nombre` AS `nombre_posicion`, `i`.`dorsal` AS `dorsal`, `i`.`peso_kg` AS `peso_kg`, `i`.`estatura_cm` AS `estatura_cm`, `i`.`talla_pantalon` AS `talla_pantalon`, `i`.`talla_franela` AS `talla_franela`, `i`.`talla_calzado` AS `talla_calzado`, ifnull(`i`.`estatus`,1) AS `estatus`, `primer_ingreso`.`fecha_inscripcion` AS `fecha_ingreso`, CASE WHEN `primer_ingreso`.`codigo_inscripcion` <> `i`.`codigo_inscripcion` THEN `i`.`fecha_inscripcion` ELSE NULL END AS `fecha_reingreso`, `ret`.`fecha_retiro` AS `fecha_retiro`, `ret`.`motivo` AS `motivo_retiro` FROM (((((((((((((`atletas` `a` left join `identidad_atleta` `ia` on(`a`.`codigo_atleta` = `ia`.`codigo_atleta`)) left join `contacto_atleta` `ca` on(`a`.`codigo_atleta` = `ca`.`codigo_atleta`)) left join `datos_medicos` `dm` on(`a`.`codigo_atleta` = `dm`.`codigo_atleta`)) left join `atleta_representante` `ar` on(`a`.`codigo_atleta` = `ar`.`codigo_atleta`)) left join `representantes` `r` on(`ar`.`codigo_representante` = `r`.`codigo_representante`)) left join (select `inscripciones`.`codigo_atleta` AS `codigo_atleta`,max(`inscripciones`.`codigo_inscripcion`) AS `max_id` from `inscripciones` group by `inscripciones`.`codigo_atleta`) `max_i` on(`a`.`codigo_atleta` = `max_i`.`codigo_atleta`)) left join `inscripciones` `i` on(`max_i`.`max_id` = `i`.`codigo_inscripcion`)) left join `categorias` `c` on(`i`.`codigo_categoria` = `c`.`codigo_categoria`)) left join `posiciones` `p` on(`i`.`codigo_posicion` = `p`.`codigo_posicion`)) left join (select `inscripciones`.`codigo_atleta` AS `codigo_atleta`,min(`inscripciones`.`codigo_inscripcion`) AS `min_id` from `inscripciones` group by `inscripciones`.`codigo_atleta`) `min_i` on(`a`.`codigo_atleta` = `min_i`.`codigo_atleta`)) left join `inscripciones` `primer_ingreso` on(`min_i`.`min_id` = `primer_ingreso`.`codigo_inscripcion`)) left join (select `ins`.`codigo_atleta` AS `codigo_atleta`,max(`r`.`codigo_inscripcion`) AS `ultima_inscripcion_retirada` from (`retiros` `r` join `inscripciones` `ins` on(`r`.`codigo_inscripcion` = `ins`.`codigo_inscripcion`)) group by `ins`.`codigo_atleta`) `ultimo_retiro_id` on(`a`.`codigo_atleta` = `ultimo_retiro_id`.`codigo_atleta`)) left join `retiros` `ret` on(`ultimo_retiro_id`.`ultima_inscripcion_retirada` = `ret`.`codigo_inscripcion`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_atletas_asignacion`
--
DROP TABLE IF EXISTS `vista_atletas_asignacion`;

DROP VIEW IF EXISTS `vista_atletas_asignacion`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_atletas_asignacion`  AS SELECT `a`.`codigo_atleta` AS `id_atleta`, max(`ia`.`numero_doc`) AS `doc_identidad`, max(`a`.`p_nombre`) AS `nombres`, max(`a`.`p_apellidos`) AS `apellidos`, max(`c`.`nombre`) AS `nombre_categoria`, max(`p`.`nombre`) AS `nombre_posicion` FROM ((((`atletas` `a` left join `identidad_atleta` `ia` on(`a`.`codigo_atleta` = `ia`.`codigo_atleta`)) join `inscripciones` `i` on(`a`.`codigo_atleta` = `i`.`codigo_atleta`)) join `categorias` `c` on(`c`.`codigo_categoria` = `i`.`codigo_categoria`)) join `posiciones` `p` on(`p`.`codigo_posicion` = `i`.`codigo_posicion`)) WHERE `i`.`estatus` = 1 GROUP BY `a`.`codigo_atleta` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_atletas_equipo`
--
DROP TABLE IF EXISTS `vista_atletas_equipo`;

DROP VIEW IF EXISTS `vista_atletas_equipo`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_atletas_equipo`  AS SELECT `a`.`codigo_atleta` AS `id_atleta`, `de`.`codigo_equipo` AS `id_equipo`, max(`ia`.`numero_doc`) AS `doc_identidad`, max(`a`.`p_nombre`) AS `nombres`, max(`a`.`p_apellidos`) AS `apellidos`, max(`c`.`nombre`) AS `nombre_categoria`, max(`p`.`nombre`) AS `nombre_posicion` FROM (((((`detalles_equipos` `de` join `atletas` `a` on(`a`.`codigo_atleta` = `de`.`codigo_atleta`)) left join `identidad_atleta` `ia` on(`a`.`codigo_atleta` = `de`.`codigo_atleta`)) join `inscripciones` `i` on(`a`.`codigo_atleta` = `i`.`codigo_atleta`)) join `categorias` `c` on(`c`.`codigo_categoria` = `i`.`codigo_categoria`)) join `posiciones` `p` on(`p`.`codigo_posicion` = `i`.`codigo_posicion`)) WHERE `i`.`estatus` = 1 GROUP BY `a`.`codigo_atleta`, `de`.`codigo_equipo` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_cargos`
--
DROP TABLE IF EXISTS `vista_cargos`;

DROP VIEW IF EXISTS `vista_cargos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_cargos`  AS SELECT `c`.`codigo_cargo` AS `id_cobrar`, `c`.`codigo_atleta` AS `id_atleta`, `c`.`codigo_concepto` AS `id_concepto`, `c`.`fecha_emision` AS `fecha_emision`, `c`.`fecha_emision`+ interval `co`.`dias_gracia` day AS `fecha_vencimiento`, `c`.`monto_total` AS `monto_total`, `c`.`monto_total` AS `monto_personalizado`, greatest(`c`.`monto_total` - coalesce(`abonos`.`total_abonado`,0),0) AS `monto_pendiente`, `c`.`estatus` AS `estatus`, `c`.`multado` AS `multado`, CASE WHEN `c`.`estatus` = 3 THEN 'Anulado' WHEN `c`.`estatus` = 2 THEN 'Pagado' WHEN `c`.`estatus` = 1 THEN 'Pendiente' ELSE 'Abonado/Otro' END AS `estatus_texto`, `a`.`p_nombre` AS `atleta_nombre`, `a`.`p_apellidos` AS `atleta_apellido`, coalesce((select nullif(concat(`identidad_atleta`.`tipo_doc`,'-',`identidad_atleta`.`numero_doc`),'-') from `identidad_atleta` where `identidad_atleta`.`codigo_atleta` = `c`.`codigo_atleta` limit 1),(select nullif(concat(`r`.`tipo_doc`,'-',`r`.`cedula`),'-') from (`atleta_representante` `ar` join `representantes` `r` on(`ar`.`codigo_representante` = `r`.`codigo_representante`)) where `ar`.`codigo_atleta` = `c`.`codigo_atleta` limit 1),'No Aplica') AS `documento_identidad`, `co`.`nombre` AS `concepto_nombre`, `m`.`nombre` AS `moneda_nombre`, `m`.`simbolo` AS `moneda_simbolo`, `m`.`abreviatura` AS `moneda_abreviatura`, sum(case when `c`.`estatus` not in (2,3) then greatest(`c`.`monto_total` - coalesce(`abonos`.`total_abonado`,0),0) else 0 end) over ( partition by `c`.`codigo_atleta`,`m`.`codigo_moneda`) AS `deuda_moneda_atleta`, count(`c`.`codigo_cargo`) over ( partition by `c`.`codigo_atleta`) AS `total_facturas_atleta` FROM ((((`cargos` `c` join `atletas` `a` on(`c`.`codigo_atleta` = `a`.`codigo_atleta`)) join `conceptos` `co` on(`c`.`codigo_concepto` = `co`.`codigo_concepto`)) join `monedas` `m` on(`m`.`codigo_moneda` = `c`.`codigo_moneda`)) left join (select `dp`.`codigo_cargo` AS `codigo_cargo`,sum(`dp`.`monto_abonado`) AS `total_abonado` from (`detalles_pagos` `dp` join `pagos` `p` on(`dp`.`codigo_pago` = `p`.`codigo_pago` and `p`.`estatus` = 1)) group by `dp`.`codigo_cargo`) `abonos` on(`abonos`.`codigo_cargo` = `c`.`codigo_cargo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_pagos`
--
DROP TABLE IF EXISTS `vista_pagos`;

DROP VIEW IF EXISTS `vista_pagos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_pagos`  AS SELECT `p`.`codigo_pago` AS `id_pago`, `p`.`fecha` AS `fecha_pago`, `p`.`monto_pago` AS `monto_pagado`, ifnull((select sum(`v`.`monto_vuelto`) from `vueltos` `v` where `v`.`codigo_pago` = `p`.`codigo_pago`),0) AS `monto_vuelto`, `p`.`referencia` AS `referencia`, `p`.`estatus` AS `estatus`, `mp`.`nombre` AS `nombre_metodo_pago`, `m`.`simbolo` AS `simbolo`, `m`.`abreviatura` AS `abre`, `m`.`nombre` AS `moneda`, `dp`.`codigo_detalles_pagos` AS `id_detalle_pago`, `dp`.`monto_abonado` AS `monto_abonado`, `dp`.`tasa_cambio` AS `tasa_cambio`, `con`.`nombre` AS `concepto_pago`, `car`.`fecha_emision` AS `fecha_cargo`, `a`.`p_nombre` AS `nombre_atleta`, `a`.`p_apellidos` AS `nombre_apellido`, `mb`.`simbolo` AS `simbolo_cuenta`, `mb`.`abreviatura` AS `abre_cuenta` FROM (((((((`pagos` `p` left join `metodos_pago` `mp` on(`p`.`codigo_metodo` = `mp`.`codigo_metodo`)) left join `monedas` `m` on(`p`.`codigo_moneda` = `m`.`codigo_moneda`)) left join `detalles_pagos` `dp` on(`p`.`codigo_pago` = `dp`.`codigo_pago`)) left join `cargos` `car` on(`dp`.`codigo_cargo` = `car`.`codigo_cargo`)) left join `conceptos` `con` on(`car`.`codigo_concepto` = `con`.`codigo_concepto`)) left join `atletas` `a` on(`car`.`codigo_atleta` = `a`.`codigo_atleta`)) join (select `monedas`.`simbolo` AS `simbolo`,`monedas`.`abreviatura` AS `abreviatura` from `monedas` where `monedas`.`base` = 1 limit 1) `mb`) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_palmares_grupal`
--
DROP TABLE IF EXISTS `vista_palmares_grupal`;

DROP VIEW IF EXISTS `vista_palmares_grupal`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_palmares_grupal`  AS SELECT `pg`.`codigo_grupal` AS `codigo_grupal`, `pg`.`codigo_premio` AS `id_premio`, `part`.`codigo_equipo` AS `id_equipo`, `e`.`nombre` AS `nombre_equipo`, `p`.`nombre` AS `nombre_premio`, `p`.`tipo` AS `tipo_premio`, `part`.`codigo_torneo` AS `id_torneo`, `t`.`nombre` AS `nombre_torneo`, `t`.`fecha_inicio` AS `fecha_torneo` FROM ((((`palmares_grupal` `pg` join `premios` `p` on(`pg`.`codigo_premio` = `p`.`codigo_premio`)) join `participaciones` `part` on(`pg`.`codigo_participacion` = `part`.`codigo_participacion`)) join `equipos` `e` on(`part`.`codigo_equipo` = `e`.`codigo_equipo`)) join `torneos` `t` on(`part`.`codigo_torneo` = `t`.`codigo_torneo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_palmares_individual`
--
DROP TABLE IF EXISTS `vista_palmares_individual`;

DROP VIEW IF EXISTS `vista_palmares_individual`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_palmares_individual`  AS SELECT `pi`.`codigo_individual` AS `id_individual`, `pi`.`codigo_premio` AS `id_premio`, `dp`.`codigo_atleta` AS `id_atleta`, `a`.`p_nombre` AS `atleta_nombres`, `a`.`p_apellidos` AS `atleta_apellidos`, `a`.`foto` AS `atleta_foto`, `p`.`nombre` AS `nombre_premio`, `p`.`tipo` AS `tipo_premio`, `part`.`codigo_torneo` AS `id_torneo`, `t`.`nombre` AS `nombre_torneo`, `t`.`fecha_inicio` AS `fecha_torneo` FROM (((((`palmares_individual` `pi` join `premios` `p` on(`pi`.`codigo_premio` = `p`.`codigo_premio`)) join `detalles_participacion` `dp` on(`pi`.`codigo_dtll_prtc` = `dp`.`codigo_dtll_prtc`)) join `atletas` `a` on(`dp`.`codigo_atleta` = `a`.`codigo_atleta`)) join `participaciones` `part` on(`dp`.`codigo_participacion` = `part`.`codigo_participacion`)) join `torneos` `t` on(`part`.`codigo_torneo` = `t`.`codigo_torneo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_resumen_devoluciones`
--
DROP TABLE IF EXISTS `vista_resumen_devoluciones`;

DROP VIEW IF EXISTS `vista_resumen_devoluciones`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_resumen_devoluciones`  AS SELECT `d`.`id_devolucion` AS `id_devolucion`, date_format(`d`.`fecha_devolucion`,'%Y-%m-%d') AS `fecha_vista`, `d`.`fecha_devolucion` AS `fecha_devolucion`, `d`.`id_asignacion` AS `id_asignacion`, `d`.`id_estado` AS `id_estado`, `d`.`observacion` AS `observacion`, `ee`.`nombre` AS `estado_fisico`, `ee`.`nivel_estado` AS `nivel_estado`, `at`.`codigo_atleta` AS `codigo_atleta`, `at`.`p_nombre` AS `atleta_nombre`, `at`.`p_apellidos` AS `atleta_apellido`, CASE WHEN `ia`.`numero_doc` is not null AND `ia`.`numero_doc` <> '' THEN `ia`.`numero_doc` ELSE concat('R-',`r`.`cedula`) END AS `doc_identidad`, `cat`.`nombre` AS `articulo_nombre`, `eq`.`codigo_club` AS `codigo_club`, (select count(0) from (`devoluciones` `d2` join `asignaciones` `a2` on(`d2`.`id_asignacion` = `a2`.`id_asignacion`)) where `a2`.`codigo_atleta` = `at`.`codigo_atleta`) AS `total_devoluciones_atleta` FROM ((((((((`devoluciones` `d` join `asignaciones` `asig` on(`d`.`id_asignacion` = `asig`.`id_asignacion`)) join `atletas` `at` on(`asig`.`codigo_atleta` = `at`.`codigo_atleta`)) left join `identidad_atleta` `ia` on(`at`.`codigo_atleta` = `ia`.`codigo_atleta`)) left join `atleta_representante` `ar` on(`at`.`codigo_atleta` = `ar`.`codigo_atleta`)) left join `representantes` `r` on(`ar`.`codigo_representante` = `r`.`codigo_representante`)) join `estado_fisico` `ee` on(`d`.`id_estado` = `ee`.`id_estado`)) join `articulos_inventario` `eq` on(`asig`.`codigo_articulo` = `eq`.`codigo_articulo`)) join `catalogo` `cat` on(`eq`.`id_catalogo` = `cat`.`id_catalogo`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulos_inventario`
--
ALTER TABLE `articulos_inventario`
  ADD PRIMARY KEY (`codigo_articulo`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `id_catalogo` (`id_catalogo`),
  ADD KEY `idx_inventario_club` (`codigo_club`),
  ADD KEY `idx_inventario_estatus` (`estatus`,`id_estado`);

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
  ADD KEY `idx_atletas_nombre` (`p_nombre`,`p_apellidos`);

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
  ADD KEY `Id_categoria` (`Id_categoria`),
  ADD KEY `idx_catalogo_busqueda` (`nombre`,`talla`),
  ADD KEY `idx_catalogo_categoria` (`Id_categoria`);

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
  ADD PRIMARY KEY (`codigo_concepto`);

--
-- Indices de la tabla `contacto_atleta`
--
ALTER TABLE `contacto_atleta`
  ADD PRIMARY KEY (`codigo_atleta`);

--
-- Indices de la tabla `datos_medicos`
--
ALTER TABLE `datos_medicos`
  ADD KEY `fk_datos_medicos_atleta` (`codigo_atleta`);

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
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `indice_fecha_devolucion` (`fecha_devolucion`);

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
  ADD PRIMARY KEY (`codigo_moneda`),
  ADD KEY `indice_codigo_moneda` (`codigo_moneda`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`codigo_pago`),
  ADD KEY `codigo_metodo` (`codigo_metodo`),
  ADD KEY `codigo_moneda` (`codigo_moneda`),
  ADD KEY `idx_pagos_fecha` (`fecha`);

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
  ADD KEY `codigo_torneo` (`codigo_torneo`),
  ADD KEY `indice_torneo_participaciones` (`codigo_torneo`),
  ADD KEY `indice_equipo_participaciones` (`codigo_equipo`);

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
  MODIFY `codigo_articulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `atletas`
--
ALTER TABLE `atletas`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `atleta_representante`
--
ALTER TABLE `atleta_representante`
  MODIFY `codigo_at_re` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cargos`
--
ALTER TABLE `cargos`
  MODIFY `codigo_cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `catalogo`
--
ALTER TABLE `catalogo`
  MODIFY `id_catalogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `codigo_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `categoria_catalogo`
--
ALTER TABLE `categoria_catalogo`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `conceptos`
--
ALTER TABLE `conceptos`
  MODIFY `codigo_concepto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `contacto_atleta`
--
ALTER TABLE `contacto_atleta`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `detalles_equipos`
--
ALTER TABLE `detalles_equipos`
  MODIFY `codigo_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `detalles_pagos`
--
ALTER TABLE `detalles_pagos`
  MODIFY `codigo_detalles_pagos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `detalles_participacion`
--
ALTER TABLE `detalles_participacion`
  MODIFY `codigo_dtll_prtc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id_devolucion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `codigo_equipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estado_fisico`
--
ALTER TABLE `estado_fisico`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `identidad_atleta`
--
ALTER TABLE `identidad_atleta`
  MODIFY `codigo_atleta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `codigo_inscripcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `codigo_metodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `monedas`
--
ALTER TABLE `monedas`
  MODIFY `codigo_moneda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `codigo_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de la tabla `palmares_grupal`
--
ALTER TABLE `palmares_grupal`
  MODIFY `codigo_grupal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `palmares_individual`
--
ALTER TABLE `palmares_individual`
  MODIFY `codigo_individual` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `participaciones`
--
ALTER TABLE `participaciones`
  MODIFY `codigo_participacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `posiciones`
--
ALTER TABLE `posiciones`
  MODIFY `codigo_posicion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `premios`
--
ALTER TABLE `premios`
  MODIFY `codigo_premio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `representantes`
--
ALTER TABLE `representantes`
  MODIFY `codigo_representante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `retiros`
--
ALTER TABLE `retiros`
  MODIFY `codigo_retiro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `tasa_cambios`
--
ALTER TABLE `tasa_cambios`
  MODIFY `codigo_tasa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `codigo_torneo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `vueltos`
--
ALTER TABLE `vueltos`
  MODIFY `codigo_vuelto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- Filtros para la tabla `datos_medicos`
--
ALTER TABLE `datos_medicos`
  ADD CONSTRAINT `fk_datos_medicos_atleta` FOREIGN KEY (`codigo_atleta`) REFERENCES `atletas` (`codigo_atleta`) ON DELETE CASCADE;

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
