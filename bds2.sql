-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-08-2026 a las 23:23:57
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
(1, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:04:21', 3),
(2, 1, 'Registro al usuario: 28456123 - Pedro Perez', 'No Aplica', 'Rol Asignado: 4', 'Base de Datos', '2026-08-29 00:05:07', 3),
(3, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:06:13', 3),
(4, 2, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:07:44', 3),
(5, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:07:46', 3),
(6, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:09:47', 3),
(7, 112, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:09:54', 3),
(8, 109, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:09:56', 3),
(9, 112, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:10:01', 3),
(10, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:10:03', 3),
(11, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:13:57', 3),
(12, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:17:34', 3),
(13, 1, 'Registro al usuario: 20789456 - Rosa Perez', 'No Aplica', 'Cédula: 20789456, Nombre: Rosa Perez, Correo: rosa@gmail.com, Teléfono: 0416-0526525, Rol: Contador', 'Base de Datos', '2026-08-29 00:18:32', 3),
(14, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:18:34', 3),
(15, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:20:32', 3),
(16, 106, 'Generó el respaldo: backup_cannibalsbd2_2026-08-28_20-21-05.sql', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:21:07', 3),
(17, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:21:41', 3),
(18, 112, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:21:44', 3),
(19, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:21:46', 3),
(20, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:21:48', 3),
(21, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:23:56', 3),
(22, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:24:47', 3),
(23, 106, 'Generó el respaldo: backup_cannibalsbd2_2026-08-28_20-24-50.sql', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:24:51', 3),
(24, 3, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:24:57', 3),
(25, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:25:05', 3),
(26, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:25:11', 3),
(27, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:25:48', 3),
(28, 1, 'Ingreso a Editar Perfil', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:26:02', 3),
(29, 1, 'Actualizó su información personal de perfil.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:26:15', 3),
(30, 1, 'Ingreso a Editar Perfil', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:26:17', 3),
(31, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:28:31', 3),
(32, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 00:28:52', 3),
(33, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:26:31', 3),
(34, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:27:12', 3),
(35, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:27:14', 3),
(36, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:27:20', 3),
(37, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:29:34', 3),
(38, 12, 'Generó reporte de cuentas por cobrar en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:30:11', 3),
(39, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:31:02', 3),
(40, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:49:13', 3),
(41, 13, 'Generó reporte de Pagos en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:55:08', 3),
(42, 13, 'Generó reporte de Pagos en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:55:47', 3),
(43, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:02', 3),
(44, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:03', 3),
(45, 102, 'Selecciono la moneda: 2', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:08', 3),
(46, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:11', 3),
(47, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:14', 3),
(48, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:16', 3),
(49, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:31', 3),
(50, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:57:48', 3),
(51, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:58:00', 3),
(52, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:58:09', 3),
(53, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:58:12', 3),
(54, 12, 'Anuló el Cargo: Inscripcion De Jose Lopez y la fecha 2026-08-28', '{\"id_atleta\":10,\"id_concepto\":2,\"fecha_emision\":\"2026-08-28\",\"fecha_vencimiento\":\"2026-09-07\",\"monto_total\":\"25.00\",\"monto_personalizado\":\"25.00\",\"monto_pendiente\":\"25.00\",\"estatus\":1,\"multado\":0,\"estatus_texto\":\"Pendiente\",\"atleta_nombre\":\"Jose\",\"atleta_', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:59:15', 3),
(55, 12, 'Anuló el Cargo: Mensualidad De Jose Lopez y la fecha 2026-08-28', '{\"id_atleta\":10,\"id_concepto\":1,\"fecha_emision\":\"2026-08-28\",\"fecha_vencimiento\":\"2026-09-02\",\"monto_total\":\"30.00\",\"monto_personalizado\":\"30.00\",\"monto_pendiente\":\"30.00\",\"estatus\":1,\"multado\":0,\"estatus_texto\":\"Pendiente\",\"atleta_nombre\":\"Jose\",\"atleta_', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:59:19', 3),
(56, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:59:29', 3),
(57, 100, 'Retiró al Atleta: 29506933 - Jose Lopez', '{\"id_atleta\":10,\"nombres\":\"Jose\",\"apellidos\":\"Lopez\",\"p_nombre\":\"Jose\",\"s_nombre\":\"\",\"p_apellidos\":\"Lopez\",\"s_apellidos\":\"\",\"genero\":\"H\",\"fecha_nac\":\"2006-07-20\",\"foto\":\"atleta_2006-07-20_1787947857.jpg\",\"lugar_nacimiento\":\"El Tocuyo\",\"doc_identidad\":\"295', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:59:45', 3),
(58, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 19:59:52', 3),
(59, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:00:02', 3),
(60, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:14:09', 3),
(61, 13, 'Registro de Pago por el monto de 26', '', '{\"monto\":26,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":0.001258}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:15:07', 3),
(62, 13, 'Anuló el Pago por el monto de $ 26.00 Motivo: porque si', '{\"monto\":\"26.00\",\"fecha\":\"2026-08-29\",\"referencia\":\"\",\"metodo\":\"Efectivo\",\"moneda\":\"$\"}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:15:56', 3),
(63, 13, 'Registro de Pago por el monto de 30', '', '{\"monto\":30,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":0.001258}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:16:36', 3),
(64, 13, 'Registro de vuelto para el pago: 53', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:19:18', 3),
(65, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:20:46', 3),
(66, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:20:48', 3),
(67, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:20:59', 3),
(68, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:22:39', 3),
(69, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:22:42', 3),
(70, 13, 'Anuló el Pago por el monto de $ 30.00 Motivo: sdasd', '{\"monto\":\"30.00\",\"fecha\":\"2026-08-29\",\"referencia\":\"\",\"metodo\":\"Efectivo\",\"moneda\":\"$\"}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:22:52', 3),
(71, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:23:13', 3),
(72, 13, 'Registro de Pago por el monto de 30', '', '{\"monto\":30,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:23:45', 3),
(73, 13, 'Registro de Pago por el monto de 4000', '', '{\"monto\":4000,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":794.9917}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:25:03', 3),
(74, 13, 'Registro de vuelto para el pago: 57', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:26:25', 3),
(75, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:29:47', 3),
(76, 13, 'Registro de Pago por el monto de 20000', '', '{\"monto\":20000,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":794.99}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:30:53', 3),
(77, 13, 'Registro de vuelto para el pago: 58', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:33:13', 3),
(78, 13, 'Registro de Pago por el monto de 8', '', '{\"monto\":8,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":1}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:33:40', 3),
(79, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:34:34', 3),
(80, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:34:54', 3),
(81, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:36:08', 3),
(82, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:36:24', 3),
(83, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:36:52', 3),
(84, 13, 'Registro de Pago por el monto de 4000', '', '{\"monto\":4000,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":794.99}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:38:12', 3),
(85, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:43:09', 3),
(86, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:43:11', 3),
(87, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:43:13', 3),
(88, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:43:37', 3),
(89, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:43:56', 3),
(90, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:43:57', 3),
(91, 102, 'Selecciono la moneda: 1', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:44:00', 3),
(92, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:44:04', 3),
(93, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:44:24', 3),
(94, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:44:26', 3),
(95, 102, 'Selecciono la moneda: 2', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:44:29', 3),
(96, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:45:07', 3),
(97, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:49:09', 3),
(98, 13, 'Registro de Pago por el monto de 4000', '', '{\"monto\":4000,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":794.99}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:49:36', 3),
(99, 13, 'Registro de Pago por el monto de 4000', '', '{\"monto\":4000,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":794.99}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:50:14', 3),
(100, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:50:30', 3),
(101, 110, 'Sincronizó tasa de cambio para la moneda: Dolar ($)', '', '{\"moneda\":\"Dolar ($)\",\"tasa\":\"1\",\"fecha\":\"2026-08-29\",\"tipo\":\"automatica\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:50:40', 3),
(102, 110, 'Sincronizó tasa de cambio para la moneda: Bolivar (Bs)', '', '{\"moneda\":\"Bolivar (Bs)\",\"tasa\":\"794.9917\",\"fecha\":\"2026-08-29\",\"tipo\":\"automatica\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:50:51', 3),
(103, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:50:59', 3),
(104, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:51:03', 3),
(105, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:54:56', 3),
(106, 110, 'Sincronizó tasa de cambio para la moneda: Bolivar (Bs)', '', '{\"moneda\":\"Bolivar (Bs)\",\"tasa\":\"794.9917\",\"fecha\":\"2026-08-29\",\"tipo\":\"automatica\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 20:55:03', 3),
(107, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:02:16', 3),
(108, 110, 'Sincronizó tasa de cambio para la moneda: Bolivar (Bs)', '', '{\"moneda\":\"Bolivar (Bs)\",\"tasa\":\"794.99\",\"fecha\":\"2026-08-29\",\"tipo\":\"automatica\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:02:25', 3),
(109, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:05:05', 3),
(110, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:05:07', 3),
(111, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:15:58', 3),
(112, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:16:07', 3),
(113, 13, 'Registro de Pago por el monto de 4000', '', '{\"monto\":4000,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":794.99}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:16:41', 3),
(114, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:28:44', 3),
(115, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:29:06', 3),
(116, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:29:07', 3),
(117, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:29:07', 3),
(118, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:35:35', 3),
(119, 13, 'Registro de vuelto para el pago: 63', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:35:59', 3),
(120, 13, 'Registro de vuelto para el pago: 62', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:38:07', 3),
(121, 13, 'Registro de vuelto para el pago: 59', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:38:37', 3),
(122, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:38:53', 3),
(123, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:38:56', 3),
(124, 14, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:39:20', 3),
(125, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:39:21', 3),
(126, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:00', 3),
(127, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:13', 3),
(128, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:18', 3),
(129, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:20', 3),
(130, 102, 'Selecciono la moneda: 1', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:24', 3),
(131, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:27', 3),
(132, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:29', 3),
(133, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:31', 3),
(134, 102, 'Selecciono la moneda: 2', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:39', 3),
(135, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:54:41', 3),
(136, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:55:38', 3),
(137, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:55:41', 3),
(138, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:58:18', 3),
(139, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:58:21', 3),
(140, 101, 'Generó reporte de Conceptos en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 21:58:37', 3),
(141, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:01:40', 3),
(142, 101, 'Registró el Concepto de Pago: nuevo monto 30.00', '', '{\"nombre\":\"nuevo monto\",\"monto\":\"30.00\",\"frecuencia\":\"L\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:02:14', 3),
(143, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:02:23', 3),
(144, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:02:35', 3),
(145, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:03:39', 3),
(146, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:04:16', 3),
(147, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:04:59', 3),
(148, 101, 'Modifico el proceso de cargo: Nuevo Monto 30.50', '{\"nombre\":\"Nuevo Monto\",\"monto\":\"30\",\"frecuencia\":\"L\",\"dias_gracia\":0,\"estatus\":1}', '{\"nombre\":\"Nuevo Monto\",\"monto\":\"30.50\",\"frecuencia\":\"L\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:05:37', 3),
(149, 101, 'Modifico el proceso de cargo: Nuevo Monto 30.50', '{\"nombre\":\"Nuevo Monto\",\"monto\":\"31.00\",\"frecuencia\":\"L\",\"dias_gracia\":0,\"estatus\":1}', '{\"nombre\":\"Nuevo Monto\",\"monto\":\"30.50\",\"frecuencia\":\"L\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:06:22', 3),
(150, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:06:45', 3),
(151, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:16:04', 3),
(152, 102, 'Registró la moneda: Euro', '', '{\"nombre\":\"Euro\",\"abreviatura\":\"EUR\",\"simbolo\":\"\\u20ac\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:16:20', 3),
(153, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:16:34', 3),
(154, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:16:58', 3),
(155, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:17:09', 3),
(156, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:17:12', 3),
(157, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:17:23', 3),
(158, 13, 'Registro de Pago por el monto de 5', '', '{\"monto\":5,\"fecha\":\"2026-08-29\",\"referencia\":\"No aplica\",\"tasa_usada\":0.86}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:19:27', 3),
(159, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:20:04', 3),
(160, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:20:07', 3),
(161, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:20:36', 3),
(162, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:22:12', 3),
(163, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:23:32', 3),
(164, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:27:09', 3),
(165, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:27:22', 3),
(166, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:29:11', 3),
(167, 101, 'Registró el Concepto de cargo: multa 10.00', '', '{\"nombre\":\"multa\",\"monto\":\"10.00\",\"frecuencia\":\"T\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:30:15', 3),
(168, 12, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:40:39', 3),
(169, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:40:55', 3),
(170, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:49:29', 3),
(171, 101, 'Modifico el proceso de cargo: Nuevo Monto 30.50', '{\"nombre\":\"Nuevo Monto\",\"monto\":\"30.50\",\"frecuencia\":\"L\",\"dias_gracia\":0,\"estatus\":1}', '{\"nombre\":\"Nuevo Monto\",\"monto\":\"30.50\",\"frecuencia\":\"L\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:49:54', 3),
(172, 101, 'Registró el Concepto de cargo: multa 30.00', '', '{\"nombre\":\"multa\",\"monto\":\"30.00\",\"frecuencia\":\"M\",\"dias\":\"0\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:50:26', 3),
(173, 101, 'Elimino el concepto de cargo: Multa', '{\"nombre\":\"Multa\",\"monto\":\"30.00\",\"frecuencia\":\"M\",\"dias_gracia\":0,\"estatus\":1}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:50:30', 3),
(174, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:53:36', 3),
(175, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:53:38', 3),
(176, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:53:40', 3),
(177, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:54:07', 3),
(178, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 22:54:17', 3),
(179, 15, 'Generó reporte del inventario de artículos en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:33:20', 3),
(180, 15, 'Generó reporte del inventario de artículos en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:33:37', 3),
(181, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:34:00', 3),
(182, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:34:21', 3),
(183, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:34:26', 3),
(184, 15, 'Modificó artículo Código: 6', '{\"codigo_articulo\":6,\"id_estado\":3,\"id_catalogo\":1,\"codigo_club\":\"CL-0003\",\"estatus\":1}', '{\"codigo_articulo\":\"6\",\"id_catalogo\":\"1\",\"id_estado\":\"3\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:34:32', 3),
(185, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:34:43', 3),
(186, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:35:13', 3),
(187, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:35:24', 3),
(188, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:35:25', 3),
(189, 101, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:35:30', 3),
(190, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:35:34', 3),
(191, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:37:00', 3),
(192, 15, 'Generó reporte del inventario de artículos en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:37:12', 3),
(193, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:38:41', 3),
(194, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:38:48', 3),
(195, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:38:51', 3),
(196, 102, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:38:56', 3),
(197, 102, 'Generó reporte de Monedas en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:39:04', 3),
(198, 102, 'Generó reporte de Monedas en PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:39:12', 3),
(199, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:39:22', 3),
(200, 15, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:49:51', 3),
(201, 15, 'Generó reporte del inventario de artículos en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:50:00', 3),
(202, 15, 'Generó reporte del inventario de artículos en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:50:10', 3),
(203, 16, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:50:17', 3),
(204, 16, 'Generó reporte del catálogo en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:56:22', 3),
(205, 16, 'Generó reporte del catálogo en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:57:49', 3),
(206, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:58:07', 3),
(207, 103, 'Generó reporte de categorías de catálogo en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-29 23:58:22', 3),
(208, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:06:49', 3),
(209, 100, 'Generó documento (ficha_tecnica) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:06:55', 3),
(210, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:07:14', 3),
(211, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:11:28', 3),
(212, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:11:35', 3),
(213, 100, 'Generó documento (ficha_tecnica) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:11:42', 3),
(214, 100, 'Generó documento (ficha_alto_rendimiento) del atleta: Moises Jesus Torrellas', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:16:12', 3),
(215, 100, 'Generó reporte de atletas en formato PDF.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:17:13', 3),
(216, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:19:19', 3),
(217, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:34:53', 3),
(218, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:39:39', 3),
(219, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:53:45', 3),
(220, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:57:36', 3),
(221, 13, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:57:43', 3),
(222, 104, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:58:57', 3),
(223, 103, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:59:01', 3),
(224, 17, 'Generó reporte de Asignaciones en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 00:59:43', 3),
(225, 17, 'Generó reporte de Asignaciones en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:01:14', 3),
(226, 17, 'Generó reporte de Asignaciones en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:03:36', 3),
(227, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:03:53', 3),
(228, 18, 'Anuló devolución de: Casco Tiplex - Atleta: Rosa Lopez (CI: 32847654)', '{\"fecha_devolucion\":\"2026-08-28\",\"observacion\":\"\",\"atleta\":\"Rosa Lopez\",\"doc_identidad\":\"32847654\",\"articulo\":\"Casco Tiplex\",\"estado_fisico\":\"Da\\u00f1ado\"}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:04:26', 3),
(229, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:04:41', 3),
(230, 18, 'Registró devolución de: Casco Tiplex - Atleta: Rosa Lopez (CI: 32847654)', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:05:03', 3),
(231, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:14:56', 3),
(232, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:15:08', 3),
(233, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:21:20', 3),
(234, 18, 'Generó reporte filtrado de devoluciones en formato EXCEL', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:22:21', 3),
(235, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:23:29', 3),
(236, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:34:17', 3),
(237, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:35:04', 3),
(238, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:35:09', 3),
(239, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:35:14', 3),
(240, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:35:19', 3),
(241, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:35:48', 3),
(242, 18, 'Generó reporte filtrado de devoluciones en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:36:26', 3),
(243, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:36:49', 3),
(244, 18, 'Generó reporte filtrado de devoluciones en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:37:13', 3),
(245, 18, 'Ingreso al Modulo de Devoluciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:43:30', 3),
(246, 18, 'Generó reporte filtrado de devoluciones en formato PDF', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 01:43:42', 3),
(247, 4, 'Inicio de sesión exitoso', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 20:41:30', 3),
(248, 110, 'Ingreso al Modulo de Tasas de Cambio', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 20:41:38', 3),
(249, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 20:59:42', 3),
(250, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 20:59:44', 3),
(251, 105, 'Genero un reporte de participaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:03:52', 3),
(252, 105, 'Genero un reporte de participaciones', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:04:19', 3),
(253, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:04:36', 3),
(254, 107, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:11:23', 3),
(255, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:11:36', 3),
(256, 105, 'Elimino una participacion', '{\"torneo\":\"SUPER TORNEO\",\"equipo\":\"U-12\"}', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:11:51', 3),
(257, 105, 'Registro una participacion', '', '{\"torneo\":\"SUPER TORNEO\",\"equipo\":\"U-12\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:11:58', 3),
(258, 105, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:15:51', 3),
(259, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:16:25', 3),
(260, 1, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:16:31', 3),
(261, 106, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:16:35', 3),
(262, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:16:52', 3),
(263, 100, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:19:57', 3),
(264, 20, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:20:08', 3),
(265, 22, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:20:40', 3),
(266, 21, 'Ingreso al Modulo', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:21:27', 3),
(267, 21, 'Modificó el Premio: Segundo Lugar (I)', '{\"tipo\":\"G\",\"nombre\":\"Segundo Lugar\"}', '{\"tipo\":\"I\",\"nombre\":\"Segundo Lugar\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:21:33', 3),
(268, 21, 'Modificó el Premio: Segundo Lugar (G)', '{\"tipo\":\"I\",\"nombre\":\"Segundo Lugar\"}', '{\"tipo\":\"G\",\"nombre\":\"Segundo Lugar\"}', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:21:38', 3),
(269, 5, 'Cierre de sesión exitoso.', '', '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb', '2026-08-30 21:22:20', 3);

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
(1, 1, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-29 19:26:30', 1),
(2, 10, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-29 19:26:30', 1),
(3, 11, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-29 19:26:30', 1),
(4, 3, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-29 19:26:30', 2),
(5, 5, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-29 19:26:30', 1),
(6, 6, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-29 19:26:30', 1),
(7, 7, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-29 19:26:30', 1),
(8, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(9, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(10, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(11, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 2),
(12, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(13, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(14, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Mensualidad\'. Saldo pendiente: 30.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(15, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Inscripcion\'. Saldo pendiente: 7.15. Fecha emisión: 2026-07-08.', 2, '2026-08-29 19:26:30', 1),
(16, 10, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Inscripcion\'. Saldo pendiente: 7.15. Fecha emisión: 2026-07-08.', 2, '2026-08-29 19:26:30', 1),
(17, 11, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Inscripcion\'. Saldo pendiente: 7.15. Fecha emisión: 2026-07-08.', 2, '2026-08-29 19:26:30', 1),
(18, 3, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Inscripcion\'. Saldo pendiente: 7.15. Fecha emisión: 2026-07-08.', 2, '2026-08-29 19:26:30', 2),
(19, 5, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Inscripcion\'. Saldo pendiente: 7.15. Fecha emisión: 2026-07-08.', 2, '2026-08-29 19:26:30', 1),
(20, 6, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Inscripcion\'. Saldo pendiente: 7.15. Fecha emisión: 2026-07-08.', 2, '2026-08-29 19:26:30', 1),
(21, 7, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Inscripcion\'. Saldo pendiente: 7.15. Fecha emisión: 2026-07-08.', 2, '2026-08-29 19:26:30', 1),
(22, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-29 19:26:30', 1),
(23, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-29 19:26:30', 1),
(24, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-29 19:26:30', 1),
(25, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-29 19:26:30', 2),
(26, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-29 19:26:30', 1),
(27, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-29 19:26:30', 1),
(28, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-29 19:26:30', 1),
(29, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-07-10.', 2, '2026-08-29 19:26:30', 1),
(30, 10, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-07-10.', 2, '2026-08-29 19:26:30', 1),
(31, 11, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-07-10.', 2, '2026-08-29 19:26:30', 1),
(32, 3, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-07-10.', 2, '2026-08-29 19:26:30', 2),
(33, 5, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-07-10.', 2, '2026-08-29 19:26:30', 1),
(34, 6, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-07-10.', 2, '2026-08-29 19:26:30', 1),
(35, 7, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-07-10.', 2, '2026-08-29 19:26:30', 1),
(36, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(37, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(38, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(39, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 2),
(40, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(41, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(42, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Lopez por \'Inscripcion\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-28.', 2, '2026-08-29 19:26:30', 1),
(43, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2004-03-18.', 2, '2026-08-29 19:26:30', 1),
(44, 10, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2004-03-18.', 2, '2026-08-29 19:26:30', 1),
(45, 11, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2004-03-18.', 2, '2026-08-29 19:26:30', 1),
(46, 3, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2004-03-18.', 2, '2026-08-29 19:26:30', 2),
(47, 5, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2004-03-18.', 2, '2026-08-29 19:26:30', 1),
(48, 6, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2004-03-18.', 2, '2026-08-29 19:26:30', 1),
(49, 7, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2004-03-18.', 2, '2026-08-29 19:26:30', 1),
(50, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-29 19:26:30', 1),
(51, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-29 19:26:30', 1),
(52, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-29 19:26:30', 1),
(53, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-29 19:26:30', 2),
(54, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-29 19:26:30', 1),
(55, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-29 19:26:30', 1),
(56, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-29 19:26:30', 1),
(57, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-16.', 2, '2026-08-29 19:26:30', 1),
(58, 10, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-16.', 2, '2026-08-29 19:26:30', 1),
(59, 11, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-16.', 2, '2026-08-29 19:26:30', 1),
(60, 3, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-16.', 2, '2026-08-29 19:26:30', 2),
(61, 5, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-16.', 2, '2026-08-29 19:26:30', 1),
(62, 6, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-16.', 2, '2026-08-29 19:26:30', 1),
(63, 7, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-16.', 2, '2026-08-29 19:26:30', 1),
(64, 1, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(65, 10, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(66, 11, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(67, 3, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 2),
(68, 5, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(69, 6, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(70, 7, 'Cargo Atrasado', 'Cargo atrasado de Maria Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(71, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(72, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(73, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(74, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 2),
(75, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(76, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(77, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-20.', 2, '2026-08-29 19:26:30', 1),
(78, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-30.', 2, '2026-08-29 19:26:30', 1),
(79, 10, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-30.', 2, '2026-08-29 19:26:30', 1),
(80, 11, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-30.', 2, '2026-08-29 19:26:30', 1),
(81, 3, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-30.', 2, '2026-08-29 19:26:30', 2),
(82, 5, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-30.', 2, '2026-08-29 19:26:30', 1),
(83, 6, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-30.', 2, '2026-08-29 19:26:30', 1),
(84, 7, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-07-30.', 2, '2026-08-29 19:26:30', 1),
(85, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(86, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(87, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(88, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 2),
(89, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(90, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(91, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(92, 1, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(93, 10, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(94, 11, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(95, 3, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 2),
(96, 5, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(97, 6, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(98, 7, 'Cargo Atrasado', 'Cargo atrasado de Rosa Lopez por \'Multa Por Demora\'. Saldo pendiente: 5.00. Fecha emisión: 2026-08-23.', 2, '2026-08-29 19:26:30', 1),
(99, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Dolar ($): 0.0013', 3, '2026-08-29 19:26:31', 1),
(100, 10, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Dolar ($): 0.0013', 3, '2026-08-29 19:26:31', 1),
(101, 11, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Dolar ($): 0.0013', 3, '2026-08-29 19:26:31', 1),
(102, 3, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Dolar ($): 0.0013', 3, '2026-08-29 19:26:31', 2),
(103, 5, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Dolar ($): 0.0013', 3, '2026-08-29 19:26:31', 1),
(104, 6, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Dolar ($): 0.0013', 3, '2026-08-29 19:26:31', 1),
(105, 7, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Dolar ($): 0.0013', 3, '2026-08-29 19:26:31', 1),
(106, 1, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-29 19:26:31', 1),
(107, 10, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-29 19:26:31', 1),
(108, 11, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-29 19:26:31', 1),
(109, 3, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-29 19:26:31', 2),
(110, 5, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-29 19:26:31', 1),
(111, 6, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-29 19:26:31', 1),
(112, 7, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-29 19:26:31', 1),
(113, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Bolivar (Bs): 794.9900 | Euro (€): 0.8614', 3, '2026-08-29 22:16:58', 1),
(114, 10, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Bolivar (Bs): 794.9900 | Euro (€): 0.8614', 3, '2026-08-29 22:16:58', 1),
(115, 11, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Bolivar (Bs): 794.9900 | Euro (€): 0.8614', 3, '2026-08-29 22:16:58', 1),
(116, 3, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Bolivar (Bs): 794.9900 | Euro (€): 0.8614', 3, '2026-08-29 22:16:58', 2),
(117, 5, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Bolivar (Bs): 794.9900 | Euro (€): 0.8614', 3, '2026-08-29 22:16:58', 1),
(118, 6, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Bolivar (Bs): 794.9900 | Euro (€): 0.8614', 3, '2026-08-29 22:16:58', 1),
(119, 7, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-29). Bolivar (Bs): 794.9900 | Euro (€): 0.8614', 3, '2026-08-29 22:16:58', 1),
(120, 1, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-30 20:41:27', 1),
(121, 10, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-30 20:41:27', 1),
(122, 11, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-30 20:41:27', 1),
(123, 3, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-30 20:41:27', 2),
(124, 5, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-30 20:41:27', 1),
(125, 6, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-30 20:41:27', 1),
(126, 7, 'Torneo Próximo', 'El torneo \'SUPER TORNEO\' comenzará pronto (2026-08-31).', 3, '2026-08-30 20:41:27', 1),
(127, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-30 20:41:27', 1),
(128, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-30 20:41:27', 1),
(129, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-30 20:41:27', 1),
(130, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-30 20:41:27', 2),
(131, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-30 20:41:27', 1),
(132, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-30 20:41:27', 1),
(133, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Inscripcion\'. Saldo pendiente: 3.86. Fecha emisión: 2026-07-09.', 2, '2026-08-30 20:41:27', 1),
(134, 1, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-30 20:41:27', 1),
(135, 10, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-30 20:41:27', 1),
(136, 11, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-30 20:41:27', 1),
(137, 3, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-30 20:41:27', 2),
(138, 5, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-30 20:41:27', 1),
(139, 6, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-30 20:41:27', 1),
(140, 7, 'Cargo Atrasado', 'Cargo atrasado de Jose Perez por \'Viaticos\'. Saldo pendiente: 25.00. Fecha emisión: 2026-08-24.', 2, '2026-08-30 20:41:27', 1),
(141, 1, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-30). Bolivar (Bs): 794.9917 | Euro (€): 0.8615', 3, '2026-08-30 20:41:30', 1),
(142, 10, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-30). Bolivar (Bs): 794.9917 | Euro (€): 0.8615', 3, '2026-08-30 20:41:30', 1),
(143, 11, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-30). Bolivar (Bs): 794.9917 | Euro (€): 0.8615', 3, '2026-08-30 20:41:30', 1),
(144, 3, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-30). Bolivar (Bs): 794.9917 | Euro (€): 0.8615', 3, '2026-08-30 20:41:30', 2),
(145, 5, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-30). Bolivar (Bs): 794.9917 | Euro (€): 0.8615', 3, '2026-08-30 20:41:30', 1),
(146, 6, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-30). Bolivar (Bs): 794.9917 | Euro (€): 0.8615', 3, '2026-08-30 20:41:30', 1),
(147, 7, 'Tasa de Cambio Actualizada', 'Las tasas de cambio fueron actualizadas automáticamente a la fecha de hoy (2026-08-30). Bolivar (Bs): 794.9917 | Euro (€): 0.8615', 3, '2026-08-30 20:41:30', 1),
(148, 1, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-30 20:41:30', 1),
(149, 10, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-30 20:41:30', 1),
(150, 11, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-30 20:41:30', 1),
(151, 3, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-30 20:41:30', 2),
(152, 5, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-30 20:41:30', 1),
(153, 6, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-30 20:41:30', 1),
(154, 7, 'Alerta de Inventario', '⚠️ El artículo \'Casco Tiplex (Talla: 10)\' ha alcanzado su stock mínimo. Stock disponible: 0 / Mínimo: 1.', 4, '2026-08-30 20:41:30', 1);

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
(16, 4, 4),
(17, 5, 4),
(18, 6, 4),
(19, 7, 4),
(20, 8, 4),
(21, 13, 4),
(22, 18, 4),
(23, 19, 4),
(24, 20, 4),
(25, 21, 4),
(26, 47, 4),
(27, 48, 4),
(28, 49, 4),
(29, 50, 4),
(30, 51, 4),
(31, 1, 4),
(32, 2, 4),
(33, 3, 4),
(34, 14, 4),
(35, 15, 4),
(36, 16, 4),
(37, 17, 4);

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
(5, 1, 'backup_cannibalsbd2_2026-08-28_20-21-05.sql', '74.5 KB', '2026-08-28 20:21:07', 1),
(6, 3, 'backup_cannibalsbd2_2026-08-28_20-24-50.sql', '74.5 KB', '2026-08-28 20:24:51', 1);

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
(4, 'Contador', 'Maneja El Area Contable Del Club', 3, 1),
(5, 'Soporte', 'Rol Para Soporte', 1, 1);

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
(1, '12345678', 'Admin', 'Admin', 'user_12345678_1783874906.jpg', '1234-5678909', '$2y$10$wX2681v1JKAWgLVNC4ILleAltRb1SSikv2T1aMknanUrC2.Vo3Y3i', 'admin@gmail.com', 1, '2026-07-12 13:15:55', 0, 1, 1),
(3, '29506932', 'Moises', 'Torrellas', 'user_29506932_1787963175.jpg', '0412-0565231', '$2y$10$aG.8xwekD3.T1Vp1oktcp.W6hG5Kztobw8QjkglaEeZAYAQyhTmbe', 'moitcj@gmail.com', 5, '2026-08-30 16:41:26', 0, 1, 1),
(5, '29517871', 'Leonardo', 'Medina', 'user_29517871_1783876738.jpg', '0426-6589382', '$2y$10$nzDUpRMt.RMrMxKm/VvZk..mAaOdcg24jhqnO0386LKj3QSYeSP4O', 'leodi0611@gmail.com', 5, NULL, 0, 1, 1),
(6, '29997994', 'Yessica', 'Melendez', 'user_29997994_1783877342.jpg', '0426-2430903', '$2y$10$NQ2l46IOYTd1DJIRvNlrFOHYlzSumMp8kPMVUK221couPGCy/bvq.', 'yessicamelendez0708@gmail.com', 5, NULL, 0, 1, 1),
(7, '29531465', 'Yonathan', 'Mogollón', 'user_29531465_1783877573.jpg', '0412-3652677', '$2y$10$jZPAhca4AS48yEuVKBrS3OltCeWQ54xdKpUNMkS/fhRHwWw.Inlqu', 'yonathanmogollon2002@gmail.com', 5, NULL, 0, 1, 1),
(10, '28456123', 'Pedro', 'Perez', 'default.png', '0214-5111545', '$2y$10$ZaGKqaFWCycN8Ue20O7eF.Wo/RZWBn9zhXS2B5mqb1auU3Stankym', 'pedro@gmail.com', 4, NULL, 0, 1, 1),
(11, '20789456', 'Rosa', 'Perez', 'default.png', '0416-0526525', '$2y$10$eqMsN9Y9z3SkZYob1Gv1s.W.U9sZOWd9U/hpThxCNcyoY0NBciWH2', 'rosa@gmail.com', 4, NULL, 0, 1, 1);

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
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT de la tabla `excepciones`
--
ALTER TABLE `excepciones`
  MODIFY `id_excepcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  MODIFY `id_respaldo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
