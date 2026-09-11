-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-09-2026 a las 03:27:08
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

DELIMITER $$
--
-- Procedimientos
--
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `excepciones`
--

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

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` tinyint(4) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `estatus` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

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

CREATE TABLE `permisos_rol` (
  `id_permiso_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respaldos`
--

CREATE TABLE `respaldos` (
  `id_respaldo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_archivo` varchar(100) NOT NULL,
  `peso` varchar(20) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estatus` tinyint(2) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

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
(5, 'Soporte', 'Rol Para Soporte', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

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
(1, '12345678', 'Admin', 'Admin', 'user_12345678_1783874906.jpg', '1234-5678909', '$2y$10$wX2681v1JKAWgLVNC4ILleAltRb1SSikv2T1aMknanUrC2.Vo3Y3i', 'admin@gmail.com', 1, '2026-07-12 13:15:55', 0, 1, 1);

--
-- Disparadores `usuarios`
--
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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_consulta_bitacora`  AS SELECT `b`.`id_bitacora` AS `id_bitacora`, `u`.`nombreUsuario` AS `nombreUsuario`, `u`.`apellidoUsuario` AS `apellidoUsuario`, `u`.`cedulaUsuario` AS `cedulaUsuario`, `m`.`nombre_modulo` AS `nombre_modulo`, `m`.`icono` AS `icono`, `b`.`acciones` AS `acciones`, `b`.`datos_previos` AS `datos_previos`, `b`.`datos_nuevos` AS `datos_nuevos`, `b`.`entorno` AS `entorno`, cast(`b`.`fecha_hora` as date) AS `fecha`, cast(`b`.`fecha_hora` as time) AS `hora` FROM ((`bitacora` `b` join `usuarios` `u` on(`u`.`idUsuario` = `b`.`idUsuario`)) join `modulos` `m` on(`m`.`id_modulo` = `b`.`id_modulo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_consulta_permisos`
--
DROP TABLE IF EXISTS `vista_consulta_permisos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_consulta_permisos`  AS SELECT `p`.`id_permiso` AS `id_permiso`, `p`.`nombre` AS `nombre_permiso`, `p`.`clave` AS `clave`, `p`.`descripcion` AS `descripcion`, `p`.`estatus` AS `estatus_permiso`, `m`.`id_modulo` AS `id_modulo`, `m`.`nombre_modulo` AS `nombre_modulo`, `m`.`estatus` AS `estatus_modulo`, `m`.`icono` AS `icono` FROM (`permisos` `p` join `modulos` `m` on(`p`.`id_modulo` = `m`.`id_modulo`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_consulta_usuarios`
--
DROP TABLE IF EXISTS `vista_consulta_usuarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_consulta_usuarios`  AS SELECT `u`.`idUsuario` AS `idUsuario`, `u`.`cedulaUsuario` AS `cedulaUsuario`, `u`.`nombreUsuario` AS `nombreUsuario`, `u`.`apellidoUsuario` AS `apellidoUsuario`, `u`.`foto` AS `foto`, `u`.`telefonoUsuario` AS `telefonoUsuario`, `u`.`correo` AS `correo`, `u`.`id_rol` AS `id_rol`, `u`.`bloqueo` AS `bloqueo`, `r`.`nombre_rol` AS `nombre_rol`, `u`.`ultimo_ingreso` AS `ultimo_ingreso` FROM (`usuarios` `u` join `roles` `r` on(`r`.`id_rol` = `u`.`id_rol`)) WHERE `u`.`estatus` <> 0 ;

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
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  MODIFY `id_permiso_rol` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `respaldos`
--
ALTER TABLE `respaldos`
  MODIFY `id_respaldo` int(11) NOT NULL AUTO_INCREMENT;

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
