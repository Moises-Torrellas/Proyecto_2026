-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-09-2026 a las 00:35:48
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

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `pa_incluir_bitacora`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `pa_incluir_bitacora` (IN `p_modulo` INT, IN `p_acciones` VARCHAR(255), IN `p_previos` VARCHAR(255), IN `p_nuevos` VARCHAR(255), IN `p_entorno` VARCHAR(50), IN `p_usuario` INT, OUT `p_resultado` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Revierte en caso de falla
        ROLLBACK;
        SET p_resultado = 0;
    END;

    START TRANSACTION;
    
    -- Insertar el log de auditoría
    INSERT INTO bitacora 
        (id_modulo, acciones, datos_previos, datos_nuevos, entorno, idUsuario)
    VALUES 
        (p_modulo, p_acciones, p_previos, p_nuevos, p_entorno, p_usuario);
        
    -- Si fue exitoso, persistimos en la base de datos
    COMMIT;
    SET p_resultado = 1;
END$$

DROP PROCEDURE IF EXISTS `pa_incluir_usuario`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `pa_incluir_usuario` (IN `p_cedula` VARCHAR(10), IN `p_nombre` VARCHAR(35), IN `p_apellido` VARCHAR(35), IN `p_foto` VARCHAR(255), IN `p_telefono` VARCHAR(15), IN `p_contra` VARCHAR(255), IN `p_correo` VARCHAR(60), IN `p_rol` INT, OUT `p_resultado` INT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Si hay cualquier error SQL, se revierte la transacción
        ROLLBACK;
        SET p_resultado = 0;
    END;

    START TRANSACTION;
    
    -- Operación principal: Insertar el usuario
    INSERT INTO `usuarios`
        (`cedulaUsuario`, `nombreUsuario`, `apellidoUsuario`, `foto`, `telefonoUsuario`, `pass_hash`, `correo`, `id_rol`, `estatus`, `bloqueo`, `intentos_fallidos`) 
    VALUES 
        (p_cedula, p_nombre, p_apellido, p_foto, p_telefono, p_contra, p_correo, p_rol, 1, 1, 0);
    
    -- Si la inserción ocurre sin problemas, hacemos commit
    COMMIT;
    SET p_resultado = 1;
END$$

--
-- Funciones
--
DROP FUNCTION IF EXISTS `funcion_estado_cuenta_usuario`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `funcion_estado_cuenta_usuario` (`p_id_usuario` INT) RETURNS VARCHAR(20) CHARSET utf8 COLLATE utf8_spanish_ci DETERMINISTIC BEGIN
    DECLARE v_bloqueo TINYINT;
    DECLARE v_resultado VARCHAR(20);
    
    SELECT bloqueo INTO v_bloqueo 
    FROM usuarios 
    WHERE idUsuario = p_id_usuario 
    LIMIT 1;
    
    -- Lógica solicitada: 0 es Bloqueado, 1 es Desbloqueado. Sin inactivo.
    IF v_bloqueo = 0 THEN
        SET v_resultado = 'Bloqueado';
    ELSE
        SET v_resultado = 'Desbloqueado';
    END IF;
    
    RETURN v_resultado;
END$$

DROP FUNCTION IF EXISTS `funcion_obtener_nombre_rol`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `funcion_obtener_nombre_rol` (`p_id_rol` INT) RETURNS VARCHAR(35) CHARSET utf8 COLLATE utf8_spanish_ci DETERMINISTIC BEGIN
    DECLARE v_nombre VARCHAR(35);
    
    SELECT nombre_rol INTO v_nombre 
    FROM roles 
    WHERE id_rol = p_id_rol 
    LIMIT 1;
    
    RETURN IFNULL(v_nombre, 'Rol Desconocido');
END$$

DELIMITER ;

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
(1, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:06:29', 1),
(2, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:06:50', 1),
(3, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:07:07', 1),
(4, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:12:12', 1),
(5, 100, 'Registró al Atleta: 30258558 - PABLO  Perez', '', '{\"doc_identidad\":\"30258558\",\"nombre\":\"Pablo\",\"apellido\":\"Perez\",\"genero\":\"H\",\"fecha_nac\":\"2006-06-15\",\"telefono\":\"0415-5151554\",\"direccion\":\"Calle 60, Oeste De Barquisimeto\",\"representante\":\"\",\"categoria\":\"7\",\"posicion\":\"1\",\"dorsal\":\"25\",\"peso_kg\":\"65\",\"e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:14:50', 1),
(6, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:14:58', 1),
(7, 100, 'Generó documento (curriculum) del atleta: Pablo Perez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:15:35', 1),
(8, 100, 'Modificó al Atleta: 29506933 - Jose Lopez', '{\"nombres\":\"Jose\",\"apellidos\":\"Lopez\",\"p_nombre\":\"Jose\",\"s_nombre\":\"\",\"p_apellidos\":\"Lopez\",\"s_apellidos\":\"\",\"genero\":\"H\",\"fecha_nac\":\"2006-07-20\",\"foto\":\"atleta_2006-07-20_1787947857.jpg\",\"lugar_nacimiento\":\"El Tocuyo\",\"doc_identidad\":\"29506933\",\"telefon', '{\"doc_identidad\":\"29506933\",\"nombre\":\"Jose\",\"apellido\":\"Lopez\",\"genero\":\"H\",\"fecha_nac\":\"2006-07-20\",\"telefono\":\"0412-0565234\",\"direccion\":\"Calle 8\",\"representante\":\"\",\"categoria\":\"7\",\"posicion\":\"1\",\"dorsal\":\"15\",\"peso_kg\":\"100\",\"estatura_cm\":\"185\",\"foto\"', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:16:24', 1),
(9, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Pablo Perez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:16:46', 1),
(10, 100, 'Generó documento (ficha_tecnica) del atleta: Pablo Perez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:17:02', 1),
(11, 100, 'Generó documento (curriculum) del atleta: Jose Lopez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:17:15', 1),
(12, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Jose Lopez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:17:25', 1),
(13, 100, 'Generó documento (ficha_tecnica) del atleta: Jose Lopez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:17:33', 1),
(14, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Pablo Perez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:20:41', 1),
(15, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Pablo Perez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 10:26:32', 1),
(16, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:32:51', 1),
(17, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:33:11', 1),
(18, 100, 'Generó documento (ficha_tecnica) del atleta: Jose Jose Perez Perez', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:33:23', 1),
(19, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:34:20', 1),
(20, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:34:26', 1),
(21, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:34:37', 1),
(22, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:34:52', 1),
(23, 13, 'Registro de Pago por el monto de 32', '', '{\"monto\":32,\"fecha\":\"2026-09-11\",\"referencia\":\"No aplica\",\"tasa_usada\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:35:31', 1),
(24, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:35:45', 1),
(25, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:35:58', 1),
(26, 13, 'Registro de vuelto para el pago: 65', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:36:44', 1),
(27, 13, 'Registro de vuelto para el pago: 64', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:37:01', 1),
(28, 13, 'Registro de vuelto para el pago: 61', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:37:41', 1),
(29, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:37:50', 1),
(30, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:38:00', 1),
(31, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:38:01', 1),
(32, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:38:22', 1),
(33, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:38:24', 1),
(34, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:40:10', 1),
(35, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:40:57', 1),
(36, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:43:01', 1),
(37, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:43:39', 1),
(38, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 12:44:03', 1),
(39, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:41:14', 1),
(40, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:41:24', 1),
(41, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:42:21', 1),
(42, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:42:58', 1),
(43, 11, 'Modificó la categoría: U-6', '{\"nombre\":\"U-6\",\"edad_min\":5,\"edad_max\":6}', '{\"nombre\":\"U-6\",\"edad_minima\":\"5\",\"edad_maxima\":\"6\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:45:02', 1),
(44, 11, 'Modificó la categoría: U-8', '{\"nombre\":\"U-8\",\"edad_min\":7,\"edad_max\":8}', '{\"nombre\":\"U-8\",\"edad_minima\":\"7\",\"edad_maxima\":\"8\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:45:11', 1),
(45, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:45:16', 1),
(46, 11, 'Modificó la categoría: U-10', '{\"nombre\":\"U-10\",\"edad_min\":9,\"edad_max\":10}', '{\"nombre\":\"U-10\",\"edad_minima\":\"9\",\"edad_maxima\":\"10\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 19:45:29', 1),
(47, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:23:58', 1),
(48, 100, 'Generó reporte de atletas en formato PDF.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:24:42', 1),
(49, 100, 'Generó reporte de atletas en formato EXCEL.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:25:30', 1),
(50, 100, 'Generó documento (curriculum) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:26:46', 1),
(51, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:27:37', 1),
(52, 100, 'Generó documento (ficha_tecnica) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:30:08', 1),
(53, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:32:10', 1),
(54, 12, 'Anuló el Cargo: Multa Por Demora De Moises Torrellas y la fecha 2026-09-10', '{\"id_atleta\":2,\"id_concepto\":5,\"fecha_emision\":\"2026-09-10\",\"fecha_vencimiento\":\"2026-09-10\",\"monto_total\":\"5.00\",\"monto_personalizado\":\"5.00\",\"monto_pendiente\":\"5.00\",\"estatus\":1,\"multado\":0,\"estatus_texto\":\"Pendiente\",\"atleta_nombre\":\"Moises\",\"atleta_ap', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:32:38', 1),
(55, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-11 20:33:07', 1),
(56, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:22:10', 1),
(57, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:22:19', 1),
(58, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:22:21', 1),
(59, 100, 'Registró al Atleta: 34534534 - gdfgfdg dfgdfg', '', '{\"doc_identidad\":\"34534534\",\"nombre\":\"Gdfgfdg\",\"apellido\":\"Dfgdfg\",\"genero\":\"H\",\"fecha_nac\":\"2002-09-18\",\"telefono\":\"5464-5645645\",\"direccion\":\"Sdfsdfsdf\",\"representante\":\"\",\"categoria\":\"7\",\"posicion\":\"1\",\"dorsal\":\"34\",\"peso_kg\":\"80\",\"estatura_cm\":\"160\",\"', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:23:45', 1),
(60, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:24:05', 1),
(61, 11, 'Modificó la categoría: U-8', '{\"nombre\":\"U-8\",\"edad_min\":0,\"edad_max\":0}', '{\"nombre\":\"U-8\",\"edad_minima\":\"0\",\"edad_maxima\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:24:27', 1),
(62, 11, 'Modificó la categoría: SENIOR', '{\"nombre\":\"SENIOR\",\"edad_min\":18,\"edad_max\":50}', '{\"nombre\":\"SENIOR\",\"edad_minima\":\"18\",\"edad_maxima\":\"50\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:24:32', 1),
(63, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:36:44', 1),
(64, 11, 'Modificó la categoría: U-6', '{\"nombre\":\"U-6\",\"edad_min\":0,\"edad_max\":0}', '{\"nombre\":\"U-6\",\"edad_minima\":\"5\",\"edad_maxima\":\"6\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:37:06', 1),
(65, 11, 'Modificó la categoría: U-8', '{\"nombre\":\"U-8\",\"edad_min\":0,\"edad_max\":0}', '{\"nombre\":\"U-8\",\"edad_minima\":\"7\",\"edad_maxima\":\"8\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:37:23', 1),
(66, 11, 'Modificó la categoría: U-10', '{\"nombre\":\"U-10\",\"edad_min\":0,\"edad_max\":0}', '{\"nombre\":\"U-10\",\"edad_minima\":\"9\",\"edad_maxima\":\"10\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:37:36', 1),
(67, 11, 'Modificó la categoría: SENIOR', '{\"nombre\":\"SENIOR\",\"edad_min\":0,\"edad_max\":0}', '{\"nombre\":\"SENIOR\",\"edad_minima\":\"18\",\"edad_maxima\":\"50\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:37:54', 1),
(68, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:37:59', 1),
(69, 100, 'Modificó al Atleta:  - Maria Jose Perez Perez', '{\"nombres\":\"Maria Jose\",\"apellidos\":\"Perez Perez\",\"p_nombre\":\"Maria\",\"s_nombre\":\"Jose\",\"p_apellidos\":\"Perez\",\"s_apellidos\":\"Perez\",\"genero\":\"M\",\"fecha_nac\":\"2019-02-22\",\"foto\":\"atleta_2019-02-22_1783802489.jpg\",\"lugar_nacimiento\":\"Barquisimeto\",\"doc_ident', '{\"doc_identidad\":\"\",\"nombre\":\"Maria Jose\",\"apellido\":\"Perez Perez\",\"genero\":\"M\",\"fecha_nac\":\"2019-02-22\",\"telefono\":\"\",\"direccion\":\"\",\"representante\":\"2\",\"categoria\":\"2\",\"posicion\":\"1\",\"dorsal\":\"34\",\"peso_kg\":\"60\",\"estatura_cm\":\"150\",\"foto\":\"atleta_2019-0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:38:17', 1),
(70, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:38:27', 1),
(71, 11, 'Registró la categoría: U-12', '', '{\"nombre\":\"U-12\",\"edad_minima\":\"11\",\"edad_maxima\":\"12\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:38:41', 1),
(72, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:38:51', 1),
(73, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:38:52', 1),
(74, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:40:12', 1),
(75, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:41:01', 1),
(76, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:41:13', 1),
(77, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:41:15', 1),
(78, 2, 'Registró el Rol: Entrenador', '', '{\"nombre\":\"Entrenador\",\"descripcion\":\"El que entrena\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:41:33', 1),
(79, 2, 'Modificó permisos al rol: Entrenador', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:41:55', 1),
(80, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:42:03', 1),
(81, 1, 'Registro al usuario: 29506932 - Moises Torrellas', 'No Aplica', 'Cédula: 29506932, Nombre: Moises Torrellas, Correo: moitcj@gmail.com, Teléfono: 0412-0565231, Rol: Entrenador', 'Base de Datos', '2026-09-14 17:42:57', 1),
(82, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:43:08', 1),
(83, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:43:19', 12),
(84, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:43:29', 12),
(85, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-14 17:44:34', 12),
(86, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:27:46', 1),
(87, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:31:22', 1),
(88, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:33:05', 1),
(89, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:33:52', 1),
(90, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:34:29', 1),
(91, 11, 'Modificó la categoría: U-8', '{\"nombre\":\"U-8\",\"edad_min\":7,\"edad_max\":8}', '{\"nombre\":\"U-8\",\"edad_minima\":\"7\",\"edad_maxima\":\"8\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:35:04', 1),
(92, 11, 'Modificó la categoría: SENIOR', '{\"nombre\":\"SENIOR\",\"edad_min\":18,\"edad_max\":50}', '{\"nombre\":\"SENIOR\",\"edad_minima\":\"18\",\"edad_maxima\":\"30\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:35:16', 1),
(93, 11, 'Registró la categoría: senior 2', '', '{\"nombre\":\"senior 2\",\"edad_minima\":\"31\",\"edad_maxima\":\"50\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:35:36', 1),
(94, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:35:46', 1),
(95, 100, 'Registró al Atleta: 29531465 - Yonathan Joseph Mogollon Duran', '', '{\"doc_identidad\":\"29531465\",\"nombre\":\"Yonathan Joseph\",\"apellido\":\"Mogollon Duran\",\"genero\":\"H\",\"fecha_nac\":\"2002-05-17\",\"telefono\":\"0412-3652677\",\"direccion\":\"La Sabilas, Manzana P23, Casa 10\",\"representante\":\"\",\"categoria\":\"7\",\"posicion\":\"1\",\"dorsal\":\"9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:44:00', 1),
(96, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:45:45', 1),
(97, 9, 'Registró al representante: 43543545 Leonardo Medina', '', '{\"cedula\":\"43543545\",\"nacionalidad\":\"V\",\"nombre\":\"Leonardo\",\"apellido\":\"Medina\",\"telefono\":\"0504-5645684\",\"direccion\":\"Barquisimeto, Calle 58\",\"correo\":\"leonardo@gmail.com\",\"instagram\":\"\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:47:56', 1),
(98, 9, 'Modificó al representante: 43543545 - Leonardo Medina', '{\"cedula\":\"43543545\",\"telefono\":\"0504-5645684\",\"direccion\":\"Barquisimeto, Calle 58\",\"nombre\":\"Leonardo\",\"apellido\":\"Medina\",\"tipo_doc\":\"V\",\"correo\":\"leonardo@gmail.com\",\"instagram\":\"\"}', '{\"cedula\":\"43543545\",\"nacionalidad\":\"V\",\"nombre\":\"Leonardo\",\"apellido\":\"Medina\",\"telefono\":\"0504-5645684\",\"direccion\":\"Barquisimeto, Calle 58\",\"correo\":\"leonardo@gmail.com\",\"instagram\":\"\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:48:05', 1),
(99, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:48:08', 1),
(100, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:48:18', 1),
(101, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:48:38', 1),
(102, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:48:59', 1),
(103, 102, 'Selecciono la moneda: 1', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:49:10', 1),
(104, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:49:14', 1),
(105, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:49:28', 1),
(106, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:49:47', 1),
(107, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:49:50', 1),
(108, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:50:10', 1),
(109, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:50:56', 1),
(110, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:50:58', 1),
(111, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:52:10', 1),
(112, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:52:37', 1),
(113, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:54:21', 1),
(114, 17, 'Registró asignación del artículo ID: 6 al atleta ID: 2', '', '{\"codigo_atleta\":\"2\",\"codigo_articulo\":\"6\",\"fecha_asignacion\":\"2026-09-15\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:55:04', 1),
(115, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:55:25', 1),
(116, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:56:08', 1),
(117, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:56:22', 1),
(118, 17, 'Modificó asignación ID: 18', '{\"id_asignacion\":18,\"codigo_atleta\":2,\"codigo_articulo\":6,\"fecha_asignacion\":\"2026-09-15\",\"estatus\":1}', '{\"id_asignacion\":\"18\",\"codigo_atleta\":\"2\",\"codigo_articulo\":\"6\",\"fecha_asignacion\":\"2026-09-15\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:57:41', 1),
(119, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:57:48', 1),
(120, 18, 'Registró devolución de: Casco Tiplex - Atleta: Moises Torrellas (CI: 29506932)', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:58:08', 1),
(121, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:58:24', 1),
(122, 15, 'Reincorporó artículo Código: 6', '{\"codigo_articulo\":6,\"id_estado\":2,\"id_catalogo\":1,\"codigo_club\":\"CL-0003\",\"estatus\":3}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:58:34', 1),
(123, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:58:43', 1),
(124, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:58:53', 1),
(125, 15, 'Modificó artículo Código: 6', '{\"codigo_articulo\":6,\"id_estado\":2,\"id_catalogo\":1,\"codigo_club\":\"CL-0003\",\"estatus\":3}', '{\"codigo_articulo\":\"6\",\"id_catalogo\":\"1\",\"id_estado\":\"1\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:58:58', 1),
(126, 15, 'Reincorporó artículo Código: 6', '{\"codigo_articulo\":6,\"id_estado\":1,\"id_catalogo\":1,\"codigo_club\":\"CL-0003\",\"estatus\":3}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 12:59:05', 1),
(127, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:00:00', 1),
(128, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:00:25', 1),
(129, 17, 'Registró asignación del artículo ID: 6 al atleta ID: 2', '', '{\"codigo_atleta\":\"2\",\"codigo_articulo\":\"6\",\"fecha_asignacion\":\"2026-09-15\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:00:36', 1),
(130, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:00:44', 1),
(131, 18, 'Registró devolución de: Casco Tiplex - Atleta: Moises Torrellas (CI: 29506932)', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:01:00', 1),
(132, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:01:13', 1),
(133, 104, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:01:21', 1),
(134, 104, 'Registró el estado físico: Regular', '', '{\"nombre\":\"Regular\",\"nivel_estado\":\"2\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:01:35', 1),
(135, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:01:39', 1),
(136, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:01:45', 1),
(137, 16, 'Registró un artículo en catálogo: Proteccion Pectoral', '', '{\"nombre\":\"Proteccion Pectoral\",\"stock_minimo\":\"1\",\"id_categoria\":\"2\",\"talla\":\"10\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:02:08', 1),
(138, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:02:12', 1),
(139, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"3\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0004\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:02:25', 1),
(140, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:03:10', 1),
(141, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:03:36', 1),
(142, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:04:03', 1),
(143, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:04:15', 1),
(144, 20, 'Modificó al equipo: Senior', '{\"nombre\":\"Senior\"}', '{\"nombre\":\"Senior\",\"atletas\":[\"2\",\"13\",\"10\",\"12\"]}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:04:44', 1),
(145, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:04:50', 1),
(146, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:05:07', 1),
(147, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:05:12', 1),
(148, 23, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:05:25', 1),
(149, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:06:29', 1),
(150, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:15:48', 1),
(151, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:16:38', 1),
(152, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 13:16:45', 1),
(153, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:27:26', 1),
(154, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:27:45', 1),
(155, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:27:48', 1),
(156, 10, 'Registró la posición: Delantero', '', '{\"nombre\":\"Delantero\",\"abreviatura\":\"DC\",\"descripcion\":\"\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:28:10', 1),
(157, 10, 'Registró la posición: DEFENSA', '', '{\"nombre\":\"DEFENSA\",\"abreviatura\":\"DF\",\"descripcion\":\"\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:28:28', 1),
(158, 10, 'Registró la posición: PORTERO', '', '{\"nombre\":\"PORTERO\",\"abreviatura\":\"PR\",\"descripcion\":\"\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:28:41', 1),
(159, 10, 'Registró la posición: MEDIO CAMPISTA', '', '{\"nombre\":\"MEDIO CAMPISTA\",\"abreviatura\":\"MC\",\"descripcion\":\"\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:28:59', 1),
(160, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:29:06', 1),
(161, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:30:28', 1),
(162, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:30:32', 1),
(163, 14, 'Registro el método de pago: TRANSFERENCIA', '', '{\"nombre\":\"TRANSFERENCIA\",\"nec_referencia\":\"1\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:30:48', 1),
(164, 14, 'Registro el método de pago: PAGO MOVIL', '', '{\"nombre\":\"PAGO MOVIL\",\"nec_referencia\":\"1\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:31:31', 1),
(165, 14, 'Registro el método de pago: EFECTIVO', '', '{\"nombre\":\"EFECTIVO\",\"nec_referencia\":\"1\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:31:44', 1),
(166, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:32:00', 1),
(167, 101, 'Registró el Concepto de cargo: Mensualidad 30.00', '', '{\"nombre\":\"Mensualidad\",\"monto\":\"30.00\",\"frecuencia\":\"M\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:32:22', 1),
(168, 101, 'Registró el Concepto de cargo: Inscripción 25.00', '', '{\"nombre\":\"Inscripci\\u00f3n\",\"monto\":\"25.00\",\"frecuencia\":\"A\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:33:02', 1),
(169, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:33:19', 1),
(170, 102, 'Registró la moneda: Bolívar', '', '{\"nombre\":\"Bol\\u00edvar\",\"abreviatura\":\"VES\",\"simbolo\":\"Bs\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:33:26', 1),
(171, 102, 'Registró la moneda: Dólar', '', '{\"nombre\":\"D\\u00f3lar\",\"abreviatura\":\"USD\",\"simbolo\":\"$\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:33:30', 1),
(172, 102, 'Selecciono la moneda: 2', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:33:45', 1),
(173, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:33:48', 1),
(174, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:33:53', 1),
(175, 110, 'Sincronizó tasa de cambio para la moneda: Bolívar (Bs)', '', '{\"moneda\":\"Bol\\u00edvar (Bs)\",\"tasa\":\"842.20\",\"fecha\":\"2026-09-15\",\"tipo\":\"automatica\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:37:46', 1),
(176, 110, 'Sincronizó tasa de cambio para la moneda: Dólar ($)', '', '{\"moneda\":\"D\\u00f3lar ($)\",\"tasa\":\"1.00\",\"fecha\":\"2026-09-15\",\"tipo\":\"automatica\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:37:54', 1),
(177, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:38:13', 1),
(178, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:38:24', 1),
(179, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:38:33', 1),
(180, 21, 'Registró el Premio: Primer Lugar (G)', '', '[]', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:38:49', 1),
(181, 21, 'Registró el Premio: Segundo Lugar (G)', '', '[]', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:39:06', 1),
(182, 21, 'Registró el Premio: MVP (I)', '', '[]', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:39:21', 1),
(183, 21, 'Registró el Premio: Maximo goleador (I)', '', '[]', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:39:42', 1),
(184, 21, 'Registró el Premio: Maximo Asistedor (I)', '', '[]', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:39:59', 1),
(185, 21, 'Registró el Premio: Mejor Portero (I)', '', '[]', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:40:29', 1),
(186, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:40:40', 1),
(187, 11, 'Registró la categoría: Inicial', '', '{\"nombre\":\"Inicial\",\"edad_minima\":\"4\",\"edad_maxima\":\"4\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:41:37', 1),
(188, 11, 'Modificó la categoría: INICIAL', '{\"nombre\":\"INICIAL\",\"edad_min\":4,\"edad_max\":4}', '{\"nombre\":\"INICIAL\",\"edad_minima\":\"3\",\"edad_maxima\":\"4\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:41:48', 1),
(189, 11, 'Registró la categoría: u-6', '', '{\"nombre\":\"u-6\",\"edad_minima\":\"5\",\"edad_maxima\":\"6\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:42:10', 1),
(190, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:43:03', 1),
(191, 11, 'Registró la categoría: u-8', '', '{\"nombre\":\"u-8\",\"edad_minima\":\"7\",\"edad_maxima\":\"8\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:43:22', 1),
(192, 11, 'Registró la categoría: u-10', '', '{\"nombre\":\"u-10\",\"edad_minima\":\"9\",\"edad_maxima\":\"10\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:43:47', 1),
(193, 11, 'Registró la categoría: u-12', '', '{\"nombre\":\"u-12\",\"edad_minima\":\"11\",\"edad_maxima\":\"12\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:44:04', 1),
(194, 11, 'Registró la categoría: u-14', '', '{\"nombre\":\"u-14\",\"edad_minima\":\"13\",\"edad_maxima\":\"14\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:44:20', 1),
(195, 11, 'Registró la categoría: u-17', '', '{\"nombre\":\"u-17\",\"edad_minima\":\"15\",\"edad_maxima\":\"17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:44:34', 1),
(196, 11, 'Registró la categoría: senior', '', '{\"nombre\":\"senior\",\"edad_minima\":\"18\",\"edad_maxima\":\"50\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:44:48', 1),
(197, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:45:10', 1),
(198, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:48:28', 1),
(199, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:49:01', 1),
(200, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:49:03', 1),
(201, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 15:50:18', 1),
(202, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 16:50:35', 1),
(203, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 16:54:13', 1),
(204, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 16:54:43', 1),
(205, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 16:55:12', 1),
(206, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 16:55:46', 1),
(207, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 16:59:19', 1),
(208, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 16:59:56', 1),
(209, 9, 'Registró al representante: 12944555 Yasmelbi chirinos', '', '{\"cedula\":\"12944555\",\"nacionalidad\":\"V\",\"nombre\":\"Yasmelbi\",\"apellido\":\"Chirinos\",\"telefono\":\"0416-5533382\",\"direccion\":\"Calle 60 Entre Carrera 14A Y 14B Edificio Pozo Blanco Apartamento 8A\",\"correo\":\"yasmelbi2011@hotmail.com\",\"instagram\":\"@marchirojs\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:03:16', 1),
(210, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:07:10', 1),
(211, 100, 'Registró al Atleta: 34772516 - Diego Alexandro reinoso chirinos', '', '{\"doc_identidad\":\"34772516\",\"nombre\":\"Diego Alexandro\",\"apellido\":\"Reinoso Chirinos\",\"genero\":\"H\",\"fecha_nac\":\"2011-12-07\",\"telefono\":\"\",\"direccion\":\"\",\"representante\":\"1\",\"categoria\":\"7\",\"posicion\":\"3\",\"dorsal\":\"10\",\"peso_kg\":\"70\",\"estatura_cm\":\"170\",\"fo', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:14:20', 1),
(212, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:16:17', 1),
(213, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:24:13', 1),
(214, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:25:39', 1),
(215, 19, 'Registró el torneo: Valencia 2022', '', '{\"nombre\":\"Valencia 2022\",\"fecha_inicio\":\"2022-12-19\",\"fecha_fin\":\"2022-12-21\",\"ubicacion\":\"Estado Valencia\",\"estatus\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:27:32', 1),
(216, 19, 'Registró el torneo: valencia 2023 marzo', '', '{\"nombre\":\"valencia 2023 marzo\",\"fecha_inicio\":\"2023-03-17\",\"fecha_fin\":\"2023-03-19\",\"ubicacion\":\"Estado Valencia\",\"estatus\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:30:00', 1),
(217, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:30:25', 1),
(218, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:30:28', 1),
(219, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:31:53', 1),
(220, 19, 'Registró el torneo: valencia 2023 junio', '', '{\"nombre\":\"valencia 2023 junio\",\"fecha_inicio\":\"2023-06-23\",\"fecha_fin\":\"2023-06-25\",\"ubicacion\":\"estado valencia\",\"estatus\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:34:12', 1),
(221, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:34:28', 1),
(222, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:34:36', 1),
(223, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:36:27', 1),
(224, 19, 'Registró el torneo: valencia 2024 Abril', '', '{\"nombre\":\"valencia 2024 Abril\",\"fecha_inicio\":\"2024-04-02\",\"fecha_fin\":\"2024-04-04\",\"ubicacion\":\"Estado Valencia\",\"estatus\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:51:25', 1),
(225, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:51:40', 1),
(226, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:53:07', 1),
(227, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:53:13', 1),
(228, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:53:28', 1),
(229, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:54:33', 1),
(230, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:54:58', 1),
(231, 19, 'Registró el torneo: valencia 2024 noviembre', '', '{\"nombre\":\"valencia 2024 noviembre\",\"fecha_inicio\":\"2024-11-02\",\"fecha_fin\":\"2024-11-04\",\"ubicacion\":\"estado valencia\",\"estatus\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:56:21', 1),
(232, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:57:27', 1),
(233, 19, 'Registró el torneo: Puerto Ordaz 2025', '', '{\"nombre\":\"Puerto Ordaz 2025\",\"fecha_inicio\":\"2025-02-15\",\"fecha_fin\":\"2025-02-17\",\"ubicacion\":\"Estado Bolivar\",\"estatus\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:59:17', 1),
(234, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:59:37', 1),
(235, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:59:39', 1),
(236, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 17:59:42', 1),
(237, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:00:48', 1),
(238, 19, 'Registró el torneo: valencia 2026 febrero', '', '{\"nombre\":\"valencia 2026 febrero\",\"fecha_inicio\":\"2026-02-20\",\"fecha_fin\":\"2026-02-22\",\"ubicacion\":\"Estado Valencia\",\"estatus\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:02:28', 1),
(239, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:02:45', 1),
(240, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:03:01', 1),
(241, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:03:02', 1),
(242, 20, 'Registró al Equipo: U 17', '', '{\"nombre\":\"U 17\",\"atletas\":[\"1\"]}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:03:24', 1),
(243, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:03:30', 1),
(244, 105, 'Registro una participacion', '', '{\"torneo\":\"VALENCIA 2022\",\"equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:04:00', 1),
(245, 105, 'Registro una participacion', '', '{\"torneo\":\"VALENCIA 2023 MARZO\",\"equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:04:11', 1),
(246, 105, 'Registro una participacion', '', '{\"torneo\":\"VALENCIA 2023 JUNIO\",\"equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:04:21', 1),
(247, 105, 'Registro una participacion', '', '{\"torneo\":\"VALENCIA 2024 ABRIL\",\"equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:04:33', 1),
(248, 105, 'Registro una participacion', '', '{\"torneo\":\"VALENCIA 2024 NOVIEMBRE\",\"equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:05:53', 1),
(249, 105, 'Registro una participacion', '', '{\"torneo\":\"PUERTO ORDAZ 2025\",\"equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:06:06', 1),
(250, 105, 'Registro una participacion', '', '{\"torneo\":\"VALENCIA 2026 FEBRERO\",\"equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:06:15', 1),
(251, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:06:31', 1),
(252, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:09:46', 1),
(253, 22, 'Registró un palmarés individual para: Diego Reinoso (34772516)', '', '{\"id_premio\":6,\"id_atleta\":1,\"id_torneo\":1,\"nombres\":\"Diego\",\"apellidos\":\"Reinoso\",\"cedula\":\"34772516\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:12:03', 1),
(254, 22, 'Registró un palmarés grupal para: U 17', '', '{\"id_premio\":1,\"id_equipo\":1,\"id_torneo\":1,\"nombre_equipo\":\"U 17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:12:48', 1),
(255, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:15:03', 1),
(256, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:15:09', 1),
(257, 20, 'Modificó al equipo: Cannibals Lara u17', '{\"nombre\":\"U 17\"}', '{\"nombre\":\"Cannibals Lara u17\",\"atletas\":[\"1\"]}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:15:33', 1),
(258, 20, 'Registró al Equipo: Coyotes de carabobo', '', '{\"nombre\":\"Coyotes de carabobo\",\"atletas\":[\"1\"]}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:15:58', 1),
(259, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:16:07', 1),
(260, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:16:11', 1),
(261, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:16:55', 1),
(262, 105, 'Registro una participacion', '', '{\"torneo\":\"VALENCIA 2023 MARZO\",\"equipo\":\"Coyotes De Carabobo\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:17:14', 1),
(263, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:17:27', 1),
(264, 22, 'Registró un palmarés grupal para: Coyotes De Carabobo', '', '{\"id_premio\":2,\"id_equipo\":2,\"id_torneo\":2,\"nombre_equipo\":\"Coyotes De Carabobo\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:17:43', 1),
(265, 22, 'Registró un palmarés grupal para: Cannibals Lara U17', '', '{\"id_premio\":1,\"id_equipo\":1,\"id_torneo\":2,\"nombre_equipo\":\"Cannibals Lara U17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:22:28', 1),
(266, 22, 'Registró un palmarés grupal para: Cannibals Lara U17', '', '{\"id_premio\":2,\"id_equipo\":1,\"id_torneo\":3,\"nombre_equipo\":\"Cannibals Lara U17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:23:14', 1),
(267, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:23:56', 1),
(268, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:23:59', 1),
(269, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:26:09', 1),
(270, 21, 'Modificó el Premio: Campeón (G)', '{\"tipo\":\"G\",\"nombre\":\"Primer Lugar\"}', '{\"tipo\":\"G\",\"nombre\":\"Campe\\u00f3n\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:26:43', 1),
(271, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:26:58', 1),
(272, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:28:10', 1),
(273, 23, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:28:44', 1),
(274, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:28:46', 1),
(275, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:29:18', 1),
(276, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Diego Alexandro Reinoso Chirinos', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:29:26', 1),
(277, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:32:36', 1),
(278, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:34:58', 1),
(279, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:35:13', 1),
(280, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:35:16', 1),
(281, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:35:18', 1),
(282, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:35:25', 1),
(283, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:35:35', 1),
(284, 22, 'Registró un palmarés individual para: Diego Reinoso (34772516)', '', '{\"id_premio\":3,\"id_atleta\":1,\"id_torneo\":1,\"nombres\":\"Diego\",\"apellidos\":\"Reinoso\",\"cedula\":\"34772516\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:36:03', 1),
(285, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:38:58', 1),
(286, 21, 'Modificó el Premio: 2do Lugar (G)', '{\"tipo\":\"G\",\"nombre\":\"Segundo Lugar\"}', '{\"tipo\":\"G\",\"nombre\":\"2Do Lugar\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:39:08', 1),
(287, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:39:13', 1),
(288, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:43:37', 1),
(289, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:46:14', 1),
(290, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Diego Alexandro Reinoso Chirinos', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:46:28', 1),
(291, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:50:17', 1),
(292, 100, 'Modificó al Atleta: 34772516 - Diego Alexandro Reinoso Chirinos', '{\"nombres\":\"Diego Alexandro\",\"apellidos\":\"Reinoso Chirinos\",\"p_nombre\":\"Diego\",\"s_nombre\":\"Alexandro\",\"p_apellidos\":\"Reinoso\",\"s_apellidos\":\"Chirinos\",\"genero\":\"H\",\"fecha_nac\":\"2011-12-07\",\"foto\":\"default.png\",\"lugar_nacimiento\":\"Barquisimeto\",\"doc_identi', '{\"doc_identidad\":\"34772516\",\"nombre\":\"Diego Alexandro\",\"apellido\":\"Reinoso Chirinos\",\"genero\":\"H\",\"fecha_nac\":\"2011-12-07\",\"telefono\":\"\",\"direccion\":\"\",\"representante\":\"1\",\"categoria\":\"7\",\"posicion\":\"3\",\"dorsal\":\"10\",\"peso_kg\":\"70\",\"estatura_cm\":\"170\",\"fo', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:50:39', 1),
(293, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:51:20', 1),
(294, 103, 'Registró la categoría: proteccion', '', '{\"nombre\":\"Proteccion\",\"descripcion\":\"protecciones corporales\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:51:46', 1),
(295, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:51:56', 1),
(296, 16, 'Registró un artículo en catálogo: Cascos con rejilla ccs', '', '{\"nombre\":\"Cascos Con Rejilla Ccs\",\"stock_minimo\":\"1\",\"id_categoria\":\"1\",\"talla\":\"12\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 18:54:40', 1),
(297, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:01:53', 1),
(298, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:01:56', 1),
(299, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:02:01', 1),
(300, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:02:26', 1),
(301, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:02:37', 1);
INSERT INTO `bitacora` (`id_bitacora`, `id_modulo`, `acciones`, `datos_previos`, `datos_nuevos`, `entorno`, `fecha_hora`, `idUsuario`) VALUES
(302, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:03:47', 1),
(303, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:03:49', 1),
(304, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:03:57', 1),
(305, 100, 'Registró al Atleta: 36552840 - Andrea valentina chirinos torres', '', '{\"doc_identidad\":\"36552840\",\"nombre\":\"Andrea Valentina\",\"apellido\":\"Chirinos Torres\",\"genero\":\"M\",\"fecha_nac\":\"2015-01-05\",\"telefono\":\"\",\"direccion\":\"\",\"representante\":\"1\",\"categoria\":\"5\",\"posicion\":\"2\",\"dorsal\":\"16\",\"peso_kg\":\"50\",\"estatura_cm\":\"145\",\"fo', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:10:22', 1),
(306, 100, 'Modificó al Atleta: 34772516 - Diego Alexandro Reinoso Chirinos', '{\"nombres\":\"Diego Alexandro\",\"apellidos\":\"Reinoso Chirinos\",\"p_nombre\":\"Diego\",\"s_nombre\":\"Alexandro\",\"p_apellidos\":\"Reinoso\",\"s_apellidos\":\"Chirinos\",\"genero\":\"H\",\"fecha_nac\":\"2011-12-07\",\"foto\":\"atleta_2011-12-07_1789498239.jpg\",\"lugar_nacimiento\":\"Barq', '{\"doc_identidad\":\"34772516\",\"nombre\":\"Diego Alexandro\",\"apellido\":\"Reinoso Chirinos\",\"genero\":\"H\",\"fecha_nac\":\"2011-12-07\",\"telefono\":\"\",\"direccion\":\"\",\"representante\":\"1\",\"categoria\":\"7\",\"posicion\":\"3\",\"dorsal\":\"7\",\"peso_kg\":\"70\",\"estatura_cm\":\"170\",\"fot', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:11:03', 1),
(307, 100, 'Modificó al Atleta: 34772516 - Diego Alexandro Reinoso Chirinos', '{\"nombres\":\"Diego Alexandro\",\"apellidos\":\"Reinoso Chirinos\",\"p_nombre\":\"Diego\",\"s_nombre\":\"Alexandro\",\"p_apellidos\":\"Reinoso\",\"s_apellidos\":\"Chirinos\",\"genero\":\"H\",\"fecha_nac\":\"2011-12-07\",\"foto\":\"atleta_2011-12-07_1789499463.jpg\",\"lugar_nacimiento\":\"Barq', '{\"doc_identidad\":\"34772516\",\"nombre\":\"Diego Alexandro\",\"apellido\":\"Reinoso Chirinos\",\"genero\":\"H\",\"fecha_nac\":\"2011-12-07\",\"telefono\":\"\",\"direccion\":\"\",\"representante\":\"1\",\"categoria\":\"7\",\"posicion\":\"3\",\"dorsal\":\"7\",\"peso_kg\":\"70\",\"estatura_cm\":\"170\",\"fot', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:11:19', 1),
(308, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:12:03', 1),
(309, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:13:05', 1),
(310, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:13:37', 1),
(311, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:13:48', 1),
(312, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Diego Alexandro Reinoso Chirinos', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:13:58', 1),
(313, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:17:44', 1),
(314, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:21:40', 1),
(315, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Diego Alexandro Reinoso Chirinos', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:21:49', 1),
(316, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:33:27', 1),
(317, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:33:35', 1),
(318, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 19:33:39', 1),
(319, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:08:50', 1),
(320, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:08:51', 1),
(321, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Diego Alexandro Reinoso Chirinos', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:09:50', 1),
(322, 19, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:10:52', 1),
(323, 19, 'Registró el torneo: Maracaibo 2026', '', '{\"nombre\":\"Maracaibo 2026\",\"fecha_inicio\":\"2026-10-24\",\"fecha_fin\":\"2026-10-25\",\"ubicacion\":\"Estado Zulia\",\"estatus\":\"1\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:13:37', 1),
(324, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:13:56', 1),
(325, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:14:29', 1),
(326, 105, 'Registro una participacion', '', '{\"torneo\":\"MARACAIBO 2026\",\"equipo\":\"Cannibals Lara U17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:14:54', 1),
(327, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:15:08', 1),
(328, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:15:22', 1),
(329, 22, 'Registró un palmarés grupal para: Cannibals Lara U17', '', '{\"id_premio\":1,\"id_equipo\":1,\"id_torneo\":4,\"nombre_equipo\":\"Cannibals Lara U17\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:15:58', 1),
(330, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:16:29', 1),
(331, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:16:34', 1),
(332, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:17:02', 1),
(333, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-15 21:17:20', 1),
(334, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 15:57:01', 1),
(335, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 15:59:08', 1),
(336, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 15:59:22', 1),
(337, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 15:59:25', 1),
(338, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 16:00:06', 1),
(339, 23, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 16:00:18', 1),
(340, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 16:00:31', 1),
(341, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 16:39:31', 1),
(342, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 16:39:32', 1),
(343, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 17:20:28', 1),
(344, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 17:43:26', 1),
(345, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 18:19:58', 1),
(346, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 18:21:22', 1),
(347, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 18:37:52', 1),
(348, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 18:38:08', 1),
(349, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 19:03:20', 1),
(350, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 19:09:14', 1),
(351, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:45:49', 1),
(352, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:57:34', 1),
(353, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:57:38', 1),
(354, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:57:44', 1),
(355, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:57:55', 1),
(356, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:58:00', 1),
(357, 16, 'Eliminó el artículo del catálogo ID: 1', '{\"id_catalogo\":1,\"nombre\":\"Cascos Con Rejilla Ccs\",\"stock_minimo\":1,\"Id_categoria\":1,\"codigo_posicion\":null,\"talla\":\"12\"}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:58:15', 1),
(358, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:58:17', 1),
(359, 103, 'Eliminó la categoría ID: 1', '{\"id_categoria\":1,\"nombre\":\"Proteccion\",\"tipo_talla\":\"Numerico\",\"descripcion\":\"protecciones corporales\"}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 21:58:20', 1),
(360, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:01:31', 1),
(361, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:04:49', 1),
(362, 103, 'Registró la categoría: Cascos', '', '{\"nombre\":\"Cascos\",\"descripcion\":\"\",\"tipo_talla\":\"Letras\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:05:10', 1),
(363, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:07:00', 1),
(364, 103, 'Registró la categoría: Stick ', '', '{\"nombre\":\"Stick\",\"descripcion\":\"\",\"tipo_talla\":\"Categorico\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:07:45', 1),
(365, 103, 'Registró la categoría: espinilleras', '', '{\"nombre\":\"Espinilleras\",\"descripcion\":\"\",\"tipo_talla\":\"Numerico\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:08:22', 1),
(366, 103, 'Registró la categoría: coderas', '', '{\"nombre\":\"Coderas\",\"descripcion\":\"\",\"tipo_talla\":\"Numerico\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:08:36', 1),
(367, 103, 'Registró la categoría: protecciones', '', '{\"nombre\":\"Protecciones\",\"descripcion\":\"\",\"tipo_talla\":\"Categorico\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:08:57', 1),
(368, 104, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:09:02', 1),
(369, 104, 'Registró el estado físico: Excelente', '', '{\"nombre\":\"Excelente\",\"nivel_estado\":\"1\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:09:19', 1),
(370, 104, 'Registró el estado físico: Regular', '', '{\"nombre\":\"Regular\",\"nivel_estado\":\"2\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:09:49', 1),
(371, 104, 'Registró el estado físico: dañado', '', '{\"nombre\":\"Da\\u00f1ado\",\"nivel_estado\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:10:00', 1),
(372, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:10:03', 1),
(373, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:10:06', 1),
(374, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:12:06', 1),
(375, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:12:40', 1),
(376, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:13:53', 1),
(377, 16, 'Registró un artículo en catálogo: casco con rejillas', '', '{\"nombre\":\"Casco Con Rejillas\",\"stock_minimo\":\"1\",\"id_categoria\":\"2\",\"talla\":\"S\",\"codigo_posicion\":null}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:14:22', 1),
(378, 16, 'Registró un artículo en catálogo: coderas ccs', '', '{\"nombre\":\"Coderas Ccs\",\"stock_minimo\":\"1\",\"id_categoria\":\"5\",\"talla\":\"10\",\"codigo_posicion\":null}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:15:22', 1),
(379, 16, 'Registró un artículo en catálogo: espinilleras css', '', '{\"nombre\":\"Espinilleras Css\",\"stock_minimo\":\"1\",\"id_categoria\":\"5\",\"talla\":\"10\",\"codigo_posicion\":null}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:15:43', 1),
(380, 16, 'Registró un artículo en catálogo: pads', '', '{\"nombre\":\"Pads\",\"stock_minimo\":\"1\",\"id_categoria\":\"6\",\"talla\":\"\",\"codigo_posicion\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:16:11', 1),
(381, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:18:13', 1),
(382, 16, 'Modificó artículo en catálogo ID: 5', '{\"id_catalogo\":5,\"nombre\":\"Pads\",\"stock_minimo\":1,\"Id_categoria\":6,\"codigo_posicion\":3,\"talla\":\"\"}', '{\"id_catalogo\":\"5\",\"nombre\":\"Pads\",\"stock_minimo\":\"1\",\"id_categoria\":\"6\",\"talla\":\"SENIOR\",\"codigo_posicion\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:18:32', 1),
(383, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:20:44', 1),
(384, 16, 'Modificó artículo en catálogo ID: 5', '{\"id_catalogo\":5,\"nombre\":\"Pads\",\"stock_minimo\":1,\"Id_categoria\":6,\"codigo_posicion\":3,\"talla\":\"SENIOR\"}', '{\"id_catalogo\":\"5\",\"nombre\":\"Pads\",\"stock_minimo\":\"1\",\"id_categoria\":\"6\",\"talla\":\"SENIOR\",\"codigo_posicion\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:20:57', 1),
(385, 16, 'Registró un artículo en catálogo: catcher', '', '{\"nombre\":\"Catcher\",\"stock_minimo\":\"1\",\"id_categoria\":\"6\",\"talla\":\"SENIOR\",\"codigo_posicion\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:22:26', 1),
(386, 16, 'Registró un artículo en catálogo: sticks fibra de carbono', '', '{\"nombre\":\"Sticks Fibra De Carbono\",\"stock_minimo\":\"1\",\"id_categoria\":\"3\",\"talla\":\"SENIOR\",\"codigo_posicion\":null}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:22:56', 1),
(387, 16, 'Registró un artículo en catálogo: stick de fibra de carbono', '', '{\"nombre\":\"Stick De Fibra De Carbono\",\"stock_minimo\":\"1\",\"id_categoria\":\"3\",\"talla\":\"JUVENIL\",\"codigo_posicion\":null}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:23:26', 1),
(388, 16, 'Modificó artículo en catálogo ID: 7', '{\"id_catalogo\":7,\"nombre\":\"Sticks Fibra De Carbono\",\"stock_minimo\":1,\"Id_categoria\":3,\"codigo_posicion\":null,\"talla\":\"SENIOR\"}', '{\"id_catalogo\":\"7\",\"nombre\":\"Sticks De Fibra De Carbono\",\"stock_minimo\":\"1\",\"id_categoria\":\"3\",\"talla\":\"SENIOR\",\"codigo_posicion\":null}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:23:37', 1),
(389, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:23:43', 1),
(390, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"8\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0001\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:23:53', 1),
(391, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"7\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0002\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:24:02', 1),
(392, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"8\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0003\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:24:10', 1),
(393, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"8\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0004\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:24:18', 1),
(394, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"8\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0005\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:24:33', 1),
(395, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"7\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0006\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:24:44', 1),
(396, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"5\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0007\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:24:56', 1),
(397, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"5\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0008\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:25:03', 1),
(398, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"4\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0009\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:25:12', 1),
(399, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"5\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0010\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:25:22', 1),
(400, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"6\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0011\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:25:31', 1),
(401, 15, 'Registró un nuevo artículo en inventario físico.', '', '{\"id_catalogo\":\"2\",\"id_estado\":\"1\",\"codigo_club\":\"CL-0012\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:25:38', 1),
(402, 17, 'Registró asignación del artículo ID: 1 al atleta ID: 1', '', '{\"codigo_atleta\":\"1\",\"codigo_articulo\":\"1\",\"fecha_asignacion\":\"2026-09-16\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:26:04', 1),
(403, 17, 'Falló al registrar asignación: Ocurrió un error inesperado al procesar la asignación.', '', '{\"codigo_atleta\":\"2\",\"codigo_articulo\":\"10\",\"fecha_asignacion\":\"2026-09-16\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:26:17', 1),
(404, 17, 'Falló al registrar asignación: El atleta no juega en la posición requerida para este equipamiento.', '', '{\"codigo_atleta\":\"2\",\"codigo_articulo\":\"10\",\"fecha_asignacion\":\"2026-09-16\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:30:19', 1),
(405, 17, 'Registró asignación del artículo ID: 10 al atleta ID: 1', '', '{\"codigo_atleta\":\"1\",\"codigo_articulo\":\"10\",\"fecha_asignacion\":\"2026-09-16\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:30:27', 1),
(406, 17, 'Registró asignación del artículo ID: 7 al atleta ID: 1', '', '{\"codigo_atleta\":\"1\",\"codigo_articulo\":\"7\",\"fecha_asignacion\":\"2026-09-16\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:30:37', 1),
(407, 17, 'Anuló asignación ID: 3', '{\"id_asignacion\":3,\"codigo_atleta\":1,\"codigo_articulo\":7,\"fecha_asignacion\":\"2026-09-16\",\"estatus\":1}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:30:52', 1),
(408, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:31:01', 1),
(409, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:31:37', 1),
(410, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:31:40', 1),
(411, 17, 'Registró asignación del artículo ID: 3 al atleta ID: 2', '', '{\"codigo_atleta\":\"2\",\"codigo_articulo\":\"3\",\"fecha_asignacion\":\"2026-09-16\",\"estatus\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:35:45', 1),
(412, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:36:03', 1),
(413, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:36:24', 1),
(414, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:36:33', 1),
(415, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:37:02', 1),
(416, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:38:29', 1),
(417, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:38:35', 1),
(418, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:38:45', 1),
(419, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:40:26', 1),
(420, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:40:40', 1),
(421, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:46:41', 1),
(422, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:46:51', 1),
(423, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-16 22:47:02', 1),
(424, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:50:57', 1),
(425, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:53:44', 1),
(426, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:53:53', 1),
(427, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:54:04', 1),
(428, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:54:13', 1),
(429, 23, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:54:18', 1),
(430, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:54:22', 1),
(431, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:59:24', 1),
(432, 9, 'Generó reporte de representantes en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 17:59:45', 1),
(433, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:01:23', 1),
(434, 100, 'Generó reporte de atletas en formato PDF.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:02:08', 1),
(435, 100, 'Generó reporte de atletas en formato PDF.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:02:20', 1),
(436, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:02:29', 1),
(437, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:02:46', 1),
(438, 10, 'Generó reporte de posiciones en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:03:04', 1),
(439, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:03:15', 1),
(440, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:03:32', 1),
(441, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:08:42', 1),
(442, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:11:04', 1),
(443, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:12:03', 1),
(444, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:12:17', 1),
(445, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:12:19', 1),
(446, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:12:21', 1),
(447, 100, 'Generó reporte de atletas en formato PDF.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:14:20', 1),
(448, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:14:29', 1),
(449, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:14:34', 1),
(450, 11, 'Generó reporte de categorías en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:14:51', 1),
(451, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:15:06', 1),
(452, 12, 'Generó reporte de cuentas por cobrar en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:15:13', 1),
(453, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:17:02', 1),
(454, 1, 'Ingreso a Editar Perfil', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:17:54', 1),
(455, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:18:01', 1),
(456, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:18:15', 1),
(457, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:19:56', 1),
(458, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:20:19', 1),
(459, 10, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:20:21', 1),
(460, 11, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:20:24', 1),
(461, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:20:30', 1),
(462, 112, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:20:34', 1),
(463, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:21:48', 1),
(464, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:21:59', 1),
(465, 106, 'Generó el respaldo: backup_cannibalsbd2_2026-09-17_14-22-03.sql', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:22:07', 1),
(466, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:22:10', 1),
(467, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:26:14', 1),
(468, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:29:12', 1),
(469, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 18:29:20', 1),
(470, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 19:46:37', 1),
(471, 9, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 20:30:30', 1),
(472, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-09-17 21:01:05', 1);

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
(1, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-11 10:06:27', 2),
(2, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-11 10:06:27', 2),
(3, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-09-11 10:06:27', 2),
(4, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-09-11 10:06:27', 2),
(5, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-11 10:06:27', 2),
(6, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-11 10:06:27', 2),
(7, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-11 10:06:27', 2),
(8, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-11 10:06:27', 2),
(9, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-11 10:06:27', 2),
(10, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-11 10:06:27', 2),
(11, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-11 10:06:27', 2),
(12, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-11 10:06:27', 2),
(13, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-31.', 2, '2026-09-11 10:06:27', 2),
(14, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-31.', 2, '2026-09-11 10:06:27', 2),
(15, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-11 10:06:27', 2),
(16, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-07.', 2, '2026-09-11 10:06:27', 2),
(17, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-11 10:06:27', 2),
(18, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-11). Bolivar (Bs): 832.4883 | Euro (€): 0.8608', 3, '2026-09-11 10:06:29', 2),
(19, 1, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-11 10:06:29', 2),
(20, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-11). Bolivar (Bs): 832.4800 | Euro (€): 0.8600', 3, '2026-09-11 10:14:50', 2),
(21, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-14 17:22:07', 2),
(22, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-09-14 17:22:07', 2),
(23, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-09-14 17:22:07', 2),
(24, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-14 17:22:07', 2),
(25, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-14 17:22:07', 2),
(26, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-14 17:22:07', 2),
(27, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-14 17:22:07', 2),
(28, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-14 17:22:07', 2),
(29, 1, 'Cargo Atrasado', 'Cargo atrasado de Pablo Perez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-11.', 2, '2026-09-14 17:22:07', 2),
(30, 1, 'Cargo Atrasado', 'Cargo atrasado de Pablo Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-11.', 2, '2026-09-14 17:22:07', 2),
(31, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-14 17:22:07', 2),
(32, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-14 17:22:07', 2),
(33, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-31.', 2, '2026-09-14 17:22:07', 2),
(34, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-31.', 2, '2026-09-14 17:22:07', 2),
(35, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-14 17:22:07', 2),
(36, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-07.', 2, '2026-09-14 17:22:07', 2),
(37, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-14 17:22:07', 2),
(38, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-11.', 2, '2026-09-14 17:22:07', 2),
(39, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-14). Bolivar (Bs): 842.2067 | Euro (€): 0.8623', 3, '2026-09-14 17:22:10', 2),
(40, 1, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-14 17:22:10', 2),
(41, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-14). Bolivar (Bs): 842.2000 | Euro (€): 0.8600', 3, '2026-09-14 17:23:45', 2),
(42, 1, 'Cargo Atrasado', 'Cargo atrasado de Gdfgfdg Dfgdfg por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-14.', 2, '2026-09-15 12:27:45', 2),
(43, 12, 'Cargo Atrasado', 'Cargo atrasado de Gdfgfdg Dfgdfg por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-14.', 2, '2026-09-15 12:27:45', 1),
(44, 1, 'Cargo Atrasado', 'Cargo atrasado de Gdfgfdg Dfgdfg por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-14.', 2, '2026-09-15 12:27:45', 2),
(45, 12, 'Cargo Atrasado', 'Cargo atrasado de Gdfgfdg Dfgdfg por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-14.', 2, '2026-09-15 12:27:45', 1),
(46, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:45', 2),
(47, 12, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:45', 1),
(48, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-09-15 12:27:45', 2),
(49, 12, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-09-15 12:27:45', 1),
(50, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-09-15 12:27:45', 2),
(51, 12, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-09-15 12:27:45', 1),
(52, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:45', 2),
(53, 12, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:45', 1),
(54, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:45', 2),
(55, 12, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:45', 1),
(56, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 2),
(57, 12, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 1),
(58, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:46', 2),
(59, 12, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:46', 1),
(60, 1, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 2),
(61, 12, 'Cargo Atrasado', 'Cargo atrasado de Moises Torrellas por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 1),
(62, 1, 'Cargo Atrasado', 'Cargo atrasado de Pablo Perez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-11.', 2, '2026-09-15 12:27:46', 2),
(63, 12, 'Cargo Atrasado', 'Cargo atrasado de Pablo Perez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-11.', 2, '2026-09-15 12:27:46', 1),
(64, 1, 'Cargo Atrasado', 'Cargo atrasado de Pablo Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-11.', 2, '2026-09-15 12:27:46', 2),
(65, 12, 'Cargo Atrasado', 'Cargo atrasado de Pablo Perez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-11.', 2, '2026-09-15 12:27:46', 1),
(66, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 2),
(67, 12, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 1),
(68, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:46', 2),
(69, 12, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:46', 1),
(70, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-31.', 2, '2026-09-15 12:27:46', 2),
(71, 12, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-31.', 2, '2026-09-15 12:27:46', 1),
(72, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-31.', 2, '2026-09-15 12:27:46', 2),
(73, 12, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-31.', 2, '2026-09-15 12:27:46', 1),
(74, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 2),
(75, 12, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-04.', 2, '2026-09-15 12:27:46', 1),
(76, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-07.', 2, '2026-09-15 12:27:46', 2),
(77, 12, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-07.', 2, '2026-09-15 12:27:46', 1),
(78, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:46', 2),
(79, 12, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-10.', 2, '2026-09-15 12:27:46', 1),
(80, 1, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-11.', 2, '2026-09-15 12:27:46', 2),
(81, 12, 'Cargo Atrasado', 'Cargo atrasado de Sdsfdsdf Sdfsdfs por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-09-11.', 2, '2026-09-15 12:27:46', 1),
(82, 1, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-15 12:27:46', 2),
(83, 12, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-15 12:27:46', 1),
(84, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-15). Bolívar (Bs): 842.2000', 3, '2026-09-15 17:14:20', 2),
(85, 12, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-15). Bolívar (Bs): 842.2000', 3, '2026-09-15 17:14:20', 1),
(86, 1, 'Alerta de Inventario', '⚠️ El artículo \'Cascos Con Rejilla Ccs (Talla: 12)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-09-15 19:10:22', 2),
(87, 12, 'Alerta de Inventario', '⚠️ El artículo \'Cascos Con Rejilla Ccs (Talla: 12)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-09-15 19:10:22', 1),
(88, 1, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 2),
(89, 12, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 1),
(90, 1, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 2),
(91, 12, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 1),
(92, 1, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 2),
(93, 12, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 1),
(94, 1, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 2),
(95, 12, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-16 15:56:59', 1),
(96, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-16). Bolívar (Bs): 846.5131', 5, '2026-09-16 15:57:00', 2),
(97, 12, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-16). Bolívar (Bs): 846.5131', 5, '2026-09-16 15:57:00', 1),
(98, 1, 'Alerta de Inventario', '⚠️ El artículo \'Cascos Con Rejilla Ccs (Talla: 12)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-09-16 15:57:01', 2),
(99, 12, 'Alerta de Inventario', '⚠️ El artículo \'Cascos Con Rejilla Ccs (Talla: 12)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-09-16 15:57:01', 1),
(100, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-16). Bolívar (Bs): 846.5100', 5, '2026-09-16 18:19:58', 2),
(101, 12, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-09-16). Bolívar (Bs): 846.5100', 5, '2026-09-16 18:19:58', 1),
(102, 1, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 2),
(103, 12, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 1),
(104, 1, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 2),
(105, 12, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 1),
(106, 1, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 2),
(107, 12, 'Cargo Atrasado', 'Cargo atrasado de Diego Reinoso por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 1),
(108, 1, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 2),
(109, 12, 'Cargo Atrasado', 'Cargo atrasado de Andrea Chirinos por \'Inscripción\'. Saldo pendiente: 25.00. Fecha emisión: 2026-09-15.', 2, '2026-09-17 17:50:57', 1),
(110, 1, 'Alerta de Inventario', '⚠️ El artículo \'Casco Con Rejillas (Talla: S)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 2),
(111, 12, 'Alerta de Inventario', '⚠️ El artículo \'Casco Con Rejillas (Talla: S)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 1),
(112, 1, 'Alerta de Inventario', '⚠️ El artículo \'Coderas Ccs (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 2),
(113, 12, 'Alerta de Inventario', '⚠️ El artículo \'Coderas Ccs (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 1),
(114, 1, 'Alerta de Inventario', '⚠️ El artículo \'Espinilleras Css (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 2),
(115, 12, 'Alerta de Inventario', '⚠️ El artículo \'Espinilleras Css (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 1),
(116, 1, 'Alerta de Inventario', '⚠️ El artículo \'Catcher (Talla: SENIOR)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 2),
(117, 12, 'Alerta de Inventario', '⚠️ El artículo \'Catcher (Talla: SENIOR)\' ha alcanzado su stock mínimo. Stock disponible: 1 / Mínimo: 1.', 4, '2026-09-17 17:50:57', 1);

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
(135, 17, 'Generar Reporte Asignacion', 'generar_asignaciones', 'Sin Descripción ', 1),
(136, 106, 'Ingresar A Mantenimiento De La Bd', 'ingresar_respaldos', 'Sin Descripción ', 1),
(137, 106, 'Crear Respaldo', 'registrar_respaldo', 'Sin Descripción ', 1),
(138, 106, 'Restaurar Base De Datos', 'modificar_respaldo', 'Sin Descripción ', 1),
(139, 106, 'Eliminar Respaldo', 'eliminar_respaldo', 'Sin Descripción ', 1);

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
(1, 63, 6),
(2, 64, 6),
(3, 65, 6),
(4, 66, 6),
(5, 67, 6);

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

--
-- Volcado de datos para la tabla `respaldos`
--

INSERT INTO `respaldos` (`id_respaldo`, `id_usuario`, `nombre_archivo`, `peso`, `fecha_creacion`, `estatus`) VALUES
(1, 1, 'backup_cannibalsbd2_2026-09-17_14-22-03.sql', '71.92 KB', '2026-09-17 14:22:07', 1);

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
(5, 'Soporte', 'Rol Para Soporte', 1, 1),
(6, 'Entrenador', 'El Que Entrena', 3, 1);

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
(1, '12345678', 'Admin', 'Admin', 'user_12345678_1783874906.jpg', '1234-5678909', '$2y$10$wX2681v1JKAWgLVNC4ILleAltRb1SSikv2T1aMknanUrC2.Vo3Y3i', 'admin@gmail.com', 1, '2026-09-17 14:19:56', 0, 1, 1),
(12, '29506932', 'Moises', 'Torrellas', 'default.png', '0412-0565231', '$2y$10$9bWzrrjb5Er1IKPmSW5lIuT/PjRXHOZ4AL.ZibwMOTZeBt91/0ZEy', 'moitcj@gmail.com', 6, '2026-09-14 13:43:19', 3, 1, 2);

--
-- Disparadores `usuarios`
--
DROP TRIGGER IF EXISTS `disparador_despues_insertar_usuario`;
DELIMITER $$
CREATE TRIGGER `disparador_despues_insertar_usuario` AFTER INSERT ON `usuarios` FOR EACH ROW BEGIN
    DECLARE v_nombre_rol VARCHAR(35);
    
    -- Obtenemos el nombre del rol para que sea legible
    SELECT nombre_rol INTO v_nombre_rol FROM roles WHERE id_rol = NEW.id_rol LIMIT 1;
    
    INSERT INTO bitacora (id_modulo, acciones, datos_previos, datos_nuevos, entorno, idUsuario)
    VALUES (
        1, -- Asumiendo que 1 es el módulo de Seguridad
        CONCAT('Registro al usuario: ', NEW.cedulaUsuario, ' - ', NEW.nombreUsuario, ' ', NEW.apellidoUsuario),
        'No Aplica',
        CONCAT('Cédula: ', NEW.cedulaUsuario, ', Nombre: ', NEW.nombreUsuario, ' ', NEW.apellidoUsuario, ', Correo: ', NEW.correo, ', Teléfono: ', NEW.telefonoUsuario, ', Rol: ', IFNULL(v_nombre_rol, 'Desconocido')),
        'Base de Datos',
        IFNULL(@usuario_actual, NEW.idUsuario) -- Se toma el usuario de la sesión de BD o el creado si es el primero
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_consulta_bitacora`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_consulta_bitacora`;
CREATE TABLE `vista_consulta_bitacora` (
`id_bitacora` int(11)
,`nombreUsuario` varchar(35)
,`apellidoUsuario` varchar(35)
,`cedulaUsuario` varchar(10)
,`nombre_modulo` varchar(50)
,`icono` varchar(25)
,`acciones` varchar(255)
,`datos_previos` varchar(255)
,`datos_nuevos` varchar(255)
,`entorno` varchar(50)
,`fecha` date
,`hora` time
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_consulta_permisos`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_consulta_permisos`;
CREATE TABLE `vista_consulta_permisos` (
`id_permiso` int(11)
,`nombre_permiso` varchar(100)
,`clave` varchar(50)
,`descripcion` varchar(100)
,`estatus_permiso` tinyint(4)
,`id_modulo` int(11)
,`nombre_modulo` varchar(50)
,`estatus_modulo` tinyint(4)
,`icono` varchar(25)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_consulta_usuarios`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_consulta_usuarios`;
CREATE TABLE `vista_consulta_usuarios` (
`idUsuario` int(11)
,`cedulaUsuario` varchar(10)
,`nombreUsuario` varchar(35)
,`apellidoUsuario` varchar(35)
,`foto` varchar(255)
,`telefonoUsuario` varchar(15)
,`correo` varchar(60)
,`id_rol` int(11)
,`bloqueo` tinyint(4)
,`nombre_rol` varchar(35)
,`ultimo_ingreso` datetime
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_consulta_bitacora`
--
DROP TABLE IF EXISTS `vista_consulta_bitacora`;

DROP VIEW IF EXISTS `vista_consulta_bitacora`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_consulta_bitacora`  AS SELECT `b`.`id_bitacora` AS `id_bitacora`, `u`.`nombreUsuario` AS `nombreUsuario`, `u`.`apellidoUsuario` AS `apellidoUsuario`, `u`.`cedulaUsuario` AS `cedulaUsuario`, `m`.`nombre_modulo` AS `nombre_modulo`, `m`.`icono` AS `icono`, `b`.`acciones` AS `acciones`, `b`.`datos_previos` AS `datos_previos`, `b`.`datos_nuevos` AS `datos_nuevos`, `b`.`entorno` AS `entorno`, cast(`b`.`fecha_hora` as date) AS `fecha`, cast(`b`.`fecha_hora` as time) AS `hora` FROM ((`bitacora` `b` join `usuarios` `u` on(`u`.`idUsuario` = `b`.`idUsuario`)) join `modulos` `m` on(`m`.`id_modulo` = `b`.`id_modulo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_consulta_permisos`
--
DROP TABLE IF EXISTS `vista_consulta_permisos`;

DROP VIEW IF EXISTS `vista_consulta_permisos`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_consulta_permisos`  AS SELECT `p`.`id_permiso` AS `id_permiso`, `p`.`nombre` AS `nombre_permiso`, `p`.`clave` AS `clave`, `p`.`descripcion` AS `descripcion`, `p`.`estatus` AS `estatus_permiso`, `m`.`id_modulo` AS `id_modulo`, `m`.`nombre_modulo` AS `nombre_modulo`, `m`.`estatus` AS `estatus_modulo`, `m`.`icono` AS `icono` FROM (`permisos` `p` join `modulos` `m` on(`p`.`id_modulo` = `m`.`id_modulo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_consulta_usuarios`
--
DROP TABLE IF EXISTS `vista_consulta_usuarios`;

DROP VIEW IF EXISTS `vista_consulta_usuarios`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_consulta_usuarios`  AS SELECT `u`.`idUsuario` AS `idUsuario`, `u`.`cedulaUsuario` AS `cedulaUsuario`, `u`.`nombreUsuario` AS `nombreUsuario`, `u`.`apellidoUsuario` AS `apellidoUsuario`, `u`.`foto` AS `foto`, `u`.`telefonoUsuario` AS `telefonoUsuario`, `u`.`correo` AS `correo`, `u`.`id_rol` AS `id_rol`, `u`.`bloqueo` AS `bloqueo`, `r`.`nombre_rol` AS `nombre_rol`, `u`.`ultimo_ingreso` AS `ultimo_ingreso` FROM (`usuarios` `u` join `roles` `r` on(`r`.`id_rol` = `u`.`id_rol`)) WHERE `u`.`estatus` <> 0 ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `idUsuario` (`idUsuario`),
  ADD KEY `id_modulo` (`id_modulo`),
  ADD KEY `indice_bitacora_fecha` (`fecha_hora`);

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
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `indice_usuarios_rol_estatus` (`id_rol`,`estatus`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bitacora`
--
ALTER TABLE `bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=473;

--
-- AUTO_INCREMENT de la tabla `excepciones`
--
ALTER TABLE `excepciones`
  MODIFY `id_excepcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  MODIFY `id_respaldo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
